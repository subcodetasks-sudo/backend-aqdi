<?php

namespace App\Http\Traits;

use App\Support\JsonEncoding;
use Illuminate\Http\JsonResponse;

trait Responser
{
    protected function jsonResponse(array $payload, int $http = 200): JsonResponse
    {
        return response()->json($payload, $http, [], JsonEncoding::OPTIONS);
    }

    protected function successMessage($msg, $code = 200)
    {
        return $this->jsonResponse([
            'message' => $msg,
            'code' => $code,
            'success' => true,
        ], $this->httpStatus($code));
    }

    protected function errorMessage($msg, $code = 400)
    {
        $http = $this->httpStatus($code);

        return $this->jsonResponse([
            'message' => $msg,
            'code' => $code,
            'success' => false,
        ], $http);
    }

    protected function errorResponse($data, $code)
    {
        $http = $this->httpStatus($code);

        return $this->jsonResponse([
            'code' => $code,
            'success' => false,
            'errors' => $data,
        ], $http);
    }

    protected function apiResponse($data, $msg, $code = 200)
    {
        return $this->jsonResponse([
            'message' => $msg,
            'code' => $code,
            'success' => true,
            'data' => $data,
        ], $this->httpStatus($code));
    }

    protected function paginate($object)
    {
        return [
            'current_page' => $object->currentPage(),
            'last_page' => $object->lastPage(),
            'first_page_url' => $object->url(1),
            'last_page_url' => $object->url($object->lastPage()),
            'next_page_url' => $object->nextPageUrl(),
            'prev_page_url' => $object->previousPageUrl(),
            'from' => $object->firstItem(),
            'to' => $object->lastItem(),
            'per_page' => $object->perPage(),
            'total' => $object->total(),
        ];
    }

    private function httpStatus(int $code): int
    {
        return ($code >= 100 && $code < 600) ? $code : 200;
    }
}
