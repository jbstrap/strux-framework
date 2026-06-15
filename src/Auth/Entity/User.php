<?php

declare(strict_types=1);

namespace Strux\Auth\Entity;

use Strux\Component\Database\ORM\Model;
use Strux\Component\Database\Schema\Attributes\Column;
use Strux\Component\Database\Schema\Attributes\Id;
use Strux\Component\Database\Schema\Attributes\Unique;
use Strux\Component\Database\Schema\Types\Field;
use Strux\Component\Database\ORM\Attributes\Reformat;
use Strux\Component\Database\ORM\Attributes\Hidden;
use Strux\Component\Database\Schema\Attributes\Entity;
use Strux\Component\Database\ORM\Attributes\OwnedByMany;
use Strux\Support\Collection;

#[Entity(table: 'users')]
class User extends Model
{
    #[Id(autoincrement: false, autoGenerate: 'uuid')]
    #[Column(type: Field::uuid)]
    public string $id = '';

    #[Column]
    #[Reformat(get: 'ucwords')]
    public ?string $name = null;

    #[Unique]
    #[Column]
    #[Reformat(get: 'strtolower')]
    public ?string $email = null;

    #[Column]
    #[Hidden]
    public ?string $password = null;

    #[Column(type: Field::dateTime, nullable: true)]
    #[Hidden]
    public ?\DateTimeInterface $email_verified_at = null;

    #[Column(type: Field::dateTime, nullable: true)]
    #[Hidden]
    public ?\DateTimeInterface $last_login_at = null;

    #[Column(nullable: true)]
    public ?string $remember_token = null;

    #[OwnedByMany(
        related: Role::class,
        pivotTable: 'roles_users',
        foreignPivotKey: 'users_id',
        relatedPivotKey: 'roles_id'
    )]
    /** @var Collection<Role> */
    public Collection $roles;
}
