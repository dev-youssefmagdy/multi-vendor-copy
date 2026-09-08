<?php

namespace App\Livewire\Admin\Concerns;

trait HasCsvExport
{
    protected function exportHeaders(): array
    {
        return [];
    }

    protected function exportRows(): array
    {
        return [];
    }

    protected function exportFileName(): string
    {
        return 'export-' . now()->format('Y-m-d') . '.csv';
    }

    public function export(): void
    {
        ob_start();
        $handle = fopen('php://output', 'w');

        // UTF-8 BOM so Excel opens correctly
        fwrite($handle, "\xEF\xBB\xBF");

        $headers = $this->exportHeaders();
        if (!empty($headers)) {
            fputcsv($handle, $headers);
        }

        foreach ($this->exportRows() as $row) {
            fputcsv($handle, array_values((array) $row));
        }

        fclose($handle);
        $content = ob_get_clean();

        $this->dispatch(
            'csv-download',
            content: base64_encode($content),
            filename: $this->exportFileName(),
        );
    }
}
