moebius/socket
==============

An easy to use interface for performing many simultaneous socket connections.

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
