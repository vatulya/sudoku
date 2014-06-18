<?php

class Sudoku_IndexController extends Zend_Controller_Action
{

    const EXAMPLE_OPEN_CELLS = 35;

    const DEFAULT_USER_GAMES_HISTORY_LIMIT = 5;
    const DEFAULT_TOP_USERS_RATING_LIMIT = 5;
    const DEFAULT_TOP_USERS_TIME_LIMIT = 5;

    const DEFAULT_PAGE_SIZE = 20;

    public $ajaxable = [
        'index'                => ['html'],
        'create'               => ['html', 'json'],
        'get-board'            => ['html'],
        'user-games-history'   => ['html'],
        'users-rating'         => ['html'],
        'check-field'          => ['json'],
        'user-action'          => ['json'],
        'get-top-users-time'   => ['html'],
        'get-top-users-rating' => ['html'],
    ];

    protected $_modules = [
        'index'              => ['my-games-history', 'top-users-rating', 'top-users-time'],
        'game'               => ['pause-game-button', 'top-users-rating', 'top-users-time'],
        'user-games-history' => ['my-games-history', 'top-users-rating', 'top-users-time'],
        'users-rating'       => ['my-games-history', 'top-users-rating', 'top-users-time'],
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
            'sudoku-default',
            true
        );
        $currentAction = $this->_request->getActionName();

        $modules = isset($this->_modules[$currentAction]) ? $this->_modules[$currentAction] : [];
        array_unshift($modules, 'new-game-button');

