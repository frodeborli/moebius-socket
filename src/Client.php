<?php
namespace Moebius\Socket;

use Closure;
use Charm\Event\{EventEmitterInterface, EventEmitterTrait};
use function M\{readable, writable, go};

/**
 * A generic socket client class for working asynchronously with
 * sockets.
 */
class Client implements EventEmitterInterface {
    use EventEmitterTrait;

    public readonly string $address;
    public readonly ClientOptions $options;

    private mixed $_context = null;
    private mixed $_socket = null;

    /**
     * @param string $address An address to the socket to connect to, such as "tcp://127.0.0.1:80"
     */
    public function __construct(string $address, ClientOptions|array $options=[]) {
        $this->address = $address;
        $this->options = ClientOptions::create($options);
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
        $this->assertConnected();
        return feof($this->_socket);
    }

    public function isConnected(): bool {
        return $this->_socket !== null;
    }

    /**
     * Establish a connection
     *
     * @throws ConnectioonError
     */
    public function connect(): void {
        if ($this->isConnected()) {
            throw new AlreadyConnectedError();
        }

        $startTime = microtime(true);
        do {
            if ($this->options->timeout !== null) {
                if (microtime(true) < $startTime + $this->options->timeout) {
                    throw new TimeoutException();
                }
            }
            $errorCode = null;
            $errorMessage = null;
            $socket = stream_socket_client(
                $this->address,
                $errorCode,
                $errorMessage,
                0,
                STREAM_CLIENT_ASYNC_CONNECT,
                $this->_context
            );
            if (false === $socket) {
                if ($errorCode === 110) {
                    throw new TimeoutError($errorMessage);
                }
                throw new ConnectionError($errorMessage);
            }
        } while (!$socket);

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

    public function disconnect(): void {
        $this->assertConnected();
        if (!fclose($this->_socket)) {
            throw new IOException("fclose() call failed");
        }
    }

    private function readOperation(Closure $callable, mixed ...$args): string {
        $this->assertConnected();
        readable($this->_socket);
        $result = $callable(...$args);
        if ($result === false) {
            throw new ReadException("Reading from socket failed");
        }
        return $result;
    }

    private function writeOperation(Closure $callable, mixed ...$args): int {
        $this->assertConnected();
        writable($this->_socket);
        $result = $callable(...$args);
        if ($result === false) {
            throw new WriteException("Writing to socket failed");
        }
        return $result;
    }

    private function assertConnected(): void {
        if (!$this->isConnected()) {
            throw new NotConnectedError();
        }
        if (!is_resource($this->_socket)) {
            throw new DisconnectedException();
        }
    }

}

