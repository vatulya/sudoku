<?php

class Application_Model_Db_Sudoku_Difficulties extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'sudoku_difficulties';

    public function getOne(array $parameters = [], array $order = [])
    {
        $select = $this->_db
            ->select()
            ->from(['sd' => static::TABLE_NAME])
            ->joinInner(
                ['sdp' => Application_Model_Db_Sudoku_Difficulty_Parameters::TABLE_NAME],
                'sd.id = sdp.difficulty_id',
                ['open_cells', 'start_rating', 'minimal_rating', 'penalty_per_second']
            );
        foreach ($parameters as $field => $value) {
            $select->where($field . ' = ?', $value);
        }
        if (!empty($order)) {
            $select->order($order);
        }
        $select->limit(1);
        $result = $this->_db->fetchRow($select);
        return $result;
    }

    public function getAll(array $parameters = [], array $order = [])
    {
        $paginator = parent::getAll($parameters, $order);
        /** @var  $select Zend_Db_Select */
        $select = $paginator->getSource();
        $select->reset(Zend_Db_Select::FROM)->reset(Zend_Db_Select::COLUMNS)
            ->from(['sd' => static::TABLE_NAME])
            ->joinInner(
                ['sdp' => Application_Model_Db_Sudoku_Difficulty_Parameters::TABLE_NAME],
                'sd.id = sdp.difficulty_id',
                ['open_cells', 'start_rating', 'minimal_rating', 'penalty_per_second']
            );
        return $paginator;
    }


    /**
     * @param array $data
     * @return false
     */
    public function insert(array $data)
    {
        return false;
    }

    /**
     * @param $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data)
    {
        return false;
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        return false;
    }

}
