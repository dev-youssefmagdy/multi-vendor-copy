<?php

namespace App\Services\Admin;

class SimplePdfRenderer
{
    public function renderInvoice(array $payload): string
    {
        $pages = $this->chunkLines($this->invoiceLines($payload), 42);
        $objects = [];
        $pageObjectNumbers = [];
        $fontObjectNumber = (count($pages) * 2) + 3;

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        foreach ($pages as $index => $pageLines) {
            $pageObjectNumber = ($index * 2) + 3;
            $contentObjectNumber = $pageObjectNumber + 1;

            $pageObjectNumbers[] = $pageObjectNumber;
            $objects[$pageObjectNumber] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>',
                $fontObjectNumber,
                $contentObjectNumber,
            );

            $stream = $this->contentStream($pageLines);
            $objects[$contentObjectNumber] = sprintf(
                "<< /Length %d >>\nstream\n%s\nendstream",
                strlen($stream),
                $stream,
            );
        }

        $objects[2] = sprintf(
            '<< /Type /Pages /Kids [%s] /Count %d >>',
            implode(' ', array_map(fn(int $number) => sprintf('%d 0 R', $number), $pageObjectNumbers)),
            count($pageObjectNumbers),
        );
        $objects[$fontObjectNumber] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objects as $number => $content) {
            $offsets[$number] = strlen($pdf);
            $pdf .= sprintf("%d 0 obj\n%s\nendobj\n", $number, $content);
        }

        $xrefOffset = strlen($pdf);
        $maxObjectNumber = max(array_keys($objects));

        $pdf .= sprintf("xref\n0 %d\n", $maxObjectNumber + 1);
        $pdf .= "0000000000 65535 f \n";

        for ($number = 1; $number <= $maxObjectNumber; $number++) {
            $offset = $offsets[$number] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= sprintf(
            "trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF",
            $maxObjectNumber + 1,
            $xrefOffset,
        );

        return $pdf;
    }

    protected function invoiceLines(array $payload): array
    {
        $lines = [
            'Marketplace Order Invoice',
            '',
            sprintf('Invoice Number: %s', $payload['invoice_number'] ?? '-'),
            sprintf('Order Number: %s', $payload['order_number'] ?? '-'),
            sprintf('Store: %s', $payload['store_name'] ?? '-'),
            sprintf('Customer: %s', $payload['customer_name'] ?? '-'),
            sprintf('Customer Email: %s', $payload['customer_email'] ?? '-'),
            sprintf('Issued At: %s', $payload['issued_at'] ?? '-'),
            sprintf('Payment Method: %s', $payload['payment_method'] ?? '-'),
            sprintf('Payment Reference: %s', $payload['payment_reference'] ?? '-'),
            '',
            'Financial Summary',
            sprintf('Subtotal: %s', $payload['subtotal'] ?? '0.00'),
            sprintf('Discount: %s', $payload['discount'] ?? '0.00'),
            sprintf('Tax: %s', $payload['tax'] ?? '0.00'),
            sprintf('Shipping: %s', $payload['shipping'] ?? '0.00'),
            sprintf('Grand Total: %s', $payload['grand_total'] ?? '0.00'),
            '',
            'Shipping Address',
        ];

        foreach (($payload['shipping_lines'] ?? []) as $shippingLine) {
            $lines[] = $shippingLine;
        }

        $lines[] = '';
        $lines[] = 'Line Items';

        foreach (($payload['items'] ?? []) as $item) {
            $lines[] = sprintf(
                '%s | Qty %s | Price %s | Total %s',
                $item['name'] ?? 'Item',
                $item['qty'] ?? 0,
                $item['price'] ?? '0.00',
                $item['total'] ?? '0.00',
            );

            if (!empty($item['variant'])) {
                $lines[] = sprintf('  Variant: %s', $item['variant']);
            }
        }

        return array_merge(...array_map([$this, 'wrapLine'], $lines));
    }

    protected function chunkLines(array $lines, int $perPage): array
    {
        $pages = array_chunk($lines, $perPage);

        return $pages === [] ? [['Marketplace Order Invoice']] : $pages;
    }

    protected function contentStream(array $lines): string
    {
        $commands = ['BT', '/F1 11 Tf', '50 760 Td', '14 TL'];

        foreach ($lines as $index => $line) {
            $escaped = $this->escapePdfText($line);
            $commands[] = sprintf('(%s) Tj', $escaped);

            if ($index !== array_key_last($lines)) {
                $commands[] = 'T*';
            }
        }

        $commands[] = 'ET';

        return implode("\n", $commands);
    }

    protected function wrapLine(string $line, int $width = 88): array
    {
        if ($line === '') {
            return [''];
        }

        return preg_split('/\n/', wordwrap($line, $width, "\n", true)) ?: [$line];
    }

    protected function escapePdfText(string $value): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $value,
        );
    }
}
