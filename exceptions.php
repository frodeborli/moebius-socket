<?php
namespace Moebius\Socket;

/**
 * Base exception class for moebius/socket
 */
class SocketException extends \Exception {}

/**
 * Base exception class for Moebius\Socket\Client
 */
class ClientException extends SocketException {}

/**
 * Base exception class for Moebius\Socket\Server
 */
class ServerException extends SocketException {}

/**
 * Exception class for errors related to incorrect
 * usage of the client
 */
class LogicError extends SocketException {}

/**
 * Exception when trying to perform some operation
 * that requires a connection, while not connected.
 */
class NotConnectedError extends LogicError {
    public function __construct() {
        parent::__construct("Client is not connected", 2);
    }
}

/**
 * Exception when trying to connect and the client is already
 * connected.
 */
class AlreadyConnectedError extends LogicError {
    public function __construct() {
        parent::__construct("Client is already connected", 1);
    }
}

/**
 * Exception class for errors related to networking
 * issues.
 */
class IOException extends ClientException {}

/**
 * Exception when a timeout occurs
 */
class TimeoutException extends IOException {}

/**
 * Exception when the socket is disconnected
 */
class DisconnectedException extends IOException {}

/**
 * Exception when a read operation fails
 */
class ReadException extends IOException {}

/**
 * Exception when a write operation fails
 */
class WriteException extends IOException {}
