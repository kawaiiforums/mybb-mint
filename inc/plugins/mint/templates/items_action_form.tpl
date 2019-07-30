<br />

<div class="mint__flowing-list">
    <div class="mint__flowing-list__entry">
        <span class="mint__flowing-list__entry__title">{$lang->mint_items_action_name}</span>
        <span class="mint__flowing-list__entry__value">{$itemActionTitle}</span>
    </div>
    <div class="mint__flowing-list__entry">
        <span class="mint__flowing-list__entry__title">{$lang->mint_amount}</span>
        <span class="mint__flowing-list__entry__value">{$itemActionItemCount}</span>
    </div>
</div>

<form action="" method="post">
    <div class="mint__form">
        <input type="submit" class="button" value="{$lang->mint_submit}" />
    </div>
    <input type="hidden" name="name" value="{$itemActionName}" />
    <input type="hidden" name="selected_items" value="{$selectedItemsJson}" />
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
</form>
