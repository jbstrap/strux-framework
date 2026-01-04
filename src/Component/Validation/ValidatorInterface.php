<?php

namespace Strux\Component\Validation;

interface ValidatorInterface
{
    /**
     * Validate the entire dataset against the given rules.
     *
     * @param array $data Key-value pair of data (e.g., $_POST)
     * @param array $rules Key-value pair of rules (e.g., ['email' => ['required']])
     * @return bool
     */
    public function validate(array $data, array $rules): bool;

    public function add($field, array $validators): void;

    public function isValid(): bool;

    public function validateField($field, $rules = []): bool;

    public function getData(): array;

    public function getErrors(): array;

    public function clearErrors(): array;
}