{extends file="parent:frontend/account/index.tpl"}

{block name='frontend_index_content'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}