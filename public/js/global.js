(function (w, d, $) {

    $(d)
        .on('change', '.select-difficulties', function() {
            var difficulty = $(this).val();
            w.location = '/?difficulty=' + difficulty;
        })
        .on('click', '.new-game', function() {
            w.location.reload();
        })
    ;

    w.gogogo = function() {
        $('.sudoku-table').each(function(i, el) {
            w.Sudoku.checkUndoRedoButtons(el);
            w.Sudoku.checkNumbersCount(el);
        });
    }

    Array.prototype.clean = function(deleteValue) {
        for (var i = 0; i < this.length; i++) {
            if (this[i] == deleteValue) {
                this.splice(i, 1);
                i--;
            }
        }
        return this;
    };

})(this, this.document, this.jQuery);
