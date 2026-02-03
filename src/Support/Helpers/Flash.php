<?php

declare(strict_types=1);

namespace Strux\Support\Helpers;

use Strux\Component\Session\SessionInterface;

class Flash implements FlashInterface
{
    private string $sessionKeyPrefix = '_flash.';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->session->pull($this->sessionKeyPrefix . $key, $default);
    }

    public function set(string $key, mixed $message): void
    {
        $this->session->set($this->sessionKeyPrefix . $key, $message);
    }

    public function has(string $key): bool
    {
        return $this->session->has($this->sessionKeyPrefix . $key);
    }

    public function show(string|array $key, string $defaultType = 'info', bool $withAlert = true): string
    {
        $output = '';

        if (is_array($key)) {
            foreach ($key as $messageKey => $typeOrMessageKey) {
                $currentType = $defaultType;

                if (is_string($messageKey)) {
                    // Associative array: ['message_key' => 'type']
                    $currentKey = $messageKey;
                    $currentType = is_string($typeOrMessageKey) ? $typeOrMessageKey : $defaultType;
                } else {
                    // Indexed array: ['message_key1', 'message_key2']
                    $currentKey = $typeOrMessageKey;
                }
                $output .= $this->renderMessageHtml($currentKey, $currentType, $withAlert);
            }
        } else {
            // Single key string
            $output = $this->renderMessageHtml($key, $defaultType, $withAlert);
        }
        return $output;
    }

    /**
     * Renders the HTML for a single flash message key.
     */
    private function renderMessageHtml(string $key, string $type, bool $withAlert): string
    {
        $htmlOutput = '';
        if ($this->has($key)) {
            $messages = (array)$this->get($key);

            if (!empty($messages)) {
                if ($withAlert) {
                    $htmlOutput .= '<div class="alert alert-' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . ' mb-3">';
                    foreach ($messages as $message) {
                        $htmlOutput .= '<div>' . htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') . '</div>';
                    }
                    $htmlOutput .= '</div>';
                } else {
                    foreach ($messages as $message) {
                        $htmlOutput .= htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8');
                    }
                }
            }
        }
        return $htmlOutput;
    }
}