<?php

declare(strict_types=1);

namespace App\Services\Refunds;

use App\Enums\CancellationActor;

/**
 * Who requested / approved / acted on a refund. `id` is the actor's id in its own guard
 * (customer → tenant customers, vendor → tenant admin_users, admin → central admin_users).
 * `name` is optional — RefundService resolves it from the id when missing.
 */
final readonly class RefundActor
{
    public function __construct(
        public CancellationActor $type,
        public ?int $id = null,
        public ?string $name = null,
    ) {}

    public static function from(CancellationActor|self $actor, ?int $id = null, ?string $name = null): self
    {
        return $actor instanceof self ? $actor : new self($actor, $id, $name);
    }

    public static function system(): self
    {
        return new self(CancellationActor::System);
    }

    public static function customer(?int $id): self
    {
        return new self(CancellationActor::Customer, $id);
    }

    public static function vendor(?int $id, ?string $name = null): self
    {
        return new self(CancellationActor::Vendor, $id, $name);
    }

    public static function admin(?int $id, ?string $name = null): self
    {
        return new self(CancellationActor::Admin, $id, $name);
    }

    public function isStaff(): bool
    {
        return $this->type->isStaff();
    }

    public function withName(?string $name): self
    {
        return new self($this->type, $this->id, $name);
    }

    /** @return array{type: string, id: int|null, name: string|null} */
    public function toArray(): array
    {
        return ['type' => $this->type->value, 'id' => $this->id, 'name' => $this->name];
    }
}
