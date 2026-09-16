<?php

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Roja\Component\Rojacomments\Site\Helper\CommentHelper;

final class PlgContentRojacomments extends CMSPlugin
{
    public function onContentPrepare(string $context, object &$article, ?object $params = null, int $page = 0): void
    {
        try {
            if (!class_exists(CommentHelper::class) || !method_exists(CommentHelper::class, 'isCommentsAllowed')) {
                return;
            }

            $app = Factory::getApplication();
            if ($context !== 'com_content.article' || $app->isClient('administrator')) {
                return;
            }

            if (!$this->params->get('enabled', 1)) {
                return;
            }

            if (!is_object($article) || !isset($article->id) || empty($article->id)) {
                return;
            }

            if (!class_exists(ComponentHelper::class)) {
                return;
            }

            $componentParams = ComponentHelper::getParams('com_rojacomments');
            if (!(int) $componentParams->get('enabled', 1)) {
                return;
            }

            if (!CommentHelper::isCommentsAllowed((int) $article->id)) {
                return;
            }

            $count = CommentHelper::getCommentCount((int) $article->id);
            $document = $app->getDocument();
            $wa = $document->getWebAssetManager();
            $wa->registerStyle('rojacomments', 'plg_content_rojacomments/rojacomments.css');
            $wa->registerScript('rojacomments', 'plg_content_rojacomments/rojacomments.js', ['defer' => true]);
            $wa->useStyle('rojacomments')->useScript('rojacomments');

            $maxLength = (int) $componentParams->get('max_comment_length', 2000);
            $maxDepth = (int) $componentParams->get('max_reply_depth', 2);
            $articleTitle = htmlspecialchars((string) ($article->title ?? ''), ENT_QUOTES, 'UTF-8');
            $articleText = htmlspecialchars((string) ($article->title ?? ''), ENT_QUOTES, 'UTF-8');
            $article->text .= '
<div class="roja-comments-shell" data-roja-article-id="' . (int) $article->id . '" data-roja-max-length="' . $maxLength . '" data-roja-max-depth="' . $maxDepth . '" data-roja-article-title="' . $articleText . '">
    <div class="roja-comments-actions" aria-label="Aksi komentar artikel">
        <button type="button" class="roja-comments-action-btn" data-roja-action="recommend-article" aria-label="Rekomendasikan artikel">
            <span aria-hidden="true">♡</span> Rekomendasikan
        </button>
        <button type="button" class="roja-comments-trigger" data-roja-open aria-controls="roja-comments-drawer" aria-expanded="false">
            <span aria-hidden="true">💬</span>
            <span data-roja-comment-total>' . $count . '</span>
            <span>Komentar</span>
        </button>
        <button type="button" class="roja-comments-action-btn" data-roja-action="share-article" aria-label="Bagikan artikel">
            <span aria-hidden="true">↗</span> Bagikan
        </button>
    </div>

    <div class="roja-comments-overlay" data-roja-overlay aria-hidden="true"></div>

    <aside class="roja-comments-drawer" id="roja-comments-drawer" data-roja-drawer aria-hidden="true" aria-labelledby="roja-comments-title">
        <header class="roja-comments-header">
            <div>
                <strong><span data-roja-comment-total-header>' . $count . '</span> Komentar pada</strong>
                <div class="roja-comments-article-title" id="roja-comments-title">' . $articleTitle . '</div>
            </div>
            <button type="button" class="roja-comments-close" data-roja-close aria-label="Tutup komentar">×</button>
        </header>

        <div class="roja-comments-body">
            <div class="roja-comments-form-wrap">
                <label class="roja-comments-label" for="roja-comments-textarea">Bagikan pendapat Anda...</label>
                <textarea id="roja-comments-textarea" class="roja-comments-textarea" maxlength="' . $maxLength . '" data-roja-comment-input placeholder="Bagikan pendapat Anda..."></textarea>
                <div class="roja-comments-form-meta">
                    <span data-roja-counter>0 / ' . $maxLength . '</span>
                    <div class="roja-comments-form-actions">
                        <button type="button" class="roja-comments-secondary" data-roja-cancel>Batal</button>
                        <button type="button" class="roja-comments-primary" data-roja-submit>Kirim</button>
                    </div>
                </div>
            </div>

            <div class="roja-comments-toolbar">
                <div class="roja-comments-tabs" role="tablist" aria-label="Filter komentar">
                    <button type="button" class="roja-comments-tab is-active" data-roja-filter="all">Semua</button>
                    <button type="button" class="roja-comments-tab" data-roja-filter="reader_picks">Pilihan Pembaca</button>
                </div>

                <label class="roja-comments-sort-wrap" for="roja-comments-sort">Urutkan</label>
                <select id="roja-comments-sort" class="roja-comments-sort" data-roja-sort>
                    <option value="newest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                    <option value="recommended">Paling Direkomendasikan</option>
                </select>
            </div>

            <div class="roja-comments-status" data-roja-status role="status" aria-live="polite"></div>
            <div class="roja-comments-list" data-roja-list></div>
            <button type="button" class="roja-comments-more" data-roja-load-more hidden>Muat komentar lainnya</button>
        </div>
    </aside>
</div>
';
        } catch (\Throwable $e) {
            return;
        }
    }
}
