<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse extends JsonResponse
{
    /**
     * ApiResponse constructor.
     */
    public function __construct(
        bool $success = true,
        $data = null,
        array $errors = [],
        int $status = 200,
        array $headers = [],
        bool $json = false
    ) {
        parent::__construct(
            $this->format($success, $data, $errors),
            $status,
            $headers,
            $json
        );
    }

    /**
     * Response a Success.
     *
     * @param int   $status
     * @param array $headers
     * @param bool  $json
     */
    public function responseSuccess($data = null, $status = 200, $headers = [], $json = false): ApiResponse
    {
        return new self(true, $data, [], $status, $headers, $json);
    }

    /**
     * Response Error.
     *
     * @param array $errors
     * @param int   $status
     * @param array $headers
     * @param bool  $json
     */
    public function responseError($errors = [], $status = 500, $headers = [], $json = false): ApiResponse
    {
        return new self(false, null, $errors, $status, $headers, $json);
    }

    /**
     * Format the API response.
     */
    private function format(bool $success, $data = null, array $errors = []): array
    {
        $response = [
            'success' => $success,
        ];

        if ($success) {
            $response['data'] = $data ?: [];
        }

        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}
