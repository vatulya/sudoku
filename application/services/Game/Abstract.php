<?php

abstract class Application_Service_Game_Abstract extends Application_Service_Abstract
{

    const STATE_NEW         = 0;
    const STATE_IN_PROGRESS = 1;
    const STATE_PAUSED      = 2;
    const STATE_REJECTED    = 3;
    const STATE_FINISHED    = 4;

    const STATE_CODE_NEW         = 'new';
    const STATE_CODE_IN_PROGRESS = 'in_progress';
    const STATE_CODE_PAUSED      = 'paused';
    const STATE_CODE_REJECTED    = 'rejected';
    const STATE_CODE_FINISHED    = 'finished';

    const DIFFICULTY_PRACTICE  = 1;
    const DIFFICULTY_EASY      = 2;
    const DIFFICULTY_NORMAL    = 4;
    const DIFFICULTY_EXPERT    = 6;
    const DIFFICULTY_NIGHTMARE = 10;
    const DIFFICULTY_RANDOM    = 0;
    const DIFFICULTY_TEST      = -1;

    const DIFFICULTY_CODE_PRACTICE  = 'practice';
    const DIFFICULTY_CODE_EASY      = 'easy';
    const DIFFICULTY_CODE_NORMAL    = 'normal';
    const DIFFICULTY_CODE_EXPERT    = 'expert';
    const DIFFICULTY_CODE_NIGHTMARE = 'nightmare';
    const DIFFICULTY_CODE_RANDOM    = 'random';
    const DIFFICULTY_CODE_TEST      = 'test';

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
            self::STATE_NEW => [
                'id' => self::STATE_NEW,
                'code' => self::STATE_CODE_NEW,
                'title' => 'Новая',
            ],
            self::STATE_IN_PROGRESS => [
                'id' => self::STATE_IN_PROGRESS,
                'code' => self::STATE_CODE_IN_PROGRESS,
                'title' => 'В процессе',
            ],
            self::STATE_PAUSED => [
                'id' => self::STATE_PAUSED,
                'code' => self::STATE_CODE_PAUSED,
                'title' => 'Приостановлена',
            ],
            self::STATE_REJECTED => [
                'id' => self::STATE_REJECTED,
                'code' => self::STATE_CODE_REJECTED,
                'title' => 'Отменена',
            ],
            self::STATE_FINISHED => [
                'id' => self::STATE_FINISHED,
                'code' => self::STATE_CODE_FINISHED,
                'title' => 'Завершена',
            ],
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
            self::DIFFICULTY_PRACTICE  => [
                'id'    => self::DIFFICULTY_PRACTICE,
                'code'  => self::DIFFICULTY_CODE_PRACTICE,
                'title' => 'Практика',
            ],
            self::DIFFICULTY_EASY      => [
                'id'    => self::DIFFICULTY_EASY,
                'code'  => self::DIFFICULTY_CODE_EASY,
                'title' => 'Легкая',
            ],
            self::DIFFICULTY_NORMAL    => [
                'id'    => self::DIFFICULTY_NORMAL,
                'code'  => self::DIFFICULTY_CODE_NORMAL,
                'title' => 'Средняя',
            ],
            self::DIFFICULTY_EXPERT    => [
                'id'    => self::DIFFICULTY_EXPERT,
                'code'  => self::DIFFICULTY_CODE_EXPERT,
                'title' => 'Сложная',
            ],
            self::DIFFICULTY_NIGHTMARE => [
                'id'    => self::DIFFICULTY_NIGHTMARE,
                'code'  => self::DIFFICULTY_CODE_NIGHTMARE,
                'title' => 'Эксперт',
            ],
            self::DIFFICULTY_RANDOM    => [
                'id'    => self::DIFFICULTY_RANDOM,
                'code'  => self::DIFFICULTY_CODE_RANDOM,
                'title' => 'Случайная',
            ],
            self::DIFFICULTY_TEST      => [
                'id'    => self::DIFFICULTY_TEST,
                'code'  => self::DIFFICULTY_CODE_TEST,
                'title' => 'Test',
            ],
        ];
    }

}