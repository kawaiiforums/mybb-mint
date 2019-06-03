<br />

<div class="mint__flowing-list">
    <div class="mint__flowing-list__entry">
        <span class="mint__flowing-list__entry__title">{$lang->mint_item_type}</span>
        <span class="mint__flowing-list__entry__value">{$itemTypeTitle}</span>
    </div>
</div>

<form action="" method="post">
    <div class="mint__form">
        <label class="mint__form__element">
            <p class="mint__form__element__title">{$lang->mint_amount}</p>
            <input type="number" name="amount" class="textbox" value="1" min="1" max="{$maxAmount}" {$amountFieldAttributes} />
        </label>
        <input type="submit" class="button" value="{$lang->mint_submit}" />
    </div>
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
</form>
