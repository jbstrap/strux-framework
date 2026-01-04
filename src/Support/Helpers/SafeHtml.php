<?php

namespace Strux\Support\Helpers;

final class SafeHtml
{
    private string $html;

    public function __construct(string $html)
    {
        $this->html = $html;
    }

    public function __toString(): string
    {
        return $this->html;
    }
}