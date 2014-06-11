(function (w, d, $) {

    var allMarksCssClasses = 'mark-1 mark-2 mark-3 mark-4 mark-5 mark-6 mark-7 mark-8 mark-9';

    function SudokuBoard(board) {

        var $Board = this;

        $Board.board = board;

        w.disableSelect($Board.board);

        /*** ***/

        $Board.setCell = function ($cell, number, marks) {
            if (typeof number != 'undefined') {
                if ($Board.setCellNumber($cell, number)) {
                    $Board.removeColRowMarks($cell, number);
                }
            }
            if (typeof marks != 'undefined') {
                $Board.setCellMarks($cell, marks);
            }
        };

        /*** NUMBER ***/

        $Board.setCellNumber = function ($cell, number) {
            if ($cell.hasClass('open')) {
                number = $Board._setCellNumber($cell, number);
                number ? $cell.removeClass('empty marks') : $cell.addClass('empty marks');
                return number;
            }
            return undefined;
        };

        $Board._setCellNumber = function ($cell, number) {
            number = parseInt(number);
            if (number > 0 && number <= 9) {
                $cell.data('number', number);
                $cell.find('.number-container').html(number);
                return number;
            }
            $cell.data('number', '');
            $cell.find('.number-container').html('');
            return 0;
        };

        $Board.getCellNumber = function ($cell) {
            return parseInt($cell.data('number'));
        };

        /*** MARKS ***/

        $Board.addCellMark = function ($cell, mark) {
            var marks = $Board.getCellMarks($cell);
            marks.push(mark);
            marks = marks.getUnique();
            return $Board.setCellMarks($cell, marks);
        };

        $Board.setCellMarks = function ($cell, marks) {
            if ($cell.hasClass('open')) {
                $cell.removeClass(allMarksCssClasses);
                marks = $Board._setCellMarks($cell, marks);
                if ($cell.hasClass('empty')) {
                    marks ? $cell.addClass('marks') : $cell.removeClass('marks');
                }
                return marks;
            }
            return undefined;
        };

        $Board._setCellMarks = function ($cell, marks) {
            var marked = [];
            $.each(marks, function (i, mark) {
                mark = $Board._setCellMark($cell, mark);
                if (mark) {
                    marked.push(mark);
                }
            });
            return marked;
        };

        $Board._setCellMark = function ($cell, mark) {
            mark = parseInt(mark);
            if (mark > 0 && mark <= 9) {
                $cell.addClass('mark-' + mark);
                return mark;
            }
            return false;
        };

        $Board.removeColRowMarks = function ($cell, mark) {
            var coords = $Board.getCellCoords($cell);
            $Board.board.find('.cell.row-' + coords[0] + ', .cell.col-' + coords[1]).removeClass('mark-' + mark);
            return true;
        };

        $Board.getCellMarks = function ($cell) {
            var marks = [];
            for (var i = 1; i <= 9; i++) {
                if ($cell.hasClass('mark-' + i)) {
                    marks.push(i);
                }
            }
            return marks;
        };

        /*** ***/

        $Board.clearBoard = function () {
            $Board.board.find('.cell.open').each(function(i, cell) {
                $Board.setCell($(cell), 0, []);
            });
        };

        /*** SELECT AND HOVER ***/

        $Board.hoverColAndRow = function ($cell) {
            $Board.board.find('.cell.hover').removeClass('hover');
            if ($cell) {
                var coords = $Board.getCellCoords($cell);
                $Board.board.find('.cell.row-' + coords[0] + ', .cell.col-' + coords[1]).addClass('hover'); // Hover row and col
                $Board.board.find('.cell.row-' + coords[0] + '.col-' + coords[1]).removeClass('hover'); // But don't hover focus cell
            }
        };

        $Board.hoverNumber = function (number) {
            number = parseInt(number);
            if (number > 0 && number <= 9) {
                $Board.board.find('.cell.hovered').removeClass('hovered');
                $Board.board.find('.cell').each(function (i, cell) {
                    cell = $(cell);
                    if (cell.data('number') == number) {
                        cell.addClass('hovered');
                    }
                });
            }
        };

        $Board.selectCell = function ($cell) {
            var coords = $Board.getCellCoords($cell);
            $Board.board.find('.cell.selected').removeClass('selected');
            $Board.board.find('.cell.row-' + coords[0] + '.col-' + coords[1]).addClass('selected');
        };

        $Board.unselectCell = function (cell) {
            cell = $(cell);
            $Board.board.find('.cell.selected').removeClass('selected');
            $Board.board.data('selected-cell', '');
        };

        /*** COORDS ***/

        $Board.getCellCoords = function ($cell, asString) {
            if (!$cell.length) {
                return false;
            }
            if (asString) {
                return '' + $cell.data('row') + $cell.data('col');
            }
            return [$cell.data('row'), $cell.data('col')];
        };

        $Board.getCellByCoordsRow = function (coords) {
            return $Board.getCellByCoords(coords[0], coords[1]);
        };

        $Board.getCellByCoords = function (row, col) {
            return $Board.board.find('.cell.row-' + row + '.col-' + col);
        };

        /*** SHOW ERROR ***/

        $Board.showError = function (row, col) {
            $Board.showErrorCell($Board.getCellByCoords(row, col));
        };

        $Board.showErrorCell = function ($cell) {
            $cell.addClass('error');
            setTimeout(function () { $cell.removeClass('error'); }, 2000);
        };

        /*** ***/

        $Board.fillBoard = function (cells) {
            $.each(cells['openCells'] || {}, function(coords, number) {
                var $cell = $Board.getCellByCoordsRow(coords);
                $Board.setCellNumber($cell, number);
                $cell.removeClass('open').addClass('locked');
            });
            $.each(cells['checkedCells'] || {}, function(coords, number) {
                $Board.setCellNumber($Board.getCellByCoordsRow(coords), number);
            });
            $.each(cells['markedCells'] || {}, function(coords, marks) {
                $Board.setCellMarks($Board.getCellByCoordsRow(coords), marks);
            });
        };

        $Board.showBoard = function () {
            $Board.board.removeClass('hide-board');
        };

        $Board.hideBoard = function () {
            $Board.board.addClass('hide-board');
        };

        $Board.getBoardState = function () {
            var state = {
                'openCells': {},
                'checkedCells': {},
                'markedCells': {}
            };
            $Board.board.find('.cell.locked').each(function(i, $cell) {
                $cell = $($cell);
                var number = $Board.getCellNumber($cell);
                if (number) {
                    state['openCells'][$Board.getCellCoords($cell, true)] = number;
                }
            });
            $Board.board.find('.cell.open').each(function(i, $cell) {
                $cell = $($cell);
                var coords = $Board.getCellCoords($cell, true),
                    number = $Board.getCellNumber($cell),
                    marks = $Board.getCellMarks($cell)
                    ;
                if (number) {
                    state['checkedCells'][coords] = number;
                }
                if (marks.length) {
                    state['markedCells'][coords] = marks;
                }
            });
            return state;
        };

        $Board.getBoardHash = function () {
            var boardString = '';
            $Board.board.find('.cell').each(function(i, cell) {
                boardString += (parseInt($(cell).data('number'))) || '0';
            });
            return $.md5(boardString);
        };

    }

    w.SudokuBoard = SudokuBoard;

})(this, this.document, this.jQuery);
