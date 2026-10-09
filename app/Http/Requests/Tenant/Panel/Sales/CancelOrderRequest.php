<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Enums\CancellationActor;
use App\Http\Requests\Concerns\ValidatesCancellationReason;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;

/** Tenant panel: the vendor cancels an order (staff reasons only). */
final class CancelOrderRequest extends TenantFormRequest
{
    use ValidatesCancellationReason;

    public function rules(): array
    {
        return $this->cancellationRules(CancellationActor::Vendor);
    }

    public function messages(): array
    {
        return $this->cancellationMessages();
    }
}
