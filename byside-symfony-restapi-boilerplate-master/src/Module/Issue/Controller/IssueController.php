<?php

namespace App\Module\Issue\Controller;

use App\Module\Issue\IssueService;
use App\Module\Issue\Transformer\IssueTransformer;
use App\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @codeCoverageIgnore
 */
class IssueController extends AbstractController
{
    /** @var ApiResponse */
    public $response;
    /** @var IssueTransformer */
    public $transformer;
    /** @var IssueService */
    public $service;

    public function __construct(
        IssueService $service,
        IssueTransformer $transformer,
        ApiResponse $response
    ) {
        $this->transformer = $transformer;
        $this->response = $response;
        $this->service = $service;
    }

    /**
     * @api {post} /issue/ Get issue
     *
     * @apiName Issue
     *
     * @apiGroup Issue
     *
     * @apiParam id {number} Issue Id
     *
     * @apiSuccessExample Response:
     * HTTP/1.1 200 OK
     * {
     *     "success": true,
     *     "data": {
     *         "id": "1",
     *         "name": Issue 1
     *     }
     * }
     */
    public function getIssue(int $id): ApiResponse
    {
        $issue = $this->service->getIssue($id);

        return $this->response->responseSuccess(
            $this->transformer->transformIssue($issue)
        );
    }

    /**
     * Post example.
     */
    public function createIssue(Request $request): ApiResponse
    {
        $name = $request->request->get('name');

        if (empty($name)) {
            throw new BadRequestHttpException('Issue name can not be empty.');
        }

        $issue = $this->service->createIssue($name);

        return $this->response->responseSuccess(
            $this->transformer->transformIssue($issue)
        );
    }

    /**
     * Exception Error.
     *
     * @return void
     */
    public function withoutAccess(): never
    {
        throw new AccessDeniedHttpException('You cannot access this page!');
    }
}
