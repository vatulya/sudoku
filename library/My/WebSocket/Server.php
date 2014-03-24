<?php

class My_WebSocket_Server
{

    const BUFFER_LENGTH = 2048;

    const WEBSOCKET_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    const EVENT_SERVER_CREATED           = 'serverCreated';
    const EVENT_SERVER_LOOP_STARTED      = 'serverLoopStarted';
    const EVENT_SERVER_LOOP_STOPPED      = 'serverLoopStopped';
    const EVENT_SERVER_STOPPED           = 'serverStopped';
    const EVENT_SERVER_CLOSED_CONNECTION = 'serverClosedConnection';
    const EVENT_CLIENT_CONNECTED         = 'clientConnected';
    const EVENT_CLIENT_HANDSHAKE         = 'clientHandshake';
    const EVENT_CLIENT_RECEIVED_DATA     = 'clientReceivedData';
    const EVENT_CLIENT_CLOSED_CONNECTION = 'clientClosedConnection';

    protected $master;
    protected $sockets = [];
    /**
     * @var My_WebSocket_User[]
     */
    protected $users = [];

    protected $headerOriginRequired = false;
    protected $headerSecWebSocketProtocolRequired = false;
    protected $headerSecWebSocketExtensionsRequired = false;

    /**
     * @var My_WebSocket_Logger_Abstract[]
     */
    protected $loggers = [];
    /**
     * @var My_WebSocket_Listener_Abstract[]
     */
    protected $listeners = [];

    /**
     * @param int $port
     * @throws Exception
     */
    public function __construct($port)
    {
        $start = time();
        $timeToTries = 120; // 2 mins
        $secs = 0;
        while ($secs < $timeToTries) {
            $this->master = @socket_create_listen($port);
            if ($this->master) {
                break;
            }
            $secs = time() - $start;
        }
        if (!$this->master) {
            $this->fatal('Error! Can\'t create new WebSocket connection on port "' . $port . '"');
            throw new Exception('Error! Can\'t create new WebSocket connection on port "' . $port . '"');
        }
        $this->sockets[] = $this->master;
        $this->trigger(self::EVENT_SERVER_CREATED);
    }

    public function __destruct()
    {
        $this->trigger(self::EVENT_SERVER_STOPPED);
    }

    /**
     * Main processing loop
     */
    public function run()
    {
        $this->trigger(self::EVENT_SERVER_LOOP_STARTED);
        while (true) {
            if (empty($this->sockets)) {
                $this->sockets[] = $this->master;
            }
            $read  = $this->sockets;
            $write = $except = null;
            @socket_select($read, $write, $except, null);
            foreach ($read as $socket) {
                if ($socket == $this->master) {
                    $client = socket_accept($socket);
                    if ($client < 0) {
                        $this->trigger(self::EVENT_SERVER_CLOSED_CONNECTION);
                        continue;
                    } else {
                        $this->connect($client);
                    }
                } else {
                    $numBytes = @socket_recv($socket, $buffer, self::BUFFER_LENGTH, 0);
                    if ($numBytes === false) {
                    } elseif ($numBytes == 0) {
                        $this->disconnect($socket);
                    } else {
                        $user = $this->getUserBySocket($socket);
                        $this->trigger(self::EVENT_CLIENT_CONNECTED, ['client' => $user]);
                        if (!$user->getHandshake()) {
                            $tmp = str_replace("\r", '', $buffer);
                            if (strpos($tmp, "\n\n") === false) {
                                continue; // If the client has not finished sending the header, then wait before sending our upgrade response.
                            }
                            $this->doHandshake($user, $buffer);
                        } else {
                            if (($message = $this->deframe($buffer, $user)) !== false) {
                                if ($user->hasSentClose) {
                                    $this->disconnect($user->getSocket());
                                } else {
                                    $this->process($user, $message); // todo: Re-check this.  Should already be UTF-8.
                                }
                            } else {
                                do {
                                    $numByte = @socket_recv($socket, $buffer, self::BUFFER_LENGTH, MSG_PEEK);
                                    if ($numByte > 0) {
                                        $numByte = @socket_recv($socket, $buffer, self::BUFFER_LENGTH, 0);
                                        if (($message = $this->deframe($buffer, $user)) !== false) {
                                            if ($user->hasSentClose) {
                                                $this->disconnect($user->getSocket());
                                            } else {
                                                $this->process($user, $message);
                                            }
                                        }
                                    }
                                } while ($numByte > 0);
                            }
                        }
                    }
                }
            }
        }
        $this->trigger(self::EVENT_SERVER_LOOP_STOPPED);
    }

