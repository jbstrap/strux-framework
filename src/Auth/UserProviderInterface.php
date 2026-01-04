<?php

declare(strict_types=1);

namespace Strux\Auth;

interface UserProviderInterface
{
    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById(mixed $identifier): ?object;

    /**
     * Retrieve a user by their unique credentials (e.g., email).
     */
    public function retrieveByCredentials(array $credentials): ?object;

    /**
     * Validate a user against the given credentials (e.g., check password).
     */
    public function validateCredentials(object $user, array $credentials): bool;
}