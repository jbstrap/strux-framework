<?php

declare(strict_types=1);

namespace Strux\Auth\Entity;

use App\Domain\Identity\Entity\Permissions;
use Strux\Component\Database\ORM\Model;
use Strux\Component\Database\Schema\Attributes\Column;
use Strux\Component\Database\Schema\Attributes\Id;
use Strux\Component\Database\Schema\Attributes\Unique;
use Strux\Component\Database\Schema\Attributes\Entity;
use Strux\Component\Database\ORM\Attributes\OwnedByMany;
use Strux\Support\Collection;

#[Entity(table: 'roles')]
class Role extends Model
{
	#[Id, Column]
	public ?int $id = null;

	#[Column]
	public string $name;

	#[Column, Unique]
	public string $slug;

	#[Column(nullable: true)]
	public ?string $description = null;

	#[OwnedByMany(
		related: User::class,
		pivotTable: 'roles_users',
		foreignPivotKey: 'roles_id',
		relatedPivotKey: 'users_id'
	)]
	/** @var Collection<User> */
	public Collection $users;

	#[OwnedByMany(
		related: Permission::class,
		pivotTable: 'permissions_roles',
		foreignPivotKey: 'roles_id',
		relatedPivotKey: 'permissions_id'
	)]
	/** @var Collection<Permissions> */
	public Collection $permissions;
}
