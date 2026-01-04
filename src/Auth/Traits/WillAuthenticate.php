<?php

declare(strict_types=1);

namespace Strux\Auth\Traits;

use App\Domain\Identity\Entity\Permission;
use App\Domain\Identity\Entity\Role;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Strux\Auth\JwtService;
use Strux\Component\Exceptions\Container\ContainerException;
use Strux\Support\ContainerBridge;

trait WillAuthenticate
{
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password ?? '');
    }

    public function setPassword(mixed $password): void
    {
        if (is_string($password)) {
            $this->password = password_hash($password, PASSWORD_DEFAULT);
        } elseif (is_array($password) && isset($password['password'])) {
            $this->password = password_hash($password['password'], PASSWORD_DEFAULT);
        } else {
            throw new InvalidArgumentException('Password must be a string or an array with a "password" key.');
        }
    }

    /**
     * Create a new JWT for the user.
     */
    public function createToken(): string
    {
        try {
            /** @var JwtService $jwtService */
            $jwtService = ContainerBridge::resolve(JwtService::class);
            return $jwtService->generateToken($this);
        } catch (ContainerException|NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            throw new InvalidArgumentException("Failed to resolve JWT Service: " . $e->getMessage(), 0, $e);
        }
    }

    public function getAuthIdentifier()
    {
        $pk = $this->getPrimaryKey();
        return $this->{$pk};
    }

    /**
     * Check if user has a specific role (string or array).
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        // Ensure roles are loaded
        if ($this->roles->isEmpty()) {
            // We access the property via __get to trigger lazy loading if not set
            // or manually load it if __get doesn't handle empty collections well
            $this->roles = $this->__get('roles');
            // If __get returns a Collection, we use it.
            // If relation was empty in DB, it returns empty Collection.
        }

        // Fallback for string-based 'role' column (backward compatibility)
        if (in_array($this->role, $roles)) {
            return true;
        }

        /** @var Role $role */
        foreach ($this->roles as $role) {
            if (in_array($role->slug, $roles) || in_array($role->name, $roles)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has a specific permission (via roles).
     */
    public function hasPermission(string|array $permissions): bool
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];

        // 1. Ensure Roles are loaded
        if ($this->roles->isEmpty()) {
            $this->__get('roles');
        }

        // 2. Iterate through roles and check their permissions
        // We need to make sure each Roles has its permissions loaded.
        // Lazy loading loop (N+1 issue risk, but functional for single user auth check)

        /** @var Role $role */
        foreach ($this->roles as $role) {
            // Lazy load permissions for this role if not already loaded
            if (!isset($role->permissions) || $role->permissions->isEmpty()) {
                // Accessing the property triggers __get on the Roles model
                $role->permissions;
            }

            /** @var Permission $permission */
            foreach ($role->permissions as $permission) {
                if (in_array($permission->slug, $permissions) || in_array($permission->name, $permissions)) {
                    return true;
                }
            }
        }

        return false;
    }
}