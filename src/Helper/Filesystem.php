<?php
declare(strict_types = 1);

namespace BrowscapPHP\Helper;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem as BaseFilesystem;

/**
 * Provides basic utility to manipulate the file system.
 */
class Filesystem extends BaseFilesystem
{
    /**
     * Atomically dumps content into a file.
     *
     * @param  string      $filename The file to be written to.
     * @param  string      $content  The data to write into the file.
     * @param  int|null    $mode     The file mode (octal). If null, file permissions are not modified
     *                               Deprecated since version 2.3.12, to be removed in 3.0.
     *
     * @throws IOException If the file cannot be written to.
     */
    public function dumpFile($filename, $content, ?int $mode = 0666) : void
    {
        $dir = dirname($filename);

        if (! is_dir($dir)) {
            $this->mkdir($dir);
        } elseif (! is_writable($dir)) {
            throw new IOException(sprintf('Unable to write to the "%s" directory.', $dir), 0, null, $dir);
        }

        // "tempnam" did not work with VFSStream for tests
        $tmpFile = dirname($filename) . '/temp_' . md5(time() . basename($filename));

        if (false === @file_put_contents($tmpFile, $content)) {
            throw new IOException(sprintf('Failed to write file "%s".', $filename), 0, null, $filename);
        }

        try {
            $this->rename($tmpFile, $filename, true);
        } catch (IOException $e) {
            unlink($tmpFile);

            throw $e;
        }

        if (null !== $mode) {
            $this->chmod($filename, $mode);
        }
    }
}
