{$messages}
<br />

{$urlToCopy}

<div class="mint__flowing-list">
    <div class="mint__flowing-list__entry">
        <span class="mint__flowing-list__entry__title">{$lang->mint_item_transaction_status}</span>
        <span class="mint__flowing-list__entry__value">{$status}</span>
    </div>
    <div class="mint__flowing-list__entry">
        <span class="mint__flowing-list__entry__title">{$lang->mint_item_transaction_ask_price}</span>
        <span class="mint__flowing-list__entry__value">{$askPrice}</span>
    </div>
    <div class="mint__flowing-list__entry">
        <span class="mint__flowing-list__entry__title">{$lang->mint_item_transaction_ask_date}</span>
        <span class="mint__flowing-list__entry__value">{$askDate}</span>
    </div>
    <div class="mint__flowing-list__entry" data-value="{$completedDate}">
        <span class="mint__flowing-list__entry__title">{$lang->mint_item_transaction_completed_date}</span>
        <span class="mint__flowing-list__entry__value">{$completedDate}</span>
    </div>
    <div class="mint__flowing-list__entry">
        <span class="mint__flowing-list__entry__title">{$lang->mint_item_transaction_ask_user}</span>
        <span class="mint__flowing-list__entry__value">{$askUser}</span>
    </div>
    <div class="mint__flowing-list__entry" data-value="{$transaction['bid_user_id']}">
        <span class="mint__flowing-list__entry__title">{$lang->mint_item_transaction_bid_user}</span>
        <span class="mint__flowing-list__entry__value">{$bidUser}</span>
    </div>
</div>

{$items}

<form action="" method="post">
    <div class="mint__action-links">{$actionLinks}</div>
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
    <input type="hidden" name="action_signature" value='{$actionSignatureJson}' />
</form>