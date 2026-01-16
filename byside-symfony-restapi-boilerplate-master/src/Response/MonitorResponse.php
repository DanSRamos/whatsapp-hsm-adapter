<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class MonitorResponse extends JsonResponse
{
    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct(
        array $content = [],
        int $status = 200,
        array $headers = []
    ) {
        parent::__construct($content, $status, $headers);
    }

    /**
     * Response Monitor Status.
     */
    public function response(?array $data = null, int $status = 200, array $headers = []): self
    {
        return new self($data, $status, $headers);
    }
}
