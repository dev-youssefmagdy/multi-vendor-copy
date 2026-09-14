<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Settings;

use App\Enums\ReturnReason;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveReturnPolicyRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'window_days' => ['required', 'integer', 'min:1', 'max:365'],
            'non_returnable_ids' => ['nullable', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'conditions' => ['nullable', 'string', 'max:5000'],
            'video_required_reasons' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'window_days' => 'return window (days)',
            'non_returnable_ids' => 'non-returnable product IDs',
            'fee' => 'return fee',
            'conditions' => 'accepted conditions',
            'video_required_reasons' => 'reasons requiring video',
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $nonReturnableIds = $this->input('non_returnable_ids');

        if (is_array($nonReturnableIds)) {
            $this->merge(['non_returnable_ids' => implode(',', $nonReturnableIds)]);
        }

        $videoReasons = $this->input('video_required_reasons');

        if (is_array($videoReasons)) {
            $this->merge(['video_required_reasons' => implode(',', $videoReasons)]);
        }
    }

    public function toIntList(): array
    {
        return collect(explode(',', (string) $this->validated('non_returnable_ids')))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => $value !== '' && is_numeric($value))
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    }

    public function toReasonList(): array
    {
        $valid = array_map(fn ($reason) => $reason->value, ReturnReason::cases());

        return collect(explode(',', (string) $this->validated('video_required_reasons')))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => in_array($value, $valid, true))
            ->values()
            ->all();
    }
}
