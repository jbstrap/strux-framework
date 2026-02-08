---
title: Controllers
sidebar_position: 2
description: Handling HTTP requests using Web and API Controllers.
---

## Introduction

Controllers handle incoming HTTP requests and return responses.
They act as the glue between **routes**, **middleware**, **domain logic**, and **responses**.

:::info
Strux controllers leverage PHP 8 Attributes, dependency injection, and response helpers to stay concise and readable.
:::

---

## Key Features

- Attribute-based routing
- Automatic dependency injection
- Middleware and authorization attributes
- Typed request handling
- Response helpers (views, JSON, redirects, API responses)

---

## Controller Types

Strux provides two primary controller types:

| Type | Base Class | Purpose |
|-----|----------|--------|
| Web Controller | `Strux\Component\Http\Controller\Web\Controller` | HTML responses, sessions, redirects |
| API Controller | `Strux\Component\Http\Controller\Api\Controller` | JSON APIs, stateless requests |

Controllers are typically organized as:

```

App\Http\Controllers\Web
App\Http\Controllers\Api

````

---

## Creating a Controller

A basic web controller method:

```php
#[Route('/', methods: ['GET'])]
public function home(): Response
{
    return $this->view('home', ['title' => 'Welcome']);
}
````

All controllers extend the base `Controller` class and have access to:

* `$this->request`
* `$this->auth`
* `$this->flash`
* Response helpers

---

## Attribute-Based Routing

### Route Parameters

:::tip Recommended Syntax
Use colon-based parameters (`:id`) instead of legacy `{id}` syntax.
:::

```php
#[Route('/users/:id')]
public function show(int $id): Response {}
```

Parameters are automatically type-cast when possible.

---

### Optional Parameters

```php
#[Route('/blog/:category/page/int:page|?')]
public function blog(string $category, ?int $page = 1): Response {}
```

Optional parameters must have a default value.

---

## Middleware on Controllers

Middleware can be applied at **method**, **class**, or **global** level.

### Method-Level Middleware

```php
#[Middleware([AuthMiddleware::class])]
public function dashboard(): Response
{
    return $this->view('dashboard');
}
```

---

### Class-Level Middleware

```php
#[Middleware([AuthorizationMiddleware::class])]
class AdminController extends Controller
{
    // All methods are protected
}
```

---

## Request Handling

Controllers may receive the request explicitly or access it via `$this->request`.

```php
public function store(Request $request): Response
{
    $email = $request->input('email');
    return $this->redirect('/success');
}
```

---

## Response Types

### View Responses

```php
return $this->view('profile/index', ['user' => $user]);
```

---

### JSON Responses

```php
return $this->json(['status' => 'success']);
```

---

### Redirect Responses

```php
return $this->redirect('/login');
```

---

## API Controllers

API Controllers are optimized for **JSON APIs** and **stateless communication**.

They typically use:

* `#[ApiController]`
* `#[ApiRoute]`
* `ApiResponse`
* Content negotiation attributes

---

### API Controller Example

```php
#[ApiController]
#[Prefix('/api/tickets')]
#[Produces('application/json')]
#[Consumes('application/json')]
#[Middleware([ApiAuthMiddleware::class])]
class TicketController extends Controller
{
    #[ApiRoute('/', methods: ['GET'], name: 'api.tickets.index')]
    #[ResponseHeader('X-App-Version', '1.5.0')]
    public function index(): ApiResponse
    {
        $tickets = Ticket::query()
            ->with('status')
            ->with('priority')
            ->latest()
            ->limit(3)
            ->all();

        return $this->Ok($tickets);
    }
}
```

---

### Caching API Responses

```php
#[ApiRoute('/int:id', methods: ['GET'])]
#[Cache(ttl: 60)]
public function show(int $id): ApiResponse
{
    $ticket = Ticket::query()->find($id);

    if (!$ticket) {
        return $this->NotFound('Ticket not found.');
    }

    return $this->Ok($ticket);
}
```

---

### Request DTOs (`#[RequestBody]`)

API controllers can bind JSON payloads to typed request objects.

```php
#[ApiRoute('/', methods: ['POST'])]
#[ResponseStatus(201)]
public function store(
    #[RequestBody] TicketCreateRequest $request
): ApiResponse {
    $ticket = new Ticket();
    $ticket->subject = $request->subject;
    $ticket->description = $request->description;
    $ticket->save();

    return $this->Created($request, 'Ticket created successfully.');
}
```

---

## Validation Inside Controllers

Validation can be performed manually using the Validator component.

```php
$validator = new Validator($request->all());
$validator->add('email', [new Required(), new Email()]);

if (!$validator->isValid()) {
    return $this->UnprocessableEntity($validator->getErrors());
}
```

---

## Authentication API Example

```php
#[ApiController]
#[Prefix('/api/auth')]
#[Produces('application/json')]
#[Consumes('application/json')]
class AuthController extends Controller
{
    #[ApiRoute('/login', methods: ['POST'])]
    public function login(Request $request): ApiResponse
    {
        $validator = new Validator($request->all());
        $validator->add('email', [new Required(), new Email()]);
        $validator->add('password', [new Required()]);

        if (!$validator->isValid()) {
            return $this->UnprocessableEntity($validator->getErrors());
        }

        if (!$this->auth->sentinel('web')->validate($request->only(['email', 'password']))) {
            return $this->Unauthorized('Invalid credentials.');
        }

        $user = $this->auth->sentinel('web')->user();
        $token = $user->createToken();

        return $this->Ok([
            'token' => $token,
            'user' => $user->toArray(),
        ]);
    }
}
```

---

## Invokable Controllers

Controllers may define a single `__invoke()` method.

```php
class TestInvokeMethodController extends Controller
{
    public function __invoke(): Response
    {
        return $this->json([
            'message' => 'This is a test response from the invoke method.',
            'status' => 'success',
        ]);
    }
}
```

Invokable controllers are useful for:

* Single-purpose endpoints
* Simple callbacks
* Lightweight routes

---

## Large Web Controller Example

Complex web controllers often combine:

* Authorization
* Repository injection
* Validation
* File uploads
* Redirects and flash messages

```php
#[Prefix('/admin')]
#[Middleware([AuthorizationMiddleware::class])]
#[Authorize(roles: ['admin'])]
class AdminTicketController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly CommentRepositoryInterface $commentRepo
    ) {
        parent::__construct();
    }

    #[Route('/tickets')]
    public function index(): Response
    {
        return $this->view('account/index', [
            'tickets' => $this->ticketRepo->listRecent(10),
        ]);
    }
}
```

This pattern is recommended for **admin panels and dashboards**.

---

## Best Practices

* Keep controllers thin; move logic to services or repositories
* Prefer constructor injection over container access
* Use request DTOs for complex API payloads
* Avoid heavy ORM logic inside controllers
* Use attributes consistently for routing, auth, and middleware

---

## Summary

Controllers in Strux provide:

* Clear separation between Web and API concerns
* Attribute-driven configuration
* First-class dependency injection
* Flexible response handling

They are designed to scale from simple endpoints to large, real-world applications without losing clarity.