<?php

declare(strict_types=1);

namespace Strux\Auth;

use Psr\Http\Message\ServerRequestInterface;

class TokenSentinel implements SentinelInterface
{
    private ?object $user = null;
    private bool $userLoaded = false;
    private ServerRequestInterface $request;
    private JwtService $jwtService;
    private UserProviderInterface $provider;

    public function __construct(
        ServerRequestInterface $request,
        JwtService             $jwtService,
        UserProviderInterface  $provider
    )
    {
        $this->request = $request;
        $this->jwtService = $jwtService;
        $this->provider = $provider;
    }

    public function user(): ?object
    {
        if ($this->userLoaded) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();
        if ($token) {
            $decodedPayload = $this->jwtService->validateToken($token);

            if ($decodedPayload && isset($decodedPayload->sub)) {
                // Use Provider to get user by ID (sub)
                $this->user = $this->provider->retrieveById($decodedPayload->sub);
            }
        }

        $this->userLoaded = true;
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the User ID from the object safely.
     */
    private function getUserIdFromObject(object $user): string|int|null
    {
        // 1. Try Framework Model Logic
        if (method_exists($user, 'getPrimaryKey')) {
            $pk = $user->getPrimaryKey();

            // Direct property access
            if (isset($user->{$pk})) {
                return $user->{$pk};
            }

            // Getter method access
            $getter = 'get' . ucfirst($pk);
            if (method_exists($user, $getter)) {
                return $user->{$getter}();
            }
        }

        // 2. Fallbacks
        return $user->id ?? $user->userId ?? $user->userID ?? $user->user_id ?? null;
    }

    public function id(): int|string|null
    {
        $user = $this->user();
        if (!$user) {
            return null;
        }
        return $this->getUserIdFromObject($user);
    }

    private function getTokenFromRequest(): ?string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (empty($header)) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    // Token sentinel usually doesn't handle these, but interface requires them
    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function attempt(array $credentials = []): bool
    {
        return false;
    }

    public function login(object $user): void
    {
    }

    public function logout(): void
    {
    }

    public function setUser(object $user): void
    {
        // Set the user instance for the current request
        $this->user = $user;
        $this->userLoaded = true;
    }
}