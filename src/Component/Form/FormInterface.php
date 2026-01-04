<?php

declare(strict_types=1);

namespace Strux\Component\Form;

interface FormInterface
{
    public function isValid(): bool;

    public function getData(): array;

    public function getErrors(): array;
}