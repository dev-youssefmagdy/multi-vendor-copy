<?php

declare(strict_types=1);

// ROUTES:
// Route::put('/return-policy', [ReturnPolicyController::class, 'update'])->name('return-policy.update');
// Route::post('/return-policy/validate', [ReturnPolicyController::class, 'validateUpdate'])->name('return-policy.validate');
// (both under the existing tenant.permission:sales.returns.manage middleware, next to the kept `return-policy` GET route)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Enums\ReturnReason;
use App\Enums\Tenant\SettingType;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\SaveReturnPolicyRequest;
use App\Models\Tenant\Product;
use App\Models\Tenant\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

final class ReturnPolicyController extends PanelController
{
    private const GROUP = 'return_policy';

    private const KEYS = [
        'window_days' => 'return_policy_window_days',
        'non_returnable_ids' => 'return_policy_non_returnable_ids',
        'fee' => 'return_policy_fee',
        'conditions' => 'return_policy_conditions',
        'video_required_reasons' => 'return_policy_video_required_reasons',
    ];

    public function index(): View
    {
        $rows = Setting::query()->where('group', self::GROUP)->get()->keyBy('name');

        $nonReturnableIds = json_decode($rows->get(self::KEYS['non_returnable_ids'])?->value ?: '[]', true) ?: [];
        $videoReasons = json_decode($rows->get(self::KEYS['video_required_reasons'])?->value ?: '[]', true) ?: [];

        $selectedProducts = Product::query()
            ->whereIn('id', $nonReturnableIds)
            ->pluck('name', 'id')
            ->all();

        return view('tenant.pages.settings.return-policy.index', [
            'groups' => [
                [
                    'title' => 'Own Products Return Policy',
                    'description' => 'Return window, fees, and evidence requirements for products you sell directly.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Return Window (days)', 'model' => 'window_days', 'type' => 'number'],
                        ['label' => 'Return Fee', 'model' => 'fee', 'type' => 'number'],
                        ['label' => 'Accepted Conditions', 'model' => 'conditions', 'type' => 'textarea', 'wrapperClass' => 'span-2'],
                    ],
                ],
            ],
            'values' => [
                'window_days' => $rows->get(self::KEYS['window_days'])?->value ?: '14',
                'fee' => $rows->get(self::KEYS['fee'])?->value ?: '0',
                'conditions' => $rows->get(self::KEYS['conditions'])?->value ?: '',
                'non_returnable_ids' => $nonReturnableIds,
                'video_required_reasons' => $videoReasons,
            ],
            'nonReturnableSelected' => $selectedProducts,
            'videoReasonOptions' => collect(ReturnReason::cases())->mapWithKeys(fn (ReturnReason $reason) => [$reason->value => $reason->value])->all(),
        ]);
    }

    public function update(SaveReturnPolicyRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->putSetting(self::KEYS['window_days'], $validated['window_days']);
            $this->putSetting(self::KEYS['non_returnable_ids'], json_encode($request->toIntList()));
            $this->putSetting(self::KEYS['fee'], $validated['fee']);
            $this->putSetting(self::KEYS['conditions'], $validated['conditions'] ?? '');
            $this->putSetting(self::KEYS['video_required_reasons'], json_encode($request->toReasonList()));
        } catch (Throwable $exception) {
            report($exception);

            return $this->failure('Something went wrong while saving the return policy. Please try again.');
        }

        return $this->success('Return policy saved successfully.');
    }

    public function validateUpdate(SaveReturnPolicyRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    private function putSetting(string $name, ?string $value): void
    {
        Setting::query()->updateOrCreate(
            ['name' => $name, 'group' => self::GROUP],
            ['value' => (string) $value, 'type' => SettingType::String],
        );
    }
}
