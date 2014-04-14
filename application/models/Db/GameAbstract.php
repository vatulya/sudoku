<?php

abstract class Application_Model_Db_GameAbstract extends Application_Model_Db_Abstract
{

    public function delete($id)
    {
        return false;
    }

    /**
     * @param $state
     * @param array $exceptIds
     * @return bool
     */
    public function setAllGamesState($state, array $exceptIds = [])
    {
        $where = null;
        $ids = [];
        $exceptIds = (array)$exceptIds;
        foreach ($exceptIds as $id) {
            $ids[] = $id;
        }
        if ($ids) {
            $where = ['id IN (?)', $ids];
        }
        $update = ['state' => $state];
        $result = (bool)$this->_db->update(static::TABLE_NAME, $update, $where);
        return $result;
    }

}