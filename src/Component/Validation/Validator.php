<?php

declare(strict_types=1);

namespace Strux\Component\Validation;

class Validator implements ValidatorInterface
{
    private array $postData;
    private array $validators;
    private array $errors;

    public function __construct(array $postData = [])
    {
        $this->postData = $postData;
        $this->validators = [];
        $this->errors = [];
    }

    public function add($field, array $validators): void
    {
        $this->validators[$field] = $validators;
    }

    public function isValid(): bool
    {
        $isFormValid = true;

        foreach ($this->validators as $field => $validators) {
            $isFieldValid = $this->validateField($field);
            $isFormValid = $isFormValid && $isFieldValid;
        }
        return $isFormValid;
    }

    public function validateField($field): bool
    {
        $value = $this->postData[$field] ?? '';
        $validators = $this->validators[$field];
        $isValid = true;

        foreach ($validators as $validator) {
            $errorMessage = $validator->validate($value, $this->postData);
            if ($errorMessage) {
                $this->errors[$field] = $errorMessage;
                $isValid = false;
                break;
            }
        }
        return $isValid;
    }

    /**
     * @return mixed
     */
    public function getData(): array
    {
        return $this->postData;
    }

    /**
     * @return mixed
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function clearErrors(): array
    {
        return $this->errors = [];
    }
}