(function (w, d, $, undefined) {

    function WS() {


        var $this = this;

        $this.S = '|'; // trigger name parts' separator

        $this.requestsQueue = {};
        $this.sentRequests = {};

        $this.socket = new WebSocket("ws://sudoku.lan:9900/");
        $this.socket.onopen = function() {
            $(d).trigger('websocket' + $this.S + 'open');
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
            $(d).trigger('websocket' + $this.S + 'close');
        };

        $this.socket.onmessage = function(event) {
            var data = $.parseJSON(event.data);

            var triggerName = 'websocket' + $this.S + 'message';
            // websocket|message
            $(d).trigger(triggerName, data);

            var module = '';
            if (data['_module'] != null) {
                module = '' + data['_module'];
                $this._processedRequestQueue(data, module);

                // websocket|message|sudoku
                $(d).trigger(triggerName + $this.S + module, data);

                if (data['_system'] != null) {
                    // websocket|message|sudoku|systemData
                    $(d).trigger(triggerName + $this.S + module + $this.S + 'systemData', data);
                }

                if (data['_action'] != null) {
                    // websocket|message|sudoku|someAction
                    $(d).trigger(triggerName + $this.S + module + $this.S + data._action, data);
                }
            }
        };

        $this.socket.onerror = function(error) {
            console.log(error);
            alert("Error: " + error.message);
            $(d).trigger('websocket_error');
        };

        /************ SEND REQUEST *******************/

        $this.send = function(config, module) {
            module = '' + module;
            if (module == '') {
                $this._send(config);
            } else {
                if (!$this.requestsQueue.hasOwnProperty(module) || $this.requestsQueue[module] == undefined) {
                    $this.requestsQueue[module] = [];
                }
                $this.requestsQueue[module].push(config);
                $this._processRequestQueue(module);
            }
        };

        $this._processRequestQueue = function(module) {
            module = '' + module;
            if (
                module != ''
                && $this.requestsQueue[module].length
                && $this.sentRequests[module] == undefined
            ) {
                $this.sentRequests[module] = $this.requestsQueue[module].shift();
                $this._send($this.sentRequests[module]);
            }
        };

        $this._send = function(config) {
            $this.socket.send(JSON.stringify(config.data || {}));
        };

        /*********** PROCESSED REQUEST **************/

        $this._processedRequestQueue = function(data, module) {
            if ($this.sentRequests[module] != undefined) {
                var config = $this.sentRequests[module];
                delete $this.sentRequests[module];
                if (typeof config['callback'] === 'function') {
                    config['callback'](data);
                }
                $this._processRequestQueue(module);
            }
        };

    };

    w.WS = WS;

})(this, this.document, this.jQuery, undefined);
