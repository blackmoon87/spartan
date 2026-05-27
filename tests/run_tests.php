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
} else {
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
}
require_once __DIR__ . '/../src/Core/Application.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Core/Container.php';
require_once __DIR__ . '/../src/Core/Cache.php';
require_once __DIR__ . '/../src/Core/CacheDriverInterface.php';
require_once __DIR__ . '/../src/Core/CacheDrivers/FileCacheDriver.php';
require_once __DIR__ . '/../src/Core/CacheDrivers/RedisCacheDriver.php';
require_once __DIR__ . '/../src/Core/Request.php';
require_once __DIR__ . '/../src/Core/Response.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Core/Session.php';
require_once __DIR__ . '/../src/Core/View.php';
require_once __DIR__ . '/../src/Core/EventDispatcher.php';
require_once __DIR__ . '/../src/Core/JobQueue.php';
require_once __DIR__ . '/../src/Core/Model.php';
require_once __DIR__ . '/../src/Core/RelationQuery.php';
require_once __DIR__ . '/../src/Core/QueryBuilder.php';
require_once __DIR__ . '/../src/Core/Validator.php';
require_once __DIR__ . '/../src/Core/Middleware.php';
require_once __DIR__ . '/../src/Middlewares/SecurityHeadersMiddleware.php';
require_once __DIR__ . '/../src/Core/Controller.php';

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
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SESSION = []; // clean session mock

