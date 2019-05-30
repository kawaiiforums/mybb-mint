{$messages}

{$itemCard}

<form action="" method="post">
    <div class="mint__action-links">{$actionLinks}</div>
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
    <input type="hidden" name="action_signature" value='{$actionSignatureJson}' />
</form>