{extends file="parent:frontend/index/index.tpl"}

{block name='frontend_index_navigation'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}
{block name="frontend_index_footer"}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}
{block name='frontend_index_left_last_articles'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}