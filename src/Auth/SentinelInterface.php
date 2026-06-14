<?php

declare(strict_types=1);

namespace Strux\Auth;

interface SentinelInterface
{
	public function authenticate(array|object $credentials = [], bool $remember = false): bool;

	public function validate(array $credentials = []): bool;

	public function login(object $user, bool $remember = false): void;

	public function logout(): void;

	public function isAuthenticated(): bool;

	public function user(): ?object;

	public function id(): int|string|null;

	public function setUser(object $user): void;
}
