<?php
namespace App\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CommandCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('app')) {
            return;
        }

        $definition = $container->findDefinition('app');

        $contentTypes = $container->findTaggedServiceIds('command');

        foreach ($contentTypes as $id => $tags) {
            $definition->addMethodCall(
                'add',
                array(new Reference($id))
            );
        }
    }
}
