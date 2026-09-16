<?php
defined('_JEXEC') or die;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Extension\PluginTrait;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
return new class() implements ServiceProviderInterface {
    public function register(Container $container): void { $container->set(PluginInterface::class, new class($container->get('Dispatcher'), ['name' => 'rojacomments', 'group' => 'ajax']) implements PluginInterface { use PluginTrait; }); }
};
