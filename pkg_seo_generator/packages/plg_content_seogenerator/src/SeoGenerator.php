<?php

final class SeoGenerator
{
    private const ENGLISH_STOPWORDS = [
        'a','about','above','after','again','against','all','am','an','and','any','are','as','at','be',
        'because','been','before','being','below','between','both','but','by','can','could','did','do','does',
        'doing','down','during','each','few','for','from','further','had','has','have','having','he','her','here',
        'hers','herself','him','himself','his','how','i','if','in','into','is','it','its','itself','just','me',
        'more','most','my','myself','no','nor','not','of','off','on','once','only','or','other','our','ours','ourselves',
        'out','over','own','same','she','should','so','some','such','than','that','the','their','theirs','them','themselves',
        'then','there','these','they','this','those','through','to','too','under','until','up','very','was','we','were','what',
        'when','where','which','while','who','whom','why','with','you','your','yours','yourself','yourselves'
    ];

    private const GERMAN_STOPWORDS = [
        'aber','alle','allem','allen','aller','alles','als','also','am','an','ander','andere','anderem','anderen',
        'anderer','anderes','anderm','andern','anders','auch','auf','aus','bei','bin','bis','bist','da','damit','dann',
        'der','den','des','dem','die','das','dass','dass','dazu','dein','deine','deinem','deinen','deiner','deines',
        'denn','derer','derselbe','derselben','denselben','desselben','deshalb','diese','diesem','diesen','dieser',
        'dieses','doch','dort','drin','durch','ein','eine','einem','einen','einer','eines','einerseits','einig',
        'einige','einigem','einigen','einiger','einiges','einmal','er','es','euer','eure','eurem','euren','eurer','eures',
        'fuer','gab','ganz','gegen','gehen','geht','gemacht','gewesen','habe','haben','hat','hatte','hatten','hier',
        'hin','hinter','ich','ihm','ihn','ihnen','ihr','ihre','ihrem','ihren','ihrer','ihres','im','in','indem','ins',
        'ist','jede','jedem','jeden','jeder','jedes','jene','jenem','jenen','jener','jenes','jetzt','kann','kein',
        'keine','keinem','keinen','keiner','keines','konnte','konnten','machen','man','manner','mehr','mein','meine',
        'meinem','meinen','meiner','meines','mit','muss','musste','nach','nicht','noch','nun','nur','ob','oder','ohne',
        'sagt','sagte','sah','sehr','sei','seid','sein','seine','seinem','seinen','seiner','seines','selbst','sich',
        'sie','sind','so','solche','solchem','solchen','solcher','solches','soll','sollen','sollte','sondern','sprache',
        'statt','such','tagen','teil','und','uns','unsere','unserem','unseren','unserer','unseres','unter','viel','vom',
        'von','vor','war','waren','warst','was','weg','weil','weiter','weitere','weiterem','weiteren','weiterer','weiteres',
        'wenn','wer','wie','wieder','will','wir','wird','wirst','wo','wollen','worden','wurde','wurden','zwar','zum','zur'
    ];

    private array $blacklist = [];

    public function __construct(array $customBlacklist = [])
    {
        $this->blacklist = $this->normalizeStopWords($customBlacklist);
    }

    public function buildMetadata(array $article, array $options = []): array
    {
        $title = (string) ($article['title'] ?? '');
        $content = (string) ($article['content'] ?? '');
        $language = (string) ($options['blacklist_language'] ?? 'combined');
        $source = (string) ($options['source'] ?? 'both');
        $custom = (string) ($options['custom_blacklist'] ?? '');

        $stopWords = $this->getStopWords($language, $custom);
        $contentPart = $this->prepareText($content);
        $titlePart = $this->prepareText($title);

        $inputText = $source === 'title' ? $titlePart : ($source === 'content' ? $contentPart : $titlePart . ' ' . $contentPart);
        $keywords = $this->generateKeywordsFromText($inputText, [
            'min_word_length' => (int) ($options['min_word_length'] ?? 4),
            'max_keywords' => (int) ($options['max_keywords'] ?? 10),
            'stop_words' => $stopWords,
            'title' => $titlePart,
        ]);

        $description = $this->generateDescriptionFromText($titlePart, $contentPart, (int) ($options['description_length'] ?? 180));

        return [
            'keywords' => $keywords,
            'description' => $description,
            'robots' => (string) ($options['robots'] ?? 'index,follow'),
        ];
    }

