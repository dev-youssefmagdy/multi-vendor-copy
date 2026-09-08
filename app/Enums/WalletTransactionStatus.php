<?php

namespace App\Enums;

enum WalletTransactionStatus: string
{
    case Pending = 'pending';
    case Posted = 'posted';
    case Failed = 'failed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return str($this->value)->headline()->toString();
    }
}
