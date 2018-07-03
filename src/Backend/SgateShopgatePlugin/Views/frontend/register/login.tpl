{extends file="parent:frontend/register/login.tpl"}

{block name='frontend_register_login_customer'}
    {$smarty.block.parent}

    {if $sgWebCheckout}
        <script type="text/javascript">
            {literal}
                ;(function () {
                    var targetLink = null;
                    Array.from(document.getElementsByTagName('a')).forEach(function (link) {
                        if (link.getAttribute("target") === '_blank') {
                            targetLink = link;
                            // Overwrite default behavior of the "Continue Shopping"-Button
                            var url = targetLink.getAttribute('href');
                            targetLink.onclick = (function (e) {
                                e.preventDefault();
                                var commands = [
                                    {
                                        c: 'openPage',
                                        p: {
                                            src: url,
                                            emulateBrowser: true,
                                            targetTab: 'in_app_browser',
                                            requestManipulation: false,
                                            navigationBarParams: {
                                                type: 'in-app-browser-default',
                                                popTab: 'in_app_browser',
                                                animation: 'none'
                                            }
                                        }
                                    },
                                    {
                                        c: 'showTab',
                                        p: {
                                            targetTab: 'in_app_browser',
                                            transition: 'slideInFromBottom'
                                        }
                                    }
                                ];
                                window.SGAppConnector.sendAppCommands(commands);
                            })
                        }
                    })
                })();
            {/literal}
        </script>
    {/if}
{/block}