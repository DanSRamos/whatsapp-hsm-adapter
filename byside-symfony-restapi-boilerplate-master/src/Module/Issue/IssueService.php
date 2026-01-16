<?php

namespace App\Module\Issue;

use App\Module\Issue\Entity\Issue;
use App\Module\Issue\Repository\IssueRepository;

class IssueService
{
    /** @var IssueRepository */
    public $repository;

    public function __construct(IssueRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Undocumented function.
     */
    public function getIssue(int $id): Issue
    {
        return $this->repository->getIssue($id);
    }

    /**
     * Undocumented function.
     */
    public function createIssue(string $name): Issue
    {
        return $this->repository->create($name);
    }
}
