<?php

namespace MartenaSoft\ConsoleProfileBundle;

use MartenaSoft\ConsoleProfileBundle\DependencyInjection\Compiler\ProfilerCollectorsFilterPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ConsoleProfileBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ProfilerCollectorsFilterPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
