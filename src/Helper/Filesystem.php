<?php

declare(strict_types=1);

namespace BrowscapPHP\Helper;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;

use function basename;
use function dirname;
use function file_put_contents;
use function is_dir;
use function is_writable;
use function md5;
use function sprintf;
use function time;
use function unlink;

/**
 * Provides basic utility to manipulate the file system.
 *
 * @internal This extends Symfony API, and we do not want to expose upstream BC breaks, so we DO NOT promise BC on this
 */
class Filesystem extends BaseFilesystem
{
    /**
     * Atomically dumps content into a file.
     *
     * @param  string   $filename The file to be written to.
     * @param  string   $content  The data to write into the file.
     * @param  int|null $mode     The file mode (octal). If null, file permissions are not modified
     *                            Deprecated since version 2.3.12, to be removed in 3.0.
     *
     * @throws IOException If the file cannot be written to.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function dumpFile(string $filename, $content, ?int $mode = 0666): void
    {
        $dir = dirname($filename);

        if (! is_dir($dir)) {
            $this->mkdir($dir);
        } elseif (! is_writable($dir)) {
            throw new IOException(sprintf('Unable to write to the "%s" directory.', $dir), 0, null, $dir);
        }

        // "tempnam" did not work with VFSStream for tests
        $tmpFile = dirname($filename) . '/temp_' . md5(time() . basename($filename));

        if (@file_put_contents($tmpFile, $content) === false) {
            throw new IOException(sprintf('Failed to write file "%s".', $filename), 0, null, $filename);
        }

        try {
            $this->rename($tmpFile, $filename, true);
        } catch (IOException $e) {
            unlink($tmpFile);

            throw $e;
        }

        if ($mode === null) {
            return;
        }

        $this->chmod($filename, $mode);
    }
}
