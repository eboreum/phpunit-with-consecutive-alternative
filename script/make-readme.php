#!/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$content = file_get_contents(__DIR__ . '/README.source.md');

assert(is_string($content));

$composerJsonArray = (static function (): array {
    $contents = file_get_contents(dirname(__DIR__) . '/composer.json');

    assert(is_string($contents));

    $decoded = json_decode($contents, true);

    assert(is_array($decoded));

    return $decoded;
})();

$regexLineBreaks = '/(\r?\n|\r)/';
$split = preg_split($regexLineBreaks, $content);

assert(is_array($split));

foreach ($split as $i => &$line) {
    if ('%composer.json.description%' === trim($line)) {
        $line = (
            $composerJsonArray['description']
            . "\n"
            . "\n"
            . '[comment]: # (The README.md is generated using `script/generate-readme.php`)'
            . "\n"
        );

        continue;
    }

    if ('%composer.json.authors%' === trim($line)) {
        $segments = [];
        foreach ($composerJsonArray['authors'] as $author) {
            $homepageURL = null;

            if (array_key_exists('homepage', $author)) {
                $homepageURL = $author['homepage'];
            }

            $segment = '- **' . $author['name'] . '**';

            if ($homepageURL) {
                preg_match(
                    sprintf(
                        '/^%s\/(\w+)/',
                        preg_quote('https://github.com', '/'),
                    ),
                    $author['homepage'],
                    $match,
                );

                if ($match) {
                    $segment .= sprintf(
                        ' (%s)',
                        $match[1],
                    );
                }
            }

            if (array_key_exists('email', $author)) {
                $segment .= '<br>E-mail: ' . sprintf(
                    '<a href="mailto:%s">%s</a>',
                    $author['email'],
                    $author['email']
                );
            }

            if ($homepageURL) {
                $segment .= '<br>Homepage: ' . sprintf(
                    '<a href="%s">%s</a>',
                    htmlspecialchars($homepageURL, ENT_COMPAT | ENT_HTML5),
                    htmlspecialchars($homepageURL, ENT_COMPAT | ENT_HTML5),
                );
            }

            $segments[] = $segment;
        }

        if ($segments) {
            $line = implode("\n", $segments);
        } else {
            unset($split[$i]);
        }

        continue;
    }

    preg_match('/^%composer\.json\.(require(-dev)?)%$/', trim($line), $match);

    if ($match) {
        $segments = [];
        $composerJsonArrayKey = $match[1];

        assert(array_key_exists($composerJsonArrayKey, $composerJsonArray));

        foreach ($composerJsonArray[$composerJsonArrayKey] as $requireName => $requireVersion) {
            assert(is_string($requireName));
            assert(is_string($requireVersion));

            $segments[] = sprintf(
                '"%s": "%s"',
                $requireName,
                $requireVersion,
            );
        }

        $line = (
            '```json'
            . "\n"
            . implode(',' . "\n", $segments)
            . "\n"
            . '```'
        );

        continue;
    }

    preg_match('/^%include "(.+)"%$/', $line, $includeMatch);

    if ($includeMatch) {
        $includeContent = file_get_contents(dirname(__DIR__) . '/' . $includeMatch[1]);

        assert(is_string($includeContent));

        $includeSplit = preg_split($regexLineBreaks, $includeContent);

        assert(is_array($includeSplit));

        foreach ($includeSplit as $j => &$includeLine) {
            assert(is_string($includeLine));

            if (preg_match('/^(.+);\s*\/\/\s*README.md.remove\s*$/', trim($includeLine))) {
                $includeLine = null;

                if (array_key_exists($j - 1, $includeSplit)) {
                    if ('' === $includeSplit[$j - 1]) {
                        $includeSplit[$j - 1] = null;
                    }
                }

                continue;
            }
        }

        $includeSplit = array_filter($includeSplit, 'is_string');

        $line = implode("\n", $includeSplit);
    }

    preg_match('/^%run "(.+)"%$/', $line, $runMatch);

    if ($runMatch) {
        ob_start();
        include dirname(__DIR__) . '/' . $runMatch[1];
        $output = ob_get_contents();
        ob_end_clean();

        $line = $output;
    }
}

$content = implode("\n", $split);

if (defined('TEST_ROOT_PATH')) {
    echo $content;
} else {
    file_put_contents(dirname(__DIR__) . '/README.md', $content);
}
