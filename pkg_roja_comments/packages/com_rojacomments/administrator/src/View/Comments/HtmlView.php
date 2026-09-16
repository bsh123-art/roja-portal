<?php

namespace Roja\Component\Rojacomments\Administrator\View\Comments;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

final class HtmlView extends BaseHtmlView
{
    public function display($tpl = null): void
    {
        /** @var \Roja\Component\Rojacomments\Administrator\Model\CommentsModel $model */
        $model = $this->getModel();
        $this->items = $model->getItems();
        $this->stats = $model->getDashboardStats();
        $this->pagination = $model->getPagination();
        $this->state = $model->getState();

        parent::display($tpl);
    }
}
