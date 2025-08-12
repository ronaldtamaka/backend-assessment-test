<?php

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

if (!function_exists('success_response')) {
    function success_response($data = null, string $message = 'Success', int $code = 200)
    {
        // If the data is a JsonResource (Resource or ResourceCollection)
        if ($data instanceof JsonResource) {
            // Force response to array and paginate manually if needed
            $response = $data->response()->getData(true);

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $response['data'] ?? $response,
                'meta' => $response['meta'] ?? null,
                'links' => $response['links'] ?? null,
            ], $code);
        }

        // Fallback for normal paginated model without resource
        if ($data instanceof LengthAwarePaginator || $data instanceof Paginator) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $data->items(),
                'meta' => [
                    'current_page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                    'total' => method_exists($data, 'total') ? $data->total() : null,
                    'last_page' => method_exists($data, 'lastPage') ? $data->lastPage() : null,
                    'next_page_url' => $data->nextPageUrl(),
                    'prev_page_url' => $data->previousPageUrl(),
                ]
            ], $code);
        }

        // Default (non-resource, non-paginated)
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}

if (!function_exists('error_response')) {
    function error_response(string $message = 'Something went wrong', int $code = 400, $errors = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
