<?php

namespace App\Models\Tenant;

use App\Enums\PaymentGatewayMode;
use App\Enums\PaymentGatewayType;
use App\Models\PaymentGateway as CentralPaymentGateway;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'central_payment_gateway_id',
        'name',
        'code',
        'type',
        'is_active',
        'use_own',
        'hide',
        'mode',
        'connection_status',
        'required_keys',
        'required_values',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'use_own' => 'boolean',
            'hide' => 'boolean',
            'mode' => PaymentGatewayMode::class,
            'type' => PaymentGatewayType::class,
            'required_keys' => 'array',
            'required_values' => 'array',
        ];
    }

    public function centralPaymentGateway(): BelongsTo
    {
        return $this->belongsTo(CentralPaymentGateway::class, 'central_payment_gateway_id');
    }

    protected function credentials(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->normalizeCredentials(),
        );
    }

    protected function effectiveCredentials(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->use_own
            ? $this->credentials
            : (array) ($this->resolveCentralGateway()?->credentials ?? $this->credentials),
        );
    }

    protected function effectiveMode(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->use_own
            ? ($this->mode ?? PaymentGatewayMode::Test)
            : ($this->resolveCentralGateway()?->mode ?? $this->mode ?? PaymentGatewayMode::Test),
        );
    }

    protected function gatewaySource(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->use_own ? 'tenant' : 'central',
        );
    }

    private function normalizeCredentials(): array
    {
        $values = (array) ($this->required_values ?? []);
        $credentials = [];

        foreach ((array) ($this->required_keys ?? []) as $key) {
            $credentials[$key] = (string) ($values[$key] ?? '');
        }

        return $credentials;
    }

    private function resolveCentralGateway(): ?CentralPaymentGateway
    {
        if ($this->relationLoaded('centralPaymentGateway')) {
            return $this->getRelation('centralPaymentGateway');
        }

        if ($this->central_payment_gateway_id) {
            return $this->centralPaymentGateway()->first();
        }

        if (blank($this->code)) {
            return null;
        }

        return CentralPaymentGateway::query()
            ->where('code', $this->code)
            ->first();
    }
}
