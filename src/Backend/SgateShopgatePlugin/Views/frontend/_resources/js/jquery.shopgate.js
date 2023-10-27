(function($, window) {

    $(function($) {

        function getUrlParameter(sParam) {
            var sPageURL = window.location.search.substring(1),
                sURLVariables = sPageURL.split('&'),
                sParameterName,
                i;

            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');

                if (sParameterName[0] === sParam) {
                    return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                }
            }
        }

        function closeBrowser () {
            window.SGAppConnector.sendAppCommand(
              {
                  'c': 'broadcastEvent',
                  'p': {
                      'event': 'closeInAppBrowser',
                      'parameters': [{'redirectTo': '/cart'}]
                  }
              }
            );
        }

        function payPalPayment(event, me) {

            if ($('#sgate_paypal_express').length) {
                var token,
                    form,
                    urlToken = getUrlParameter('token');

                if (window.location.href.includes('/token/') || (urlToken && urlToken.length > 1)) {
                    closeBrowser();
                } else {
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
        }

        $.subscribe('plugin/swagPayPalUnifiedExpressCheckoutButtonCart/createButton', payPalPayment);
        $.subscribe('plugin/swAddArticle/onAddArticle', closeBrowser); // supposedly only product detail page
    });
})(jQuery, window);
