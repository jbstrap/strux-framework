# Queue System & Database Migrations

A robust, database-driven **Queue System** and a powerful **Code-First Migration Framework** for PHP applications.
This system improves performance, developer workflow, and schema consistency across environments.

---

## Table of Contents

* [Queue System](#queue-system)

    * [Introduction](#introduction)
    * [Getting Started](#getting-started)
    * [Dispatching Jobs](#dispatching-jobs)
    * [Running the Worker](#running-the-worker)
    * [Configuration](#configuration)
    * [Architecture & Lifecycle](#architecture--lifecycle)
    * [Best Practices](#best-practices)
* [Database Migrations & Schema Management](#database-migrations--schema-management)

    * [Key Features](#key-features)
    * [CLI Commands](#cli-commands)
    * [Defining Models](#defining-models)
    * [Available Attributes](#available-attributes)
    * [Relationships & Foreign Keys](#relationships--foreign-keys)
    * [Migration Workflow](#migration-workflow)
    * [Resetting Development DB](#resetting-development-db)
* [License](#license)

---

# Queue System

## Introduction

This framework includes a **database-driven queue system** for offloading long-running tasks such as sending emails,
processing file uploads, or generating reports.
By moving these operations into background workers, applications respond to user requests significantly faster.

---

## Getting Started

### 1. Initialize the Queue Table

Create the queue table used to store pending jobs:

```bash
php console queue:init
```

This generates a `_jobs` table (or whichever name is defined in `config/queue.php`).

---

### 2. Create a Job Class

Generate a new Job via CLI:

```bash
php console new:job SendWelcomeEmailJob
```

**Example:**

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\Queue\Job;
use App\Core\Mail\MailerInterface;
use App\Models\User;

class SendWelcomeEmailJob extends Job
{
    public function __construct(
        protected User $user
    ) {}

    public function handle(MailerInterface $mailer): void
    {
        $mailer->to($this->user->email)
               ->subject('Welcome!')
               ->send('emails/welcome', ['name' => $this->user->firstname]);
    }
}
```

---

### 3. Dispatch the Job

```php
use App\Jobs\SendWelcomeEmailJob;
use App\Core\Queue\Queue;

$queue = container(Queue::class);

// Dispatch immediately
$queue->push(new SendWelcomeEmailJob($user));

// Dispatch with delay (5 minutes)
$queue->later(300, new SendWelcomeEmailJob($user));
```

---

## Running the Worker

```bash
php console queue:start
```

For production, run this under a process monitor like **Supervisor**, **systemd**, or **PM2**.

---

## Configuration

Queue config (`config/queue.php`):

```php
return [
    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'database'),
        'connections' => [
            'database' => [
                'driver' => 'database',
                'table' => '_jobs',
                'queue' => 'default',
                'retry_after' => 90,
            ],
        ],
    ]
];
```

---

## Architecture & Lifecycle

**Dispatch:**
`Queue::push()` serializes the job and stores it in the `_jobs` table.

**Polling:**
`queue:start` continuously scans for available jobs (`reserved_at = NULL`, `available_at <= NOW()`).

**Locking:**
When a worker picks a job, `reserved_at` is set to lock it.

**Execution:**
The job is deserialized and its `handle()` method is executed with auto-injected dependencies.

**Completion:**
Successful jobs are removed from the database.

**Failure:**

* The job is released and `attempts` increments.
* If max attempts is exceeded, the job can be moved to `failed_jobs`.

---

## Best Practices

* **Keep Jobs Lightweight:** Pass IDs or simple DTOs; load heavy models inside `handle()`.
* **Use Dependency Injection:** Inject Mailers, Repositories, etc. through `handle()`, not the constructor.
* **Idempotency:** Ensure jobs are safe to run multiple times in case of retries.

---

# Database Migrations & Schema Management

A powerful, **code-first migration system** inspired by Prisma and TypeORM, but fully PHP-native.

---

## Key Features

* **Code-First Schema:** Define schema using PHP attributes on Models.
* **Automatic Migrations:** `db:migrate` generates diff-based SQL.
* **Smart Type Inference:** PHP types map to SQL types automatically.
* **Automatic Pivot Tables:** Created for Many-to-Many relations.
* **Safe Renaming:** Use `#[RenamedFrom]` without data loss.
* **Foreign Key Management:** Automatically handled from relationships.

---

## CLI Commands

| Command        | Description                                        | Example                                           |
|----------------|----------------------------------------------------|---------------------------------------------------|
| `db:init`      | Initializes migration system                       | `php console db:init`                             |
| `db:migrate`   | Generates migration based on code diff             | `php console db:migrate`                          |
| —              | —                                                  | `php console db:migrate --m=User --n="add_phone"` |
| `db:upgrade`   | Applies pending migrations                         | `php console db:upgrade`                          |
| `db:downgrade` | Reverts last batch                                 | `php console db:downgrade`                        |
| `db:reset`     | ⚠ Drops all tables and re-runs migrations          | `php console db:reset`                            |
| `db:fresh`     | ⚠ Drops DB, deletes migrations, regenerates schema | `php console db:fresh`                            |
| `db:history`   | Shows applied migrations                           | `php console db:history`                          |
| `db:seed`      | Runs seeders                                       | `php console db:seed`                             |

---

## Defining Models

Models are the **single source of truth** for your database.

```php
namespace App\Models;

use App\Core\Database\Attributes\Table;
use App\Core\Database\Attributes\Id;
use App\Core\Database\Attributes\Column;
use App\Core\Database\Types\Field;
use App\Core\Model\Model;
use DateTime;

#[Table('users')]
class User extends Model
{
    #[Id, Column]
    public ?int $id = null;

    #[Column]
    public string $username;

    #[Column(type: Field::text, nullable: true)]
    public ?string $bio = null;

    #[Column(type: Field::enum, enums: ['active', 'banned'], default: 'active')]
    public string $status = 'active';

    #[Column(type: Field::timestamp, currentTimestamp: true)]
    public DateTime $createdAt;

    #[Column(type: Field::timestamp, currentTimestamp: true, onUpdateCurrentTimestamp: true)]
    public DateTime $updatedAt;
}
```

---

## Available Attributes

### `#[Table(name: string)]`

Defines the database table name.

### `#[Id(autoincrement: bool = true)]`

Marks a property as the primary key.

### `#[Column(...)]`

Options:

* `name`
* `type`
* `length`
* `nullable`
* `unique`
* `default`
* `currentTimestamp`
* `onUpdateCurrentTimestamp`

### `#[RenamedFrom(oldName: string)]`

For renaming columns without losing data.

---

## Relationships & Foreign Keys

### 1. One-to-Many / Many-to-One – `#[BelongsTo]`

```php
#[BelongsTo(User::class, onDelete: 'CASCADE')]
public User $user;
```

This generates:

* `user_id` column
* Foreign key constraint with cascade rules

---

### 2. Many-to-Many – `#[BelongsToMany]`

```php
#[BelongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')]
public Collection $roles;
```

Automatically creates pivot table:

| Column  | Type   |
|---------|--------|
| user_id | BIGINT |
| role_id | BIGINT |

With foreign keys referencing their parent tables.

---

## Migration Workflow

1. **Create/Edit Model**

   ```bash
   php console new:model Post
   ```

2. **Generate Migration**

   ```bash
   php console db:migrate
   ```

3. **Review** generated SQL.
   Destructive operations are **commented out** by default.

4. **Apply Migrations**

   ```bash
   php console db:upgrade
   ```

---

## Resetting Development DB

When the schema becomes messy during development:

```bash
php console db:fresh
```

This:

* Drops the database
* Deletes migration files
* Rebuilds a clean schema matching your current models