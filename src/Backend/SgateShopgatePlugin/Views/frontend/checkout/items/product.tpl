{extends file="parent:frontend/checkout/items/product.tpl"}

{block name="frontend_checkout_cart_item_delete_article"}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}