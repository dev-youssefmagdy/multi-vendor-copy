<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Support;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Models\SupportTicket;

final class StoreTicketRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(SupportTicket::categoryOptions()))],
            'priority' => ['required', 'string', 'in:'.implode(',', array_keys(SupportTicket::priorityOptions()))],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
