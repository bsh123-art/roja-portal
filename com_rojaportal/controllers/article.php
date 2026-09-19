<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

require_once JPATH_COMPONENT . '/helpers/navigation.php';

class RojaPortalControllerArticle extends BaseController
{
    public function display($cachable = false, $urlparams = false)
    {
        $app = Factory::getApplication();
        $input = $app->getInput();

        $articleId = (int) $input->getInt('article_id', 0);
        $categoryId = (int) $input->getInt('catid', 0);

        if ($articleId <= 0 || $categoryId <= 0) {
            $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
            http_response_code(400);

            echo json_encode([
                'status' => 'error',
                'message' => 'article_id and catid are required.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $app->close();
        }

        $payload = RojaPortalNavigationHelper::getAdjacentArticles($articleId, $categoryId);

        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        http_response_code(($payload['status'] ?? 'empty') === 'ok' ? 200 : 404);

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $app->close();
    }

    public function getAdjacentArticles()
    {
        return $this->display();
    }
}
