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

    };

    w.test = function(data) {
        w.websocket.send(data);
    };

    w.disableSelect = function(el) {
        el = $(el);
        el
            .attr('unselectable','on')
            .addClass('select-disabled')
            .bind('selectstart', function() { return false; })
        ;
    };

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
