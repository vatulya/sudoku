<?php

/**
 * Class Application_Model_Game_Sudoku
 */
class Application_Model_Game_Sudoku extends Application_Model_Game_Abstract
{

    protected static $modelDb = 'SudokuGames';

    protected static $service = 'Sudoku';

    public function logUserAction($action, array $parameters = array())
    {
        $cell = $parameters['cell'];
        $number = $parameters['number'];
        $cells = $this->getParameter('checkedCells') ?: array();
        if ($number) {
            $cells[$cell] = $number;
        } else {
            unset($cells[$cell]);
        }
        $this->setParameter('checkedCells', $cells);
        return true;
    }

}
