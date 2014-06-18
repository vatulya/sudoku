<?php

class Application_Service_Multiplayer_Sudoku extends Application_Service_Multiplayer_Abstract
{

    public function create($userId, array $parameters = [])
    {
        $difficulty = isset($parameters['difficulty']) ? $parameters['difficulty'] : null;
        $difficulty = $this->serviceDifficulty->getDifficulty($difficulty, true);

        $game = [
            'user_id'    => $userId,
            'difficulty' => $difficulty,
        ];
        $game = Application_Model_Multiplayer_Sudoku::create($game);
        return $game;
    }

}