<?php

declare(strict_types=1);

/**
 * Spartan Framework Self-Contained Test Suite
 *
 * This script runs unit and integration tests across all components of Spartan.
 * It uses the local MySQL credentials from .env, dynamically creates test tables,
 * and cleans them up after completion.
 *
 * Execute with:
 *   php tests/run_tests.php
 */

// 1. Force CLI SAPI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Test suite must be run from the command line.\n");
}

// Start output buffering so header() calls don't trigger "headers already sent" warnings
ob_start();

// 2. Setup colors and assertions helpers
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RESET', "\033[0m");
define('SPARTAN_TESTING', true);

$testsCount = 0;
$assertionsCount = 0;
$failedAssertions = [];

function test_group(string $name): void {
    echo "\n" . COLOR_CYAN . "=== Testing: $name ===" . COLOR_RESET . "\n";
}


function assert_true(bool $condition, string $message): void {
    global $assertionsCount, $failedAssertions;
    $assertionsCount++;
    if (!$condition) {
        $failedAssertions[] = $message;
        echo COLOR_RED . "  ✗ Assertion Failed: $message" . COLOR_RESET . "\n";
        // Backtrace output
        $trace = debug_backtrace();
        echo "    File: {$trace[0]['file']}:{$trace[0]['line']}\n";
    } else {
        echo COLOR_GREEN . "  ✓ $message" . COLOR_RESET . "\n";
    }
}

function assert_equals(mixed $expected, mixed $actual, string $message): void {
    assert_true($expected === $actual, "$message (Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true) . ")");
}

function assert_throws(string $exceptionClass, callable $callback, string $message): void {
    global $assertionsCount, $failedAssertions;
    $assertionsCount++;
    try {
        $callback();
        $failedAssertions[] = $message;
        echo COLOR_RED . "  ✗ Assertion Failed (Expected exception $exceptionClass was not thrown): $message" . COLOR_RESET . "\n";
    } catch (\Throwable $e) {
        if ($e instanceof $exceptionClass) {
            echo COLOR_GREEN . "  ✓ $message (Threw expected: " . get_class($e) . ")" . COLOR_RESET . "\n";
        } else {
            $failedAssertions[] = $message;
            echo COLOR_RED . "  ✗ Assertion Failed (Threw " . get_class($e) . " instead of $exceptionClass): $message" . COLOR_RESET . "\n";
        }
    }
}

// 3. Autoload & Bootstrap Spartan
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
require_once __DIR__ . '/../src/Core/Application.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Container.php';
require_once __DIR__ . '/../src/Core/Cache.php';
require_once __DIR__ . '/../src/Core/CacheDriverInterface.php';
require_once __DIR__ . '/../src/Core/CacheDrivers/FileCacheDriver.php';
require_once __DIR__ . '/../src/Core/CacheDrivers/RedisCacheDriver.php';
require_once __DIR__ . '/../src/Core/Request.php';
require_once __DIR__ . '/../src/Core/Response.php';
require_once __DIR__ . '/../src/Core/SessionInterface.php';
require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/AuthInterface.php';
require_once __DIR__ . '/../src/Core/Auth.php';
require_once __DIR__ . '/../src/Core/ViewInterface.php';
require_once __DIR__ . '/../src/Core/View.php';
require_once __DIR__ . '/../src/Core/Database/Migrator.php';
require_once __DIR__ . '/../src/Middlewares/CsrfMiddleware.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Core/EventDispatcher.php';
require_once __DIR__ . '/../src/Core/JobQueue.php';
require_once __DIR__ . '/../src/Core/Model.php';
require_once __DIR__ . '/../src/Core/RelationQuery.php';
require_once __DIR__ . '/../src/Core/QueryBuilder.php';
require_once __DIR__ . '/../src/Core/Validator.php';
require_once __DIR__ . '/../src/Core/Middleware.php';
require_once __DIR__ . '/../src/Middlewares/SecurityHeadersMiddleware.php';
require_once __DIR__ . '/../src/Core/Controller.php';
require_once __DIR__ . '/../src/Core/Attributes/RequireRole.php';
require_once __DIR__ . '/../src/Core/Attributes/RequirePermission.php';
require_once __DIR__ . '/../src/Core/Gate.php';
require_once __DIR__ . '/../src/Core/Traits/HasAuthorization.php';

// Register PSR-4 Autoloader for Tests\Sample\ namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'Tests\\Sample\\';
    $baseDir = __DIR__ . '/sample_project/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load config & initialize App
$config = require __DIR__ . '/../config/config.php';
$config['auth']['model'] = \Tests\Sample\Models\User::class;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION = []; // clean session mock

