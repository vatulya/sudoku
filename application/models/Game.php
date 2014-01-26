<?php

class Application_Model_Game extends Application_Model_Abstract
{

    const DB_MODEL_NAME = 'Games';

    const DB_MODEL_GAME_NAME_TEMPLATE = 'Application_Model_Db_Games_%s';

    /**
     * @var Application_Model_Db_Games
     */
    protected $_modelDb;

    /**
     * @var Application_Model_Db_Games_Abstract
     */
    protected $_modelDbGame;

    protected $_name;

    /**
     * @var Application_Model_User
     */
    protected $_user;

    protected $_params = array();

    /**
     * @param array $user
     * @param array $params
     * @return Application_Model_Game
     */
    static public function create(array $user, $gameCode, array $params = array())
    {
        $model = new self();
        $model->setName($gameCode);
        $user['id'] = 1;
        $gameId = $model->getModelDb()->insert($user['id'], $model->getName());
        $game = $gameId ? $model->getModelDb()->getById($gameId) : array();
        if (!$game) {
            throw new Exception('Error! Can\'t create game.');
        }
        $gameEngineId = $model->getModelDbGame()->insert($game['id'], $params);
        $gameEngine = $gameEngineId ? $model->getModelDbGame()->getById($gameEngineId) : array();
        if (!$gameEngine) {
            throw new Exception('Error! Can\'t create game.');
        }
        $model->setParams($gameEngine['parameters']);
        return $model;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->_name = strtolower($name);
        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getName()
    {
        if (is_null($this->_name)) {
            throw new Exception('Game getName() error. Name is empty.');
        }
        return $this->_name;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @param array|string $params
     * @return $this
     */
    public function setParams($params)
    {
        if (is_string($params)) {
            try {
                $params = Zend_Json::decode($params);
            } catch (Exception $e) {
                // ERROR
                $params = array();
            }
        }
        $this->_params = $params;
        return $this;
    }

    public function getModelDbGame()
    {
        if (is_null($this->_modelDbGame)) {
            $class = sprintf(self::DB_MODEL_GAME_NAME_TEMPLATE, ucfirst($this->getName()));
            if (class_exists($class)) {
                $this->_modelDbGame = new $class();
            }
        }
        return $this->_modelDbGame;
    }

}