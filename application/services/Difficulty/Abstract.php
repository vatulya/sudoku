<?php

abstract class Application_Service_Difficulty_Abstract extends Application_Service_Abstract
{

    const DIFFICULTY_TEST      = -1;
    const DIFFICULTY_RANDOM    = 0;
    const DIFFICULTY_PRACTICE  = 1;
    const DIFFICULTY_EASY      = 2;
    const DIFFICULTY_NORMAL    = 4;
    const DIFFICULTY_EXPERT    = 6;
    const DIFFICULTY_NIGHTMARE = 10;

    const DEFAULT_GAME_DIFFICULTY = 2;

    protected $modelDb = '';

    /**
     * @var array
     */
    protected $difficulties;

    protected function initDifficulties()
    {
        $this->difficulties = [];
        $rows = $this->getModelDb()->getAll()->getItems();
        foreach ($rows as $row) {
            $this->difficulties[$row['id']] = $row;
        }
    }

    /**
     * @return array
     */
    public function getAllDifficulties()
    {
        if (is_null($this->difficulties)) {
            $this->initDifficulties();
        }
        return $this->difficulties;
    }

    /**
     * @param int $difficulty
     * @param bool $orGetDefault
     * @return array
     */
    public function getDifficulty($difficulty, $orGetDefault = false)
    {
        $difficulties = $this->getAllDifficulties();
        if (isset($difficulties[$difficulty])) {
            return $difficulties[$difficulty];
        } elseif ($orGetDefault) {
            return $difficulties[static::DEFAULT_GAME_DIFFICULTY];
        } else {
            return [];
        }
    }

}
