<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Finance\Concerns;

use Illuminate\Validation\Rule;

trait InlinePaymentRules
{
    /**
     * @param  string[]  $gatewayCodes
     */
    protected function inlinePaymentRules(array $gatewayCodes): array
    {
        return [
            'gateway' => ['required', 'string', Rule::in($gatewayCodes)],
            'stripe_token' => ['nullable', 'string', 'max:2048'],
            'authnet_desc' => ['nullable', 'string', 'max:2048'],
            'authnet_value' => ['nullable', 'string', 'max:2048'],
            'twoco_token' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
