<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    const EXAMPLE_OPEN_CELLS = 35;

    const DEFAULT_USER_GAMES_HISTORY_LIMIT = 5;
    const DEFAULT_USER_RATING_LIMIT = 5;

    const DEFAULT_PAGE_SIZE = 10;

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
            'currentUser'  => Application_Service_User::getInstance()->getCurrentUser(),
            'states'       => Application_Service_Game_Sudoku::getStates(),
            'difficulties' => Application_Service_Game_Sudoku::getAllDifficulties(),
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
            'boardExample'       => $boardExample,
            'boardSolvedExample' => $boardSolvedExample,
        ]);
        $this->view->pageCode = 'index';
        $this->_rightColumn();
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

        if ($this->_request->getParam('submitted')) {
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
            'sudoku'       => $sudokuGame,
        ]);
        $this->view->breadcrumbs[$this->_helper->Url->url(['action' => 'create'], 'sudoku', true)] = 'Игра';
        $this->view->pageCode = 'game';
        $this->_rightColumn();
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

        $offset = intval($this->_request->getParam('offset'));
        $userGamesHistory = $sudokuService->getUserGamesHistory($user['id'], static::DEFAULT_PAGE_SIZE, $offset);

        $this->view->assign([
            'userGamesHistory' => $userGamesHistory,
            'user'             => $user,
        ]);
        $this->view->breadcrumbs[$this->_helper->Url->url(['action' => 'user-games-history'], 'sudoku', true)] = 'История игр';
        $this->view->pageCode = 'user-games-history';
        $this->_rightColumn();
    }

    protected function _rightColumn($modules = [])
    {
        $user          = Application_Service_User::getInstance()->getCurrentUser();
        $sudokuService = Application_Service_Game_Sudoku::getInstance();

        if (empty($modules) || in_array('my-games-history', $modules)) {
            $gamesHistory = $sudokuService->getUserGamesHistory($user['id'], static::DEFAULT_USER_GAMES_HISTORY_LIMIT);
            $this->view->gamesHistory = $gamesHistory;
        }

        if (false && empty($modules) || in_array('my-rating', $modules)) {
            $topUsersRating = $sudokuService->getUsersRating(
                $this->getParam('my-rating-difficulty', $sudokuService::DEFAULT_GAME_DIFFICULTY)
            );
            $this->view->topUsersRating = $topUsersRating;
        }
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