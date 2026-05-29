<?php

namespace MartenaSoft\ConsoleProfileBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ProfilerCollectorsFilterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition('profiler')
            || !$container->hasParameter('console_profile')
        ) {
            return;
        }

        $config = $container->getParameter('console_profile');
        $enabledCollectors = $config['collectors'] ?? [];

        if ([] === $enabledCollectors) {
            return;
        }

        $enabledCollectors = array_fill_keys($enabledCollectors, true);

        foreach ($container->findTaggedServiceIds('data_collector', true) as $serviceId => $tags) {
            $collectorId = $tags[0]['id'] ?? $serviceId;

            if (isset($enabledCollectors[$collectorId])) {
                continue;
            }

            $definition = $container->getDefinition($serviceId);
            $definition->clearTag('data_collector');
        }
    }
}
