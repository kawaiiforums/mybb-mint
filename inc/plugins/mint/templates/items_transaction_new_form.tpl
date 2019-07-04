<form action="" method="post">
    <div class="mint__form">
        <label class="mint__form__element">
            <p class="mint__form__element__title">{$lang->mint_item_transaction_ask_price}</p>
            <input type="number" name="ask_price" class="textbox" value="1" min="0" />
        </label>
        <label class="mint__form__element">
            <input type="checkbox" name="unlisted" class="textbox" /> {$lang->mint_item_transaction_unlisted}
        </label>
        <input type="submit" class="button" value="{$lang->mint_submit}" />
    </div>
    <input type="hidden" name="selected_items" value="{$selectedItemsJson}" />
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
</form>