    /**
     * @param My_WebSocket_User $user
     * @param string $message
     */
    protected function process($user, $message)
    {
        $this->trigger(self::EVENT_CLIENT_RECEIVED_DATA, ['client' => $user, 'data' => $message]);
    }

    /**
     * @param My_WebSocket_User $user
     * @param string $message
     */
    protected function send($user, $message)
    {
        // TODO: check this method
        //$this->stdout("> $message");
        $message = $this->frame($message, $user);
        $result  = @socket_write($user->getSocket(), $message, strlen($message));
    }

    /**
     * @param $socket
     */
    protected function connect($socket)
    {
        $user = new My_WebSocket_User(uniqid(), $socket);
        $this->users[] = $user;
        $this->sockets[] = $socket;
        $this->trigger(self::EVENT_CLIENT_CONNECTED, ['client' => $user]);
    }

    /**
     * @param $socket
     */
    protected function disconnect($socket)
    {
        $foundUser = $disconnectedUser = $foundSocket = null;

        foreach ($this->users as $key => $user) {
            if ($user->getSocket() == $socket) {
                $foundUser        = $key;
                $disconnectedUser = $user;
                break;
            }
        }
        if ($foundUser !== null) {
            /** @var My_WebSocket_User $disconnectedUser */
            unset($this->users[$foundUser]);
            $this->users = array_values($this->users);
            $message     = $this->frame('', $disconnectedUser, 'close');
            @socket_write($disconnectedUser->getSocket(), $message, strlen($message));
        }
        foreach ($this->sockets as $key => $sock) {
            if ($sock == $socket) {
                $foundSocket = $key;
                break;
            }
        }
        if ($foundSocket !== null) {
            unset($this->sockets[$foundSocket]);
            $this->sockets = array_values($this->sockets);
            $this->trigger(self::EVENT_CLIENT_CLOSED_CONNECTION, ['client' => $socket]);
        }

    }

    /**
     * @param My_WebSocket_User $user
     * @param string $buffer
     */
    protected function doHandshake(My_WebSocket_User $user, $buffer)
    {
        $headers = [];
        $lines   = explode("\n", $buffer);
        foreach ($lines as $line) {
            if (strpos($line, ":") !== false) {
                $header = explode(":", $line, 2);
                $headers[strtolower(trim($header[0]))] = trim($header[1]);
            } elseif (stripos($line, "get ") !== false) {
                preg_match("/GET (.*) HTTP/i", $buffer, $reqResource);
                $headers['get'] = trim($reqResource[1]);
            }
        }
        if (isset($headers['get'])) {
            $user->requestedResource = $headers['get'];
        } else {
            $handshakeResponse = "HTTP/1.1 405 Method Not Allowed\r\n\r\n";
        }
        if (!isset($headers['host'])) {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }
        if (!isset($headers['upgrade']) || strtolower($headers['upgrade']) != 'websocket') {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }
        if (!isset($headers['connection']) || strpos(strtolower($headers['connection']), 'upgrade') === false) {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }
        if (!isset($headers['sec-websocket-key'])) {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        } else {
        }
        if (!isset($headers['sec-websocket-version']) || strtolower($headers['sec-websocket-version']) != 13) {
            $handshakeResponse = "HTTP/1.1 426 Upgrade Required\r\nSec-WebSocketVersion: 13";
        }
        if (($this->headerOriginRequired && !isset($headers['origin'])) || ($this->headerOriginRequired && !$this->checkOrigin($headers['origin']))) {
            $handshakeResponse = "HTTP/1.1 403 Forbidden";
        }
        if (($this->headerSecWebSocketProtocolRequired && !isset($headers['sec-websocket-protocol'])) || ($this->headerSecWebSocketProtocolRequired && !$this->checkWebsocProtocol($headers['sec-websocket-protocol']))) {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }
        if (($this->headerSecWebSocketExtensionsRequired && !isset($headers['sec-websocket-extensions'])) || ($this->headerSecWebSocketExtensionsRequired && !$this->checkWebsocExtensions($headers['sec-websocket-extensions']))) {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }

        // Done verifying the _required_ headers and optionally required headers.

        if (isset($handshakeResponse)) {

            // TODO: sendData
            socket_write($user->getSocket(), $handshakeResponse, strlen($handshakeResponse));
            $this->disconnect($user->getSocket());

            return;
        }

        $user->setHeaders($headers);
        $user->setHandshake($buffer);

        $webSocketKeyHash = sha1($headers['sec-websocket-key'] . self::WEBSOCKET_GUID);

        $rawToken = "";
        for ($i = 0; $i < 20; $i++) {
            $rawToken .= chr(hexdec(substr($webSocketKeyHash, $i * 2, 2)));
        }
        $handshakeToken = base64_encode($rawToken) . "\r\n";

        $subProtocol = (isset($headers['sec-websocket-protocol'])) ? $this->processProtocol($headers['sec-websocket-protocol']) : "";
        $extensions  = (isset($headers['sec-websocket-extensions'])) ? $this->processExtensions($headers['sec-websocket-extensions']) : "";

        $handshakeResponse = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $handshakeToken$subProtocol$extensions\r\n";
        // TODO: sendData
        socket_write($user->getSocket(), $handshakeResponse, strlen($handshakeResponse));

        $this->trigger(self::EVENT_CLIENT_HANDSHAKE, ['client' => $user]);
    }

