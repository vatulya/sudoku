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
            w.scrollTo(0, 0);
        });
    ;

})(this, this.document, this.jQuery);
