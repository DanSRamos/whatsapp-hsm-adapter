<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestDatabaseCommand extends Command
{
    protected static $defaultName = 'db:test';

    public function __construct(protected Connection $conn)
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this
            ->setDescription('Pings a MySQL host');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = 'SELECT * FROM issue';

        try {
            $stmt = $this->conn->executeQuery($sql);

            $result = [];
            while (($row = $stmt->fetchAssociative()) !== false) {
                $result[] = $row;
            }

            var_dump($result);

            echo 'Connected to DB successfully :)' . PHP_EOL;

            return Command::SUCCESS;
        } catch (\Exception) {
            echo 'Failed to connect DB host :(' . PHP_EOL;

            return Command::FAILURE;
        }
    }
}
