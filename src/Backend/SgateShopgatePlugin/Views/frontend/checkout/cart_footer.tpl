{extends file="parent:frontend/checkout/cart_footer.tpl"}

{block name="frontend_checkout_cart_footer_element"}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}