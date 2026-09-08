<?php

namespace App\Enums;

enum WalletTransactionDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
