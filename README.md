# Spartan — Lightweight PHP MVC Framework

> Zero dependencies. Full control. Production-ready security.

A hand-crafted PHP 8.1+ MVC framework built for developers who want to understand every line of their stack. No magic, no bloat — just clean architecture with serious security baked in.

---

## What's Inside

| Layer | Capability |
|---|---|
| **Router** | GET / POST / PUT / PATCH / DELETE + HTML form method spoofing & middleware groups |
| **Request** | Auto JSON body parsing, file upload helpers, header resolver, client IP resolver |
| **Rate Limiter** | Parameterized rate limiting middleware (`rate_limit:100,60`) with Client IP tracking |
| **QueryBuilder** | Fluent, fully parameterized — no raw SQL. Write guards on `update()` / `delete()` |
| **Model Relationships** | `hasMany`, `hasOne`, `belongsTo` + eager loading (no N+1) |
| **Async Job Queue** | DB-backed queue with retry + exponential backoff |
| **DI Container** | Auto-resolution via Reflection + singleton / factory / instance bindings |
| **Cache** | File or Redis driver — `Cache::remember()` pattern |
| **Events** | Synchronous and async listeners — side effects stay out of controllers |
| **Validator** | `required`, `email`, `unique`, `regex`, `nullable`, `confirmed`, `min`, `max`, `in` |
| **Session** | HttpOnly + SameSite=Lax + Secure (auto-detect) + CSRF generation |
| **View** | Layout + template rendering with double path-traversal guard |
| **Security** | CSRF (form/AJAX/JSON), XSS escape, open redirect guard, security headers middleware |

---

## Requirements

- PHP **8.1+**
- MySQL / MariaDB (for DB features)
- Apache with `mod_rewrite` **or** PHP built-in server

---

## Getting Started

### 1. Clone

```bash
git clone https://github.com/blackmoon87/spartan.git
cd spartan
```

### 2. Configure

```bash
cp .env.example .env
```

Edit `.env`:

```env
APP_NAME=Spartan
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Run

```bash
# Built-in PHP server
php -S localhost:8000 -t public

# With native built-in autoloader (Zero-dependency)
php -S localhost:8000 -t public

# Or with Composer autoloader (optional)
composer dump-autoload
php -S localhost:8000 -t public
```

### 4. Async Queue (optional)

Run `storage/jobs.sql` in your database, then start the worker:

```bash
# Single pass (use with Cron — every minute)
php worker.php

# Continuous loop (development / daemon)
php worker.php --loop
```

---

## Structure

```
├── config/
│   └── config.php              # .env loader → config array
├── public/
│   ├── .htaccess               # URL rewriting
│   └── index.php               # Front controller
├── routes/
│   ├── web.php                 # Public routes
│   ├── admin.php               # Protected routes
│   └── api.php                 # JSON API routes
├── src/
│   ├── Core/                   # Framework kernel (do not modify)
│   │   ├── Application.php
│   │   ├── Router.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Controller.php
│   │   ├── Model.php
│   │   ├── QueryBuilder.php
│   │   ├── RelationQuery.php
│   │   ├── JobQueue.php
│   │   ├── EventDispatcher.php
│   │   ├── Container.php
│   │   ├── Cache.php
│   │   ├── Session.php
│   │   ├── Validator.php
│   │   └── View.php
│   ├── Controllers/
│   ├── Models/
│   ├── Services/
│   ├── Listeners/
│   ├── Events/
│   ├── Middlewares/
│   └── Views/
│       └── layouts/
├── storage/
│   ├── cache/
│   └── jobs.sql                # Async queue table schema
├── worker.php                  # CLI queue worker
├── .env
├── .env.example
├── .cursorrules                # AI IDE architecture rules
└── composer.json
```

---

## Core Examples

### Routing

Spartan features a robust, regex-based router that supports RESTful methods, route parameters, middleware piping, and form method spoofing.

#### 1. Defining Route Types
Define routes in their respective files under the `routes/` directory depending on their context:
* **Web Routes (`routes/web.php`)**: For standard browser pages (GET) and web forms (POST).
* **Protected/Admin Routes (`routes/admin.php`)**: For routes requiring authentication or specific security checks. Attach middlewares to these routes:
  ```php
  $app->router->get('/admin/dashboard', [AdminController::class, 'index'], [
      SecurityHeadersMiddleware::class,
      AuthMiddleware::class,
  ]);
  ```
* **API Routes (`routes/api.php`)**: For stateless JSON API endpoints.

#### 2. Parameterized Routes
Capture dynamic URL segments using curly braces `{param}`. These are automatically extracted and passed to your controller action as arguments:
```php
// Route Definition
$app->router->get('/users/{userId}/orders/{orderId}', [UserController::class, 'showOrder']);

