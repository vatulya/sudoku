<?php

abstract class Application_Model_Db_Games_Abstract extends Application_Model_Db_Abstract
{

    const TABLE_NAME = '';

    /**
     * @param int $id
     * @return array
     */
    abstract function getById($id);

    /**
     * @param int $gameId
     * @param array $params
     * @return int new ID
     */
    abstract function insert($gameId, array $params);

}