<?php

namespace Carbon_Fields\Frontend;

use Carbon_Fields\Frontend\Field;

class Container
{
    protected static array $instances = [];

    protected string $type;
    protected string $name;

    protected array $conditions = [];
    protected array $fields = [];

    protected function __construct(string $type, string $name)
    {
        $this->type = $type;
        $this->name = $name;

        self::$instances[] = $this;
    }

    public static function make(string $type, string $name): self
    {
        return new self($type, $name);
    }

    public function add_fields(array $fields): self
    {
        $index = 0;
        foreach ($fields as $field) {

            $field->set_index($index);

            $this->fields[] = $field;

            $index++;
        }

        return $this;
    }

    public function where(string $field, string $operator, mixed $value): self
    {
        if ($operator === '=') {
            $this->conditions[$field] = ['operator' => '=', 'value' => $value];
        } elseif ($operator === 'in') {
            if (!is_array($value)) {
                throw new \InvalidArgumentException("'in' operator requires an array as value.");
            }
            $this->conditions[$field] = ['operator' => 'in', 'value' => $value];
        }

        return $this;
    }

    public function matches(string $formId): bool
    {
        foreach ($this->conditions as $field => $condition) {
            $operator = $condition['operator'];
            $value = $condition['value'];

            if ($field === 'id') {
                if ($operator === '=' && (string)$value !== (string)$formId) {
                    return false;
                }
                if ($operator === 'in' && !in_array((string)$formId, $value, true)) {
                    return false;
                }
            }
        }

        return true;
    }


    public static function forForm(string $formId): array
    {
        return array_filter(
            self::$instances,
            fn(self $container) => $container->matches($formId)
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
