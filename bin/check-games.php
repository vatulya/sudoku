<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
    define('LOG_FILE', __DIR__ . '/../logs/check-games.log');

class CheckGames
{

    protected $_db;

    public function __construct()
    {
        $this->_db = Zend_Db_Table::getDefaultAdapter();
    }

    public function check()
    {
        $deadline = Application_Model_Db_Sudoku_Games::getNow(false)->modify('-1 minute');
        $sql = '
            UPDATE ' . Application_Model_Db_Sudoku_Games::TABLE_NAME . '
            SET state = ' . Application_Service_Game_Abstract::STATE_PAUSED . '
            WHERE
                state = ' . Application_Service_Game_Abstract::STATE_IN_PROGRESS . '
                AND updated < "' . $deadline->format('Y-m-d H:i:s') . '"
        ';
        $this->_db->query($sql);
        return true;
    }

}

echo 'Start' . PHP_EOL;

$checkGames = new CheckGames();
$checkGames->check();

echo 'Finish' . PHP_EOL;
