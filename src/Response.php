<?php

declare(strict_types=1);

namespace Portfolio;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status = 200,
        public readonly array $headers = [],
        public readonly string $body = '',
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html; charset=utf-8'], $body);
    }

    public static function text(string $body, int $status = 200, string $type = 'text/plain; charset=utf-8'): self
    {
        return new self($status, ['Content-Type' => $type], $body);
    }

    public static function redirect(string $location, int $status = 303): self
    {
        return new self($status, ['Location' => $location, 'Cache-Control' => 'no-store']);
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        foreach (array_keys($headers) as $existing) {
            if (strcasecmp($existing, $name) === 0) {
                unset($headers[$existing]);
            }
        }
        $headers[$name] = $value;

        return new self($this->status, $headers, $this->body);
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
