<?php
defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Component\Content\Site\Helper\RouteHelper;

final class RojaPortalComments
{
    public static function render(object $article, object $app): string
    {
        $input = $app->getInput();
        if (!$app->isClient('site') || $input->getCmd('option') !== 'com_content'
            || $input->getCmd('view') !== 'article' || $input->getCmd('tmpl') === 'component'
            || $input->getInt('print', 0)) {
            return '';
        }

        $settings = $app->getTemplate(true)->params;
        $shortname = strtolower(trim((string) $settings->get('disqusShortname', '')));
        // A shortname is a single DNS label, never a URL or arbitrary script host.
        if (!$settings->get('commentsEnabled', 0)
            || !preg_match('/\A[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\z/D', $shortname)) {
            return '';
        }

        // Disqus threads are public. Never embed private, unpublished or expired articles.
        $now = gmdate('Y-m-d H:i:s');
        if ((int) ($article->id ?? 0) < 1 || (int) ($article->state ?? 0) !== 1
            || (int) ($article->access ?? 0) !== 1 || (int) ($article->category_access ?? 0) !== 1
            || empty($article->params) || !$article->params->get('access-view', false)
            || (!empty($article->publish_up) && $article->publish_up > $now)
            || (!empty($article->publish_down) && $article->publish_down !== '0000-00-00 00:00:00' && $article->publish_down < $now)) {
            return '';
        }

        $url = Route::link('site', RouteHelper::getArticleRoute(
            (int) $article->id . ':' . (string) ($article->alias ?? ''),
            (int) $article->catid,
            (string) ($article->language ?? '*')
        ), false, Route::TLS_IGNORE, true);
        $escape = static fn ($text): string => htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $defaultHeading = Text::_('TPL_ROJA_COMMENTS_HEADING');
        $heading = trim((string) $settings->get('commentsHeading', $defaultHeading)) ?: $defaultHeading;
        $app->getDocument()->getWebAssetManager()->registerAndUseScript(
            'roja.comments', 'media/templates/site/roja_portal/js/comments.js',
            ['version' => 'auto'], ['defer' => true]
        );

        return '<section id="rp-comments" class="rp-comments" aria-labelledby="rp-comments-title"'
            . ' data-shortname="' . $escape($shortname) . '"'
            . ' data-identifier="joomla-article-' . (int) $article->id . '"'
            . ' data-url="' . $escape($url) . '" data-title="' . $escape($article->title) . '">'
            . '<header class="rp-comments-header">'
            . '<div><p class="rp-comments-eyebrow">' . $escape(Text::_('TPL_ROJA_COMMENTS_EYEBROW')) . '</p>'
            . '<h2 id="rp-comments-title">' . $escape($heading) . '</h2></div>'
            . '<div class="rp-comments-header-actions"><svg class="rp-comments-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 4.5h16v11H10l-6 4v-15Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg><button type="button" class="rp-comments-close" aria-label="' . $escape(Text::_('TPL_ROJA_CLOSE_COMMENTS')) . '"><svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m4 4 12 12m0-12L4 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button></div>'
            . '</header>'
            . '<div class="rp-comments-prompt"><p class="rp-comments-intro">' . $escape(Text::_('TPL_ROJA_COMMENTS_INTRO')) . '</p>'
            . '<div class="rp-comments-actions"><button type="button" class="rp-button rp-comments-load" aria-controls="disqus_thread"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 6h14v9H9l-4 3V6Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 10h8M8 13h5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg><span>' . $escape(Text::_('TPL_ROJA_SHOW_COMMENTS')) . '</span></button><button type="button" class="rp-comments-share" aria-label="' . $escape(Text::_('TPL_ROJA_SHARE_COMMENTS')) . '"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12a8 8 0 0 1 13.5-5.8L20 8.7M20 8.7V4.5m0 4.2h-4.2M20 12a8 8 0 0 1-13.5 5.8L4 15.3m0 0v4.2m0-4.2h4.2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg><span>' . $escape(Text::_('TPL_ROJA_SHARE_COMMENTS')) . '</span></button></div></div>'
            . '<div class="rp-comments-bar"><span>' . $escape(Text::_('TPL_ROJA_ALL_COMMENTS')) . '</span><span>' . $escape(Text::_('TPL_ROJA_COMMENTS_MODERATED')) . '</span></div>'
            . '<p class="rp-comments-status" role="status" aria-live="polite"></p>'
            . '<div id="disqus_thread"></div>'
            . '<noscript>' . $escape(Text::_('TPL_ROJA_COMMENTS_NOSCRIPT')) . '</noscript>'
            . '</section>';
    }
}
