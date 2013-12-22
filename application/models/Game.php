<?php

class Application_Model_Game
{

    protected $_name;

    protected $_params = array();

    protected $_difficulty;

    public function __construct($name, $difficulty)
    {
        $this->_name = $name;
        $this->_difficulty = $difficulty;
    }

    /**
     * @return int
     */
    public function getDifficulty()
    {
        return $this->_difficulty;
    }

    /**
     * @return string
     */
    public function getName()
    {
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
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->_params = $params;
    }

}