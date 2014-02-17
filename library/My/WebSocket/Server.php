<?php

class My_WebSocket_Server
{

    const STATE_NEW       = 0;
    const STATE_LISTENING = 1;
    const STATE_STOPPED   = 2;

    const EVENT_SERVER_CREATED           = 'serverCreated';
    const EVENT_SERVER_LOOP_STARTED      = 'serverLoopStarted';
    const EVENT_SERVER_LOOP_STOPPED      = 'serverLoopStopped';
    const EVENT_SERVER_STOPPED           = 'serverStopped';
    const EVENT_SERVER_CLOSED_CONNECTION = 'serverClosedConnection';
    const EVENT_CLIENT_CONNECTED         = 'clientConnected';
    const EVENT_CLIENT_HANDSHAKE         = 'clientHandshake';
    const EVENT_CLIENT_RECEIVED_DATA     = 'clientReceivedData';
    const EVENT_CLIENT_CLOSED_CONNECTION = 'clientClosedConnection';

    const COMMAND_SERVER_STOP = 'WebSocketServer: STOP';

    protected $connection = false;

    protected $client;

    protected $listeners = [];

    protected $state = self::STATE_NEW;

    public function __construct($port)
    {
        $this->connection = @socket_create_listen($port);
        if (!$this->connection) {
            throw new Exception('Error! Can\'t create new WebSocket connection on port "' . $port . '"');
        }
        $this->trigger(self::EVENT_SERVER_CREATED, ['port' => $port]);
    }

    public function startLoop()
    {
        $this->state = self::STATE_LISTENING;
        $this->trigger(self::EVENT_SERVER_LOOP_STARTED);
        while ($this->state === self::STATE_LISTENING) {
            $this->client = My_WebSocket_Client::listen($this);
            $this->trigger(self::EVENT_CLIENT_CONNECTED);
//            $this->client->handshake();
            $this->trigger(self::EVENT_CLIENT_HANDSHAKE);
            $data = $this->client->getData();
            $this->trigger(self::EVENT_CLIENT_RECEIVED_DATA, ['data' => $data]);
            if ($data == self::COMMAND_SERVER_STOP) {
                $this->closeConnection();
            }
            $this->client->close();
            $this->trigger(self::EVENT_CLIENT_CLOSED_CONNECTION);
        }
        $this->trigger(self::EVENT_SERVER_LOOP_STOPPED);
    }

    protected function listen()
    {
        $this->client = socket_accept($this->connection);
    }

    public function stopLoop()
    {
        $this->state = self::STATE_STOPPED;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getState()
    {
        return $this->state;
    }

    public function addListener(My_WebSocket_ListenerAbstract $listener)
    {
        $this->listeners[] = $listener;
        return $this;
    }

    protected function trigger($event, array $additionalData = [])
    {
        if (!in_array($event, $this->getAllowedEvents())) {
            throw new Exception('Error! Unknown event "' . $event . '"');
        }
        $method = 'on' . ucfirst($event);
        foreach ($this->listeners as $listener) {
            $listener->$method($this, $additionalData);
        }
        return $this;
    }

    public static function getAllowedEvents()
    {
        return [
            self::EVENT_SERVER_CREATED,
            self::EVENT_SERVER_LOOP_STARTED,
            self::EVENT_SERVER_LOOP_STOPPED,
            self::EVENT_SERVER_STOPPED,
            self::EVENT_CLIENT_CONNECTED,
            self::EVENT_CLIENT_HANDSHAKE,
            self::EVENT_CLIENT_RECEIVED_DATA,
            self::EVENT_CLIENT_CLOSED_CONNECTION,
        ];
    }

    public function closeConnection()
    {
        $this->stopLoop();
        socket_close($this->connection);
        $this->trigger(self::EVENT_SERVER_CLOSED_CONNECTION);
    }

}