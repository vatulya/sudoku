<?php

abstract class Application_Service_Multiplayer_Abstract extends Application_Service_Abstract
{

    const STATE_NEW         = 0;
    const STATE_SEARCHING   = 1;
    const STATE_PAUSED      = 2;
    const STATE_REJECTED    = 3;
    const STATE_STARTED     = 4;
    const STATE_FINISHED    = 5;

    /**
     * @var Application_Service_Difficulty_Abstract
     */
    protected $serviceDifficulty;

    /**
     * @return $this
     */
    protected function init()
    {
        parent::init();
        $service = 'Application_Service_Difficulty_' . ucfirst(static::CODE);
        $this->serviceDifficulty = $service::getInstance();
        return $this;
    }

}