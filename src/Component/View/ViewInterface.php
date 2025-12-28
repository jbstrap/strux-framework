<?php

declare(strict_types=1);

namespace Strux\Component\View;

use Exception;

/**
 * Interface ViewInterface
 *
 * Defines a common contract for view rendering engines.
 */
interface ViewInterface
{
    /**
     * Renders a template.
     *
     * @param string $template The name/path of the template to render.
     * For Twig, this is like 'pages/home.html.twig'.
     * For Plates, this is like 'pages::home' or 'pages/home'.
     * @param array $data An array of data to pass to the template.
     * @return string The rendered content.
     * @throws Exception If rendering fails.
     */
    public function render(string $template, array $data = []): string;
}