    /**
     * @param $socket
     * @return My_WebSocket_User|null
     */
    protected function getUserBySocket($socket)
    {
        foreach ($this->users as $user) {
            /** @var My_WebSocket_User $user */
            if ($user->getSocket() == $socket) {
                return $user;
            }
        }

        return null;
    }

    /******************************** OVERWRITE OR EMPTY METHODS ***************************/

    /**
     * @param string $hostName
     * @return bool
     */
    protected function checkHost($hostName)
    {
        return true; // Override and return false if the host is not one that you would expect.
        // Ex: You only want to accept hosts from the my-domain.com domain,
        // but you receive a host from malicious-site.com instead.
    }

    /**
     * @param string $origin
     * @return bool
     */
    protected function checkOrigin($origin)
    {
        return true; // Override and return false if the origin is not one that you would expect.
    }

    /**
     * @param string $protocol
     * @return bool
     */
    protected function checkWebsocProtocol($protocol)
    {
        return true; // Override and return false if a protocol is not found that you would expect.
    }

    /**
     * @param string $extensions
     * @return bool
     */
    protected function checkWebsocExtensions($extensions)
    {
        return true; // Override and return false if an extension is not found that you would expect.
    }

    /**
     * @param string $protocol
     * @return string
     */
    protected function processProtocol($protocol)
    {
        return ""; // return either "Sec-WebSocket-Protocol: SelectedProtocolFromClientList\r\n" or return an empty string.
        // The carriage return/newline combo must appear at the end of a non-empty string, and must not
        // appear at the beginning of the string nor in an otherwise empty string, or it will be considered part of
        // the response body, which will trigger an error in the client as it will not be formatted correctly.
    }

    /**
     * @param string $extensions
     * @return string
     */
    protected function processExtensions($extensions)
    {
        return ""; // return either "Sec-WebSocket-Extensions: SelectedExtensions\r\n" or return an empty string.
    }


    /************************* SYSTEM METHODS *********************/

    /**
     * @param string $message
     * @param My_WebSocket_User $user
     * @param string $messageType
     * @param bool $messageContinues
     * @return string
     */
    protected function frame($message, $user, $messageType = 'text', $messageContinues = false)
    {
        $b1 = 0;
        switch ($messageType) {
            case 'continuous':
                $b1 = 0;
                break;
            case 'text':
                $b1 = ($user->sendingContinuous) ? 0 : 1;
                break;
            case 'binary':
                $b1 = ($user->sendingContinuous) ? 0 : 2;
                break;
            case 'close':
                $b1 = 8;
                break;
            case 'ping':
                $b1 = 9;
                break;
            case 'pong':
                $b1 = 10;
                break;
        }
        if ($messageContinues) {
            $user->sendingContinuous = true;
        } else {
            $b1 += 128;
            $user->sendingContinuous = false;
        }

        $length      = strlen($message);
        $lengthField = "";
        if ($length < 126) {
            $b2 = $length;
        } elseif ($length <= 65536) {
            $b2        = 126;
            $hexLength = dechex($length);
            //$this->stdout("Hex Length: $hexLength");
            if (strlen($hexLength) % 2 == 1) {
                $hexLength = '0' . $hexLength;
            }
            $n = strlen($hexLength) - 2;

            for ($i = $n; $i >= 0; $i = $i - 2) {
                $lengthField = chr(hexdec(substr($hexLength, $i, 2))) . $lengthField;
            }
            while (strlen($lengthField) < 2) {
                $lengthField = chr(0) . $lengthField;
            }
        } else {
            $b2        = 127;
            $hexLength = dechex($length);
            if (strlen($hexLength) % 2 == 1) {
                $hexLength = '0' . $hexLength;
            }
            $n = strlen($hexLength) - 2;

            for ($i = $n; $i >= 0; $i = $i - 2) {
                $lengthField = chr(hexdec(substr($hexLength, $i, 2))) . $lengthField;
            }
            while (strlen($lengthField) < 8) {
                $lengthField = chr(0) . $lengthField;
            }
        }

        return chr($b1) . chr($b2) . $lengthField . $message;
    }

