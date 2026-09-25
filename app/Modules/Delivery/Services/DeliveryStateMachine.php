<?php

namespace App\Modules\Delivery\Services;

use App\Modules\Delivery\Enums\DeliveryStatus;
use App\Modules\Delivery\Models\Delivery;
use App\Shared\Exceptions\BusinessException;

/**
 * Single entry point for status changes: checks the transition is allowed and
 * records it in the delivery history.
 */
class DeliveryStateMachine
{
    public const ACTOR_CUSTOMER = 'customer';

    public const ACTOR_COURIER = 'courier';

    public const ACTOR_SYSTEM = 'system';

    /**
     * First status of a new delivery (no previous status to check).
     */
    public function start(Delivery $delivery, DeliveryStatus $status, string $actor, ?string $note = null): void
    {
        $delivery->forceFill(['status' => $status])->save();
        $this->record($delivery, $status, $actor, $note);
    }

    public function transition(Delivery $delivery, DeliveryStatus $to, string $actor, ?string $note = null): void
    {
        if (! $delivery->status->canTransitionTo($to)) {
            throw new BusinessException(
                "Impossible de passer de « {$delivery->status->label()} » à « {$to->label()} ».",
                'INVALID_STATUS_TRANSITION',
                409,
            );
        }

        $delivery->forceFill(['status' => $to])->save();
        $this->record($delivery, $to, $actor, $note);
    }

    private function record(Delivery $delivery, DeliveryStatus $status, string $actor, ?string $note): void
    {
        $delivery->events()->create([
            'status' => $status,
            'actor' => $actor,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}
