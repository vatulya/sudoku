<?php

require __DIR__ . '/bootstrap.php';

defined('LOG_FILE') ||
    define('LOG_FILE', __DIR__ . '/../logs/calculate-games-rating.log');

class CalculateGamesRating
{

    protected $_db;

    public function __construct()
    {
        $this->_db = Zend_Db_Table::getDefaultAdapter();
    }

    public function calculate()
    {
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $sudokuGamesDbModel = new Application_Model_Db_Sudoku_Games();
        $sudokuRatingsDbModel = new Application_Model_Db_Sudoku_Ratings();
        $games = $sudokuGamesDbModel->getAll(['state' => Application_Service_Game_Abstract::STATE_FINISHED, 'rating' => null]);
        $toUpdate = [];
        $difficulties = [];
        foreach ($games as $game) {
            $userId     = $game['user_id'];
            $difficulty = $game['difficulty_id'];
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
            $difficulty = $row['difficulty_id'];
            $rating     = $row['rating'];
            if (!isset($check[$userId])) {
                $check[$userId] = [];
            }
            $check[$userId][$difficulty] = $rating;
        }
        $difficultyService = Application_Service_Difficulty_Sudoku::getInstance();
        foreach ($toUpdate as $userId => $ratings) {
            foreach ($ratings as $difficulty => $rating) {
                $difficulty = $difficultyService->getDifficulty($difficulty);
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
                            AND difficulty_id = ' . intval($difficulty['id']) . '
                    ';
                    $this->_db->query($sql);
                } else {
                    $sudokuRatingsDbModel->insert([
                        'user_id'       => $userId,
                        'difficulty_id' => $difficulty,
                        'rating'        => $rating,
                    ]);
                }
            }
        }
        $sql = '
            CREATE TEMPORARY TABLE tmp_sudoku_ratings (
                id INT NOT NULL AUTO_INCREMENT,
                user_id INT NOT NULL,
                difficulty_id INT NOT NULL,
                faster_game_hash VARCHAR(50) NOT NULL DEFAULT "",
                faster_game_duration INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                INDEX (user_id),
                INDEX (difficulty_id)
            )
        ';
        $this->_db->query($sql);

        foreach ($difficulties as $difficulty) {
            $sql = '
                INSERT INTO tmp_sudoku_ratings
                SELECT
                    NULL AS id,
                    user_id AS user_id,
                    difficulty_id AS difficulty_id,
                    "" AS faster_game_hash,
                    0 AS faster_game_duration
                FROM
                    ' . $sudokuRatingsDbModel::TABLE_NAME . '
                WHERE
                    difficulty_id = ' . intval($difficulty) . '
                ORDER BY rating DESC
            ';
            $this->_db->query($sql);

            $sql = '
                UPDATE tmp_sudoku_ratings tsr
                INNER JOIN ' . $sudokuGamesDbModel::TABLE_NAME . ' sg ON (tsr.user_id = sg.user_id AND tsr.difficulty_id = sg.difficulty_id)
                SET
                    tsr.faster_game_hash = sg.hash,
                    tsr.faster_game_duration = sg.duration
                WHERE
                    tsr.faster_game_hash = ""
                    OR sg.duration < tsr.faster_game_duration
            ';
            $this->_db->query($sql);

            $sql = '
                UPDATE ' . $sudokuRatingsDbModel::TABLE_NAME . ' sr
                INNER JOIN tmp_sudoku_ratings tsr ON (sr.user_id = tsr.user_id AND sr.difficulty_id = tsr.difficulty_id)
                SET
                    sr.position = tsr.id,
                    sr.faster_game_hash = tsr.faster_game_hash,
                    sr.faster_game_duration = tsr.faster_game_duration
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

$checkGames = new CalculateGamesRating();
$checkGames->calculate();

echo 'Finish' . PHP_EOL;
