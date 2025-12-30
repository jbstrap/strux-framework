# Strux Framework – Controller Documentation

## Introduction

Strux controllers handle incoming HTTP requests and return responses. They act as the glue between **routes**, **models
**, and **views**, providing a clean and expressive way to build web and API applications using modern PHP features.

The framework leverages **PHP 8 Attributes**, **dependency injection**, and **response helpers** to keep controllers
concise and readable.

---

## Table of Contents

* [Key Features](#key-features)
* [Controller Structure](#controller-structure)
* [Creating a Controller](#creating-a-controller)
* [Attribute-Based Routing](#attribute-based-routing)

    * [Basic Routes](#basic-routes)
    * [Route Parameters](#route-parameters)
    * [Type Constraints](#type-constraints)
    * [Named Routes](#named-routes)
    * [Optional Parameters](#optional-parameters)
    * [Default Values](#default-values)
    * [Redirect Routes](#redirect-routes)
    * [API Routes](#api-routes)
* [Middleware](#middleware)
* [Request Handling](#request-handling)
* [Response Types](#response-types)
* [Complete Example](#complete-example-studentcontroller)

---

## Key Features

* 🚀 **Attribute-Based Routing**
  Define routes directly above controller methods using PHP 8 attributes.

* 🧩 **Dependency Injection**
  Automatic injection of services and the `Request` object.

* 🛡 **Middleware Support**
  Attach middleware to specific routes or entire controllers.

* 📤 **Response Helpers**
  Easily return views, JSON responses, or redirects.

---

## Controller Structure

Controllers are organized by application type:

* **Web Controllers:**
  `App\Http\Controller\Web`

* **API Controllers:**
  `App\Http\Controller\Api`

All controllers should extend the base `Controller` class.

---

## Creating a Controller

### Basic Example

```php
<?php

namespace App\Http\Controller\Web;

use Strux\Component\Http\Controller\Web\Controller;
use Strux\Component\Http\Response;
use Strux\Component\Attributes\Route;

class PageController extends Controller
{
    #[Route('/', methods: ['GET'])]
    public function home(): Response
    {
        return $this->view('home', ['title' => 'Welcome']);
    }
}
```

---

## Attribute-Based Routing

### Basic Routes

```php
#[Route('/about', methods: ['GET'])]
public function about(): Response
{
    return $this->view('about');
}
```

---

### Route Parameters

Dynamic route segments can be defined using **colon syntax** (recommended):

```php
#[Route('/users/:id', methods: ['GET'])]
public function show(int $id): Response
{
    $user = User::find($id);
    return $this->view('users/show', ['user' => $user]);
}
```

---

### Type Constraints

You can enforce parameter types directly in the route:

```php
#[Route('/users/int:id', methods: ['GET'])]
public function show(int $id): Response
{
    // ...
}
```

---

### Named Routes

Named routes allow easy URL generation:

```php
#[Route('/contact', methods: ['GET', 'POST'], name: 'contact.form')]
public function contact(): Response
{
    // ...
}
```

---

### Optional Parameters

Optional parameters use the `|?` suffix:

```php
#[Route('/blog/:category/page/int:page|?', methods: ['GET'])]
public function blog(string $category, ?int $page = 1): Response
{
    // ...
}
```

---

### Default Values

Provide defaults using the `defaults` argument:

```php
#[Route('/list/:page', methods: ['GET'], defaults: ['page' => 1])]
public function index(int $page): Response
{
    // ...
}
```

---

### Redirect Routes

Define redirects directly in the route attribute:

```php
#[Route('/old-profile', toRoute: 'user.profile')]
public function oldProfile(): Response
{
    // ...
}
```

---

### API Routes

API controllers typically live in the API namespace and return JSON:

```php
#[Route('/api/v1/products', methods: ['GET'])]
public function index(): Response
{
    return $this->json(['products' => []]);
}
```

---

## Middleware

Attach middleware to individual controller methods using the `#[Middleware]` attribute:

```php
use Strux\Component\Attributes\Middleware;
use App\Http\Middleware\AuthMiddleware;

#[Route('/dashboard', methods: ['GET'])]
#[Middleware([AuthMiddleware::class])]
public function dashboard(): Response
{
    return $this->view('dashboard');
}
```

---

## Request Handling

The current `Request` object is automatically injected when type-hinted.

```php
use Strux\Component\Http\Request;

#[Route('/submit', methods: ['POST'])]
public function store(Request $request): Response
{
    $name = $request->safe()->input('name');
    $email = $request->input('email');

    return $this->redirect('/success');
}
```

### Input Helpers

* `$request->input('key', 'default')` – POST/PUT data
* `$request->query('key')` – Query parameters
* `$request->all()` – All input data
* `$request->has('key')` – Check existence

---

## Response Types

### View Response (HTML)

```php
return $this->view('profile/index', [
    'user' => $user,
    'settings' => $settings
]);
```

---

### JSON Response (API)

```php
return $this->json([
    'status' => 'success',
    'data' => $user
], 200);
```

---

### Redirect Response

```php
return $this->redirect('/login');
```

---

## Complete Example: `StudentController`

```php
<?php

namespace App\Http\Controller\Web;

use Strux\Component\Http\Controller\Web\Controller;
use Strux\Component\Http\Request;
use Strux\Component\Attributes\Route;
use App\Model\Student;

class StudentController extends Controller
{
    #[Route('/students', methods: ['GET'], name: 'students.index')]
    public function index(Request $request)
    {
        $students = Student::all();
        return $this->view('students/index', ['students' => $students]);
    }

    #[Route('/students/create', methods: ['GET'], name: 'students.create')]
    public function create()
    {
        return $this->view('students/create');
    }

    #[Route('/students', methods: ['POST'], name: 'students.store')]
    public function store(Request $request)
    {
        $student = new Student();
        $student->first_name = $request->input('first_name');
        $student->save();

        $student->courses()->sync($request->input('courses', []));

        return $this->redirect('/students');
    }

    #[Route('/students/int:id', methods: ['GET'], name: 'students.show')]
    public function show(int $id)
    {
        $student = Student::find($id);
        return $this->view('students/show', ['student' => $student]);
    }

    #[Route('/students/int:id', methods: ['PUT'], name: 'students.update')]
    public function update(Request $request, int $id)
    {
        $student = Student::find($id);
        $student->update($request->all());

        return $this->redirect('/students');
    }

    #[Route('/students/int:id', methods: ['DELETE'], name: 'students.delete')]
    public function delete(int $id)
    {
        Student::destroy($id);
        return $this->redirect('/students');
    }
}
```