    /**
     * @param string $message
     * @param My_WebSocket_User $user
     * @return bool|int|string
     */
    protected function deframe($message, &$user)
    {
        //echo $this->strtohex($message);
        $headers   = $this->extractHeaders($message);
        $pongReply = false;
        $willClose = false;
        switch ($headers['opcode']) {
            case 0:
            case 1:
            case 2:
                break;
            case 8:
                // todo: close the connection
                $user->hasSentClose = true;

                return "";
            case 9:
                $pongReply = true;
            case 10:
                break;
            default:
                //$this->disconnect($user); // todo: fail connection
                $willClose = true;
                break;
        }

        if ($user->handlingPartialPacket) {
            $message                     = $user->partialBuffer . $message;
            $user->handlingPartialPacket = false;

            return $this->deframe($message, $user);
        }

        if ($this->checkRSVBits($headers, $user)) {
            return false;
        }

        if ($willClose) {
            // todo: fail the connection
            return false;
        }

        $payload = $user->partialMessage . $this->extractPayload($message, $headers);

        if ($pongReply) {
            $reply = $this->frame($payload, $user, 'pong');
            socket_write($user->getSocket(), $reply, strlen($reply));

            return false;
        }
        if (extension_loaded('mbstring')) {
            if ($headers['length'] > mb_strlen($payload)) {
                $user->handlingPartialPacket = true;
                $user->partialBuffer         = $message;

                return false;
            }
        } else {
            if ($headers['length'] > strlen($payload)) {
                $user->handlingPartialPacket = true;
                $user->partialBuffer         = $message;

                return false;
            }
        }

        $payload = $this->applyMask($headers, $payload);

        if ($headers['fin']) {
            $user->partialMessage = "";

            return $payload;
        }
        $user->partialMessage = $payload;

        return false;
    }

    /**
     * @param string $message
     * @return array
     */
    protected function extractHeaders($message)
    {
        $header = [
            'fin'     => $message[0] & chr(128),
            'rsv1'    => $message[0] & chr(64),
            'rsv2'    => $message[0] & chr(32),
            'rsv3'    => $message[0] & chr(16),
            'opcode'  => ord($message[0]) & 15,
            'hasmask' => $message[1] & chr(128),
            'length'  => 0,
            'mask'    => "",
        ];
        $header['length'] = (ord($message[1]) >= 128) ? ord($message[1]) - 128 : ord($message[1]);

        if ($header['length'] == 126) {
            if ($header['hasmask']) {
                $header['mask'] = $message[4] . $message[5] . $message[6] . $message[7];
            }
            $header['length'] = ord($message[2]) * 256
                + ord($message[3]);
        } elseif ($header['length'] == 127) {
            if ($header['hasmask']) {
                $header['mask'] = $message[10] . $message[11] . $message[12] . $message[13];
            }
            $header['length'] = ord($message[2]) * 65536 * 65536 * 65536 * 256
                + ord($message[3]) * 65536 * 65536 * 65536
                + ord($message[4]) * 65536 * 65536 * 256
                + ord($message[5]) * 65536 * 65536
                + ord($message[6]) * 65536 * 256
                + ord($message[7]) * 65536
                + ord($message[8]) * 256
                + ord($message[9]);
        } elseif ($header['hasmask']) {
            $header['mask'] = $message[2] . $message[3] . $message[4] . $message[5];
        }

        //echo $this->strtohex($message);
        //$this->printHeaders($header);
        return $header;
    }

