<?php
namespace Roja\Component\Rojacomments\Administrator\View\Comments;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
final class HtmlView extends BaseHtmlView
{
    public array $comments = [];
    public function display($tpl = null): void
    {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $this->comments = (array) $db->setQuery($db->getQuery(true)->select('*')->from($db->quoteName('#__roja_comments'))->order('created DESC'), 0, 100)->loadObjectList();
        parent::display($tpl);
    }
}
