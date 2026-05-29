<?php

namespace MartenaSoft\ConsoleProfileBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ConsoleProfileExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('framework')) {
            return;
        }

        $bundleEnabled = true;

        $configs = $container->getExtensionConfig('console_profile');

        foreach ($configs as $config) {
            if (isset($config['enabled'])) {
                $bundleEnabled = (bool) $config['enabled'];
            }
        }

        if (!$bundleEnabled) {
            $container->prependExtensionConfig('framework', [
                'profiler' => [
                    'enabled' => false,
                    'collect' => false,
                ],
            ]);
            return;
        }

        $container->prependExtensionConfig('framework', [
            'profiler' => [
                'enabled' => true,
                'collect' => true,
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('service.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('console_profile', $config);
        
        if (!$config['enabled']) {
            // $container->removeDefinition('my_bundle.listener');
        }
    }
}

