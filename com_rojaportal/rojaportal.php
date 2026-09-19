<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

$app = Factory::getApplication();
$input = $app->getInput();

$controller = $input->getWord('controller', 'article');
$task = $input->getCmd('task', 'display');

$controllerClass = 'RojaPortalController' . ucfirst($controller);
if (!class_exists($controllerClass)) {
    $controllerClass = 'RojaPortalController';
}

$controllerObject = new $controllerClass();
$controllerObject->execute($task);
$controllerObject->redirect();
