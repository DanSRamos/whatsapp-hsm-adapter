<?php

namespace Tests\Unit\Module\Issue;

use App\Module\Issue\Entity\Issue;
use App\Module\Issue\IssueService;
use App\Module\Issue\Repository\IssueRepository;
use PHPUnit\Framework\TestCase;

class IssueServiceTest extends TestCase
{
    /** @var MockObject||IssueRepository */
    private $repository;

    public function setUp(): void
    {
        $this->repository = $this->createMock(IssueRepository::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getIssueProvider')]
    public function testGetIssue($issueId, $expected)
    {
        $this->repository
            ->expects($this->once())
            ->method('getIssue')
            ->willReturn($expected);

        $service = new IssueService($this->repository);

        $result = $service->getIssue($issueId);

        $this->assertEquals($result, $expected);
    }

    public static function getIssueProvider()
    {
        return [
            'Test 0' => [
                1,
                new Issue(1, 'Issue 1'),
            ],
        ];
    }
}
