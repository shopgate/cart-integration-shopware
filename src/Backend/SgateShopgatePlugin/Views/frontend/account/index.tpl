{extends file="parent:frontend/account/index.tpl"}

{block name='frontend_index_content'}
    {$smarty.block.parent}

    {block name="frontend_account_index_page_wrap_shopgate_script"}
        {if $sgWebCheckout && !$sgForgotPassword}
            <script type="text/javascript">
                function initPipelineCall () {ldelim}
                    window.location.href = '/account#show-registration';
                    showLoadingScreen()
                    window.SGAppConnector.sendPipelineRequest(
                        'onedot.checkout.updateSession.v1',
                        false,
                        {ldelim}'sessionId': '{$sgSessionId}'{rdelim},
                        function (err, serial, output) {ldelim}
                            window.SGAppConnector.sendPipelineRequest(
                                'shopgate.user.loginUser.v1',
                                true,
                                {ldelim}'strategy': 'auth_code', 'parameters': {ldelim}'email': '{$sgEmail}', 'hash': '{$sgHash}'{rdelim}{rdelim},
                                function (err, serial, output) {ldelim}
                                    var commands = [
                                        {ldelim}
                                            'c': 'broadcastEvent',
                                            'p': {ldelim}
                                                'event': 'userLoggedIn'
                                                {rdelim}
                                            {rdelim},
                                        {ldelim}
                                            'c': 'broadcastEvent',
                                            'p': {ldelim}
                                                'event': 'closeNotification'
                                                {rdelim}
                                            {rdelim},
                                        {ldelim}
                                            'c': 'broadcastEvent',
                                            'p': {ldelim}
                                                'event': 'closeInAppBrowser',
                                                'parameters': [{ldelim}'redirectTo': '/'{rdelim}]
                                                {rdelim}
                                            {rdelim}
                                    ]
                                    window.SGAppConnector.sendAppCommands(commands)
                                    {rdelim}
                            )
                        {rdelim}
                    )
                {rdelim}

                /**
                 * Showing up the loading screen to tell the customer "something" is happening in the background
                 */
                function showLoadingScreen () {ldelim}
                    var command = {ldelim}
                        'c': 'presentNotification',
                        'p': {ldelim}
                            presentationType: 'centeredFade',
                            src: 'sgapi:loading_notification',
                            timeout: 10,
                            notificationParams: {ldelim}
                                fullSize: true
                            {rdelim}
                        {rdelim}
                    {rdelim}
                    window.SGAppConnector.sendAppCommand(command)
                {rdelim}
            </script>
        {/if}
    {/block}
{/block}