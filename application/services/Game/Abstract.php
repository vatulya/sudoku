<?php

abstract class Application_Service_Game_Abstract extends Application_Service_Abstract
{

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

    protected static $difficulties;

    /**
     * @param int $userId
     * @param array $parameters
     * @return Application_Model_Game_Abstract
     */
    abstract public function create($userId, array $parameters = []);

    /**
     * @param int $id
     * @return Application_Model_Game_Abstract
     */
    abstract public function load($id);

    /**
     * @param Application_Model_Game_Abstract $game
     * @return array|bool
     */
    abstract public function checkGameSolution(Application_Model_Game_Abstract $game);

    /**
     * @param int $oldState
     * @param int $newState
     * @return bool
     */
    public function checkState($oldState, $newState)
    {
        // TODO: finish it
        return true;
    }

    /**
     * @return array
     */
    public static function getStates()
    {
        return [
            self::STATE_NEW,
            self::STATE_IN_PROGRESS,
            self::STATE_PAUSED,
            self::STATE_REJECTED,
            self::STATE_FINISHED,
        ];
    }

    /**
     * @return array
     */
    public static function getAllDifficulties()
    {
        if (is_null(static::$difficulties)) {
            static::initDifficulties();
        }
        return static::$difficulties;
    }

    /**
     * @param int $difficulty
     * @return array
     */
    public function getDifficulty($difficulty)
    {
        $difficulties = static::getAllDifficulties();
        if (isset($difficulties[$difficulty])) {
            return $difficulties[$difficulty];
        } else {
            return [];
        }
    }

    protected static function initDifficulties()
    {
        static::$difficulties = [
            self::DIFFICULTY_PRACTICE  => ['code' => self::DIFFICULTY_PRACTICE, 'title' => 'Практика',],
            self::DIFFICULTY_EASY      => ['code' => self::DIFFICULTY_EASY, 'title' => 'Легкая',],
            self::DIFFICULTY_NORMAL    => ['code' => self::DIFFICULTY_NORMAL, 'title' => 'Средняя',],
            self::DIFFICULTY_EXPERT    => ['code' => self::DIFFICULTY_EXPERT, 'title' => 'Сложная',],
            self::DIFFICULTY_NIGHTMARE => ['code' => self::DIFFICULTY_NIGHTMARE, 'title' => 'Эксперт',],
            self::DIFFICULTY_RANDOM    => ['code' => self::DIFFICULTY_RANDOM, 'title' => 'Случайная',],
            self::DIFFICULTY_TEST      => ['code' => self::DIFFICULTY_TEST, 'title' => 'Test',],
        ];
    }

}