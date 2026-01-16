<?php

namespace App\Module\Health\Controller;

use App\Module\Health\HealthService;
use App\Module\Shared\BaseController;
use App\Response\MonitorResponse;

class HealthController extends BaseController
{
    public function __construct(private readonly HealthService $service, private readonly MonitorResponse $response)
    {
    }

    /**
     * Check project Health.
     *
     * @api {get} /health Check project Health
     *
     * @apiName Health Check
     *
     * @apiGroup health
     *
     * @apiSuccessExample Response:
     * HTTP/1.1 200 OK
     *   {
     *   "success": true,
     *   "data": {
     *      "status": "success",
     *      "request-time": 100
     *   }
     *}
     */
    public function healthCheck(): MonitorResponse
    {
        return $this->response->response(
            $this->service->healthCheck()
        );
    }
}
