<?php

namespace Roja\Component\Rojacomments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

final class DisplayController extends BaseController
{
    public function display($cachable = false, $urlparams = []): void
    {
        $viewName = $this->input->getCmd('view', 'comments');
        $this->input->set('view', $viewName);

        parent::display($cachable, $urlparams);
    }
}
