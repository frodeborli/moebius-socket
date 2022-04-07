<?php
namespace Moebius\Socket;

use Charm\AbstractOptions;

class ConnectionOptions extends AbstractOptions {

    /**
     * Configure a timeout for socket operations in seconds.
     */
    public ?float $timeout = null;

    /**
     * Configure the chunk size for the underlying stream operations.
     * {@see https://www.php.net/manual/en/function.stream-set-chunk-size.php}
     */
    public ?int $chunkSize = null;

    /**
     * Configure the size of the read buffer.
     * {@see https://www.php.net/manual/en/function.stream-set-read-buffer.php}
     */
    public ?int $readBufferSize = null;

    /**
     * Configure the size of the write buffer.
     * {@see https://www.php.net/manual/en/function.stream-set-write-buffer.php}
     */
    public ?int $writeBufferSize = null;

    /**
     * Configure the default line ending for readLine and writeLine operations.
     * {@see https://www.php.net/manual/en/function.stream-get-line.php}
     */
    public string $lineEnding = "\n";

    /**
     * The default maximum line length when using Client::readLine().
     * {@see https://www.php.net/manual/en/function.stream-get-line.php}
     */
    public int $lineLength = 65536;
}
