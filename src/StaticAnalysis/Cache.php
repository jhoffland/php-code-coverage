<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use const DIRECTORY_SEPARATOR;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function is_file;
use function serialize;
use function str_replace;
use function unserialize;
use SebastianBergmann\CodeCoverage\Directory;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
abstract class Cache
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var bool
     */
    private $validate;

    public function __construct(string $directory, bool $validate = true)
    {
        Directory::create($directory);

        $this->directory = $directory . DIRECTORY_SEPARATOR;
        $this->validate  = $validate;
    }

    protected function has(string $filename, string $method): bool
    {
        if (!$this->validate) {
            return true;
        }

        $cacheFile = $this->cacheFile($filename, $method);

        if (!is_file($cacheFile)) {
            return false;
        }

        if (filemtime($cacheFile) < filemtime($filename)) {
            return false;
        }

        return true;
    }

    /**
     * @psalm-param list<class-string> $allowedClasses
     *
     * @return mixed
     */
    protected function read(string $filename, string $method, array $allowedClasses = [])
    {
        $options = ['allowed_classes' => false];

        if (!empty($allowedClasses)) {
            $options = ['allowed_classes' => $allowedClasses];
        }

        return unserialize(
            file_get_contents(
                $this->cacheFile($filename, $method)
            ),
            $options
        );
    }

    /**
     * @param mixed $data
     */
    protected function write(string $filename, string $method, $data): void
    {
        file_put_contents(
            $this->cacheFile($filename, $method),
            serialize($data)
        );
    }

    private function cacheFile(string $filename, string $method): string
    {
        return $this->directory . str_replace([DIRECTORY_SEPARATOR, '.'], '_', $filename) . '_' . $method;
    }
}
