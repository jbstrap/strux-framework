<?php

declare(strict_types=1);

namespace Strux\Component\ORM\Behavior;

use Strux\Component\Validation\Validator;
use Strux\Component\Validation\ValidatorInterface;
use Strux\Support\ContainerBridge;
use Strux\Component\Exceptions\ValidationException;

trait HasValidation
{
    protected array $_errors = [];

    /**
     * Define validation rules for the model.
     * Override this method in your models.
     */
    public function getRules(): array
    {
        $rules = [];

        if (property_exists($this, 'rules')) {
            $rules = $this->rules;
        }

        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $property) {
            $attributes = $property->getAttributes(\Strux\Component\ORM\Attributes\Validate::class);
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $name = $property->getName();
                if (!isset($rules[$name])) {
                    $rules[$name] = [];
                }
                $rules[$name] = array_merge($rules[$name], $instance->rules);
            }
        }

        return $rules;
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }

    /**
     * Validate the model's attributes against its rules.
     *
     * @param bool $throw Whether to throw a ValidationException on failure
     * @return bool
     * @throws ValidationException
     */
    public function validate(bool $throw = false): bool
    {
        $rules = $this->getRules();
        if (empty($rules)) {
            $this->_errors = [];
            return true;
        }

        try {
            /** @var ValidatorInterface $validator */
            $validator = ContainerBridge::get(ValidatorInterface::class);
        } catch (\Throwable $e) {
            $validator = new Validator();
        }

        // Get properties to validate. Since we want to validate the DB properties:
        $data = method_exists($this, '_getPublicPropertiesForDb') 
            ? $this->_getPublicPropertiesForDb() 
            : (array) $this;

        if ($validator->validate($data, $rules)) {
            $this->_errors = [];
            return true;
        }

        $this->_errors = $validator->getErrors();

        if ($throw) {
            throw new ValidationException($this->_errors);
        }

        return false;
    }
}