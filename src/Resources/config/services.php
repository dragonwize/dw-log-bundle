<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Dragonwize\DwLogBundle\Command\CreateTableCommand;
use Dragonwize\DwLogBundle\Command\DropTableCommand;
use Dragonwize\DwLogBundle\Controller\LogController;
use Dragonwize\DwLogBundle\Monolog\DbalHandler;
use Dragonwize\DwLogBundle\Repository\LogRepository;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Register repository with configured DBAL conn
    $services->set(LogRepository::class)
        ->args([service('dw_log.doctrine_dbal.connection')]);

    // Register the DBAL handler with configured DBAL conn
    $services->set(DbalHandler::class)
        ->args([
            service('dw_log.doctrine_dbal.connection'),
            'debug',
            true,
        ]);

    // Register controller
    $services->set(LogController::class)
        ->args([
            service(LogRepository::class),
            service('twig'),
        ])
        ->tag('controller.service_arguments');

    // Register console commands
    $services->set(CreateTableCommand::class)
        ->args([service('dw_log.doctrine_dbal.connection')])
        ->tag('console.command');

    $services->set(DropTableCommand::class)
        ->args([service('dw_log.doctrine_dbal.connection')])
        ->tag('console.command');
};
