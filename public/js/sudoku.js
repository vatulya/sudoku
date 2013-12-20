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
        .on('click', '.sudoku-board .cell.open', function() {
            Sudoku.cellClick(this);
        })
        .on('click', '.sudoku-numpad .number', function() {
            Sudoku.checkNumber(this);
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
    ;

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
            if (el.hasClass('sudoku-board')) {
                return el;
            }
            var board = el.parents('.sudoku-board');
            if (!board.length) {
                var table = Sudoku.findTable(el);
                if (table.length) {
                    board = table.find('.sudoku-board');
                }
            }
            return board;
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
            table.find('.selected-cell').val(row + '-' + col);
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
            var table = el.hasClass('sudoku-table') ? el : Sudoku.findTable(el);
            var coords = table.find('.selected-cell').val().split('-');
            var cell = table.find('.cell.row-' + coords[0] + '.col-' + coords[1]);
            return cell;
        },

        checkNumber: function(el) {
            el = $(el);
            var table = Sudoku.findTable(el);
            var cell = Sudoku.getSelectedCell(table);
            var value = el.data('number');
            cell.html(value).data('value', value);
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
            board.find('.cell').each(function(i, el) {
                el = $(el);
                var value = el.data('value');
                if (value) {
                    var cellNumber = '' + el.data('row') + el.data('col');
                    cells[cellNumber] = value;
                }
            });
            $.ajax({
                url: '/index/check-field',
                data: {
                    format: 'json',
                    cells: cells
                },
                success: function(response) {
                    if (typeof response.errors == 'undefined') {
                        board.addClass('no-errors');
                        setTimeout(function() {board.removeClass('no-errors');}, 2000);
                    } else {
                        $.each(response.errors, function(coords, value) {
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
                el.data('value', '');
                el.html('');
            });
        }

    };

})(this, this.document, this.jQuery);
