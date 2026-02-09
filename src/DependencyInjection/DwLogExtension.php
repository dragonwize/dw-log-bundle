<?php

declare(strict_types=1);

namespace Dragonwize\DwLogBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class DwLogExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        // Create parameters.
        $container->setParameter('dw_log.enabled', $config['enabled']);
        $container->setParameter('dw_log.connection_name', $config['connection_name']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        if (isset($config['connection_name'])) {
            $container->setAlias(
                'dw_log.doctrine_dbal.connection',
                'doctrine.dbal.' . $config['connection_name'] . '_connection'
            );
        }
    }
}
