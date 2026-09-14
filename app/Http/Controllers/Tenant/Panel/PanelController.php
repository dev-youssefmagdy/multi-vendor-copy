<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class PanelController extends Controller
{
    protected function success(string $message, array $data = [], ?string $redirect = null, int $status = 200): JsonResponse
    {
        if ($redirect !== null) {
            session()->flash('status', $message);
            session()->flash('status_type', 'success');
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'redirect' => $redirect,
        ], $status);
    }

    protected function failure(string $message, int $status = 422, array $errors = [], ?string $redirect = null, string $toastType = 'error'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'redirect' => $redirect,
            'toast_type' => $toastType,
        ], $status);
    }

    protected function validFormResponse(): JsonResponse
    {
        return response()->json(['valid' => true]);
    }

    protected function filters(Request $request, array $allowed): array
    {
        $filters = (array) $request->input('filters', []);

        return collect($filters)
            ->only($allowed)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->all();
    }

    protected function fragment(string $view, array $data, LengthAwarePaginator $paginator): JsonResponse
    {
        return response()->json([
            'html' => view($view, $data)->render(),
            'pagination' => view('tenant::components.pagination', [
                'paginator' => $paginator,
                'mode' => 'ajax',
            ])->render(),
        ]);
    }

    protected function streamCsv(string $fileName, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers);

            foreach ($rows as $row) {
                fputcsv($stream, $row);
            }

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
