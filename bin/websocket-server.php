<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
define('LOG_FILE', __DIR__ . '/../logs/websocket-server.log');

echo 'Start' . PHP_EOL;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

//$myServer = new My_WebSocket_Server();
//$myServer->addListener(new My_WebSocket_Listener_Sudoku(), 'sudoku');
$myWampHandler = new My_Wamp_Handler();
$myWampHandler->on('sudoku_message', [new My_WebSocket_Listener_Sudoku(), 'onMessage']);

$loop = React\EventLoop\Factory::create();

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new React\ZMQ\Context($loop);
/** @var \React\ZMQ\SocketWrapper $pull */
$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:8079'); // Binding to 127.0.0.1 means the only client that can connect is itself
$pull->on('message', array($myWampHandler, 'onServerEvent'));

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new React\Socket\Server($loop);
$webSock->listen(8080, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
//$webSock->on('connection', [$myWampHandler, 'onOpen']);

$webServer = new IoServer(
    new HttpServer(
        new WsServer(
            //$myServer
            new Ratchet\Wamp\WampServer(
                $myWampHandler
            )
        )
    ),
    $webSock
);

$loop->run();
/****************/



//use Ratchet\Server\IoServer;
//use Ratchet\Http\HttpServer;
//use Ratchet\WebSocket\WsServer;
//
//$myServer = new My_WebSocket_Server();
//$myServer->addListener(new My_WebSocket_Listener_Sudoku(), 'sudoku');
//
//$server = IoServer::factory(
//    new HttpServer(
//        new WsServer(
//            $myServer
//        )
//    ),
//    8080
//);
//
//$server->run();


echo 'Finish' . PHP_EOL;
