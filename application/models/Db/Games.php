<?php

class Application_Model_Db_Games extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'games';

    public function getById($id)
    {
        $select = $this->_db->select()
            ->from(array('g' => self::TABLE_NAME))
            ->where('g.id = ?', $id);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function getAllByUserId($userId)
    {
        $select = $this->_db->select()
            ->from(array('g' => self::TABLE_NAME))
            ->where('g.user_id = ?', $userId)
            ->order(array('g.created DESC'));
        $result = $this->_db->fetchAll($select);
        return $result;
    }

    public function getAll()
    {
        $select = $this->_db->select()
            ->from(array('g' => self::TABLE_NAME))
            ->order(array('g.created DESC'));
        $result = $this->_db->fetchAll($select);
        return $result;
    }

    public function insert($userId, $gameCode)
    {
        $now = new \DateTime('NOW', new \DateTimeZone('UTC'));
        $data = array(
            'user_id'   => $userId,
            'game_code' => $gameCode,
            'created'   => $now->format('Y-m-d H:i:s'),
        );
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        if ($result) {
            $result = $this->_db->lastInsertId();
        }
        return $result;
    }

}