<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;



    protected function process(ContainerBuilder $container): void
    {
        if ('test' === $this->environment) {
            // prevents the security token to be cleared
            $container->getDefinition('security.token_storage')->clearTag('kernel.reset');

            // prevents Doctrine entities to be detached
            $container->getDefinition('doctrine')->clearTag('kernel.reset');

            // ...
        }
    }
}
