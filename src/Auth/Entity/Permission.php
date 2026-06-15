<?php

declare(strict_types=1);

namespace Strux\Auth\Entity;

use Strux\Component\Database\ORM\Model;
use Strux\Component\Database\Schema\Attributes\Column;
use Strux\Component\Database\Schema\Attributes\Id;
use Strux\Component\Database\Schema\Attributes\Unique;
use Strux\Component\Database\Schema\Attributes\Entity;
use Strux\Component\Database\ORM\Attributes\OwnedByMany;
use Strux\Support\Collection;

#[Entity(table: 'permissions')]
class Permission extends Model
{
    #[Id, Column]
    public ?int $id = null;

    #[Column]
    public string $name;

    #[Column, Unique]
    public string $slug;

    #[OwnedByMany(
        related: Role::class,
        pivotTable: 'permissions_roles',
        foreignPivotKey: 'permissions_id',
        relatedPivotKey: 'roles_id'
    )]
    /** @var Collection<Role> */
    public Collection $roles;
}
