<?php

namespace Roja\Component\Rojacomments\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Roja\Component\Rojacomments\Site\Helper\CommentHelper;

final class CommentModel extends BaseDatabaseModel
{
    public function listComments(int $articleId, string $filter = 'all', string $sort = 'newest', int $offset = 0, int $limit = 20): array
    {
        return CommentHelper::listComments($articleId, $filter, $sort, $offset, $limit);
    }

    public function createComment(array $data): array
    {
        return CommentHelper::createComment($data);
    }

    public function recommendComment(int $commentId, int $userId, string $sessionHash): array
    {
        return CommentHelper::recommendComment($commentId, $userId, $sessionHash);
    }

    public function reportComment(array $data): array
    {
        return CommentHelper::reportComment($data);
    }
}
