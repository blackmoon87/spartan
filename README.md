# Spartan — Lightweight PHP MVC Framework

> Zero dependencies. Full control. Production-ready security.

A hand-crafted PHP 8.1+ MVC framework built for developers who want to understand every line of their stack. No magic, no bloat — just clean architecture with serious security baked in.

---

## What's Inside

| Layer | Capability |
|---|---|
| **Router** | GET / POST / PUT / PATCH / DELETE + HTML form method spoofing |
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
git clone https://github.com/your-username/spartan.git
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

# Or with Composer autoloader (recommended)
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

```php
// routes/web.php
$app->router->get('/users', [UserController::class, 'index']);
$app->router->post('/users', [UserController::class, 'store']);
$app->router->put('/users/{id}', [UserController::class, 'update']);
$app->router->delete('/users/{id}', [UserController::class, 'destroy']);

// With middleware
$app->router->get('/admin', [AdminController::class, 'index'], [
    SecurityHeadersMiddleware::class,
    AuthMiddleware::class,
]);
```

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
