<?php

namespace Roja\Component\Rojacomments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

final class CommentsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'status', 'created', 'article_id'];
        }

        parent::__construct($config);
    }

    protected function getListQuery(): QueryInterface
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select(['c.id', 'c.article_id', 'c.parent_id', 'c.user_id', 'c.guest_name', 'c.comment', 'c.status', 'c.recommend_count', 'c.reply_count', 'c.created'])
            ->from($db->quoteName('#__roja_comments', 'c'))
            ->order($db->quoteName('c.created') . ' DESC');

        $status = $this->getState('filter.status');
        if ($status && $status !== '*') {
            $query->where($db->quoteName('c.status') . ' = ' . $db->quote($status));
        }

        return $query;
    }

    public function getDashboardStats(): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $stats = [
            'total' => (int) $this->countComments(),
            'pending' => 0,
            'published' => 0,
            'reported' => 0,
            'spam' => 0,
        ];

        $statusQuery = $db->getQuery(true)
            ->select([$db->quoteName('status'), 'COUNT(*) AS total'])
            ->from($db->quoteName('#__roja_comments'))
            ->group($db->quoteName('status'));

        foreach ($db->setQuery($statusQuery)->loadObjectList() as $row) {
            $status = (string) $row->status;
            if ($status === 'pending') {
                $stats['pending'] = (int) $row->total;
            }
            if ($status === 'published') {
                $stats['published'] = (int) $row->total;
            }
            if ($status === 'spam') {
                $stats['spam'] = (int) $row->total;
            }
        }

        $reportQuery = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__roja_comment_reports'))
            ->where($db->quoteName('status') . ' = ' . $db->quote('pending'));
        $stats['reported'] = (int) $db->setQuery($reportQuery)->loadResult();

        return $stats;
    }

    protected function countComments(): int
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__roja_comments'));

        return (int) $db->setQuery($query)->loadResult();
    }
}
