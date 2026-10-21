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
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'subject' => 'subject',
            'category' => 'category',
            'body' => 'message',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Please enter a subject for your ticket.',
            'subject.max' => 'The subject may not be longer than :max characters.',
            'category.required' => 'Please choose a category.',
            'category.in' => 'Please choose a valid category.',
            'body.required' => 'Please describe your issue.',
            'body.min' => 'Your message must be at least :min characters so the team can help.',
            'body.max' => 'Your message may not be longer than :max characters.',
        ];
    }
}
