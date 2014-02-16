<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
    define('LOG_FILE', __DIR__ . '/../logs/game-socket.log');

$socket = @socket_create_listen(9900);
if (!$socket) {
    print "Failed to create socket!\n";
    exit;
}

echo 'Start' . PHP_EOL;

while (true) {
    $client = socket_accept($socket);
    $welcome = "\nWelcome\n";
    socket_write($client, $welcome);

    while(($input = socket_read($client, 1024, PHP_NORMAL_READ)) !== false) {
        $input = trim($input);
        echo $input . PHP_EOL;
        file_put_contents(LOG_FILE, 'INPUT: ' . $input, FILE_APPEND);
    }

    socket_close ($client);
}

socket_close ($socket);


echo 'Finish' . PHP_EOL;
