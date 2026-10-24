<?php

namespace App\Livewire\Tenant\Setting;

use App\Enums\ReturnReason;
use App\Enums\Tenant\SettingType;
use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\Setting;
use Throwable;

class ReturnPolicyPage extends ContentPage
{
    use InteractsWithTenantUi;

    private const GROUP = 'return_policy';

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
        $rows = Setting::query()->where('group', self::GROUP)->get()->keyBy('name');

        $this->windowDays = $rows->get(self::KEYS['window_days'])?->value ?: '14';
        $this->nonReturnableIds = implode(',', json_decode($rows->get(self::KEYS['non_returnable_ids'])?->value ?: '[]', true) ?: []);
        $this->fee = $rows->get(self::KEYS['fee'])?->value ?: '0';
        $this->conditions = $rows->get(self::KEYS['conditions'])?->value ?: '';
        $this->videoRequiredReasons = implode(',', json_decode($rows->get(self::KEYS['video_required_reasons'])?->value ?: '[]', true) ?: []);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Return Policy',
            'badge' => 'Store',
            'description' => 'Return policy applied to your own products (not the central Neozena catalog).',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'windowDays' => ['required', 'integer', 'min:1', 'max:365'],
            'nonReturnableIds' => ['nullable', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'conditions' => ['nullable', 'string', 'max:5000'],
            'videoRequiredReasons' => ['nullable', 'string'],
        ]);

        try {
            $this->putSetting(self::KEYS['window_days'], $validated['windowDays']);
            $this->putSetting(self::KEYS['non_returnable_ids'], json_encode($this->toIntList($validated['nonReturnableIds'])));
            $this->putSetting(self::KEYS['fee'], $validated['fee']);
            $this->putSetting(self::KEYS['conditions'], $validated['conditions']);
            $this->putSetting(self::KEYS['video_required_reasons'], json_encode($this->toReasonList($validated['videoRequiredReasons'])));
        } catch (Throwable $exception) {
            report($exception);
            $this->toast('Something went wrong while saving the return policy. Please try again.', 'error');

            return;
        }

        $this->toast('Return policy saved successfully.');
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'formAction' => 'save',
            'submitLabel' => 'Save Policy',
            'fieldGroups' => [
                [
                    'title' => 'Own Products Return Policy',
                    'description' => 'Return window, fees, and evidence requirements for products you sell directly.',
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

    private function putSetting(string $name, ?string $value): void
    {
        Setting::query()->updateOrCreate(
            ['name' => $name, 'group' => self::GROUP],
            ['value' => (string) $value, 'type' => SettingType::String],
        );
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
