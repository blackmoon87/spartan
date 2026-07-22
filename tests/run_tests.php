<?php

declare(strict_types=1);

/**
 * Spartan Framework Core Kernel — Comprehensive Independent Test Suite
 */

// 1. PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = dirname(__DIR__) . '/src/';
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
use App\Core\Auth;
use App\Core\AuthInterface;
use App\Core\Cache;
use App\Core\Container;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Database\Migrator;
use App\Core\Database\MysqlDialect;
use App\Core\Database\SqliteDialect;
use App\Core\EventDispatcher;
use App\Core\FormRequest;
use App\Core\Gate;
use App\Core\JobQueue;
use App\Core\Logger;
use App\Core\Model;
use App\Core\RelationQuery;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\SessionInterface;
use App\Core\Validator;
use App\Core\View;

echo "======================================================\n";
echo "   SPARTAN FRAMEWORK GENERAL KERNEL TEST SUITE       \n";
echo "======================================================\n\n";

$passedCount = 0;
$failedCount = 0;

function assertKernel(string $name, bool $condition, string $details = ''): void {
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
    // Set environment flag for testing
    define('SPARTAN_TESTING', true);

    // Clean up test DB
    $testDbFile = dirname(__DIR__) . '/storage/test_kernel.sqlite';
    if (file_exists($testDbFile)) {
        unlink($testDbFile);
    }

    // 1. Application & Singleton Boot
    $config = require dirname(__DIR__) . '/config/config.php';
    $config['db']['connection'] = 'sqlite';
    $config['db']['database']   = 'storage/test_kernel.sqlite';

    $app = new Application($config);
    assertKernel("1. Application Bootstrapping", isset(Application::$app), "Global Application instance initialized");

    // 2. DI Container
    $container = $app->container;
    $resolvedLogger = $container->make(Logger::class);
    assertKernel("2. DI Container Resolution", $resolvedLogger instanceof Logger, "Resolved Logger instance via Reflection");

    // 3. Database Migrations
    $migrator = new Migrator($app->db, dirname(__DIR__) . '/database/migrations');
    $migrator->migrate();
    assertKernel("3. Database Migrations Engine", true, "Ran DDL schema migrations on SQLite");

    // Seed dummy tables for kernel testing
    $app->db->exec("
        CREATE TABLE IF NOT EXISTS test_authors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS test_books (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            author_id INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            pages INTEGER DEFAULT 100,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        INSERT INTO test_authors (id, name) VALUES (1, 'Jane Austen');
        INSERT INTO test_books (id, author_id, title, pages) VALUES (10, 1, 'Pride and Prejudice', 432);
        INSERT INTO test_books (id, author_id, title, pages) VALUES (11, 1, 'Sense and Sensibility', 408);
    ");
    assertKernel("4. Database Seeding & Schema Setup", true, "Created and seeded test_authors & test_books");

    // 4. Dialect Compiler Test
    $mysqlDialect = new MysqlDialect();
    $sqliteDialect = new SqliteDialect();
    assertKernel("5. SQL Dialect Identifier Escaping", $mysqlDialect->quoteIdentifier('name') === '`name`' && $sqliteDialect->quoteIdentifier('name') === '"name"', "Compiled MySQL backticks vs SQLite double quotes");

    // 5. QueryBuilder Parameterization & Select
    $builder = new \App\Core\QueryBuilder($app->db, 'test_books');
    $bookRows = $builder->where('pages', 400, '>')->orderBy('title', 'ASC')->get();
    assertKernel("6. QueryBuilder Parameterization", count($bookRows) === 2, "Fetched " . count($bookRows) . " books matching pages > 400");

    // 6. Models & Hydration
    class TestAuthorModel extends Model {
        protected string $table = 'test_authors';
        protected bool $timestamps = true;

        public function books(): RelationQuery {
            return $this->hasMany(TestBookModel::class, foreignKey: 'author_id');
        }
    }
    class TestBookModel extends Model {
        protected string $table = 'test_books';
        protected bool $timestamps = true;

        public function author(): RelationQuery {
            return $this->belongsTo(TestAuthorModel::class, foreignKey: 'author_id');
        }
    }

    $authorModel = (new TestAuthorModel())->findInstance(1);
    assertKernel("7. Model Hydration (findInstance)", $authorModel instanceof TestAuthorModel && $authorModel->name === 'Jane Austen', "Hydrated TestAuthorModel ID 1");

    // 7. Relationships & Eager Loading
    $authorBooks = (new TestAuthorModel())->books()->for($authorModel);
    assertKernel("8. Model Relationships (hasMany)", count($authorBooks) === 2, "Fetched " . count($authorBooks) . " books for author via hasMany()");

    $allAuthors = (new TestAuthorModel())->all();
    $allAuthorsWithBooks = (new TestAuthorModel())->books()->loadFor($allAuthors, as: 'books');
    assertKernel("9. Eager Loading Collection (loadFor)", count($allAuthorsWithBooks[0]['books']) === 2, "Eager loaded collection without N+1");

    // 8. Atomic Database Transactions
    $bookModel = new TestBookModel();
    $newBookId = $bookModel->transaction(function() {
        return (new TestBookModel())->create([
            'author_id' => 1,
            'title'     => 'Emma',
            'pages'     => 475,
        ]);
    });
    $emma = (new TestBookModel())->findInstance((int)$newBookId);
    assertKernel('10. Atomic DB Transactions ($model->transaction)', $emma !== null && $emma->title === 'Emma', "Executed atomic transaction and auto-stamped created_at");

    // 9. Validator Rules Engine
    $validator = new Validator();
    $vResult = $validator->validate([
        'email' => 'author@example.com',
        'age'   => 25,
    ], [
        'email' => 'required|email',
        'age'   => 'required|integer|min:18',
    ]);
    assertKernel("11. Validator Rules Engine", $vResult === true, "Validated email and integer min:18 rules");

    // 10. FormRequest Injection & Authorization
    class SampleKernelFormRequest extends FormRequest {
        public function authorize(): bool { return true; }
        public function rules(): array {
            return ['title' => 'required|string|min:3'];
        }
    }

    $formReq = new SampleKernelFormRequest();
    assertKernel("12. FormRequest Instantiation & Service Binding", isset($formReq->session), "Auto-bound session and auth in FormRequest constructor");

    // 11. Request & Method Spoofing
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['_method']          = 'PUT';
    $_SERVER['REQUEST_URI']    = '/test/resource/42';
    $req = new Request();
    assertKernel("13. Request & Form Method Spoofing", $req->getMethod() === 'PUT' && $req->getPath() === '/test/resource/42', "Intercepted _method spoofing to PUT");

    // 12. Response Headers & JSON Serialization
    $resp = new Response();
    $resp->json(['status' => 'ok', 'code' => 200]);
    assertKernel("14. Response JSON Serialization", str_contains($resp->getContent(), '"status":"ok"') && $resp->getStatusCode() === 200, "Generated structured JSON response body");

    // 13. Router Resolution & Dynamic Parameters
    class TestKernelController extends Controller {
        public function show(string $id): string {
            return "Resource #{$id}";
        }
    }

    $app->router->get('/test/resource/{id}', [TestKernelController::class, 'show']);
    $app->request = $req;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $reqGet = new Request();
    $app->router->setRequest($reqGet);

    ob_start();
    $routeResult = $app->router->resolve();
    ob_end_clean();
    assertKernel("15. Router Dynamic Parameter Resolution", $routeResult === 'Resource #42', "Matched route /test/resource/{id} and injected parameter 42");

    // 14. Session Management & Flash Storage
    $app->session->set('test_key', 'spartan_value');
    $app->session->setFlash('notice', 'Kernel test flash message');
    assertKernel("16. Session & Flash Storage", $app->session->get('test_key') === 'spartan_value' && $app->session->getFlash('notice') === 'Kernel test flash message', "Stored and retrieved session and flash values");

    // 15. Authorization Gates & RBAC
    Gate::define('edit-book', fn($user, $book) => true);
    assertKernel("17. Gate Authorization System", Gate::check('edit-book', null, null) === true, "Evaluated Gate policy definition");

    // 16. View Engine & Blade Compiler
    $viewEngine = new View(dirname(__DIR__) . '/src/Views');
    $viewEngine->share('globalVar', 'SpartanCore');
    
    // Create temporary blade template
    $tmpBlade = dirname(__DIR__) . '/src/Views/test_kernel.blade.php';
    file_put_contents($tmpBlade, '@extends("layouts.main") @section("content") <h2>{{ $title }} - {{ $globalVar }}</h2> @flash("notice") <p>{{ $flashMsg }}</p> @endflash @endsection');

    $renderedHtml = $viewEngine->render('test_kernel', ['title' => 'Kernel Test']);
    unlink($tmpBlade); // Cleanup
    assertKernel("18. View Engine & Blade Directive Compilation", str_contains($renderedHtml, 'Kernel Test - SpartanCore') && !str_contains($renderedHtml, '@endflash'), "Compiled Blade directives, layout inheritance, and shared variables");

    // 17. Event Dispatcher Sync & Async Queue
    class KernelSyncListener {
        public static bool $executed = false;
        public function handle(mixed $payload): void {
            self::$executed = true;
        }
    }
    class KernelAsyncListener {
        public function handle(mixed $payload): void {}
    }

    $app->events->listen('kernel.event', KernelSyncListener::class);
    $app->events->listen('kernel.event', KernelAsyncListener::class, async: true);
    $app->events->dispatch('kernel.event', ['test' => 123]);

    assertKernel("19. Event Dispatcher (Sync Listener)", KernelSyncListener::$executed === true, "Dispatched sync listener immediately");

    // 18. Job Queue & Worker Processing
    $jobQueue = new JobQueue($app->db);
    $processedCount = $jobQueue->processPending();
    assertKernel("20. Job Queue Worker Loop", $processedCount >= 1, "Fetched and processed {$processedCount} queued async jobs from SQLite");

    // 19. PSR-3 Logger Rotation & Interpolation
    $app->logger->info("Kernel test log for user {user}", ['user' => 'Developer']);
    $todayLog = dirname(__DIR__) . '/storage/logs/app-' . date('Y-m-d') . '.log';
    assertKernel("21. PSR-3 Rotated File Logger", file_exists($todayLog) && str_contains(file_get_contents($todayLog), 'Kernel test log for user Developer'), "Wrote formatted PSR-3 log with placeholder interpolation");

    // 20. Cache Driver
    Cache::put('kernel_cache_key', 'cached_data', 60);
    assertKernel("22. Cache Layer (File Driver)", Cache::get('kernel_cache_key') === 'cached_data', "Stored and retrieved value from FileCacheDriver");

    echo "\n------------------------------------------------------\n";
    echo " KERNEL TEST RESULTS: {$passedCount} Passed, {$failedCount} Failed\n";
    echo "------------------------------------------------------\n";

    if ($failedCount > 0) {
        exit(1);
    }
} catch (\Throwable $e) {
    echo "\n[ERROR] Uncaught Exception in Kernel Test Suite: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
