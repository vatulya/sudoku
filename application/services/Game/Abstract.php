<?php

abstract class Application_Service_Game_Abstract extends Application_Service_Abstract
{

    const CODE = '';

    const GAME_TYPE_SINGLE_PLAYER = 'single';
    const GAME_TYPE_VERSUS_BOT    = 'bot';
    const GAME_TYPE_VERSUS_PLAYER = 'player';

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

    /**
     * @var Application_Service_Difficulty_Abstract
     */
    protected $serviceDifficulty;

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
     * @return $this
     */
    protected function init()
    {
        parent::init();
        $service = 'Application_Service_Difficulty_' . ucfirst(static::CODE);
        $this->serviceDifficulty = $service::getInstance();
        return $this;
    }

    /**
     * @return Application_Service_Difficulty_Abstract
     */
    public function getServiceDifficulty()
    {
        return $this->serviceDifficulty;
    }

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

}