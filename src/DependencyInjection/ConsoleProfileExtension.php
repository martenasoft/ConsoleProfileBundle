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

        // По умолчанию считаем, что бандл включен
        $bundleEnabled = true;

        // Извлекаем сырые конфигурации нашего бандла console_profile
        $configs = $container->getExtensionConfig('console_profile');

        // Перебираем конфигурации (их может быть несколько из разных файлов)
        foreach ($configs as $config) {
            if (isset($config['enabled'])) {
                $bundleEnabled = (bool) $config['enabled'];
            }
        }

        // Если пользователь отключил бандл, выключаем профайлер или просто выходим
        if (!$bundleEnabled) {
            $container->prependExtensionConfig('framework', [
                'profiler' => [
                    'enabled' => false,
                    'collect' => false,
                ],
            ]);
            return;
        }

        // Если бандл включен, принудительно активируем профайлер
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

        // Необязательно, но полезно: если бандл выключен конфигом,
        // вы можете здесь удалить ваши внутренние сервисы (listeners/commands),
        // чтобы они не тратили ресурсы в продакшене.
        if (!$config['enabled']) {
            // Например: $container->removeDefinition('my_bundle.listener');
        }
    }
}

