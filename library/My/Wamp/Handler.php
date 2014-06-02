<?php
use Evenement\EventEmitter;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class My_Wamp_Handler extends EventEmitter implements WampServerInterface
{

    /**
     * @var SplObjectStorage
     */
    protected $testConnections;

    protected $subscribedTopics = [];

    public function __construct()
    {
        $this->testConnections = new SplObjectStorage();
    }

    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        if (!array_key_exists($topic->getId(), $this->subscribedTopics)) {
            $this->subscribedTopics[$topic->getId()] = $topic;
        }
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        $a = 1;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->testConnections[$conn] = $conn;
    }

    public function onClose(ConnectionInterface $conn)
    {
        unset($this->testConnections[$conn]);
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        $response = new My_Wamp_Response();
        $this->emit($topic . '_message', [$conn, $response, $params]);
        $error = isset($response->getResponse()['error']) ? $response->getResponse()['error'] : '';
        if (!empty($error)) {
            $conn->callError($id, '/', $error);
        } else {
            $conn->callResult($id, $response->getResponse());
        }
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
        $a = 1;
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $a = 1;
    }

    public function onServerEvent($data)
    {
        $data = json_decode($data, true);

        $topic = $this->subscribedTopics[$data];

        // re-send the data to all the clients subscribed to that category
        $topic->broadcast($data);
    }

}