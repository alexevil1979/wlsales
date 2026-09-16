<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private array $data)
    {
    }

    public function required(string $field, string $label = ''): self
    {
        $v = $this->data[$field] ?? null;
        if ($v === null || (is_string($v) && trim($v) === '')) {
            $this->errors[$field] = ($label ?: $field) . ' обязательно';
        }
        return $this;
    }

    public function email(string $field): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Некорректный email';
        }
        return $this;
    }

    public function minLen(string $field, int $min, string $label = ''): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if ($v !== '' && mb_strlen($v) < $min) {
            $this->errors[$field] = ($label ?: $field) . " — минимум {$min} символов";
        }
        return $this;
    }

    public function maxLen(string $field, int $max, string $label = ''): self
    {
        $v = (string) ($this->data[$field] ?? '');
        if (mb_strlen($v) > $max) {
            $this->errors[$field] = ($label ?: $field) . " — максимум {$max} символов";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label = ''): self
    {
        $v = $this->data[$field] ?? null;
        if ($v !== null && $v !== '' && !in_array($v, $allowed, true)) {
            $this->errors[$field] = ($label ?: $field) . ' имеет недопустимое значение';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return $this->errors !== [] ? (string) reset($this->errors) : '';
    }
}
