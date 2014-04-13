<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    public $ajaxable = array(
        'index'       => array('html'),
        'check-field' => array('json'),
        'user-action' => array('json'),
    );

    public function init()
    {
        $this->_helper->getHelper('AjaxContext')->initContext();
    }

    public function preDispatch()
    {
    }

    public function postDispatch()
    {
        $uLoginRedirectUrl = $this->view->serverUrl();
        $uLoginRedirectUrl .= $this->_helper->Url->url(
            array(
                'controller' => 'user',
                'action'     => 'u-login'
            ),
            'sudoku',
            true
        );
        $this->view->assign(array(
            'uLoginData'   => Application_Service_User::getULoginData($uLoginRedirectUrl),
            'difficulties' => Application_Service_Game_Sudoku::getAllDifficulties(),
            'user'         => Application_Service_User::getInstance()->getCurrentUser(),
        ));
    }

    public function indexAction()
    {
        $difficulty = $this->_request->getParam('difficulty');
        $user = Application_Service_User::getInstance()->getCurrentUser();

        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $sudokuGame = $sudokuService->create($user['id'], array('difficulty' => $difficulty));

        $this->getHelper('redirector')->gotoRoute(['gameHash' => $sudokuGame->getHash()], 'sudoku-game', true);

        $this->view->assign(array(
            'sudoku' => $sudokuGame,
        ));
    }

    public function gameAction()
    {
        $gameHash = $this->_request->getParam('gameHash');
        if (empty($gameHash)) {
            $this->getHelper('redirector')->gotoRoute([], 'sudoku', true);
        }

        $user = Application_Service_User::getInstance()->getCurrentUser();

        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $sudokuGame = $sudokuService->loadByUserIdAndGameHash($user['id'], $gameHash);
        if (empty($sudokuGame)) {
            $this->getHelper('redirector')->gotoRoute([], 'sudoku', true);

        }
        $this->view->assign(array(
            'sudoku' => $sudokuGame,
        ));
    }

    public function checkFieldAction()
    {
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $game = $this->_getParam('game_id');
        $game = $sudokuService->load($game);
        $errors = $sudokuService->checkGameSolution($game);
        if (is_array($errors)) {
            $this->view->errors = $errors;
        } else {
            $this->view->resolved = (bool)$errors; // TRUE if resolved
        }
    }

    public function userActionAction()
    {
        $sudokuService = Application_Service_Game_Sudoku::getInstance();

        $game       = $this->_getParam('game_id');
        $action     = $this->_getParam('user_action');
        $parameters = $this->_getParam('parameters');

        $game = $sudokuService->load($game);
        $game->logUserAction($action, $parameters);
    }

}