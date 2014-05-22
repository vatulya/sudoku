<?php

class Application_Model_Db_Sudoku_Ratings extends Application_Model_Db_GameAbstract
{

    const TABLE_NAME = 'sudoku_ratings';

    public function insert(array $data)
    {
        $now = $this->getNow();
        $data = [
            'user_id'              => $data['user_id'],
            'difficulty'           => $data['difficulty']['id'],
            'rating'               => $data['rating'],
            'position'             => 0,
            'faster_game_hash'     => '',
            'faster_game_duration' => 0,
            'updated'              => $now,
        ];
        $result = $this->_db->insert(self::TABLE_NAME, $data);
        return $result;
    }

    public function update($id, array $data)
    {
        $update = [];
        foreach (['rating', 'position', 'faster_game_hash', 'faster_game_duration'] as $field) {
            if (isset($data[$field])) {
                $update[$field] = $data[$field];
            }
        }
        $update['updated'] = $this->getNow();
        $result = false;
        if (!empty($update)) {
            $result = (bool)$this->_db->update(static::TABLE_NAME, $update, ['id = ?' => $id]);
        }
        return $result;
    }

    /**
     * @param array $where
     * @param array $order
     * @return My_Paginator
     */
    public function getAllUsersRatings(array $where = [], array $order = [])
    {
        $select = $this->_db
            ->select()
            ->from(['sr' => static::TABLE_NAME])
            ->joinInner(
                ['u' => Application_Model_Db_Users::TABLE_NAME],
                'sr.user_id = u.id',
                ['full_name']
            )
            ->where('u.role_id > ?', Application_Service_User::ROLE_GUEST)
        ;
        foreach ($where as $field => $value) {
            if (null === $value) {
                $expression = ' IS NULL';
            } elseif ('!null' === $value) {
                $expression = ' IS NOT NULL';
            } else {
                $expression = is_array($value) ? ' IN (?)' : ' = ?';
            }
            $select->where($field . $expression, $value);
        }
        if (!empty($order)) {
            $select->order($order);
        }
        $paginator = new Zend_Paginator_Adapter_DbSelect($select);
        $paginator = new My_Paginator($paginator);
        return $paginator;
    }

}