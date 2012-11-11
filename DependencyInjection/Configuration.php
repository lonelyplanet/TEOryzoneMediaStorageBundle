<?php

namespace Oryzone\Bundle\MediaStorageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\ConfigurationInterface,
    Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

use Oryzone\Bundle\MediaStorageBundle\Variant\Variant;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    protected $defaultNamingStrategy = 'slugged';

    protected $defaultProvider = 'file';

    protected $defaultCdn = 'media_local';
    protected $defaultCdns = array(
        'media_local' => array(
            'local' => array(
                'path' => '/media/'
            )
        )
    );

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oryzone_media_storage');

        $this->addDbDriver($rootNode);
        $this->addDefaults($rootNode);
        $this->addCdns($rootNode);
        $this->addContexts($rootNode);

        return $treeBuilder;
    }

    protected function addDbDriver(ArrayNodeDefinition $root)
    {
        $root
        ->children()
            ->scalarNode('db_driver')
                ->validate()
                    ->ifNotInArray(array('orm', 'mongodb'))
                    ->thenInvalid('Invalid database driver "%s". Allowed values: "orm", "mongodb"')
                ->end()
            ->end()
        ->end();
    }

    protected function addDefaults(ArrayNodeDefinition $root)
    {
        $root->children()
            ->scalarNode('defaultCdn')
            ->defaultValue($this->defaultCdn)
            ->end()
        ->end();

        $root->children()
            ->scalarNode('defaultContext')
            ->defaultNull()
        ->end();

        $root->children()
            ->scalarNode('defaultFilesystem')
            ->defaultNull()
        ->end();

        $root->children()
            ->scalarNode('defaultProvider')
            ->defaultValue($this->defaultProvider)
        ->end();

        $root->children()
            ->scalarNode('defaultNamingStrategy')
            ->defaultValue($this->defaultNamingStrategy)
        ->end();
    }

    protected function addCdns(ArrayNodeDefinition $root)
    {
        $root
            ->children()
                ->arrayNode('cdns')
                    ->useAttributeAsKey('name')
                    ->prototype('variable')->end()
                ->end()
            ->end()
        ->end();
    }

    protected function addContexts(ArrayNodeDefinition $root)
    {
        $root
            ->children()
                ->arrayNode('contexts')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('provider')->defaultNull()->end()
                            ->scalarNode('filesystem')->defaultNull()->end()
                            ->scalarNode('cdn')->defaultNull()->end()
                            ->scalarNode('namingStrategy')->defaultNull()->end()
                            ->arrayNode('variants')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('parent')->defaultNull()->end()
                                        ->scalarNode('mode')
                                            ->beforeNormalization()
                                                ->ifTrue(function($v){ return true; })
                                                ->then(function($v){ return Variant::$VARIANT_MODE_MAP[$v]; })
                                            ->end()
                                            ->defaultValue(Variant::$VARIANT_MODE_MAP['instant'])
                                            ->validate()
                                                ->ifNotInArray(array_values(Variant::$VARIANT_MODE_MAP))
                                                ->thenInvalid('Invalid variant mode "%s". Allowed values: '. json_encode(Variant::$VARIANT_MODE_MAP))
                                            ->end()
                                        ->end()
                                        ->arrayNode('process')
                                            ->prototype('variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

}
