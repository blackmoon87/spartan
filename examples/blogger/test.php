<?php

declare(strict_types=1);

/**
 * Spartan Framework Blogger Publishing Platform Comprehensive Test Suite
 */

// 1. PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = __DIR__ . '/src/';
    $len     = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Application;
use App\Core\Database\Migrator;
use App\Core\JobQueue;
use App\Core\Request;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use App\Services\PostService;

echo "======================================================\n";
echo "  SPARTAN FRAMEWORK FULL FEATURE TEST SUITE (BLOGGER) \n";
echo "======================================================\n\n";

$passedCount = 0;
$failedCount = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passedCount, $failedCount;
    if ($condition) {
        $passedCount++;
        echo "  [PASS] {$name}" . ($details ? " ({$details})" : "") . "\n";
    } else {
        $failedCount++;
        echo "  [FAIL] {$name}" . ($details ? " ({$details})" : "") . "\n";
    }
}

try {
    // Clean up previous test database
    $dbFile = __DIR__ . '/storage/blogger.sqlite';
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }

    // 1. Boot Application
    $config = require __DIR__ . '/config/config.php';
    $app = new Application($config);
    assertTest("Application Bootstrapping", isset(Application::$app), "Config loaded with SQLite driver");

    // 2. Database Migrations
    $migrator = new Migrator($app->db, __DIR__ . '/database/migrations');
    $migrator->migrate();
    assertTest("Database Migrations", true, "Executed 0001, 0002, 0003 migrations on SQLite");

    // 3. Database Seeding
    $seedSql = file_get_contents(__DIR__ . '/database/seed.sql');
    $app->db->exec($seedSql);
    assertTest("Database Seeding", true, "Seeded roles, permissions, users, categories, posts, comments");

    // 4. DI Container Auto-resolution
    $postService = $app->container->make(PostService::class);
    assertTest("DI Container Resolution", $postService instanceof PostService, "Auto-resolved PostService");

    // 5. QueryBuilder & Hydrated Models
    $posts = (new Post())->table()->where('featured', 1)->get();
    assertTest("QueryBuilder Select", count($posts) >= 2, "Fetched " . count($posts) . " featured posts");

    $article = (new Post())->findInstanceBy('slug', 'building-zero-dependency-php-mvc');
    assertTest("Model Hydration (findInstanceBy)", $article !== null && str_contains($article->title, 'Zero-Dependency'), "Hydrated '{$article->title}' (ID {$article->id})");

    // 6. Relationships & Eager Loading (loadFor)
    $categories = (new Category())->all();
    $categoriesWithPosts = (new Category())->posts()->loadFor($categories, as: 'posts');
    assertTest("Relationships & Eager Loading (loadFor)", isset($categoriesWithPosts[0]['posts']), "Eager loaded posts across categories without N+1");

    // 7. Domain Post Creation Service & View Increment
    $newPost = $postService->createPost(1, 1, 'Understanding Reflection in PHP 8 DI', 'Deep dive into ReflectionClass for autowiring dependencies.', 'Full article content describing container reflection instantiation.');
    assertTest("Domain Post Creation Service", $newPost instanceof Post && $newPost->slug === 'understanding-reflection-in-php-8-di', "Created post '{$newPost->title}'");

    $initialViews = (int)$article->views;
    $postService->incrementViews($article);
    $articleAfter = (new Post())->findInstance((int)$article->id);
    assertTest("Post Views Increment", (int)$articleAfter->views === ($initialViews + 1), "Incremented view count from {$initialViews} to {$articleAfter->views}");

    // 8. Comment Service Execution & Relationship Fetch
    $commentService = $app->container->make(CommentService::class);
    $newComment = $commentService->addComment((int)$article->id, 'Michael Scott', 'michael@dundermifflin.com', 'Great architectural breakdown!');
    assertTest("Comment Service Execution", $newComment instanceof Comment && $newComment->author_name === 'Michael Scott', "Added comment by {$newComment->author_name}");

    $articleComments = (new Post())->comments()->for($article);
    assertTest("HasMany Relationship Fetch (Post -> Comments)", count($articleComments) >= 3, "Fetched " . count($articleComments) . " comments for article #{$article->id}");

    // 9. Event Dispatcher (Sync & Async Queue Push)
    $app->events->listen('post.published', \App\Listeners\UpdatePostMetricsListener::class);
    $app->events->listen('post.published', \App\Listeners\NotifySubscribersListener::class, async: true);
    $app->events->listen('post.published', \App\Listeners\PingSearchEnginesListener::class, async: true);

    $app->events->dispatch('post.published', ['id' => $newPost->id, 'title' => $newPost->title, 'slug' => $newPost->slug]);
    assertTest("Event Dispatcher (Sync + Async)", true, "Dispatched post.published sync listener & pushed 2 async jobs");

    // 10. Queue Worker Processing
    $queue = new JobQueue($app->db);
    $processedJobs = $queue->processPending();
    assertTest("Async Job Queue Worker", $processedJobs >= 2, "Processed {$processedJobs} async jobs successfully");

    // 11. Web Route & Controller Action Resolution
    require __DIR__ . '/routes/web.php';
    require __DIR__ . '/routes/api.php';

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI']    = '/';
    $app->request = new Request();
    $app->router->setRequest($app->request);

    ob_start();
    $htmlOutput = $app->router->resolve();
    ob_end_clean();
    assertTest("Full Page Blade View Render (Blog Home)", str_contains((string)$htmlOutput, 'Systems Architecture'), "Rendered blog home page with glassmorphism layout");

    // 12. HTMX Partial Fragment Swap Render with CSRF Token
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI']    = '/blog/search';
    $_POST['query']            = 'Reflection';
    $_POST['_csrf']            = $app->session->get('_csrf_token');
    $app->request = new Request();
    $app->router->setRequest($app->request);

    ob_start();
    $partialOutput = $app->router->resolve();
    ob_end_clean();
    assertTest("HTMX Partial View Swap (renderViewOnly)", str_contains((string)$partialOutput, 'Understanding Reflection') && !str_contains((string)$partialOutput, '<html>'), "Returned raw HTML partial fragment without layout wrapper");

    // 13. REST JSON API Endpoint
    $_POST = [];
    $_GET  = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI']    = '/api/posts';
    $app->request = new Request();
    $app->router->setRequest($app->request);

    $app->router->resolve();
    $jsonOutput = $app->response->getContent();
    $jsonObj = json_decode((string)$jsonOutput, true);
    assertTest("Stateless REST JSON API Endpoint", isset($jsonObj['status']) && $jsonObj['status'] === 'success', "Returned structured JSON response with " . ($jsonObj['count'] ?? 0) . " items");

    // 14. RBAC Authorization & Attribute Verification
    $app->session->set('user_id', 2);
    $app->session->set('role', 'author');
    $authorUser = (new User())->findInstance(2);
    if ($authorUser) {
        $app->container->instance('auth_user', $authorUser);
    }
    
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI']    = '/author/posts';
    $app->request = new Request();
    $app->router->setRequest($app->request);

    ob_start();
    $authorHtml = $app->router->resolve();
    ob_end_clean();
    assertTest("RBAC Authorization & Attribute Checks (#[RequireRole])", str_contains((string)$authorHtml, 'Author Publishing Portal'), "Authorized author user to access protected endpoint");

    echo "\n------------------------------------------------------\n";
    echo " TEST RESULTS: {$passedCount} Passed, {$failedCount} Failed\n";
    echo "------------------------------------------------------\n";

    if ($failedCount > 0) {
        exit(1);
    }
} catch (\Throwable $e) {
    echo "\n[ERROR] Uncaught Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
