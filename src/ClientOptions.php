<?php
namespace Moebius\Socket;

class ClientOptions extends ConnectionOptions {

    /**
     * Should the client immediately connect to the target
     * address?
     */
    public bool $connect = true;

    /**
     * Error 0 generally means that PHP is unable to create
     * more sockets. In that case Client can wait 0.1 seconds
     * and retry the connection. This number limits how many
     * times to try.
     */
    public int $retries = 10;

}
