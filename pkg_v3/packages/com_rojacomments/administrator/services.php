<?php

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\Service\Provider\MVCFactory as MVCProvider;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCProvider('Roja\\Component\\Rojacomments'));
        $container->set(
            ComponentDispatcherFactoryInterface::class,
            function () {
                return new \Joomla\CMS\Dispatcher\ComponentDispatcherFactory('Roja\\Component\\Rojacomments');
            }
        );
    }
};
