<?php

namespace App\Modules\Delivery\Enums;

/**
 * Lifecycle of a delivery (see docs/PLAN_BACKEND.md §2.2).
 */
enum DeliveryStatus: string
{
    case AwaitingPayment = 'EN_ATTENTE_PAIEMENT';
    case SearchingCourier = 'RECHERCHE_COURSIER';
    case CourierAssigned = 'COURSIER_ASSIGNE';
    case PickupInProgress = 'RAMASSAGE_EN_COURS';
    case PickedUp = 'COLIS_RECUPERE';
    case InDelivery = 'EN_LIVRAISON';
    case Delivered = 'LIVRE';
    case Confirmed = 'LIVRE_CONFIRME';
    case Cancelled = 'ANNULEE';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'En attente de paiement',
            self::SearchingCourier => 'Recherche d\'un coursier',
            self::CourierAssigned => 'Coursier en route',
            self::PickupInProgress => 'Ramassage en cours',
            self::PickedUp => 'Colis récupéré',
            self::InDelivery => 'En cours de livraison',
            self::Delivered => 'Livré',
            self::Confirmed => 'Réception confirmée',
            self::Cancelled => 'Annulée',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::AwaitingPayment => [self::SearchingCourier, self::Cancelled],
            self::SearchingCourier => [self::CourierAssigned, self::Cancelled],
            self::CourierAssigned => [self::PickupInProgress, self::SearchingCourier, self::Cancelled],
            self::PickupInProgress => [self::PickedUp, self::Cancelled],
            self::PickedUp => [self::InDelivery],
            self::InDelivery => [self::Delivered],
            self::Delivered => [self::Confirmed],
            self::Confirmed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /**
     * The customer may cancel until the parcel is picked up.
     */
    public function isCancellableByCustomer(): bool
    {
        return in_array($this, [
            self::AwaitingPayment, self::SearchingCourier, self::CourierAssigned, self::PickupInProgress,
        ], true);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Delivered, self::Confirmed, self::Cancelled], true);
    }

    /**
     * Filter of "Mes livraisons": active | done | cancelled.
     *
     * @return list<self>
     */
    public static function group(string $group): array
    {
        return match ($group) {
            'done' => [self::Delivered, self::Confirmed],
            'cancelled' => [self::Cancelled],
            default => array_values(array_filter(self::cases(), fn (self $s) => ! $s->isFinished())),
        };
    }
}