    /**
     * @param string $message
     * @param array $headers
     * @return string
     */
    protected function extractPayload($message, $headers)
    {
        $offset = 2;
        if ($headers['hasmask']) {
            $offset += 4;
        }
        if ($headers['length'] > 65535) {
            $offset += 8;
        } elseif ($headers['length'] > 125) {
            $offset += 2;
        }

        return substr($message, $offset);
    }

    /**
     * @param array $headers
     * @param string $payload
     * @return int
     */
    protected function applyMask($headers, $payload)
    {
        $effectiveMask = "";
        if ($headers['hasmask']) {
            $mask = $headers['mask'];
        } else {
            return $payload;
        }

        while (strlen($effectiveMask) < strlen($payload)) {
            $effectiveMask .= $mask;
        }
        while (strlen($effectiveMask) > strlen($payload)) {
            $effectiveMask = substr($effectiveMask, 0, -1);
        }

        return $effectiveMask ^ $payload;
    }

    /**
     * @param array $headers
     * @param My_WebSocket_User $user
     * @return bool
     */
    protected function checkRSVBits($headers, $user)
    {
        // override this method if you are using an extension where the RSV bits are used.
        if (ord($headers['rsv1']) + ord($headers['rsv2']) + ord($headers['rsv3']) > 0) {
            //$this->disconnect($user); // todo: fail connection
            return true;
        }

        return false;
    }

    /**
     * @param string $str
     * @return string
     */
    protected function strToHex($str)
    {
        $strout = "";
        for ($i = 0; $i < strlen($str); $i++) {
            $strout .= (ord($str[$i]) < 16) ? "0" . dechex(ord($str[$i])) : dechex(ord($str[$i]));
            $strout .= " ";
            if ($i % 32 == 7) {
                $strout .= ": ";
            }
            if ($i % 32 == 15) {
                $strout .= ": ";
            }
            if ($i % 32 == 23) {
                $strout .= ": ";
            }
            if ($i % 32 == 31) {
                $strout .= "\n";
            }
        }

        return $strout . "\n";
    }

    /**
     * @param array $headers
     */
    protected function printHeaders($headers)
    {
        echo "Array\n(\n";
        foreach ($headers as $key => $value) {
            if ($key == 'length' || $key == 'opcode') {
                echo "\t[$key] => $value\n\n";
            } else {
                echo "\t[$key] => " . $this->strToHex($value) . "\n";
            }
        }
        echo ")\n";
    }

    /********************* LOG **********************/

    /**
     * @param My_WebSocket_Logger_Abstract $logger
     * @return $this
     */
    public function addLogger(My_WebSocket_Logger_Abstract $logger)
    {
        $this->loggers[] = $logger;
        return $this;
    }

    /**
     * @param string $message
     * @param null $type
     */
    public function log($message, $type = null)
    {
        foreach ($this->loggers as $logger) {
            $logger->log($message, $type);
        }
    }

    /**
     * @param string $message
     */
    public function fatal($message) {
        foreach ($this->loggers as $logger) {
            $logger->fatal($message);
        }
    }

    /**
     * @param string $message
     */
    public function error($message) {
        foreach ($this->loggers as $logger) {
            $logger->error($message);
        }
    }

    /**
     * @param string $message
     */
    public function debug($message) {
        foreach ($this->loggers as $logger) {
            $logger->debug($message);
        }
    }

    /*********************** LISTENERS *********************/

    /**
     * @param My_WebSocket_Listener_Abstract $listener
     * @return $this
     */
    public function addListener(My_WebSocket_Listener_Abstract $listener)
    {
        $this->listeners[] = $listener;
        return $this;
    }

    /**
     * @param string $event
     * @param array $additionalData
     * @return $this
     * @throws Exception
     */
    protected function trigger($event, array $additionalData = [])
    {
        $this->debug('TRIGGER ' . $event . '. Data: ' . var_export($additionalData, true));
        if (!in_array($event, $this->getAllowedEvents())) {
            $this->fatal('Error! Unknown event "' . $event . '"');
            throw new Exception('Error! Unknown event "' . $event . '"');
        }
        $method = 'on' . ucfirst($event);
        foreach ($this->listeners as $listener) {
            $listener->$method($this, $additionalData);
        }
        return $this;
    }

    /**
     * @return array
     */
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

}