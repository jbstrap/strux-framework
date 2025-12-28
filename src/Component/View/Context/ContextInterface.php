<?php

declare(strict_types=1);

namespace Strux\Component\View\Context;

interface ContextInterface
{
    /**
     * Process the context and return data to be shared with the view.
     * @return array
     */
    public function process(): array;
}