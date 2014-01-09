(function (w, d, $) {

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
        .on('success', 'form.login', function() {
            console.log('login success');
        })
        .on('success', 'form.register', function() {
            console.log('register success');
        })
    ;

    var User = {

    };

    w.User = User;

})(this, this.document, this.jQuery);
