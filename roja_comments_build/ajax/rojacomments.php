<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

final class PlgAjaxRojacomments extends CMSPlugin
{
    public function onAjaxRojacomments(): array
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        if (!hash_equals((string) $input->get('format', ''), 'json')) {
            throw new RuntimeException('Invalid response format', 400);
        }
        if (!$app->checkToken('post')) {
            throw new RuntimeException('Token tidak valid.', 403);
        }
        $task = $input->getCmd('task', 'list');
        $db = Factory::getContainer()->get('DatabaseDriver');
        $table = $db->quoteName('#__roja_comments');
        $articleId = $input->getInt('article_id');
        $plugin = PluginHelper::getPlugin('content', 'rojacomments');
        $pluginParams = new \Joomla\Registry\Registry($plugin->params ?? '{}');
        $user = $app->getIdentity();
        $userId = $user ? (int) $user->id : 0;
        $sessionHash = hash('sha256', (string) $app->getSession()->getId());
        $now = Factory::getDate()->toSql();
        $article = $db->setQuery($db->getQuery(true)->select('id, state, access, catid')->from($db->quoteName('#__content'))->where('id = ' . $articleId))->loadObject();
        if (!$article || (int) $article->state !== 1 || (int) $article->access > 1) {
            throw new RuntimeException('Artikel tidak tersedia untuk komentar.', 404);
        }

        if ($task === 'list') {
            $sort = $input->getCmd('sort', 'newest');
            $order = $sort === 'oldest' ? 'created ASC' : ($sort === 'recommended' ? 'recommend_count DESC, created DESC' : 'created DESC');
            $query = $db->getQuery(true)->select('*')->from($table)->where('article_id = ' . $articleId)->where("status = 'published'")->order($order);
            $items = (array) $db->setQuery($query, $input->getInt('offset', 0), min(50, max(1, $input->getInt('limit', 10))))->loadObjectList();
            return ['comments' => array_map([$this, 'publicComment'], $items), 'total' => (int) $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($table)->where('article_id = ' . $articleId)->where("status = 'published'"))->loadResult()];
        }

        if (!in_array($task, ['create', 'recommend', 'report'], true)) {
            throw new RuntimeException('Task tidak tersedia.', 400);
        }
        if ($task === 'recommend') {
            $commentId = $input->getInt('comment_id');
            $voteTable = $db->quoteName('#__roja_comment_votes');
            $columns = ['comment_id', 'user_id', 'session_hash', 'created'];
            $values = [$commentId, $userId, $db->quote($sessionHash), $db->quote($now)];
            $alreadyVoted = $db->setQuery($db->getQuery(true)->select('COUNT(*)')->from($voteTable)->where('comment_id = ' . $commentId)->where('(user_id = ' . $userId . ' OR session_hash = ' . $db->quote($sessionHash) . ')'))->loadResult();
            if ((int) $alreadyVoted > 0) return ['recommended' => false, 'duplicate' => true];
            $db->setQuery($db->getQuery(true)->insert($voteTable)->columns(array_map([$db, 'quoteName'], $columns))->values(implode(',', $values)))->execute();
            $db->setQuery($db->getQuery(true)->update($table)->set('recommend_count = recommend_count + 1')->where('id = ' . $commentId))->execute();
            return ['recommended' => true];
        }
        if ($task === 'report') {
            $reportTable = $db->quoteName('#__roja_comment_reports');
            $db->setQuery($db->getQuery(true)->insert($reportTable)->columns(['comment_id','user_id','reason','description','status','created'])->values(implode(',', [$input->getInt('comment_id'), $userId, $db->quote($input->post->getCmd('reason', 'other')), $db->quote(substr($input->post->getString('description', ''), 0, 500)), $db->quote('pending'), $db->quote($now)])))->execute();
            return ['reported' => true];
        }
        if (!$userId && !(int) $pluginParams->get('guest_comments', 0)) throw new RuntimeException('Silakan masuk untuk berkomentar.', 403);
        $maxLength = max(100, min(10000, (int) $pluginParams->get('max_length', 2000)));
        $comment = trim($input->post->getString('comment', ''));
        if (mb_strlen($comment) < 2 || mb_strlen($comment) > $maxLength) throw new RuntimeException('Panjang komentar tidak valid.', 422);
        $parentId = $input->getInt('parent_id', 0);
        if ($parentId) {
            $parent = $db->setQuery($db->getQuery(true)->select('id, parent_id, article_id')->from($table)->where('id = ' . $parentId))->loadObject();
            $maxDepth = max(1, min(5, (int) $pluginParams->get('max_depth', 2)));
            $depth = 1;
            while ($parent && (int) $parent->parent_id > 0 && $depth < $maxDepth) {
                $parent = $db->setQuery($db->getQuery(true)->select('id, parent_id, article_id')->from($table)->where('id = ' . (int) $parent->parent_id))->loadObject();
                $depth++;
            }
            if (!$parent || (int) $parent->article_id !== $articleId || $depth >= $maxDepth) throw new RuntimeException('Kedalaman balasan maksimum tercapai.', 422);
        }
        $guestName = $userId ? '' : trim($input->post->getString('guest_name', ''));
        if (!$userId && $guestName === '') throw new RuntimeException('Nama wajib diisi.', 422);
        $db->setQuery($db->getQuery(true)->insert($table)->columns(array_map([$db, 'quoteName'], ['article_id','parent_id','user_id','guest_name','comment','status','ip_hash','user_agent','created','modified']))->values(implode(',', [$articleId,$parentId,$userId,$db->quote($guestName),$db->quote($comment),$db->quote('pending'),$db->quote(hash('sha256', (string) $input->server->getString('REMOTE_ADDR'))),$db->quote(substr($input->server->getString('HTTP_USER_AGENT'), 0, 255)),$db->quote($now),$db->quote($now)])))->execute();
        if ($parentId) $db->setQuery($db->getQuery(true)->update($table)->set('reply_count = reply_count + 1')->where('id = ' . $parentId))->execute();
        return ['created' => true, 'status' => 'pending'];
    }

    private function publicComment(object $comment): array
    {
        return ['id' => (int) $comment->id, 'parent_id' => (int) $comment->parent_id, 'name' => $comment->user_id ? 'Pengguna Joomla' : $comment->guest_name, 'comment' => $comment->comment, 'recommend_count' => (int) $comment->recommend_count, 'reply_count' => (int) $comment->reply_count, 'created' => $comment->created];
    }
}
