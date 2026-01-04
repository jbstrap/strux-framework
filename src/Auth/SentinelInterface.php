<?php

declare(strict_types=1);

namespace Strux\Auth;

interface SentinelInterface
{
    public function check(): bool;

    public function user(): ?object;

    public function id(): int|string|null;

    public function validate(array $credentials = []): bool;

    public function setUser(object $user): void;
}