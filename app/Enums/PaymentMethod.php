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
}
