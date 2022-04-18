<?php
namespace Moebius\Socket;

use Closure;
use Charm\Event\{EventEmitterInterface, EventEmitterTrait};
use Moebius\Coroutine as Co;

/**
 * A generic socket client class for working asynchronously with
 * network connections.
 */
abstract class AbstractConnection implements EventEmitterInterface {
    use EventEmitterTrait;

    const CLOSE_EVENT = 'CLOSE_EVENT';

    public readonly ConnectionOptions $options;
    protected mixed $_context = null;
    private mixed $_socket = null;

    public function __construct(ConnectionOptions $options=null) {
        $this->options = ConnectionOptions::create($options);
    }

    protected function setSocket($socket) {
        if ($this->_socket) {
            throw new LogicError("Socket already set");
        }
        if (!is_resource($socket)) {
            throw new LogicError("Not a stream resource");
        }
        stream_set_blocking($socket, false);
        stream_set_timeout($socket, 0, 500000);
        if ($this->options->chunkSize !== null) {
            stream_set_chunk_size($socket, $this->options->chunkSize);
        }
        if ($this->options->readBufferSize !== null) {
            stream_set_read_buffer($socket, $this->options->readBufferSize);
        }
        if ($this->options->writeBufferSize !== null) {
            stream_set_write_buffer($socket, $this->options->writeBufferSize);
        }

        $this->_socket = $socket;
    }

    protected function getSocket() {
        return $this->_socket;
    }


    public function read(int $length): string {
        $limit = 10;
        $result = null;
        while (is_resource($this->_socket)) {
            if (0 === --$limit) {
                throw new ReadException("Reading from socket failed");
            }
            if (null !== ($result = $this->readOperation(fread(...), $this->_socket, $length))) {
                return $result;
            }
            Co::sleep(0.1);
        }
        throw new DisconnectedException("Socket was closed while trying to read");
    }

    public function readAll(): string {
        $res = $this->readOperation(stream_get_contents(...), $this->_socket);
        if (is_string($res)) {
            return $res;
        }
        throw new ReadException("Reading from socket failed");
    }

    public function readLine(int $length=null, string $ending=null): string {
        $limit = 10;
        $result = null;
        while (is_resource($this->_socket)) {

            if (0 === --$limit) {
                throw new ReadException("Reading from socket failed");
            }
            if (null !== ($result = $this->readOperation(
                stream_get_line(...),
                $this->_socket,
                $length ?? $this->options->lineLength,
                $ending ?? $this->options->lineEnding
            ))) {
                return $result;
            }
            Co::sleep(0.1);
        }
        throw new DisconnectedException("Socket was closed while trying to read");
    }

    public function write(string $data, ?int $length = null): int {
        $limit = 10;
        $result = null;
        while (is_resource($this->_socket)) {
            if (0 === --$limit) {
                throw new ReadException("Writing to socket failed");
            }
            if (null !== ($result = $this->writeOperation(fwrite(...), $this->_socket, $data, $length))) {
                return $result;
            }
            Co::sleep(0.1);
        }
        throw new DisconnectedException("Connection closed while trying to write");
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
        $this->_socket = null;
        $this->handleClose();
    }

    public function isConnected(): bool {
        return $this->_socket !== null;
    }

    public function isDisconnected(): bool {
        if ($this->isConnected() && !is_resource($this->_socket)) {
            $this->handleClose();
            return true;
        }
        return false;
    }

    protected function handleClose(): void {
        $this->emit(self::CLOSE_EVENT, $this);
    }

    protected function readOperation(Closure $callable, mixed ...$args): ?string {
        $this->assertStillConnected();
        Co::readable($this->_socket);
        $result = $callable(...$args);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    protected function writeOperation(Closure $callable, mixed ...$args): ?int {
        $this->assertStillConnected();
        Co::writable($this->_socket);
        $result = $callable(...$args);
        if ($result === false) {
            return null;
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

