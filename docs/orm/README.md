Below is a polished **README.md** generated from the content you provided, structured, clarified, and formatted for
direct use in your repository.

---

# Strux ORM

**Strux ORM** is a modern, lightweight **Active Record Object-Relational Mapper (ORM)** for **PHP 8.1+**, designed
around **PHP Attributes** for clean, declarative database modeling.

Inspired by frameworks like **Laravel Eloquent**, Strux ORM emphasizes **clarity**, **simplicity**, and **developer
ergonomics**, while avoiding heavy configuration files or boilerplate.

---

## 📑 Table of Contents

* [Overview](#overview)
* [Key Concepts](#key-concepts)
* [Requirements](#requirements)
* [Defining Models](#defining-models)
* [Attributes Reference](#attributes-reference)

    * [Table & Column Mapping](#table--column-mapping)
    * [Column Options](#column-options)
    * [Model Behavior Attributes](#model-behavior-attributes)
* [Relationships](#relationships)
* [CRUD Operations](#crud-operations)
* [Query Builder](#query-builder)

    * [Basic Wheres](#basic-wheres)
    * [Advanced Wheres & Grouping](#advanced-wheres--grouping)
    * [Subqueries](#subqueries)
    * [Raw Expressions](#raw-expressions)
* [Soft Deletes](#soft-deletes)
* [Best Practices](#best-practices)
* [Limitations](#limitations)
* [License](#license)

---

## Overview

Strux ORM follows the **Active Record pattern**, where:

* Each database table maps to a PHP **model class**
* Each row maps to a **model instance**
* Models contain both **data** and **persistence logic**

Unlike traditional ORMs that rely on configuration arrays or XML/YAML mappings, Strux ORM uses **native PHP 8 Attributes
**, resulting in a **type-safe**, expressive, and maintainable developer experience.

---

## Key Concepts

* **Models = Tables**
* **Properties = Columns**
* **Attributes = Mapping Metadata**
* **Relationships defined on properties**, not methods
* **Magic relationship resolution** via `__call()`

---

## Requirements

* **PHP 8.1 or higher**
* **PDO-enabled** database connection
* Compatible SQL database:

    * MySQL
    * PostgreSQL
    * SQLite
    * Others supported by PDO

---

## Defining Models

Models are defined in:

```
src/Domain/{Domain}/Entity
```

They must extend:

```php
Strux\Component\Model\Model
```

Attributes are used to map properties to database columns and relationships.

### Comprehensive Model Example

```php
<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Entity;

use DateTime;
use Strux\Component\Database\Attributes\Column;
use Strux\Component\Database\Attributes\Id;
use Strux\Component\Database\Attributes\OrderBy;
use Strux\Component\Database\Attributes\SoftDelete;
use Strux\Component\Database\Attributes\Table;
use Strux\Component\Database\Types\Field;
use Strux\Component\Database\Types\KeyAction;
use Strux\Component\Model\Attributes\BelongsTo;
use Strux\Component\Model\Attributes\HasMany;
use Strux\Component\Model\Model;
use Strux\Support\Collection;

#[Table(name: 'tickets')]
#[SoftDelete(column: 'deletedAt')]
class Ticket extends Model
{
    #[Id, Column(type: Field::bigInteger)]
    public ?int $ticketID = null;

    #[Column(type: Field::bigInteger, nullable: true)]
    public ?int $customerID = null;

    #[Column(type: Field::string, length: 255)]
    public string $subject;

    #[Column(type: Field::text)]
    public ?string $description = null;

    #[Column(type: Field::bigInteger, nullable: true)]
    public ?int $statusID = null;

    #[Column(type: Field::timestamp, nullable: true, currentTimestamp: true)]
    public ?DateTime $createdAt;

    #[Column(
        type: Field::timestamp,
        nullable: true,
        currentTimestamp: true,
        onUpdateCurrentTimestamp: true
    )]
    public ?DateTime $updatedAt = null;

    // Relationships

    #[HasMany(related: TicketComment::class, foreignKey: 'ticketID', localKey: 'ticketID')]
    #[OrderBy('createdAt', 'DESC')]
    public Collection $comments;

    #[BelongsTo(
        related: Department::class,
        foreignKey: 'departmentID',
        ownerKey: 'departmentID',
        onDelete: KeyAction::CASCADE
    )]
    public ?Department $department = null;

    #[BelongsTo(
        related: Agent::class,
        foreignKey: 'assignedTo',
        ownerKey: 'agentID',
        onDelete: KeyAction::SET_NULL
    )]
    public ?Agent $agent = null;

    public function __construct(array $attributes = [])
    {
        $this->comments = new Collection();
        parent::__construct($attributes);
    }
}
```

---

## Attributes Reference

### Table & Column Mapping

| Attribute    | Purpose                            | Example                            |
|--------------|------------------------------------|------------------------------------|
| `#[Table]`   | Binds model to a database table    | `#[Table('users')]`                |
| `#[Column]`  | Maps property to a column          | `#[Column(type: Field::string)]`   |
| `#[Id]`      | Marks primary key                  | `#[Id]`                            |
| `#[OrderBy]` | Default ordering for relationships | `#[OrderBy('created_at', 'DESC')]` |

---

### Column Options

The `#[Column]` attribute supports:

* `name` – Override column name
* `type` – `Field` enum (`string`, `text`, `json`, `timestamp`, etc.)
* `length` – For VARCHAR / CHAR
* `nullable` – Boolean
* `unique` – Adds unique index
* `currentTimestamp` – DEFAULT CURRENT_TIMESTAMP
* `onUpdateCurrentTimestamp` – Auto-update timestamp

---

### Model Behavior Attributes

| Attribute       | Behavior                              |
|-----------------|---------------------------------------|
| `#[Timestamps]` | Manages `created_at` and `updated_at` |
| `#[SoftDelete]` | Enables soft deletes                  |

---

## Relationships

Relationships are defined **on properties**, not methods.

### One-to-Many (HasMany)

```php
#[HasMany(related: TicketComment::class, foreignKey: 'ticketID')]
public Collection $comments;
```

### Many-to-One (BelongsTo)

```php
#[BelongsTo(related: Customer::class, foreignKey: 'customerID')]
public ?Customer $customer = null;
```

### Many-to-Many (BelongsToMany)

```php
#[BelongsToMany(
    related: Tag::class,
    pivotTable: 'ticket_tags',
    foreignPivotKey: 'ticket_id',
    relatedPivotKey: 'tag_id'
)]
public Collection $tags;
```

---

### Accessing Relationships

**Lazy Loading (property access):**

```php
$ticket = Ticket::find(1);

foreach ($ticket->comments as $comment) {
    // ...
}
```

**Eager Loading (method access):**

```php
$tickets = Ticket::query()
    ->with('comments', 'agent')
    ->get();
```

---

## CRUD Operations

### Create

```php
$ticket = new Ticket();
$ticket->subject = "Login Issue";
$ticket->save();
```

```php
$ticket = Ticket::create([
    'subject' => 'Payment Error',
    'customerID' => 123
]);
```

### Read

```php
Ticket::find(1);
Ticket::findOrFail(1);
Ticket::query()->all();
```

### Update

```php
$ticket = Ticket::find(1);
$ticket->statusID = 2;
$ticket->save();
```

### Delete

```php
$ticket->delete();       // Soft delete
$ticket->forceDelete(); // Hard delete

Ticket::destroy([1, 2, 3]);
```

---

## Query Builder

Strux provides a fluent, expressive query builder.

### Basic Wheres

```php
$query->where('statusID', 1);
$query->where('priority', '>', 5);
$query->where('subject', 'LIKE', '%Error%');
$query->whereIn('id', [1, 2, 3]);
```

---

### Advanced Wheres & Grouping

```php
$query->where('department_id', 5)
      ->where(function ($q) {
          $q->where('status', 'Open')
            ->orWhere('priority', 'Critical');
      });
```

```php
$query->whereAny(
    ['subject', 'description', 'customer_notes'],
    'LIKE',
    '%urgent%'
);
```

Case-sensitive LIKE:

```php
$query->whereLike('license_code', 'ABC-123', caseSensitive: true);
```

---

### Subqueries

```php
$query->where('priority', '>', function ($sub) {
    $sub->selectRaw('AVG(priority)')
        ->from('tickets');
});
```

```php
$query->whereIn('assignedTo', function ($sub) {
    $sub->select('agentID')
        ->from('agents')
        ->where('department', 'Support');
});
```

---

### Raw Expressions

```php
$query->selectRaw('count(*) as count, statusID')
      ->groupBy('statusID')
      ->whereRaw('created_at > ?', ['2023-01-01']);
```

---

## Soft Deletes

When `#[SoftDelete]` is enabled:

```php
Ticket::withTrashed()->get();
Ticket::onlyTrashed()->get();

$ticket->restore();
```

---

## Best Practices

* **Strict Typing:** Always type your properties (`?int`, `string`, `DateTime`). This ensures the ORM casts data
  correctly.
* **Eager Loading:** Prevent N+1 issues by using `with()` when iterating over collections.

```php
$tickets = Ticket::query()->with('comments.author')->get();
```

* **Indexing:** Use `#[Unique]` or manual migrations for foreign keys and frequently searched columns.
* Use `exists()`: For checking existence efficiently (`SELECT 1 ... LIMIT 1`):

```php
$exists = Ticket::query()->where('id', 1)->exists();
```

---

## Limitations

* ❌ Composite primary keys (not supported)
* ❌ Polymorphic relationships (Planned for future release)

---

## License

**MIT License**