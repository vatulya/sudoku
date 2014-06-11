(function (w, d, $, undefined) {

    function Sudoku(gameHash) {

        var $Sudoku = this;

        $Sudoku.hash = gameHash;

        $Sudoku.SC = w.SC;
        var S = $Sudoku.SC.S;

        $Sudoku.table = $('#game-sudoku-' + $Sudoku.hash);
        $Sudoku.board = new SudokuBoard($Sudoku.table.find('.sudoku-board'));

        $Sudoku.pushTimer = false; // Mouse button push
        $Sudoku.pingTimer = false; // Ping timer
        $Sudoku.durationTimer = 0; // Update duration timer
        $Sudoku.duration = 0;
        $Sudoku.lastSystemDataMicrotime = 0;

        $Sudoku.selectedCell = undefined;
        $Sudoku.allowedNumbers = [true, true, true, true, true, true, true, true, true, true]; // 0..9
        $Sudoku.history = {'undo': false, 'redo': false};

        // BIND EVENTS
        var bindEvents = function () {

            $Sudoku.table
                .on('mouseover', '.sudoku-board .cell', function () {
                    // hover vertical col and horizontal row
                    $Sudoku.board.hoverColAndRow($(this));
                })
                .on('mouseout', '.sudoku-board', function () {
                    // unhover vertical col and horizontal row
                    $Sudoku.board.hoverColAndRow();
                })
                .on('click', '.sudoku-board .cell', function () {
                    // look mousedown/mouseup
                    $Sudoku.board.hoverNumber($Sudoku.board.getCellNumber($(this)));
                })
                .on('click', '.sudoku-numpad .number', function () {
                    var el = $(this);
                    if (!el.hasClass('disabled')) {
                        $Sudoku.checkNumber(el.data('number'));
                    }
                })
                .on('click', '.check-field', function () {
                    $Sudoku.checkBoard(this);
                })
                .on('click', '.clear-field', function () {
                    if (confirm('Вы действительно хотите очистить поле?\nЭто действие удалит все ваши ходы и пометки.')) {
                        $Sudoku.clearBoard(this);
                    }
                })
                .on('click', '.undo-move', function () {
                    if (!$(this).hasClass('disabled')) {
                        $Sudoku.useHistory('undo');
                    }
                })
                .on('click', '.redo-move', function () {
                    if (!$(this).hasClass('disabled')) {
                        $Sudoku.useHistory('redo');
                    }
                })
                .on('mouseover', '.sudoku-numpad.popup .number.enabled', function () {
                    $(this).addClass('hover');
                })
                .on('mouseout', '.sudoku-numpad.popup .number.enabled', function () {
                    $Sudoku.table.find('.sudoku-numpad.popup .number.hover').removeClass('hover');
                })
                .on('mousedown', '.cell.open', function () {
                    $Sudoku.mouseDown($(this));
                })
                .on('mouseup', '.sudoku-numpad.popup .number.enabled', function () {
                    $Sudoku.mouseUp($(this));
                })
                .on('mouseup', function () {
                    clearTimeout($Sudoku.pushTimer);
                    $Sudoku.table.find('.cell.pushed').removeClass('pushed');
                    $Sudoku.hidePopupNumpad();
                })
            ;

            $(d)
                .on('keypress', function (e) {
                    $Sudoku.keyPress(e.charCode);
                })
                .on('websocket' + S + 'open', function (e) {
                    $Sudoku.loadBoard();
                })
                .on('websocket' + S + 'close', function (e) {
                    $Sudoku._stopPing();
                })
                .on('websocket' + S + 'message' + S + 'sudoku' + S + 'systemData', function (e, data) {
                    if ($.isPlainObject(data['_system'])) {
                        $Sudoku.systemDataResponse(data['_system']);
                    }
                })
                .on('websocket' + S + 'message' + S + 'sudoku' + S + 'forceRefresh', function (e, data) {
                    $Sudoku.forceRefresh(data['reason'] || '');
                })
                .on('game.pause', function (e, state) {
                    state ? $Sudoku.pause() : $Sudoku.start();
                })
            ;

        };
        bindEvents();
        // BIND EVENTS

        // WEBSOCKET METHODS
        var websocketMethods = function () {

            /**************************** SEND USER ACTION **********************/

            $Sudoku.sendUserAction = function (action, parameters, callback) {
                parameters = $.extend({
                    '_game_hash': $Sudoku.hash,
                    '_action': action,
                    '_hash': $Sudoku.board.getBoardHash()
                }, parameters || {});
                $Sudoku.SC.call('sudoku', parameters).then(function(response) {
                    if ($Sudoku.systemDataResponse(response)) {
                        callback(response);
                    }
                });
            };

            /**************************** /SEND USER ACTION *********************/

            /**************************** LOAD BOARD ****************************/

            $Sudoku.loadBoard = function () {
                $Sudoku.sendUserAction('loadBoard', {}, $Sudoku.loadBoardResponse);
            };

            $Sudoku.loadBoardResponse = function (response) {
                $Sudoku.board.fillBoard(response);
                $Sudoku.checkHistoryButtons();
                $Sudoku.checkAllowedNumbers();
                $Sudoku.start();
            };

            /**************************** /LOAD BOARD ***************************/

            /**************************** GAME START / STOP *********************/

            $Sudoku.start = function () {
                $Sudoku.board.showBoard();
                $Sudoku._startDurationTimer();
                $Sudoku.sendUserAction('start');
                $Sudoku._startPing();
            };

            $Sudoku.pause = function () {
                $Sudoku.board.hideBoard();
                $Sudoku._stopPing();
                $Sudoku.sendUserAction('pause');
                $Sudoku._stopDurationTimer();
            };

            /**************************** /GAME START / STOP ********************/

            /**************************** SYSTEM DATA RESPONSE ******************/

            $Sudoku.systemDataResponse = function (response) {
                if ($.isPlainObject(response['_system'])) {
                    response = response['_system'];
                    if (typeof response['microtime'] != 'undefined' && response['microtime'] > $Sudoku.lastSystemDataMicrotime) {
                        $Sudoku.lastSystemDataMicrotime = response['microtime'];
                        $Sudoku.checkGameHash(response['gameHash'] || '');
                        $Sudoku.setHistory('undo', response['undoMove'] || {});
                        $Sudoku.setHistory('redo', response['redoMove'] || {});
                        $Sudoku.checkHistoryButtons();
                        $Sudoku.updateGameServerTime(response['duration']);
                        return true;
                    }
                }
                return false;
            };

            /**************************** /SYSTEM DATA RESPONSE *****************/

            /**************************** SET CELL NUMBER ***********************/

            $Sudoku.setCellNumber = function ($cell, number) {
                number = parseInt(number);
                if ($Sudoku['allowedNumbers'][number]) {
                    $Sudoku.board.setCell($cell, number);
                    $Sudoku.sendUserAction('setCell', $Sudoku.board.getBoardState());
                    $Sudoku.checkAllowedNumbers();
                }
            };

            /**************************** /SET CELL NUMBER **********************/

            /**************************** SET CELL MARK *************************/

            $Sudoku.setCellMark = function ($cell, mark) {
                mark ? $Sudoku.board.addCellMark($cell, mark) : $Board.setCellMarks($cell, []);
                $Sudoku.sendUserAction('setCell', $Sudoku.board.getBoardState());
            };

            /**************************** /SET CELL MARK ************************/

            /**************************** CLEAR BOARD ***************************/

            $Sudoku.clearBoard = function () {
                $Sudoku.clearHistory();
                $Sudoku.board.clearBoard();
                $Sudoku.sendUserAction('clearBoard');
            };

            /**************************** /CLEAR BOARD **************************/

            /**************************** CHECK BOARD ***************************/

            $Sudoku.checkBoard = function () {
                $Sudoku.sendUserAction('checkBoard', {}, $Sudoku.checkBoardResponse);
            };

            $Sudoku.checkBoardResponse = function (response) {
                if (response.resolved) {
                    $Sudoku.win();
                } else if (typeof response.errors == 'undefined' || response.errors.length == 0 || !response.errors) {
                    var board = $Sudoku.table.find('.sudoku-board').addClass('no-errors');
                    setTimeout(function () {board.removeClass('no-errors');}, 1000);
                } else {
                    $.each(response.errors, function (coords, number) {
                        $Sudoku.board.showError(coords[0], coords[1]);
                    });
                }
            };

            /**************************** /CHECK BOARD **************************/

            /**************************** PING **********************************/

            $Sudoku._startPing = function () {
                $Sudoku._stopPing();
                $Sudoku.pingTimer = setInterval(function () {
                    $Sudoku.sendUserAction('ping');
                }, 3000);
            };

            $Sudoku._stopPing = function () {
                clearInterval($Sudoku.pingTimer);
                $Sudoku.pingTimer = false;
            };

            /**************************** /PING *********************************/

            /**************************** DURATION ******************************/

            $Sudoku._startDurationTimer = function () {
                var timer = $Sudoku.table.find('.game-time');
                if (!$Sudoku.durationTimer) {
                    $Sudoku.durationTimer = setInterval(function () {
                        if ($Sudoku.duration > 0) {
                            $Sudoku.duration += 1;
                            timer.html($Sudoku.duration.toDDHHMMSS(false, true));
                        }
                    }, 1000);
                }
            };

            $Sudoku._stopDurationTimer = function () {
                clearInterval($Sudoku.durationTimer);
                $Sudoku.durationTimer = false;
            };

            $Sudoku.updateGameServerTime = function (time) {
                $Sudoku.duration = time;
            };

            /**************************** /DURATION ******************************/

        };
        websocketMethods();
        // WEBSOCKET METHODS

        // USER ACTION METHODS
        var userActionMethods = function () {

            $Sudoku.checkNumber = function (number) {
                var $cell = $Sudoku.getSelectedCell();
                if ($cell && $cell.length) {
                    $Sudoku.clearHistory();
                    $Sudoku.isMarkMode() ? $Sudoku.setCellMark($cell, number) : $Sudoku.setCellNumber($cell, number);
                    $Sudoku.board.hoverNumber($Sudoku.board.getCellNumber($cell));
                    $Sudoku.checkWinGame();
                }
            };

            /*** SELECTED CELL ***/

            $Sudoku.selectCell = function ($cell) {
                $Sudoku.table.find('.cell.selected').removeClass('selected');
                $Sudoku.selectedCell = $cell.addClass('selected');
            };

            $Sudoku.unselectCell = function () {
                $Sudoku.table.find('.cell.selected').removeClass('selected');
                $Sudoku.selectedCell = undefined;
            };

            $Sudoku.getSelectedCell = function () {
                return $Sudoku.selectedCell;
            };

            /*** SELECTED CELL ***/

            /******************************** UNDO REDO HISTORY *****************************/

            $Sudoku.setHistory = function (historyType, move) {
                if ($Sudoku.history.hasOwnProperty(historyType)) {
                    $Sudoku.history[historyType] = ($.isPlainObject(move) && !$.isEmptyObject(move)) ? move :  false;
                }
            };

            $Sudoku.checkHistoryButtons = function () {
                var undoButton = $Sudoku.table.find('.undo-move'),
                    redoButton = $Sudoku.table.find('.redo-move')
                    ;
                $Sudoku.history.hasOwnProperty('undo') && !$.isEmptyObject($Sudoku.history['undo'])
                    ? undoButton.removeClass('disabled')
                    : undoButton.addClass('disabled');
                $Sudoku.history.hasOwnProperty('redo') && !$.isEmptyObject($Sudoku.history['redo'])
                    ? redoButton.removeClass('disabled')
                    : redoButton.addClass('disabled');
            };

            $Sudoku.getLastMoveFromHistory = function (historyType) {
                if ($Sudoku.history.hasOwnProperty(historyType)) {
                    return $Sudoku.history[historyType];
                }
                return false;
            };

            $Sudoku.useHistory = function (historyType) {
                var cells = $Sudoku.getLastMoveFromHistory(historyType);
                $Sudoku.clearHistory();
                if (!$.isPlainObject(cells) || $.isEmptyObject(cells)) {
                    return false;
                }
                $.each(cells, function (coords, data) {
                    var $cell = $Sudoku.board.getCellByCoordsRow(coords),
                        number = typeof data['number'] != 'undefined' ? parseInt(data['number']) : undefined,
                        marks = typeof data['marks'] != 'undefined' ? data['marks'] : undefined
                        ;
                    $Sudoku.board.setCell($cell, number, marks);
                });
                $Sudoku.sendUserAction(historyType + 'Move', $Sudoku.board.getBoardState());
                return true;
            };

            $Sudoku.clearHistory = function () {
                $Sudoku.setHistory('undo');
                $Sudoku.setHistory('redo');
                $Sudoku.checkHistoryButtons();
            };

            /******************************** /UNDO REDO HISTORY ****************************/

        };
        userActionMethods();
        // USER ACTION METHODS

        // PROTECTED METHODS
        var protectedMethods = function () {

            $Sudoku.isMarkMode = function () {
                return $Sudoku.table.find('.mark-mode').hasClass('active');
            };

            $Sudoku.checkGameHash = function (hash) {
                return true;
            };

            $Sudoku.checkAllowedNumbers = function () {
                var numbersCount = {};
                $Sudoku.table.find('.cell').each(function (i, el) {
                    var number = '' + $Sudoku.board.getCellNumber($(el));
                    if (number) {
                        numbersCount.hasOwnProperty(number) ? numbersCount[number]++ : numbersCount[number] = 1;
                    }
                });
                $.each($Sudoku.allowedNumbers, function(number, isAllowed) {
                    $Sudoku['allowedNumbers'][number] = !(numbersCount.hasOwnProperty(number) && numbersCount[number] >= 9);
                });
                $Sudoku.checkNumpad();
            };

            $Sudoku.checkNumpad = function () {
                $Sudoku.table.find('.sudoku-numpad .number').each(function (i, el) {
                    el = $(el);
                    el.removeClass('disabled').addClass('enabled');
                    var number = '' + el.data('number');
                    if ($Sudoku['allowedNumbers'].hasOwnProperty(number) && !$Sudoku['allowedNumbers'][number]) {
                        el.addClass('disabled').removeClass('enabled');
                    }
                });
            };

            $Sudoku.checkWinGame = function () {
                if (!$Sudoku.table.find('.cell.empty').length) {
                    $Sudoku.checkBoard();
                }
            };

            $Sudoku.win = function () {
                $Sudoku.table.addClass('resolved');
                $Sudoku._stopDurationTimer();
                $Sudoku._stopPing();
            };

            $Sudoku.mouseDown = function ($cell) {
                $Sudoku.selectCell($cell.addClass('pushed'));
                $Sudoku.pushTimer = setTimeout(function () { $Sudoku.showPopupNumpad(); }, 500);
            };

            $Sudoku.mouseUp = function ($number) {
                $Sudoku.checkNumber($number.data('number'));
            };

            $Sudoku.getPopupNumpad = function () {
                var $numpad = $Sudoku.table.find('.sudoku-numpad').clone().addClass('popup');
                $Sudoku.table.append($numpad);
                w.disableSelect($numpad);
                return $numpad;
            };

            $Sudoku.showPopupNumpad = function () {
                $Sudoku.hidePopupNumpad();
                var $cell = $Sudoku.table.find('.cell.pushed'),
                    coords = $cell.position(),
                    popupNumpad = $Sudoku.getPopupNumpad().show()
                    ;
//                coords.top = coords.top - (popupNumpad.outerHeight() / 2)/* + $cell.outerHeight()*/;
//                coords.left = coords.left - (popupNumpad.outerWidth() / 2)/* + ($cell.outerWidth() / 2)*/;
//                popupNumpad.offset(coords);
                popupNumpad.css('position', 'absolute');
                popupNumpad.css('top', coords.top - (popupNumpad.outerHeight() / 2));
                popupNumpad.css('left', coords.left - (popupNumpad.outerWidth() / 2));
            };

            $Sudoku.hidePopupNumpad = function () {
                $Sudoku.table.find('.sudoku-numpad.popup').remove();
            };

            $Sudoku.keyPress = function (charCode) {
                if (charCode == 96 || charCode == 42) { // ~` OR *
                    $Sudoku.table.find('.mark-mode').click();
                } else {
                    var number = parseInt(String.fromCharCode(charCode));
                    if (number >= 0 && number <= 9) { // 0..9
                        $Sudoku.checkNumber(number);
                    }
                }
            };

            $Sudoku.forceRefresh = function (reason) {
                alert('Принудительная перезагрузка страницы. Причина: ' + reason);
                w.location.reload();
            };

        };
        protectedMethods();
        // PROTECTED METHODS
    }

    w.Sudoku = Sudoku;

})(this, this.document, this.jQuery);
