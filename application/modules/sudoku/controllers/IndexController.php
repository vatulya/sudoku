<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    const EXAMPLE_OPEN_CELLS = 35;

    public $ajaxable = [
        'index'       => ['html'],
        'create'      => ['html'],
        'check-field' => ['json'],
        'user-action' => ['json'],
    ];

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
            [
                'controller' => 'user',
                'action'     => 'u-login'
            ],
            'sudoku',
            true
        );
        $this->view->assign([
            'uLoginData'   => Application_Service_User::getULoginData($uLoginRedirectUrl),
            'difficulties' => Application_Service_Game_Sudoku::getAllDifficulties(),
            'user'         => Application_Service_User::getInstance()->getCurrentUser(),
        ]);
    }

    public function indexAction()
    {
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $board = $sudokuService->generateBoard();
        $board = $sudokuService->normalizeBoardKeys($board);
        $openCells = $sudokuService->getOpenCells($board, static::EXAMPLE_OPEN_CELLS);
        $boardExample = [
            'openCells' => $openCells,
        ];
        $boardSolvedExample = [
            'openCells'    => $openCells,
            'checkedCells' => array_diff_key($board, $openCells),
        ];
        $this->view->assign([
            'difficulties'       => $sudokuService::getAllDifficulties(),
            'boardExample'       => $boardExample,
            'boardSolvedExample' => $boardSolvedExample,
        ]);
    }

    public function createAction()
    {
        $errors = [];
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $user = Application_Service_User::getInstance()->getCurrentUser();
        try {
            $difficulty = $this->_request->getParam('difficulty');
            $sudokuGame = $sudokuService->create($user['id'], ['difficulty' => $difficulty]);
            if ($sudokuGame) {
                $this->getHelper('redirector')->gotoRoute(['gameHash' => $sudokuGame->getHash()], 'sudoku-game', true);
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        $this->view->assign([
            'errors' => $errors,
        ]);
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
        $this->view->assign([
            'sudoku' => $sudokuGame,
        ]);
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