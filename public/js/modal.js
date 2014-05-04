(function (w, d, $) {

    var Modal = function(selector) {

        var $Modal = this;

        $Modal.container = $(selector);
        $Modal.header = $Modal.container.find('.modal-header');
        $Modal.body = $Modal.container.find('.modal-body');
        $Modal.headerH3 = $Modal.container.find('h3');
        
        $Modal.loader = '<div class="modal-loader-container">Загружаем...</div>';

        $Modal.container.modal({'show': false});

        $Modal.getContainer = function() {
            return $Modal.container;
        };

        $Modal.show = function() {
            if (!$Modal.headerH3.html()) {
                $Modal.header.hide();
            } else {
                $Modal.header.show();
            }
            $Modal.container.modal('show');
        };

        $Modal.hide = function() {
            $Modal.container.modal('hide');
        };

        $Modal.load = function(title, options) {
            title = title || '';
            $Modal.headerH3.html(title);
            $Modal.showLoader();
            $Modal.show();

            $.ajax(options)
                .done(function(response) {
                    $Modal.body.html(response);
                });
        };

        $Modal.showLoader = function() {
            $Modal.body.html($Modal.loader);
        };

    };

    w.Modal = Modal;

})(this, this.document, this.jQuery);
