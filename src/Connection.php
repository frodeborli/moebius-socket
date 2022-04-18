<?php
namespace Moebius\Socket;

use Closure;
use Charm\Event\{EventEmitterInterface, EventEmitterTrait};
use Moebius\Coroutine as Co;

/**
 * A class for working with incoming connections
 */
class Connection extends AbstractConnection {

    readonly public ?string $peerName;

    public function __construct($socket, string $peerName=null, array|ConnectionOptions $options=[]) {
        parent::__construct(ConnectionOptions::create($options));

        stream_set_blocking($socket, false);
        $this->setSocket($socket);
        $this->peerName = $peerName;
    }

}
