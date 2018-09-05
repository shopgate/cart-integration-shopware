{extends file="parent:frontend/checkout/finish.tpl"}

{block name="frontend_index_content"}
    {$smarty.block.parent}

    {block name="frontend_index_content_shopgate_finish_script"}
        {if $sgWebCheckout}
            <script type="text/javascript">
                window.onbeforeunload = function() {ldelim}
                    var commands = [
                        {ldelim}
                            'c': 'broadcastEvent',
                            'p': {ldelim}
                                'event': 'closeInAppBrowser',
                                'parameters': [{ldelim}'redirectTo': '/'{rdelim}]
                            {rdelim}
                        {rdelim}
                    ];
                    window.SGAppConnector.sendAppCommands(commands);
                {rdelim};
                function initPipelineCall() {ldelim}
                    disableCloseButton();
                    exchangeContinueShoppingButton();
                    var commands = [
                        {ldelim}
                            'c': 'broadcastEvent',
                            'p': {ldelim}
                                'event': 'checkoutSuccess',
                                "parameters": [{$sgCheckoutParams}]
                                {rdelim}
                            {rdelim},
                        {ldelim}
                            'c': 'setNavigationBarParams',
                            'p': {ldelim}
                                'navigationBarParams': {ldelim}
                                    'rightButton' : true,
                                    'rightButtonType' : 'close',
                                    'rightButtonCallback' : "SGAction.broadcastEvent({ldelim}event: 'closeInAppBrowser','parameters': [{ldelim}'redirectTo': '/'{rdelim}]{rdelim});"
                                    {rdelim}
                                {rdelim}
                            {rdelim}
                    ];
                    window.SGAppConnector.sendAppCommands(commands);
                {rdelim}
                function disableCloseButton() {ldelim}
                    var setNavigationBarParams = {ldelim}
                        'c': 'setNavigationBarParams',
                        'p': {ldelim}
                            'navigationBarParams': {ldelim}
                                'rightButton': false
                            {rdelim}
                        {rdelim}
                    {rdelim}
                    window.SGAppConnector.sendAppCommand(setNavigationBarParams);
                {rdelim}
                function exchangeContinueShoppingButton() {ldelim}
                    if (document.getElementsByClassName('btn')) {ldelim}
                        var targetButton = null;
                        var shopBaseUrl = window.location.protocol + "//" + window.location.host + "/";
                        Array.from(document.getElementsByClassName('btn')).forEach(function (button) {ldelim}
                            if ((new RegExp(shopBaseUrl)).test(button.getAttribute('href'))) {ldelim}
                                targetButton = button;
                                if (targetButton.nodeName === 'A') {ldelim}
                                    // Overwrite default behavior of the "Continue Shopping"-Button
                                    targetButton.setAttribute('href', '#0');
                                    targetButton.onclick = (function () {ldelim}
                                        var commands = [
                                            {ldelim}
                                                'c': 'broadcastEvent',
                                                'p': {ldelim}
                                                    'event': 'closeInAppBrowser',
                                                    'parameters': [{ldelim}'redirectTo': '/'{rdelim}]
                                                    {rdelim}
                                                {rdelim}
                                        ];
                                        window.SGAppConnector.sendAppCommands(commands);
                                    {rdelim})
                                {rdelim}
                            {rdelim}
                            if (button.classList.contains('teaser--btn-print')) {ldelim}
                                button.remove();
                            {rdelim}
                        {rdelim});
                    {rdelim}
                {rdelim}
            </script>
        {/if}
    {/block}
{/block}