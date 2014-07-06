<?php

class Application_Service_Multiplayer_Sudoku extends Application_Service_Multiplayer_Abstract
{

    const CODE = 'sudoku';

    protected $modelDb = 'Sudoku_Multiplayer';

    /**
     * @param int $userId
     * @param array $parameters
     * @return Application_Model_Game_Abstract|int
     */
    public function create($userId, array $parameters = [])
    {
        $difficulty = isset($parameters['difficulty']) ? $parameters['difficulty'] : null;
        $difficulty = $this->serviceDifficulty->getDifficulty($difficulty, true);

        $gameType = isset($parameters['gameType']) ? $parameters['gameType'] : null;
        if (!in_array($gameType, [Application_Service_Game_Abstract::GAME_TYPE_VERSUS_BOT, Application_Service_Game_Abstract::GAME_TYPE_VERSUS_PLAYER])) {
            $gameType = Application_Service_Game_Abstract::GAME_TYPE_VERSUS_BOT;
        }

        $result = $this->getModelDb()->insert([
            'user_id'    => $userId,
            'difficulty' => $difficulty,
            'game_type'  => Application_Service_Game_Abstract::GAME_TYPE_VERSUS_BOT,
        ]);

        if ($gameType === Application_Service_Game_Abstract::GAME_TYPE_VERSUS_BOT) {
            $this->getModelDb()->update($result, ['state' => Application_Service_Multiplayer_Abstract::STATE_CREATED]);
            $result = Application_Service_Game_Sudoku::getInstance()->create($userId, [
                'difficulty'     => $difficulty,
                'multiplayer_id' => $result,
            ]);
        }

        return $result;
    }

}