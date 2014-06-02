<?php

class My_Wamp_Response
{

    /**
     * @var array
     */
    protected $response = [];

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param array $response
     */
    public function send(array $response)
    {
        $this->response += $response;
    }

}