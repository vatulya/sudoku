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
            var data = $.parseJSON(event.data);
            $(d).trigger('websocket:message', data);

            var module = '';
            if (data['_module'] != null) {
                module += ':' + data._module;

                // websocket_message.sudoku
                $(d).trigger('websocket:message' + module, data);

                if (data['_system'] != null) {
                    // websocket_message.sudoku_systemData
                    $(d).trigger('websocket:message' + module + ':systemData', data);
                }

                if (data['_action'] != null) {
                    // websocket_message.sudoku_someAction
                    $(d).trigger('websocket:message' + module + ':' + data._action, data);
                }
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
