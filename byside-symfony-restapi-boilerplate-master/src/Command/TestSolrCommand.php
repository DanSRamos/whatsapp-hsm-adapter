<?php

namespace App\Command;

use App\Component\Solr\SolariumClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestSolrCommand extends Command
{
    protected static $defaultName = 'solr:test';

    public function __construct(protected SolariumClient $solrClient)
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this
            ->setDescription('Pings a Solr host');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->solrClient->ping('.system');

        if ($response['status'] === 'OK') {
            echo 'Pinged Solr host successfully :)' . PHP_EOL;

            return Command::SUCCESS;
        }

        echo 'Failed to ping Solr host :(' . PHP_EOL;

        return Command::FAILURE;
    }
}