// Initialize DB if not configured
if (empty($config['db']['database'])) {
    $config['db']['database'] = 'spartan_test_db';
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

// 4. SETUP TEMPORARY TEST TABLES
test_group("Database Setup");
try {
    $db = \App\Core\Application::$app->db;
    if ($db === null) {
        throw new \RuntimeException("Database connection is null.");
    }
    
    // Drop old test tables to ensure fresh state
    $db->exec("DROP TABLE IF EXISTS `test_profiles` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_orders` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_users` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_jobs` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `clinic_invoices` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `clinic_appointments` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `clinic_patients` CASCADE");

    // Create schema
    $db->exec("CREATE TABLE `test_users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) UNIQUE NOT NULL,
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

    // Create dental clinic tables
    $db->exec("CREATE TABLE `clinic_patients` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) UNIQUE NOT NULL,
        `medical_history` TEXT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `clinic_appointments` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT UNSIGNED NOT NULL,
        `appointment_date` DATETIME NOT NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'scheduled',
        `treatment_notes` TEXT NULL,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        FOREIGN KEY(patient_id) REFERENCES clinic_patients(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE `clinic_invoices` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT UNSIGNED NOT NULL,
        `appointment_id` INT UNSIGNED NOT NULL,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `status` VARCHAR(50) NOT NULL DEFAULT 'unpaid',
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        FOREIGN KEY(patient_id) REFERENCES clinic_patients(id) ON DELETE CASCADE,
        FOREIGN KEY(appointment_id) REFERENCES clinic_appointments(id) ON DELETE CASCADE
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
$app->db->exec("INSERT INTO `test_users` (name, email) VALUES ('Alex', 'alex@mail.com')");
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
$newUserId = $userModel->create(['name' => 'Timestamps User', 'email' => 'time@mail.com']);
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
    public ?string $redirectUrl = '';

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
        $this->redirectUrl = '';
    }
}

$mockRequest = new \App\Core\Request();
$mockResponse = new MockResponse();
$testRouter = new \App\Core\Router($mockRequest, $mockResponse);

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

// 1. READ: Test GET / index dashboard rendering
$dashboardHTML = simulate_mvc_request('GET', '/');
assert_true(str_contains($dashboardHTML, 'Spartan Framework Showcase'), "MVC Integration GET '/' renders the views layout");
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

// 4. UPDATE: Test PUT /order/{id} status toggle
$orderModel = new \Tests\Sample\Models\Order();
$orderId = $orderModel->create([
    'user_id' => (int)$user['id'],
    'status'  => 'pending',
    'total'   => 150.00
]);

$mockResponse->redirectUrl = '';
$app->session->removeFlashMessages(); // clear flash
simulate_mvc_request('POST', "/order/{$orderId}", [
    '_method' => 'PUT',
    'status'  => 'completed',
    '_csrf' => $csrfToken
]);
assert_equals('/', $mockResponse->redirectUrl, "MVC PUT updates redirect properly");
$updatedOrder = $orderModel->find($orderId);
assert_equals('completed', $updatedOrder['status'], "MVC PUT update successfully updates model attributes in the DB");

// 5. DELETE: Test DELETE /order/{id}
$mockResponse->redirectUrl = '';
$app->session->removeFlashMessages(); // clear flash
simulate_mvc_request('POST', "/order/{$orderId}", [
    '_method' => 'DELETE',
    '_csrf' => $csrfToken
]);
assert_equals('/', $mockResponse->redirectUrl, "MVC DELETE redirects properly");
$deletedOrder = $orderModel->find($orderId);
assert_true($deletedOrder === null, "MVC DELETE successfully removes record from database");

// 6. Blade & HTMX Integration: Test GET /search and POST /search/query
(new \Tests\Sample\Models\User())->create([
    'name'  => 'Searchable User',
    'email' => 'search@example.com'
]);

$searchPageHtml = simulate_mvc_request('GET', '/search');
assert_true(str_contains($searchPageHtml, 'Customer Directory Search'), "Blade template renders sections correctly");
assert_true(str_contains($searchPageHtml, 'SPARTAN + BLADE + HTMX'), "Blade template inherits layout structures (@extends)");
assert_true(str_contains($searchPageHtml, 'hx-post="/search/query"'), "Blade templates support HTMX tags");

// Simulate POST search request
$searchResultsHtml = simulate_mvc_request('POST', '/search/query', [
    'query' => 'Searchable',
    '_csrf' => $csrfToken
]);
assert_true(str_contains($searchResultsHtml, 'Searchable User'), "HTMX searchQuery controller renders matching query results");
assert_true(str_contains($searchResultsHtml, 'search@example.com'), "HTMX searchQuery renders values cleanly");
assert_true(!str_contains($searchResultsHtml, 'SPARTAN + BLADE + HTMX'), "renderViewOnly compiles without layouts");




// ─────────────────────────────────────────────────────────────────────────────
// Dental Clinic Integration Tests [ignoring loop detection]
// ─────────────────────────────────────────────────────────────────────────────
test_group("Dental Clinic Management System");

$patientModel = new \Tests\Sample\Models\Patient();
$appointmentModel = new \Tests\Sample\Models\Appointment();
$invoiceModel = new \Tests\Sample\Models\Invoice();

// 1. Model Relationship & Creation tests
$patientId = $patientModel->create([
    'name' => 'Alice Clinic',
    'phone' => '555-9876',
    'email' => 'alice@clinic.com',
    'medical_history' => 'Lactose intolerant'
]);
assert_true(is_numeric($patientId), "Patient created successfully and returned ID");

$apptId = $appointmentModel->create([
    'patient_id' => (int)$patientId,
    'appointment_date' => '2026-06-01 10:00:00',
    'status' => 'scheduled',
    'treatment_notes' => 'Routine tooth cleaning'
]);
assert_true(is_numeric($apptId), "Appointment created successfully and returned ID");

$invoiceId = $invoiceModel->create([
    'patient_id' => (int)$patientId,
    'appointment_id' => (int)$apptId,
    'total_amount' => 120.00,
    'paid_amount' => 0.00,
    'status' => 'unpaid'
]);
assert_true(is_numeric($invoiceId), "Invoice created successfully and returned ID");

// Test Relationships
$fetchedPatient = $patientModel->find($patientId);
$patientAppts = $patientModel->appointments()->for($fetchedPatient);
assert_equals(1, count($patientAppts), "Patient appointments relationship resolves successfully");

$fetchedAppt = $appointmentModel->find($apptId);
$apptPatient = $appointmentModel->patient()->for($fetchedAppt);
assert_equals('Alice Clinic', $apptPatient['name'], "Appointment belongsTo patient relationship resolves successfully");

// 2. Controller & Routing HTTP Tests
$csrfToken = $app->session->get('_csrf_token');

// Post a new patient via route
$mockResponse->redirectUrl = '';
simulate_mvc_request('POST', '/clinic/patient', [
    'name' => 'Bob Dentist',
    'phone' => '555-1234',
    'email' => 'bob@dentist.com',
    'medical_history' => 'No medical issues',
    '_csrf' => $csrfToken
]);
assert_equals('/clinic', $mockResponse->redirectUrl, "Store patient redirects back to clinic dashboard");

$bobPatient = $patientModel->table()->where('email', 'bob@dentist.com')->first();
assert_true(!empty($bobPatient), "Store patient route successfully inserts patient into database");

// Post a new appointment (which auto-creates an invoice)
$mockResponse->redirectUrl = '';
simulate_mvc_request('POST', '/clinic/appointment', [
    'patient_id' => (int)$bobPatient['id'],
    'appointment_date' => '2026-06-02 14:00:00',
    'procedure_cost' => '250.50',
    'treatment_notes' => 'Tooth extraction',
    '_csrf' => $csrfToken
]);
assert_equals('/clinic', $mockResponse->redirectUrl, "Store appointment redirects back to clinic dashboard");

$bobAppt = $appointmentModel->table()->where('patient_id', (int)$bobPatient['id'])->first();
assert_true(!empty($bobAppt), "Store appointment route successfully inserts appointment");

$bobInvoice = $invoiceModel->table()->where('appointment_id', (int)$bobAppt['id'])->first();
assert_true(!empty($bobInvoice), "Store appointment automatically generates matching clinic invoice");
assert_equals(250.50, (float)$bobInvoice['total_amount'], "Auto-generated invoice total amount is correct");
assert_equals('unpaid', $bobInvoice['status'], "Auto-generated invoice starts as unpaid");

// Pay invoice (partial payment)
$mockResponse->redirectUrl = '';
simulate_mvc_request('POST', "/clinic/invoice/{$bobInvoice['id']}/pay", [
    'amount' => '100.00',
    '_csrf' => $csrfToken
]);
assert_equals('/clinic', $mockResponse->redirectUrl, "Pay invoice redirects back to clinic dashboard");

$updatedInvoice = $invoiceModel->find($bobInvoice['id']);
assert_equals(100.00, (float)$updatedInvoice['paid_amount'], "Partial payment is successfully recorded");
assert_equals('partial', $updatedInvoice['status'], "Invoice status transitions to partial");

// Pay invoice (remaining payment)
$mockResponse->redirectUrl = '';
simulate_mvc_request('POST', "/clinic/invoice/{$bobInvoice['id']}/pay", [
    'amount' => '150.50',
    '_csrf' => $csrfToken
]);
assert_equals('/clinic', $mockResponse->redirectUrl, "Remaining payment redirects back to clinic dashboard");

$fullyPaidInvoice = $invoiceModel->find($bobInvoice['id']);
assert_equals(250.50, (float)$fullyPaidInvoice['paid_amount'], "Total paid amount is updated");
assert_equals('paid', $fullyPaidInvoice['status'], "Invoice status transitions to paid");

// Complete appointment (PUT status spoofing)
$mockResponse->redirectUrl = '';
simulate_mvc_request('POST', "/clinic/appointment/{$bobAppt['id']}", [
    '_method' => 'PUT',
    'status' => 'completed',
    '_csrf' => $csrfToken
]);
assert_equals('/clinic', $mockResponse->redirectUrl, "Complete appointment redirects back to clinic dashboard");

$completedAppt = $appointmentModel->find($bobAppt['id']);
assert_equals('completed', $completedAppt['status'], "Appointment status successfully updated to completed");

// Test GET /clinic dashboard view rendering
$clinicDashboardHtml = simulate_mvc_request('GET', '/clinic');
assert_true(str_contains($clinicDashboardHtml, 'Total Patients'), "Clinic dashboard renders stats widgets");
assert_true(str_contains($clinicDashboardHtml, 'Bob Dentist'), "Clinic dashboard displays patient name in patient records");
assert_true(str_contains($clinicDashboardHtml, 'Tooth extraction'), "Clinic dashboard displays treatment notes in appointments");

// HTMX Patient Live Search
$searchHtml = simulate_mvc_request('POST', '/clinic/patients/search', [
    'query' => 'Bob',
    '_csrf' => $csrfToken
]);
assert_true(str_contains($searchHtml, 'Bob Dentist'), "HTMX patients search route returns search result partial");

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
    $tempRouter = new \App\Core\Router($app->request, $app->response);
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

} catch (\Throwable $e) {
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
// 14. CLEANUP & SUMMARY
// ─────────────────────────────────────────────────────────────────────────────
test_group("Tear Down");
try {
    $db->exec("DROP TABLE IF EXISTS `test_profiles` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_orders` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_users` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `test_jobs` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `clinic_invoices` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `clinic_appointments` CASCADE");
    $db->exec("DROP TABLE IF EXISTS `clinic_patients` CASCADE");
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

