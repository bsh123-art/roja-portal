<?php
defined('_JEXEC') or die;

use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class() implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ComponentDispatcherFactory('Roja\\Component\\Rojacomments'));
        $container->registerServiceProvider(new MVCFactory('Roja\\Component\\Rojacomments'));
        $container->set(ComponentInterface::class, function (Container $container) {
            $component = new MVCComponent($container->get('MVCFactory'));
            $component->setMVCFactory($container->get('MVCFactory'));
            return $component;
        });
    }
};
