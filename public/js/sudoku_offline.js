(function (w, d, $) {

    function Sudoku(table) {

        var $Sudoku = this;

        $Sudoku.table = $(table);
        $Sudoku.pushTimer = false;

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
                $Sudoku.clearBoard(this);
            })
            .on('click', '.undo-move', function() {
                $Sudoku.undoMove(this);
            })
            .on('click', '.redo-move', function() {
                $Sudoku.redoMove(this);
            })
            .on('mouseover', '.sudoku-numpad.popup .number.enabled', function() {
                var el = $(this);
                el.addClass('hover');
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
            .on('websocket_open', function(e) {
                $Sudoku.startPing();
            })
            .on('websocket_close', function(e) {
                $Sudoku.stopPing();
            })
            .on('websocket_message.sudoku_checkFields', function(e, data) {
                $Sudoku.checkBoardResponse(data);
            })
        ;

        w.disableSelect($Sudoku.table);

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
            $Sudoku.saveMoveToHistory(cell, number, 'undo');
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
            var coords = '' + cell.data('row') + cell.data('col');
            return coords;
        };

        $Sudoku.getCellByCoords = function(coords) {
            coords = coords.split(''); // '' + row + col
            var cell = $Sudoku.table.find('.cell.row-' + coords[0] + '.col-' + coords[1]);
            return cell;
        };

        $Sudoku.setCellNumber = function(cell, number) {
            cell = $(cell);
            number = '' + number;
            if (cell.hasClass('open')) {
                cell.html(number).data('number', number);
                if (number) {
                    cell.removeClass('empty');
                } else {
                    cell.addClass('empty');
                }
                // TODO: send action
                $Sudoku.logUserAction('setCellNumber', {cell: $Sudoku.getCellCoords(cell), number: number})
                $Sudoku.checkNumbersCount(cell);
            }
        };

        /************************* CHECK BOARD ***************************/
        $Sudoku.checkBoard = function() {
            var data = {
                '_game_id': $Sudoku.table.data('game-id'),
                '_action': 'checkField'
            };
            w.websocket.send(data);
        };

        $Sudoku.checkBoardResponse = function(response) {
            if (response.resolved) {
                $Sudoku.win();
            } else if (typeof response.errors == 'undefined' || response.errors.length == 0 || !response.errors) {
                var board = $Sudoku.table.find('.sudoku-board');
                board.addClass('no-errors');
                setTimeout(function() {board.removeClass('no-errors');}, 2000);
            } else {
                $.each(response.errors, function(coords, number) {
                    coords = coords.split('');
                    var row = coords[0],
                        col = coords[1]
                        ;
                    $Sudoku.showError(row, col);
                });
            }
        };
        /************************* /CHECK BOARD ***************************/

        $Sudoku.showError = function(row, col) {
            var cell = $Sudoku.table.find('.cell.row-' + row + '.col-' + col);
            if (cell.length) {
                cell.addClass('error');
                setTimeout(function() {cell.removeClass('error')}, 2000);
            }
        };

        $Sudoku.clearBoard = function() {
            $Sudoku.table.find('.cell.open').each(function(i, el) {
                el = $(el);
                el.data('number', '');
                el.html('');
            });
            $Sudoku.clearHistory();
            // TODO: send action
        };

        $Sudoku.checkUndoRedoButtons = function() {
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
        };

        $Sudoku.saveMoveToHistory = function(cell, newNumber, historyType) {
            cell = $(cell);
            historyType = historyType == 'redo' ? 'redo' : 'undo';
            if (!cell.hasClass('open')) {
                return;
            }
            var coords = $Sudoku.getCellCoords(cell),
                historyButton = $Sudoku.table.find('.' + historyType + '-move'),
                history = historyButton.data('moves').split(';').clean(false),
                oldNumber = cell.data('number')
            ;
            if (typeof oldNumber == 'undefined') oldNumber = '';
            var historyStep = '' + coords + ':' + oldNumber + '|' + newNumber;
            history.push(historyStep);
            history = history.join(';');
            historyButton.data('moves', history);
            $Sudoku.checkUndoRedoButtons();
        };

        $Sudoku.removeLastMoveFromHistory = function(historyButton) {
            historyButton = $(historyButton);
            var history = historyButton.data('moves').split(';').clean(false);
            history.pop(); // remove last element
            history = history.join(';');
            historyButton.data('moves', history);
        };

        $Sudoku.clearHistory = function(historyType) {
            if (historyType) {
                historyType = historyType == 'redo' ? 'redo' : 'undo';
                $Sudoku.table.find('.' + historyType + '-move').data('moves', '');
            } else {
                $Sudoku.table.find('.undo-move').data('moves', '');
                $Sudoku.table.find('.redo-move').data('moves', '');
            }
            $Sudoku.checkUndoRedoButtons();
        };

        $Sudoku.getLastMoveFromHistory = function(historyButton) {
            historyButton = $(historyButton);
            var move = historyButton.data('moves').split(';').clean(false);
            move = move[move.length - 1];
            move = move.split(':');
            var coords = move[0];
            move = move[1].split('|');
            var moveData = {
                coords: coords,
                old_number: move[0],
                number: move[1]
            }
            return moveData;
        };

        $Sudoku.undoMove = function(undoButton) {
            undoButton = $(undoButton);
            if (undoButton.hasClass('disabled')) {
                return;
            }
            var move = $Sudoku.getLastMoveFromHistory(undoButton),
                cell = $Sudoku.getCellByCoords(move.coords)
            ;
            $Sudoku.saveMoveToHistory(cell, move.old_number, 'redo');
            $Sudoku.setCellNumber(cell, move.old_number);
            $Sudoku.removeLastMoveFromHistory(undoButton);
            $Sudoku.checkUndoRedoButtons();

            cell = $Sudoku.getSelectedCell();
            $Sudoku.hoverNumber(cell);
        };

        $Sudoku.redoMove = function(redoButton) {
            redoButton = $(redoButton);
            if (redoButton.hasClass('disabled')) {
                return;
            }
            var move = $Sudoku.getLastMoveFromHistory(redoButton),
                cell = $Sudoku.getCellByCoords(move.coords)
            ;
            $Sudoku.saveMoveToHistory(cell, move.old_number, 'undo');
            $Sudoku.setCellNumber(cell, move.old_number);
            $Sudoku.removeLastMoveFromHistory(redoButton);
            $Sudoku.checkUndoRedoButtons();

            cell = $Sudoku.getSelectedCell();
            $Sudoku.hoverNumber(cell);
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

        /**************************** LOG USER ACTION **********************/
        $Sudoku.logUserAction = function(action, parameters) {
            var data = {
                '_game_id': $Sudoku.table.data('game-id'),
                '_action': 'userActionLog',
                'action': action,
                'parameters': parameters
            };
            w.websocket.send(data);
        };
        /**************************** /LOG USER ACTION **********************/

        $Sudoku.startPing = function() {
            $Sudoku.stopPing();
            $Sudoku.ping = setInterval(function() {
                var data = {
                    '_game_id': $Sudoku.table.data('game-id'),
                    '_action': 'ping'
                };
                w.websocket.send(data);
            }, 3000);
        };

        $Sudoku.stopPing = function() {
            clearInterval($Sudoku.ping);
        };

        $Sudoku.checkUndoRedoButtons();
        $Sudoku.checkNumbersCount();
    }

    w.Sudoku = Sudoku;

})(this, this.document, this.jQuery);
