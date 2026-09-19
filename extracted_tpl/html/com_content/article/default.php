<?php
/**
 * ROJA Portal: render article details without the inherited menu page heading.
 * Delegate to Joomla's installed layout so core content features stay current.
 */
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

/** @var \Joomla\Component\Content\Site\View\Article\HtmlView $this */
$rojaOriginalViewParams = $this->params;
$this->params = clone $rojaOriginalViewParams;
$this->params->set('show_page_heading', 0);
$this->params->set('show_navigation', 0);

try {
    require JPATH_SITE . '/components/com_content/tmpl/article/default.php';
} finally {
    // Do not change the shared menu/component parameters for other renderers.
    $this->params = $rojaOriginalViewParams;
}

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$database = Factory::getContainer()->get('DatabaseDriver');
$currentArticle = $this->item;
$findAdjacentArticle = static function (string $direction) use ($database, $currentArticle): array {
    $currentId = (int) ($currentArticle->id ?? 0);
    $categoryId = (int) ($currentArticle->catid ?? 0);
    $publishDate = trim((string) ($currentArticle->publish_up ?? ''));

    if (!$currentId || !$categoryId || !$publishDate) {
        return ['link' => '', 'title' => ''];
    }

    $query = $database->getQuery(true)
        ->select([
            $database->quoteName('a.id'),
            $database->quoteName('a.title'),
            $database->quoteName('a.alias'),
            $database->quoteName('a.catid'),
            $database->quoteName('a.publish_up'),
        ])
        ->from($database->quoteName('#__content', 'a'))
        ->where($database->quoteName('a.state') . ' = 1')
        ->where($database->quoteName('a.id') . ' <> ' . $currentId)
        ->where($database->quoteName('a.catid') . ' = ' . $categoryId)
        ->where($database->quoteName('a.publish_up') . ' IS NOT NULL');

    if ($direction === 'previous') {
        $query->where('(' . $database->quoteName('a.publish_up') . ' < ' . $database->quote($publishDate)
            . ' OR (' . $database->quoteName('a.publish_up') . ' = ' . $database->quote($publishDate)
            . ' AND ' . $database->quoteName('a.id') . ' < ' . $currentId . '))')
            ->order($database->quoteName('a.publish_up') . ' DESC, ' . $database->quoteName('a.id') . ' DESC');
    } else {
        $query->where('(' . $database->quoteName('a.publish_up') . ' > ' . $database->quote($publishDate)
            . ' OR (' . $database->quoteName('a.publish_up') . ' = ' . $database->quote($publishDate)
            . ' AND ' . $database->quoteName('a.id') . ' > ' . $currentId . '))')
            ->order($database->quoteName('a.publish_up') . ' ASC, ' . $database->quoteName('a.id') . ' ASC');
    }

    $item = $database->setQuery($query, 0, 1)->loadObject();
    if (!$item) {
        return ['link' => '', 'title' => ''];
    }

    $link = 'index.php?option=com_content&view=article&id=' . (int) $item->id . '&catid=' . (int) $item->catid;
    if (class_exists('\Joomla\Component\Content\Site\Helper\RouteHelper')) {
        $link = \Joomla\CMS\Router\Route::_(
            \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute((int) $item->id, (int) $item->catid)
        );
    }

    return [
        'link' => trim((string) $link),
        'title' => trim((string) ($item->title ?? '')),
    ];
};
$articleNavigation = [];
foreach (['previous', 'next'] as $direction) {
    $adjacent = $findAdjacentArticle($direction);
    $link = trim((string) ($adjacent['link'] ?? ''));
    $title = trim((string) ($adjacent['title'] ?? ''));

    if ($link === '') {
        continue;
    }

    $label = $direction === 'previous'
        ? Text::_('TPL_ROJA_PREVIOUS_ARTICLE')
        : Text::_('TPL_ROJA_NEXT_ARTICLE');
    $labelText = $direction === 'previous'
        ? Text::_('TPL_ROJA_PREVIOUS_ARTICLE_LABEL')
        : Text::_('TPL_ROJA_NEXT_ARTICLE_LABEL');
    $actionText = $direction === 'previous'
        ? Text::_('TPL_ROJA_PREVIOUS_ACTION')
        : Text::_('TPL_ROJA_NEXT_ACTION');

    $title = $title !== '' ? $title : $label;

    $articleNavigation[] = '<a class="rp-article-nav-card ' . $direction . '" href="' . $escape($link) . '">'
        . '<span class="rp-article-nav-label">' . $escape($labelText) . '</span>'
        . '<span class="rp-article-nav-title">' . $escape($title) . '</span>'
        . '<span class="rp-article-nav-action">' . $escape($actionText) . '</span>'
        . '</a>';
}

if ($articleNavigation !== []) {
    echo '<nav class="rp-article-nav" aria-label="Article navigation"><div class="rp-article-nav-grid">'
        . implode('', $articleNavigation)
        . '</div></nav>';
}

$articleTools = '<div class="rp-article-tools" role="toolbar" aria-label="' . $escape(Text::_('TPL_ROJA_ARTICLE_TOOLS')) . '">'
    . '<button type="button" class="rp-article-tool rp-article-listen" aria-label="' . $escape(Text::_('TPL_ROJA_LISTEN_ARTICLE')) . '">'
    . '<svg aria-hidden="true" viewBox="0 0 55 55" focusable="false"><path d="M22 38 37 28 22 18v20Z" fill="currentColor"/><circle cx="27.5" cy="27.5" r="26.75" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>'
    . '<span>' . $escape(Text::_('TPL_ROJA_LISTEN_ARTICLE')) . '</span><small>· ' . $escape(Text::_('TPL_ROJA_LISTEN_BROWSER')) . '</small></button>'
    . '<button type="button" class="rp-article-tool rp-article-share" aria-label="' . $escape(Text::_('TPL_ROJA_SHARE_ARTICLE')) . '">'
    . '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M4 12a8 8 0 0 1 13.5-5.8L20 8.7M20 8.7V4.5m0 4.2h-4.2M20 12a8 8 0 0 1-13.5 5.8L4 15.3m0 0v4.2m0-4.2h4.2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    . '<span>' . $escape(Text::_('TPL_ROJA_SHARE_ARTICLE')) . '</span></button>'
    . '<button type="button" class="rp-article-tool rp-article-save" role="switch" aria-checked="false" aria-label="' . $escape(Text::_('TPL_ROJA_SAVE_ARTICLE')) . '">'
    . '<svg aria-hidden="true" viewBox="0 0 18 24" focusable="false"><path d="M2 1.5h14v21l-7-5-7 5v-21Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>'
    . '<span>' . $escape(Text::_('TPL_ROJA_SAVE_ARTICLE')) . '</span></button>'
    . '<button type="button" class="rp-article-tool rp-article-comments" aria-label="' . $escape(Text::_('TPL_ROJA_OPEN_COMMENTS')) . '">'
    . '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M4 4.5h16v11H10l-6 4v-15Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>'
    . '<span>' . $escape(Text::_('TPL_ROJA_OPEN_COMMENTS')) . '</span></button>'
    . '</div>';
echo $articleTools;

require_once __DIR__ . '/roja_comments.php';
echo RojaPortalComments::render($this->item, \Joomla\CMS\Factory::getApplication());
