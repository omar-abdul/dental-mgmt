<?php

namespace App\Enums;

enum LabOrderStatus: string
{
    case Ordered = 'ordered';
    case ReceivedByLab = 'received_by_lab';
    case InProduction = 'in_production';
    case Ready = 'ready';
    case ReceivedByClinic = 'received_by_clinic';
    case Fitted = 'fitted';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Ordered => 'Ordered',
            self::ReceivedByLab => 'Received by lab',
            self::InProduction => 'In production',
            self::Ready => 'Ready',
            self::ReceivedByClinic => 'Received by clinic',
            self::Fitted => 'Fitted',
            self::Returned => 'Returned',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Fitted,
            self::Returned,
            self::Cancelled,
        ], true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Ordered => [self::ReceivedByLab, self::Cancelled],
            self::ReceivedByLab => [self::InProduction, self::Cancelled],
            self::InProduction => [self::Ready, self::Cancelled],
            self::Ready => [self::ReceivedByClinic, self::Cancelled],
            self::ReceivedByClinic => [self::Fitted, self::Returned, self::Cancelled],
            default => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
