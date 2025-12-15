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

    public function where(string $field, string $operator, mixed $value): self
    {
        if ($operator === '=') {
            $this->conditions[$field] = $value;
        }

        return $this;
    }

    public function add_fields(array $fields): self
    {   
        $index = 0;
        foreach ($fields as $field) {

            $field->set_index($index);

            $this->fields[] = $field;

            $index ++;
        }

        return $this;
    }

    public function matches(string $formId): bool
    {
        if (isset($this->conditions['id'])) {
            return (string) $this->conditions['id'] === (string) $formId;
        }

        return true;
    }

    public static function forForm(string $formId): array
    {
        return array_filter(
            self::$instances,
            fn (self $container) => $container->matches($formId)
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
