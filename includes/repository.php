<?php

// Optimized by Claude code
const ARTICLE_SORT_FIELDS = [
    'date' => 'a.created_at',
    'views' => 'a.views',
];
// End Claude code

/**
 * Fetch all categories
 */
function getCategories(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, name, description FROM categories ORDER BY name');
    return $stmt->fetchAll();
}

/**
 * Get category by id
 */
function getCategory(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT id, name, description FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    return $category ?: null;
}

/**
 * Categories with their N most recent articles attached, for the home page.
 */
function getCategoriesWithRecentArticles(PDO $pdo, int $limit = 3): array
{
    $categories = getCategories($pdo);

    foreach ($categories as &$category) {
        $category['articles'] = getArticlesByCategory($pdo, $category['id'], 'date', 'desc', 1, $limit);
        $category['total'] = countArticlesByCategory($pdo, $category['id']);
    }
    unset($category);

    return $categories;
}

function countArticlesByCategory(PDO $pdo, int $categoryId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM article_categories WHERE category_id = ?');
    $stmt->execute([$categoryId]);
    return (int)$stmt->fetchColumn();
}

function getArticlesByCategory(
    PDO $pdo,
    int $categoryId,
    string $sort = 'date',
    string $order = 'desc',
    int $page = 1,
    int $perPage = 10
): array {
    $sortColumn = ARTICLE_SORT_FIELDS[$sort] ?? ARTICLE_SORT_FIELDS['date'];
    $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
    $perPage = max(1, $perPage);
    $offset = max(0, ($page - 1) * $perPage);

    $stmt = $pdo->prepare(
        "SELECT a.id, a.name, a.description, a.image, a.views, a.created_at
         FROM articles a
         JOIN article_categories ac ON ac.article_id = a.id
         WHERE ac.category_id = ?
         ORDER BY $sortColumn $order
         LIMIT ? OFFSET ?"
    );

    $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function getArticle(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, name, description, content, image, views, created_at FROM articles WHERE id = ?'
    );
    $stmt->execute([$id]);
    $article = $stmt->fetch();

    if (!$article) {
        return null;
    }

    $article['categories'] = getArticleCategories($pdo, $id);

    return $article;
}

function getArticleCategories(PDO $pdo, int $articleId): array
{
    $stmt = $pdo->prepare(
        'SELECT c.id, c.name
         FROM categories c
         JOIN article_categories ac ON ac.category_id = c.id
         WHERE ac.article_id = ?
         ORDER BY c.name'
    );
    $stmt->execute([$articleId]);
    return $stmt->fetchAll();
}

/**
 * Get articles similar to given articleId
 */
function getRelatedArticles(PDO $pdo, int $articleId, array $categoryIds, int $limit = 3): array
{
    if (empty($categoryIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT DISTINCT a.id, a.name, a.description, a.image, a.views, a.created_at
         FROM articles a
         JOIN article_categories ac ON ac.article_id = a.id
         WHERE ac.category_id IN ($placeholders) AND a.id != ?
         ORDER BY a.created_at DESC
         LIMIT " . (int)$limit
    );
    $stmt->execute([...$categoryIds, $articleId]);

    return $stmt->fetchAll();
}

function incrementArticleViews(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('UPDATE articles SET views = views + 1 WHERE id = ?');
    $stmt->execute([$id]);
}
