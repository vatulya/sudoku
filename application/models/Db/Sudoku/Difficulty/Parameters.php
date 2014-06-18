<?php

class Application_Model_Db_Sudoku_Difficulty_Parameters extends Application_Model_Db_Abstract
{

    const TABLE_NAME = 'sudoku_difficulty_parameters';

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
