<?php

declare(strict_types=1);

namespace Test\Unit\Eboreum\PhpunitWithConsecutiveAlternative;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

use function array_map;
use function array_shift;
use function assert;
use function dirname;
use function file_get_contents;
use function implode;
use function is_array;
use function is_file;
use function is_string;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function preg_match;
use function preg_quote;
use function preg_split;
use function sprintf;

#[CoversNothing]
class ReadmeMdTest extends TestCase
{
    private string $contents;

    public function setUp(): void
    {
        $readmeFilePath = dirname(TEST_ROOT_PATH) . '/README.md';

        $this->assertTrue(is_file($readmeFilePath), 'README.md does not exist!');

        $contents = file_get_contents($readmeFilePath);

        assert(is_string($contents)); // Make phpstan happy

        $this->contents = $contents;
    }

    /**
     * Did we leave remember to update the contents of README.md?
     */
    public function testIsReadmeMdUpToDate(): void
    {
        ob_start();
        include dirname(TEST_ROOT_PATH) . '/script/make-readme.php';
        $producedContents = ob_get_contents();
        ob_end_clean();

        $this->assertTrue(
            $this->contents === $producedContents,
            sprintf(
                "README.md is not up–to-date. Please run: php script/make-readme.php: Diff:\n\n%s",
                (new Differ(new UnifiedDiffOutputBuilder()))->diff($this->contents, $producedContents ?: ''),
            ),
        );
    }

    public function testDoesReadmeMdContainLocalFilePaths(): void
    {
        $split = preg_split('/([\\\\\/])/', PROJECT_ROOT_DIRECTORY_PATH);

        $this->assertIsArray($split);
        assert(is_array($split)); // Make phpstan happy

        if ('' === ($split[0] ?? null)) {
            array_shift($split);
        }

        $wrapAndImplode = static function (string ...$strings) {
            $inner = '(\\\\+\/|\\\\+|\/)'; // Handle both Windows and Unix

            return sprintf(
                '/%s%s%s/',
                $inner,
                implode(
                    $inner,
                    array_map(
                        static function (string $v) {
                            return preg_quote($v, '/');
                        },
                        $strings,
                    ),
                ),
                $inner,
            );
        };

        $rootPathRegex = $wrapAndImplode(...$split);

        $this->assertSame(
            0,
            preg_match($rootPathRegex, $this->contents),
            'README.md contains local file paths (on your system) and it should not.',
        );
    }
}
