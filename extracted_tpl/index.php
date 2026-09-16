<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\CMS\Document\HtmlDocument $this */
$app = Factory::getApplication();
$input = $app->getInput();
$wa = $this->getWebAssetManager();
$menu = $app->getMenu();
$active = $menu->getActive();
$default = $menu->getDefault($this->language);

// A routed article/category can retain the Home Itemid. Comparing only the
// active menu id would therefore hide the component on internal pages.
// Treat the request as Home only when its routed option/view/id match the
// default menu item's query.
$defaultQuery = $default ? (array) $default->query : [];
$requestOption = $input->getCmd('option', '');
$requestView = $input->getCmd('view', '');
$requestId = $input->getInt('id', 0);

$isHome = $active && $default && (int) $active->id === (int) $default->id;

if ($isHome && !empty($defaultQuery)) {
    if (!empty($defaultQuery['option']) && $requestOption !== (string) $defaultQuery['option']) {
        $isHome = false;
    }

    if ($isHome && !empty($defaultQuery['view']) && $requestView !== (string) $defaultQuery['view']) {
        $isHome = false;
    }

    if ($isHome && isset($defaultQuery['id']) && (int) $defaultQuery['id'] !== $requestId) {
        $isHome = false;
    }
}

$wa->registerAndUseStyle('roja.portal', 'media/templates/site/roja_portal/css/template.css', ['version' => 'auto']);
$wa->registerAndUseScript('roja.portal', 'media/templates/site/roja_portal/js/template.js', ['version' => 'auto'], ['defer' => true]);

$siteTimezone = new \DateTimeZone('Asia/Makassar');
$now = new \DateTimeImmutable('now', $siteTimezone);
$dayNames = [
    Text::_('TPL_ROJA_DAY_1'),
    Text::_('TPL_ROJA_DAY_2'),
    Text::_('TPL_ROJA_DAY_3'),
    Text::_('TPL_ROJA_DAY_4'),
    Text::_('TPL_ROJA_DAY_5'),
    Text::_('TPL_ROJA_DAY_6'),
    Text::_('TPL_ROJA_DAY_7'),
];
$monthNames = [
    '',
    Text::_('TPL_ROJA_MONTH_1'),
    Text::_('TPL_ROJA_MONTH_2'),
    Text::_('TPL_ROJA_MONTH_3'),
    Text::_('TPL_ROJA_MONTH_4'),
    Text::_('TPL_ROJA_MONTH_5'),
    Text::_('TPL_ROJA_MONTH_6'),
    Text::_('TPL_ROJA_MONTH_7'),
    Text::_('TPL_ROJA_MONTH_8'),
    Text::_('TPL_ROJA_MONTH_9'),
    Text::_('TPL_ROJA_MONTH_10'),
    Text::_('TPL_ROJA_MONTH_11'),
    Text::_('TPL_ROJA_MONTH_12'),
];
$headerDay = $dayNames[(int) $now->format('N') - 1];
$headerMonth = $monthNames[(int) $now->format('n')];
$headerDate = Text::_('TPL_ROJA_LOCATION') . ', ' . $headerDay . ', ' . $now->format('d') . ' ' . $headerMonth . ' ' . $now->format('Y');
$headerTime = $now->format('H:i');
$headerTimezone = Text::_('TPL_ROJA_TIMEZONE');

$sitename = htmlspecialchars((string) $app->get('sitename'), ENT_QUOTES, 'UTF-8');
$title = htmlspecialchars((string) $this->params->get('siteTitle', $sitename), ENT_QUOTES, 'UTF-8');
$tagline = htmlspecialchars((string) $this->params->get('siteDescription', ''), ENT_QUOTES, 'UTF-8');
$accent = htmlspecialchars((string) $this->params->get('accent', '#c62026'), ENT_QUOTES, 'UTF-8');
$sticky = $this->params->get('stickyHeader', 1) ? ' is-sticky' : '';
$showComponent = !$isHome || (bool) $this->params->get('homeComponent', 0);

$option = $input->getCmd('option', '');
$view = $input->getCmd('view', '');

if ($this->params->get('logoFile')) {
    $logo = HTMLHelper::_('image', Uri::root(false) . htmlspecialchars((string) $this->params->get('logoFile'), ENT_QUOTES, 'UTF-8'), $sitename, ['class' => 'rp-logo', 'loading' => 'eager'], false, 0);
} else {
    $logo = '<span class="rp-wordmark">' . $title . '</span>';
}

$this->setMetaData('viewport', 'width=device-width, initial-scale=1');
?>
<!doctype html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
  <jdoc:include type="metas" />
  <jdoc:include type="styles" />
  <jdoc:include type="scripts" />
  <style>:root{--rp-accent:<?php echo $accent; ?>}</style>
