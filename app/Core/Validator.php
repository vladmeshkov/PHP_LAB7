<?php
declare(strict_types=1);

namespace Hotel\Core;

use DateTimeImmutable;

/**
 * Проверка данных формы по простым правилам: 'required|email|max:150'.
 * Возвращает по одному сообщению на поле.
 */
final class Validator
{
    private array $errors = [];
    private array $data = [];

    /**
     * @param array<string, string> $rules  поле => правила через «|»
     * @param array<string, string> $labels поле => название для сообщений
     */
    public static function make(array $data, array $rules, array $labels = []): self
    {
        $v = new self();
        $v->data = $data;

        foreach ($rules as $field => $ruleString) {
            $label = $labels[$field] ?? $field;
            $value = (string) ($data[$field] ?? '');

            foreach (explode('|', $ruleString) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                // Необязательное пустое поле остальными правилами не проверяется.
                if ($name !== 'required' && $value === '' && !in_array($name, ['confirmed'], true)) {
                    continue;
                }
                if (($message = $v->check($name, $arg, $field, $value, $label)) !== null) {
                    $v->errors[$field] = $message;
                    break;
                }
            }
        }

        return $v;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /** Добавляет ошибку вручную (например, «email уже занят»). */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }

    private function check(string $rule, ?string $arg, string $field, string $value, string $label): ?string
    {
        switch ($rule) {
            case 'required':
                return $value === '' ? "Поле «{$label}» обязательно для заполнения" : null;
            case 'min':
                return mb_strlen($value) < (int) $arg ? "Поле «{$label}»: минимум {$arg} символов" : null;
            case 'max':
                return mb_strlen($value) > (int) $arg ? "Поле «{$label}»: максимум {$arg} символов" : null;
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) === false ? "Поле «{$label}» должно быть адресом электронной почты" : null;
            case 'name':
                return preg_match('/^[\p{L}][\p{L} .\'-]*$/u', $value) !== 1 ? "Поле «{$label}» содержит недопустимые символы" : null;
            case 'password':
                return (preg_match('/\p{L}/u', $value) !== 1 || preg_match('/\d/', $value) !== 1) ? "Поле «{$label}» должно содержать буквы и цифры" : null;
            case 'int':
                return filter_var($value, FILTER_VALIDATE_INT) === false ? "Поле «{$label}» должно быть целым числом" : null;
            case 'between':
                [$min, $max] = array_map('intval', explode(',', (string) $arg));
                $n = filter_var($value, FILTER_VALIDATE_INT);

                return ($n === false || $n < $min || $n > $max) ? "Поле «{$label}» должно быть от {$min} до {$max}" : null;
            case 'decimal':
                return preg_match('/^\d{1,8}([.,]\d{1,2})?$/', $value) !== 1 ? "Поле «{$label}» должно быть числом (до двух знаков после запятой)" : null;
            case 'min_value':
                return (float) str_replace(',', '.', $value) < (float) $arg ? "Поле «{$label}» должно быть не меньше {$arg}" : null;
            case 'in':
                return !in_array($value, explode(',', (string) $arg), true) ? "Поле «{$label}»: недопустимое значение" : null;
            case 'date':
                $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

                return ($d === false || $d->format('Y-m-d') !== $value) ? "Поле «{$label}» должно быть датой" : null;
            case 'confirmed':
                return $value !== (string) ($this->data[$field . '_confirmation'] ?? '') ? 'Пароли не совпадают' : null;
        }

        return null;
    }
}
