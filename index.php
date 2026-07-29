<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/db.php';
require __DIR__ . '/includes/repository.php';

use Smarty\Smarty;

$smarty = new Smarty();
$pdo = getDbConnection();

$smarty->setTemplateDir(__DIR__ . '/templates/');

// Optimized by Claude code
$smarty->setCompileDir(__DIR__ . '/templates_c/');
$smarty->setCacheDir(__DIR__ . '/cache/');
// End Claude code

$smarty->assign('nav_categories', getCategories($pdo));
$smarty->assign('current_year', date('Y'));

// Optimized by Claude code
const ARTICLES_PER_PAGE = 5;
const HOME_ARTICLES_PER_CATEGORY = 3;
const RELATED_ARTICLES_LIMIT = 3;
// End Claude code

$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'category':
        $categoryId = (int)($_GET['id'] ?? 0);
        $category = getCategory($pdo, $categoryId);

        if (!$category) {
            $smarty->assign('message', 'Category not found.');
            $smarty->display('not_found.tpl');
            break;
        }

        $sort = ($_GET['sort'] ?? 'date') === 'views' ? 'views' : 'date';
        $order = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $currentPage = max(1, (int)($_GET['p'] ?? 1));

        $total = countArticlesByCategory($pdo, $categoryId);
        $totalPages = max(1, (int)ceil($total / ARTICLES_PER_PAGE));
        $currentPage = min($currentPage, $totalPages);

        $articles = getArticlesByCategory($pdo, $categoryId, $sort, $order, $currentPage, ARTICLES_PER_PAGE);

        $smarty->assign('category', $category);
        $smarty->assign('articles', $articles);
        $smarty->assign('sort', $sort);
        $smarty->assign('order', $order);
        $smarty->assign('currentPage', $currentPage);
        $smarty->assign('totalPages', $totalPages);
        $smarty->assign('pageNumbers', range(1, $totalPages));
        $smarty->display('category.tpl');
        break;

    case 'article':
        $articleId = (int)($_GET['id'] ?? 0);
        $article = getArticle($pdo, $articleId);

        if (!$article) {
            $smarty->assign('message', 'Article not found.');
            $smarty->display('not_found.tpl');
            break;
        }

        incrementArticleViews($pdo, $articleId);
        $article['views']++;

        $categoryIds = array_column($article['categories'], 'id');
        $relatedArticles = getRelatedArticles($pdo, $articleId, $categoryIds, RELATED_ARTICLES_LIMIT);

        $smarty->assign('article', $article);
        $smarty->assign('relatedArticles', $relatedArticles);
        $smarty->display('article.tpl');
        break;

    case 'home':
    default:
        $smarty->assign('categories', getCategoriesWithRecentArticles($pdo, HOME_ARTICLES_PER_CATEGORY));
        $smarty->display('home.tpl');
        break;
}
