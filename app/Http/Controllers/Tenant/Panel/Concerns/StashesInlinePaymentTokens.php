<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Concerns;

use Illuminate\Http\Request;

trait StashesInlinePaymentTokens
{
    /**
     * Write the tokenised inline-card data into the session under the exact
     * keys the payment-gateway `charge` routes already read, matching the
     * Livewire `stashInlineTokens()` implementations verbatim.
     */
    protected function stashInlineTokens(Request $request, string $gateway): void
    {
        if ($gateway === 'stripe' && filled($request->input('stripe_token'))) {
            session(['pgtoken_stripe_stripeToken' => $request->input('stripe_token')]);

            return;
        }

        if ($gateway === 'authorize_net' && filled($request->input('authnet_desc')) && filled($request->input('authnet_value'))) {
            session([
                'pgtoken_authorize_net_opaqueDataDescriptor' => $request->input('authnet_desc'),
                'pgtoken_authorize_net_opaqueDataValue' => $request->input('authnet_value'),
            ]);

            return;
        }

        if ($gateway === '2checkout' && filled($request->input('twoco_token'))) {
            session(['pgtoken_2checkout_2co_token' => $request->input('twoco_token')]);
        }
    }
}
