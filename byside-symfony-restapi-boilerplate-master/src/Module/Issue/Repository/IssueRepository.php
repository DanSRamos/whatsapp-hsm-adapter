<?php

namespace App\Module\Issue\Repository;

use App\Module\Issue\Entity\Issue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class IssueRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Get by ORM.
     */
    public function getIssue(string $id): ?Issue
    {
        // Get from cache contract
        // $issue = $this->cache->get('issue', function() use ($id) {
        //     return $this->entityManager
        //         ->getRepository(Issue::class)
        //         ->find($id);
        // });

        // Get by DBAL Query Builder
        // $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        // $result = $queryBuilder->select('*')
        //    ->from('issue', 'i')
        //    ->where('id = :id')
        //    ->setParameter(':id', $id)
        //    ->execute()
        //    ->fetch();

        // Get by ORM
        $issue = $this->entityManager
            ->getRepository(Issue::class)
            ->find($id);

        if ($issue === null) {
            throw new NotFoundHttpException('No issue found for id ' . $id);
        }

        return $issue;
    }

    /**
     * Set by ORM.
     */
    public function create(string $name): Issue
    {
        $issue = new Issue();
        $issue->setName($name);

        $errors = $this->validator->validate($issue);
        if (count($errors) > 0) {
            throw new HttpException(400, strval($errors));
        }

        // tell Doctrine you want to (eventually) save the Issue (no queries yet)
        $this->entityManager->persist($issue);

        // actually executes the queries (i.e. the INSERT query)
        $this->entityManager->flush();

        return $issue;
    }
}
