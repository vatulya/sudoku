<?php

class Application_Model_Sudoku extends Application_Model_Abstract
{

    const DEFAULT_GAME_DIFFICULTY = 2;

    const TOTAL_CELLS = 81;

    public function createGame($difficulty = self::DEFAULT_GAME_DIFFICULTY)
    {
        // TODO: create game from resolved board

        $totalOpenCells = 30;
        $attempts = 1000;
        $openCells = $openCellsPerRows = $openCellsPerCols = $openCellsPerSquares = array();
        while ($attempts > 0 && $totalOpenCells > 0) {
            $error = false;
            $cellNumber = rand(1, self::TOTAL_CELLS);
            $row = (int)ceil($cellNumber / 9);
            $col = $cellNumber % 9;
            if (!$col) $col = 9;
            $square = (int)((ceil($row / 3) - 1) * 3 + ceil($col / 3));
            $cellValue = rand(1,9);

            try {
                if (isset($openCells[$cellNumber])) {
                    throw new Exception('Cell #' . $cellNumber . ' already opened');
                }
                if (isset($openCellsPerRows[$row][$cellValue])) {
                    throw new Exception('Cell with value "' . $cellValue . '" already opened in same row #' . $row);
                }
                if (isset($openCellsPerCols[$col][$cellValue])) {
                    throw new Exception('Cell with value "' . $cellValue . '" already opened in same col #' . $col);
                }
                if (isset($openCellsPerSquares[$square][$cellValue])) {
                    throw new Exception('Cell with value "' . $cellValue . '" already opened in same square #' . $square);
                }
            } catch (Exception $e) {
                $error = true;
            }

            if (!$error) {
                $openCells[$cellNumber] = $cellValue;

                if (empty($openCellsPerRows[$row])) {
                    $openCellsPerRows[$row] = array();
                }
                $openCellsPerRows[$row][$cellValue] = $cellValue;

                if (empty($openCellsPerCols[$col])) {
                    $openCellsPerCols[$col] = array();
                }
                $openCellsPerCols[$col][$cellValue] = $cellValue;

                if (empty($openCellsPerSquares[$square])) {
                    $openCellsPerSquares[$square] = array();
                }
                $openCellsPerSquares[$square][$cellValue] = $cellValue;
            }

            if ($error) {
                $attempts--;
            } else {
                $totalOpenCells--;
            }
        }
        return $openCells;
    }

    public function checkField(array $cells)
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

}
