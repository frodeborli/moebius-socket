<?php
namespace Moebius\Socket;

use Closure;
use Charm\Event\{EventEmitterInterface, EventEmitterTrait};
use function M\{readable, writable, go};

/**
 * A generic socket client class for working asynchronously with
 * network connections.
 */
abstract class AbstractConnection implements EventEmitterInterface {
    use EventEmitterTrait;

    public readonly ConnectionOptions $options;
    protected mixed $_context = null;
    protected mixed $_socket = null;

    public function __construct(array|ConnectionOptions $options=[]) {
        $this->options = ConnectionOptions::create($options);
    }


    public function read(int $length): string {
        return $this->readOperation(fread(...), $this->_socket, $length);
    }

    public function readAll(): string {
        $buffer = '';
        while (!$this->eof()) {
            $buffer .= $this->read(4096);
        }
        return $buffer;
    }

    public function readLine(int $length=null, string $ending=null): string {
        return $this->readOperation(
            stream_get_line(...),
            $this->_socket,
            $length ?? $this->options->lineLength,
            $ending ?? $this->options->lineEnding
        );
    }

    public function write(string $data, ?int $length = null): int {
        return $this->writeOperation(fwrite(...), $this->_socket, $data, $length);
    }

    public function eof(): bool {
        $this->assertStillConnected();
        return feof($this->_socket);
    }

    public function close(): void {
        $this->assertStillConnected();
        if (!fclose($this->_socket)) {
            throw new IOException("fclose() call failed");
        }
    }

    public function isConnected(): bool {
        return $this->_socket !== null;
    }

    public function isDisconnected(): bool {
        return $this->isConnected() && !is_resource($this->_socket);
    }

    protected function readOperation(Closure $callable, mixed ...$args): string {
        $this->assertStillConnected();
        readable($this->_socket);
        $result = $callable(...$args);
        if ($result === false) {
            throw new ReadException("Reading from socket failed");
        }
        return $result;
    }

    protected function writeOperation(Closure $callable, mixed ...$args): int {
        $this->assertStillConnected();
        writable($this->_socket);
        $result = $callable(...$args);
        if ($result === false) {
            throw new WriteException("Writing to socket failed");
        }
        return $result;
    }

    protected function assertConnected(): void {
        if (!$this->isConnected()) {
            throw new NotConnectedError();
        }
    }

    protected function assertStillConnected(): void {
        if (!$this->isConnected() || $this->isDisconnected()) {
            throw new DisconnectedException();
        }
    }

}

