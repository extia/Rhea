<?php

namespace EasyTask\Bundle\WorkflowBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('workflow');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('base_layout')
                    ->defaultValue('EasyTaskThemeBundle::layout.html.twig')
                ->end()

                ->scalarNode('timeline_template')
                    ->defaultValue('EasyTaskThemeBundle:Timeline:timeline.html.twig')
                ->end()

                ->arrayNode('workflows')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('id')->end()
                        ->scalarNode('class')->end()
                        ->arrayNode('nodes')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                            ->children()
                                ->scalarNode('id')->end()
                                ->scalarNode('class')->end()
                                ->scalarNode('route')->end()
                                ->booleanNode('bootstrap')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
