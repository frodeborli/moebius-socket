<?php
require(__DIR__.'/../vendor/autoload.php');

use Moebius\Socket\Client;
use function M\{go, await};

// creating the instances is non-blocking
$clientA = new Client('tcp://www.dagbladet.no:443');
$clientB = new Client('tcp://www.vg.no:443');

// writing to the sockets is non-blocking

$clientA->write("GET / HTTP/1.0\r\n\r\n");
$clientB->write("GET / HTTP/1.0\r\n\r\n");

// reading from the sockets is non-blocking

$responseFromA = go(function() use ($clientA) {
    $buffer = '';
    while (!$clientA->eof()) {
        $chunk = $clientA->read(4096);
        echo "Received ".strlen($chunk)." bytes from A\n";
        $buffer .= $chunk;
    }
    return $buffer;
});

$responseFromB = go(function() use ($clientB) {
    $buffer = '';
    while (!$clientB->eof()) {
        $chunk = $clientB->read(4096);
        echo "Received ".strlen($chunk)." bytes from A\n";
        $buffer .= $chunk;
    }
    return $buffer;
});

// since the above requests are asynchronous, you have to wait for them
echo "TOTAL FOR B: ".strlen(await($responseFromB))." bytes\n";
echo "TOTAL FOR A: ".strlen(await($responseFromA))." bytes\n";
