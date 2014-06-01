<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
define('LOG_FILE', __DIR__ . '/../logs/websocket-events-listener.log');

echo 'Start' . PHP_EOL;

$context = new ZMQContext();
$socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'my pusher');
$socket->connect("tcp://localhost:8079");

$socket->send(json_encode(['test', 'test123']));

echo 'Finish' . PHP_EOL;
