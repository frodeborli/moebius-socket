<?php
require(__DIR__.'/../vendor/autoload.php');
require("/root/php-utils.php");

use Moebius\Socket\{Server, Connection, Client};
use function M\{go, await, sleep};

//register_tick_function(\Ennerd\trace(...));

$server = new Server('tcp://127.0.0.1:8080');

$requestCount = 50;

go(function() use ($server) {
    sleep(2);
    $server->close();
});

go(function() use ($requestCount) {
    for ($i = 0; $i < $requestCount; $i++) {
        go(function() use ($i, &$requestCount) {
            try {
                echo "Coroutine $i\n";
                echo " - $i: Connecting\n";
                $client = new Client('tcp://127.0.0.1:8080');
                if (!$client->isConnected()) {
                    echo " - $i: Failed to connect\n";
                    return;
                }
                $client->write("GET / HTTP/1.0\r\n\r\n");
                echo " - $i: Sent request\n";
                $response = $client->read(8192);
                echo " - $i: response\n";
                $client->close();
            } catch (\Throwable $e) {
                echo "FAILED REQUEST: ".$e->getMessage()."\n";
                $requestCount--;
            }
        });
        sleep(0.001);
    }
});

while (null !== ($connection = $server->accept())) {
    go(handle_connection(...), $connection);
}

echo "FINAL STATEMENT\n";


function handle_connection(Connection $connection) {
    global $server, $requestCount;

    try {
        $requestLine = $connection->readLine();
        $headers = handle_connection_headers($connection);
        sleep(0.2);
        $connection->write(
            "HTTP/1.1 200 OK\r\n".
            "Content-Type: text/plain\r\n".
            "Connection: close\r\n".
            "\r\n".
            "Hello World\r\n"
        );
        $connection->close();
    } catch (\Throwable $e) {
        echo "\nERROR ".$e->getMessage()."\n";
    }
    if (--$requestCount === 0) {
        $server->close();
    }
}

function handle_connection_headers(Connection $connection): array {
    $headers = [];

    while (!$connection->eof()) {
        $line = trim($connection->readLine());
        if (trim($line) === '') {
            // headers stop with an empty line
            return $headers;
        }
        $split = strpos($line, ":");
        if ($split === false) {
            throw new \Exception("Invalid header '$line' received");
        }
        if ($line[0] === "\t") {
            // this should be added to previous header
            $value .= "\r\n".$line;
        } else {
            unset($value);
            $name = strtolower(substr($line, 0, $split));
            if ($line[$split+1] === ' ') {
                $value = substr($line, $split+2);
            } else {
                $value = substr($line, $split+1);
            }
            $headers[$name][] = &$value;
        }
    }
}
