<?php declare(strict_types=1);

namespace src\validation;

/**
 * @package src\validation
 */
final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @return ValidationResult
     */
    public function make(array $data, array $rules): ValidationResult
    {
        $result = new ValidationResult();

        foreach ($rules as $field => $definition) {
            $value = $data[$field] ?? null;
            $segments = explode('|', $definition);

            foreach ($segments as $segment) {
                $segment = trim($segment);
                if ($segment === '') {
                    continue;
                }

                $this->evaluateRule($field, $segment, $value, $result);
            }

            if (!array_key_exists($field, $result->errors())) {
                $result->setValidated($field, $value);
            }
        }

        return $result;
    }

    /**
     * @param string $field
     * @param string $rule
     * @param mixed $value
     * @param ValidationResult $result
     * @return void
     */
    private function evaluateRule(
        string $field,
        string $rule,
        mixed $value,
        ValidationResult $result,
    ): void {
        if ($rule === 'required') {
            if (
                $value === null ||
                $value === '' ||
                (is_array($value) && $value === [])
            ) {
                $result->addError($field, 'This field is required.');
            }

            return;
        }

        if ($value === null) {
            return;
        }

        if ($rule === 'string' && !is_string($value)) {
            $result->addError($field, 'The value must be a string.');
            return;
        }

        if (
            $rule === 'email' &&
            (is_string($value) && !filter_var($value, FILTER_VALIDATE_EMAIL))
        ) {
            $result->addError(
                $field,
                'The value must be a valid email address.',
            );
            return;
        }

        if (str_starts_with($rule, 'max:')) {
            $limit = (int) substr($rule, strlen('max:'));
            if (is_string($value) && mb_strlen($value) > $limit) {
                $result->addError(
                    $field,
                    "The value may not be greater than {$limit} characters.",
                );
            }
        }

        if (str_starts_with($rule, 'min:')) {
            $limit = (int) substr($rule, strlen('min:'));
            if (is_string($value) && mb_strlen($value) < $limit) {
                $result->addError(
                    $field,
                    "The value must be at least {$limit} characters.",
                );
            }
        }
    }
}
