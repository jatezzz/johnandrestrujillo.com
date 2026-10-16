<?php

declare(strict_types=1);

namespace Portfolio;

final class View
{
    public function __construct(private readonly string $templateDir)
    {
    }

    /** @param array<string, mixed> $vars */
    public function render(string $template, array $vars = []): string
    {
        $file = $this->templateDir . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Missing template: ' . $template);
        }

        $vars['view'] = $this;
        $vars['e'] = self::e(...);
        $vars['m'] = self::markup(...);

        return (static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            try {
                require $__file;
            } catch (\Throwable $error) {
                ob_end_clean();
                throw $error;
            }

            return (string) ob_get_clean();
        })($file, $vars);
    }

    /** @param array<string, mixed> $vars */
    public function partial(string $name, array $vars): string
    {
        return $this->render('partials/' . $name, $vars);
    }

    public static function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /** Escapes text, then sets {braced} runs (years, figures) in the mono face. */
    public static function markup(string $value): string
    {
        return (string) preg_replace('/\{([^{}]+)\}/u', '<span class="num">$1</span>', self::e($value));
    }
}
