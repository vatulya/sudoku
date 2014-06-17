<?php

abstract class Application_Service_Multiplayer_Abstract extends Application_Service_Abstract
{

    const STATE_NEW         = 0;
    const STATE_SEARCHING   = 1;
    const STATE_PAUSED      = 2;
    const STATE_REJECTED    = 3;
    const STATE_STARTED     = 4;
    const STATE_FINISHED    = 5;

}