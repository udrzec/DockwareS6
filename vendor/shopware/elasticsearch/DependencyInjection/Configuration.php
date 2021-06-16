<?php declare(strict_types=1);

namespace Shopware\Elasticsearch\DependencyInjection;

use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('elasticsearch');

        $debug = ($_SERVER['APP_ENV'] ?? 'prod') !== 'prod';

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->booleanNode('enabled')->end()
                ->booleanNode('indexing_enabled')->end()
                ->scalarNode('hosts')->end()
                ->scalarNode('index_prefix')->end()
                ->scalarNode('throw_exception')->end()
                ->scalarNode('logger_level')->defaultValue($debug ? Logger::DEBUG : Logger::ERROR)->end()
            ->end();

        return $treeBuilder;
    }
}
