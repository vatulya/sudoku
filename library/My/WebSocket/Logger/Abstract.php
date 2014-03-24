<?php

/**
 * Class My_WebSocket_Logger_Abstract
 *
 * @method My_WebSocket_Logger_Abstract fatal(string $message)
 * @method My_WebSocket_Logger_Abstract error(string $message)
 * @method My_WebSocket_Logger_Abstract debug(string $message)
 */
abstract class My_WebSocket_Logger_Abstract
{

    const LEVEL_FATAL = 1;
    const LEVEL_ERROR = 2;
    const LEVEL_DEBUG = 3;

    protected $allowedLogMethods = [
        'FATAL' => self::LEVEL_FATAL,
        'ERROR' => self::LEVEL_ERROR,
        'DEBUG' => self::LEVEL_DEBUG,
    ];

    /**
     * @param string $message
     * @param int $type
     * @return $this
     */
    abstract public function log($message, $type = self::LEVEL_DEBUG);

    public function __call($name, $arguments)
    {
        $type = isset($this->allowedLogMethods[strtoupper($name)]) ? $this->allowedLogMethods[strtoupper($name)] : null;
        if (is_null($type)) {
            $type = self::LEVEL_FATAL;
            $this->log(sprintf('Wrong log method "%s". This and the Next log message set as FATAL', $name), $type);
        }
        return $this->log($arguments[0], $type);
    }

}