    public function generateKeywords(string $title, string $content, array $options = []): string
    {
        $source = (string) ($options['source'] ?? 'both');
        $titleText = $this->prepareText($title);
        $contentText = $this->prepareText($content);
        $inputText = $source === 'title' ? $titleText : ($source === 'content' ? $contentText : $titleText . ' ' . $contentText);

        return $this->generateKeywordsFromText($inputText, [
            'min_word_length' => (int) ($options['min_word_length'] ?? 4),
            'max_keywords' => (int) ($options['max_keywords'] ?? 10),
            'stop_words' => $this->getStopWords((string) ($options['blacklist_language'] ?? 'combined'), (string) ($options['custom_blacklist'] ?? '')),
            'title' => $titleText,
        ]);
    }

    public function generateDescription(string $title, string $content, int $maxLength = 180): string
    {
        return $this->generateDescriptionFromText($this->prepareText($title), $this->prepareText($content), $maxLength);
    }

    private function generateKeywordsFromText(string $text, array $options = []): string
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($words) || $words === []) {
            return '';
        }

        $minLength = (int) ($options['min_word_length'] ?? 4);
        $limit = max(1, (int) ($options['max_keywords'] ?? 10));
        $stopWords = $options['stop_words'] ?? [];
        $titleText = $this->prepareText((string) ($options['title'] ?? ''));
        $titleWords = array_flip(array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', strtolower($titleText), -1, PREG_SPLIT_NO_EMPTY) ?: [], static fn ($word) => is_string($word) && $word !== '')));

        $counts = [];

        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '' || mb_strlen($word) < $minLength) {
                continue;
            }

            if (isset($stopWords[$word])) {
                continue;
            }

            $counts[$word] = ($counts[$word] ?? 0) + (isset($titleWords[$word]) ? 2 : 1);
        }

        if ($counts === []) {
            return '';
        }

        uasort($counts, static fn ($left, $right) => $right <=> $left);
        if ($counts === []) {
            return '';
        }

        $keywords = array_slice(array_keys($counts), 0, $limit);

        return implode(', ', $keywords);
    }

    private function generateDescriptionFromText(string $title, string $content, int $maxLength): string
    {
        $title = trim($title);
        $content = trim($content);
        $baseText = $title !== '' ? $title . ' ' . $content : $content;
        $baseText = preg_replace('/\s+/', ' ', $baseText);
        $baseText = preg_replace('/\s+([,.;:!?])/', '$1', (string) $baseText);
        $baseText = trim((string) $baseText);

        if ($baseText === '') {
            return '';
        }

        if (mb_strlen($baseText) <= $maxLength) {
            return rtrim($baseText, ' .');
        }

        $cut = mb_substr($baseText, 0, $maxLength);
        $ending = mb_strrpos($cut, ' ');
        if ($ending !== false) {
            $cut = trim(mb_substr($cut, 0, $ending));
        }

        return rtrim($cut, ' .') . '...';
    }

    private function prepareText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/https?:\/\/[^\s]+/i', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', (string) $text);
        return trim((string) $text);
    }

    private function getStopWords(string $language, string $custom): array
    {
        $base = match ($language) {
            'english' => self::ENGLISH_STOPWORDS,
            'german' => self::GERMAN_STOPWORDS,
            'combined' => array_merge(self::ENGLISH_STOPWORDS, self::GERMAN_STOPWORDS),
            default => array_merge(self::ENGLISH_STOPWORDS, self::GERMAN_STOPWORDS),
        };

        $customs = $this->normalizeStopWords(preg_split('/[\r\n,]+/', $custom) ?: []);

        return array_fill_keys(array_merge($base, $customs), true);
    }

    private function normalizeStopWords(array $words): array
    {
        $normalized = [];
        foreach ($words as $word) {
            $word = strtolower(trim((string) $word));
            if ($word === '') {
                continue;
            }
            $normalized[] = preg_replace('/[^\p{L}\p{N}]+/u', '', $word);
        }

        return array_values(array_filter($normalized, static fn ($word) => $word !== ''));
    }
}
