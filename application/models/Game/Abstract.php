<?php

abstract class Application_Model_Game_Abstract extends Application_Model_Abstract
{

    protected static $service;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $difficulty;

    /**
     * @var int
     */
    protected $state = 0;

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @param int $id
     * @throws RuntimeException
     */
    protected function __construct($id)
    {
        $game = self::getModelDb()->getOne(array('id' => $id));
        if (!$game) {
            throw new RuntimeException('Wrong game ID "' . $id . '".');
        }
        $this->id         = $game['id'];
        $this->difficulty = $game['difficulty'];
        $this->state      = $game['state'];
        $this->parameters = $game['parameters'];
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public static function create(array $parameters)
    {
        $id = self::getModelDb()->insert($parameters);
        $game = new static($id);
        return $game;
    }

    /**
     * @param int $id
     * @return Application_Model_Game_Sudoku
     */
    public static function load($id)
    {
        $game = new static($id);
        return $game;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getDifficulty()
    {
        return $this->difficulty;
    }

    /**
     * @param int $state
     * @return bool
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    protected function setState($state)
    {
        $service = $this->getService();
        if ($service->checkState($this->getState(), $state)) {
            $this->state = $state;
            $this->save();
        } else {
            throw new RuntimeException('Wrong game state. Game ID "' . $this->getId() . '". Old state "' . $this->getState() . '". New state "' . $state . '".');
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function start()
    {
        $service = $this->getService();
        $this->setState($service::STATE_IN_PROGRESS);
        return $this;
    }

    /**
     * @return $this
     */
    public function pause()
    {
        $service = $this->getService();
        $this->setState($service::STATE_PAUSED);
        return $this;
    }

    /**
     * @return $this
     */
    public function reject()
    {
        $service = $this->getService();
        $this->setState($service::STATE_REJECTED);
        return $this;
    }

    /**
     * @return $this
     */
    public function finish()
    {
        $service = $this->getService();
        $this->setState($service::STATE_FINISHED);
        return $this;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParameters($params)
    {
        $this->parameters = $params;
        $this->save();
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParameter($key, $value)
    {
        $this->parameters[$key] = $value;
        $this->save();
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParameter($key)
    {
        return isset($this->parameters[$key]) ? $this->parameters[$key] : null;
    }

    /**
     * @param string $action
     * @param array $parameters
     * @return bool
     */
    public function logUserAction($action, array $parameters = array())
    {
        // TODO: finish it
        return true;
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->getModelDb()->update($this->id, $this->toArray());
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = array(
            'id'         => $this->id,
//            'user_id'    => $this->userId,
            'state'      => $this->state,
            'difficulty' => $this->difficulty,
//            'created'    => $this->created,
//            'started'    => $this->started,
//            'ended'      => $this->ended,
//            'duration'   => $this->duration,
            'parameters' => $this->parameters,
        );
        return $data;
    }

    /**
     * @return $this
     */
    protected function initService()
    {
        if (is_string(static::$service)) {
            $service = 'Application_Service_Game_' . static::$service;
            static::$service = $service::getInstance();
        }
        return $this;
    }

    /**
     * @return Application_Service_Game_Abstract
     */
    public function getService()
    {
        if (is_string(static::$service)) {
            $this->initService();
        }
        return static::$service;
    }

}