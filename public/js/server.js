(function (w, d, $, ab, undefined) {

    var S = '|';

    var serverConnection = new ab.Session(
        'ws://sudoku.kv2.kirby.pp.ua:8080' // The host (our Ratchet WebSocket server) to connect to
        , function() {            // Once the connection has been established
            serverConnection.subscribe('system', function(topic, data) {
                // This is where you would add the new article to the DOM (beyond the scope of this tutorial)
                console.log('New article published to category "' + topic + '" : ' + data.title);
                alert('New article published to category "' + topic + '" : ' + data.title);
            });
            serverConnection.call('setSessionId', {"session": $.cookie('PHPSESSID')});
            $(d).trigger('websocket' + S + 'open');
        }
        , function(code) {            // When the connection is closed
            $(d).trigger('websocket' + S + 'close');
            console.warn('WebSocket connection closed. Code: ' + code + '.');
        }
        , {                       // Additional parameters, we're ignoring the WAMP sub-protocol for older browsers
            'skipSubprotocolCheck': true,
            'retryDelay': 2000
        }
    );
    serverConnection.S = S;

    w.SC = serverConnection;

})(this, this.document, this.jQuery, ab, undefined);
