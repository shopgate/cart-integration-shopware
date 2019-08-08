{extends file="parent:frontend/checkout/header.tpl"}

{block name='frontend_index_logo_container'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}