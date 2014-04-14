<?php

class My_WebSocket_User
{

    protected $socket;
    protected $id;
    protected $headers = [];
    protected $handshake = false;

    protected $cookies;
    protected $sessionId;

    public $handlingPartialPacket = false;
    public $partialBuffer = "";

    public $sendingContinuous = false;
    public $partialMessage = "";

    public $hasSentClose = false;

    public function __construct($id, $socket)
    {
        $this->id     = $id;
        $this->socket = $socket;
    }

    /**
     * @param string $handshake
     */
    public function setHandshake($handshake)
    {
        $this->handshake = $handshake;
    }

    /**
     * @return string|boolean
     */
    public function getHandshake()
    {
        return $this->handshake;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $socket
     */
    public function setSocket($socket)
    {
        $this->socket = $socket;
    }

    /**
     * @return mixed
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param mixed $cookies
     */
    public function setCookies($cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * @return mixed
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param string $key
     * @return mixed $value
     */
    public function getCookie($key)
    {
        return array_key_exists($key, $this->cookies) ? $this->cookies[$key] : null;
    }

    /**
     * @param string $sessionId
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

}