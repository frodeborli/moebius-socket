<?php
namespace Moebius\Socket;

use Charm\AbstractOptions;

class ClientOptions extends ConnectionOptions {

    /**
     * Should the client immediately connect to the target
     * address?
     */
    public bool $connect = true;

}
