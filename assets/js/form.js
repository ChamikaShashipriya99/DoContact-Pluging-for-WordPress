(function($){
    $(function(){

        function isValidEmail(email){
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        function isValidPhone(phone){
            // Remove any non-digit characters and check if exactly 10 digits
            var digits = phone.replace(/[^\d]/g,'');
            return digits.length === 10 && /^\d+$/.test(phone);
        }
        function isValidName(name){
            // Only letters and spaces allowed
            return /^[A-Za-z\s]+$/.test(name);
        }

        var $form = $('#docontact-form');
        if (!$form.length) return;

        /**
         * Set field error message
         *
         * @param {jQuery} $field - The input field jQuery object
         * @param {string} message - Error message to display (empty string to clear)
         */
        function setFieldError($field, message) {
            var fieldId = $field.attr('id');
            var $error = $('#' + fieldId + '_error');
            
            if (message) {
                $error.text(message).show();
                $field.addClass('doc-field-invalid');
                $field.attr('aria-invalid', 'true');
            } else {
                $error.text('').hide();
                $field.removeClass('doc-field-invalid');
                $field.removeAttr('aria-invalid');
            }
        }

        /**
         * Validate full name field
         */
        function validateName() {
            var $field = $('#doc_full_name');
            var value = $.trim($field.val() || '');
            
            if (!value) {
                setFieldError($field, 'Full name is required.');
                return false;
            } else if (!isValidName(value)) {
                setFieldError($field, 'Full name can only contain letters and spaces.');
                return false;
            } else {
                setFieldError($field, '');
                return true;
            }
        }

        /**
         * Validate email field
         */
        function validateEmail() {
            var $field = $('#doc_email');
            var value = $.trim($field.val() || '');
            
            if (!value) {
                setFieldError($field, 'Email is required.');
                return false;
            } else if (!isValidEmail(value)) {
                setFieldError($field, 'Email format is invalid.');
                return false;
            } else {
                setFieldError($field, '');
                return true;
            }
        }

        /**
         * Validate phone field
         */
        function validatePhone() {
            var $field = $('#doc_phone');
            var value = $.trim($field.val() || '');
            
            if (!value) {
                setFieldError($field, 'Phone number is required.');
                return false;
            } else if (!isValidPhone(value)) {
                setFieldError($field, 'Phone number must be exactly 10 digits.');
                return false;
            } else {
                setFieldError($field, '');
                return true;
            }
        }

        // Real-time validation for full name
        $('#doc_full_name').on('input blur', function(){
            var value = $(this).val().replace(/[^A-Za-z\s]/g,'');
            $(this).val(value);
            validateName();
        });

        // Real-time validation for email
        $('#doc_email').on('input blur', function(){
            validateEmail();
        });

        // Real-time validation for phone
        $('#doc_phone').on('input blur', function(){
            var value = $(this).val().replace(/[^\d]/g,'');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            $(this).val(value);
            validatePhone();
        });

        $form.on('submit', function(e){
            e.preventDefault();
            var $messages = $('#docontact-messages');
            $messages.html('').show();

            var full_name = $.trim($('#doc_full_name').val() || '');
            var email = $.trim($('#doc_email').val() || '');
            var phone = $.trim($('#doc_phone').val() || '');
            var service = $('#doc_service').val() || '';
            var message = $.trim($('#doc_message').val() || '');

            // Validate all fields before submission
            var isValid = true;
            isValid = validateName() && isValid;
            isValid = validateEmail() && isValid;
            isValid = validatePhone() && isValid;

            if (!isValid) {
                // Focus on first invalid field
                var $firstInvalid = $form.find('.doc-field-invalid').first();
                if ($firstInvalid.length) {
                    $firstInvalid.focus();
                }
                $messages.html('<div class="doc-error">Please correct the errors in the form fields above.</div>');
                return;
            }

            $('#doc-loading').show();
            $('#doc_submit').prop('disabled', true);

            var postData = {
                action: 'docontact_submit',
                docontact_nonce: $('input[name="docontact_nonce"]').val(),
                full_name: full_name,
                email: email,
                phone: phone,
                service: service,
                message: message
            };

            $.post(DoContactVars.ajax_url, postData, function(res){
                $('#doc-loading').hide();
                $('#doc_submit').prop('disabled', false);
                if (res && res.success) {
                    // Hide the form and show thank you message
                    $form.slideUp(400, function(){
                        var thankYouMessage = '<div class="doc-thank-you-message">' +
                            '<div class="doc-thank-you-icon">✓</div>' +
                            '<h3 class="doc-thank-you-title">Thank You!</h3>' +
                            '<p class="doc-thank-you-text">' + res.data.message + '</p>' +
                            '<p class="doc-thank-you-subtext">We will get back to you soon.</p>' +
                            '<p class="doc-thank-you-redirect">Redirecting to home page in 3 seconds...</p>' +
                            '</div>';
                        $messages.html(thankYouMessage);
                        $messages.show();
                        
                        // Redirect to home page after 3 seconds
                        setTimeout(function(){
                            window.location.href = DoContactVars.home_url;
                        }, 3000);
                    });
                    $form[0].reset();
                } else {
                    var msg = 'Submission failed.';
                    if (res && res.data && res.data.message) msg = res.data.message;
                    $messages.html('<div class="doc-error">' + msg + '</div>');
                }
            }).fail(function(xhr){
                $('#doc-loading').hide();
                $('#doc_submit').prop('disabled', false);
                var msg = 'An error occurred; please try again later.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                $('#docontact-messages').html('<div class="doc-error">' + msg + '</div>');
            });
        });
    });
})(jQuery);
