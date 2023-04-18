jQuery(document).ready(function () {
    var emailField = jQuery('input#billing_email');
    emailField.on('change', function () {
        var emailValue = emailField.val();
        console.log(emailValue);
        jQuery.ajax({
            type: 'POST',
            url: senderAjax.ajaxUrl,
            data: {
                action: 'trigger_backend_hook',
                email: emailValue
            },

            success: function (response) {
                console.log(response);
            },

            error: function (textStatus, errorThrown) {
                console.log("AJAX Error: " + textStatus + ", " + errorThrown);
            }
        });
    });
});
