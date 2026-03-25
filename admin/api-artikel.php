<?php
require __DIR__ . '/data-store.php';

function sp_load_articles(): array {
    $articles = sp_read_json('blog_articles.json', []);
    return is_array($articles) ? array_values($articles) : [];
}

function sp_normalize_article(array $article): array {
    if (!empty($article['published_at']) && is_numeric($article['published_at'])) {
        $article['published_at'] = gmdate('c', (int)round(((float)$article['published_at']) / 1000));
    }
    if (empty($article['published_at'])) {
        $article['published_at'] = gmdate('c');
    }
    return $article;
}

function sp_save_articles(array $articles): void {
    $articles = array_map('sp_normalize_article', $articles);
    usort($articles, function ($a, $b) {
        return strtotime((string)($b['published_at'] ?? '')) <=> strtotime((string)($a['published_at'] ?? ''));
    });
    sp_write_json('blog_articles.json', array_values($articles));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = (string)($_GET['id'] ?? '');

if ($method === 'GET') {
    sp_json_response(['data' => sp_load_articles()]);
}

sp_require_admin();
$articles = sp_load_articles();
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'POST') {
    $article = sp_normalize_article($input);
    $article['id'] = (string)($article['id'] ?? $article['slug'] ?? uniqid('article_', true));
    $articles[] = $article;
    sp_save_articles($articles);
    sp_json_response($article, 201);
}

if ($method === 'PUT') {
    $target = (string)($input['id'] ?? $id);
    foreach ($articles as $index => $article) {
        if ((string)($article['id'] ?? '') === $target) {
            if (array_key_exists('published_at', $input) && ($input['published_at'] === null || $input['published_at'] === '')) {
                unset($input['published_at']);
            }
            $articles[$index] = sp_normalize_article(array_merge($article, $input));
            sp_save_articles($articles);
            sp_json_response($articles[$index]);
        }
    }
    sp_json_response(['error' => 'not_found'], 404);
}

if ($method === 'DELETE') {
    $target = (string)($id ?: ($input['id'] ?? ''));
    $before = count($articles);
    $articles = array_values(array_filter($articles, function ($article) use ($target) {
        return (string)($article['id'] ?? '') !== $target;
    }));
    if (count($articles) === $before) {
        sp_json_response(['error' => 'not_found'], 404);
    }
    sp_save_articles($articles);
    sp_json_response(['ok' => true]);
}

sp_json_response(['error' => 'method_not_allowed'], 405);
