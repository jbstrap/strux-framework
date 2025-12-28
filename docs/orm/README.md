# Strux ORM

Strux ORM is a modern, lightweight **Active Record Object-Relational Mapper** for PHP 8+, designed with **PHP Attributes** for clean, declarative database modeling. It provides expressive relationships, a fluent query builder, and robust relationship management without heavy configuration.

Inspired by frameworks like Laravel Eloquent, Strux ORM emphasizes **clarity, simplicity, and developer ergonomics**.

---

## 📑 Table of Contents

* [Overview](#overview)
* [Key Concepts](#key-concepts)
* [Requirements](#requirements)
* [Defining Models](#defining-models)
* [Attributes Reference](#attributes-reference)
* [Primary Keys & Timestamps](#primary-keys--timestamps)
* [Relationships](#relationships)
* [Accessing Relationships](#accessing-relationships)
* [CRUD Operations](#crud-operations)
* [Managing Relationships](#managing-relationships)
* [Query Builder](#query-builder)
* [Soft Deletes](#soft-deletes)
* [Best Practices](#best-practices)
* [Limitations](#limitations)
* [License](#license)

---

## Overview

Strux ORM follows the **Active Record pattern**, where:

* Each database table maps to a PHP model class
* Each row maps to a model instance
* Models contain both data and persistence logic

Unlike traditional ORMs that rely on configuration arrays or YAML/XML mappings, Strux ORM uses **native PHP 8 Attributes** for a clean and type-safe developer experience.

---

## Key Concepts

* **Models = Tables**
* **Properties = Columns**
* **Attributes = Mapping Metadata**
* **Relations defined on properties, not methods**
* **Magic relationship resolution via `__call()`**

---

## Requirements

* PHP **8.0 or higher**
* PDO-enabled database connection
* Compatible SQL database (MySQL, PostgreSQL, SQLite, etc.)

---

## Defining Models

All ORM models must extend:

```php
Strux\Component\Model\Model
```

### Basic Model Example

```php
<?php

namespace App\Model;

use Strux\Component\Model\Model;
use Strux\Component\Database\Attributes\Table;
use Strux\Component\Database\Attributes\Id;
use Strux\Component\Database\Attributes\Column;
use Strux\Component\Database\Attributes\Timestamps;

#[Table('students')]
#[Timestamps]
class Student extends Model
{
    #[Id]
    #[Column('student_number')]
    public int $student_number;

    #[Column('first_name')]
    public string $first_name;

    #[Column('email')]
    public string $email;
}
```

---

## Attributes Reference

### Table & Column Mapping

| Attribute                  | Purpose                 |
| -------------------------- | ----------------------- |
| `#[Table('table_name')]`   | Binds model to a table  |
| `#[Column('column_name')]` | Maps property to column |
| `#[Id]` / `#[PrimaryKey]`  | Marks primary key       |

### Model Behavior

| Attribute        | Behavior                            |
| ---------------- | ----------------------------------- |
| `#[Timestamps]`  | Manages `created_at`, `updated_at`  |
| `#[SoftDeletes]` | Enables soft deletes (`deleted_at`) |

---

## Primary Keys & Timestamps

* Primary keys can be named freely
* Composite keys are not supported
* Timestamps require `created_at` and `updated_at` columns
* Values are automatically maintained during `save()`

---

## Relationships

Relationships are declared using **property attributes**, a core design feature of Strux ORM.

### Supported Relationship Types

* `HasMany`
* `BelongsTo`
* `BelongsToMany`

### Relationship Definitions

```php
use Strux\Component\Model\Attributes\HasMany;
use Strux\Component\Model\Attributes\BelongsTo;
use Strux\Component\Model\Attributes\BelongsToMany;
use Strux\Support\Collection;

class Student extends Model
{
    #[BelongsToMany(
        related: Course::class,
        pivotTable: 'enrollments',
        foreignPivotKey: 'student_number',
        relatedPivotKey: 'course_id'
    )]
    public Course|Collection $courses;
}

class Course extends Model
{
    #[BelongsTo(related: Instructor::class, foreignKey: 'instructor_id')]
    protected Instructor $instructor;
}

class Instructor extends Model
{
    #[HasMany(related: Course::class, foreignKey: 'instructor_id')]
    protected Course|Collection $courses;
}
```

---

## Accessing Relationships

### Property Access (Lazy Loading)

```php
$student = Student::find(1);

$courses = $student->courses;

foreach ($courses as $course) {
    echo $course->course_name;
}
```

Accessing a relationship as a property executes the query automatically.

---

### Method Access (Relation Builder)

```php
$student->courses()->sync([1, 2, 3]);
$student->courses()->attach(5);
$student->courses()->detach();
```

Calling the relationship as a method returns a **Relation Builder**.

> ⚡ **Important**
> You do **not** need to define the method.
> Strux resolves it dynamically using reflection and attributes.

#### Optional IDE Support

```php
public function courses()
{
    return $this->belongsToMany(
        Course::class,
        'enrollments',
        'student_number',
        'course_id'
    );
}
```

---

## CRUD Operations

### Create

```php
$student = Student::create([
    'first_name' => 'Jane',
    'email' => 'jane@example.com'
]);
```

```php
$student = new Student();
$student->first_name = 'John';
$student->save();
```

---

### Read

```php
$student = Student::find(1);
$students = Student::all();
```

---

### Update

```php
$student = Student::find(1);
$student->first_name = 'Updated';
$student->save();
```

---

### Delete

```php
$student->delete();
```

```php
Student::destroy(1);
Student::destroy([1, 2, 3]);
```

---

## Managing Relationships

### Many-to-Many (`BelongsToMany`)

```php
$student->courses()->attach(3);
$student->courses()->detach(3);
$student->courses()->sync([1, 2, 4]);
```

---

### One-to-Many (`HasMany`)

```php
$instructor->courses()->create([
    'course_name' => 'Advanced PHP',
    'course_number' => 4001
]);
```

```php
$instructor->courses()->createMany([
    ['course_name' => 'PHP 101'],
    ['course_name' => 'OOP in PHP']
]);
```

---

## Query Builder

Every model includes a fluent query builder.

```php
Student::query()
    ->where('age', '>', 18)
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();
```

### Fetching Results

```php
->get();        // Collection
->first();      // Model|null
->firstOrFail(); // Throws exception
```

---

## Soft Deletes

Enable soft deletes with:

```php
#[SoftDeletes]
```

Requirements:

* `deleted_at` column
* Deleted records are excluded from queries by default

---

## Best Practices

* Use **typed properties** for all columns
* Keep models focused on domain logic
* Define explicit methods only for IDE support
* Prefer relationship property access for reads
* Prefer relationship method access for writes

---

## Limitations

* No composite primary keys
* No eager loading (planned)
* No polymorphic relations (planned)

---

## License

MIT License