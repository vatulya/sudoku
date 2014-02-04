<?php

abstract class Application_Model_Game_Abstract
{

    const NAME = '';
    const CODE = '';

    const PRACTICE_DIFFICULTY  = 1;
    const EASY_DIFFICULTY      = 2;
    const NORMAL_DIFFICULTY    = 4;
    const EXPERT_DIFFICULTY    = 6;
    const NIGHTMARE_DIFFICULTY = 10;
    const RANDOM_DIFFICULTY    = 0;
    const TEST_DIFFICULTY      = -1;

    const DEFAULT_GAME_DIFFICULTY = 2;

    protected static $difficulties = array(
        self::PRACTICE_DIFFICULTY  => array('title' => 'Practice',),
        self::EASY_DIFFICULTY      => array('title' => 'Easy',),
        self::NORMAL_DIFFICULTY    => array('title' => 'Normal',),
        self::EXPERT_DIFFICULTY    => array('title' => 'Expert',),
        self::NIGHTMARE_DIFFICULTY => array('title' => 'Nightmare',),
        self::RANDOM_DIFFICULTY    => array('title' => 'Random',),
        self::TEST_DIFFICULTY      => array('title' => 'Test',),
    );

    protected $difficulty;

    protected $state;

    protected $params = array();

    /**
     * @param array $user
     * @return Application_Model_Game_Abstract
     */
    abstract function createGame(array $user);

    /**
     * @param array $params
     * @return $this
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParam($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
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
            $difficulty = self::DEFAULT_GAME_DIFFICULTY;
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

}