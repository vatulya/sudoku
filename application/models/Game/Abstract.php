<?php

abstract class Application_Model_Game_Abstract extends Application_Model_Abstract
{

    protected static $modelDbLogs;

    protected static $service;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var array
     */
    protected $user;

    /**
     * @var array
     */
    protected $difficulty;

    /**
     * @var int
     */
    protected $state = Application_Service_Game_Abstract::STATE_NEW;

    /**
     * @var string
     */
    protected $created;

    /**
     * @var string
     */
    protected $started;

    /**
     * @var string
     */
    protected $ended;

    /**
     * @var int
     */
    protected $duration = 0;

    /**
     * @var int
     */
    protected $clientDuration = 0;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var string
     */
    protected $hash;

    /**
     * @var string
     */
    protected $updated;

    /**
     * @param int $id
     * @throws RuntimeException
     */
    protected function __construct($id)
    {
        $game = self::getModelDb()->getOne(['id' => $id]);
        if (!$game) {
            throw new RuntimeException('Wrong game ID "' . $id . '".');
        }
        try { $parameters = (array)Zend_Json::decode($game['parameters']); } catch (Exception $e) { $parameters = []; }
        $this->id         = $game['id'];
        $this->user       = (new Application_Model_Db_Users())->getOne(['id' => $game['user_id']]);
        $this->difficulty = $this->getService()->getServiceDifficulty()->getDifficulty($game['difficulty_id']);
        $this->state      = (int)$game['state'];
        $this->created    = (string)$game['created'];
        $this->started    = (string)$game['started'];
        $this->ended      = (string)$game['ended'];
        $this->duration   = (int)$game['duration'];
        $this->parameters = $parameters;
        $this->hash       = (string)$game['hash'];
        $this->updated    = (string)$game['updated'];
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public static function create(array $parameters)
    {
        $parameters['hash'] = md5(Zend_Json::encode($parameters) . microtime());
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
     * @param int $userId
     * @param string $gameHash
     * @return Application_Model_Game_Sudoku
     */
    public static function loadByUserIdAndGameHash($userId, $gameHash)
    {
        $game = self::getModelDb()->getOne(['user_id' => $userId, 'hash' => $gameHash]);
        $game = new static($game['id']);
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
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
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
            $this->save(false);
        } else {
            throw new RuntimeException('Wrong game state. Game ID "' . $this->getId() . '". Old state "' . $this->getState() . '". New state "' . $state . '".');
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getDifficulty()
    {
        return $this->difficulty['id'];
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * @return string
     */
    public function getEnded()
    {
        return $this->ended;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    protected function updateDuration()
    {
        $service = $this->getService();
        if ($this->getState() === $service::STATE_IN_PROGRESS) {
            $diff = time() - strtotime($this->getUpdated());
            if ($diff > 0) {
                $this->duration += $diff;
            }
        }
    }

    /**
     * @return int
     */
    public function getClientDuration()
    {
        return $this->clientDuration;
    }

    /**
     * @param int $clientDuration
     */
    public function setClientDuration($clientDuration)
    {
        if ($clientDuration > 0) {
            $this->clientDuration = $clientDuration;
        }
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
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
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    public function ping()
    {
        $this->save();
    }

    /**
     * @return $this
     */
    public function start()
    {
        $service = $this->getService();
        $otherGames = $this->getModelDb()->getAll(['state' => [$service::STATE_IN_PROGRESS, $service::STATE_NEW]]);
        foreach ($otherGames as $otherGame) {
            /** @var Application_Model_Game_Abstract $game */
            $game = self::load($otherGame['id']);
            $game->setState($service::STATE_PAUSED);
        }
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
     * @param bool $updateDuration
     * @return $this
     */
    public function save($updateDuration = true)
    {
        if ($updateDuration) {
            $this->updateDuration();
        }
        $this->getModelDb()->update($this->id, $this->toArray());
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [
            'id'            => $this->id,
//            'user_id'       => $this->user,
            'state'         => $this->state,
            'difficulty_id' => $this->difficulty['id'],
            'created'       => $this->created,
            'started'       => $this->started,
            'ended'         => $this->ended,
            'duration'      => $this->duration,
            'parameters'    => $this->parameters,
        ];
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

    /************** LOGS *******************/

    /**
     * @return array
     */
    public function getLogs()
    {
        $where = ['game_id' => $this->getId()];
        $order = ['created_microtime DESC'];
        return $this->getModelDbLogs()->getAll($where, $order);
    }

    /**
     * @param string $actionType
     * @param array $oldParameters
     * @param array $newParameters
     * @return int
     */
    public function addLog($actionType, array $oldParameters = [], array $newParameters = [])
    {
        $data = [
            'game_id'        => $this->getId(),
            'action_type'    => $actionType,
            'new_parameters' => $newParameters,
            'old_parameters' => $oldParameters,
        ];
        return $this->getModelDbLogs()->insert($data);
    }

    protected static function initModelDbLogs()
    {
        if (is_string(static::$modelDbLogs)) {
            $class = 'Application_Model_Db_' . static::$modelDbLogs;
            static::$modelDbLogs = new $class();
        }
    }

    /**
     * @return Application_Model_Db_Abstract
     */
    protected static function getModelDbLogs()
    {
        if (is_string(static::$modelDbLogs)) {
            static::initModelDbLogs();
        }
        return static::$modelDbLogs;
    }

}