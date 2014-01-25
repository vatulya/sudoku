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
            $user = array();
        }
        return $user;
    }

    public function convert(array $userData)
    {
        $user = array(
            'login'      => $userData['first_name'],
            'network'    => $userData['network'],
            'network_id' => $userData['uid'],
        );
        return $user;
    }

    static public function getLoginData($redirectUrl)
    {
        $uLoginData = 'display=panel;';
        $uLoginData .= 'fields=first_name;';
        $uLoginData .= 'providers=google,facebook,odnoklassniki,mailru;';
        // vkontakte,odnoklassniki,mailru,facebook,twitter,google,yandex,livejournal,openid,lastfm,linkedin,liveid,soundcloud,steam,flickr,vimeo,youtube,webmoney,foursquare,tumblr,googleplus,dudu
        $uLoginData .= 'redirect_uri=' . urlencode($redirectUrl);
        return $uLoginData;
    }

}