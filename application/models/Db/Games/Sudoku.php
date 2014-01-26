<?php

class Application_Model_Db_Games_Sudoku extends Application_Model_Db_Games_Abstract
{

    const TABLE_NAME = 'games_sudoku';

    public function getById($id)
    {
        $select = $this->_db->select()
            ->from(array('gs' => self::TABLE_NAME))
            ->where('gs.id = ?', $id);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function insert($gameId, array $params)
    {
        $now = new \DateTime('NOW', new \DateTimeZone('UTC'));
        $params = Zend_Json::encode($params);
        $data = array(
            'game_id'    => $gameId,
            'started'    => $now->format('Y-m-d H:i:s'),
            'ended'      => null,
            'duration'   => 0,
            'parameters' => $params,
        );
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

}