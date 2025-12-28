Below is a polished **README.md** generated from your documentation, structured for a typical MVC framework repository. It keeps your technical details intact while making the system easy to understand for new contributors and users.

---

# Enhanced Routing System

An advanced routing system for an MVC framework that supports modern, expressive route definitions. It introduces placeholder-based parameters, type constraints, optional parameters, named routes, default values, redirects, and flexible configuration via both attributes and a fluent API.

## Table of Contents

* [Overview](#overview)
* [Key Features](#key-features)
* [Route Parameter Syntax](#route-parameter-syntax)
* [Type Constraints](#type-constraints)
* [Optional Parameters](#optional-parameters)
* [Route Prefixes](#route-prefixes)
* [Named Routes](#named-routes)
* [Default Parameter Values](#default-parameter-values)
* [Redirects](#redirects)
* [Defining Multiple Routes](#defining-multiple-routes)
* [Controller Parameter Type Handling](#controller-parameter-type-handling)
* [Usage Examples](#usage-examples)
* [Backward Compatibility](#backward-compatibility)

---

## Overview

The Enhanced Routing System extends the framework’s existing router with a more expressive and developer-friendly syntax. Inspired by popular frameworks such as Express and Flask, it simplifies route definitions while maintaining backward compatibility.

Routes can be defined using:

* PHP attributes
* A fluent routing API

Both approaches can coexist in the same application.

---

## Key Features

* Placeholder-based parameters (`:id`)
* Type constraints with automatic validation and casting
* Optional parameters with defaults
* Repeatable controller-level prefixes
* Named routes with URL generation helpers
* Built-in redirect support
* Attribute-based and fluent API routing
* Automatic controller parameter type handling

---

## Route Parameter Syntax

The router supports both legacy and modern parameter styles:

```php
// Legacy syntax (supported)
"/user/edit/{id}"

// Recommended modern syntax
"/user/edit/:id"
```

The colon (`:`) syntax is recommended for all new routes.

---

## Type Constraints

Parameters can include type constraints that validate and cast values automatically.

```php
"/user/edit/int:id"
"/user/profile/string:username"
"/post/view/slug:title"
```

### Supported Types

| Type      | Description                          |
| --------- | ------------------------------------ |
| `int:`    | Integer values only                  |
| `string:` | Any string without slashes (default) |
| `slug:`   | Alphanumeric characters and hyphens  |

---

## Optional Parameters

Parameters can be marked optional using the `|?` suffix:

```php
"/blog/:category/page/int:page|?"
```

**Rules:**

* Optional parameters must appear at the end of the route.
* If omitted, the parameter is set to `null` or its default value.

---

## Route Prefixes

Controller-level prefixes can be defined using the `#[Prefix]` attribute.
Prefixes are **repeatable**, allowing a controller to respond to multiple base paths.

```php
#[Prefix('/user')]
#[Prefix('/users')]
class UserController
{
    // Handles /user/... and /users/...
}
```

Prefixes may also define default parameter values that are merged with route-level defaults.

---

## Named Routes

Routes can be assigned names for easy URL generation and redirection.

### Defining Named Routes

```php
#[Route('/user/profile/:username', name: 'user.profile')]
public function profile(string $username) {}
```

```php
$router->get('/user/profile/:username', [UserController::class, 'profile'])
       ->name('user.profile');
```

### Generating URLs

```php
$url = route('user.profile', ['username' => 'john']);
// /user/profile/john
```

### Redirecting to Named Routes

```php
return toRoute('user.profile', ['username' => 'john']);
// or
return response()->toRoute('user.profile', ['username' => 'john']);
```

---

## Default Parameter Values

Routes can define default values for parameters:

```php
#[Route(
    '/blog/:category/page/int:page|?',
    defaults: ['category' => 'general', 'page' => 1]
)]
public function blog(string $category, int $page) {}
```

```php
$router->get('/blog/:category/page/int:page|?', [BlogController::class, 'index'])
       ->defaults(['category' => 'general', 'page' => 1]);
```

---

## Redirects

Routes can redirect to:

* Another path
* A named route
* A controller action

### Attribute-Based Redirects

```php
#[Route('/old-path', toPath: '/new-path')]
#[Route('/profile', toRoute: 'user.profile')]
#[Route('/dashboard', toAction: 'DashboardController::index')]
```

### Fluent API Redirects

```php
$router->get('/old-path', null)->redirect('/new-path', 'path');
$router->get('/profile', null)->redirect('user.profile', 'route');
$router->get('/dashboard', null)->redirect('DashboardController::index', 'action');
```

---

## Defining Multiple Routes

Both routing styles are fully supported.

### Attribute-Based Routing

```php
#[Prefix('/admin')]
#[Middleware([AuthMiddleware::class])]
class UserController
{
    #[Route('/users', name: 'admin.users.index')]
    public function index() {}

    #[Route('/users/create', name: 'admin.users.create')]
    public function create() {}
}
```

### Fluent API Routing

```php
$router->group(['prefix' => '/admin', 'middleware' => 'auth'], function () use ($router) {
    $router->get('/users', [UserController::class, 'index'])
           ->name('admin.users.index');

    $router->get('/users/create', [UserController::class, 'create'])
           ->name('admin.users.create');
});
```

---

## Controller Parameter Type Handling

The framework automatically converts route parameters based on:

* The route’s type constraint
* The controller method’s type hint

```php
// Route: /user/edit/int:id
public function edit(int $id) {} // $id is an integer

// Route: /user/profile/:username
public function profile(string $username) {} // $username is a string

// Route: /blog/:category/page/int:page|?
public function blog(string $category, ?int $page = 1) {}
```

For optional parameters:

* Use nullable type hints (`?int`, `?string`)
* Provide default values when appropriate

---

## Usage Examples

```php
// Simple typed route
$router->get('/post/view/slug:title', [PostController::class, 'view']);

// Named route with defaults
$router->get('/blog/:category/page/int:page|?', [BlogController::class, 'index'])
       ->name('blog.index')
       ->defaults(['category' => 'general', 'page' => 1]);
```