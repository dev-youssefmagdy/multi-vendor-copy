<?php

namespace App\Livewire\Admin\Setting;

use App\Enums\ReturnReason;
use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Repositories\AppSettingRepository;
use App\Services\AppSettingService;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReturnPolicySettingsPage extends ContentPage
{
    use InteractsWithAdminUi;

    private const KEYS = [
        'window_days' => 'return_policy_window_days',
        'non_returnable_ids' => 'return_policy_non_returnable_ids',
        'fee' => 'return_policy_fee',
        'conditions' => 'return_policy_conditions',
        'video_required_reasons' => 'return_policy_video_required_reasons',
    ];

    public string $windowDays = '14';
    public string $nonReturnableIds = '';
    public string $fee = '0';
    public string $conditions = '';
    public string $videoRequiredReasons = '';

    public function mount(): void
    {
        $settings = app(AppSettingRepository::class)->getByKeys(array_values(self::KEYS));

        $this->windowDays = $settings->get(self::KEYS['window_days'])?->value ?: '14';
        $this->nonReturnableIds = implode(',', json_decode($settings->get(self::KEYS['non_returnable_ids'])?->value ?: '[]', true) ?: []);
        $this->fee = $settings->get(self::KEYS['fee'])?->value ?: '0';
        $this->conditions = $settings->get(self::KEYS['conditions'])?->value ?: '';
        $this->videoRequiredReasons = implode(',', json_decode($settings->get(self::KEYS['video_required_reasons'])?->value ?: '[]', true) ?: []);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Return Policy',
            'badge' => 'Configuration',
            'description' => 'Return policy for products sourced from the central Neozena catalog.',
            'bullets' => [
                'Applies to orders for central-catalog products only.',
                'Tenants configure their own policy for their own products separately.',
                'ReturnRequestService and ReturnRequestValidationService read these values live.',
            ],
        ];
    }

    public function save(AppSettingService $service): void
    {
        try {
            $validated = $this->validate([
                'windowDays' => ['required', 'integer', 'min:1', 'max:365'],
                'nonReturnableIds' => ['nullable', 'string'],
                'fee' => ['required', 'numeric', 'min:0'],
                'conditions' => ['nullable', 'string', 'max:5000'],
                'videoRequiredReasons' => ['nullable', 'string'],
            ]);
        } catch (ValidationException $exception) {
            $this->toast('Please fix the highlighted fields and try again.', 'error');

            throw $exception;
        }

        try {
            $service->saveMany([
                self::KEYS['window_days'] => ['value' => $validated['windowDays']],
                self::KEYS['non_returnable_ids'] => ['value' => json_encode($this->toIntList($validated['nonReturnableIds']))],
                self::KEYS['fee'] => ['value' => $validated['fee']],
                self::KEYS['conditions'] => ['value' => $validated['conditions']],
                self::KEYS['video_required_reasons'] => ['value' => json_encode($this->toReasonList($validated['videoRequiredReasons']))],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->toast('Something went wrong while saving the return policy. Please try again.', 'error');

            return;
        }

        $this->toast('Return policy saved successfully.', 'success');
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'formAction' => 'save',
            'submitLabel' => 'Save Policy',
            'fieldGroups' => [
                [
                    'title' => 'Central Catalog Return Policy',
                    'description' => 'Return window, fees, and evidence requirements for Neozena-sourced products.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Return Window (days)', 'model' => 'windowDays', 'type' => 'number'],
                        ['label' => 'Return Fee', 'model' => 'fee', 'type' => 'number'],
                        ['label' => 'Non-returnable Product IDs (comma-separated)', 'model' => 'nonReturnableIds', 'wrapperClass' => 'span-2'],
                        [
                            'label' => 'Reasons Requiring Video (comma-separated: ' . implode(', ', array_map(fn ($r) => $r->value, ReturnReason::cases())) . ')',
                            'model' => 'videoRequiredReasons',
                            'wrapperClass' => 'span-2',
                        ],
                        ['label' => 'Accepted Conditions', 'model' => 'conditions', 'type' => 'textarea', 'wrapperClass' => 'span-2'],
                    ],
                ],
            ],
        ]);
    }

    private function toIntList(?string $value): array
    {
        return collect(explode(',', (string) $value))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => $v !== '' && is_numeric($v))
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
    }

    private function toReasonList(?string $value): array
    {
        $valid = array_map(fn ($r) => $r->value, ReturnReason::cases());

        return collect(explode(',', (string) $value))
            ->map(fn ($v) => trim($v))
            ->filter(fn ($v) => in_array($v, $valid, true))
            ->values()
            ->all();
    }
}
