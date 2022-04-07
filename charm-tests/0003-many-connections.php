<?php
require(__DIR__.'/../vendor/autoload.php');

use Moebius\Socket\Client;
use function M\{go, await, sleep};

function make_connection(string $address) {
    $client = new Client($address);
    $client->connect();
    sleep(1);
    $client->write("GET / HTTP/1.0\r\n\r\n");
    return $client->readAll();
}

$time = microtime(true);

echo <<<EOT
    Making 1900 connections to localhost port 80, then for each
    connection WE WAIT 1 SECOND before we send the request
    EOT;

$results = [];
for ($i = 0; $i < 1900; $i++) {
    $results[] = go(make_connection(...), 'tcp://127.0.0.1:80');
}

echo "Checking that we have all the results:\n";
foreach ($results as $number => $future) {
    try {
        await($future);
    } catch (\Throwable $e) {
        echo "Request $number failed\n";
    }
}

echo "Finished in ".((microtime(true) - $time)-1)." seconds (removing the 1 second wait)\n";
