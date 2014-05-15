(function (w, d, $) {

    function Sudoku(gameHash) {

        var $Sudoku = this;

        $Sudoku.websocket = new w.WS();

        var S = $Sudoku.websocket.S;

        $Sudoku.hash = gameHash;

        $Sudoku.pushTimer = false; // Mouse button push
        $Sudoku.pingTimer = false; // Ping timer
        $Sudoku.durationTimer = 0; // Update duration timer

        $Sudoku.duration = 0;

        $Sudoku.table = $('#game-sudoku-' + $Sudoku.hash);
        w.disableSelect($Sudoku.table);

        // BIND EVENTS
        var bindEvents = function() {

            $Sudoku.table
                .on('mouseover', '.sudoku-board .cell', function() {
                    // .hover vertical col and horizontal row
                    $Sudoku.hoverColAndRow(this);
                })
                .on('mouseout', function() {
                    $Sudoku.hoverColAndRow(); // un .hover vertical cols and horizontal rows
                })
                .on('click', '.sudoku-board .cell', function() {
                    // look mousedown/mouseup
                    $Sudoku.hoverNumber(this);
                })
                .on('click', '.sudoku-board .cell.open', function() {
                    // look mousedown/mouseup
    //            Sudoku.cellClick(this);
                })
                .on('click', '.sudoku-numpad .number', function() {
                    var el = $(this);
                    if (!el.hasClass('disabled')) {
                        $Sudoku.checkNumber(el.data('number'));
                    }
                })
                .on('click', '.check-field', function() {
                    $Sudoku.checkBoard(this);
                })
                .on('click', '.clear-field', function() {
                    if (confirm('Вы действительно хотите очистить поле?\nЭто действие удалит все ваши ходы и пометки.')) {
                        $Sudoku.clearBoard(this);
                    }
                })
                .on('click', '.undo-move', function() {
                    $Sudoku.undoMove(this);
                })
                .on('click', '.redo-move', function() {
                    $Sudoku.redoMove(this);
                })
                .on('mouseover', '.sudoku-numpad.popup .number.enabled', function() {
                    $(this).addClass('hover');
                })
                .on('mouseout', '.sudoku-numpad.popup .number.enabled', function() {
                    $Sudoku.table.find('.sudoku-numpad.popup .number.hover').removeClass('hover');
                })
                .on('mousedown', '.cell.open', function() {
                    $Sudoku.mouseDown(this);
                })
                .on('mouseup', '.sudoku-numpad.popup .number.enabled', function() {
                    $Sudoku.mouseUp(this);
                })
                .on('mouseup', function() {
                    clearTimeout($Sudoku.pushTimer);
                    $Sudoku.table.find('.cell.pushed').removeClass('pushed');
                    $Sudoku.hidePopupNumpad();
                })
            ;

            $(d)
                .on('keypress', function(e) {
                    $Sudoku.keyPress(e.charCode);
                })
                .on('websocket' + S + 'open', function(e) {
                    $Sudoku.loadBoard();
                })
                .on('websocket' + S + 'close', function(e) {
                    $Sudoku._stopPing();
                })
                .on('websocket' + S + 'message' + S + 'sudoku' + S + 'systemData', function(e, data) {
                    $Sudoku.systemDataResponse(data['_system'] || {});
                })
                .on('websocket' + S + 'message' + S + 'sudoku' + S + 'forceRefresh', function(e, data) {
                    $Sudoku.forceRefresh(data['reason'] || '');
                })
                .on('game.pause', function(e, state) {
                    state ? $Sudoku.pause() : $Sudoku.start();
                })
            ;

        };
        bindEvents();
        // BIND EVENTS

        // WEBSOCKET METHODS
        var websocketMethods = function() {

            /**************************** SEND USER ACTION **********************/

            $Sudoku.sendUserAction = function(action, parameters, withQueue, callback) {
                parameters = $.extend({
                    '_game_hash': $Sudoku.hash,
                    '_action': action,
                    '_hash': $Sudoku.getBoardHash()
                }, parameters || {});
                var config = {
                    'data': parameters,
                    'callback': callback
                };
                $Sudoku.websocket.send(config, 'sudoku', withQueue ? 'sudoku' : '');
            };

            /**************************** /SEND USER ACTION *********************/

            /**************************** LOAD BOARD ****************************/

            $Sudoku.loadBoard = function() {
                $Sudoku.sendUserAction('loadBoard', {}, true, $Sudoku.loadBoardResponse);
            };

            $Sudoku.loadBoardResponse = function(response) {
                $Sudoku._fillBoard(response);
                $Sudoku.start();
            };

            /**************************** /LOAD BOARD ***************************/

            /**************************** GAME START / STOP *********************/

            $Sudoku.start = function() {
                $Sudoku._showBoard();
                $Sudoku._startDurationTimer();
                $Sudoku.sendUserAction('start', {}, true);
                $Sudoku._startPing();
            };

            $Sudoku.pause = function() {
                $Sudoku._hideBoard();
                $Sudoku._stopPing();
                $Sudoku.sendUserAction('pause', {}, true);
                $Sudoku._stopDurationTimer();
            };

            /**************************** /GAME START / STOP ********************/

            /**************************** SYSTEM DATA RESPONSE ******************/

            $Sudoku.systemDataResponse = function(response) {
                $Sudoku.checkGameHash(response['gameHash'] || '');
                $Sudoku.setHistoryButton('undo', response['undoMove'] || {});
                $Sudoku.setHistoryButton('redo', response['redoMove'] || {});
                $Sudoku.checkHistoryButtons();
                $Sudoku.updateGameServerTime(response['duration']);
            };

            /**************************** /SYSTEM DATA RESPONSE *****************/

            /************************ SET CELL NUMBER ***********************/

            $Sudoku.setCellNumber = function(cell, number) {
                cell = $(cell);
                if ($Sudoku._setCellNumber(cell, number)) {
                    var coords = '' + $Sudoku.getCellCoords(cell);
                    var data = {
                        'coords': coords,
                        'number': cell.data('number'),
                        'marks': $Sudoku.getCellMarks(cell)
                    };
                    $Sudoku.sendUserAction('setCellNumber', data, true);
                }
            };

            $Sudoku._setCellNumber = function(cell, number) {
                cell = $(cell);
                number = number ? '' + number : '';
                if (cell.hasClass('open')) {
                    if ($Sudoku.isMarkMode() && cell.hasClass('empty')) {
                        cell.addClass('marks');
                        if (number) {
                            cell.toggleClass('mark-' + number);
                        }
                    } else {
                        cell.data('number', number);
                        cell.find('.number-container').html(number);
                        if (number) {
                            cell.removeClass('empty marks');
                        } else {
                            cell.addClass('empty marks');
                        }
                        $Sudoku.checkNumbersCount(cell);
                    }
                    return true;
                }
                return false;
            };

            /************************ /SET CELL NUMBER ***********************/

            /************************ CLEAR BOARD ****************************/

            $Sudoku.clearBoard = function() {
                var classes = 'marks mark-1 mark-2 mark-3 mark-4 mark-5 mark-6 mark-7 mark-8 mark-9';
                $Sudoku.table.find('.cell.open').each(function(i, el) {
                    el = $(el);
                    el.data('number', '');
                    el.find('.number-container').html('');
                    el.removeClass(classes);
                });
                $Sudoku.clearHistory();
                $Sudoku.sendUserAction('clearBoard', {}, true);
            };

            /************************ /CLEAR BOARD ****************************/

            /************************* CHECK BOARD ****************************/

            $Sudoku.checkBoard = function() {
                $Sudoku.sendUserAction('checkBoard', {}, true, $Sudoku.checkBoardResponse);
            };

            $Sudoku.checkBoardResponse = function(response) {
                if (response.resolved) {
                    $Sudoku.win();
                } else if (typeof response.errors == 'undefined' || response.errors.length == 0 || !response.errors) {
                    var board = $Sudoku.table.find('.sudoku-board').addClass('no-errors');
                    setTimeout(function() {board.removeClass('no-errors');}, 1000);
                } else {
                    $.each(response.errors, function(coords, number) {
                        coords = coords.split('');
                        $Sudoku.showError(coords[0], coords[1]);
                    });
                }
            };

            /************************* /CHECK BOARD ****************************/

            /**************************** PING *********************************/

            $Sudoku._startPing = function() {
                $Sudoku._stopPing();
                $Sudoku.pingTimer = setInterval(function() {
                    $Sudoku.sendUserAction('ping');
                }, 3000);
            };

            $Sudoku._stopPing = function() {
                clearInterval($Sudoku.pingTimer);
                $Sudoku.pingTimer = false;
            };

            /**************************** /PING *********************************/

            $Sudoku._startDurationTimer = function() {
                if (!$Sudoku.durationTimer) {
                    $Sudoku.durationTimer = setInterval(function() {
                        if ($Sudoku.duration > 0) {
                            $Sudoku.duration += 1;
                            $Sudoku.table.find('.game-time').html($Sudoku.duration.toDDHHMMSS(false, true));
                        }
                    }, 1000);
                }
            };

            $Sudoku._stopDurationTimer = function() {
                clearInterval($Sudoku.durationTimer);
                $Sudoku.durationTimer = false;
            };

            $Sudoku.updateGameServerTime = function(time) {
                $Sudoku.duration = time;
            }

        };
        websocketMethods();
        // WEBSOCKET METHODS

        // USER ACTION METHODS
        var userActionMethods = function() {

            $Sudoku.hoverColAndRow = function(cell) {
                cell = $(cell);
                $Sudoku.table.find('.cell.hover').removeClass('hover');
                if (cell.length) {
                    var row = cell.data('row'),
                        col = cell.data('col')
                        ;
                    $Sudoku.table.find('.cell.row-' + row + ', .cell.col-' + col).each(function(i, c){
                        c = $(c);
                        if (c.data('row') != row || c.data('col') != col) {
                            c.addClass('hover');
                        }
                    });
                }
            };

            $Sudoku.checkNumber = function(number) {
                var cell = $Sudoku.getSelectedCell();
                $Sudoku.setCellNumber(cell, number);
                $Sudoku.clearHistory('redo');
                $Sudoku.hoverNumber(cell);
                $Sudoku.checkWinGame();
            };

            $Sudoku.hoverNumber = function(cell) {
                cell = $(cell);
                $Sudoku.table.find('.cell.hovered').removeClass('hovered');
                var number = cell.data('number');
                if (number) {
                    $Sudoku.table.find('.cell').each(function(i, c) {
                        c = $(c);
                        if (c.data('number') == number) {
                            c.addClass('hovered');
                        }
                    });
                }
            };

            $Sudoku.cellClick = function(cell) {
                cell = $(cell);
                if (cell.hasClass('selected')) {
                    $Sudoku.unselectCell(cell);
                } else {
                    $Sudoku.selectCell(cell);
                }
            };

            $Sudoku.selectCell = function(cell) {
                cell = $(cell);
                var row = cell.data('row'),
                    col = cell.data('col')
                    ;
                $Sudoku.table.data('selected-cell', '' + row + col);
                $Sudoku.table.find('.cell.selected').removeClass('selected');
                $Sudoku.table.find('.cell.row-' + row + '.col-' + col).addClass('selected');
            };

            $Sudoku.unselectCell = function(cell) {
                cell = $(cell);
                $Sudoku.table.find('.cell.selected').removeClass('selected');
                $Sudoku.table.data('selected-cell', '');
            };

            $Sudoku.getSelectedCell = function() {
                var coords = $Sudoku.table.data('selected-cell'),
                    cell = $Sudoku.getCellByCoords(coords)
                    ;
                return cell;
            };

            $Sudoku.getCellCoords = function(cell) {
                cell = $(cell);
                var coords = '' + cell.data('row') + cell.data('col');
                return coords;
            };

            $Sudoku.getCellByCoords = function(coords) {
                coords = coords.split(''); // '' + row + col
                var cell = $Sudoku.table.find('.cell.row-' + coords[0] + '.col-' + coords[1]);
                return cell;
            };

            $Sudoku.showError = function(row, col) {
                var cell = $Sudoku.table.find('.cell.row-' + row + '.col-' + col);
                if (cell.length) {
                    cell.addClass('error');
                    setTimeout(function() {cell.removeClass('error')}, 2000);
                }
            };


            /******************************** UNDO REDO HISTORY *****************************/

            $Sudoku.setHistoryButton = function(historyType, move) {
                move = Object.keys(move).length ? JSON.stringify(move) : '';
                historyType = historyType == 'redo' ? 'redo' : 'undo';
                $Sudoku.table.find('.' + historyType + '-move').data('moves', move);
                return $Sudoku;
            };

            $Sudoku.checkHistoryButtons = function() {
                var undoButton = $Sudoku.table.find('.undo-move'),
                    redoButton = $Sudoku.table.find('.redo-move')
                    ;

                if (undoButton.data('moves')) {
                    undoButton.removeClass('disabled');
                } else {
                    undoButton.addClass('disabled');
                }

                if (redoButton.data('moves')) {
                    redoButton.removeClass('disabled');
                } else {
                    redoButton.addClass('disabled');
                }
                return $Sudoku;
            };

            $Sudoku.removeLastMoveFromHistory = function(historyButton) {
                $(historyButton).data('moves', '');
            };

            $Sudoku.clearHistory = function(historyType) {
                if (historyType) {
                    historyType = historyType == 'redo' ? 'redo' : 'undo';
                    $Sudoku.table.find('.' + historyType + '-move').data('moves', '');
                } else {
                    $Sudoku.table.find('.undo-move').data('moves', '');
                    $Sudoku.table.find('.redo-move').data('moves', '');
                }
                $Sudoku.checkHistoryButtons();
            };

            $Sudoku.getLastMoveFromHistory = function(historyButton) {
                historyButton = $(historyButton);
                var moves = JSON.parse(historyButton.data('moves'))['cells'] || {},
                    movesData = []
                    ;
                for (var coords in moves) {
                    if (moves.hasOwnProperty(coords)) {
                        movesData.push({
                            'coords': coords,
                            'number': moves[coords]
                        });
                    }
                }
                return movesData;
            };

            $Sudoku.undoMove = function(undoButton) {
                undoButton = $(undoButton);
                if (undoButton.hasClass('disabled')) {
                    return;
                }
                var moves = $Sudoku.getLastMoveFromHistory(undoButton);
                $.each(moves, function (i, move) {
                    var cell = $Sudoku.getCellByCoords(move['coords']);
                    $Sudoku._setCellNumber(cell, move['number']);
                });
                $Sudoku.removeLastMoveFromHistory(undoButton);
                $Sudoku.checkHistoryButtons();
                $Sudoku.sendUserAction('undoMove', {}, true);

//            cell = $Sudoku.getSelectedCell();
//            $Sudoku.hoverNumber(cell);
            };

            $Sudoku.redoMove = function(redoButton) {
                redoButton = $(redoButton);
                if (redoButton.hasClass('disabled')) {
                    return;
                }
                var moves = $Sudoku.getLastMoveFromHistory(redoButton);
                $.each(moves, function (i, move) {
                    var cell = $Sudoku.getCellByCoords(move['coords']);
                    $Sudoku._setCellNumber(cell, move['number']);
                });
                $Sudoku.removeLastMoveFromHistory(redoButton);
                $Sudoku.checkHistoryButtons();
                $Sudoku.sendUserAction('redoMove', {}, true);

//            cell = $Sudoku.getSelectedCell();
//            $Sudoku.hoverNumber(cell);
            };

            /******************************** /UNDO REDO HISTORY ****************************/

        };
        userActionMethods();
        // USER ACTION METHODS

        // PROTECTED METHODS
        var protectedMethods = function() {

            $Sudoku.isMarkMode = function() {
                return $Sudoku.table.find('.mark-mode').hasClass('active');
            };

            $Sudoku.getCellMarks = function(cell) {
                var marks = '';
                for (var i = 1; i <= 9; i++) {
                    if (cell.hasClass('mark-' + i)) {
                        marks += (marks == '') ? i : ',' + i;
                    }
                }
                return marks;
            };

            $Sudoku._fillBoard = function(data) {
                $Sudoku._clearBoard();
                $.each(data['openCells'] || {}, function(coords, number) {
                    if (number) {
                        $Sudoku.getCellByCoords(coords)
                            .data('number', number || '')
                            .removeClass('open empty')
                            .addClass('locked')
                        ;
                    }
                });
                $.each(data['checkedCells'] || {}, function(coords, number) {
                    var cell = $Sudoku.getCellByCoords(coords);
                    if (number && !cell.data('number')) {
                        cell
                            .data('number', number || '')
                            .removeClass('empty')
                        ;
                    }
                });
            };

            $Sudoku._clearBoard = function() {
                $Sudoku.table.find('.cell')
                    .removeClass('locked selected hover')
                    .addClass('open empty')
                    .data('number', '')
                    .find('.number-container').html('')
                ;
            };

            $Sudoku._showBoard = function() {
                $Sudoku.table.find('.cell').each(function(i, el) {
                    el = $(el);
                    el.find('.number-container').html(el.data('number'));
                });
            };

            $Sudoku._hideBoard = function() {
                $Sudoku.table.find('.cell').html('');
            };

            $Sudoku.getBoardHash = function() {
                var board = '';
                $Sudoku.table.find('.cell').each(function(i, el) {
                    el = $(el);
                    var number = '' + el.data('number');
                    board += number || '0';
                });
                return $.md5(board);
            };

            $Sudoku.checkGameHash = function(hash) {
                return true;
            };

            $Sudoku.checkNumbersCount = function() {
                var numbersCount = {};
                $Sudoku.table.find('.cell').each(function(i, el) {
                    el = $(el);
                    var number = '' + el.data('number');
                    if (number) {
                        if (numbersCount[number]) {
                            numbersCount[number]++;
                        } else {
                            numbersCount[number] = 1;
                        }
                    }
                });
                $Sudoku.table.find('.sudoku-numpad .number').each(function(i, el) {
                    el = $(el);
                    el.removeClass('disabled').addClass('enabled');
                    var number = el.data('number');
                    if (numbersCount[number]) {
                        el.data('count', numbersCount[number]);
                        if (numbersCount[number] >= 9) {
                            el.addClass('disabled').removeClass('enabled');
                        }
                    } else {
                        el.data('count', 0);
                    }
                });
            };

            $Sudoku.checkWinGame = function() {
                if (!$Sudoku.table.find('.cell.empty').length) {
                    $Sudoku.checkBoard();
                }
            };

            $Sudoku.win = function() {
                $Sudoku.table.addClass('resolved');
                $Sudoku._stopDurationTimer();
                $Sudoku._stopPing();
            };

            $Sudoku.mouseDown = function(cell) {
                cell = $(cell);
                cell.addClass('pushed');
                $Sudoku.selectCell(cell);
                $Sudoku.pushTimer = setTimeout(function() {$Sudoku.showPopupNumpad();}, 500);
            };

            $Sudoku.mouseUp = function(number) {
                number = $(number);
                $Sudoku.checkNumber(number.data('number'));
            };

            $Sudoku.getPopupNumpad = function() {
                var numpad = $Sudoku.table.find('.sudoku-numpad').clone().addClass('popup');
                $Sudoku.table.append(numpad);
                w.disableSelect(numpad);
                return numpad;
            };

            $Sudoku.showPopupNumpad = function() {
                $Sudoku.hidePopupNumpad();
                var cell = $Sudoku.table.find('.cell.pushed'),
                    coords = cell.position(),
                    popupNumpad = $Sudoku.getPopupNumpad().show()
                    ;
                coords.top = coords.top - (popupNumpad.outerHeight() / 2) + cell.outerHeight();
                coords.left = coords.left - (popupNumpad.outerWidth() / 2) + (cell.outerWidth() / 2);
                popupNumpad.offset(coords);
            };

            $Sudoku.hidePopupNumpad = function() {
                $Sudoku.table.find('.sudoku-numpad.popup').remove();
            };

            $Sudoku.keyPress = function(charCode) {
                var cell = $Sudoku.getSelectedCell();
                if (cell.hasClass('open')) {
                    var number = String.fromCharCode(charCode) * 1;
                    if (number >= 1 && number <= 9) { // 1..9
                        $Sudoku.checkNumber(number);
                    }
                }
            };

            $Sudoku.forceRefresh = function(reason) {
                alert('Force game refresh. Reason: ' + reason);
                w.location.reload();
            };

        };
        protectedMethods();
        // PROTECTED METHODS

        $Sudoku.checkHistoryButtons();
        $Sudoku.checkNumbersCount();
    }

    w.Sudoku = Sudoku;

})(this, this.document, this.jQuery);
