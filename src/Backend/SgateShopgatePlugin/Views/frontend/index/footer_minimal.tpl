{extends file="parent:frontend/index/footer_minimal.tpl"}

{block name="frontend_index_minimal_footer"}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}