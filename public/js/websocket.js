(function (w, d, $) {

    function WS() {

        $this = this;

        $this.socket = new WebSocket("ws://sudoku.lan:9900/");
        $this.socket.onopen = function() {
            $(d).trigger('websocket_open');
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
            $(d).trigger('websocket_close');
        };

        $this.socket.onmessage = function(event) {
            var data = event.data;
            $(d).trigger('websocket_message', $.parseJSON(data));

            var complex = '';
            if (data._module != null) {
                complex += '.' + data._module;
                $(d).trigger('websocket_message' + complex, $.parseJSON(data)); // websocket_message.sudoku
                if (data._action != null) {
                    complex += '_' + data._action;
                }
                $(d).trigger('websocket_message' + complex, $.parseJSON(data)); // websocket_message.sudoku_someAction
            }
        };

        $this.socket.onerror = function(error) {
            console.log(error);
            alert("Error: " + error.message);
            $(d).trigger('websocket_error');
        };

        $this.send = function(data) {
            $this.socket.send(JSON.stringify(data));
        }

    };

    w.WS = WS;

})(this, this.document, this.jQuery);
