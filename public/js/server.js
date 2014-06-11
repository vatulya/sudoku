(function (w, d, $, ab, undefined) {

    var S = '|';

    var $status = $('.server-container .server-status');

    function setServerStatus(status) {
        $status.removeClass('ready connecting disconnected');
        $status.addClass(status);
    }


    var serverConnection = new ab.Session(
        'ws://sudoku.lan:8080' // The host (our Ratchet WebSocket server) to connect to
        , function() {            // Once the connection has been established
            setServerStatus('ready');
            serverConnection.subscribe('system', function(topic, data) {
                // This is where you would add the new article to the DOM (beyond the scope of this tutorial)
                console.log('New article published to category "' + topic + '" : ' + data.title);
                alert('New article published to category "' + topic + '" : ' + data.title);
            });
            serverConnection.call('setSessionId', {"session": $.cookie('PHPSESSID')});
            $(d).trigger('websocket' + S + 'open');
        }
        , function(code) {            // When the connection is closed
            setServerStatus('disconnected');
            $(d).trigger('websocket' + S + 'close');
            console.warn('WebSocket connection closed. Code: ' + code + '.');
        }
        , {                       // Additional parameters, we're ignoring the WAMP sub-protocol for older browsers
            'skipSubprotocolCheck': true,
            'maxRetries': 100,
            'retryDelay': 2000
        }
    );
    serverConnection.S = S;

    w.SC = serverConnection;

})(this, this.document, this.jQuery, ab);
