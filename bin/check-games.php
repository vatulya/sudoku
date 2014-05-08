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
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $sudokuGamesDbModel = new Application_Model_Db_Sudoku_Games();
        $sudokuRatingsDbModel = new Application_Model_Db_Sudoku_Ratings();
        $games = $sudokuGamesDbModel->getAll(['state' => Application_Service_Game_Abstract::STATE_FINISHED, 'rating' => null]);
        $toUpdate = [];
        $difficulties = [];
        foreach ($games as $game) {
            $userId     = $game['user_id'];
            $difficulty = $game['difficulty'];
            $duration   = $game['duration'];
            $rating = $sudokuService->calculateRating($difficulty, $duration);
            if ($rating > 0) {
                if (!isset($toUpdate[$userId])) {
                    $toUpdate[$userId] = [];
                }
                if (!isset($toUpdate[$userId][$difficulty])) {
                    $toUpdate[$userId][$difficulty] = 0;
                }
                $toUpdate[$userId][$difficulty] += $rating;
                $difficulties[$difficulty] = $difficulty;
            }
            $sql = '
                UPDATE ' . Application_Model_Db_Sudoku_Games::TABLE_NAME . '
                SET
                    rating = ' . intval($rating) . '
                WHERE
                    id = ' . intval($game['id']) . '
                LIMIT 1
            ';
            $this->_db->query($sql);
        }
        if (empty($toUpdate)) {
            return true;
        }
        $rows = $sudokuRatingsDbModel->getAll(['user_id' => array_keys($toUpdate)]);
        $check = [];
        foreach ($rows as $row) {
            $userId     = $row['user_id'];
            $difficulty = $row['difficulty'];
            $rating     = $row['rating'];
            if (!isset($check[$userId])) {
                $check[$userId] = [];
            }
            $check[$userId][$difficulty] = $rating;
        }
        foreach ($toUpdate as $userId => $ratings) {
            foreach ($ratings as $difficulty => $rating) {
                $difficulty = $sudokuService->getDifficulty($difficulty);
                if (empty($difficulty)) {
                    continue;
                }
                if (isset($check[$userId][$difficulty['id']])) {
                    $newRating = $rating + $check[$userId][$difficulty['id']];
                    $sql = '
                        UPDATE ' . $sudokuRatingsDbModel::TABLE_NAME . '
                        SET
                            rating = ' . intval($newRating) . '
                        WHERE
                            user_id = ' . intval($userId) . '
                            AND difficulty = ' . intval($difficulty['id']) . '
                    ';
                    $this->_db->query($sql);
                } else {
                    $sudokuRatingsDbModel->insert([
                        'user_id'    => $userId,
                        'difficulty' => $difficulty,
                        'rating'     => $rating,
                    ]);
                }
            }
        }
        $sql = '
            CREATE TEMPORARY TABLE tmp_sudoku_ratings (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                difficulty INT NOT NULL,
                PRIMARY KEY (id),
                INDEX (user_id),
                INDEX (difficulty)
            )
        ';
        $this->_db->query($sql);

        foreach ($difficulties as $difficulty) {
            $sql = '
                INSERT INTO tmp_sudoku_ratings
                SELECT
                    NULL AS id,
                    user_id AS user_id,
                    difficulty AS difficulty
                FROM
                    ' . $sudokuRatingsDbModel::TABLE_NAME . '
                WHERE
                    difficulty = ' . intval($difficulty) . '
                ORDER BY rating DESC
            ';
            $this->_db->query($sql);

            $sql = '
                UPDATE ' . $sudokuRatingsDbModel::TABLE_NAME . ' sr
                INNER JOIN tmp_sudoku_ratings tsr ON (sr.user_id = tsr.user_id AND sr.difficulty = tsr.difficulty)
                SET
                    sr.position = tsr.id
            ';
            $this->_db->query($sql);

            $sql = '
                TRUNCATE TABLE tmp_sudoku_ratings
            ';
            $this->_db->query($sql);
        }

        $sql = '
            DROP TEMPORARY TABLE tmp_sudoku_ratings
        ';
        $this->_db->query($sql);
        return true;
    }

}

echo 'Start' . PHP_EOL;

$checkGames = new CheckGames();
$checkGames->check();

echo 'Finish' . PHP_EOL;
