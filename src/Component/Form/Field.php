<?php

declare(strict_types=1);

namespace Strux\Component\Form;

class Field
{
    protected string $name;
    protected string $type;
    protected ?string $label;
    protected mixed $value = null;
    protected array $attributes = [];
    protected array $rules = [];
    protected array $errors = [];
    protected array $options = [];

    public function __construct(string $name, string $type = 'text', array $config = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->label = $config['label'] ?? ucfirst(str_replace('_', ' ', $name));
        $this->value = $config['value'] ?? null;
        $this->attributes = $config['attributes'] ?? [];
        $this->rules = $config['rules'] ?? [];
        $this->options = $config['options'] ?? [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Render the Label HTML.
     */
    public function label(array $attributes = []): string
    {
        if ($this->label === null || in_array($this->type, ['submit', 'button', 'reset'])) {
            return '';
        }

        $attrString = $this->buildAttributes(array_merge(['for' => $this->name], $attributes));
        return sprintf('<label %s>%s</label>', $attrString, htmlspecialchars($this->label));
    }

    /**
     * Render the Input HTML.
     */
    public function input(array $attributes = []): string
    {
        $attributes = array_merge($this->attributes, $attributes);

        if ($this->hasError()) {
            $class = $attributes['class'] ?? '';
            $attributes['class'] = trim($class . ' is-invalid');
        }

        $attributes['name'] = $this->name;
        $attributes['id'] = $attributes['id'] ?? $this->name;

        if ($this->type === 'textarea') {
            return $this->renderTextarea($attributes);
        }

        if ($this->type === 'select') {
            return $this->renderSelect($attributes);
        }

        if (in_array($this->type, ['submit', 'button', 'reset'])) {
            return $this->renderButton($attributes);
        }

        $attributes['type'] = $this->type;
        $attributes['value'] = (string)$this->value;

        return sprintf('<input %s>', $this->buildAttributes($attributes));
    }

    /**
     * Render validation errors for this field.
     */
    public function error(string $class = 'invalid-feedback'): string
    {
        if (!$this->hasError()) {
            return '';
        }

        return sprintf('<div class="%s">%s</div>', $class, htmlspecialchars($this->errors[0]));
    }

    protected function renderTextarea(array $attributes): string
    {
        $value = $this->value;
        unset($attributes['value'], $attributes['type']);

        return sprintf(
            '<textarea %s>%s</textarea>',
            $this->buildAttributes($attributes),
            htmlspecialchars((string)$value)
        );
    }

    protected function renderSelect(array $attributes): string
    {
        $value = $this->value;
        unset($attributes['value'], $attributes['type']);

        $optionsHtml = '';
        foreach ($this->options as $val => $text) {
            $selected = ($val == $value) ? 'selected' : '';
            $optionsHtml .= sprintf('<option value="%s" %s>%s</option>', htmlspecialchars((string)$val), $selected, htmlspecialchars($text));
        }

        return sprintf('<select %s>%s</select>', $this->buildAttributes($attributes), $optionsHtml);
    }

    protected function renderButton(array $attributes): string
    {
        $text = $this->label ?? 'Submit';

        $type = $this->type;
        unset($attributes['type']); // handled manually in sprintf

        if ($this->value !== null) {
            $attributes['value'] = $this->value;
        }

        return sprintf(
            '<button type="%s" %s>%s</button>',
            $type,
            $this->buildAttributes($attributes),
            htmlspecialchars($text)
        );
    }

    protected function buildAttributes(array $attributes): string
    {
        $html = [];
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $html[] = $key;
            } elseif ($value !== false && $value !== null) {
                $html[] = sprintf('%s="%s"', $key, htmlspecialchars((string)$value));
            }
        }
        return implode(' ', $html);
    }
}