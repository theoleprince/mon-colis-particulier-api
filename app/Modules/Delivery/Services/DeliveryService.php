<?php

namespace App\Modules\Delivery\Services;

use App\Models\User;
use App\Modules\Delivery\Enums\DeliveryStatus;
use App\Modules\Delivery\Enums\PaymentMethod;
use App\Modules\Delivery\Models\Delivery;
use App\Modules\Delivery\Models\DeliveryQuote;
use App\Modules\Delivery\Models\PackageNature;
use App\Shared\Exceptions\BusinessException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliveryService
{
    /** Tolerance between the quoted points and the delivery points (map pin nudged...). */
    private const MAX_POINT_DRIFT_KM = 0.15;

    public function __construct(private readonly DeliveryStateMachine $states)
    {
    }

    /**
     * @param  array<string, mixed>  $data  validated StoreDeliveryRequest payload
     */
    public function create(User $user, array $data): Delivery
    {
        return DB::transaction(function () use ($user, $data) {
            /** @var DeliveryQuote|null $quote */
            $quote = DeliveryQuote::query()
                ->whereKey($data['quoteId'])
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($quote === null || ! $quote->isUsable()) {
                throw new BusinessException(
                    'Ce prix n\'est plus valable. Actualisez l\'estimation pour obtenir le prix du moment.',
                    'QUOTE_EXPIRED',
                );
            }
            $this->assertMatchesQuote($quote, $data);

            $payment = PaymentMethod::from($data['paymentMethod']);

            $delivery = Delivery::create([
                'reference' => $this->newReference(),
                'user_id' => $user->id,
                'quote_id' => $quote->id,
                'status' => $payment->isPrepaid() ? DeliveryStatus::AwaitingPayment : DeliveryStatus::SearchingCourier,
                'vehicle_code' => $quote->vehicle_code,
                'pickup_address' => $data['pickup']['address'],
                'pickup_details' => $data['pickup']['details'] ?? null,
                'pickup_instructions' => $data['pickup']['instructions'] ?? null,
                'pickup_latitude' => $data['pickup']['latitude'],
                'pickup_longitude' => $data['pickup']['longitude'],
                'delivery_address' => $data['delivery']['address'],
                'delivery_details' => $data['delivery']['details'] ?? null,
                'delivery_instructions' => $data['delivery']['instructions'] ?? null,
                'delivery_latitude' => $data['delivery']['latitude'],
                'delivery_longitude' => $data['delivery']['longitude'],
                'sender_name' => $data['sender']['name'],
                'sender_phone' => $data['sender']['phone'],
                'receiver_name' => $data['receiver']['name'],
                'receiver_phone' => $data['receiver']['phone'],
                'distance_km' => $quote->distance_km,
                'eta_minutes' => $quote->eta_minutes,
                'options' => $quote->options,
                'breakdown' => $quote->breakdown,
                'total_amount' => $quote->total,
                'currency' => config('moncolis.delivery.currency'),
                'payment_method' => $payment,
                'payment_status' => $payment->isPrepaid() ? 'pending' : 'cash_on_delivery',
                'payment_phone' => $data['paymentPhone'] ?? null,
            ]);

            $natures = PackageNature::query()->pluck('id', 'code');
            foreach ($data['packages'] as $package) {
                $delivery->packages()->create([
                    'package_nature_id' => $natures[$package['natureCode']],
                    'description' => $package['description'] ?? null,
                    'weight_kg' => $package['weightKg'],
                    'quantity' => $package['quantity'] ?? 1,
                    'declared_value' => $package['declaredValue'] ?? 0,
                    'length_cm' => $package['lengthCm'] ?? null,
                    'width_cm' => $package['widthCm'] ?? null,
                    'height_cm' => $package['heightCm'] ?? null,
                ]);
            }

            $quote->forceFill(['used_at' => now()])->save();

            $this->states->start(
                $delivery,
                $delivery->status,
                DeliveryStateMachine::ACTOR_CUSTOMER,
                $payment->isPrepaid() ? 'Livraison créée, en attente du paiement.' : 'Livraison créée, paiement à la livraison.',
            );

            return $delivery;
        });
    }

    public function cancel(Delivery $delivery, ?string $reason): Delivery
    {
        if (! $delivery->status->isCancellableByCustomer()) {
            throw new BusinessException(
                'Le colis a déjà été récupéré par le coursier : la livraison ne peut plus être annulée.',
                'CANCEL_NOT_ALLOWED',
                409,
            );
        }

        return DB::transaction(function () use ($delivery, $reason) {
            $this->states->transition($delivery, DeliveryStatus::Cancelled, DeliveryStateMachine::ACTOR_CUSTOMER, $reason);
            $delivery->forceFill(['cancelled_at' => now(), 'cancel_reason' => $reason])->save();

            return $delivery;
        });
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function addMedia(Delivery $delivery, array $files): void
    {
        if ($delivery->status->isFinished()) {
            throw new BusinessException('Cette livraison est terminée.', 'DELIVERY_FINISHED', 409);
        }

        $counts = $delivery->media()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $images = (int) ($counts['image'] ?? 0);
        $videos = (int) ($counts['video'] ?? 0);

        foreach ($files as $file) {
            str_starts_with((string) $file->getMimeType(), 'video/') ? $videos++ : $images++;
        }
        if ($images > config('moncolis.delivery.max_images') || $videos > config('moncolis.delivery.max_videos')) {
            throw new BusinessException(
                'Maximum '.config('moncolis.delivery.max_images').' photos et '
                    .config('moncolis.delivery.max_videos').' vidéo par livraison.',
                'MEDIA_LIMIT',
            );
        }

        foreach ($files as $file) {
            $type = str_starts_with((string) $file->getMimeType(), 'video/') ? 'video' : 'image';
            $path = $file->storeAs(
                'deliveries/'.$delivery->reference,
                Str::uuid()->toString().'.'.$file->extension(),
                ['disk' => config('moncolis.delivery.media_disk')],
            );
            $delivery->media()->create(['type' => $type, 'path' => $path]);
        }
    }

    /**
     * The delivery must match what was priced: same points (small drift allowed),
     * no heavier parcel, no higher declared value.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertMatchesQuote(DeliveryQuote $quote, array $data): void
    {
        $weight = collect($data['packages'])->sum(fn ($p) => $p['weightKg'] * ($p['quantity'] ?? 1));
        $value = collect($data['packages'])->sum(fn ($p) => ($p['declaredValue'] ?? 0) * ($p['quantity'] ?? 1));

        $pickupDrift = RouteDistanceService::haversineKm(
            $quote->pickup_latitude, $quote->pickup_longitude, $data['pickup']['latitude'], $data['pickup']['longitude'],
        );
        $dropoffDrift = RouteDistanceService::haversineKm(
            $quote->delivery_latitude, $quote->delivery_longitude, $data['delivery']['latitude'], $data['delivery']['longitude'],
        );

        if ($pickupDrift > self::MAX_POINT_DRIFT_KM
            || $dropoffDrift > self::MAX_POINT_DRIFT_KM
            || $weight > $quote->weight_kg + 0.01
            || $value > $quote->declared_value) {
            throw new BusinessException(
                'La livraison a changé depuis l\'estimation (adresse, poids ou valeur). Actualisez le prix.',
                'QUOTE_MISMATCH',
            );
        }
    }

    /**
     * "MC-250925-7KQX": date + 4 characters without ambiguous ones (0/O, 1/I).
     */
    private function newReference(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $reference = 'MC-'.now()->format('ymd').'-'.$suffix;
        } while (Delivery::where('reference', $reference)->exists());

        return $reference;
    }
}
