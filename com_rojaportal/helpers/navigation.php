<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;

final class RojaPortalNavigationHelper
{
    public static function getAdjacentArticles(int $articleId, int $categoryId): array
    {
        $db = Factory::getDbo();

        $current = self::getCurrentArticle($articleId);
        if (!$current || empty($current->publish_up)) {
            return [
                'status' => 'empty',
                'prev' => null,
                'next' => null,
            ];
        }

        return [
            'status' => 'ok',
            'prev' => self::getAdjacentArticle($db, $current, $categoryId, 'previous'),
            'next' => self::getAdjacentArticle($db, $current, $categoryId, 'next'),
        ];
    }

    private static function getCurrentArticle(int $articleId): ?object
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.catid'),
                $db->quoteName('a.publish_up'),
            ])
            ->from($db->quoteName('#__content', 'a'))
            ->where($db->quoteName('a.id') . ' = :article_id')
            ->bind(':article_id', $articleId, ParameterType::INTEGER);

        return $db->setQuery($query, 0, 1)->loadObject();
    }

    private static function getAdjacentArticle($db, object $current, int $categoryId, string $direction): ?array
    {
        $direction = strtolower($direction);
        if (!in_array($direction, ['previous', 'next'], true)) {
            return null;
        }

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('a.id'),
                $db->quoteName('a.title'),
                $db->quoteName('a.alias'),
                $db->quoteName('a.catid'),
                $db->quoteName('a.publish_up'),
            ])
            ->from($db->quoteName('#__content', 'a'))
            ->where($db->quoteName('a.state') . ' = :state')
            ->where($db->quoteName('a.catid') . ' = :catid')
            ->where($db->quoteName('a.id') . ' <> :article_id')
            ->where($db->quoteName('a.publish_up') . ' IS NOT NULL');

        if ($direction === 'previous') {
            $query
                ->where(
                    '(' . $db->quoteName('a.publish_up') . ' < :publish_up'
                    . ' OR (' . $db->quoteName('a.publish_up') . ' = :publish_up'
                    . ' AND ' . $db->quoteName('a.id') . ' < :article_id))'
                )
                ->order($db->quoteName('a.publish_up') . ' DESC, ' . $db->quoteName('a.id') . ' DESC');
        } else {
            $query
                ->where(
                    '(' . $db->quoteName('a.publish_up') . ' > :publish_up'
                    . ' OR (' . $db->quoteName('a.publish_up') . ' = :publish_up'
                    . ' AND ' . $db->quoteName('a.id') . ' > :article_id))'
                )
                ->order($db->quoteName('a.publish_up') . ' ASC, ' . $db->quoteName('a.id') . ' ASC');
        }

        $query
            ->bind(':state', 1, ParameterType::INTEGER)
            ->bind(':catid', $categoryId, ParameterType::INTEGER)
            ->bind(':article_id', (int) $current->id, ParameterType::INTEGER)
            ->bind(':publish_up', $current->publish_up, ParameterType::STRING);

        $item = $db->setQuery($query, 0, 1)->loadObject();
        if (!$item) {
            return null;
        }

        $link = 'index.php?option=com_content&view=article&id=' . (int) $item->id . '&catid=' . (int) $item->catid;

        if (class_exists('\Joomla\Component\Content\Site\Helper\RouteHelper')) {
            $link = Route::_(
                \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute((int) $item->id, (int) $item->catid)
            );
        }

        return [
            'id' => (int) $item->id,
            'title' => (string) $item->title,
            'alias' => (string) ($item->alias ?? ''),
            'slug' => (string) ($item->alias ?? ''),
            'catid' => (int) $item->catid,
            'publish_up' => (string) $item->publish_up,
            'link' => (string) $link,
            'direction' => $direction,
        ];
    }
}
