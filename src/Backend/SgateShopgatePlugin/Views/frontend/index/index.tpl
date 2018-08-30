{extends file="parent:frontend/index/index.tpl"}

{block name='frontend_index_navigation'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}
{block name="frontend_index_footer"}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {else}
        <script type="text/javascript">
            {literal}
            ;(function () {
                var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                if (!iOS) {
                    document.getElementsByTagName("BODY")[0].setAttribute("style", "margin-top: 48px;");
                }
            })();
            {/literal}
        </script>
    {/if}
{/block}
{block name='frontend_index_left_last_articles'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}