        $this->view->assign([
            'uLoginData'         => Application_Service_User::getULoginData($uLoginRedirectUrl),
            'currentUser'        => Application_Service_User::getInstance()->getCurrentUser(),
            'states'             => Application_Service_Game_Sudoku::getStates(),
            'difficulties'       => Application_Service_Difficulty_Sudoku::getInstance()->getAllDifficulties(),
            'rightColumnModules' => $modules,
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
        $vars          = [];
        $messages      = [];
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $difficulties  = Application_Service_Difficulty_Sudoku::getInstance()->getAllDifficulties();
        $difficulty    = $this->_request->getParam('difficulty', Application_Service_Difficulty_Sudoku::DEFAULT_GAME_DIFFICULTY);
        $gameType      = $this->_request->getParam('gameType');

        $vars['selectedDifficulty'] = $difficulty;

        if ($this->_request->getParam('submitted')) {
            $sudokuGame = null;
            try {
                $user = Application_Service_User::getInstance()->getCurrentUser();
                if (!isset($difficulties[$difficulty])) {
                    throw new Exception('Неправильная сложность. Выберите другую.');
                }
                if (in_array($gameType, [Application_Service_Game_Abstract::GAME_TYPE_VERSUS_BOT, Application_Service_Game_Abstract::GAME_TYPE_VERSUS_PLAYER])) {
                    if ($sudokuService->createMultiplayer($user['id'], ['difficulty' => $difficulty])) {
                        $vars['success']  = true;
                    }
                } else {
                    $sudokuGame = $sudokuService->create($user['id'], ['difficulty' => $difficulty]);
                    if ($sudokuGame instanceof Application_Model_Game_Abstract) {
                        $vars['gameHash'] = $sudokuGame->getHash();
                        $vars['success']  = true;
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
            } catch (Exception $e) {
                $messages[] = [
                    'name' => '',
                    'title' => '',
                    'text' => $e->getMessage(),
                    'type' => 'error',
                ];
            }
        }
        if (isset($difficulties[$difficulty]['open_cells'])) {
            $board = $sudokuService->generateBoard();
            $board = $sudokuService->normalizeBoardKeys($board);
            $openCells = $sudokuService->getOpenCells($board, $difficulties[$difficulty]['open_cells']);
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

        $userGamesHistory = $sudokuService->getUserGamesHistory($user['id']);
        $userGamesHistory->setLimit(static::DEFAULT_PAGE_SIZE)->setOffset($this->_request->getParam('offset'));

        $this->view->assign([
            'userGamesHistory' => $userGamesHistory,
            'user'             => $user,
        ]);
        $this->view->breadcrumbs[$this->_helper->Url->url(['action' => 'user-games-history'], 'sudoku', true)] = 'История игр';
        $this->view->pageCode = 'user-games-history';
        $this->_rightColumn();
    }

    public function usersRatingAction()
    {
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $where = [];

        $user = $this->_request->getParam('user');
        if ($user) {
            if ($user = Application_Service_User::getInstance()->getById($user)) {
//                $where['user_id'] = $user['id'];
            }
        }
        $where['difficulty_id'] = $this->getParam('difficulty', Application_Service_Difficulty_Sudoku::DEFAULT_GAME_DIFFICULTY);

        $order = $this->_request->getParam('sort', 'position') . ' ' . $this->_request->getParam('direction', 'ASC');

        $usersRating = $sudokuService->getUsersRating($where, [$order]);
        $usersRating->setLimit(static::DEFAULT_PAGE_SIZE)->setOffset($this->_request->getParam('offset'));

        $this->view->assign([
            'usersRating' => $usersRating,
            'user'        => $user ?: null,
        ]);
        $this->view->breadcrumbs[$this->_helper->Url->url(['action' => 'users-rating'], 'sudoku', true)] = 'Рейтинг игроков';
        $this->view->pageCode = 'users-rating';
        $this->_rightColumn();
    }

    public function getBoardAction()
    {
        $sudokuService = Application_Service_Game_Sudoku::getInstance();
        $difficulties = Application_Service_Difficulty_Sudoku::getInstance()->getAllDifficulties();
        $difficulty = $this->_request->getParam('difficulty');
        if (null === $difficulty) {
            $difficulty = Application_Service_Difficulty_Sudoku::DEFAULT_GAME_DIFFICULTY;
        }
        $boardExample = [];
        if (isset($difficulties[$difficulty]['open_cells'])) {
            $board = $sudokuService->generateBoard();
            $board = $sudokuService->normalizeBoardKeys($board);
            $openCells = $sudokuService->getOpenCells($board, $difficulties[$difficulty]['open_cells']);
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
        $game       = $this->_getParam('game_id');
        $action     = $this->_getParam('user_action');
        $parameters = $this->_getParam('parameters');

        $sudokuService = Application_Service_Game_Sudoku::getInstance();

        $game = $sudokuService->load($game);
        $game->logUserAction($action, $parameters);
    }

    public function getTopUsersTimeAction()
    {
        $difficulty = $this->getParam('top-users-time-difficulty');
        $difficulty = Application_Service_Difficulty_Sudoku::getInstance()->getDifficulty($difficulty, true);

        $sudokuService = Application_Service_Game_Sudoku::getInstance();

        $topUsersTime = $sudokuService->getUsersRating(
            ['difficulty_id' => $difficulty['id']],
            ['faster_game_duration ASC']
        );
        $topUsersTime->setLimit(static::DEFAULT_TOP_USERS_TIME_LIMIT);
        $this->view->assign([
            'topUsersTimeDifficulty' => $difficulty,
            'topUsersTime'           => $topUsersTime,
        ]);
    }

    public function getTopUsersRatingAction()
    {
        $difficulty = $this->getParam('top-users-rating-difficulty');
        $difficulty = Application_Service_Difficulty_Sudoku::getInstance()->getDifficulty($difficulty, true);

        $sudokuService = Application_Service_Game_Sudoku::getInstance();

        $topUsersRating = $sudokuService->getUsersRating(
            ['difficulty_id' => $difficulty['id']],
            ['rating DESC']
        );
        $topUsersRating->setLimit(static::DEFAULT_TOP_USERS_RATING_LIMIT);
        $this->view->assign([
            'topUsersRatingDifficulty' => $difficulty['id'],
            'topUsersRating'           => $topUsersRating,
        ]);
    }

    protected function _rightColumn($modules = [])
    {
        $user          = Application_Service_User::getInstance()->getCurrentUser();
        $sudokuService = Application_Service_Game_Sudoku::getInstance();

        if (empty($modules) || in_array('my-games-history', $modules)) {
            $gamesHistory = $sudokuService->getUserGamesHistory($user['id']);
            $gamesHistory->setLimit(static::DEFAULT_USER_GAMES_HISTORY_LIMIT);
            $this->view->gamesHistory = $gamesHistory;
        }

        if (empty($modules) || in_array('top-users-rating', $modules)) {
            $this->getTopUsersRatingAction();
        }
        if (empty($modules) || in_array('top-users-time', $modules)) {
            $this->getTopUsersTimeAction();
        }
    }

}