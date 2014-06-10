(function (w, d, $) {

    $(d)
        .on('keypress', 'input', function(e) {
            if (e.keyCode == '13') { // Enter button
                e.preventDefault();
                e.stopPropagation();
                Form.clickSubmit($(this));
            }
        })
        .on('click', '.submit-form', function(e) {
            e.preventDefault();
            Form.clickSubmit($(this));
        })
        .on('click', '.form-field .tooltip', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).closest('.form-field').find('.message-tooltip').tooltip('destroy');
        })
        .on('click', '.form-message', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).hide();
        })
        .on('submit', '.ajax-form-submit', function(e) {
            e.preventDefault();
            Form.submitForm($(this));
        })
    ;

    var Form = {

        allowedMessageTypes: ['error', 'notice', 'success'],

        clickSubmit: function($button) {
            var form = $button.closest('form'),
                additionalData = {}
                ;
            if ($button.attr('name') && typeof $button.attr('value') != 'undefined') {
                additionalData[$button.attr('name')] = $button.attr('value');
            }
            Form.submitForm(form, additionalData);
        },

        submitForm: function($form, additionalData) {
            if ($form.hasClass('disabled')) {
                return false;
            }
            $form.addClass('disabled');
            $form.find('.submit-form').addClass('disabled');
            var formData = additionalData || {};
            $.each($form.serializeArray(), function(_, kv) {
                if (formData.hasOwnProperty(kv.name)) {
                    formData[kv.name] = $.makeArray(formData[kv.name]);
                    formData[kv.name].push(kv.value);
                } else {
                    formData[kv.name] = kv.value;
                }
            });
            formData.format = 'json';
            $.ajax({
                url: $form.attr('action'),
                data: formData,
                success: function(response) {
                    $form.removeClass('disabled');
                    $form.find('.submit-form').removeClass('disabled');
                    if (typeof response.success != 'undefined') {
                        $form.trigger('success', response);
                    } else {
                        Form.showMessages($form, response.messages || {});
                    }
                }
            });
            return true;
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
                        fieldBlock = form.find('.field-' + (message.name || '')),
                        field = fieldBlock.find('input'),
                        fieldMessageBlock = fieldBlock.find('.message'),
                        messageText = message.text || '',
                        messageType = Form.getMessageType(message)
                    ;
                    fieldBlock.addClass(messageType);
                    if (fieldMessageBlock.length) {
                        // Show in special block
                        fieldMessageBlock.html(messageText).show();
                    } else if (field.length && field.hasClass('message-tooltip')) {
                        // Show as tooltip
                        var options = {
                            title: messageText,
                            trigger: 'manual'
                        };
                        field.tooltip(options);
                    } else {
                        // Show in common form message block
                        var messageTitle = '' + message.title;
                        messageText = (messageTitle ? messageTitle + ': ' : '') + messageText;
                        formMessageBlock += '<li>' + messageText + '</li>';
                    }
                }
            }
            if (formMessageBlock != '') {
                form.find('.form-message').addClass('error').append('<ul>' + formMessageBlock + '</ul>').show();
            }
            form.find('.message-tooltip').tooltip('show');
        },

        clearMessages: function(form) {
            form = $(form);
            form.find(".form-message").removeClass('error').removeClass('success').html('').hide();
            form.find('.form-field').removeClass('error').removeClass('success');
            form.find('.form-field .message').html('').hide();
            form.find('.form-field .message-tooltip').tooltip('destroy');
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