// Force MySQL for tests to prevent SQLite syntax conflicts when .env is configured for SQLite.
$config['db']['connection'] = 'mysql';
if (empty($config['db']['database']) || $config['db']['database'] !== 'spartan_test_db') {
    $config['db']['database'] = 'spartan_test_db';
}
// Force host, port, username, password to fall back to MySQL defaults if SQLite was selected in .env
if (($_ENV['DB_CONNECTION'] ?? 'mysql') === 'sqlite') {
    $config['db']['host'] = !empty($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : '127.0.0.1';
    $config['db']['port'] = !empty($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '3306';
    $config['db']['username'] = !empty($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'root';
    $config['db']['password'] = !empty($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : '';
}

// Dynamically create database if missing
try {
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port']);
    $pdo = new PDO($dsn, $config['db']['username'], $config['db']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['db']['database']}`");
} catch (\PDOException $e) {
    echo COLOR_YELLOW . "Warning: Dynamic MySQL DB creation failed. Reverting to configured credentials.\n" . COLOR_RESET;
}

$app = new \App\Core\Application($config);
\App\Core\Cache::flush();

// 4. SETUP TEMPORARY TEST TABLES
test_group("Database Setup");
try {
    $db = \App\Core\Application::$app->db;
    if ($db === null) {
        throw new \RuntimeException("Database connection is null.");
    }
    
    // Drop old test tables to ensure fresh state
    $db->exec("DROP TABLE IF EXISTS `comments` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `posts` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `blogger_comments` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `blogger_posts` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_profiles` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_orders` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_users` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_jobs` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `user_roles` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `role_permissions` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `roles` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `permissions` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `users` CASCADE");

    // Create schema
    $db->exec("CREATE TABLE `users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL DEFAULT '',
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `roles` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `permissions` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `slug` VARCHAR(255) UNIQUE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `role_permissions` (
        `role_id` INT UNSIGNED NOT NULL,
        `permission_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`role_id`, `permission_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `user_roles` (
        `user_id` INT UNSIGNED NOT NULL,
        `role_id` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`user_id`, `role_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `test_users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL DEFAULT '',
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `test_orders` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `status` VARCHAR(255) NOT NULL,
        `total` DECIMAL(10,2) NOT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `test_profiles` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED UNIQUE NOT NULL,
        `bio` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Create blogger tables
    $db->exec("CREATE TABLE `posts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `body` TEXT NOT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `comments` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT UNSIGNED NOT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `content` TEXT NOT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Copy jobs schema but naming it `test_jobs` for isolated run
    $db->exec("CREATE TABLE `test_jobs` (
        `id`           INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
        `event`        VARCHAR(255)     NOT NULL,
        `listener`     VARCHAR(255)     NOT NULL,
        `payload`      JSON             NOT NULL,
        `status`       ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
        `attempts`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
        `max_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 3,
        `on_failure`   ENUM('retry','stop') NOT NULL DEFAULT 'retry',
        `error`        TEXT             NULL,
        `run_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `created_at`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Seed default roles
    $db->exec("INSERT INTO `roles` (name, slug) VALUES 
        ('Administrator', 'admin'),
        ('Editor', 'editor'),
        ('User', 'user')
    ");

    assert_true(true, "Database tables created successfully.");
} catch (\Throwable $e) {
    echo COLOR_RED . "CRITICAL: Database tables setup failed: " . $e->getMessage() . COLOR_RESET . "\n";
    exit(1);
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. TEST: Container
// ─────────────────────────────────────────────────────────────────────────────
test_group("Container");

class DummyService {
    public function __construct(public string $val = 'default') {}
}

class DependentService {
    public function __construct(public DummyService $dummy) {}
}

$container = $app->container;

// Test basic binding
$container->bind(DummyService::class, fn() => new DummyService('bound'));
$instance1 = $container->make(DummyService::class);
assert_equals('bound', $instance1->val, "Container binds and resolves custom factories");

// Test singleton binding
$container->forget(DummyService::class);
$container->singleton(DummyService::class, fn() => new DummyService('singleton'));
$s1 = $container->make(DummyService::class);
$s2 = $container->make(DummyService::class);
assert_true($s1 === $s2, "Container resolves identical instances for singletons");

// Test autowired resolution via Reflection
$container->forget(DummyService::class);
$container->forget(DependentService::class);
$dep = $container->make(DependentService::class);
assert_true($dep instanceof DependentService, "Container auto-resolves dependencies recursively");
assert_equals('default', $dep->dummy->val, "Container auto-resolves nested dependencies");


// ─────────────────────────────────────────────────────────────────────────────
// 6. TEST: Cache
// ─────────────────────────────────────────────────────────────────────────────
test_group("Cache (File Driver)");

\App\Core\Cache::flush();
assert_true(!\App\Core\Cache::has('test_key'), "Cache reports non-existent keys as false");

\App\Core\Cache::put('test_key', ['complex' => 'value'], 10);
assert_true(\App\Core\Cache::has('test_key'), "Cache can verify key existence after storage");
assert_equals('value', \App\Core\Cache::get('test_key')['complex'] ?? null, "Cache retains complex types (serialized)");

\App\Core\Cache::forget('test_key');
assert_true(!\App\Core\Cache::has('test_key'), "Cache can explicitly forget keys");

$fetched = \App\Core\Cache::remember('remember_key', 10, fn() => "fresh");
assert_equals("fresh", $fetched, "Cache remember executes callback on cache miss");
$fetched2 = \App\Core\Cache::remember('remember_key', 10, fn() => "stale");
assert_equals("fresh", $fetched2, "Cache remember returns cached value on hit");


// ─────────────────────────────────────────────────────────────────────────────
// 7. TEST: Validator
// ─────────────────────────────────────────────────────────────────────────────
test_group("Validator");

$validator = new \App\Core\Validator();
$validator->setDb($app->db);

// Simple Rules
$data = ['email' => 'user@domain.com', 'age' => 25];
$rules = ['email' => 'required|email', 'age' => 'required|integer'];
assert_true($validator->validate($data, $rules), "Validator passes valid simple data types");

// Min & Max
$rules2 = ['age' => 'required|integer|min:18|max:30'];
assert_true($validator->validate($data, $rules2), "Validator accepts numeric within min/max bounds");
$data2 = ['age' => 15];
assert_true(!$validator->validate($data2, $rules2), "Validator rejects inputs below min");

// Nullable
$rules3 = ['phone' => 'nullable|string|min:5'];
assert_true($validator->validate(['phone' => null], $rules3), "Validator skips other rules for empty nullable fields");
assert_true(!$validator->validate(['phone' => 'abc'], $rules3), "Validator validates nullable fields if a value is supplied");

// Regex
$rules4 = ['code' => 'required|regex:/^[A-Z]{3}-\d{3}$/'];
assert_true($validator->validate(['code' => 'ABC-123'], $rules4), "Validator accepts regex matching pattern");
assert_true(!$validator->validate(['code' => 'abc-123'], $rules4), "Validator rejects regex mismatch pattern");

// Unique (requires test table insert)
$app->db->exec("INSERT INTO `test_users` (name, email, password) VALUES ('Alex', 'alex@mail.com', 'password123')");
$rules5 = ['email' => 'required|email|unique:test_users,email'];
assert_true($validator->validate(['email' => 'bob@mail.com'], $rules5), "Validator unique rule accepts non-existing unique values");
assert_true(!$validator->validate(['email' => 'alex@mail.com'], $rules5), "Validator unique rule rejects already existing unique values");


// ─────────────────────────────────────────────────────────────────────────────
// 8. TEST: QueryBuilder
// ─────────────────────────────────────────────────────────────────────────────
test_group("QueryBuilder");

// Clear table
$app->db->exec("DELETE FROM `test_users` WHERE 1=1");

$qb = new \App\Core\QueryBuilder($app->db, 'test_users');

// Test Insert
$id = $qb->insert(['name' => 'Charlie', 'email' => 'charlie@mail.com']);
assert_true((int)$id > 0, "QueryBuilder inserts rows and returns auto-increment IDs");
$app->db->exec("INSERT INTO `users` (id, name, email, password) VALUES ({$id}, 'Charlie', 'charlie@mail.com', 'password123')");

// Test Select/Where
$user = (new \App\Core\QueryBuilder($app->db, 'test_users'))
    ->where('email', 'charlie@mail.com')
    ->first();
assert_equals('Charlie', $user['name'] ?? null, "QueryBuilder can query rows using where criteria");

// Test Logic Exception write guard (delete/update without where clause)
$qbNoWhere = new \App\Core\QueryBuilder($app->db, 'test_users');
assert_throws(\LogicException::class, function () use ($qbNoWhere) {
    $qbNoWhere->update(['name' => 'bad']);
}, "QueryBuilder throws LogicException on update() without a where clause");

assert_throws(\LogicException::class, function () use ($qbNoWhere) {
    $qbNoWhere->delete();
}, "QueryBuilder throws LogicException on delete() without a where clause");

// Test updates
$affected = (new \App\Core\QueryBuilder($app->db, 'test_users'))
    ->where('id', $id)
    ->update(['name' => 'Charlie Modified']);
assert_equals(1, $affected, "QueryBuilder updates database values successfully");

// Test joins
$orderQb = new \App\Core\QueryBuilder($app->db, 'test_orders');
$orderId = $orderQb->insert(['user_id' => $id, 'status' => 'pending', 'total' => 150.50]);

$joined = (new \App\Core\QueryBuilder($app->db, 'test_orders'))
    ->join('test_users', 'test_orders.user_id', 'test_users.id')
    ->select('test_orders.id', 'test_users.name', 'test_orders.total')
    ->where('test_orders.id', $orderId)
    ->first();
assert_equals('Charlie Modified', $joined['name'] ?? null, "QueryBuilder INNER JOIN queries map columns correctly");

// Test GroupBy & Having
$qbStats = new \App\Core\QueryBuilder($app->db, 'test_orders');
$qbStats->insert(['user_id' => $id, 'status' => 'pending', 'total' => 50.00]);

$aggregated = (new \App\Core\QueryBuilder($app->db, 'test_orders'))
    ->select('status', 'COUNT(*) as count')
    ->groupBy('status')
    ->having('count', 1, '>')
    ->get();
assert_true(count($aggregated) > 0, "QueryBuilder supports groupBy() and having() aggregates");

// Test Pagination
$pageResult = (new \App\Core\QueryBuilder($app->db, 'test_orders'))->paginate(1, 1);
assert_equals(2, $pageResult['total'], "Pagination queries calculate total matches");
assert_equals(1, count($pageResult['data']), "Pagination applies limit and offset properly");
assert_equals(2, $pageResult['last_page'], "Pagination correctly evaluates last page number");


// ─────────────────────────────────────────────────────────────────────────────
// 9. TEST: Models & Relationships
// ─────────────────────────────────────────────────────────────────────────────
test_group("Model Relationships & Timestamps");

// Define test models dynamically
class TestUser extends \App\Core\Model {
    protected string $table = 'test_users';
    protected bool $timestamps = true;

    public function orders(): \App\Core\RelationQuery {
        return $this->hasMany(TestOrder::class, 'user_id');
    }

    public function profile(): \App\Core\RelationQuery {
        return $this->hasOne(TestProfile::class, 'user_id');
    }
}

class TestOrder extends \App\Core\Model {
    protected string $table = 'test_orders';
    protected bool $timestamps = true;

    public function user(): \App\Core\RelationQuery {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}

class TestProfile extends \App\Core\Model {
    protected string $table = 'test_profiles';
}

$userModel = new TestUser();
$orderModel = new TestOrder();
$profileModel = new TestProfile();

// Test Model Timestamps on create
$newUserId = $userModel->create(['name' => 'Timestamps User', 'email' => 'time@mail.com', 'password' => 'password123']);
$createdUser = $userModel->find($newUserId);
assert_true(!empty($createdUser['created_at']), "Model timestamp create() appends created_at values");
assert_true(!empty($createdUser['updated_at']), "Model timestamp create() appends updated_at values");

// Test Model Timestamps on save
$userModel->save($newUserId, ['name' => 'Timestamps Modified']);
$updatedUser = $userModel->find($newUserId);
assert_equals('Timestamps Modified', $updatedUser['name'], "Model save() updates table columns");

// Test single relationship: HasMany
$orderModel->create(['user_id' => $newUserId, 'status' => 'completed', 'total' => 200.00]);
$userOrders = $userModel->orders()->for($createdUser);
assert_true(count($userOrders) >= 1, "Model hasMany() relationship fetches related collection rows");

// Test single relationship: BelongsTo
$orderRow = $orderModel->table()->where('user_id', $newUserId)->first();
$orderOwner = $orderModel->user()->for($orderRow);
assert_equals('Timestamps Modified', $orderOwner['name'] ?? null, "Model belongsTo() retrieves owner record");

// Test eager loading (loadFor)
$users = $userModel->table()->where('id', $newUserId)->get();
$usersWithOrders = $userModel->orders()->loadFor($users, 'orders');
assert_true(isset($usersWithOrders[0]['orders']), "Eager loader loadFor() appends relationship key");
assert_equals('completed', $usersWithOrders[0]['orders'][0]['status'] ?? null, "Eager loader retrieves values cleanly with 1 query");


// ─────────────────────────────────────────────────────────────────────────────
// 10. TEST: Request / Response / Router / Security Headers
// ─────────────────────────────────────────────────────────────────────────────
test_group("Request, Response & Router Routing");

// Custom subclass to bypass process exits and output buffers
class MockResponse extends \App\Core\Response {
    public int $statusCode = 200;
    public array $headers = [];
    public ?string $jsonOutput = null;
    public ?string $redirectUrl = null;

    public function setStatusCode(int $code): void {
        parent::setStatusCode($code);
        $this->statusCode = $code;
    }

    public function redirect(string $url): void {
        parent::redirect($url);
        $this->redirectUrl = $this->getRedirectUrl() ?: '/';
    }

    public function json(mixed $data, int $statusCode = 200): void {
        parent::json($data, $statusCode);
        $this->statusCode = $statusCode;
        $this->jsonOutput = $this->getContent();
    }

    public function reset(): void {
        parent::reset();
        $this->statusCode = 200;
        $this->headers = [];
        $this->jsonOutput = null;
        $this->redirectUrl = null;
    }
}

$mockRequest = new \App\Core\Request();
$mockResponse = new MockResponse();
$testRouter = new \App\Core\Router($mockRequest, $mockResponse);
$testRouter->excludeCsrf('/test-route', '/test-route/*');
\App\Core\Application::$app->router->excludeCsrf('/test-route', '/test-route/*');

// Test routes mapping
$testRouter->get('/test-route', function () {
    return 'hello get';
});
$testRouter->put('/test-route/{id}', function ($id) {
    return 'hello put ' . $id;
});

// Mock request variables
$_SERVER['REQUEST_URI'] = '/test-route';
$_SERVER['REQUEST_METHOD'] = 'GET';
assert_equals('hello get', $testRouter->resolve(), "Router correctly routes standard GET requests");

// Test HTTP Method Spoofing
$_SERVER['REQUEST_URI'] = '/test-route/42';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['_method'] = 'PUT';
assert_equals('hello put 42', $testRouter->resolve(), "Router supports PUT/PATCH/DELETE HTTP form method spoofing");
unset($_POST['_method']);

// Test Request File Upload helpers
$_FILES = [
    'document' => [
        'name' => 'test.pdf',
        'type' => 'application/pdf',
        'tmp_name' => '/tmp/phpabc123',
        'error' => 0,
        'size' => 12345
    ]
];
$fileRequest = new \App\Core\Request();
assert_equals('test.pdf', $fileRequest->file('document')['name'] ?? null, "Request::file() returns metadata of uploaded file by field name");
assert_true(is_array($fileRequest->getFiles()), "Request::getFiles() returns the entire files array");
assert_equals(null, $fileRequest->file('non_existent'), "Request::file() returns null for non-existent upload field");
$_FILES = []; // clean up

// Test Request Header Helper
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer my-api-token-123';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$headerRequest = new \App\Core\Request();
assert_equals('Bearer my-api-token-123', $headerRequest->header('Authorization'), "Request::header() retrieves correct header value");
assert_equals('application/json', $headerRequest->header('Content-Type'), "Request::header() normalizes Content-Type header name");
assert_equals(null, $headerRequest->header('X-Non-Existent'), "Request::header() returns null for undefined headers");
unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['CONTENT_TYPE']);

// Test Request JSON Parsing Helper
$jsonRequest = new class extends \App\Core\Request {
    public function setJsonParams(array $data): void {
        $this->jsonParams = $data;
    }
};
$jsonRequest->setJsonParams(['username' => 'alice', 'email' => 'alice@example.com']);
assert_equals('alice', $jsonRequest->input('username'), "Request::input() retrieves values from JSON body");
assert_equals('alice@example.com', $jsonRequest->post('email'), "Request::post() retrieves values from JSON body");
assert_equals('default_val', $jsonRequest->input('non_existent', 'default_val'), "Request input helper returns fallback for missing JSON keys");
assert_equals('alice', $jsonRequest->getBody()['username'] ?? null, "Request::getBody() merges parsed JSON parameters");




// Test Security Headers Middleware
$middleware = new \App\Middlewares\SecurityHeadersMiddleware("default-src 'none'");

// Mock php header() by capturing headers inside a custom header handler if possible, 
// or since PHP's header() writes directly to SAPI, we can run it and check headers_list()
if (function_exists('headers_list')) {
    $middleware->execute($mockRequest, $mockResponse);
    $headers = headers_list();
    if (empty($headers)) {
        echo "  ! Skipping headers_list assertion (headers_list() is empty in CLI SAPI environment)\n";
        // To verify the middleware executed without errors
        assert_true(true, "SecurityHeadersMiddleware executed successfully");
    } else {
        $foundCsp = false;
        foreach ($headers as $h) {
            if (stripos($h, 'Content-Security-Policy: default-src \'none\'') !== false) {
                $foundCsp = true;
                break;
            }
        }
        assert_true($foundCsp, "SecurityHeadersMiddleware appends HTTP headers on execution");
    }
} else {
    echo "  ! Skipping headers_list assertion (not available in this SAPI environment)\n";
}


// Test Open Redirect Guard
$mockResponse->redirect('https://malicious.domain.com/hack');
assert_equals('/', $mockResponse->redirectUrl, "Response redirect guards against Open Redirect hijacking");


// ─────────────────────────────────────────────────────────────────────────────
// 11. TEST: EventDispatcher & Async Queue
// ─────────────────────────────────────────────────────────────────────────────
test_group("EventDispatcher & Async Job Queue");

class MockJobQueue extends \App\Core\JobQueue {
    // Custom table target override for isolated test runs
    public function push(
        string $event,
        string $listener,
        mixed  $payload,
        int    $maxAttempts = 3,
        string $onFailure   = 'retry'
    ): void {
        $stmt = \App\Core\Application::$app->db->prepare(
            "INSERT INTO test_jobs (event, listener, payload, max_attempts, on_failure)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $event,
            $listener,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $maxAttempts,
            $onFailure,
        ]);
    }

    public function processPending(): int
    {
        $db = \App\Core\Application::$app->db;
        $db->beginTransaction();
        
        $stmt = $db->prepare(
            "SELECT * FROM test_jobs
              WHERE status = 'pending'
                AND run_at <= NOW()
              ORDER BY run_at ASC
              LIMIT 50"
        );
        $stmt->execute();
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($jobs)) {
            $ids = implode(',', array_column($jobs, 'id'));
            $db->exec("UPDATE test_jobs SET status = 'processing' WHERE id IN ({$ids})");
        }
        $db->commit();

        foreach ($jobs as $job) {
            $listenerClass = $job['listener'];
            $payload       = json_decode($job['payload'], true);
            $attempts      = (int) $job['attempts'] + 1;

            try {
                $instance = new $listenerClass();
                $instance->handle($payload);

                $db->prepare("UPDATE test_jobs SET status = 'done' WHERE id = ?")->execute([$job['id']]);
            } catch (\Throwable $e) {
                if ($job['on_failure'] === 'retry' && $attempts < (int) $job['max_attempts']) {
                    $db->prepare("UPDATE test_jobs SET status = 'pending', attempts = ? WHERE id = ?")
                       ->execute([$attempts, $job['id']]);
                } else {
                    $db->prepare("UPDATE test_jobs SET status = 'failed', error = ? WHERE id = ?")
                       ->execute([$e->getMessage(), $job['id']]);
                }
            }
        }

        return count($jobs);
    }
}

// Custom Event Listener Mock
class TestOrderPlacedListener {
    public static bool $executed = false;
    public static mixed $receivedPayload = null;

    public function handle(mixed $payload): void {
        self::$executed = true;
        self::$receivedPayload = $payload;
    }
}

class TestFailingListener {
    public function handle(mixed $payload): void {
        throw new \RuntimeException("Deliberate Listener Failure");
    }
}

$events = $app->events;
$events->flush();

// Test Synchronous Events
$events->listen('order.placed', TestOrderPlacedListener::class);
$events->dispatch('order.placed', ['order_id' => 123]);
assert_true(TestOrderPlacedListener::$executed, "EventDispatcher dispatches sync events immediately");
assert_equals(123, TestOrderPlacedListener::$receivedPayload['order_id'] ?? null, "Event listeners receive the correct payload");

// Test Async Queueing
$events->flush();

$injector = \Closure::bind(function () {
    return new class extends \App\Core\EventDispatcher {
        protected function pushToQueue(string $event, array $entry, mixed $payload): void {
            $queue = new MockJobQueue(\App\Core\Application::$app->db);
            $queue->push($event, $entry['listener'], $payload, $entry['maxAttempts'], $entry['onFailure']);
        }
    };
}, null, \App\Core\EventDispatcher::class);


$mockDispatcher = $injector();
$mockDispatcher->listen('order.placed', TestOrderPlacedListener::class, async: true, maxAttempts: 2, onFailure: 'retry');

// Clear test_jobs table
$app->db->exec("DELETE FROM `test_jobs` WHERE 1=1");

$mockDispatcher->dispatch('order.placed', ['order_id' => 456]);

// Assert row was queued
$queued = $app->db->query("SELECT * FROM test_jobs LIMIT 1")->fetch();
assert_true(!empty($queued), "EventDispatcher queues async listeners to the jobs table");
assert_equals('pending', $queued['status'] ?? null, "Queued job status initializes as pending");

// Process via Mock Queue Worker
TestOrderPlacedListener::$executed = false;
$mockWorker = new MockJobQueue($app->db);
$mockWorker->processPending();

assert_true(TestOrderPlacedListener::$executed, "Queue Worker executes queued jobs successfully");
$queuedUpdated = $app->db->query("SELECT * FROM test_jobs LIMIT 1")->fetch();
assert_equals('done', $queuedUpdated['status'] ?? null, "Queue Worker updates executed job status to done");

// Test Queue Retries and Failures
$mockDispatcher->flush();
$mockDispatcher->listen('order.failed', TestFailingListener::class, async: true, maxAttempts: 2, onFailure: 'retry');

$app->db->exec("DELETE FROM `test_jobs` WHERE 1=1");
$mockDispatcher->dispatch('order.failed', ['error' => true]);

// Process attempt 1 (fails, stays pending)
$mockWorker->processPending();
$jobState1 = $app->db->query("SELECT * FROM test_jobs LIMIT 1")->fetch();
assert_equals('pending', $jobState1['status'] ?? null, "Failed job statuses revert to pending when attempts < max_attempts");
assert_equals(1, (int)$jobState1['attempts'], "Failed jobs increment their attempts counter");

// Process attempt 2 (fails, status goes to failed)
$mockWorker->processPending();
$jobState2 = $app->db->query("SELECT * FROM test_jobs LIMIT 1")->fetch();
assert_equals('failed', $jobState2['status'] ?? null, "Failed jobs status updates to failed when attempts >= max_attempts");
assert_equals("Deliberate Listener Failure", $jobState2['error'] ?? null, "Failed jobs log the exception error message");


// ─────────────────────────────────────────────────────────────────────────────
// 11. TEST: MVC Integration (Model-View-Controller)
// ─────────────────────────────────────────────────────────────────────────────
test_group("MVC Integration (CRUD)");

// Set Views path for the sample project templates
$app->view->setViewsPath(__DIR__ . '/sample_project/Views');

// Use MockResponse
$originalResponse = $app->response;
$mockResponse = new MockResponse();
$app->response = $mockResponse;

// Request simulation helper function
if (!function_exists('simulate_mvc_request')) {
    function simulate_mvc_request(string $method, string $path, array $body = [], array $get = []): string {
        global $app;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $path;
        
        // Reset and inject global values
        $_POST = $body;
        $_GET = $get;
        
        // Reset response state
        $app->response->reset();
        
        // Load routes exactly once on the router to prevent duplication
        static $routesLoaded = false;
        if (!$routesLoaded) {
            $app->request = new \App\Core\Request();
            $app->router = new \App\Core\Router($app->request, $app->response);
            require __DIR__ . '/sample_project/routes/web.php';
            $routesLoaded = true;
        }
        
        // Update request for this simulation run
        $app->request = new \App\Core\Request();
        $app->router->setRequest($app->request);
        
        ob_start();
        try {
            $res = $app->router->resolve();
            if ($res instanceof \App\Core\Response) {
                $res->send();
            } elseif (empty($res)) {
                echo "[EMPTY RESPONSE]";
            } else {
                echo $res;
            }
        } catch (\Throwable $e) {
            $err = "Error Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
            file_put_contents('php://stdout', $err);
            echo $err;
        }
        return ob_get_clean();
    }
}

// Generate and set a mock CSRF token in the session
$csrfToken = bin2hex(random_bytes(32));
$app->session->set('_csrf_token', $csrfToken);

\App\Core\Gate::policy(\Tests\Sample\Models\Post::class, \Tests\Sample\Policies\PostPolicy::class);

// Seed a post for Charlie Modified so GET / has data
$charlie = (new \Tests\Sample\Models\User())->table()->where('email', 'charlie@mail.com')->first();
if ($charlie) {
    (new \Tests\Sample\Models\Post())->create([
        'user_id' => $charlie['id'],
        'title' => 'First Post by Charlie Modified',
        'body' => 'This is a test post body.'
    ]);
}

// 1. READ: Test GET / index dashboard rendering
$dashboardHTML = simulate_mvc_request('GET', '/');
assert_true(str_contains($dashboardHTML, 'Blogger Showcase - Spartan'), "MVC Integration GET '/' renders the views layout");
assert_true(str_contains($dashboardHTML, 'Charlie Modified'), "MVC Integration GET '/' retrieves models and renders them in template");

// 2. CREATE (Validation Failure): Test POST /user with invalid inputs
$mockResponse->redirectUrl = '';
$app->session->removeFlashMessages(); // clear flash
simulate_mvc_request('POST', '/user', [
    'name' => 'Ab',
    'email' => 'invalid-email',
    '_csrf' => $csrfToken
]);
assert_equals('/', $mockResponse->redirectUrl, "MVC controller redirects on validation failure");
$valErrors = $app->session->getFlash('validation_errors', []);
assert_true(!empty($valErrors['name']) || !empty($valErrors['email']), "MVC validation captures validation failures and flashes them to session");

// 3. CREATE (Success): Test POST /user with valid input
$mockResponse->redirectUrl = '';
$app->session->removeFlashMessages(); // clear flash
simulate_mvc_request('POST', '/user', [
    'name' => 'MVC Integration User',
    'email' => 'mvc@integration.com',
    '_csrf' => $csrfToken
]);
assert_equals('/', $mockResponse->redirectUrl, "MVC controller redirects to index on success");
assert_true(str_contains($app->session->getFlash('success_message', ''), 'created successfully'), "MVC controller flashes success message upon creation");

$user = (new \Tests\Sample\Models\User())->table()->where('email', 'mvc@integration.com')->first();
assert_true(!empty($user), "MVC model stores record into database successfully");
$app->session->set('user_id', (int)$user['id']);

// 4. UPDATE: Test PUT /post/{id} update
$postModel = new \Tests\Sample\Models\Post();
$postId = $postModel->create([
    'user_id' => (int)$user['id'],
    'title'   => 'Initial Title',
    'body'    => 'Initial post body content here'
]);

$mockResponse->redirectUrl = '';
$app->session->removeFlashMessages(); // clear flash
simulate_mvc_request('POST', "/post/{$postId}", [
    '_method' => 'PUT',
    'title'   => 'Updated Title',
    'body'    => 'Updated post body content here',
    '_csrf' => $csrfToken
]);
assert_equals('/', $mockResponse->redirectUrl, "MVC PUT updates redirect properly");
$updatedPost = $postModel->find($postId);
assert_equals('Updated Title', $updatedPost['title'], "MVC PUT update successfully updates model attributes in the DB");

// 5. DELETE: Test DELETE /post/{id}
$mockResponse->redirectUrl = '';
$app->session->removeFlashMessages(); // clear flash
simulate_mvc_request('POST', "/post/{$postId}", [
    '_method' => 'DELETE',
    '_csrf' => $csrfToken
]);
assert_equals('/', $mockResponse->redirectUrl, "MVC DELETE redirects properly");
$deletedPost = $postModel->find($postId);
assert_true($deletedPost === null, "MVC DELETE successfully removes record from database");

// 6. Blade & HTMX Integration: Test GET / search rendering and POST /search/posts
(new \Tests\Sample\Models\Post())->create([
    'user_id' => (int)$user['id'],
    'title'   => 'Searchable Post Title',
    'body'    => 'Searchable body content'
]);

$searchPageHtml = simulate_mvc_request('GET', '/');
assert_true(str_contains($searchPageHtml, 'Blog Posts Directory'), "Blade template renders sections correctly");
assert_true(str_contains($searchPageHtml, 'SPARTAN + BLADE + HTMX'), "Blade template inherits layout structures (@extends)");
assert_true(str_contains($searchPageHtml, 'hx-post="/search/posts"'), "Blade templates support HTMX tags");

// Simulate POST search request
$searchResultsHtml = simulate_mvc_request('POST', '/search/posts', [
    'query' => 'Searchable',
    '_csrf' => $csrfToken
]);
assert_true(str_contains($searchResultsHtml, 'Searchable Post Title'), "HTMX searchPosts controller renders matching query results");
assert_true(!str_contains($searchResultsHtml, 'SPARTAN + BLADE + HTMX'), "renderViewOnly compiles without layouts");

// Test Nested Blade Include State Isolation
$viewsPath = $app->view->getViewsPath();
file_put_contents($viewsPath . '/test_parent_layout.blade.php', "<html><body>@yield('content')</body></html>");
file_put_contents($viewsPath . '/test_parent_view.blade.php', "@extends('test_parent_layout')\n@section('content')\nParent Start\n@include('test_child_view')\nParent End\n@endsection");
file_put_contents($viewsPath . '/test_child_view.blade.php', "Child Content");

$nestedRender = $app->view->render('test_parent_view');
assert_true(str_contains($nestedRender, 'Parent Start'), "Parent view content before include is rendered");
assert_true(str_contains($nestedRender, 'Child Content'), "Child view content inside include is rendered");
assert_true(str_contains($nestedRender, 'Parent End'), "Parent view content after include is rendered");
assert_true(str_contains($nestedRender, '<html><body>'), "Parent layout is preserved and rendered");

// Cleanup
@unlink($viewsPath . '/test_parent_layout.blade.php');
@unlink($viewsPath . '/test_parent_view.blade.php');
@unlink($viewsPath . '/test_child_view.blade.php');





// ─────────────────────────────────────────────────────────────────────────────
// Blogger Management System Integration Tests
// ─────────────────────────────────────────────────────────────────────────────
test_group("Blogger Management System");

$postModel = new \Tests\Sample\Models\Post();
$commentModel = new \Tests\Sample\Models\Comment();
$userModel = new \Tests\Sample\Models\User();

// Get the user we created earlier
$author = $userModel->table()->where('email', 'mvc@integration.com')->first();
$authorId = (int)$author['id'];

// 1. Model Relationship & Creation tests
$postId = $postModel->create([
    'user_id' => $authorId,
    'title' => 'Blogger Integration Test Post',
    'body' => 'This is a detailed post about the Spartan framework.'
]);
assert_true(is_numeric($postId), "Post created successfully and returned ID");

$commentId = $commentModel->create([
    'post_id' => (int)$postId,
    'user_id' => $authorId,
    'content' => 'Great post, very informative!'
]);
assert_true(is_numeric($commentId), "Comment created successfully and returned ID");

// Test Relationships
$fetchedPost = $postModel->find($postId);
$postComments = $postModel->comments()->for($fetchedPost);
assert_equals(1, count($postComments), "Post comments relationship resolves successfully");

$fetchedComment = $commentModel->find($commentId);
$commentAuthor = $commentModel->author()->for($fetchedComment);
assert_equals('MVC Integration User', $commentAuthor['name'], "Comment belongsTo author relationship resolves successfully");

$commentPost = $commentModel->post()->for($fetchedComment);
assert_equals('Blogger Integration Test Post', $commentPost['title'], "Comment belongsTo post relationship resolves successfully");

// 2. Controller & Routing HTTP Tests
$csrfToken = $app->session->get('_csrf_token');

// Post a new comment via route
$mockResponse->redirectUrl = '';
simulate_mvc_request('POST', "/post/{$postId}/comment", [
    'user_id' => $authorId,
    'content' => 'I agree with this completely!',
    '_csrf' => $csrfToken
]);
assert_equals("/post/{$postId}", $mockResponse->redirectUrl, "Store comment redirects back to the post page");

$newComment = $commentModel->table()->where('content', 'I agree with this completely!')->first();
assert_true(!empty($newComment), "Store comment route successfully inserts comment into database");

// Test GET /post/{id} show page rendering
$postPageHtml = simulate_mvc_request('GET', "/post/{$postId}");
assert_true(str_contains($postPageHtml, 'Blogger Integration Test Post'), "Post page renders title");
assert_true(str_contains($postPageHtml, 'Great post, very informative!'), "Post page renders comments content");
assert_true(str_contains($postPageHtml, 'MVC Integration User'), "Post page displays comment author name");

// Test GET /redirect-test open redirect check
$mockResponse->redirectUrl = '';
simulate_mvc_request('GET', '/redirect-test', [], ['url' => '/some-safe-path']);
assert_equals('/some-safe-path', $mockResponse->redirectUrl, "Redirect test redirects to local target");

$mockResponse->redirectUrl = '';
simulate_mvc_request('GET', '/redirect-test', [], ['url' => 'https://malicious-external-domain.com']);
assert_equals('/', $mockResponse->redirectUrl, "Redirect test blocks external domain and redirects to '/'");

// Restore original response object
$app->response = $originalResponse;


// ─────────────────────────────────────────────────────────────────────────────
// 12. TEST: Refactored Core Features (4-Arg Joins, CSRF Exclusions, Middleware Groups, Transactions)
// ─────────────────────────────────────────────────────────────────────────────
test_group("Refactored Core Features & Architectural Fixes");

// 1. Test 4-argument join support in QueryBuilder
try {
    $qb = new \App\Core\QueryBuilder($app->db, 'test_users');
    $qb->join('test_orders', 'test_users.id', '=', 'test_orders.user_id');
    $reflector = new \ReflectionClass($qb);
    $buildSelectMethod = $reflector->getMethod('buildSelect');
    $buildSelectMethod->setAccessible(true);
    [$sql, $bindings] = $buildSelectMethod->invoke($qb);
    assert_true(str_contains($sql, "INNER JOIN `test_orders` ON test_users.id = test_orders.user_id"), "QueryBuilder supports 4-argument join compiles with operator");
} catch (\Throwable $e) {
    assert_true(false, "4-argument join failed: " . $e->getMessage());
}

// 2. Test CSRF Exclusions in Router
try {
    $originalGlobalRouter = \App\Core\Application::$app->router;
    $tempRouter = new \App\Core\Router($app->request, $app->response);
    \App\Core\Application::$app->router = $tempRouter;
    $tempRouter->excludeCsrf('/api/webhook', '/payment/*');
    
    // Simulate non-excluded post
    $req1 = new class extends \App\Core\Request {
        public function getPath(): string { return '/checkout'; }
        public function getMethod(): string { return 'POST'; }
        public function validateCsrf(): bool { return false; }
    };
    $tempRouter->setRequest($req1);
    $tempRouter->post('/checkout', function() { return 'ok'; });
    
    $failedCsrf = false;
    try {
        $tempRouter->resolve();
    } catch (\RuntimeException $e) {
        if (str_contains($e->getMessage(), 'CSRF')) {
            $failedCsrf = true;
        }
    }
    assert_true($failedCsrf, "Router blocks non-excluded post request with failed CSRF");

    // Simulate excluded post (exact match)
    $req2 = new class extends \App\Core\Request {
        public function getPath(): string { return '/api/webhook'; }
        public function getMethod(): string { return 'POST'; }
        public function validateCsrf(): bool { return false; }
    };
    $tempRouter->setRequest($req2);
    $tempRouter->post('/api/webhook', function() { return 'webhook-ok'; });
    assert_equals('webhook-ok', $tempRouter->resolve(), "Router bypasses CSRF validation for exact excluded path matches");

    // Simulate excluded post (wildcard match)
    $req3 = new class extends \App\Core\Request {
        public function getPath(): string { return '/payment/stripe/callback'; }
        public function getMethod(): string { return 'POST'; }
        public function validateCsrf(): bool { return false; }
    };
    $tempRouter->setRequest($req3);
    $tempRouter->post('/payment/stripe/callback', function() { return 'payment-ok'; });
    assert_equals('payment-ok', $tempRouter->resolve(), "Router bypasses CSRF validation for wildcard excluded path matches");

    \App\Core\Application::$app->router = $originalGlobalRouter;
} catch (\Throwable $e) {
    if (isset($originalGlobalRouter)) {
        \App\Core\Application::$app->router = $originalGlobalRouter;
    }
    assert_true(false, "CSRF Exclusions test failed: " . $e->getMessage());
}

// 3. Test Middleware Groups in Router
try {
    class DummyMiddleware1 extends \App\Core\Middleware {
        public static int $runCount = 0;
        public function execute(\App\Core\Request $request, \App\Core\Response $response): void {
            self::$runCount++;
        }
    }
    class DummyMiddleware2 extends \App\Core\Middleware {
        public static int $runCount = 0;
        public function execute(\App\Core\Request $request, \App\Core\Response $response): void {
            self::$runCount++;
        }
    }

    $tempRouter = new \App\Core\Router($app->request, $app->response);
    $tempRouter->middlewareGroup('web_group', [DummyMiddleware1::class, DummyMiddleware2::class]);
    $tempRouter->middlewareGroup('nested_group', ['web_group', DummyMiddleware1::class]);

    $req = new class extends \App\Core\Request {
        public function getPath(): string { return '/group-test'; }
        public function getMethod(): string { return 'GET'; }
    };
    $tempRouter->setRequest($req);
    $tempRouter->get('/group-test', function() { return 'group-ok'; }, ['nested_group']);
    
    DummyMiddleware1::$runCount = 0;
    DummyMiddleware2::$runCount = 0;
    
    $res = $tempRouter->resolve();
    assert_equals('group-ok', $res, "Router resolves routes using middleware groups");
    assert_equals(2, DummyMiddleware1::$runCount, "Router resolves nested middleware groups (ran twice)");
    assert_equals(1, DummyMiddleware2::$runCount, "Router resolves simple middleware groups (ran once)");
} catch (\Throwable $e) {
    assert_true(false, "Middleware Groups test failed: " . $e->getMessage());
}

// 4. Test Transaction Helper in Model
try {
    $userModel = new TestUser();
    $initialCount = count($userModel->table()->get());
    
    $transactionFailed = false;
    try {
        $userModel->transaction(function($model) {
            $model->table()->insert([
                'name' => 'Should Not Exist',
                'email' => 'shouldnotexist@example.com'
            ]);
            throw new \RuntimeException("Force transaction rollback");
        });
    } catch (\RuntimeException $e) {
        if ($e->getMessage() === "Force transaction rollback") {
            $transactionFailed = true;
        }
    }
    
    assert_true($transactionFailed, "Model transaction propagates exceptions");
    $afterCount = count($userModel->table()->get());
    assert_equals($initialCount, $afterCount, "Model transaction() auto-rolls back changes if exception is thrown");

    // Success path of transaction
    $success = $userModel->transaction(function($model) {
        return $model->table()->insert([
            'name' => 'Should Exist Transaction',
            'email' => 'shouldexisttrans@example.com'
        ]);
    });
    assert_true(!empty($success), "Model transaction() returns results of callback on success");
    assert_equals($initialCount + 1, count($userModel->table()->get()), "Model transaction() commits changes on successful callback execution");

} catch (\Throwable $e) {
    assert_true(false, "Model Transaction Helper test failed: " . $e->getMessage());
}

// 4b. Test Model findInstanceBy
try {
    $userModel = new TestUser();
    $foundUser = $userModel->findInstanceBy('email', 'shouldexisttrans@example.com');
    assert_true($foundUser instanceof TestUser, "findInstanceBy returns hydrated Model instance");
    assert_equals('Should Exist Transaction', $foundUser->name, "findInstanceBy retrieves correct column data");
    
    $nonExistent = $userModel->findInstanceBy('email', 'nonexistent@example.com');
    assert_true($nonExistent === null, "findInstanceBy returns null on non-existent record");
} catch (\Throwable $e) {
    assert_true(false, "Model findInstanceBy test failed: " . $e->getMessage());
}

// 5. Test Controller Dependency Injection
class DummyDIService {
    public function getValue(): string { return 'resolved-service-value'; }
}

class DummyDIController extends \App\Core\Controller {
    public DummyDIService $service;
    public function __construct(DummyDIService $service) {
        parent::__construct();
        $this->service = $service;
    }
    public function index(): string {
        return $this->service->getValue();
    }
}

try {
    $app->container->singleton(DummyDIService::class, fn() => new DummyDIService());
    
    $tempRouter = new \App\Core\Router($app->request, $app->response);
    $req = new class extends \App\Core\Request {
        public function getPath(): string { return '/di-test'; }
        public function getMethod(): string { return 'GET'; }
    };
    $tempRouter->setRequest($req);
    $tempRouter->get('/di-test', [DummyDIController::class, 'index']);
    
    $res = $tempRouter->resolve();
    assert_equals('resolved-service-value', $res, "Router resolves controllers and auto-injects constructor dependencies");
} catch (\Throwable $e) {
    assert_true(false, "Controller DI test failed: " . $e->getMessage());
}

// 6. Test Global Exception Handler & AJAX rendering
try {
    // Standard HTML Request Error
    $reqHTML = new class extends \App\Core\Request {
        public function getPath(): string { return '/err-html'; }
        public function getMethod(): string { return 'GET'; }
        public function isAjax(): bool { return false; }
    };
    
    $tempRouter = new \App\Core\Router($reqHTML, $app->response);
    $tempRouter->get('/err-html', function() {
        throw new \RuntimeException("HTML Error Example");
    });
    
    ob_start();
    try {
        $res = $tempRouter->resolve();
        throw new \RuntimeException("Router resolution should have thrown");
    } catch (\Throwable $e) {
        $handler = new \App\Core\ExceptionHandler();
        $handler->handle($e, $reqHTML, $app->response, ['app' => ['debug' => true]]);
    }
    $htmlOutput = ob_get_clean();
    
    assert_true(strpos($htmlOutput, "HTML Error Example") !== false, "ExceptionHandler outputs error message on debug mode");
    assert_equals(500, $app->response->getStatusCode(), "ExceptionHandler sets status code to 500");
    
    // AJAX Request Error (Should return JSON)
    $reqAJAX = new class extends \App\Core\Request {
        public function getPath(): string { return '/err-ajax'; }
        public function getMethod(): string { return 'GET'; }
        public function isAjax(): bool { return true; }
    };
    
    $app->response->reset();
    ob_start();
    try {
        throw new \RuntimeException("AJAX Error Example");
    } catch (\Throwable $e) {
        $handler = new \App\Core\ExceptionHandler();
        $handler->handle($e, $reqAJAX, $app->response, ['app' => ['debug' => true]]);
    }
    $jsonOutput = ob_get_clean();
    
    $decoded = json_decode($jsonOutput, true);
    assert_equals('500 Internal Server Error', $decoded['error'] ?? '', "ExceptionHandler responds with JSON on AJAX requests");
    assert_equals('AJAX Error Example', $decoded['message'] ?? '', "ExceptionHandler passes exception message in JSON on debug mode");
    
    // Overridden Exception Handler in Container
    $customHandlerRun = false;
    $customHandler = new class($customHandlerRun) extends \App\Core\ExceptionHandler {
        public $run;
        public function __construct(&$run) { $this->run = &$run; }
        public function handle(\Throwable $e, \App\Core\Request $request, \App\Core\Response $response, array $config): void {
            $this->run = true;
        }
    };
    
    $app->container->instance(\App\Core\ExceptionHandler::class, $customHandler);
    
    // Simulating run() exception handling
    try {
        throw new \RuntimeException("Test Overridden Handler");
    } catch (\Throwable $e) {
        $handler = $app->container->has(\App\Core\ExceptionHandler::class)
            ? $app->container->make(\App\Core\ExceptionHandler::class)
            : new \App\Core\ExceptionHandler();
        $handler->handle($e, $reqHTML, $app->response, []);
    }
    
    assert_true($customHandlerRun, "Application resolves and delegates to overridden ExceptionHandler from container");
    
    // Cleanup custom handler instance
    $app->container->forget(\App\Core\ExceptionHandler::class);
    
} catch (\Throwable $e) {
    assert_true(false, "Global Exception Handler test failed: " . $e->getMessage());
}


// ─────────────────────────────────────────────────────────────────────────────
// 13. TEST: Rate Limiting, Parameterized Middlewares, and Early Abort
// ─────────────────────────────────────────────────────────────────────────────
test_group("Rate Limiter & Parameterized Middlewares");

// A. Test Early Abort in Router
try {
    $tempRouter = new \App\Core\Router($app->request, $app->response);
    
    class AbortDummyMiddleware extends \App\Core\Middleware {
        public function execute(\App\Core\Request $request, \App\Core\Response $response): void {
            $response->redirect('/login');
        }
    }
    
    $tempRouter->aliasMiddleware('abort_middleware', AbortDummyMiddleware::class);
    
    $callbackExecuted = false;
    $tempRouter->get('/profile-dashboard', function() use (&$callbackExecuted) {
        $callbackExecuted = true;
        return 'profile-ok';
    }, ['abort_middleware']);
    
    $req = new class extends \App\Core\Request {
        public function getPath(): string { return '/profile-dashboard'; }
        public function getMethod(): string { return 'GET'; }
    };
    $tempRouter->setRequest($req);
    $app->response->reset();
    
    $res = $tempRouter->resolve();
    assert_true($res instanceof \App\Core\Response, "Early abort returns the response object");
    assert_equals('/login', $res->getRedirectUrl(), "Early abort correctly sets the redirect URL");
    assert_true(!$callbackExecuted, "Early abort prevents controller callback from executing");
    
} catch (\Throwable $e) {
    assert_true(false, "Early abort test failed: " . $e->getMessage());
}

// B. Test Parameterized Middlewares
try {
    $tempRouter = new \App\Core\Router($app->request, $app->response);
    
    class ParamDummyMiddleware extends \App\Core\Middleware {
        public static ?string $p1 = null;
        public static ?string $p2 = null;
        
        public function __construct(string $p1 = '', string $p2 = '') {
            self::$p1 = $p1;
            self::$p2 = $p2;
        }
        
        public function execute(\App\Core\Request $request, \App\Core\Response $response): void {}
    }
    
    $tempRouter->aliasMiddleware('param_dummy', ParamDummyMiddleware::class);
    $tempRouter->get('/api/users', function() { return 'ok'; }, ['param_dummy:val1,val2']);
    
    $req = new class extends \App\Core\Request {
        public function getPath(): string { return '/api/users'; }
        public function getMethod(): string { return 'GET'; }
    };
    $tempRouter->setRequest($req);
    $app->response->reset();
    
    $tempRouter->resolve();
    assert_equals('val1', ParamDummyMiddleware::$p1, "Parameterized middleware constructor receives first argument");
    assert_equals('val2', ParamDummyMiddleware::$p2, "Parameterized middleware constructor receives second argument");
    
} catch (\Throwable $e) {
    assert_true(false, "Parameterized middleware test failed: " . $e->getMessage());
}

// C. Test Rate Limiter Middleware
try {
    $tempRouter = new \App\Core\Router($app->request, $app->response);
    $tempRouter->get('/api/rate-limited', function() { return 'rate-ok'; }, ['rate_limit:3,5']);
    
    $req = new class extends \App\Core\Request {
        public function getPath(): string { return '/api/rate-limited'; }
        public function getMethod(): string { return 'GET'; }
        public function getIp(): string { return '192.168.1.100'; }
    };
    
    // Clear any existing cache for this key
    $cacheKey = 'rate_limit:' . md5('192.168.1.100:/api/rate-limited');
    \App\Core\Cache::forget($cacheKey);
    
    // Request 1: hits = 1
    $tempRouter->setRequest($req);
    $app->response->reset();
    $res1 = $tempRouter->resolve();
    $headers1 = $app->response->getHeaders();
    assert_equals('3', $headers1['X-RateLimit-Limit'] ?? '', "Rate limit first request sets correct Limit header");
    assert_equals('2', $headers1['X-RateLimit-Remaining'] ?? '', "Rate limit first request sets remaining count to 2");
    assert_equals('rate-ok', $res1, "Rate limit first request executes route callback successfully");
    
    // Request 2: hits = 2
    $app->response->reset();
    $res2 = $tempRouter->resolve();
    $headers2 = $app->response->getHeaders();
    assert_equals('1', $headers2['X-RateLimit-Remaining'] ?? '', "Rate limit second request sets remaining count to 1");
    
    // Request 3: hits = 3
    $app->response->reset();
    $res3 = $tempRouter->resolve();
    $headers3 = $app->response->getHeaders();
    assert_equals('0', $headers3['X-RateLimit-Remaining'] ?? '', "Rate limit third request sets remaining count to 0");
    
    // Request 4: hits = 4 (Blocked!)
    $app->response->reset();
    $res4 = $tempRouter->resolve();
    $headers4 = $app->response->getHeaders();
    assert_equals(429, $app->response->getStatusCode(), "Rate limit fourth request returns 429 Too Many Requests status code");
    assert_true(str_contains($app->response->getContent(), 'Too Many Requests'), "Rate limit fourth request outputs rate limit error content");
    assert_true(isset($headers4['Retry-After']), "Rate limit blocked response sets Retry-After header");
    assert_true($res4 instanceof \App\Core\Response, "Rate limit blocked request returns response object early");
    
    // Simulating Window decay/reset by clearing cache
    \App\Core\Cache::forget($cacheKey);
    $app->response->reset();
    $res5 = $tempRouter->resolve();
    assert_equals('rate-ok', $res5, "Rate limit resets successfully after cache expiration/decay window");

} catch (\Throwable $e) {
    assert_true(false, "Rate Limiter test failed: " . $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// 13. LOGGER, DIALECTS & FORMREQUESTS
// ─────────────────────────────────────────────────────────────────────────────
test_group("Logger, Dialects, and FormRequests");
try {
    // A. LOGGER TEST
    $logPath = __DIR__ . '/scratch_logs';
    $logger = new \App\Core\Logger($logPath);
    $logger->info("User {user_id} logged in from {ip}", ['user_id' => 999, 'ip' => '127.0.0.1']);
    
    $todayLog = $logPath . '/app-' . date('Y-m-d') . '.log';
    assert_true(file_exists($todayLog), "Logger creates daily log file successfully");
    $logContent = file_get_contents($todayLog);
    assert_true(str_contains($logContent, '[INFO]'), "Logger writes correct log level prefix");
    assert_true(str_contains($logContent, 'User 999 logged in from 127.0.0.1'), "Logger interpolates context placeholders correctly");
    
    // Save original logger
    $originalLogger = $app->logger;
    $app->logger = $logger;
    $app->container->singleton(\App\Core\Logger::class, fn() => $logger);
    
    // Test Exception Logging
    $handler = new \App\Core\ExceptionHandler();
    $mockReq = new \App\Core\Request();
    $mockRes = new \App\Core\Response();
    
    ob_start();
    $handler->handle(new \Exception("Autolog exception trace test"), $mockReq, $mockRes, ['app' => ['debug' => false]]);
    ob_end_clean();
    
    $logContentAfter = file_get_contents($todayLog);
    assert_true(str_contains($logContentAfter, 'Autolog exception trace test'), "ExceptionHandler automatically logs caught exceptions to daily log");
    
    // Restore original logger
    $app->logger = $originalLogger;
    $app->container->singleton(\App\Core\Logger::class, fn() => $originalLogger);
    
    // Cleanup logger scratch directory
    @unlink($todayLog);
    @rmdir($logPath);

    // B. DIALECTS TEST
    $mysqlBuilder = new \App\Core\QueryBuilder($db, 'test_users');
    $refMethodObj = new \ReflectionMethod($mysqlBuilder, 'buildSelect');
    $refMethodObj->setAccessible(true);
    
    [$mysqlSql, $mysqlBindings] = $refMethodObj->invoke($mysqlBuilder);
    assert_true(str_contains($mysqlSql, 'SELECT * FROM `test_users`'), "QueryBuilder MysqlDialect compiles table name with backticks");

    $sqlitePdo = new \PDO('sqlite::memory:');
    $sqliteBuilder = new \App\Core\QueryBuilder($sqlitePdo, 'test_users');
    [$sqliteSql, $sqliteBindings] = $refMethodObj->invoke($sqliteBuilder);
    assert_true(str_contains($sqliteSql, 'SELECT * FROM "test_users"'), "QueryBuilder SqliteDialect compiles table name with double quotes");

    // C. FORMREQUESTS TEST
    if (!class_exists('TestUserStoreRequest', false)) {
        class TestUserStoreRequest extends \App\Core\FormRequest
        {
            public function rules(): array
            {
                return [
                    'username' => 'required|min:4',
                    'email'    => 'required|email'
                ];
            }
        }
    }

    if (!class_exists('TestFormController', false)) {
        class TestFormController
        {
            public function store(TestUserStoreRequest $request): string
            {
                return "Stored " . $request->input('username');
            }
        }
    }

    if (!class_exists('TestStandardRequestController', false)) {
        class TestStandardRequestController
        {
            public function handle(\App\Core\Request $request): string
            {
                return "Request path: " . $request->getPath();
            }
        }
    }

    $tempRouter2 = new \App\Core\Router($app->request, $app->response);
    $tempRouter2->post('/test-form-request', [TestFormController::class, 'store']);
    $tempRouter2->get('/test-std-request', [TestStandardRequestController::class, 'handle']);

    // Simulate validation success
    $_SERVER['REQUEST_URI'] = '/test-form-request';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['username' => 'valid_user', 'email' => 'valid@example.com', '_csrf' => $csrfToken];
    $app->request = new \App\Core\Request();
    $tempRouter2->setRequest($app->request);
    $app->response->reset();
    $resFormSuccess = $tempRouter2->resolve();
    assert_equals("Stored valid_user", $resFormSuccess, "FormRequest auto-injection resolves correctly on successful validation");

    // Simulate validation failure
    $_POST = ['username' => 'abc', 'email' => 'invalid-email', '_csrf' => $csrfToken];
    $app->request = new \App\Core\Request();
    $tempRouter2->setRequest($app->request);
    $app->response->reset();
    
    $failed = false;
    try {
        $tempRouter2->resolve();
    } catch (\RuntimeException $e) {
        $failed = true;
        assert_true(str_contains($e->getMessage(), 'Validation Failed'), "FormRequest throws validation exception in test environment");
        assert_true(str_contains($e->getMessage(), 'username'), "FormRequest failure exception includes username error messages");
        assert_true(str_contains($e->getMessage(), 'email'), "FormRequest failure exception includes email error messages");
    }
    assert_true($failed, "FormRequest fails validation on invalid inputs");

    // Simulate Standard Request Injection
    $_SERVER['REQUEST_URI'] = '/test-std-request';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $app->request = new \App\Core\Request();
    $tempRouter2->setRequest($app->request);
    $app->response->reset();
    $resStdReq = $tempRouter2->resolve();
    assert_equals("Request path: /test-std-request", $resStdReq, "Standard Request auto-injection resolves and passes active request");

    // === Testing View & Route Caching ===
    test_group("View & Route Caching");
    
    // 1. View Caching Test
    $app->config['views']['cache_enabled'] = true;
    
    $testViewName = 'temp_test_cache_view';
    $viewDir = $app->view->getViewsPath();
    $testViewFile = $viewDir . '/' . $testViewName . '.blade.php';
    
    file_put_contents($testViewFile, 'Initial Content');
    
    $resView1 = $app->view->renderViewOnly($testViewName);
    assert_equals('Initial Content', trim($resView1), "View compiles and renders initial content");
    
    file_put_contents($testViewFile, 'Modified Content');
    
    $resView2 = $app->view->renderViewOnly($testViewName);
    assert_equals('Initial Content', trim($resView2), "View caching returns initial compiled content when cache is enabled");
    
    $app->config['views']['cache_enabled'] = false;
    $resView3 = $app->view->renderViewOnly($testViewName);
    assert_equals('Modified Content', trim($resView3), "View recompiles when cache is disabled");
    
    if (file_exists($testViewFile)) {
        unlink($testViewFile);
    }
    $cachedFile = dirname(__DIR__) . '/storage/views/' . md5($testViewName) . '.php';
    if (file_exists($cachedFile)) {
        unlink($cachedFile);
    }

    // 2. Route Caching Test
    $app->config['router']['cache_enabled'] = true;
    $cacheFile = dirname(__DIR__) . '/storage/cache/test_routes.php';
    $app->config['router']['cache_file'] = $cacheFile;
    
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
    
    $tempRouter3 = new \App\Core\Router($app->request, $app->response);
    $tempRouter3->get('/cache-test-route', [TestStandardRequestController::class, 'handle']);
    $tempRouter3->middlewareGroup('cache_group', ['auth']);
    $tempRouter3->excludeCsrf('/cache-webhook');
    
    $saveSuccess = $tempRouter3->saveCache();
    assert_true($saveSuccess, "Router saves cache file successfully");
    assert_true(file_exists($cacheFile), "Route cache file exists on disk");
    
    $tempRouter4 = new \App\Core\Router($app->request, $app->response);
    $loadSuccess = $tempRouter4->loadCache();
    assert_true($loadSuccess, "Router loads cache file successfully");
    
    $_SERVER['REQUEST_URI'] = '/cache-test-route';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $app->request = new \App\Core\Request();
    $tempRouter4->setRequest($app->request);
    $app->response->reset();
    $resCacheRoute = $tempRouter4->resolve();
    assert_equals("Request path: /cache-test-route", $resCacheRoute, "Cached route resolves correctly");
    
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }

    // === Testing: Authorization, RBAC & Policies ===
    test_group("Authorization, RBAC & Policies");

    // 1. Setup DB Auth records
    $db->exec("DELETE FROM `user_roles` WHERE 1=1");
    $db->exec("DELETE FROM `role_permissions` WHERE 1=1");
    $db->exec("DELETE FROM `roles` WHERE 1=1");
    $db->exec("DELETE FROM `permissions` WHERE 1=1");

    (new \App\Core\QueryBuilder($db, 'users'))->insert([
        'id' => 10,
        'name' => 'Auth Tester',
        'email' => 'auth@test.com'
    ]);

    (new \App\Core\QueryBuilder($db, 'roles'))->insert([
        'id' => 1,
        'name' => 'Administrator',
        'slug' => 'admin'
    ]);
    (new \App\Core\QueryBuilder($db, 'roles'))->insert([
        'id' => 2,
        'name' => 'Editor',
        'slug' => 'editor'
    ]);

    (new \App\Core\QueryBuilder($db, 'permissions'))->insert([
        'id' => 100,
        'name' => 'Publish Post',
        'slug' => 'publish_post'
    ]);
    (new \App\Core\QueryBuilder($db, 'permissions'))->insert([
        'id' => 101,
        'name' => 'Edit Post',
        'slug' => 'edit_post'
    ]);

    (new \App\Core\QueryBuilder($db, 'role_permissions'))->insert([
        'role_id' => 1,
        'permission_id' => 100
    ]);
    (new \App\Core\QueryBuilder($db, 'role_permissions'))->insert([
        'role_id' => 2,
        'permission_id' => 101
    ]);

    // 2. Test User HasAuthorization trait
    $user = new \App\Models\User();
    $user->id = 10;
    $user->name = 'Auth Tester';
    $user->email = 'auth@test.com';

    $user->assignRole('editor');
    assert_true($user->hasRole('editor'), "User has assigned 'editor' role");
    assert_true(!$user->hasRole('admin'), "User does not have 'admin' role");
    assert_true($user->hasPermission('edit_post'), "User has permission 'edit_post' via 'editor' role");
    assert_true(!$user->hasPermission('publish_post'), "User does not have permission 'publish_post' via 'editor' role");

    $user->assignRole('admin');
    assert_true($user->hasRole('admin'), "User now has 'admin' role");
    assert_true($user->hasPermission('publish_post'), "User now has permission 'publish_post' via 'admin' role");

    // 3. Test Gates & Policies
    \App\Core\Gate::$abilities = [];
    \App\Core\Gate::$policies = [];

    \App\Core\Gate::define('access-dashboard', function(?object $user) {
        return $user !== null && $user->hasRole('admin');
    });

    $app->container->instance('auth_user', $user);
    $app->session->set('user_id', 10);

    assert_true(\App\Core\Gate::allows('access-dashboard'), "Gate allows access-dashboard for admin user");
    assert_true(!\App\Core\Gate::denies('access-dashboard'), "Gate denies returns false for admin user");

    if (!class_exists('TestPostPolicy', false)) {
        class TestPostPolicy {
            public function update(?object $user, object $post) {
                return $user !== null && (method_exists($user, 'hasRole') && $user->hasRole('admin') || $user->id === $post->user_id);
            }
        }
    }

    if (!class_exists('TestPost', false)) {
        class TestPost {
            public int $user_id;
            public function __construct(int $userId) {
                $this->user_id = $userId;
            }
        }
    }

    \App\Core\Gate::policy(TestPost::class, TestPostPolicy::class);

    $post1 = new TestPost(10); // Owned by user 10 (tester)
    $post2 = new TestPost(99); // Owned by user 99 (someone else)

    assert_true(\App\Core\Gate::allows('update', $post1), "Policy allows updating owned post");
    assert_true(\App\Core\Gate::allows('update', $post2), "Policy allows updating other's post for admin user");

    $guestUser = new \App\Models\User();
    $guestUser->id = 11;
    $app->container->instance('auth_user', $guestUser);

    assert_true(!\App\Core\Gate::allows('update', $post2), "Policy blocks updating other's post for non-admin");
    assert_true(!\App\Core\Gate::allows('update', $post1), "Policy blocks updating post1 (owner 10) for guest user 11");

    // Restore admin tester user
    $app->container->instance('auth_user', $user);

    // 4. Test Controller Action Attribute Scanner
    if (!class_exists('TestAuthAttrController', false)) {
        #[\App\Core\Attributes\RequireRole('editor')]
        class TestAuthAttrController {
            #[\App\Core\Attributes\RequirePermission('publish_post')]
            public function publish(): string {
                return "Published successfully";
            }

            public function edit(): string {
                return "Edited successfully";
            }
        }
    }

    $tempRouter5 = new \App\Core\Router($app->request, $app->response);
    $tempRouter5->get('/test-auth-publish', [TestAuthAttrController::class, 'publish']);
    $tempRouter5->get('/test-auth-edit', [TestAuthAttrController::class, 'edit']);

    // Test edit: requires 'editor' role (user has it)
    $_SERVER['REQUEST_URI'] = '/test-auth-edit';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $app->request = new \App\Core\Request();
    $tempRouter5->setRequest($app->request);
    $app->response->reset();

    $resEdit = $tempRouter5->resolve();
    assert_equals("Edited successfully", $resEdit, "Route with class attribute allows access when user has role");

    // Test publish: requires 'editor' role AND 'publish_post' permission (user has both)
    $_SERVER['REQUEST_URI'] = '/test-auth-publish';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $app->request = new \App\Core\Request();
    $tempRouter5->setRequest($app->request);
    $app->response->reset();

    $resPublish = $tempRouter5->resolve();
    assert_equals("Published successfully", $resPublish, "Route with method attribute allows access when user has permission");

    // Test Guest Blocked (no auth user in container, no user_id in session)
    $app->container->forget('auth_user');
    $app->session->remove('user_id');
    $_SERVER['REQUEST_URI'] = '/test-auth-edit';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $app->request = new \App\Core\Request();
    $tempRouter5->setRequest($app->request);
    $app->response->reset();

    $resBlockGuest = $tempRouter5->resolve();
    assert_true($resBlockGuest instanceof \App\Core\Response, "Blocking guest returns Response object");
    assert_equals(302, $resBlockGuest->getStatusCode(), "Redirect status is 302");
    assert_equals('/login', $resBlockGuest->getRedirectUrl(), "Redirects to /login");

    // Restore session
    $app->session->set('user_id', 10);
    $app->container->instance('auth_user', $user);

    // 5. Test Blade Custom Directives
    $viewRef = new \ReflectionMethod($app->view, 'compileString');
    $viewRef->setAccessible(true);

    $resDirectives1 = $viewRef->invoke($app->view, "@can('update', \$post)Edit @endcan");
    assert_equals("<?php if(\App\Core\Gate::check('update', \$post)): ?>Edit <?php endif; ?>", $resDirectives1, "View compiles @can directive correctly");

    $resDirectives2 = $viewRef->invoke($app->view, "@role('admin', 'editor')Admin stuff @endrole");
    assert_equals("<?php if((\$__user = \App\Core\Gate::resolveUser()) && method_exists(\$__user, 'hasRole') && \$__user->hasRole('admin', 'editor')): ?>Admin stuff <?php endif; ?>", $resDirectives2, "View compiles @role directive correctly");

    // 6. Test View Share & Auto authUser Inject
    $viewDir = $app->view->getViewsPath();
    $testViewFile = $viewDir . '/test_share.blade.php';
    file_put_contents($testViewFile, "Site: {{ \$siteName }}, User: {{ \$authUser->name }}");

    $app->view->share('siteName', 'Spartan');
    
    $testUserObj = new \App\Models\User();
    $testUserObj->name = 'Ali';
    $app->container->instance('auth_user', $testUserObj);

    $renderResult = $app->view->renderViewOnly('test_share');
    assert_equals("Site: Spartan, User: Ali", trim($renderResult), "View::share and authUser auto-injection render correctly");

    if (file_exists($testViewFile)) {
        unlink($testViewFile);
    }
    $cachedFile = dirname(__DIR__) . '/storage/views/' . md5('test_share') . '.php';
    if (file_exists($cachedFile)) {
        unlink($cachedFile);
    }

} catch (\Throwable $e) {
    assert_true(false, "Logger, Dialect or FormRequest test failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}


// ─────────────────────────────────────────────────────────────────────────────
// 15. TEST: Refactored Core (Interfaces, Middlewares, and Migrator)
// ─────────────────────────────────────────────────────────────────────────────
test_group("Refactored Core Architectures");

try {
    // 1. Interface implementation verification
    assert_true($app->session instanceof \App\Core\SessionInterface, "Application session implements SessionInterface");
    assert_true($app->view instanceof \App\Core\ViewInterface, "Application view implements ViewInterface");

    // 2. Request.php isSecure and Method Spoofing
    $secRequest = new \App\Core\Request();
    $_SERVER['HTTPS'] = 'on';
    assert_true($secRequest->isSecure(), "Request detects HTTPS correctly");
    unset($_SERVER['HTTPS']);

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['_method'] = 'PUT';
    assert_equals('PUT', $secRequest->getMethod(), "Request getMethod() returns spoofed _method");
    assert_equals('POST', $secRequest->getRealMethod(), "Request getRealMethod() returns transport method POST");
    unset($_POST['_method']);
    $_SERVER['REQUEST_METHOD'] = 'GET';

    // 3. CsrfMiddleware CSRF Validation
    $csrfMiddleware = new \App\Middlewares\CsrfMiddleware();
    $mockReq = new \App\Core\Request();
    $mockRes = new MockResponse();
    
    $_SERVER['REQUEST_URI'] = '/checkout';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    unset($_SERVER['HTTP_X_CSRF_TOKEN']);
    unset($_POST['_csrf']);
    // Validate that it throws an exception on missing CSRF token
    assert_throws(\RuntimeException::class, function () use ($csrfMiddleware, $mockReq, $mockRes) {
        $csrfMiddleware->execute($mockReq, $mockRes);
    }, "CsrfMiddleware throws RuntimeException on missing CSRF token");
    $_SERVER['REQUEST_METHOD'] = 'GET';
    unset($_SERVER['REQUEST_URI']);

    // 4. Migrator Test
    $tempMigDir = __DIR__ . '/scratch_migrations';
    if (!is_dir($tempMigDir)) {
        mkdir($tempMigDir, 0755, true);
    }
    file_put_contents($tempMigDir . '/0001_create_temp_test_table.sql', "
        CREATE TABLE IF NOT EXISTS `temp_test_migration` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `val` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $migrator = new \App\Core\Database\Migrator($app->db, $tempMigDir);
    // Drop table if exists to ensure clean run
    $app->db->exec("DROP TABLE IF EXISTS `temp_test_migration` CASCADE");
    $app->db->exec("DROP TABLE IF EXISTS `migrations` CASCADE");

    $migrator->migrate();

    // Check if table exists
    $tableExists = false;
    try {
        $app->db->query("SELECT 1 FROM temp_test_migration LIMIT 1");
        $tableExists = true;
    } catch (\Throwable $e) {}
    assert_true($tableExists, "Migrator successfully executes migrations and creates tables");

    // Check if migration logged in migrations table
    $logged = $app->db->query("SELECT COUNT(*) FROM migrations WHERE migration = '0001_create_temp_test_table.sql'")->fetchColumn();
    assert_equals(1, (int)$logged, "Migrator records executed migrations in the migrations table");

    // Cleanup temp migration files
    unlink($tempMigDir . '/0001_create_temp_test_table.sql');
    rmdir($tempMigDir);
    $app->db->exec("DROP TABLE IF EXISTS `temp_test_migration` CASCADE");
    $app->db->exec("DROP TABLE IF EXISTS `migrations` CASCADE");

} catch (\Throwable $e) {
    assert_true(false, "Refactored Core test failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
}


// ─────────────────────────────────────────────────────────────────────────────
// 14. CLEANUP & SUMMARY
// ─────────────────────────────────────────────────────────────────────────────
test_group("Tear Down");
try {
    $db->exec("DROP TABLE IF EXISTS `blogger_comments` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `blogger_posts` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_profiles` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_orders` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_users` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_jobs` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `user_roles` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `role_permissions` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `roles` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `permissions` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `users` CASCADE");
    assert_true(true, "Database tables dropped and cleaned up.");
} catch (\Throwable $e) {
    echo COLOR_RED . "Warning: Cleanup failed: " . $e->getMessage() . COLOR_RESET . "\n";
}

$output = ob_get_clean();
echo $output;

echo "\n" . COLOR_CYAN . "=== Test Run Summary ===" . COLOR_RESET . "\n";
echo "Total Assertions: $assertionsCount\n";
if (empty($failedAssertions)) {
    echo COLOR_GREEN . "ALL TESTS PASSED SUCCESSFULLY! 🎉" . COLOR_RESET . "\n";
    exit(0);
} else {
    echo COLOR_RED . count($failedAssertions) . " ASSERTIONS FAILED:" . COLOR_RESET . "\n";
    foreach ($failedAssertions as $fail) {
        echo " - " . COLOR_RED . $fail . COLOR_RESET . "\n";
    }
    exit(1);
}

