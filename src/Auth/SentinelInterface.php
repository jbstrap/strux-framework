<?php

declare(strict_types=1);

namespace Strux\Auth;

interface SentinelInterface
{
    public function attempt(array $credentials = [], bool $remember = false): bool;

    public function validate(array $credentials = []): bool;

    public function login(object $user, bool $remember = false): void;

    public function logout(): void;

    public function check(): bool;

    public function user(): ?object;

    public function id(): int|string|null;

    public function setUser(object $user): void;
}