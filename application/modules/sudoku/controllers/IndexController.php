<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    const EXAMPLE_OPEN_CELLS = 35;

    const DEFAULT_USER_GAMES_HISTORY_LIMIT = 5;

    const DEFAULT_PAGE_SIZE = 20;

    public $ajaxable = [
        'index'              => ['html'],
        'create'             => ['html', 'json'],
        'get-board'          => ['html'],
        'user-games-history' => ['html'],
        'check-field'        => ['json'],
        'user-action'        => ['json'],
    ];

    public function init()
    {
        $this->_helper->getHelper('AjaxContext')->initContext();
    }

    public function preDispatch()
    {
        $this->view->pageCode = '';
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
            'currentUser'  => Application_Service_User::getInstance()->getCurrentUser(),
        ]);
    }

    public function indexAction()
    {
        $user = Application_Service_User::getInstance()->getCurrentUser();
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
        $gamesHistory = $sudokuService->getUserGamesHistory($user['id'], static::DEFAULT_USER_GAMES_HISTORY_LIMIT);
        $this->view->assign([
            'states'             => $sudokuService::getStates(),
            'difficulties'       => $sudokuService::getAllDifficulties(),
            'boardExample'       => $boardExample,
            'boardSolvedExample' => $boardSolvedExample,
            'gamesHistory'       => $gamesHistory,
        ]);
        $this->view->pageCode = 'index';
    }

    public function createAction()
    {
        $vars = [];
        $messages = [];
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $difficulties = $sudokuService->getAllDifficulties();
        $difficulty = $this->_request->getParam('difficulty');
        if (null === $difficulty) {
            $difficulty = $sudokuService::DEFAULT_GAME_DIFFICULTY;
        }
        $vars['selectedDifficulty'] = $difficulty;

        if ($this->_request->getParam('submit')) {
            $sudokuGame = null;
            try {
                $user = Application_Service_User::getInstance()->getCurrentUser();
                if (!isset($difficulties[$difficulty])) {
                    throw new Exception('Неправильная сложность. Выберите другую.');
                }
                $sudokuGame = $sudokuService->create($user['id'], ['difficulty' => $difficulty]);
            } catch (Exception $e) {
                $messages[] = [
                    'name' => '',
                    'title' => '',
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
        if (isset($difficulties[$difficulty]['openCells'])) {
            $board = $sudokuService->generateBoard();
            $board = $sudokuService->normalizeBoardKeys($board);
            $openCells = $sudokuService->getOpenCells($board, $difficulties[$difficulty]['openCells']);
            $vars['boardExample'] = [
                'openCells' => $openCells,
            ];
        }

        $vars['messages'] = $messages;
        $this->view->assign($vars);
        $this->view->breadcrumbs[$this->_helper->Url->url(['action' => 'create'], 'sudoku', true)] = 'Создание новой игры';
        $this->view->pageCode = 'create';
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
        $this->view->pageCode = 'game';
    }

    public function getBoardAction()
    {
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $difficulties = $sudokuService->getAllDifficulties();
        $difficulty = $this->_request->getParam('difficulty');
        if (null === $difficulty) {
            $difficulty = $sudokuService::DEFAULT_GAME_DIFFICULTY;
        }
        $boardExample = [];
        if (isset($difficulties[$difficulty]['openCells'])) {
            $board = $sudokuService->generateBoard();
            $board = $sudokuService->normalizeBoardKeys($board);
            $openCells = $sudokuService->getOpenCells($board, $difficulties[$difficulty]['openCells']);
            $boardExample = [
                'openCells' => $openCells,
            ];
        }
        $this->view->assign([
            'boardExample' => $boardExample,
            'hide'         => $this->_request->getParam('hide', false),
        ]);
    }

    public function userGamesHistoryAction()
    {
        $sudokuService = Application_Service_Game_Sudoku::getInstance();

        $user = $this->_request->getParam('user');
        if ($user) {
            $user = Application_Service_User::getInstance()->getById($user);
            if (!$user) {
                echo 'Wrong user'; die();
                // TODO: error
            }
        } else {
            $user = Application_Service_User::getInstance()->getCurrentUser();
        }

        $limit  = static::DEFAULT_PAGE_SIZE;
        $page = intval($this->_request->getParam('page'));
        $page = $page > 1 ? $page : 1;
        $offset = ($page - 1) * $limit;

        $gamesHistory     = $sudokuService->getUserGamesHistory($user['id'], static::DEFAULT_USER_GAMES_HISTORY_LIMIT);
        $userGamesHistory = $sudokuService->getUserGamesHistory($user['id'], $limit, $offset);

        $this->view->assign([
            'states'           => $sudokuService::getStates(),
            'difficulties'     => $sudokuService::getAllDifficulties(),
            'gamesHistory'     => $gamesHistory,
            'userGamesHistory' => $userGamesHistory,
            'user'             => $user,
            'previousPage' => $page - 1,
            'nextPage'     => $page + 1,
        ]);
        $this->view->breadcrumbs[$this->_helper->Url->url(['action' => 'user-games-history'], 'sudoku', true)] = 'История игр';
        $this->view->pageCode = 'user-games-history';
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