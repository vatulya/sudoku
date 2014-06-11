(function (w, d, $, undefined) {

    var contentBlocker = $('.content-blocker');

    var Game = {};

    Game.showPause = function () {
        contentBlocker.find('.blocker-content').html('Пауза');
        contentBlocker.show();
    };

    Game.hidePause = function () {
        contentBlocker.hide();
    };

    w.Game = Game;

})(this, this.document, this.jQuery);
