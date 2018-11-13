{extends file="parent:frontend/account/index.tpl"}

{block name='frontend_index_content'}
    {if !$sgWebCheckout || $sgAccountView }
        {$smarty.block.parent}
    {/if}
{/block}
{block name='frontend_index_content_left'}
    {if !$sgWebCheckout }
        {$smarty.block.parent}
    {/if}
{/block}