<?php
require(__DIR__.'/../vendor/autoload.php');
require("/root/php-utils.php");

use Moebius\Socket\{Server, Connection, Client};
use function M\{go, await, sleep};

//register_tick_function(\Ennerd\trace(...));

$server = new Server('tcp://127.0.0.1:8080');

while (null !== ($connection = $server->accept())) {
    go(handle_connection(...), $connection);
}

function handle_connection(Connection $connection) {
    global $server;

    $requestLine = $connection->readLine();
    $headers = handle_connection_headers($connection);
    $connection->write(
        "HTTP/1.1 200 OK\r\n".
        "Content-Type: text/plain\r\n".
        "Connection: close\r\n".
        "\r\n".
        "Hello World\r\n"
    );
    sleep(mt_rand(1,1000)/200);
    $connection->close();
}

function handle_connection_headers(Connection $connection): array {
    $headers = [];

    $chunk = $connection->readAll();
    return [];

    foreach(explode("\r\n", $connection->readLine(65536, "\r\n\r\n")) as $line) {
        if ($line === '') {
            echo "EMPTY LINE\n";
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

    return $headers;
}
