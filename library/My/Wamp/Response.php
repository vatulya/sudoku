<?php

class My_Wamp_Response
{

    protected $response = [];

    public function getResponse()
    {
        return $this->response;
    }

    public function send(array $response)
    {
        $this->response += $response;
    }

}