{extends file="parent:frontend/index/footer_minimal.tpl"}

{block name="frontend_index_minimal_footer"}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}

    {block name="frontend_index_page_wrap_shopgate_script"}
        {if $sgActionName === 'confirm' || $sgActionName === 'shippingPayment'}
            <script type="text/javascript">
                function initPipelineCall () {ldelim}
                    window.SGAppConnector.sendPipelineRequest(
                        'onedot.checkout.updateSession.v1',
                        false,
                        {ldelim}'sessionId': '{$sgSessionId}'{rdelim},
                        function (err, serial, output) {ldelim}

                        {rdelim}
                    )
                {rdelim}
            </script>
        {/if}
    {/block}
{/block}