<?php

declare(strict_types=1);

namespace App\Support\Tenant;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class Select2Response
{
    public static function paginate(LengthAwarePaginator $paginator, Closure $map): JsonResponse
    {
        return response()->json([
            'results' => collect($paginator->items())->map($map)->values()->all(),
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }
}
