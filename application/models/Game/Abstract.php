<?php

abstract class Application_Model_Game_Abstract
{

    const GAME_CODE = '';

    const GAME_MODEL_NAME_TEMPLATE = 'Application_Model_Game_%s';

    const PRACTICE_DIFFICULTY  = 1;
    const EASY_DIFFICULTY      = 2;
    const NORMAL_DIFFICULTY    = 4;
    const EXPERT_DIFFICULTY    = 6;
    const NIGHTMARE_DIFFICULTY = 10;
    const RANDOM_DIFFICULTY    = 0;
    const TEST_DIFFICULTY      = -1;

    const DEFAULT_GAME_DIFFICULTY = 2;

    /**
     * @var Application_Model_Game
     */
    protected $_game;

    protected $_name;

    protected $_difficulty;

    abstract function createGame(array $user);

    static public function factory($name)
    {
        $name = strtolower($name);
        $class = sprintf(self::GAME_MODEL_NAME_TEMPLATE, ucfirst($name));
        if (class_exists($class)) {
            return new $class($name);
        }
        return null;
    }

    protected function __construct($name)
    {
        $name = strtolower($name);
        $this->_name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param int $difficulty
     * @return $this
     */
    public function setDifficulty($difficulty)
    {
        $difficulty = (int)$difficulty;
        $allDifficulties = $this->getAllDifficulties();
        if (!isset($allDifficulties[$difficulty])) {
            $difficulty = self::DEFAULT_GAME_DIFFICULTY;
        }
        $difficulty = $allDifficulties[$difficulty];
        $this->_difficulty = $difficulty;
        return $this;
    }

    /**
     * @return int
     */
    public function getDifficulty()
    {
        if (is_null($this->_difficulty)) {
            $this->_difficulty = $this->setDifficulty(self::DEFAULT_GAME_DIFFICULTY);
        }
        return $this->_difficulty;
    }

    public function getAllDifficulties()
    {
        $difficulties = array(
            self::PRACTICE_DIFFICULTY  => array('title' => 'Practice',),
            self::EASY_DIFFICULTY      => array('title' => 'Easy',),
            self::NORMAL_DIFFICULTY    => array('title' => 'Normal',),
            self::EXPERT_DIFFICULTY    => array('title' => 'Expert',),
            self::NIGHTMARE_DIFFICULTY => array('title' => 'Nightmare',),
            self::RANDOM_DIFFICULTY    => array('title' => 'Random',),
            self::TEST_DIFFICULTY      => array('title' => 'Test',),
        );
        return $difficulties;
    }

    /**
     * @param Application_Model_Game $game
     * @return $this
     */
    public function setGame(Application_Model_Game $game)
    {
        $this->_game = $game;
        return $this;
    }

    /**
     * @return Application_Model_Game
     */
    public function getGame()
    {
        return $this->_game;
    }

}