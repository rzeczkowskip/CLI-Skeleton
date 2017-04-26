<?php
require_once __DIR__ . '/../vendor/autoload.php';

$container = new \Symfony\Component\DependencyInjection\ContainerBuilder();

$rootPath = __DIR__ . '/..';
$container->setParameter('root_path', $rootPath);

$loader = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader(
    $container,
    new \Symfony\Component\Config\FileLocator($rootPath . '/etc')
);
$loader->load('config.yml');

try {
    $container->get('dotenv')->load();
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

$container->addCompilerPass(new \App\Core\DependencyInjection\Compiler\CommandCompilerPass());
$container->compile();
