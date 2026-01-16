<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        date_default_timezone_set('Europe/Lisbon');
        parent::__construct($environment, $debug);
    }

    #[\Override]
    public function getCacheDir(): string
    {
        // If to run composer install and clear the cache in pipelines.
        if ($this->environment === 'local') {
            return sys_get_temp_dir() . '/cache/webcare_projectName';
        }

        return '/var/cache/webcare_projectName/';
    }

    #[\Override]
    public function getLogDir(): string
    {
        // If to run composer install and clear the cache in pipelines.
        if ($this->environment === 'local') {
            return sys_get_temp_dir() . '/cache/webcare_projectName';
        }

        return '/var/log/webcare_projectName/';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import('../config/{packages}/*.yaml');
        $container->import('../config/{packages}/' . $this->environment . '/*.yaml');

        if (is_file(\dirname(__DIR__) . '/config/parameters.yaml')) {
            $container->import('../config/parameters.yaml');
        }

        if (is_file(\dirname(__DIR__) . '/config/services.yaml')) {
            $container->import('../config/{services}.yaml');
            $container->import('../config/{services}_' . $this->environment . '.yaml');
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        if (is_file(\dirname(__DIR__) . '/config/routes.yaml')) {
            $routes->import('../config/{routes}.yaml');
        }
    }
}
