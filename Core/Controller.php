<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    private static ?array $jsonBody = null;
    private static bool $jsonParsed = false;

    protected function view(string $name, array $data = [], ?string $layout = 'main'): void
    {
        View::render($name, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . url($url));
        exit;
    }

    protected function jsonInput(): array
    {
        if (!self::$jsonParsed) {
            self::$jsonParsed = true;
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            if (str_contains(strtolower($contentType), 'application/json')) {
                $raw = file_get_contents('php://input') ?: '{}';
                self::$jsonBody = json_decode($raw, true) ?: [];
            } else {
                self::$jsonBody = [];
            }
        }
        return self::$jsonBody ?? [];
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $json = $this->jsonInput();
        return $_POST[$key] ?? $_GET[$key] ?? $json[$key] ?? $default;
    }

    protected function validate(array $rules): array
    {
        $errors = [];
        $data = [];
        foreach ($rules as $field => $ruleStr) {
            $value = trim((string) $this->input($field, ''));
            $data[$field] = $value;
            foreach (explode('|', $ruleStr) as $rule) {
                if ($rule === 'required' && $value === '') {
                    $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                }
                if (str_starts_with($rule, 'min:') && strlen($value) < (int) substr($rule, 4)) {
                    $errors[$field][] = ucfirst($field) . ' is too short.';
                }
                if (str_starts_with($rule, 'max:') && strlen($value) > (int) substr($rule, 4)) {
                    $errors[$field][] = ucfirst($field) . ' is too long.';
                }
                if ($rule === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Invalid email address.';
                }
                if ($rule === 'phone' && $value !== '' && !preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/\s+/', '', $value) ?? '')) {
                    $errors[$field][] = 'Invalid phone number.';
                }
            }
        }
        return [$data, $errors];
    }

    protected function csrfCheck(): bool
    {
        $token = (string) ($this->input('_csrf') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if ($token === '') {
            return false;
        }
        return Session::verifyCsrf($token);
    }
}
