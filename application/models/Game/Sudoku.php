<?php

class Application_Model_Game_Sudoku extends Application_Model_Game_Abstract
{

    const NAME = 'Sudoku';
    const CODE = 'sudoku';

    const SHUFFLE_COUNT = 20;

    const TOTAL_CELLS = 81;

    /**
     * @param array $user
     * @return $this
     */
    public function createGame(array $user)
    {
        $params = $this->getDifficulty();
        $board = $this->generateBoard();
        $board = $this->_getOpenCells($board, $params['openCells']);
        $board = $this->_normalizeBoardKeys($board);

        $game = array(
            'openCells' => $board,
        );
        $this->setParams($game);

        return $this;
    }

    /**
     * @return array
     */
    public function generateBoard()
    {
        $board = $this->_getSimpleBoard();
        $board = $this->_shuffleBoard($board);
        $board = $this->_mergeBoardRows($board);
        return $board;
    }

    /**
     * @return array
     */
    protected function _getSimpleBoard()
    {
        $board = array();
        $rows = 9;
        $rowOffsets = array(null, 0, 3, 6, 1, 4, 7, 2, 5, 8);
        $row = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
        for ($r = 1; $r <= $rows; $r++) {
            $rowCopy = $row;
            $offset = $rowOffsets[$r];
            while ($offset > 0) {
                array_push($rowCopy, array_shift($rowCopy)); // move first element to the end
                $offset--;
            }
            $board[] = $rowCopy;
        }
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function _shuffleBoard(array $board)
    {
        $shuffledBoard = $board;
        $allowedMethods = $this->getAllowedShuffleMethods();
        $allowedMethodsCount = count($allowedMethods) - 1;
        $count = self::SHUFFLE_COUNT;
        while ($count > 0) {
            $method = rand(0, $allowedMethodsCount);
            $method = $allowedMethods[$method];
            $method = '_shuffleBoardType' . $method;
            $shuffledBoard = $this->$method($shuffledBoard);
            $count--;
        }
        return $shuffledBoard;
    }

    /**
     * @return array
     */
    public function getAllowedShuffleMethods()
    {
        return array(
            'Transposing',
            'SwapRows',
            'SwapCols',
            'SwapSquareRows',
            'SwapSquareCols',
        );
    }

    /**
     * @param array $board
     * @return mixed
     */
    protected function _shuffleBoardTypeTransposing(array $board)
    {
        // I don't understand this magic. Thx Stackoverflow :)
        array_unshift($board, null);
        return call_user_func_array('array_map', $board);
    }

    /**
     * @param array $board
     * @return array
     */
    protected function _shuffleBoardTypeSwapRows(array $board)
    {
        $rowToShuffleNumber = rand(1, 9); // 8
        $rowToSwitchOffset = rand(1, 2); // 2
        $squareRow = ceil($rowToShuffleNumber / 3); // 3
        $rowToShufflePosition = $rowToShuffleNumber % 3; // 2
        if (!$rowToShufflePosition) $rowToShufflePosition = 3;
        $rowToSwitchPosition = ($rowToShufflePosition + $rowToSwitchOffset) % 3; // ( (2 + 2) % 3 ) = 1
        if (!$rowToSwitchPosition) $rowToSwitchPosition = 3;
        $rowToSwitchNumber = (int)(($squareRow - 1) * 3 + $rowToSwitchPosition); // (3 - 1) * 3 = 6 + 1 = 7
        // So we should switch row 8 and 7
        $rowToShuffleNumber--;
        $rowToSwitchNumber--;
        $tempRow = $board[$rowToShuffleNumber];
        $board[$rowToShuffleNumber] = $board[$rowToSwitchNumber];
        $board[$rowToSwitchNumber] = $tempRow;
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function _shuffleBoardTypeSwapCols(array $board)
    {
        $board = $this->_shuffleBoardTypeTransposing($board);
        $board = $this->_shuffleBoardTypeSwapRows($board);
        $board = $this->_shuffleBoardTypeTransposing($board);
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function _shuffleBoardTypeSwapSquareRows(array $board)
    {
        $squareToShuffleNumber = rand(1, 3); // 2
        $squareToSwitchOffset = rand(1, 2); // 2
        $squareToSwitchNumber = ($squareToShuffleNumber + $squareToSwitchOffset) % 3; // (2 + 2) % 3 = 1
        if (!$squareToSwitchNumber) $squareToSwitchNumber = 3;
        // So we should switch square 2 and 1
        $squareToShuffleStart = ($squareToShuffleNumber - 1) * 3; // (2 - 1) * 3 = 3
        $squareToSwitchStart = ($squareToSwitchNumber - 1) * 3; // (1 - 1) * 3 = 0
        $shuffleSquare = array_slice($board, $squareToShuffleStart, 3); // 4..6
        $switchSquare = array_slice($board, $squareToSwitchStart, 3); // 1..3
        array_splice($board, $squareToShuffleStart, 3, $switchSquare);
        array_splice($board, $squareToSwitchStart, 3, $shuffleSquare);
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function _shuffleBoardTypeSwapSquareCols(array $board)
    {
        $board = $this->_shuffleBoardTypeTransposing($board);
        $board = $this->_shuffleBoardTypeSwapSquareRows($board);
        $board = $this->_shuffleBoardTypeTransposing($board);
        return $board;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function _mergeBoardRows(array $board)
    {
        $mergedBoard = array();
        foreach ($board as $row) {
            $mergedBoard = array_merge($mergedBoard, $row);
        }
        return $mergedBoard;
    }

    /**
     * @param array $board
     * @param $count
     * @return array
     */
    protected function _getOpenCells(array $board, $count)
    {
        if (is_array($count)) {
            $count = rand($count['min'], $count['max']);
        }
        $keys = array_keys($board);
        shuffle($keys);
        $openCells = array();
        while ($count > 0) {
            $key = $keys[$count];
            $openCells[$key] = $board[$key];
            $count--;
        }
        return $openCells;
    }

    /**
     * @param array $board
     * @return array
     */
    protected function _normalizeBoardKeys(array $board)
    {
        $normalizedBoard = array();
        foreach ($board as $key => $value) {
            $key++; // 0 -> 1, 8 -> 9, 9 -> 10
            $row = ceil($key / 9);
            $col = $key % 9;
            if (!$col) $col = 9;
            $coords = $row . $col;
            $normalizedBoard[$coords] = $value;
        }
        return $normalizedBoard;
    }

    /**
     * @param array $cells
     * @return array
     */
    public static function checkFields(array $cells)
    {
        $errors = array();
        $openCellsPerRows = $openCellsPerCols = $openCellsPerSquares = array();
        foreach ($cells as $coords => $value) {
            list ($row, $col) = str_split($coords);
            $square = (int)((ceil($row / 3) - 1) * 3 + ceil($col / 3));

            if (empty($openCellsPerRows[$row])) {
                $openCellsPerRows[$row] = array();
            }
            $openCellsPerRows[$row][$coords] = $value;

            if (empty($openCellsPerCols[$col])) {
                $openCellsPerCols[$col] = array();
            }
            $openCellsPerCols[$col][$coords] = $value;

            if (empty($openCellsPerSquares[$square])) {
                $openCellsPerSquares[$square] = array();
            }
            $openCellsPerSquares[$square][$coords] = $value;
        }

        function checkCells(array $cells) {
            $errors = array();
            foreach ($cells as $data) {
                $exists = array();
                foreach ($data as $coords => $value) {
                    if (isset($exists[$value])) {
                        if (!isset($errors[$exists[$value]])) { // Save first element too
                            $errors[$exists[$value]] = $value;
                        }
                        if (!isset($errors[$coords])) {
                            $errors[$coords] = $value;
                        }
                    }
                    $exists[$value] = $coords;
                }
            }
            return $errors;
        }

        $errors += checkCells($openCellsPerRows);
        $errors += checkCells($openCellsPerCols);
        $errors += checkCells($openCellsPerSquares);
        return $errors;
    }

    /**
     * @param array $cells
     * @return array|bool
     */
    public static function checkGameSolution(array $cells)
    {
        $errors = self::checkFields($cells);
        if (!empty($errors)) {
            return $errors;
        }
        if (self::TOTAL_CELLS == count($cells)) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public static function getAllDifficulties()
    {
        $difficulties = array(
            self::PRACTICE_DIFFICULTY  => array('title' => 'Practice',  'openCells' => 40),
            self::EASY_DIFFICULTY      => array('title' => 'Easy',      'openCells' => 35),
            self::NORMAL_DIFFICULTY    => array('title' => 'Normal',    'openCells' => 30),
            self::EXPERT_DIFFICULTY    => array('title' => 'Expert',    'openCells' => 25),
            self::NIGHTMARE_DIFFICULTY => array('title' => 'Nightmare', 'openCells' => 20),
            self::RANDOM_DIFFICULTY    => array('title' => 'Random',    'openCells' => array('min' => 20, 'max' => 30)),
            self::TEST_DIFFICULTY      => array('title' => 'Test',      'openCells' => 78),
        );
        return $difficulties;
    }

    /**
     * @param $difficulty
     * @return array
     */
    public function getDifficultyParams($difficulty)
    {
        $difficulties = $this->getAllDifficulties();
        if (isset($difficulties[$difficulty])) {
            return $difficulties[$difficulty];
        } else {
            return array();
        }
    }

}
