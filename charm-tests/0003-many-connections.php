<?php
require(__DIR__.'/../vendor/autoload.php');

use Moebius\Socket\Client;
use function M\{go, await, sleep};

$connectionCount = getenv('CONNECTIONS') ? getenv('CONNECTIONS') : 500;

function make_connection(string $address) {
    $client = new Client($address);
    sleep(0.5);
    $client->write("GET / HTTP/1.0\r\n\r\n");
    return $client->readAll();
}

$time = microtime(true);

echo <<<EOT
    Making $connectionCount connections to localhost on port
    80, then for each connection WE WAIT 0.5 SECONDS before
    we send the request.

    EOT;

$results = [];
for ($i = 0; $i < $connectionCount; $i++) {
    $results[] = go(make_connection(...), 'tcp://127.0.0.1:80');
}

echo "Checking that we have all the results:\n";
$bytesReceived = 0;
foreach ($results as $number => $future) {
    try {
        $bytesReceived += strlen(await($future));
    } catch (\Throwable $e) {
        echo "Request $number failed: ".$e->getMessage()." ".$e->getCode()." ".$e->getFile().":".$e->getLine()."\n";
        
    }
}

echo "Received $bytesReceived bytes in ".((microtime(true) - $time))." seconds\n";