// Controller Action
class UserController extends Controller {
    public function showOrder(string $userId, string $orderId) {
        // Automatically populated from the URL segments
    }
}
```

#### 3. Form Method Spoofing
Native HTML forms only support `GET` and `POST`. To perform RESTful `PUT`, `PATCH`, or `DELETE` requests from a form, add a hidden `_method` field. The router intercepts this field and directs the request to the correct handler:
```html
<form method="POST" action="/orders/42">
    <!-- CSRF Protection -->
    @csrf 
    <!-- Method Spoofing -->
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit">Cancel Order</button>
</form>
```

---

### View & Backend Integration

The view layer (`V`) acts as the presentation layer, integrating with the controller (`C`) by receiving structured variables, rendering layouts, and handling client-side state reactively via HTMX and Alpine.js.

#### 1. Passing Variables from Backend to Frontend
In your controller, you return a rendered template by passing an associative array containing the variables:
```php
class DashboardController extends Controller {
    public function index() {
        $stats = $this->model->getStatistics();
        return $this->render('dashboard', [
            'stats' => $stats,
            'title' => 'Admin Panel'
        ]);
    }
}
```
Behind the scenes:
- **Native PHP Views (`.php`)**: The framework extracts the associative array into local variables using PHP's `extract()` function inside the `View` object's execution context. You print them using `$this->escape($title)` or `<?= $this->escape($title) ?>`.
- **Blade Views (`.blade.php`)**: The framework compiles Blade directives natively using a regex-based compiler (extracted from the spirit of BladeOne). You print variables using `{{ $title }}` (which is escaped by default) or `{!! $title !!}` (for raw, unescaped HTML).

#### 2. Hybrid Render Methods
* **`render($view, $data)`**: Compiles the template and wraps it inside a main layout (e.g. `layouts/main_blade.blade.php`), yielding the template content inside the `@yield('content')` block.
* **`renderViewOnly($view, $data)`**: Compiles the template and returns only its raw HTML content without wrapping it in a layout. This is perfect for returning partial AJAX fragments to **HTMX** requests.

#### 3. Frontend Interactivity (HTMX & Alpine.js Flow)
HTMX handles server-client updates without writing complex JavaScript, while Alpine.js handles local client state.
* **HTMX AJAX Swap**: Send a request asynchronously on keystrokes or button clicks, and swap the returned partial template directly into a DOM node:
  ```html
  <!-- Views/search.blade.php -->
  <input type="text" name="query" 
         hx-post="/search/query" 
         hx-trigger="keyup changed delay:300ms" 
         hx-target="#search-results" 
         hx-include="[name=_csrf]"
         placeholder="Type to search...">

  <div id="search-results">
      <!-- The search_results.blade.php view will be injected here -->
  </div>
  ```
* **Controller handler (Backend)**: Receives the POST query, fetches filtered data, and returns only the partial view snippet:
  ```php
  public function searchQuery() {
      $query = $this->request->post('query');
      $results = (new Customer)->search($query);
      
      // Render ONLY the partial list view
      return $this->renderViewOnly('search_results', ['users' => $results]);
  }
### Middlewares & Rate Limiting

Spartan supports mapping middleware aliases and groups in `Router.php` and passing dynamic parameters (e.g. `rate_limit:limit,window`).

#### 1. Defining Parameterized Middleware Routes
```php
$app->router->get('/dashboard', [DashboardController::class, 'index'], [
    'auth',
    'rate_limit:100,60' // Max 100 requests per 60 seconds (IP-based)
]);
```

