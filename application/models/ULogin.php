<?php

class Application_Model_ULogin extends Application_Model_Abstract
{

    const ULOGIN_HOST = 'http://ulogin.ru';

    public function login($token, $host)
    {
        $s = file_get_contents(self::ULOGIN_HOST . '/token.php?token=' . $token . '&host=' . $host);
        try {
            $user = Zend_Json::decode($s);
            if (!empty($user['error'])) {
                throw new Exception('uLogin error: "' . $user['error'] . '".');
            }
            $user = $this->convert($user);
        } catch (Exception $e) {
            $user = [];
        }
        return $user;
    }

    public function convert(array $userData)
    {
        $user = [
            'login'      => $userData['first_name'],
            'network'    => $userData['network'],
            'network_id' => $userData['uid'],
        ];
        return $user;
    }

}