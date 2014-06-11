(function (w, d, $, undefined) {

    $(d)
        .on('click', '.server-status', function(e) {
            console.log('SYSTEM: send stop-script command');
            w.SC.call('system', {'_action': 'stopScript'}).then(function(response) {
                console.log('SYSTEM RESPONSE: ' + response);
            });
        })
        .on('click', '.hlink', function(e) {
            e.preventDefault();
            var el = $(e.currentTarget);
            if (el.data('href')) {
                if (el.data('target')) {
                    w.open(el.data('href'), el.data('target'));
                } else {
                    w.location = el.data('href');
                }
            }
        })
        .on('click', '.show-new-game-form', function(e) {
            var options = {
                'url': '/sudoku/create',
                'data': {
                    'format': 'html'
                }
            };
            w.$Modal.load('Начинаем новую игру "Судоку"', options);
        })
        .on('click', '.pause-game-button', function(e) {
            var el = $(e.currentTarget);
            if (el.hasClass('active')) {
                w.Game.showPause();
            } else {
                w.Game.hidePause();
            }
            $(d).trigger('game.pause', el.hasClass('active'));
        })
        .on('change', '.right-column-change-difficulty', function(e) {
            var el = $(e.currentTarget),
                action = el.data('action'),
                container = el.closest('.' + el.data('container-class'))
                data = {
                    'format': 'html'
                },
                data[el.attr('name')] = el.val()
            ;
            var options = {
                'url': action,
                'data': data
            };
            $.ajax(options)
                .done(function(response) {
                    container.replaceWith(response);
                });
        })
        .on('change', 'form.create-new-game .select-difficulties', function(e) {
            var options = {
                'url': '/sudoku/get-board',
                'data': {
                    'format': 'html',
                    'difficulty': $(e.currentTarget).val(),
                    'hide': 1
                }
            };
            $.ajax(options)
                .done(function(response) {
                    $Modal.body.find('.game-board-example-container').html(response);
                });
        })
        .on('success', 'form.create-new-game', function(e, response) {
            if (response.hasOwnProperty('gameHash')) {
                window.location.href = '/game/' + response['gameHash'];
            }
        })
        .on('click', '.scroll-to-top', function(e) {
            e.preventDefault();
            $('.center-column').scrollTop(0);
        })
    ;

})(this, this.document, this.jQuery);
