<?php
require(__DIR__.'/../vendor/autoload.php');

use Moebius\Socket\{Server, Connection};
use function M\{go, await, sleep};

$server = new Server('tcp://127.0.0.1:8080');
$server->open();
while (null !== ($connection = $server->accept())) {
    go(handle_connection(...), $connection);
}


function handle_connection(Connection $connection) {

    $requestLine = $connection->readLine();
    $headers = handle_connection_headers($connection);

    $connection->write(
        "HTTP/1.1 200 OK\r\n".
        "Content-Type: text/plain\r\n".
        "\r\n".
        "Hello World\r\n"
    );
    $connection->close();
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
