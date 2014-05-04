<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    const EXAMPLE_OPEN_CELLS = 35;

    public $ajaxable = [
        'index'       => ['html'],
        'create'      => ['html', 'json'],
        'check-field' => ['json'],
        'user-action' => ['json'],
    ];

    public function init()
    {
        $this->_helper->getHelper('AjaxContext')->initContext();
    }

    public function preDispatch()
    {
        $this->view->breadcrumbs = [
            '/' => 'Главная страница',
        ];
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
        $vars = [];
        $messages = [];

        if ($this->_request->getParam('submit')) {
            $sudokuGame = null;
            $difficulty = $this->_request->getParam('difficulty');
            try {
                $sudokuService = Application_Service_Game_Sudoku::getInstance();
                $user = Application_Service_User::getInstance()->getCurrentUser();
                $difficulties = $sudokuService->getAllDifficulties();
                if (null === $difficulty) {
                    throw new Exception('выберите сложность');
                }
                if (!isset($difficulties[$difficulty])) {
                    throw new Exception('Неправильная сложность. Выберите другую');
                }
                $sudokuGame = $sudokuService->create($user['id'], ['difficulty' => $difficulty]);
            } catch (Exception $e) {
                $messages[] = [
                    'name' => '',
                    'title' => 'Ошибка при создании новой игры',
                    'text' => $e->getMessage(),
                    'type' => 'error',
                ];
            }
            if ($sudokuGame instanceof Application_Model_Game_Abstract) {
                $vars['gameHash'] = $sudokuGame->getHash();
                $vars['success'] = true;
                if (!$this->_request->isXmlHttpRequest()) {
                    $url = $this->_helper->Url->url(
                        [
                            'gameHash' => $vars['gameHash'],
                        ],
                        'sudoku-game',
                        true
                    );
                    return $this->redirect($url);
                }
            }
        }

        $vars['messages'] = $messages;
        $this->view->assign($vars);
        $this->view->breadcrumbs[$this->_helper->Url->url(['action' => 'create'], 'sudoku', true)] = 'Создание новой игры';
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
        $this->view->breadcrumbs[$this->_helper->Url->url(['action' => 'create'], 'sudoku', true)] = 'Игра';
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