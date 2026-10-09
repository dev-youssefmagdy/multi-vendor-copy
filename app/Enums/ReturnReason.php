<?php

namespace App\Enums;

enum ReturnReason: string
{
    case Defective = 'defective';
    case WrongItem = 'wrong_item';
    case NotAsDescribed = 'not_as_described';
    case ChangedMind = 'changed_mind';
    case SizeOrFit = 'size_or_fit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Defective => __('Defective / Damaged'),
            self::WrongItem => __('Wrong Item Received'),
            self::NotAsDescribed => __('Not as Described'),
            self::ChangedMind => __('Changed My Mind'),
            self::SizeOrFit => __('Size or Fit Issue'),
            self::Other => __('Other'),
        };
    }

    /** Reasons where a video is required as evidence, by default policy. */
    public function requiresVideo(): bool
    {
        return match ($this) {
            self::Defective, self::WrongItem, self::NotAsDescribed => true,
            default => false,
        };
    }

    /**
     * The seller is responsible (damaged, wrong or misdescribed item): the return fee is
     * waived and shipping is refunded when the whole order comes back.
     */
    public function isSellerFault(): bool
    {
        return match ($this) {
            self::Defective, self::WrongItem, self::NotAsDescribed => true,
            self::ChangedMind, self::SizeOrFit, self::Other => false,
        };
    }

    /** At least one photo is mandatory as evidence (photos are optional otherwise). */
    public function requiresPhotos(): bool
    {
        return $this->isSellerFault();
    }

    /** A description (min 10 chars) is mandatory for seller-fault reasons. */
    public function requiresDescription(): bool
    {
        return $this->isSellerFault();
    }

    /** @return list<self> */
    public static function sellerFaultReasons(): array
    {
        return array_values(array_filter(self::cases(), fn (self $reason) => $reason->isSellerFault()));
    }
}
