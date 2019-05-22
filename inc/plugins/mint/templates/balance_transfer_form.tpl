<form action="" method="post">
    <div class="mint__form">
        <label class="mint__form__element mint__form__element--major">
            <p class="mint__form__element__title">{$lang->mint_recipient_username}</p>
            <input type="text" name="user_name" id="user_name" value="{$username}" class="textbox" />
        </label>
        <label class="mint__form__element mint__form__element--major">
            <p class="mint__form__element__title">{$lang->mint_amount}</p>
            <input type="number" name="amount" class="textbox" min="{$minAmount}" max="{$maxAmount}" value="{$amount}" /> {$currencyTitle}
        </label>
        <label class="mint__form__element">
            <p class="mint__form__element__title">{$lang->mint_balance_transfer_note}</p>
            <input type="text" name="note" class="textbox" maxlength="100" placeholder="{$lang->mint_balance_transfer_note_placeholder}" value="{$note}" style="width: 300px;" />
        </label>
        <label class="mint__form__element">
            <input type="checkbox" name="private" class="textbox"{$privateCheckboxAttributes} /> {$lang->mint_balance_transfer_private}
        </label>
        <input type="submit" class="button" value="{$lang->mint_submit}" />
    </div>
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
</form>

<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js"></script>
<script type="text/javascript">
    <!--
    if (use_xmlhttprequest == "1") {
        MyBB.select2();
        $("#user_name").select2({
            placeholder: "{$lang->search_user}",
            minimumInputLength: 2,
            maximumSelectionSize: 5,
            multiple: false,
            ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
                url: "xmlhttp.php?action=get_users",
                dataType: 'json',
                data: function (term, page) {
                    return {
                        query: term, // search term
                    };
                },
                results: function (data, page) { // parse the results into the format expected by Select2.
                    // since we are using custom formatting functions we do not need to alter remote JSON data
                    return {results: data};
                }
            },
            initSelection: function(element, callback) {
                var value = $(element).val();
                if (value !== "") {
                    callback({
                        id: value,
                        text: value
                    });
                }
            },
        });
    }
    // -->
</script>
