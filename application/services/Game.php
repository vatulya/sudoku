<?php

/**
 * Class Application_Service_Game
 *
 * @method Application_Model_Db_Users getModelDb()
 */
class Application_Service_Game extends Application_Service_Abstract
{

    const GAME_MODEL_NAME_TEMPLATE = 'Application_Model_Game_%s';

    /**
     * @param $name
     * @return Application_Model_Game_Abstract|null
     * @throws RuntimeException
     */
    static public function factory($name)
    {
        $name = strtolower($name);
        $class = sprintf(self::GAME_MODEL_NAME_TEMPLATE, ucfirst($name));
        if (!class_exists($class)) {
            throw new RuntimeException('Wrong game name: "' . $name . '".');
        }
        return new $class();
    }

}