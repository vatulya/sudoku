<?php

class My_WebSocket_Client
{

    const WEBSOCKET_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    protected $server;

    protected $client;

    protected $secretKey;

    protected $handshake = false;

    public static function listen(My_WebSocket_Server $server)
    {
        $client = new self($server);
        return $client;
    }

    protected function __construct(My_WebSocket_Server $server)
    {
        $this->server = $server;
        $this->client = socket_accept($this->server->getConnection());
    }

    public function handshake($data = [])
    {
        if ($this->handshake) {
            return $this;
        }
        if (empty($data)) {
            $data = $this->getData();
        }

        $key = '';
        foreach ($data as $row) {
            if (preg_match('~^Sec-WebSocket-Key: ([^\)]+)$~', $row, $key)) {
                $key = $key[1];
                break;
            }
        }
        if (empty($key)) {
            throw new RuntimeException('Error! Can\'t handshake because no secret key');
        }

        $hash = trim($key) . self::WEBSOCKET_GUID;
        $hash = sha1($hash, true);
        $hash = base64_encode($hash);

        $handshake = [];
        $handshake[] = "HTTP/1.1 101 Switching Protocols";
        $handshake[] = "Upgrade: websocket";
        $handshake[] = "Connection: Upgrade";
        $handshake[] = "Sec-WebSocket-Accept: " . $hash . "";
//        $handshake[] = "Sec-WebSocket-Protocol: chat";

        $this->sendData($handshake);

        $this->handshake = true;
        return $this;
    }

    public function close()
    {
        socket_close($this->client);
        $this->client = null;
        return $this;
    }

    public function sendData($data)
    {
        $data = implode("\r\n", $data) . "\r\n\r\n";
        socket_send($this->client, $data, strlen($data), MSG_EOR);
        return $this;
    }

    public function getData()
    {
        $data = [];
        $end = false;
        while(($input = @socket_read($this->client, 1024, PHP_NORMAL_READ)) !== false) {
            $input = trim($input);
            $data[] = $input;
            if (empty($input)) {
                if ($end) {
                    $this->handshake($data);
//                    break;
                } else {
                    $end = true;
                }
            } else {
                $end = false;
            }
        }
        $data = array_filter($data);
        return $data;
    }

}