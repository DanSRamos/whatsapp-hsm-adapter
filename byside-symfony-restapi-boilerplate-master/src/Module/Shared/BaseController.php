<?php

namespace App\Module\Shared;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BaseController extends AbstractController
{
    /*
     * @apiDefine BadRequestError
     * @apiErrorExample {json} Error-400:
     * HTTP/1.1 400 Bad Request
     * {
     *      "success": false,
     *      "data": [],
     *      "errors": [
     *          "mandatory_data_missing_error"
     *      ],
     *      "warnings": []
     * }
     */
}
