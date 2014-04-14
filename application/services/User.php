<?php

/**
 * Class Application_Service_User
 *
 * @method Application_Model_Db_Users getModelDb()
 */
class Application_Service_User extends Application_Service_Abstract
{

    const ROLE_GUEST = 0;
    const ROLE_USER  = 20;

    protected $modelDb = 'Users';

    /**
     * @var My_Auth_User
     */
    protected $auth;

    /**
     * @return array
     */
    public function getCurrentUser()
    {
        $user = $this->getAuth()->getCurrentUser();
        if (empty($user)) {
            $userId = $this->registerGuest();
            $this->getAuth()->login(['id' => $userId]);
            $user = $this->getAuth()->getCurrentUser();
        }
        return $user;
    }

    /**
     * @param $id
     * @return array
     */
    public function getById($id)
    {
        return $this->getModelDb()->getOne(['id' => $id]);
    }

    /**
     * @param array $data
     * @return array
     */
    public function getByData(array $data)
    {
        $user = [];
        if (!empty($data['id'])) {
            $user = $this->getModelDb()->getOne(['id' => $data['id']]);
        }
        if (!$user && !empty($data['login'])) {
            $user = $this->getModelDb()->getOne(['login' => $data['login']]);
        }
        if (!$user && !empty($data['email'])) {
            $user = $this->getModelDb()->getOne(['email' => $data['email']]);
        }
        if (!$user && !empty($data['network']) && !empty($data['network_id'])) {
            $user = $this->getModelDb()->getOne(['network' => $data['network'], 'network_id' => $data['network_id']]);
        }
        return $user;
    }

    /**
     * @param string $token
     * @param string $host
     * @return array
     */
    public function ULogin($token, $host)
    {
        $s = file_get_contents('http://ulogin.ru/token.php?token=' . $token . '&host=' . $host);
        try {
            $user = Zend_Json::decode($s);
            if (!empty($user['error'])) {
                throw new Exception('uLogin error: "' . $user['error'] . '".');
            }
            $user = $this->convertULoginData($user);
        } catch (Exception $e) {
            $user = [];
        }
        return $user;
    }

    /**
     * @param array $userData
     * @return array
     */
    public function convertULoginData(array $userData)
    {
        $user = [
            'full_name'  => $userData['first_name'],
            'network'    => $userData['network'],
            'network_id' => $userData['uid'],
        ];
        return $user;
    }

    /**
     * @param string $redirectUrl
     * @return string
     */
    static public function getULoginData($redirectUrl)
    {
        $uLoginData = 'display=panel;';
        $uLoginData .= 'fields=first_name;';
        $uLoginData .= 'providers=google,facebook,odnoklassniki,mailru;';
        // vkontakte,odnoklassniki,mailru,
        // facebook,twitter,google,
        // yandex,livejournal,openid,
        // lastfm,linkedin,liveid,
        // soundcloud,steam,flickr,
        // vimeo,youtube,webmoney,
        // foursquare,tumblr,googleplus,
        // dudu
        $uLoginData .= 'redirect_uri=' . urlencode($redirectUrl);
        return $uLoginData;
    }

    /**
     * @param array $userData
     * @return int|bool
     */
    public function register(array $userData)
    {
        $userData['role_id'] = self::ROLE_GUEST;
        if (!empty($userData['login']) || !empty($userData['email']) || (!empty($userData['network']) && !empty($userData['network_id']))) {
            $userData['role_id'] = self::ROLE_USER;
        }
        if (isset($userData['password'])) {
            $userData['password'] = self::encodePassword($userData['password']);
        }
        $userId = $this->getModelDb()->insert($userData);
        return $userId;
    }

    /**
     * @return int
     */
    public function registerGuest()
    {
        $userData = ['role_id' => self::ROLE_GUEST];
        $userId = $this->getModelDb()->insert($userData);
        return $userId;
    }

    /**
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($userId, $newPassword)
    {
        $newPassword = self::encodePassword($newPassword);
        $result = $this->getModelDb()->update($userId, ['password' => $newPassword]);
        return $result;
    }

    /**
     * @param string $password
     * @return string
     */
    static public function encodePassword($password)
    {
        return sha1($password);
    }

    /**
     * @return My_Auth_User
     */
    public function getAuth()
    {
        if (is_null($this->auth)) {
            $this->auth = My_Auth_User::getInstance();
        }
        return $this->auth;
    }

    /**
     * @param int $roleId
     * @return string
     */
    static public function getRoleName($roleId)
    {
        $roles = self::getAllowedRoles();
        $role = isset($roles[$roleId]) ? $roles[$roleId] : $roles[self::ROLE_GUEST];
        return $role;
    }

    /**
     * @return array
     */
    static public function getAllowedRoles()
    {
        $roles = [
            self::ROLE_GUEST => 'Guest',
            self::ROLE_USER  => 'User',
        ];
        return $roles;
    }

}
