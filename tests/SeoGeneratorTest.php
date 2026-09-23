<?php
require_once __DIR__ . '/../pkg_seo_generator/packages/plg_content_seogenerator/src/SeoGenerator.php';

$generator = new SeoGenerator();

$keywords = $generator->generateKeywords(
    'Joomla SEO Tips for Better Search Rankings',
    'Learn Joomla SEO tips and how to improve rankings with better titles, keywords, and meta descriptions for search engines.'
);

if (!is_string($keywords) || strpos($keywords, 'joomla') === false) {
    fwrite(STDERR, "Keyword generation failed\n");
    exit(1);
}

$description = $generator->generateDescription(
    'Joomla SEO Tips for Better Search Rankings',
    'Learn Joomla SEO tips and how to improve rankings with better titles, keywords, and meta descriptions for search engines.'
);

if (strlen($description) < 80 || strlen($description) > 180) {
    fwrite(STDERR, "Description generation failed\n");
    exit(1);
}

fwrite(STDOUT, "SEO generator test passed\n");
