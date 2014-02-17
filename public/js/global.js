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
        w.socket = new WebSocket("ws://sudoku.lan:9900/");
        w.socket.onopen = function() {
            alert("connection.");
        };

        w.socket.onclose = function(event) {
            console.log(event);
            if (event.wasClean) {
                alert('connection closed clear');
            } else {
                alert('connection die');
            }
            alert('Code: ' + event.code + ' Reason: ' + event.reason);
        };

        w.socket.onmessage = function(event) {
            console.log(event);
            alert("Data: " + event.data);
        };

        w.socket.onerror = function(error) {
            console.log(error);
            alert("Error: " + error.message);
        };


    };

    w.test = function(data) {
        w.socket.send(data);
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
