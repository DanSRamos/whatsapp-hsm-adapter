<?php

namespace App\Command;

use Cocur\BackgroundProcess\BackgroundProcess;
use Monitor\MonitorLoop;
use Monitor\MonitorWorker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Symfony command to delete old relationships.
 */
class StartRabbitConsumersCommand extends Command implements ContainerAwareInterface
{
    /** Command name. */
    protected static $defaultName = 'rabbit:consumers';

    private MonitorLoop $monitor;

    private LoggerInterface $logger;

    private ?ContainerInterface $container = null;

    /**
     * Constructor of the command.
     *
     * @param LoggerInterface         $logger    Constructor injected logger interface
     * @param ContainerInterface|null $container Constructor injected symfony container
     */
    public function __construct(LoggerInterface $logger, ?ContainerInterface $container = null)
    {
        $this->setContainer($container);
        $this->setLogger($logger);
        parent::__construct();
    }

    /**
     * Get the value of logger.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Set the value of logger.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Inherited by ContainerAwareInterface, sets the container.
     */
    public function setContainer(?ContainerInterface $container = null): ?ContainerInterface
    {
        return $this->container = $container;
    }

    /**
     * Protected method inherited from Command for setup.
     */
    protected function configure()
    {
        $name = self::$defaultName;

        $this->setName($name)
            ->setDescription('create multiple consumers for a RabbitMQ queue')
            ->addArgument('queue', InputArgument::REQUIRED, 'RabbitMQ queue');
    }

    /**
     * Protected method inherited from Command to execute the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = $input->getArgument('queue');
        $queueName = $this->container->getParameter('consumer.' . $queue . '.name');

        $this->initializeMonitor($queueName);

        $numberOfConsumers = (int) $this->container->getParameter('consumer.' . $queue . '.number');
        $numberOfMessages = (int) $this->container->getParameter('consumer.' . $queue . '.messages');

        $command = '/var/' . $_ENV['PROJECT_PATH'] . '/current/bin/console rabbitmq:consumer -m ' .
            $numberOfMessages . ' ' . $queueName;

        $backgroundProcesses = [];
        for ($i = 0; $i < $numberOfConsumers; ++$i) {
            $backgroundProcesses[$i] = new BackgroundProcess($command);
            $backgroundProcesses[$i]->run();
        }

        while ($backgroundProcesses !== []) {
            $processList = null;

            for ($i = 0; $i < $numberOfConsumers; ++$i) {
                if (!$backgroundProcesses[$i]->isRunning()) {
                    $backgroundProcesses[$i] = new BackgroundProcess($command);
                    $backgroundProcesses[$i]->run();
                }
            }

            exec('pgrep -g ' . getmypid(), $processList, $retval);
            if (count($processList) >= $numberOfConsumers) {
                $this->monitor->sendHeartbeat(60);
            } else {
                $this->getLogger()->notice(
                    'StartRabbitConsumerCommand :: ' . $queue
                        . ' :: NumberOfConsumers: ' . $numberOfConsumers . ' :: NumberOfProcess: ' . count($processList)
                );
            }

            sleep(1);
        }

        return Command::SUCCESS;
    }

    /**
     * Initialize Monitor.
     */
    private function initializeMonitor(string $channelName): MonitorWorker
    {
        $host = $_ENV['MONITOR_HOST'];
        $port = $_ENV['MONITOR_PORT'];

        $redis = new \Redis();
        $redis->pconnect($host, $port);

        $this->monitor = new MonitorLoop($channelName, $redis, []);
        $this->monitor->registerAutodiscover();

        return $this->monitor->sendHeartBeat(60);
    }
}
