<?php

namespace Strux\Component\Validation;

interface ValidatorInterface
{
    public function add($field, array $validators): void;

    public function isValid(): bool;

    public function validateField($field): bool;

    public function getData(): array;

    public function getErrors(): array;

    public function clearErrors(): array;
}