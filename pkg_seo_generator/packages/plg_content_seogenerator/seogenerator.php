<?php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;

require_once __DIR__ . '/src/SeoGenerator.php';

final class PlgContentSeogenerator extends CMSPlugin
{
    private ?SeoGenerator $generator = null;

    public function __construct(&$subject, $config = [])
    {
        parent::__construct($subject, $config);
        $this->generator = new SeoGenerator();
        $this->loadLanguage();
    }

    public function onContentBeforeSave($context, $table, $isNew, $data = []): bool
    {
        if ($context !== 'com_content.article' || !is_object($table)) {
            return true;
        }

        $app = Factory::getApplication();
        if ($app->isClient('administrator') && $this->params->get('save_generated_metadata', 1)) {
            $this->populateArticleMetadata($table);
        }

        if (!$app->isClient('administrator') && $this->params->get('save_generated_metadata', 1)) {
            $this->populateArticleMetadata($table);
        }

        return true;
    }

    public function onContentPrepare($context, &$article, &$params, $page = 0): void
    {
        if ($context !== 'com_content.article' || Factory::getApplication()->isClient('administrator')) {
            return;
        }

        if (!(int) $this->params->get('generate_on_the_fly', 1)) {
            return;
        }

        $metadata = $this->generator->buildMetadata([
            'title' => (string) ($article->title ?? ''),
            'content' => $this->collectContent($article),
        ], [
            'source' => (string) $this->params->get('source', 'both'),
            'blacklist_language' => (string) $this->params->get('blacklist_language', 'combined'),
            'custom_blacklist' => (string) $this->params->get('custom_blacklist', ''),
            'min_word_length' => (int) $this->params->get('min_word_length', 4),
            'max_keywords' => (int) $this->params->get('max_keywords', 10),
            'description_length' => (int) $this->params->get('description_length', 180),
            'robots' => (string) $this->params->get('robots', 'index,follow'),
        ]);

        $document = Factory::getApplication()->getDocument();

        if (!empty($metadata['keywords'])) {
            $document->setMetaData('keywords', $metadata['keywords']);
        }

        if (!empty($metadata['description'])) {
            $document->setDescription($metadata['description']);
        }

        if (!empty($metadata['robots'])) {
            $document->setMetaData('robots', $metadata['robots']);
        }

        $googleKey = trim((string) $this->params->get('google_verification', ''));
        if ($googleKey !== '') {
            $document->setMetaData('google-site-verification', $googleKey);
        }
    }

    private function populateArticleMetadata(object $table): void
    {
        $title = (string) ($table->title ?? '');
        $content = $this->collectContent($table);

        $metadata = $this->generator->buildMetadata([
            'title' => $title,
            'content' => $content,
        ], [
            'source' => (string) $this->params->get('source', 'both'),
            'blacklist_language' => (string) $this->params->get('blacklist_language', 'combined'),
            'custom_blacklist' => (string) $this->params->get('custom_blacklist', ''),
            'min_word_length' => (int) $this->params->get('min_word_length', 4),
            'max_keywords' => (int) $this->params->get('max_keywords', 10),
            'description_length' => (int) $this->params->get('description_length', 180),
            'robots' => (string) $this->params->get('robots', 'index,follow'),
        ]);

        if (empty(trim((string) ($table->metakey ?? ''))) && !empty($metadata['keywords'])) {
            $table->metakey = $metadata['keywords'];
        }

        if (empty(trim((string) ($table->metadesc ?? ''))) && !empty($metadata['description'])) {
            $table->metadesc = $metadata['description'];
        }
    }

    private function collectContent(object $article): string
    {
        $parts = [
            $article->title ?? '',
            $article->introtext ?? '',
            $article->fulltext ?? '',
            $article->text ?? '',
            $article->description ?? '',
        ];

        $content = '';
        foreach ($parts as $part) {
            if (is_string($part) && trim($part) !== '') {
                $content .= ' ' . $part;
            }
        }

        return trim($content);
    }
}
