<?php declare(strict_types=1);

namespace src\validation;

/**
 * @package src\validation
 */
final class ValidationResult
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $errors = [];

    /**
     * @var array<string, mixed>
     */
    private array $validated = [];

    /**
     * @param string $field
     * @param string $message
     * @return void
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * @return bool
     */
    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function setValidated(string $field, mixed $value): void
    {
        $this->validated[$field] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }
}
