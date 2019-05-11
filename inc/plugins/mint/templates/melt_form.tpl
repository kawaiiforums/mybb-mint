<br />

<div class="mint-hub__flowing-list">
    <div class="mint-hub__flowing-list__entry">
        <span class="mint-hub__flowing-list__entry__title">{$lang->mint_item_owner}</span>
        <span class="mint-hub__flowing-list__entry__value">{$profileLink}</span>
    </div>
    <br />
    <div class="mint-hub__flowing-list__entry">
        <span class="mint-hub__flowing-list__entry__title">{$lang->mint_item_type}</span>
        <span class="mint-hub__flowing-list__entry__value">{$itemTypeTitle}</span>
    </div>
</div>

<form action="" method="post">
    <div class="mint-hub__form">
        <label class="mint-hub__form__element">
            <p class="mint-hub__form__element__title">{$lang->mint_amount}</p>
            <input type="number" name="amount" class="textbox" value="1" min="1" max="{$maxAmount}" {$amountFieldAttributes} />
        </label>
        <input type="submit" class="button" value="Submit" />
    </div>
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
</form>
