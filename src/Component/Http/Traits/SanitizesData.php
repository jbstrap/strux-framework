<?php

declare(strict_types=1);

namespace Strux\Component\Http\Traits;

trait SanitizesData
{
    /**
     * Sanitizes data by trimming, removing slashes, and converting special HTML characters.
     */
    protected function sanitize(string|array|null $data): string|array|null
    {
        if ($data === null) {
            return null;
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }
        $data = trim($data);
        $data = stripslashes($data);
        return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}