</head>
<body id="top" class="rp-site <?php echo $isHome ? 'is-home ' : ''; ?><?php echo htmlspecialchars($option . ' view-' . $view, ENT_QUOTES, 'UTF-8'); ?>" data-rp-breaking-label="<?php echo htmlspecialchars(Text::_('TPL_ROJA_BREAKING_LABEL'), ENT_QUOTES, 'UTF-8'); ?>">
  <a class="rp-skip" href="#rp-main"><?php echo Text::_('TPL_ROJA_SKIP_CONTENT'); ?></a>

  <?php if ($this->countModules('topbar', true)) : ?>
    <div class="rp-topbar"><div class="rp-shell"><jdoc:include type="modules" name="topbar" style="none" /></div></div>
  <?php endif; ?>

  <header class="rp-header<?php echo $sticky; ?>">
    <div class="rp-shell rp-masthead">
      <button class="rp-menu-toggle" type="button" aria-label="<?php echo Text::_('TPL_ROJA_OPEN_MENU'); ?>" aria-controls="rp-navigation" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <a class="rp-brand" href="<?php echo $this->baseurl; ?>/" aria-label="<?php echo $sitename; ?>">
        <?php echo $logo; ?>
        <?php if ($tagline) : ?><span class="rp-tagline"><?php echo $tagline; ?></span><?php endif; ?>
      </a>
      <div class="rp-tools">
        <div class="rp-header-meta" aria-live="polite">
          <span class="rp-date"><?php echo htmlspecialchars($headerDate, ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="rp-time"><?php echo htmlspecialchars($headerTime . ($headerTimezone ? ' ' . $headerTimezone : ''), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <jdoc:include type="modules" name="header-tools" style="none" />
        <div class="rp-search"><jdoc:include type="modules" name="search" style="none" /></div>
      </div>
    </div>
    <div class="rp-navbar" id="rp-navigation" aria-label="<?php echo Text::_('TPL_ROJA_MAIN_NAVIGATION'); ?>">
      <button class="rp-menu-close" type="button" aria-label="<?php echo Text::_('TPL_ROJA_CLOSE_MENU'); ?>"><?php echo Text::_('TPL_ROJA_CLOSE_MENU'); ?> <span aria-hidden="true">×</span></button>
      <div class="rp-mobile-search"></div>
      <div class="rp-shell"><jdoc:include type="modules" name="menu" style="none" /></div>
    </div>
  </header>

  <?php if ($this->countModules('breaking-news', true)) : ?>
    <section class="rp-breaking"><div class="rp-shell"><jdoc:include type="modules" name="breaking-news" style="none" /></div></section>
  <?php endif; ?>

  <?php if ($this->countModules('banner-top', true)) : ?>
    <div class="rp-shell rp-ad"><jdoc:include type="modules" name="banner-top" style="none" /></div>
  <?php endif; ?>

  <?php if ($isHome && ($this->countModules('hero-main', true) || $this->countModules('hero-side', true))) : ?>
    <section class="rp-shell rp-hero">
      <div class="rp-hero-primary"><jdoc:include type="modules" name="hero-main" style="none" /></div>
      <aside class="rp-hero-secondary"><jdoc:include type="modules" name="hero-side" style="none" /></aside>
    </section>
  <?php endif; ?>

  <?php if ($isHome && $this->countModules('trending', true)) : ?>
    <section class="rp-shell rp-trending"><jdoc:include type="modules" name="trending" style="none" /></section>
  <?php endif; ?>

  <?php if ($isHome && ($this->countModules('latest-main', true) || $this->countModules('latest-sidebar', true))) : ?>
    <section class="rp-shell rp-home-latest">
      <div class="rp-home-latest-main"><jdoc:include type="modules" name="latest-main" style="none" /></div>
      <aside class="rp-home-latest-side"><jdoc:include type="modules" name="latest-sidebar" style="none" /></aside>
    </section>
  <?php endif; ?>

  <div id="rp-main" class="rp-shell rp-content <?php echo $this->countModules('sidebar-left', true) ? 'has-left ' : ''; ?><?php echo $this->countModules('sidebar-right', true) ? 'has-right' : ''; ?>">
    <?php if ($this->countModules('sidebar-left', true)) : ?><aside class="rp-sidebar"><jdoc:include type="modules" name="sidebar-left" style="none" /></aside><?php endif; ?>
    <main class="rp-main">
      <jdoc:include type="modules" name="breadcrumbs" style="none" />
      <jdoc:include type="modules" name="main-top" style="none" />
      <jdoc:include type="message" />
      <?php if ($showComponent) : ?><jdoc:include type="component" /><?php endif; ?>
      <jdoc:include type="modules" name="main-bottom" style="none" />
    </main>
    <?php if ($this->countModules('sidebar-right', true)) : ?><aside class="rp-sidebar"><jdoc:include type="modules" name="sidebar-right" style="none" /></aside><?php endif; ?>
  </div>

  <?php if ($isHome) : ?>
    <?php foreach (['category-1','category-2','category-3','category-4','editors-pick'] as $position) : ?>
      <?php if ($this->countModules($position, true)) : ?><section class="rp-shell rp-section"><jdoc:include type="modules" name="<?php echo $position; ?>" style="none" /></section><?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($this->countModules('banner-middle', true)) : ?><div class="rp-shell rp-ad"><jdoc:include type="modules" name="banner-middle" style="none" /></div><?php endif; ?>
  <?php if ($this->countModules('newsletter', true)) : ?><section class="rp-newsletter"><div class="rp-shell"><jdoc:include type="modules" name="newsletter" style="none" /></div></section><?php endif; ?>

  <footer class="rp-footer">
    <div class="rp-shell rp-footer-grid">
      <div class="rp-footer-brand"><jdoc:include type="modules" name="footer-about" style="none" /></div>
      <div><jdoc:include type="modules" name="footer-menu-1" style="none" /></div>
      <div><jdoc:include type="modules" name="footer-menu-2" style="none" /></div>
      <div><jdoc:include type="modules" name="footer-social" style="none" /></div>
    </div>
    <div class="rp-footer-bottom"><div class="rp-shell"><jdoc:include type="modules" name="footer-bottom" style="none" /><span>© <?php echo $now->format('Y'); ?> <?php echo $sitename; ?></span></div></div>
  </footer>

  <?php if ($this->params->get('backTop', 1)) : ?><a class="rp-backtop" href="#top" aria-label="<?php echo Text::_('TPL_ROJA_BACK_TO_TOP'); ?>">↑</a><?php endif; ?>
  <div class="rp-nav-overlay" hidden></div>
  <jdoc:include type="modules" name="debug" style="none" />
</body>
</html>
