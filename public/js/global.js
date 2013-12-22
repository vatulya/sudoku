(function ($w, $d, $) {

    $w.gogogo = function() {
        $('.sudoku-table').each(function(i, el) {
            $w.Sudoku.checkUndoRedoButtons(el);
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
