<?php
$errno = 0; $errstr = '';
$s = @stream_socket_client('tcp://smtp.gmail.com:587', $errno, $errstr, 10);
if ($s) {
    echo "CONNECTED\n";
    fclose($s);
} else {
    echo "ERROR: $errstr ($errno)\n";
}
