<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class My_WebSocket_Server implements MessageComponentInterface
{

    const DATA_KEY_MODULE = '_module';

    const COMMON_LISTENERS_KEY = 'common';

    protected $master;
    protected $sockets = [];
    /**
     * @var SplObjectStorage
     */
    protected $users;

    /**
     * @var My_WebSocket_Logger_Abstract
     */
    protected $logger;

    /**
     * @var My_WebSocket_Listener_Abstract[]
     */
    protected $listeners = [];

    public function __construct()
    {
        $this->users = new SplObjectStorage();
    }

    public function __destruct()
    {
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $cookiesRows = $conn->WebSocket->request->getHeader('cookie');
        $cookies = [];
        foreach ($cookiesRows as $cookie) {
            list ($key, $value) = explode('=', $cookie);
            $cookies[$key] = $value;
        }
        $this->users[$conn] = [
            ini_get('session.name') => $cookies[ini_get('session.name')],
        ];
        $this->trigger('open', $conn);
    }

    public function onMessage(ConnectionInterface $from, $message)
    {
        try {
            $message = (array)Zend_Json::decode($message);
            $this->trigger('message', $from, $message);
        } catch (Exception $e) {
            $this->getLogger()->error('Message process error: ' . $e->getMessage());
        }
    }

    /**
     * @param ConnectionInterface $conn
     * @param string|array $message
     */
    public function send(ConnectionInterface $conn, $message)
    {
        $messageString = is_array($message) ? Zend_Json::encode($message) : $message;
        $conn->send($messageString);
        $this->trigger('send', $conn, $message);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->users->detach($conn);
        $this->trigger('close', $conn);
    }


    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->trigger('error', $conn, ['exception' => $e]);
    }

    public function getConnection(ConnectionInterface $conn) {
        return $this->users[$conn];
    }

    /********************* LOG **********************/

    /**
     * @param My_WebSocket_Logger_Abstract $logger
     * @return $this
     */
    public function setLogger(My_WebSocket_Logger_Abstract $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new My_WebSocket_Logger_Null();
        }
        return $this->logger;
    }

    /*********************** LISTENERS *********************/

    /**
     * @param My_WebSocket_Listener_Abstract $listener
     * @param array $modules
     * @return $this
     */
    public function addListener(My_WebSocket_Listener_Abstract $listener, $modules = [])
    {
        $modules = (array)$modules;
        $this->getLogger()->debug('Added listener "' . get_class($listener) . '"');
        if (empty($modules)) {
            if (empty($this->listeners[static::COMMON_LISTENERS_KEY])) {
                $this->listeners[static::COMMON_LISTENERS_KEY] = [];
            }
            $this->listeners[static::COMMON_LISTENERS_KEY][] = $listener->setServer($this);
        } else {
            foreach ($modules as $module) {
                if (empty($this->listeners[$module])) {
                    $this->listeners[$module] = [];
                }
                $this->listeners[$module][] = $listener->setServer($this);
            }
        }
        return $this;
    }

    /**
     * @param string $event
     * @param ConnectionInterface $conn
     * @param array $data
     * @return $this
     * @throws Exception
     */
    protected function trigger($event, ConnectionInterface $conn, array $data = [])
    {
        $method = 'on' . ucfirst($event);

        /** @var $listener My_WebSocket_Listener_Abstract */

        /*** COMMON ***/
        if (!empty($this->listeners[static::COMMON_LISTENERS_KEY])) {
            foreach ($this->listeners[static::COMMON_LISTENERS_KEY] as $listener) {
                if (method_exists($listener, $method)) {
                    $listener->setUser($conn)->$method($data);
                }
            }
        }

        /*** MODULE ***/
        if (!empty($data[static::DATA_KEY_MODULE])) {
            $module = $data[static::DATA_KEY_MODULE];
            if (empty($this->listeners[$module])) {
                $this->getLogger()->error('Error! No listeners for module "' . $module . '"');
            } else {
                foreach ($this->listeners[$module] as $listener) {
                    if (method_exists($listener, $method)) {
                        $listener->setUser($conn)->$method($data);
                    }
                }
            }
        }

        return $this;
    }

}