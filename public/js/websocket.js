(function (w, d, $) {

    function WS() {

        $this = this;

        $this.socket = new WebSocket("ws://sudoku.lan:9900/");
        $this.socket.onopen = function() {
            $(d).trigger('websocket.open');
        };

        $this.socket.onclose = function(event) {
            console.log(event);
            var message = '';
            if (event.wasClean) {
                message += 'Connection closed clear.';
            } else {
                message += 'Connection die.';
            }
            message +=' Code: ' + event.code + ' Reason: ' + event.reason;
            alert(message);
            $(d).trigger('websocket.close');
        };

        $this.socket.onmessage = function(event) {
            var data = event.data;
            $(d).trigger('websocket.message', $.parseJSON(data));

            var complex = '';
            if (data._module != null) {
                complex += '.' + data._module;
                if (data._action != null) {
                    complex += '.' + data._action;
                }
                $(d).trigger('websocket.message' + complex, $.parseJSON(data));
            }
        };

        $this.socket.onerror = function(error) {
            console.log(error);
            alert("Error: " + error.message);
            $(d).trigger('websocket.error');
        };

        $this.send = function(data) {
            $this.socket.send(JSON.stringify(data));
        }

    };

    w.WS = WS;

})(this, this.document, this.jQuery);
