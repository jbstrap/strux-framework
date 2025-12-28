<?php

declare(strict_types=1);

namespace Strux\Auth;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use stdClass;

class JwtService
{
    private string $secret;
    private string $algo;
    private int $expiration;
    private string $issuer;
    private string $audience;

    public function __construct(array $config)
    {
        $this->secret = $config['secret'];
        $this->algo = $config['algo'];
        $this->expiration = $config['expiration'];
        $this->issuer = $config['issuer'];
        $this->audience = $config['audience'];
    }

    /**
     * Generates a new JWT for a given user object.
     */
    public function generateToken(object $user): string
    {
        $pk = method_exists($user, 'getPrimaryKey') ? $user->getPrimaryKey() : 'id';
        $id = $user->{$pk} ?? $user->userId ?? $user->userID ?? null;

        if (!$id) {
            throw new RuntimeException("User object must have an ID to generate a token.");
        }

        $currentTime = time();
        $payload = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $currentTime,
            'nbf' => $currentTime,
            'exp' => $currentTime + $this->expiration,
            'sub' => $id,
        ];

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * Validates a JWT and returns the decoded payload.
     */
    public function validateToken(string $token): ?stdClass
    {
        if (empty($token)) {
            return null;
        }

        try {
            $key = new Key($this->secret, $this->algo);
            return JWT::decode($token, $key);
        } catch (Exception $e) {
            return null;
        }
    }
}