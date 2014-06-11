<?php

if (`netstat -ntl | grep :8079`) {
    return true;
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
define('LOG_FILE', __DIR__ . '/../logs/websocket-server.log');

echo 'Start' . PHP_EOL;

$myWampHandler = new My_Wamp_Handler();
$myWampHandler->on('system_message', [new My_Wamp_Listener_System(), 'onMessage']);
$myWampHandler->on('sudoku_message', [new My_Wamp_Listener_Sudoku(), 'onMessage']);

$loop = React\EventLoop\Factory::create();

// Listen for the web server to make a ZeroMQ push after an ajax request
/** @var \React\ZMQ\SocketWrapper $pull */
$context = new React\ZMQ\Context($loop);
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
try {
    $pull->bind('tcp://127.0.0.1:8079');
} catch (\Exception $e) {
    die ('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . "\r\n");
}
$pull->on('message', array($myWampHandler, 'onServerEvent'));

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new React\Socket\Server($loop);
$webSock->listen(8080, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect

$webServer = new IoServer(
    new HttpServer(
        new WsServer(
            new Ratchet\Wamp\WampServer(
                $myWampHandler
            )
        )
    ),
    $webSock
);

$loop->run();

echo 'Finish' . PHP_EOL;
