(function (w, d, $, undefined) {

    $(d)
        .on('click', '.show-button', function() {
            var formsContainer = $(this).closest('.login-register'),
                loginContainer = formsContainer.find('.login-container'),
                registerContainer = formsContainer.find('.register-container')
            ;
            if (formsContainer.hasClass('show-login-form')) {
                registerContainer.hide();
                loginContainer.hide(500, function() {
                    formsContainer.find('.show-login').show();
                    registerContainer.show(500, function() {
                        formsContainer.find('.show-register').hide();
                        formsContainer.removeClass('show-login-form').addClass('show-register-form');
                    });
                });
            } else {
                loginContainer.hide();
                registerContainer.hide(500, function() {
                    formsContainer.find('.show-register').show();
                    loginContainer.show(500, function() {
                        formsContainer.find('.show-login').hide();
                        formsContainer.removeClass('show-register-form').addClass('show-login-form');
                    });
                });
            }
        })
        .on('click', '.user-info-block .user-info-title-container', function(e) {
            e.stopPropagation();
            var userBlock = $(this).closest('.user-info-block');
            if (userBlock.hasClass('user-menu-closed')) {
                userBlock.removeClass('user-menu-closed').addClass('user-menu-opened');
            } else {
                userBlock.removeClass('user-menu-opened').addClass('user-menu-closed');
            }
        })
        .on('click', function() {
            var userBlock = $('.user-info-block');
            userBlock.removeClass('user-menu-opened').addClass('user-menu-closed');
        })
        .on('success', 'form.login', function() {
            $('#login-modal').modal('hide');
            location.reload();
        })
        .on('success', 'form.register', function() {
            $('#login-modal').modal('hide');
            location.reload();
        })
    ;

    var User = {

    };

    w.User = User;

})(this, this.document, this.jQuery);
