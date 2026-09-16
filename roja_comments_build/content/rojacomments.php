<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

final class PlgContentRojacomments extends CMSPlugin
{
    public function onContentPrepare($context, &$article, &$params, $page = 0): void
    {
        if ($context !== 'com_content.article' || !$this->params->get('enabled', 1)
            || !is_object($article) || empty($article->id) || Factory::getApplication()->isClient('administrator')) {
            return;
        }

        $document = Factory::getApplication()->getDocument();
        $wa = $document->getWebAssetManager();
        $wa->registerAndUseStyle('rojacomments', 'plg_content_rojacomments/rojacomments.css', ['version' => '1.0.0']);
        $wa->registerAndUseScript('rojacomments', 'plg_content_rojacomments/rojacomments.js', ['version' => '1.0.0'], ['defer' => true]);
        $html = '<section class="roja-comments" data-article-id="' . (int) $article->id . '"'
            . ' data-max-length="' . (int) $this->params->get('max_length', 2000) . '"'
            . ' data-max-depth="' . (int) $this->params->get('max_depth', 2) . '"'
            . ' aria-labelledby="roja-comments-title">'
            . '<header class="roja-comments__header"><div><p class="roja-comments__eyebrow">Ruang pembaca</p><h2 id="roja-comments-title">Komentar</h2></div><span class="roja-comments__count" data-comments-count>0 komentar</span></header>'
            . '<button type="button" class="roja-comments__prompt" data-comment-open>Bagikan pendapat Anda...</button>'
            . '<div class="roja-comments__composer" data-comment-composer hidden><textarea maxlength="' . (int) $this->params->get('max_length', 2000) . '" data-comment-input placeholder="Bagikan pendapat Anda..."></textarea><div class="roja-comments__composer-footer"><span data-comment-counter>0 / ' . (int) $this->params->get('max_length', 2000) . '</span><div><button type="button" data-comment-cancel>Batal</button><button type="button" data-comment-submit>Kirim Komentar</button></div></div></div>'
            . '<div class="roja-comments__tabs"><button type="button" class="is-active" data-sort="all">Semua</button><button type="button" data-sort="recommended">Pilihan Pembaca</button><select data-sort-select aria-label="Urutkan komentar"><option value="newest">Terbaru</option><option value="oldest">Terlama</option><option value="recommended">Paling Direkomendasikan</option></select></div>'
            . '<div class="roja-comments__status" data-comment-status role="status" aria-live="polite"></div><div class="roja-comments__list" data-comment-list></div><button type="button" class="roja-comments__more" data-comment-more hidden>Muat komentar lainnya</button></section>';
        $article->text .= $html;
    }
}
