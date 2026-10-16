<?php

declare(strict_types=1);

namespace Portfolio;

/**
 * Site copy lives in content/<lang>.json, one file per language with the same shape.
 * Every claim on the site comes from there; templates only arrange it.
 */
final class Content
{
    public const LANGS = ['en', 'es'];

    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /** @return array<string, mixed> */
    public static function load(string $root, string $lang): array
    {
        if (!in_array($lang, self::LANGS, true)) {
            throw new \InvalidArgumentException('Unknown language: ' . $lang);
        }
        $file = $root . '/content/' . $lang . '.json';

        return self::$cache[$file] ??= self::read($file);
    }

    /** @return array<string, mixed> */
    private static function read(string $file): array
    {
        $json = file_get_contents($file);
        if ($json === false) {
            throw new \RuntimeException('Cannot read ' . basename($file));
        }
        $data = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \RuntimeException(basename($file) . ' must hold an object.');
        }

        return $data;
    }

    /**
     * Pins tagged on experience highlights, degrees and repositories, grouped per pin:
     * pin id => list of {title, period, lines[]} in page order.
     *
     * @param array<string, mixed> $c
     * @return array<string, list<array{title: string, period: string, lines: list<string>}>>
     */
    public static function evidence(array $c): array
    {
        $out = [];
        foreach ($c['pins'] as $pin) {
            $out[$pin['id']] = [];
        }

        $collect = static function (string $key, string $title, string $period, array $highlights) use (&$out): void {
            foreach ($highlights as $highlight) {
                foreach ($highlight['tags'] ?? [] as $tag) {
                    if (!isset($out[$tag])) {
                        throw new \RuntimeException('Unknown pin tag "' . $tag . '" in ' . $key);
                    }
                    $out[$tag][$key] ??= ['title' => $title, 'period' => $period, 'lines' => []];
                    $out[$tag][$key]['lines'][] = $highlight['text'];
                }
            }
        };

        foreach ($c['experience']['items'] as $item) {
            $collect($item['id'], $item['title'], $item['period'], $item['highlights'] ?? []);
            foreach ($item['engagements'] ?? [] as $engagement) {
                $collect($item['id'] . '/' . $engagement['id'], $engagement['title'], $engagement['period'], $engagement['highlights']);
            }
        }
        foreach ($c['education']['degrees'] as $i => $degree) {
            $collect('degree/' . $i, $degree['title'], $degree['period'], [['text' => $degree['school'], 'tags' => $degree['tags'] ?? []]]);
        }
        foreach ($c['code']['repos'] as $repo) {
            $collect('repo/' . $repo['name'], $c['code']['evidence_prefix'] . $repo['name'], $repo['lang'], [['text' => $repo['desc'], 'tags' => $repo['tags'] ?? []]]);
        }

        return array_map(static fn (array $items): array => array_values($items), $out);
    }

    /**
     * @param array<string, mixed> $c
     * @return array{left: list<array<string, string>>, right: list<array<string, string>>}
     */
    public static function pinSides(array $c): array
    {
        $sides = ['left' => [], 'right' => []];
        foreach ($c['pins'] as $pin) {
            $sides[$pin['side']][] = $pin;
        }

        return $sides;
    }
}
