# Strux Framework

Strux is a modern, lightweight, and powerful **PHP framework** designed for building robust web applications and APIs.
It combines a clean architecture with a rich feature set—including an Active Record ORM, built-in queue system, event
dispatcher, and flexible middleware—while maintaining a minimal core with **few external dependencies**.

Strux strictly adheres to **PSR-1, PSR-2, PSR-3, PSR-4, and PSR-7** standards for maximum interoperability.

---

## 📋 Table of Contents

* [Features](#features)
* [Requirements](#requirements)
* [Installation](#installation)
* [Configuration](#configuration)
* [Directory Structure](#directory-structure)
* [Routing](#routing)
* [Controllers](#controllers)
* [Requests & Responses](#requests--responses)
* [Middleware](#middleware)
* [Views & Templating](#views--templating)
* [Database & ORM](#database--orm)
* [Migrations](#migrations)
* [Query Builder](#query-builder)
* [Model Relationships](#model-relationships)
* [Event Dispatcher](#event-dispatcher)
* [Queue System](#queue-system)
* [Security](#security)
* [Command-Line Interface (CLI)](#command-line-interface-cli)
* [License](#license)

---

## ✨ Features

* PSR-compliant architecture (PSR-1, 2, 3, 4, 7)
* Zero external dependencies
* Attribute-based routing and ORM
* Active Record ORM with relationships
* Middleware dispatcher
* Event & queue systems
* Built-in validation and security
* CLI tooling for rapid development
* Plates templating (Twig adapter available)

---

## 🧰 Requirements

* PHP **8.2+**
* Composer
* PDO extension (for database access)

---

## 🚀 Installation

### Create a New Project

```bash
composer create-project strux/strux-app my-app
cd my-app
```

### Serve the Application

```bash
php bin/console run
```

---

## ⚙️ Configuration

Configuration files are stored in the `etc/` directory and are automatically loaded.

### Environment Variables

Copy the example environment file and update it:

```bash
cp .env.example .env
```

```env
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=strux_db
DB_USERNAME=root
DB_PASSWORD=secret
```

---

## 📂 Directory Structure

```
bin/        # CLI entry point
etc/        # Configuration & route files
src/        # Application source code
templates/  # Views, assets, language files
web/        # Public entry point (index.php)
var/        # Cache, logs, sessions
```

---

## 🛣 Routing

Routes are defined in `etc/routes/web.php` or `etc/routes/api.php`.

### Fluent Routing

```php
$router->get('/', [HomeController::class, 'index']);
$router->get('/users/:id', [UserController::class, 'show']);
$router->post('/login', [AuthController::class, 'login'])->name('login');
```

### Attribute-Based Routing

```php
use Strux\Component\Attributes\Route;

class UserController
{
    #[Route('/users/:id', methods: ['GET'])]
    public function show(int $id) {}
}
```

---

## 🎮 Controllers

Controllers live in `src/Http/Controller` and receive dependencies automatically via the service container.

```php
class PageController extends Controller
{
    public function home(Request $request)
    {
        return $this->view('home', ['name' => 'Strux']);
    }
}
```

---

## 📥 Requests & Responses

### Request Access

```php
$request->input('name');
$request->query('page');
$request->header('User-Agent');
$request->file('avatar');
```

### Responses

```php
return $this->view('profile');
return $this->json(['status' => 'ok']);
return $this->redirect('/login');
```

---

## 🛡 Middleware

Middleware intercepts requests before controllers execute.

```php
class AuthMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler): ResponseInterface
    {
        if (!Auth::check()) {
            return $this->responseFactory->createResponse(302)
            ->withHeader('Location', '/login');
        }
        return $handler->handle($request);
    }
}
```

* Global middleware: `etc/middleware.php`
* Route-specific or attribute-based registration supported

---

## 🎨 Views & Templating

Strux uses **Plates** by default (Twig supported via adapter).

```php
return $this->view('auth/login', ['error' => 'Invalid credentials']);
```

```php
<?php $this->layout('layouts/app', ['title' => 'Login']) ?>
<h1>Login</h1>
```

---

## 🗄 Database & ORM

Strux includes an **Active Record ORM** using PHP Attributes.

### Model Definition

```php
#[Table('users')]
class User extends Model
{
    #[Id]
    #[Column('id')]
    public int $id;

    #[Column('username')]
    public string $username;
}
```

### Basic Usage

```php
$user = new User();
$user->username = 'john_doe';
$user->save();

$user = User::find(1);
$user->delete();
```

---

## 🏗 Migrations

```bash
php bin/console db:migrate
```

---

## 🔍 Query Builder

```php
$users = User::query()
    ->where('active', 1)
    ->where('age', '>', 18)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();
```

---

## 🔗 Model Relationships

Supported relationships:

* `#[HasOne]`
* `#[HasMany]`
* `#[BelongsTo]`
* `#[BelongsToMany]`

```php
#[BelongsToMany(related: Course::class, pivotTable: 'enrollments')]
public Collection $courses;
```

```php
$student->courses;
$student->courses()->sync([1, 2, 3]);
```

---

## 📡 Event Dispatcher

```php
Event::dispatch(new UserRegistered($user));
```

```php
class SendWelcomeEmail
{
    public function handle(UserRegistered $event) {}
}
```

---

## 📨 Queue System

```php
Queue::push(new SendEmailJob($user));
php bin/console queue:start
```

---

## 🔒 Security

* Authentication via Sentinels (Session, JWT)
* Authorization with `#[Authorize]` attributes
* CSRF protection middleware
* Built-in validation system (`Required`, `Email`, `MinLength`, etc.)

---

## 💻 Command-Line Interface (CLI)

```bash
php bin/console
php bin/console new:controller
php bin/console new:model
php bin/console db:seed
```

---

## 📄 License

Strux Framework is open-source software licensed under the **MIT License**.