<?php
/*
    Goal: Find a way to check if reading a stream resource would block.

    Notes:

    * streams may encapsulate sockets and also other types of streams
      "fast" and "slow" streams

    * either socket_import_stream($fp) is a socket or it returns FALSE

*/

if (file_exists($fn = '/tmp/read-block-test')) {
    unlink($fn);
}
posix_mkfifo($fn, 0600);

$pid = pcntl_fork();

if ($pid === -1) {
    die("Could not fork\n");
} elseif ($pid === 0) {
//    echo "CHILD: Sleep 4 seconds\n";

    sleep(2);
//    echo "CHILD: Opening $fn\n";
    $fp = fopen($fn, 'w');
//    echo "CHILD: done\n";

    for ($i = 0; $i < 10; $i++) {
        usleep(1500000);
//        echo "CHILD: Writing 'Hello $i' to $fn\n";
        fwrite($fp, "Hello sir $i\n");
        fdatasync($fp);
echo "CHILD: wrote some bytes\n";
//        echo "CHILD: done\n";
    }

    sleep(4);
//    echo "CHILD: Closing $fn\n";
    fclose($fp);
//    echo "CHILD: done\n";

    sleep(1);
    die("CHILD: exit\n");
}
register_shutdown_function(function() use ($pid, $fn) {
    echo "PARENT: shutting down\n";
    $res = pcntl_waitpid($pid, $status);
    unlink($fn);
});

echo "PARENT: opening $fn\n";

$fp = fopen($fn, 'r');
stream_set_blocking($fp, false);

do {
    $t = hrtime(true);
    $readable = StreamTool::readable($fp);
    stream_set_timeout($fp, 0, 50000);
    $t = hrtime(true) - $t;
    echo "StreamTool::readable readable=".json_encode($readable)." (".json_encode(stream_select_readable($fp)).") time=".($t/1000000000)."\n";
    if ($readable) {
        echo "PARENT: reading chunk\n";
        $t = hrtime(true);
        $chunk = stream_get_contents($fp);
        $t = hrtime(true) - $t;
        if ($t > 10000000) {
            echo "!!!!!!!!!!!!!!!!!! BLOCKED ".($t/1000000000)."!\n";
        } else {
            echo "------------------ NON BLOCKING!\n";
        }
        echo "read chunk=".json_encode($chunk)."\n";
    }
    usleep(100000);
} while (true);

echo "PARENT: closing $fp\n";
fclose($fp);
echo "PARENT: exit\n";
exit(0);

function stream_select_readable(mixed $fp): bool {
    return false;
    $rs = [ $fp ];
    $void = [];
    return 1 === stream_select($rs, $void, $void, 0, 0);
}

/**
 * Class helps manage streams which stream_select() will not poll. This
 * is done by attempting to read the stream and then - if read succeeds,
 * we'll notify you that the stream is readable.
 */
class StreamTool extends \php_user_filter {

    public static function readable($stream): bool {
        $streamId = get_resource_id($stream);
        $meta = stream_get_meta_data($stream);

        $rs = [ $stream ];
        $void = [];

        // TODO: handle errors here
        $count = stream_select($rs, $void, $void, 0, 0);
        if ($count > 0) {
echo "fast true\n";
            return true;
        } elseif ($count === 0) {
echo "fast false\n";
            return false;
        }

        if ($meta['stream_type'] !== 'STDIO') {
            throw new \Exception("A socket stream is not supported");
        }

        if ($meta['blocked']) {
            throw new \Exception("Can poll on non-blocking streams when working with file descriptors greater than 1024");
        }

        if ($meta['unread_bytes'] > 0) {
            return true;
        }

        $chunk = stream_get_contents($stream, 8192);
        if ($chunk === '' || $chunk === false) {
            return false;
        }
        self::unread($stream, $chunk);
        return true;
    }

    private static function unread($stream, string $buffer): void {
        self::register();
        $f = stream_filter_append($stream, 'streamtool', STREAM_FILTER_READ, $buffer);
        stream_filter_remove($f);
    }

    public function onCreate(): bool {
        return true;
    }

    public function filter($in, $out, &$consumed, $closing) {
        $returnedData = false;
        while ($this->params !== '') {
            $chunk = substr($this->params, 0, 8192);
            $length = strlen($chunk);
            $this->params = substr($this->params, $length);
            $bucket = stream_bucket_new($this->stream, $chunk);
            stream_bucket_append($out, $bucket);
            $returnedData = true;
        }
        while ($bucket = stream_bucket_make_writeable($in)) {
            $returnedData = true;
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        if ($returnedData) {
            return PSFS_PASS_ON;
        } else {
            return PSFS_FEED_ME;
        }
    }

    private static function register(): void {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;
        stream_filter_register('streamtool', self::class);
    }
}
