(function (w, d, $) {

    $(d)
        .on('keypress', 'input', function(e) {
            if (e.keyCode == '13') { // Enter button
                e.preventDefault();
                e.stopPropagation();
                Form.clickSubmit(this);
            }
        })
        .on('click', '.submit-form', function() {
            Form.clickSubmit(this);
        })
    ;

    w.gogogo = function() {

    };

    var Form = {

        allowedMessageTypes: ['error', 'notice', 'success'],

        clickSubmit: function(button) {
            button = $(button);
            var form = button.closest('form');
            var formData = {};
            $.each(form.serializeArray(), function(_, kv) {
                if (formData.hasOwnProperty(kv.name)) {
                    formData[kv.name] = $.makeArray(formData[kv.name]);
                    formData[kv.name].push(kv.value);
                } else {
                    formData[kv.name] = kv.value;
                }
            });
            formData.format = 'json';
            $.ajax({
                url: form.attr('action'),
                data: formData,
                success: function(response) {
                    if (typeof response.success != 'undefined') {
                        form.trigger('success', response);
                    } else {
                        Form.showMessages(form, response.messages);
                    }
                }
            });
        },

        showMessages: function(form, messages) {
            form = $(form);
            Form.clearMessages(form);
            var formMessageBlock = '';
            for (var i in messages) {
                /*
                errors = {
                    name: "login", // field name
                    title: "Login field", // field title
                    text: "Wrong login", // error text
                    type: "error" // error|notice|success
                }
                 */
                if (messages.hasOwnProperty(i)) {
                    var message = messages[i],
                        field = form.find('.field-' + message.name),
                        fieldMessageBlock = field.find('.message'),
                        messageText = message.text,
                        messageType = Form.getMessageType(message);
                    ;
                    if (field.length && fieldMessageBlock.length) {
                        field.addClass(messageType);
                        fieldMessageBlock.html(messageText).show();
                    } else {
                        messageText = message.title + ': ' + messageText;
                        formMessageBlock += '<li>' + messageText + '</li>';
                    }
                }
            }
            if (formMessageBlock != '') {
                form.find('.form-message').addClass('error').append('<ul>' + formMessageBlock + '</ul>').show();
            }
        },

        clearMessages: function(form) {
            form = $(form);
            form.find(".form-message").removeClass('error').removeClass('success').html('').hide();
            form.find('.form-field').removeClass('error').removeClass('success');
            form.find('.form-field .message').html('').hide();
        },

        getMessageType: function(message) {
            var i = Form.allowedMessageTypes.indexOf(message.type);
            if (i == -1) {
                i = 0;
            }
            return Form.allowedMessageTypes[i];
        }

    };

    w.Form = Form;

})(this, this.document, this.jQuery);
