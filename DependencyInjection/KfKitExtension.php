<?php

namespace Kf\KitBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class KfKitExtension extends Extension
{
    const ALIAS = 'kf_kit';

    /**
     * @see Symfony\Component\DependencyInjection\Extension.ExtensionInterface::load()
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $modules = array(
            'knp_menu'      => array(),
            'knp_pagination' => array(),
            'mail_dispatcher' => array(),
        );

        foreach ($configs as $config) {
            foreach (array_keys($modules) as $module) {
                if (array_key_exists($module, $config)) {
                    $modules[$module][] = isset($config[$module]) ? $config[$module] : array();
                }
            }
        }

        foreach (array_keys($modules) as $module) {
            if (!empty($modules[$module])) {
                $this->loadConfigs($module, $modules[$module], $container);
            }
        }
    }

    /**
     * @param string           $module
     * @param array            $configs
     * @param ContainerBuilder $container
     */
    private function loadConfigs($module, array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load($module . '.xml');
        if(is_array($configs[0]))
            foreach ($configs[0] as $k => $config) {
                $container->setParameter(static::ALIAS . '.' . $module . '.config.' . $k, $config);
            }
    }


    /**
     * @see Symfony\Component\DependencyInjection\Extension.ExtensionInterface::getAlias()
     * @codeCoverageIgnore
     */
    public function getAlias()
    {
        return static::ALIAS;
    }
}
