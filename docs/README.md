# Strux Framework

A modern, attribute-driven PHP framework built around a **Model-First**, **code-first ORM** philosophy with automatic
migration generation.

---

## Introduction

**Strux** is a custom PHP framework targeting **PHP 8.1+** that leverages **PHP Attributes**, **strict typing**, and *
*Reflection** to provide a clean, expressive, and developer-friendly experience.

At its core, Strux is an **Active Record ORM** combined with an **auto-migration engine**. Instead of writing migrations
first, you define your database schema directly in your PHP model classes, and Strux intelligently generates migrations
by diffing your models against the actual database schema.

**Define models → Generate migrations → Stay in sync.**

---

## Table of Contents

- [Features](#features)
- [Architecture Overview](#architecture-overview)
    - [ORM](#orm-object-relational-mapping)
    - [Auto-Migration Engine](#auto-migration-engine)
    - [Database Layer](#database-layer)
    - [CLI & Generators](#cli--generators)
- [Usage](#usage)
    - [Defining Models](#defining-models)
    - [Relationships](#relationships)
- [Design Philosophy](#design-philosophy)
- [Code Quality & Security](#code-quality--security)
- [Known Limitations & Considerations](#known-limitations--considerations)
- [Requirements](#requirements)
- [License](#license)

---

## Features

- ✅ PHP 8.2+ with strict typing
- ✅ Attribute-based ORM schema definitions
- ✅ Active Record pattern
- ✅ Automatic migration generation via schema diffing
- ✅ Intelligent handling of foreign keys and pivot tables
- ✅ Fluent query builder with prepared statements
- ✅ Built-in CLI with code generators
- ✅ Domain-Driven Design (DDD) support
- ✅ MySQL and SQLite connection support

---

## Architecture Overview

### ORM (Object-Relational Mapping)

Strux’s ORM is built around the **Active Record** pattern: models represent database rows and encapsulate both data and
persistence logic.

#### Attribute-Driven Schema

Database schema is declared directly on model properties using PHP Attributes:

```php
#[Column(type: Field::string, length: 150)]
public string $name;
````

This keeps schema definitions close to the domain logic and eliminates external mapping files.

#### Relationships

Relationships are defined as **typed properties** with relationship attributes:

```php
#[BelongsToMany(related: Roles::class)]
public Collection $roles;
```

Relationship resolution is handled by the `HasRelationships` trait using `__call`. Calling:

```php
$user->roles()
```

returns a relationship-aware query builder by reflecting on the `$roles` property and its attributes.

#### Modular Traits

The base `Model` class remains lightweight by delegating responsibilities to traits:

* **HasAttributes**
  Handles hydration, dirty checking, and attribute reflection (with caching).
* **HasQueryBuilder**
  Provides a fluent interface (`where`, `select`, `join`, `get`, etc.).
* **HasRelationships**
  Manages eager loading and dynamic relationship resolution.

---

### Auto-Migration Engine

The auto-migration system is one of Strux’s most powerful features.

#### How It Works

* Scans the `src/Domain` directory for classes annotated with `#[Table]`
* Reflects on model attributes (`#[Column]`, `#[Id]`, `#[BelongsTo]`, etc.)
* Compares model definitions against the live database schema
* Generates migration files based on detected differences

#### Supported Detection

* New tables
* New, modified, or removed columns
* Foreign key constraints
* Pivot tables for `BelongsToMany` relationships

#### Safety Measures

* Destructive operations (e.g. `DROP COLUMN`) are commented out by default
* Column renames are supported via the `#[RenamedFrom]` attribute to avoid accidental data loss

---

### Database Layer

* Uses **PDO** directly for reliability and performance
* Wrapped in a `Database` abstraction handling connections and drivers
* Supports:

    * MySQL
    * SQLite (connection-level support)

#### Query Builder

The fluent query builder supports:

```php
User::query()
    ->where('email', '=', $email)
    ->join('profiles', ...)
    ->get();
```

* Prepared statements
* Parameter binding
* Nested boolean logic
* Aggregates and joins

---

### CLI & Generators

Strux includes a built-in CLI for developer productivity.

#### Generators

Scaffold generators are available for:

* Controllers
* Entities / Models
* Jobs
* Events & Listeners
* Middleware
* Modules

#### DDD Support

Generators can follow a **Domain-Driven Design** structure:

```
src/
 └── Domain/
     └── User/
         ├── User.php
         ├── UserRepository.php
         └── ...
```

This behavior is configurable via `app.mode`.

---

## Usage

### Defining Models

Models are defined using attributes for both schema and behavior:

```php
#[Table(name: 'users')]
class User extends Model
{
    #[Id]
    #[Column(type: Field::int)]
    public int $id;

    #[Column(type: Field::string, length: 150)]
    public string $email;
}
```

Running the migration generator will automatically create or update the corresponding database table.

---

### Relationships

Example `BelongsToMany` relationship:

```php
#[BelongsToMany(related: Role::class)]
public Collection $roles;
```

Access via:

```php
$user->roles()->get();
```

---

## Design Philosophy

* **Model-First Development**
  Models are the source of truth for both schema and behavior.
* **Explicit Over Implicit**
  Attributes make configuration discoverable and type-safe.
* **Modern PHP**
  Enums, strict typing, attributes, and PSR-compliant structure.
* **Developer Control**
  Generated code is readable, editable, and safe by default.

---

## Code Quality & Security

* **Strict typing** across properties and methods
* **Enums** (`Field`, `KeyAction`) eliminate magic strings
* **Reflection caching** minimizes runtime overhead
* **Prepared statements** protect against SQL injection
* Clean, modular architecture with clear separation of concerns

---

## Known Limitations & Considerations

* **Uninitialized Typed Relationship Properties**
  Accessing a public relationship property before loading may cause errors. Prefer calling the relationship
  method (`$user->roles()`) or ensure the property is initialized.
* **Column Renames**
  Renaming a property without `#[RenamedFrom]` is treated as drop + add. Use `#[RenamedFrom]` to preserve data.
* **Complex Query Logic**
  Deeply nested `where` closures and advanced join logic may become difficult as query complexity grows.
* **SQLite Migrations**
  While SQLite connections are supported, the migration generator is currently optimized for MySQL schema introspection.

---

## Requirements

* PHP **8.2+**
* PDO extension
* MySQL or SQLite database

---

## License

This framework is currently **custom / proprietary**.
Add a license file if you plan to open-source or distribute it.

---

**Strux** provides a strong, modern foundation for building PHP applications with minimal boilerplate and maximum
clarity.

Happy coding!