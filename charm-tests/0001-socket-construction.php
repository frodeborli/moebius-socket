<?php
require(__DIR__.'/../vendor/autoload.php');

use function M\{go, suspend};
use Moebius\Socket\{Client, ClientOptions};

$startTime = microtime(true);
$done = false;
$count = 0;

go(function() use (&$done, &$count) {
    while (!$done) {
        $count++;
        suspend();
    }
});

$startTime = microtime(true);


$client = new Client('tcp://127.0.0.1:80');
echo "> Client constructed\n";

$client->write("GET / HTTP/1.0\r\n\r\n");
echo "> Request sent\n";

while (!$client->eof()) {
    echo "response: ".$client->readLine()."\n";
}

echo "> EOF reached\n";

$client->close();

echo "> Disconnected\n";

$done = true;

if ($count > 4) {
    echo "Coroutines did run\n";
} else {
    echo "ERROR: coroutine ran $count times\n";
}
fwrite(STDERR, "Total time: ".(microtime(true)-$startTime)."\n");
