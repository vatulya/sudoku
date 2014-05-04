(function (w, d, $) {

    $(d)
        .on('click', '.show-new-game-form', function(e) {
            var options = {
                'url': '/sudoku/create',
                'data': {
                    'format': 'html'
                }
            };
            w.$Modal.load('Начинаем новую игру "Судоку"', options);
        })
        .on('success', '.sudoku-create-new-game', function(e, response) {
            if (response.hasOwnProperty('gameHash')) {
                window.location.href = '/game/' + response['gameHash'];
            }
        })
        .on('click', '.scroll-to-top', function(e) {
            e.preventDefault();
            w.scrollTo(0, 0);
        });
    ;

})(this, this.document, this.jQuery);
