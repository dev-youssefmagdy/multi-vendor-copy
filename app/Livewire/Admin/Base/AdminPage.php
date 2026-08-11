<?php

namespace App\Livewire\Admin\Base;

use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use Livewire\Component;

abstract class AdminPage extends Component
{
    use AuthorizesAdminPermissions;

    abstract protected function pageMeta(): array;

    abstract protected function pageView(): string;

    protected function pageData(): array
    {
        return $this->pageMeta();
    }

    protected function presentMetricCards(array $cards): array
    {
        return collect($cards)
            ->map(function (array $card) {
                $card['value'] = $this->formatMetricValue(
                    $card['value'] ?? 0,
                    $card['format'] ?? null,
                    $card['suffix'] ?? null,
                );

                return $card;
            })
            ->all();
    }

    protected function formatMetricValue(mixed $value, ?string $format = null, ?string $suffix = null): string
    {
        $formatted = match ($format) {
            'currency' => '$' . number_format((float) $value, 2),
            'percent' => number_format((float) $value, 2) . '%',
            'number' => fmod((float) $value, 1.0) === 0.0
            ? number_format((float) $value, 0)
            : number_format((float) $value, 2),
            default => (string) $value,
        };

        if ($suffix) {
            return $formatted . ' ' . $suffix;
        }

        return $formatted;
    }

    public function render()
    {
        return view($this->pageView(), $this->pageData());
    }
}
