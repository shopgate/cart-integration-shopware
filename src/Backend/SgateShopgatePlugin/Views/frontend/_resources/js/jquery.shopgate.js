(function($, window) {

    $(function($) {

        function createConfig(event, me) {

            if ($('#sgate_paypal_express').length) {
                var token,
                    form;

                if (CSRF.checkToken()) {
                    token = CSRF.getToken();
                }

                form = me.createCreatePaymentForm(token);

                $.loadingIndicator.open({
                    openOverlay: true,
                    closeOnClick: false
                });

                // delay the call, so the loading indicator will show up on mobile
                me.buffer(function() {
                    form.submit();
                });
            }
        }

        $.subscribe('plugin/swagPayPalUnifiedExpressCheckoutButtonCart/createButton', createConfig);
    });
})(jQuery, window);