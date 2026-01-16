<?php

namespace App\Module\Issue\Transformer;

use App\Module\Issue\Entity\Issue;

class IssueTransformer
{
    public function transformIssues(array $issue): array
    {
        $transformed = [];
        foreach ($issue as $issue) {
            $transformed[] = $this->transformIssue($issue);
        }

        return $transformed;
    }

    public function transformIssue(Issue $issue): array
    {
        return [
            'id' => $issue->getId(),
            'name' => $issue->getName(),
        ];
    }
}
