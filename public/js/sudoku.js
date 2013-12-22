(function ($w, $d, $) {

    $($d)
        .on('mouseover', '.sudoku-board .cell', function() {
            var el = $(this);
            var board = el.parents('.sudoku-board');
            board.find('.cell').removeClass('hover');
            var row = el.data('row');
            var col = el.data('col');
            board.find('.cell.row-' + row + ', .cell.col-' + col).each(function(i, cell){
                cell = $(cell);
                if (cell.data('row') != row || cell.data('col') != col) {
                    cell.addClass('hover');
                }
            })
        })
        .on('mouseout', '.sudoku-board', function() {
            $(this).find('.cell').removeClass('hover');
        })
        .on('click', '.sudoku-board .cell', function() {
            Sudoku.hoverNumber(this);
        })
        .on('click', '.sudoku-board .cell.open', function() {
            Sudoku.cellClick(this);
        })
        .on('click', '.sudoku-numpad .number', function() {
            var el = $(this);
            Sudoku.checkNumber(el, el.data('number'));
        })
        .on('click', '.sudoku-numpad .close', function() {
            Sudoku.closeNumpad(this);
        })
        .on('click', '.sudoku-table .check-field', function() {
            Sudoku.checkBoard(this);
        })
        .on('click', '.sudoku-table .clear-field', function() {
            Sudoku.clearBoard(this);
        })
        .on('click', '.sudoku-table .undo-move', function() {
            Sudoku.undoMove(this);
        })
        .on('click', '.sudoku-table .redo-move', function() {
            Sudoku.redoMove(this);
        })
        .on('keypress', function(e) {
            $('.sudoku-table').each(function(i, el) {
                Sudoku.keyPress(el, e.charCode);
            })
        });
    ;

    $('.sudoku-table').each(function(i, el) {
        $(el).on('game-win', function() {
            Sudoku.win(this);
        })
    });

    var Sudoku = {

        findTable: function(el) {
            el = $(el);
            if (el.hasClass('sudoku-table')) {
                return el;
            }
            var table = el.parents('.sudoku-table');
            return table;
        },

        findBoard: function(el) {
            el = $(el);
            var board;
            if (el.hasClass('sudoku-board')) {
                return el;
            }
            if (el.hasClass('sudoku-table')) {
                board = el.find('.sudoku-board');
                return board;
            }
            board = el.parents('.sudoku-board');
            if (!board.length) {
                var table = Sudoku.findTable(el);
                if (table.length) {
                    board = table.find('.sudoku-board');
                }
            }
            return board;
        },

        hoverNumber: function(el) {
            el = $(el);
            var number = el.data('number');
            if (number) {
                var board = Sudoku.findBoard(el);
                board.find('.hovered-number').val(number);
                board.find('.cell').each(function(i, el) {
                    el = $(el);
                    if (el.data('number') == number) {
                        el.addClass('hovered');
                    } else {
                        el.removeClass('hovered');
                    }
                });
            }
        },

        cellClick: function(el) {
            el = $(el);
            if (el.hasClass('selected')) {
                Sudoku.unselectCell(el);
            } else {
                Sudoku.selectCell(el);
            }
        },

        selectCell: function(el) {
            el = $(el);
            var
                table = Sudoku.findTable(el),
                row = el.data('row'),
                col = el.data('col')
            ;
            table.find('.selected-cell').val('' + row + col);
            table.find('.cell').removeClass('selected');
            table.find('.cell.row-' + row + '.col-' + col).addClass('selected');
            Sudoku.openNumpad(el);
        },

        unselectCell: function(el) {
            el = $(el);
            var table = Sudoku.findTable(el);
            table.find('.cell').removeClass('selected');
            table.find('.selected-cell').val('');
            Sudoku.closeNumpad(el);
        },

        getSelectedCell: function(el) {
            var table = Sudoku.findTable(el);
            var coords = table.find('.selected-cell').val();
            var cell = Sudoku.getCellByCoords(table, coords);
            return cell;
        },

        getCellCoords: function(cell) {
            var coords = '' + cell.data('row') + cell.data('col');
            return coords;
        },

        getCellByCoords: function(el, coords) {
            var table = Sudoku.findTable(el);
            coords = coords.split('');
            var cell = table.find('.cell.row-' + coords[0] + '.col-' + coords[1]);
            return cell;
        },

        checkNumber: function(el, number) {
            el = $(el);
            var table = Sudoku.findTable(el);
            var cell = Sudoku.getSelectedCell(table);
            Sudoku.saveMoveToHistory(cell, number, 'undo');
            Sudoku.setCellNumber(cell, number);
            Sudoku.clearHistory(table, 'redo');
            Sudoku.hoverNumber(cell);
            Sudoku.checkWinGame(table);
        },

        setCellNumber: function(cell, number) {
            if (cell.hasClass('open')) {
                cell.html(number).data('number', number);
                if (number) {
                    cell.removeClass('empty');
                } else {
                    cell.addClass('empty');
                }
            }
        },

        closeNumpad: function(el) {
            Sudoku.findTable(el).find('.sudoku-numpad').hide();
        },
        openNumpad: function(el) {
            Sudoku.findTable(el).find('.sudoku-numpad').show();
        },

        checkBoard: function(el) {
            var board = Sudoku.findBoard(el);
            var cells = {};
            board.find('.cell').each(function(i, cell) {
                cell = $(cell);
                var number = cell.data('number');
                if (number) {
                    var coords = Sudoku.getCellCoords(cell);
                    cells[coords] = number;
                }
            });
            $.ajax({
                url: '/index/check-field',
                data: {
                    format: 'json',
                    cells: cells
                },
                success: function(response) {
                    var table = Sudoku.findTable(board);
                    if (response.resolved) {
                        table.trigger('game-win');
                    } else if (typeof response.errors == 'undefined') {
                        board.addClass('no-errors');
                        setTimeout(function() {board.removeClass('no-errors');}, 2000);
                    } else {
                        $.each(response.errors, function(coords, number) {
                            coords = coords.split('');
                            var
                                row = coords[0],
                                col = coords[1]
                            ;
                            Sudoku.showError(board, row, col);
                        });
                    }
                }
            });
        },

        showError: function(el, row, col) {
            var board = Sudoku.findBoard(el);
            var cell = board.find('.cell.row-' + row + '.col-' + col);
            if (cell.length) {
                cell.addClass('error');
                setTimeout(function() {cell.removeClass('error')}, 2000);
            }
        },

        clearBoard: function(el) {
            var board = Sudoku.findBoard(el);
            board.find('.cell.open').each(function(i, el) {
                el = $(el);
                el.data('number', '');
                el.html('');
            });
            Sudoku.clearHistory(board);
        },

        checkUndoRedoButtons: function(el) {
            var table = Sudoku.findTable(el);
            var undoButton = table.find('.undo-move');
            var redoButton = table.find('.redo-move');
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
        },

        saveMoveToHistory: function(cell, number, historyType) {
            if (!cell.hasClass('open')) {
                return;
            }
            var table = Sudoku.findTable(cell);
            var coords = Sudoku.getCellCoords(cell);
            historyType = historyType == 'redo' ? 'redo' : 'undo';
            var historyButton = table.find('.' + historyType + '-move');
            var history = historyButton.data('moves').split(';').clean(false);
            var oldNumber = cell.data('number');
            if (typeof oldNumber == 'undefined') oldNumber = '';
            var historyStep = '' + coords + ':' + oldNumber + '|' + number;
            history.push(historyStep);
            history = history.join(';');
            historyButton.data('moves', history);
            Sudoku.checkUndoRedoButtons(table);
        },

        removeLastMoveFromHistory: function(historyButton) {
            var history = historyButton.data('moves').split(';').clean(false);
            history.pop(); // remove last element
            history = history.join(';');
            historyButton.data('moves', history);
        },

        clearHistory: function(el, historyType) {
            var table = Sudoku.findTable(el);
            if (historyType) {
                historyType = historyType == 'redo' ? 'redo' : 'undo';
                table.find('.' + historyType + '-move').data('moves', '');
            } else {
                table.find('.undo-move').data('moves', '');
                table.find('.redo-move').data('moves', '');
            }
            Sudoku.checkUndoRedoButtons(table);
        },

        getLastMoveFromHistory: function(historyButton) {
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
        },

        undoMove: function(undoButton) {
            undoButton = $(undoButton);
            if (undoButton.hasClass('disabled')) {
                return;
            }
            var table = Sudoku.findTable(undoButton);
            var move = Sudoku.getLastMoveFromHistory(undoButton);
            var cell = Sudoku.getCellByCoords(table, move.coords);
            Sudoku.saveMoveToHistory(cell, move.old_number, 'redo');
            Sudoku.setCellNumber(cell, move.old_number);
            Sudoku.removeLastMoveFromHistory(undoButton);
            Sudoku.checkUndoRedoButtons(table);
        },

        redoMove: function(redoButton) {
            redoButton = $(redoButton);
            if (redoButton.hasClass('disabled')) {
                return;
            }
            var table = Sudoku.findTable(redoButton);
            var move = Sudoku.getLastMoveFromHistory(redoButton);
            var cell = Sudoku.getCellByCoords(table, move.coords);
            Sudoku.saveMoveToHistory(cell, move.old_number, 'undo');
            Sudoku.setCellNumber(cell, move.old_number);
            Sudoku.removeLastMoveFromHistory(redoButton);
            Sudoku.checkUndoRedoButtons(table);
        },

        checkWinGame: function(el) {
            var table = Sudoku.findTable(el);
            if (!table.find('.cell.empty').length) {
                Sudoku.checkBoard(table);
            }
        },

        win: function(el) {
            var table = Sudoku.findTable(el);
            table.addClass('resolved');
        },

        keyPress: function(table, charCode) {
            var cell = Sudoku.getSelectedCell(table);
            if (cell.hasClass('open')) {
                var number = String.fromCharCode(charCode);
                if (number >= 1 && number <= 9) { // 1..9
                    Sudoku.checkNumber(cell, number);
                }
            }
        }

    };

    $w.Sudoku = Sudoku;

})(this, this.document, this.jQuery);
