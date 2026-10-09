<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\CancellationActor;
use App\Http\Requests\Concerns\ValidatesCancellationReason;
use Illuminate\Foundation\Http\FormRequest;

/** Storefront API: a customer cancels one of their orders (customer reasons only). */
final class CancelOrderRequest extends FormRequest
{
    use ValidatesCancellationReason;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->cancellationRules(CancellationActor::Customer);
    }

    public function messages(): array
    {
        return $this->cancellationMessages();
    }
}
