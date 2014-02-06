<?php

abstract class Application_Model_Game_Abstract extends Application_Model_Abstract
{

    const NAME = '';
    const CODE = '';

    const STATE_NEW         = 0;
    const STATE_IN_PROGRESS = 1;
    const STATE_PAUSED      = 2;
    const STATE_REJECTED    = 3;
    const STATE_FINISHED    = 4;

    const DIFFICULTY_PRACTICE  = 1;
    const DIFFICULTY_EASY      = 2;
    const DIFFICULTY_NORMAL    = 4;
    const DIFFICULTY_EXPERT    = 6;
    const DIFFICULTY_NIGHTMARE = 10;
    const DIFFICULTY_RANDOM    = 0;
    const DIFFICULTY_TEST      = -1;

    const DEFAULT_GAME_DIFFICULTY = 2;

    protected $id;

    protected static $difficulties = array(
        self::DIFFICULTY_PRACTICE  => array('title' => 'Practice',),
        self::DIFFICULTY_EASY      => array('title' => 'Easy',),
        self::DIFFICULTY_NORMAL    => array('title' => 'Normal',),
        self::DIFFICULTY_EXPERT    => array('title' => 'Expert',),
        self::DIFFICULTY_NIGHTMARE => array('title' => 'Nightmare',),
        self::DIFFICULTY_RANDOM    => array('title' => 'Random',),
        self::DIFFICULTY_TEST      => array('title' => 'Test',),
    );

    protected $difficulty;

    protected $state = 0;

    protected $parameters = array();

    /**
     * @param array $user
     * @return Application_Model_Game_Abstract
     */
    abstract function createGame(array $user);

    /**
     * @param array $params
     * @return $this
     */
    public function setParameters($params)
    {
        $this->parameters = $params;
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
     * @param int $code
     * @return $this
     */
    public function setDifficulty($code)
    {
        $code = (int)$code;
        $allDifficulties = $this->getAllDifficulties();
        if (!isset($allDifficulties[$code])) {
            $code = self::DEFAULT_GAME_DIFFICULTY;
        }
        $difficulty = $allDifficulties[$code];
        $difficulty['code'] = $code;
        $this->difficulty = $difficulty;
        return $this;
    }

    /**
     * @return int
     */
    public function getDifficulty()
    {
        if (is_null($this->difficulty)) {
            $this->difficulty = $this->setDifficulty(self::DEFAULT_GAME_DIFFICULTY);
        }
        return $this->difficulty;
    }

    /**
     * @return array
     */
    public static function getAllDifficulties()
    {
        return static::$difficulties;
    }

    /**
     * @return array
     */
    public static function getStates()
    {
        return array(
            self::STATE_NEW,
            self::STATE_IN_PROGRESS,
            self::STATE_PAUSED,
            self::STATE_REJECTED,
            self::STATE_FINISHED,
        );
    }

    /**
     * @param int $oldState
     * @param int $newState
     * @return bool
     */
    public static function checkState($oldState, $newState)
    {
        return true;
    }

    /**
     * @param int $state
     * @return bool
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function setState($state)
    {
        if (!$this->id) {
            throw new RuntimeException('This game isn\'t created yet.');
        }
        if (!in_array($state, $this->getStates())) {
            throw new InvalidArgumentException('Wrong State "' . $state . '".');
        }
        if (!$this->checkState($this->state, $state)) {
            throw new RuntimeException('Game can\'t move from state "' . $this->state . '" to state "' . $state . '".');
        }
        $result = $this->getModelDb()->update($this->id, array('state' => $state));
        return $result;
    }

}