#### 2. How the Rate Limiter Middleware Works
The `RateLimitMiddleware` checks the user's IP address and rate limits the route dynamically:
* In case of violations, it returns a `429 Too Many Requests` status code and terminates the route cycle early.
* Adds standard headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `Retry-After`.

---

### Request & API Inputs

Spartan's Request class encapsulates all query parameters, form data, uploaded files, and HTTP headers.

#### 1. Automatic JSON Body Parsing
When a client sends a request with `Content-Type: application/json`, the request body is automatically decoded and merged. You can retrieve inputs using standard methods:
```php
// Automatically parses JSON input: {"title": "Hello"}
$title = $this->request->input('title');
$body  = $this->request->getBody();
```

#### 2. Request Headers Helper
```php
$token = $this->request->header('Authorization'); // Bearer <token>
```

#### 3. File Uploads Helper
Never access `$_FILES` directly. Use:
```php
$file = $this->request->file('avatar'); // Retrieves file array
$allFiles = $this->request->getFiles();

// Upload path configured in config/config.php
$uploadDir = Application::$app->config['storage']['uploads'];
```

---

### QueryBuilder

```php
// Fluent, fully parameterized
$this->table()->where('active', 1)->orderBy('name')->paginate(15, $page);

// Write guards — throws LogicException without where()
$this->table()->where('id', $id)->update(['status' => 'active']);
$this->table()->where('id', $id)->delete();
```

### Model Relationships

```php
class User extends Model
{
    protected string $table = 'users';

    public function orders(): RelationQuery
    {
        return $this->hasMany(Order::class, foreignKey: 'user_id');
    }
}

// Single record
$user   = (new User)->find(1);
$orders = (new User)->orders()->for($user);

// Eager load — 2 queries total, no N+1
$users = (new User)->all();
$users = (new User)->orders()->loadFor($users, as: 'orders');
```

### Async Events

```php
// Register — async/sync per listener
$app->events->listen('order.placed', UpdateInventory::class);              // sync
$app->events->listen('order.placed', SendOrderSms::class,
    async: true, maxAttempts: 3, onFailure: 'retry'                        // async
);

// Dispatch — identical regardless
$this->event('order.placed', $order);
```

### Validation

```php
$v = $this->validate($this->request->getBody(), [
    'email'    => 'required|email|unique:users,email',
    'password' => 'required|min:8|max:64',
    'phone'    => 'nullable|string|regex:/^\+?[0-9]{7,15}$/',
]);

if ($v->fails()) {
    return $this->render('register', ['errors' => $v->errors()]);
}
```

---

## Security

| Threat | Mitigation |
|---|---|
| SQL Injection | QueryBuilder — fully parameterized, zero raw SQL |
| XSS | `$this->escape()` in all views — `ENT_QUOTES UTF-8` |
| CSRF | Token validated on all POST (form / AJAX header / JSON body) |
| Session Fixation | `Session::regenerate()` after every login |
| Path Traversal | Regex + `realpath()` double-guard in View |
| Open Redirect | `Response::redirect()` blocks external domains |
| Clickjacking | `SecurityHeadersMiddleware` — X-Frame-Options: SAMEORIGIN |
| MIME Sniffing | X-Content-Type-Options: nosniff |

---

## AI IDE Rules

The `.cursorrules` file encodes the full architecture as enforceable rules for AI assistants (Cursor, GitHub Copilot, etc.). It covers:

- Directory structure and namespace conventions
- Security rules (mandatory escape, CSRF in every form, session regeneration)
- QueryBuilder API reference
- Relationship patterns and eager loading rules
- Async queue configuration
- Feature workflow (route → service → model → controller → view)

---

## Philosophy

- **No magic** — every class is explicit and traceable
- **No raw SQL** — QueryBuilder only, write guards enforced
- **No inline side effects** — SMS/email/PDF goes through Listeners
- **No hardcoded config** — everything via `.env`
- **Explicit over implicit** — `foreignKey` is always declared, never guessed

---

## License

MIT
