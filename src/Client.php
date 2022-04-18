<?php
namespace Moebius\Socket;

use Closure;
use Charm\Event\{EventEmitterInterface, EventEmitterTrait};
use Moebius\Coroutine as Co;

/**
 * A generic socket client class for working asynchronously with
 * network connections.
 */
class Client extends AbstractConnection {
    use EventEmitterTrait;

    public readonly string $address;

    /**
     * @param string $address An address to the socket to connect to, such as "tcp://127.0.0.1:80"
     */
    public function __construct(string $address, ClientOptions|array $options=[]) {
        $this->address = $address;
        parent::__construct(ClientOptions::create($options));

        if ($this->options->connect) {
            $this->connect();
        }
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
        $retries = $this->options->retries;
        do {
            if ($this->options->timeout !== null) {
                if (microtime(true) < $startTime + $this->options->timeout) {
                    throw new TimeoutException();
                }
            }
            $errorCode = null;
            $errorMessage = null;
            $socket = @stream_socket_client(
                $this->address,
                $errorCode,
                $errorMessage,
                0,
                STREAM_CLIENT_ASYNC_CONNECT,
                $this->_context
            );
            if (false === $socket) {
                if ($errorCode === 0) {
                    // PHP may be unable to create any more connections
                    Co::sleep(0.1);
                } elseif ($errorCode === 110) {
                    throw new TimeoutError($errorMessage);
                }
            }
        } while (!$socket && --$retries > 0);

        if (!$socket) {
            throw new ConnectionError($errorMessage, $errorCode);
        }


        $this->setSocket($socket);
    }
}
