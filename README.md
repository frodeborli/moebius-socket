moebius/socket
==============

An easy to use interface for working with many simultaneous and non-blocking 
network connections. The library efficiently handles up to 1024 concurrent
connections with a default PHP 8.1 installation per server instance.

If you need more than 1024 connections, you can run multiple instances of the
server or use a more advanced event loop implementation.

Note: This library technically works via PHP streams, not the lower level sockets
functionality of PHP.


Architecture
------------

This library provides three core classes which you can use to create network
clients or server implementations.

 * `Moebius\Socket\Client` provides an API to the `stream_socket_client()` function
   in PHP, and can be used to create concurrent HTTP clients.

 * `Moebius\Socket\Server` provides an API to the `stream_socket_server()` function,
   and lets you create servers that accepts connections and gives you connection
   instances for every new client that connects.

 * `Moebius\Socket\Connection` is similar to the Client class, but are created by
   a server.

Example client
--------------

```php
use Moebius\Socket\Client;

/**
 * *** COROUTINE 1 ***
 */
$google = go(function() {
    $client = new Client('tcp://www.google.com:80');
    $client->write("GET / HTTP/1.0\r\n\r\n");
    while (!$client->eof()) {
        echo "< ".$client->readLine()."\n";
    }
});
/**
 * *** COROUTINE 2 ***
 */
$bing = go(function() {
    $client = new Client('tcp://www.bing.com:80');
    $client->write("GET / HTTP/1.0\r\n\r\n");
    while (!$client->eof()) {
        echo "< ".$client->readLine()."\n";
    }
});


/**
 * *** AWAIT BOTH COROUTINES ***
 */
await($google);
await($bing);
```

Example server
--------------

```php
use Moebius\Socket\Server;

$server = new Server('tcp://0.0.0.0:8080');
while ($connection = $server->accept()) {

    /**
     * *** LAUNCH A COROUTINE PER CONNECTION ***
     */
    go(function() use ($connection) {

        $requestLine = $connection->readLine();

        do {
            $header = $connection->readLine();
        } while ($header !== '');

        $connection->write(<<<RESPONSE
            HTTP/1.0 200 OK\r
            Content-Type: text/plain\r
            \r
            Hello World!
            RESPONSE);

        $connection->close();
            
    });
}
```

Complete working example
------------------------

To issue multiple concurrent requests, you simply wrap run your code via the
`M\go(callable $callbacl)` function. This way you can perform a lot of requests
at the same time.


```php
<?php
require(__DIR__.'/../vendor/autoload.php');

use Moebius\Socket\Client;
use function M\{go, await};

// Asynchronous operation requires that you call via the `go()` function.

$futureBufferA = go(function() {

    $client = new Client('tcp://www.dagbladet.no:80');
    $client->connect();
    $client->write("GET / HTTP/1.0\r\n\r\n");

    $buffer = ''

    while (!$client->eof()) {
        $buffer .= $client->read(4096);
    }

    return $buffer;
});

$futureBufferA = go(function() {

    $client = new Client('tcp://www.vg.no:80');
    $client->connect();
    $client->write("GET / HTTP/1.0\r\n\r\n");

    $buffer = ''

    while (!$client->eof()) {
        $buffer .= $client->read(4096);
    }

    return $buffer;
});


// note that it does not matter which order you await each of these buffers
$bufferA = await( $futureBufferA );
$bufferB = await( $futureBufferB );

echo "Received ".strlen($bufferA)." bytes from client A and ".strlen($bufferB)." bytes from client B\n";
```


API
---

 * `Client::__construct(string $address, ClientOptions|array $options=[])`. Creates an instance of the
   socket client, but does not perform any network operations.

 * `Client::connect()`. Non-blocking connect to server. (The DNS lookup operation may block at the moment)

 * `Client::disconnect()`. Close the connection.

 * `Client::read(int $length): string`. Read `$length` bytes from the server. You may want to perform this
   via the `M\go(callable $coroutine)` function if you want to issue multiple requests
