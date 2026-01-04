<?php

declare(strict_types=1);

namespace Strux\Auth;

use Psr\Container\ContainerInterface;
use Strux\Component\Config\Config;

class Authorizer
{
    private AuthManager $auth;
    private Config $config;
    private ContainerInterface $container;

    public function __construct(
        AuthManager        $auth,
        Config             $config,
        ContainerInterface $container
    )
    {
        $this->auth = $auth;
        $this->config = $config;
        $this->container = $container;
    }

    /**
     * Check if the authenticated user allows the given ability.
     *
     * @param string $ability The method name on the Policy (e.g., 'view', 'delete')
     * @param mixed $arguments The resource (Object/String) or array of arguments
     * @return bool
     */
    public function allows(string $ability, mixed $arguments = []): bool
    {
        $user = $this->auth->sentinel()->user();

        if (!$user) {
            return false;
        }

        // 1. Normalize arguments
        // Ensure we have an array of arguments to pass to the method later
        $args = is_array($arguments) ? $arguments : [$arguments];

        // 2. Determine the Resource for Policy Lookup
        // We assume the first argument is the resource (Entity object or Class Name string)
        $resource = $args[0] ?? null;

        if (!$resource) {
            // Cannot authorize without a resource context
            return false;
        }

        // Get class name (if object) or string (if static class name)
        $resourceClass = is_object($resource) ? get_class($resource) : $resource;

        // 3. Resolve Authority (Policy) Class
        $authorityClass = $this->config->get("auth.authorities.{$resourceClass}");

        if (!$authorityClass || !class_exists($authorityClass)) {
            // No policy defined for this model
            return false;
        }

        // 4. Instantiate Authority
        // We use the container to resolve it so dependencies (like Logger) are injected into the Policy
        $authorityInstance = $this->container->get($authorityClass);

        // 5. Determine Method Name
        // Convention: 'create' ability maps to 'canCreate' method
        $method = 'can' . ucfirst($ability);

        if (!method_exists($authorityInstance, $method)) {
            return false;
        }

        // 6. Execute Policy
        // We pass the User as the first argument, followed by the resource arguments
        // Example: $policy->canUpdate($user, $ticket)
        try {
            return (bool)$authorityInstance->{$method}($user, ...$args);
        } catch (\Throwable $e) {
            // Log error if needed, fail safe to false
            return false;
        }
    }
}