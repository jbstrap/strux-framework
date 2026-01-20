<?php

declare(strict_types=1);

namespace Strux\Component\Validation;

use Strux\Component\Validation\Rules\Callback;
use Strux\Component\Validation\Rules\RulesInterface;

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

    /**
     * Main entry point: Sets data and rules, then runs validation.
     */
    public function validate(array $data, array $rules): bool
    {
        $this->postData = $data;
        $this->validators = $rules;

        return $this->isValid();
    }

    public function add($field, array $validators): void
    {
        $this->validators[$field] = $validators;
    }

    public function isValid(): bool
    {
        $this->errors = []; // Clear previous errors
        $isFormValid = true;

        // Iterate through fields (key) and their rules (value)
        foreach ($this->validators as $field => $validators) {
            $isFieldValid = $this->validateField($field, $validators);
            $isFormValid = $isFormValid && $isFieldValid;
        }
        return $isFormValid;
    }

    public function validateField($field, $rules = []): bool
    {
        // Safety check: If $field is an array, it means this method was called incorrectly
        if (!is_string($field)) {
            return false;
        }

        $value = $this->postData[$field] ?? null;

        // Use passed rules if available, otherwise fallback to stored validators
        $validators = !empty($rules) ? $rules : ($this->validators[$field] ?? []);

        $isValid = true;

        foreach ($validators as $rule) {
            // 1. Resolve string rules (e.g., 'required', 'min:3') to Objects
            if (is_string($rule)) {
                $rule = $this->resolveRule($rule);
            }

            // 2. Resolve Closures to Callback Rule (Custom Validation)
            if ($rule instanceof \Closure) {
                $rule = new Callback($rule);
            }

            // 3. Execute Rule
            if ($rule instanceof RulesInterface) {
                $errorMessage = $rule->validate($value, $this->postData);
                if ($errorMessage) {
                    $this->errors[$field] = $errorMessage;
                    $isValid = false;
                    break; // Stop at first error for this field
                }
            }
        }
        return $isValid;
    }

    private function resolveRule(string $ruleString): RulesInterface
    {
        $params = [];
        if (str_contains($ruleString, ':')) {
            [$ruleName, $paramStr] = explode(':', $ruleString, 2);
            $params = explode(',', $paramStr);
        } else {
            $ruleName = $ruleString;
        }

        $className = 'Kernel\\Component\\Validation\\Rules\\' . ucfirst(strtolower($ruleName));

        if (class_exists($className)) {
            return new $className(...$params);
        }

        throw new \RuntimeException("Validation rule class '{$className}' not found for rule '{$ruleString}'.");
    }

    public function getData(): array
    {
        return $this->postData;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function clearErrors(): array
    {
        return $this->errors = [];
    }
}