<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Sales;

use App\Enums\InspectionResult;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use Illuminate\Validation\Rule;

/** Tenant panel: record the inspection of a returned item (result, notes, restock). */
final class InspectReturnRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'inspection_result' => ['required', 'string', Rule::enum(InspectionResult::class)],
            'inspection_notes' => ['nullable', 'string', 'max:2000'],
            'restock' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'inspection_result.required' => __('Please choose an inspection result.'),
            'inspection_result.enum' => __('Please choose an inspection result.'),
        ];
    }

    /** Restock the returned units; null (not sent) = the tenant's restock_returned_items policy. */
    public function restock(): ?bool
    {
        return $this->filled('restock') ? $this->boolean('restock') : null;
    }

    public function result(): InspectionResult
    {
        return InspectionResult::from((string) $this->validated('inspection_result'));
    }
}
