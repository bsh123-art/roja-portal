<?php

namespace Roja\Component\Rojacomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

final class CommentHelper
{
    public static function getParams(): Registry
    {
        return ComponentHelper::getParams('com_rojacomments');
    }

    public static function isCommentsAllowed(int $articleId): bool
    {
        try {
            $params = self::getParams();
            if (!(int) $params->get('enabled', 1)) {
                return false;
            }

            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('catid')
                ->from($db->quoteName('#__content'))
                ->where($db->quoteName('id') . ' = :articleId')
                ->bind(':articleId', $articleId, ParameterType::INTEGER);

            $catid = (int) $db->setQuery($query)->loadResult();
            $categories = $params->get('allowed_categories', []);

            if (is_string($categories)) {
                $categories = array_filter(array_map('trim', explode(',', $categories)));
            }

            if (is_array($categories) && !empty($categories)) {
                return in_array($catid, array_map('intval', $categories), true);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getCommentCount(int $articleId): int
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__roja_comments'))
                ->where($db->quoteName('article_id') . ' = :articleId')
                ->where($db->quoteName('status') . ' = :status')
                ->bind(':articleId', $articleId, ParameterType::INTEGER)
                ->bind(':status', 'published');

            return (int) $db->setQuery($query)->loadResult();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function listComments(int $articleId, string $filter = 'all', string $sort = 'newest', int $offset = 0, int $limit = 20): array
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__roja_comments'))
            ->where($db->quoteName('article_id') . ' = :articleId')
            ->where($db->quoteName('status') . ' = :status')
            ->bind(':articleId', $articleId, ParameterType::INTEGER)
            ->bind(':status', 'published');

        if ($filter === 'reader_picks') {
            $query->order($db->quoteName('recommend_count') . ' DESC')
                ->order($db->quoteName('reply_count') . ' DESC')
                ->order($db->quoteName('created') . ' DESC');
        } elseif ($sort === 'oldest') {
            $query->order($db->quoteName('created') . ' ASC');
        } elseif ($sort === 'recommended') {
            $query->order($db->quoteName('recommend_count') . ' DESC')
                ->order($db->quoteName('created') . ' DESC');
        } else {
            $query->order($db->quoteName('created') . ' DESC');
        }

        $items = (array) $db->setQuery($query, $offset, $limit)->loadAssocList();
        $grouped = [];
        foreach ($items as $item) {
            $parentId = (int) ($item['parent_id'] ?? 0);
            $grouped[$parentId][] = $item;
        }

        $roots = [];
        foreach ($grouped[0] ?? [] as $root) {
            $root['children'] = self::collectChildren((int) $root['id'], $grouped);
            $roots[] = $root;
        }

        $total = (int) $db->setQuery(
            $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__roja_comments'))
                ->where($db->quoteName('article_id') . ' = :articleId')
                ->where($db->quoteName('status') . ' = :status')
                ->bind(':articleId', $articleId, ParameterType::INTEGER)
                ->bind(':status', 'published')
        )->loadResult();

        return ['items' => $roots, 'total' => $total];
    }

    private static function collectChildren(int $parentId, array $grouped): array
    {
        $children = [];
        foreach ($grouped[$parentId] ?? [] as $child) {
            $node = $child;
            $node['children'] = self::collectChildren((int) $node['id'], $grouped);
            $children[] = $node;
        }

        return $children;
    }

    public static function createComment(array $data): array
    {
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $params = self::getParams();
        $user = $app->getIdentity();
        $userId = (int) ($user->id ?? 0);
        $articleId = (int) ($data['article_id'] ?? 0);
        $parentId = (int) ($data['parent_id'] ?? 0);
        $content = trim((string) ($data['comment'] ?? ''));

        if (!$articleId || !self::isCommentsAllowed($articleId)) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_ARTICLE_NOT_ALLOWED')];
        }

        if (trim((string) ($data['website'] ?? '')) !== '') {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_INVALID_SUBMISSION')];
        }

        if ((int) $params->get('require_login', 0) && $userId <= 0) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_LOGIN_REQUIRED')];
        }

        if ($userId <= 0 && !(int) $params->get('allow_guest_comments', 1)) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_GUEST_NOT_ALLOWED')];
        }

