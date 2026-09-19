<?php
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;

$items = $this->items ?? [];
$category = $this->category ?? null;
$pagination = $this->pagination ?? null;
$params = $this->params ?? null;

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$placeholder = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode('
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800">
  <rect width="1200" height="800" fill="#f1efe9"/>
  <rect x="60" y="60" width="1080" height="680" rx="18" fill="#ebe6df"/>
  <circle cx="600" cy="360" r="120" fill="#ddd6cf"/>
  <path d="M480 510 L720 510 L660 420 L540 420 Z" fill="#c8c0b7"/>
  <rect x="440" y="540" width="320" height="26" rx="13" fill="#c8c0b7"/>
</svg>
');

$buildArticleLink = static function ($item): string {
    $articleId = (int) ($item->id ?? 0);
    $catId = (int) ($item->catid ?? 0);
    $language = $item->language ?? '*';

    if (!$articleId) {
        return '#';
    }

    return Route::_(RouteHelper::getArticleRoute($articleId, $catId, $language));
};

$buildThumb = static function ($item) use ($placeholder, $escape): string {
    $image = '';

    if (!empty($item->images)) {
        $images = json_decode((string) $item->images, true);
        if (is_array($images)) {
            $image = $images['image_intro'] ?? $images['image_fulltext'] ?? '';
        }
    }

    if (!empty($item->image_intro) && empty($image)) {
        $image = $item->image_intro;
    }

    if ($image === '') {
        return $placeholder;
    }

    if (preg_match('#^(https?:)?//#i', $image)) {
        return $image;
    }

    if (strpos($image, 'data:') === 0) {
        return $image;
    }

    return Uri::root() . ltrim($image, '/');
};

$buildExcerpt = static function ($item): string {
    $source = trim((string) ($item->introtext ?? ''));
    if ($source === '') {
        $source = trim((string) ($item->fulltext ?? ''));
    }

    $text = strip_tags($source);
    $text = preg_replace('/\s+/', ' ', $text ?? '');
    $text = trim((string) $text);

    if ($text === '') {
        return Text::_('COM_CONTENT_NO_ARTICLES');
    }

    return HTMLHelper::_('string.truncate', $text, 150, true, false);
};
?>

<div class="rp-category-wrap">
    <?php if ($category) : ?>
        <header class="rp-category-header">
            <div class="rp-category-header__meta">
                <span class="rp-category-label"><?php echo Text::_('JGLOBAL_CATEGORY'); ?></span>
            </div>

            <h1 class="rp-category-title"><?php echo $escape($category->title ?? ''); ?></h1>

            <?php if (!empty($category->description)) : ?>
                <div class="rp-category-description"><?php echo $category->description; ?></div>
            <?php endif; ?>
        </header>
    <?php endif; ?>

    <?php if (!empty($items)) : ?>
        <div class="rp-category-grid">
            <?php foreach ($items as $item) :
                $articleLink = $buildArticleLink($item);
                $thumb = $buildThumb($item);
                $excerpt = $buildExcerpt($item);
                $publishDate = HTMLHelper::_('date', $item->publish_up ?? $item->created ?? '', Text::_('DATE_FORMAT_LC3'));
                $author = trim((string) ($item->author ?? $item->created_by_alias ?? ''));
                if ($author === '') {
                    $author = Text::_('JGLOBAL_AUTHOR');
                }
                $categoryName = trim((string) ($item->category_title ?? $category->title ?? ''));
            ?>
                <article class="rp-category-card">
                    <a href="<?php echo $escape($articleLink); ?>" class="rp-category-card__image-wrap" aria-label="<?php echo $escape($item->title ?? ''); ?>">
                        <img
                            src="<?php echo $escape($thumb); ?>"
                            alt="<?php echo $escape($item->title ?? ''); ?>"
                            loading="lazy"
                            class="rp-category-card__image"
                        >
                    </a>

                    <div class="rp-category-card__body">
                        <?php if ($categoryName !== '') : ?>
                            <span class="rp-category-card__badge"><?php echo $escape($categoryName); ?></span>
                        <?php endif; ?>

                        <h2 class="rp-category-card__title">
                            <a href="<?php echo $escape($articleLink); ?>"><?php echo $escape($item->title ?? ''); ?></a>
                        </h2>

                        <p class="rp-category-card__excerpt"><?php echo $escape($excerpt); ?></p>

                        <div class="rp-category-card__meta">
                            <span><?php echo $escape($publishDate); ?></span>
                            <span><?php echo $escape($author); ?></span>
                        </div>

                        <a href="<?php echo $escape($articleLink); ?>" class="rp-category-card__cta">
                            <?php echo Text::_('JGLOBAL_READ_MORE'); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="rp-category-empty">
            <h2>Belum ada artikel pada kategori ini</h2>
            <p>Silakan kembali nanti atau pilih kategori lain.</p>
        </div>
    <?php endif; ?>

    <?php if ($pagination && $pagination->pagesTotal > 1) : ?>
        <div class="rp-category-pagination">
            <?php echo $pagination->getPagesLinks(); ?>
        </div>
    <?php endif; ?>
</div>
