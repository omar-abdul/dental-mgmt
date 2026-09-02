<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case Zaad = 'zaad';
    case Sahal = 'sahal';
    case Edahab = 'edahab';
    case Mycash = 'mycash';
    case Insurance = 'insurance';

    public function isMobileMoney(): bool
    {
        return in_array($this, [
            self::Zaad,
            self::Sahal,
            self::Edahab,
            self::Mycash,
        ], true);
    }

    public function defaultProvider(): MobileMoneyProvider
    {
        return match ($this) {
            self::Zaad => MobileMoneyProvider::Telesom,
            self::Sahal => MobileMoneyProvider::Golis,
            self::Edahab => MobileMoneyProvider::Somtel,
            self::Mycash => MobileMoneyProvider::Somlink,
            default => throw new \InvalidArgumentException("Payment method {$this->value} has no mobile money provider."),
        };
    }

    public function requiresReference(): bool
    {
        return in_array($this, [
            self::Card,
            self::BankTransfer,
            self::Insurance,
            self::Zaad,
            self::Sahal,
            self::Edahab,
            self::Mycash,
        ], true);
    }
}