        if ($content === '' || mb_strlen($content) > (int) $params->get('max_comment_length', 2000)) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_COMMENT_LENGTH_INVALID')];
        }

        if ($parentId > 0) {
            $parentQuery = $db->getQuery(true)
                ->select(['id', 'parent_id', 'article_id'])
                ->from($db->quoteName('#__roja_comments'))
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $parentId, ParameterType::INTEGER);
            $parent = $db->setQuery($parentQuery)->loadObject();
            if (!$parent || (int) $parent->article_id !== $articleId) {
                return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_REPLY_INVALID')];
            }

            $maxDepth = max(1, (int) $params->get('max_reply_depth', 2));
            $depth = 0;
            $current = $parent;
            while ($current && (int) $current->parent_id > 0 && $depth < $maxDepth) {
                $current = $db->setQuery(
                    $db->getQuery(true)
                        ->select(['id', 'parent_id', 'article_id'])
                        ->from($db->quoteName('#__roja_comments'))
                        ->where($db->quoteName('id') . ' = :id')
                        ->bind(':id', (int) $current->parent_id, ParameterType::INTEGER)
                )->loadObject();
                $depth++;
            }

            if ($depth >= $maxDepth) {
                return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_MAX_DEPTH_REACHED')];
            }
        }

        $status = ((int) $params->get('moderate_new_comments', 1) === 1) ? 'pending' : 'published';
        $guestName = trim((string) ($data['guest_name'] ?? ''));
        $guestEmail = trim((string) ($data['guest_email'] ?? ''));

        if ($userId <= 0 && ($guestName === '' || !filter_var($guestEmail, FILTER_VALIDATE_EMAIL))) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_GUEST_DETAILS_REQUIRED')];
        }

        $now = Factory::getDate()->toSql();
        $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__roja_comments'))
            ->columns([
                $db->quoteName('article_id'),
                $db->quoteName('parent_id'),
                $db->quoteName('user_id'),
                $db->quoteName('guest_name'),
                $db->quoteName('guest_email'),
                $db->quoteName('comment'),
                $db->quoteName('status'),
                $db->quoteName('recommend_count'),
                $db->quoteName('reply_count'),
                $db->quoteName('ip_hash'),
                $db->quoteName('user_agent'),
                $db->quoteName('created'),
                $db->quoteName('modified'),
                $db->quoteName('published')
            ])
            ->values(implode(', ', [
                (int) $articleId,
                (int) $parentId,
                (int) $userId,
                $db->quote($guestName),
                $db->quote($guestEmail),
                $db->quote($content),
                $db->quote($status),
                0,
                0,
                $db->quote($ipHash),
                $db->quote($userAgent),
                $db->quote($now),
                $db->quote($now),
                $db->quote($status === 'published' ? $now : null)
            ]));

        $db->setQuery($query)->execute();

        if ($parentId > 0) {
            $db->setQuery(
                $db->getQuery(true)
                    ->update($db->quoteName('#__roja_comments'))
                    ->set($db->quoteName('reply_count') . ' = ' . $db->quoteName('reply_count') . ' + 1')
                    ->where($db->quoteName('id') . ' = :id')
                    ->bind(':id', $parentId, ParameterType::INTEGER)
            )->execute();
        }

        return [
            'success' => true,
            'message' => $status === 'pending' ? Text::_('COM_ROJACOMMENTS_COMMENT_PENDING') : Text::_('COM_ROJACOMMENTS_COMMENT_SUCCESS'),
            'status' => $status,
        ];
    }

    public static function recommendComment(int $commentId, int $userId, string $sessionHash): array
    {
        $params = self::getParams();
        if (!(int) $params->get('enable_recommend', 1)) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_RECOMMEND_DISABLED')];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $existing = $db->setQuery(
            $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__roja_comment_votes'))
                ->where($db->quoteName('comment_id') . ' = :commentId')
                ->where('(' . $db->quoteName('user_id') . ' = :userId OR ' . $db->quoteName('session_hash') . ' = :sessionHash)')
                ->bind(':commentId', $commentId, ParameterType::INTEGER)
                ->bind(':userId', $userId, ParameterType::INTEGER)
                ->bind(':sessionHash', $sessionHash)
        )->loadResult();

        if ((int) $existing > 0) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_ALREADY_RECOMMENDED')];
        }

        $db->setQuery(
            $db->getQuery(true)
                ->insert($db->quoteName('#__roja_comment_votes'))
                ->columns([
                    $db->quoteName('comment_id'),
                    $db->quoteName('user_id'),
                    $db->quoteName('session_hash'),
                    $db->quoteName('created')
                ])
                ->values(implode(', ', [
                    (int) $commentId,
                    (int) $userId,
                    $db->quote($sessionHash),
                    $db->quote(Factory::getDate()->toSql())
                ]))
        )->execute();

        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__roja_comments'))
                ->set($db->quoteName('recommend_count') . ' = ' . $db->quoteName('recommend_count') . ' + 1')
                ->where($db->quoteName('id') . ' = :commentId')
                ->bind(':commentId', $commentId, ParameterType::INTEGER)
        )->execute();

        return ['success' => true, 'message' => Text::_('COM_ROJACOMMENTS_RECOMMEND_SUCCESS')];
    }

    public static function reportComment(array $data): array
    {
        $params = self::getParams();
        if (!(int) $params->get('enable_report', 1)) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_REPORT_DISABLED')];
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $commentId = (int) ($data['comment_id'] ?? 0);
        $reason = (string) ($data['reason'] ?? 'other');
        $description = trim((string) ($data['description'] ?? ''));
        $user = Factory::getApplication()->getIdentity();

        if ($commentId <= 0) {
            return ['success' => false, 'message' => Text::_('COM_ROJACOMMENTS_REPORT_INVALID')];
        }

        $db->setQuery(
            $db->getQuery(true)
                ->insert($db->quoteName('#__roja_comment_reports'))
                ->columns([
                    $db->quoteName('comment_id'),
                    $db->quoteName('user_id'),
                    $db->quoteName('reason'),
                    $db->quoteName('description'),
                    $db->quoteName('status'),
                    $db->quoteName('created')
                ])
                ->values(implode(', ', [
                    (int) $commentId,
                    (int) ($user->id ?? 0),
                    $db->quote($reason),
                    $db->quote(substr($description, 0, 500)),
                    $db->quote('pending'),
                    $db->quote(Factory::getDate()->toSql())
                ]))
        )->execute();

        return ['success' => true, 'message' => Text::_('COM_ROJACOMMENTS_REPORT_SUCCESS')];
    }
}
