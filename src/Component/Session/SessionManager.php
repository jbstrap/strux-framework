<?php

declare(strict_types=1);

namespace Strux\Component\Session;

use Exception;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Strux\Auth\AuthManager;
use Strux\Component\Config\Config;

class SessionManager implements SessionInterface
{
    private Config $config;
    private ContainerInterface $container;
    private bool $started = false;

    public function __construct(Config $config, ContainerInterface $container)
    {
        $this->config = $config;
        $this->container = $container;

        if (PHP_SAPI === 'cli' && !isset($_SESSION)) {
            $_SESSION = [];
        }

        if (PHP_SAPI !== 'cli') {
            try {
                $this->start();
            } catch (Exception $e) {
                error_log("Session: Failed to start session - " . $e->getMessage());
            }
        }
    }

    /**
     * Start the session with the configured driver.
     * This method is now web to allow for manual starting if needed.
     */
    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            $this->started = true;
            return;
        }

        if (headers_sent()) {
            error_log("Session: Failed to start session, headers already sent.");
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            $driver = $this->config->get('session.driver', 'native');

            if ($driver === 'database') {
                $pdo = $this->container->get(PDO::class);
                $table = $this->config->get('session.table', '_sessions');

                // Check if table exists to avoid fatal error loop
                if (!$this->tableExists($pdo, $table)) {
                    // Use ANSI escape code for Yellow/Orange text
                    // \033[33m = Yellow text
                    // \033[0m  = Reset to default
                    $msg = "\033[33mSession Warning: Database table '$table' does not exist. Run 'php console session:init'. Falling back to file session.\033[0m";

                    // Write directly to stderr or stdout, so it appears in CLI output immediately
                    // error_log typically goes to web server logs or php_error.log
                    // but in CLI context (if this runs there) or dev server, this helps visibility.

                    if (PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli') {
                        file_put_contents('php://stderr', $msg . PHP_EOL);
                    } else {
                        error_log($msg);
                    }
                } else {
                    $handler = new DatabaseSessionHandler(
                        $pdo,
                        $table,
                        $this->container->get(AuthManager::class),
                        $this->container->get(ServerRequestInterface::class)
                    );
                    session_set_save_handler($handler, true);
                }
            }

            session_set_cookie_params([
                'lifetime' => (int)$this->config->get('session.lifetime', 120) * 60,
                'path' => $this->config->get('session.path', '/'),
                'domain' => $this->config->get('session.domain', ''),
                'secure' => $this->config->get('session.secure', false),
                'httponly' => $this->config->get('session.http_only', true),
                'samesite' => $this->config->get('session.same_site', 'Lax'),
            ]);

            session_name($this->config->get('session.cookie', 'strux_session'));

            // Prevent PHP from sending its own cache-related headers.
            session_cache_limiter('');

            @session_start();
        }

        $this->started = true;
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        try {
            $result = $pdo->query("SHOW TABLES LIKE '$table'");
            return $result && $result->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ensures session is started before accessing session data.
     */
    private function ensureSessionStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureSessionStarted();

        $data = &$_SESSION;
        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = &$data[$segment];
            } else {
                return $default;
            }
        }
        return $data;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureSessionStarted();

        $segments = explode('.', $key);
        $data = &$_SESSION;
        foreach ($segments as $i => $segment) {
            if (!is_array($data)) {
                $data = [];
            }
            if ($i === count($segments) - 1) {
                $data[$segment] = $value;
            } else {
                if (!isset($data[$segment]) || !is_array($data[$segment])) {
                    $data[$segment] = [];
                }
                $data = &$data[$segment];
            }
        }
    }

    public function has(string $key): bool
    {
        $this->ensureSessionStarted();

        $data = $_SESSION;
        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
            } else {
                return false;
            }
        }
        return true;
    }

    public function remove(string $key): void
    {
        $this->ensureSessionStarted();

        $segments = explode('.', $key);
        $data = &$_SESSION;
        $lastSegment = array_pop($segments);
        foreach ($segments as $segment) {
            if (isset($data[$segment]) && is_array($data[$segment])) {
                $data = &$data[$segment];
            } else {
                return;
            }
        }
        if (is_array($data)) {
            unset($data[$lastSegment]);
        }
    }

    public function all(): array
    {
        $this->ensureSessionStarted();

        return $_SESSION ?? [];
    }

    public function append(string $key, mixed $value): void
    {
        $this->ensureSessionStarted();

        $currentValue = $this->get($key, []);
        if (!is_array($currentValue)) {
            $currentValue = [];
        }
        $currentValue[] = $value;
        $this->set($key, $currentValue);
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
            }
            session_destroy();
        }
    }

    /**
     * @param bool $deleteOldSession
     * @return bool
     */
    public function regenerateId(bool $deleteOldSession = true): bool
    {
        $this->ensureSessionStarted();
        return session_regenerate_id($deleteOldSession);
    }

    public function getId(): string|false
    {
        $this->ensureSessionStarted();

        return session_id();
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $this->ensureSessionStarted();

        $value = $this->get($key, $default);
        if ($value !== $default || $this->has($key)) {
            $this->remove($key);
        }
        return $value;
    }
}