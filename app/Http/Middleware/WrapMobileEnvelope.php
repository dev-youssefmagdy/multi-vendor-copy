<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Normalises every JSON answer of the mobile API to {success, data?, message?, code?, errors?}.
 * Wrapped /api/v1 controllers (profile, orders, favorites, addresses, catalog...) answer in their own
 * shapes; those become data. Responses that already carry `success` + `data` (or are errors that
 * carry `success`) are left alone.
 */
class WrapMobileEnvelope
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response instanceof JsonResponse) {
            return $response;
        }

        $body = $response->getData(true);
        $status = $response->getStatusCode();

        if (!is_array($body)) {
            $response->setData(['success' => $status < 400, 'data' => $body]);

            return $response;
        }

        if ($status >= 400) {
            if (!array_key_exists('success', $body)) {
                $body = ['success' => false] + $body;
                $body['message'] ??= 'Request failed.';
                $response->setData($body);
            }

            return $response;
        }

        if (array_key_exists('success', $body) && array_key_exists('data', $body)) {
            return $response;
        }

        if (array_key_exists('success', $body)) {
            $success = $body['success'];
            $message = $body['message'] ?? null;
            unset($body['success'], $body['message']);
            $out = ['success' => $success];
            if ($message !== null) {
                $out['message'] = $message;
            }
            if ($body !== []) {
                $out['data'] = $body;
            }
            $response->setData($out);

            return $response;
        }

        // Plain list-style payloads ({data:[...]} paginated, {coupon: ...}, {products: ...}).
        $response->setData(['success' => true, 'data' => array_key_exists('data', $body) && count($body) === 1 ? $body['data'] : $body]);

        return $response;
    }
}
