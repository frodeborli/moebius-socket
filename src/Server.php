<?php
namespace Moebius\Socket;

use Closure;
use Charm\Event\{EventEmitterInterface, EventEmitterTrait};
use function M\{readable, writable, go};

/**
 * A generic socket server class for working asynchronously with
 * network connections.
 */
class Server implements EventEmitterInterface {
    use EventEmitterTrait;

    public readonly string $address;
    public readonly ServerOptions $options;

    private mixed $_context = null;
    private mixed $_socket = null;

    /**
     * @param string $address An address to the socket to connect to, such as "tcp://127.0.0.1:80"
     */
    public function __construct(string $address, ServerOptions|array $options=[]) {
        $this->address = $address;
        $this->options = ServerOptions::create($options);
        $this->_context = stream_context_create([
            'socket' => [
                'backlog' => $this->options->socket_backlog,
                'ipv6_v6only' => $this->options->socket_ipv6_v6only,
                'so_reuseport' => $this->options->socket_so_reuseport,
                'so_broadcast' => $this->options->socket_so_broadcast,
            ],
        ]);
        if ($this->options->connect) {
            $this->open();
        }
    }

    /**
     * When the socket server closes, this function will return null.
     */
    public function accept(): ?Connection {
        $this->assertOpen();
        if ($this->_socket && !is_resource($this->_socket)) {
            $this->_socket = null;
            return null;
        }
        $peerName = null;
        while (is_resource($this->_socket)) {
            readable($this->_socket);

            // socket may have been closed by a call to $this->close() or an error
            if (is_resource($this->_socket)) {
                $socket = stream_socket_accept($this->_socket, 0, $peerName);
                if ($socket) {
                    return new Connection($socket, $peerName);
                }
            }
        }
        return null;
    }

    /**
     * Open the socket server
     *
     * @throws ConnectioonError
     */
    public function open(): void {
        if ($this->isOpen()) {
            throw new AlreadyConnectedError($this->address.' is already opened');
        }
        $errorCode = null;
        $errorMessage = null;
        $socket = stream_socket_server(
            $this->address,
            $errorCode,
            $errorMessage,
            $this->options->serverFlags,
            $this->_context
        );

        if (false === $this->_socket) {
            throw new ConnectionError($errorMessage, $errorCode);
        }

        $this->_socket = $socket;
    }

    public function close(): void {
        if (!$this->isOpen()) {
            throw new NotConnectedError($this->address.' is already closed');
        }
        fclose($this->_socket);
    }

    public function isOpen(): bool {
        return $this->_socket !== null;
    }

    private function assertOpen(): void {
        if (!$this->isOpen()) {
            throw new NotOpenedError();
        }
        if (!is_resource($this->_socket)) {
            throw new ClosedException();
        }
    }

}

