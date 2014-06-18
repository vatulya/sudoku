<?php

class Application_Service_Difficulty_Sudoku extends Application_Service_Difficulty_Abstract
{

    protected $modelDb = 'Sudoku_Difficulties';

    protected function initDifficulties()
    {
        parent::initDifficulties();
        foreach ($this->difficulties as $key => $difficulty) {
            if (!empty($difficulty['open_cells']) && !is_numeric($difficulty['open_cells'])) {
                try {$difficulty['open_cells'] = Zend_Json::decode($difficulty['open_cells']);} catch (\Exception $e) {$difficulty['open_cells'] = 0;}
                $this->difficulties[$key] = $difficulty;
            }
        }
    }

}
