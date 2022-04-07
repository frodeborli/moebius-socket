<?php
namespace Moebius\Socket;

use Closure;
use Charm\Event\{EventEmitterInterface, EventEmitterTrait};
use function M\{readable, writable, go};

/**
 * A class for working with incoming connections
 */
class Connection extends AbstractConnection {

    protected ?string $_peerName = null;

    public function __construct($socket, string $peerName=null, array|ConnectionOptions $options=[]) {
        $this->_socket = $socket;
        $this->_peerName = $peerName;
        parent::__construct($options);
    }

}
