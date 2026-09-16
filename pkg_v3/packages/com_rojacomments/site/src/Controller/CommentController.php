<?php

namespace Roja\Component\Rojacomments\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Roja\Component\Rojacomments\Site\Helper\CommentHelper;

final class CommentController extends BaseController
{
    public function execute($task): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $taskName = (string) ($input->getCmd('task', $task) ?: 'display');

        if (!$app->checkToken('post')) {
            $this->respond(false, Text::_('COM_ROJACOMMENTS_INVALID_TOKEN'), [], 403);
            return;
        }

        switch ($taskName) {
            case 'comments.list':
                $this->listComments();
                break;
            case 'comments.create':
            case 'comments.reply':
                $this->createComment();
                break;
            case 'comments.recommend':
                $this->recommend();
                break;
            case 'comments.report':
                $this->report();
                break;
            default:
                $this->respond(false, Text::_('COM_ROJACOMMENTS_INVALID_ACTION'), [], 404);
                break;
        }
    }

    private function listComments(): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $articleId = (int) $input->getInt('article_id', 0);
        $filter = (string) $input->getString('filter', 'all');
        $sort = (string) $input->getString('sort', 'newest');
        $offset = (int) $input->getInt('offset', 0);
        $limit = (int) $input->getInt('limit', 20);

        if ($articleId <= 0 || !CommentHelper::isCommentsAllowed($articleId)) {
            $this->respond(false, Text::_('COM_ROJACOMMENTS_NO_COMMENTS_AVAILABLE'), [], 404);
            return;
        }

        $result = CommentHelper::listComments($articleId, $filter, $sort, $offset, $limit);
        $this->respond(true, Text::_('COM_ROJACOMMENTS_COMMENTS_LOADED'), $result, 200);
    }

    private function createComment(): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $data = [
            'article_id' => $input->getInt('article_id', 0),
            'parent_id' => $input->getInt('parent_id', 0),
            'comment' => $input->post->getString('comment', ''),
            'guest_name' => $input->post->getString('guest_name', ''),
            'guest_email' => $input->post->getString('guest_email', ''),
            'website' => $input->post->getString('website', ''),
        ];

        $result = CommentHelper::createComment($data);
        $statusCode = !empty($result['success']) ? 200 : 422;
        $this->respond((bool) ($result['success'] ?? false), (string) ($result['message'] ?? Text::_('COM_ROJACOMMENTS_COMMENT_FAILED')), ['status' => $result['status'] ?? null], $statusCode);
    }

    private function recommend(): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $commentId = (int) $input->getInt('comment_id', 0);
        $user = $app->getIdentity();
        $userId = (int) ($user->id ?? 0);
        $sessionHash = hash('sha256', (string) $app->getSession()->getId());

        if ($commentId <= 0) {
            $this->respond(false, Text::_('COM_ROJACOMMENTS_INVALID_COMMENT'), [], 422);
            return;
        }

        $result = CommentHelper::recommendComment($commentId, $userId, $sessionHash);
        $this->respond((bool) ($result['success'] ?? false), (string) ($result['message'] ?? Text::_('COM_ROJACOMMENTS_RECOMMEND_FAILED')), [], !empty($result['success']) ? 200 : 409);
    }

    private function report(): void
    {
        $app = Factory::getApplication();
        $input = $app->getInput();
        $data = [
            'comment_id' => $input->getInt('comment_id', 0),
            'reason' => $input->post->getString('reason', 'other'),
            'description' => $input->post->getString('description', ''),
        ];

        $result = CommentHelper::reportComment($data);
        $this->respond((bool) ($result['success'] ?? false), (string) ($result['message'] ?? Text::_('COM_ROJACOMMENTS_REPORT_FAILED')), [], !empty($result['success']) ? 200 : 422);
    }

    private function respond(bool $success, string $message, array $data = [], int $statusCode = 200): void
    {
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        http_response_code($statusCode);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], JSON_THROW_ON_ERROR);
        $app->close();
    }
}
