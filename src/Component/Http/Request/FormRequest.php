<?php

declare(strict_types=1);

namespace Strux\Component\Http\Request;

use ReflectionClass;
use ReflectionProperty;
use Strux\Component\Exceptions\ValidationException;
use Strux\Component\Validation\Validator;

abstract class FormRequest
{
    protected array $data = [];
    protected Validator $validator;

    public function __construct()
    {
        // This class is instantiated by the ParameterResolver.
    }

    /**
     * Define the validation rules for the request.
     * Example: ['name' => [new Required(), new MinLength(3)]]
     *
     * @return array
     */
    abstract protected function rules(): array;

    /**
     * Populates the FormRequest's web properties from an array of data.
     *
     * @throws ValidationException
     */
    public function populateAndValidate(array $data): void
    {
        $this->data = $data;
        $this->validator = new Validator($this->data);

        foreach ($this->rules() as $field => $rules) {
            $this->validator->add($field, $rules);
        }

        if (!$this->validator->isValid()) {
            throw new ValidationException($this->validator->getErrors());
        }

        // Validation passed, populate web properties
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            if (array_key_exists($propertyName, $this->data)) {
                $this->{$propertyName} = $this->data[$propertyName];
            }
        }
    }

    /**
     * Get all the validated data.
     */
    public function all(): array
    {
        return $this->data;
    }
}