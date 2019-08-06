<form action="" method="post">
    <div class="mint__form">
        <div class="mint__sections mint__sections--vcenter">
            <div class="mint__section">
                <label class="mint__form__element">
                    <input type="checkbox" name="unlisted" class="textbox" /> {$lang->mint_item_transaction_unlisted}
                </label>
            </div>
            <div class="mint__section">
                <label class="mint__form__element">
                    <p class="mint__form__element__title">{$lang->mint_item_transaction_ask_price}</p>
                    <input type="number" name="ask_price" class="textbox" value="{$askPrice}" min="0" />
                </label>
            </div>
        </div>

        <br />

        <div class="mint__sections mint__sections--inventories">
            {$inventories}
        </div>

        <div class="mint__form__header">
            <div class="mint__form__header__controls">
                <input type="submit" class="button" value="{$lang->mint_continue}" />
            </div>
        </div>
    </div>
    <input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
</form>

<script>
function getSelectedItemsCount()
{
    let count = document.querySelectorAll('input[type="checkbox"][name^="user_item_selection["]:checked').length;

    document.querySelectorAll('input[type="number"][name^="user_item_selection["]').forEach(e => {
        count += parseInt(e.value);
    });

    return count;
}

function validateSelectionForm()
{
    let buttons = document.querySelectorAll('.mint__form__header__controls input[type="submit"]');

    for (let button of buttons) {
        if (getSelectedItemsCount() === 0) {
            button.setAttribute('disabled', 'disabled');
            button.style.cursor = 'not-allowed';
        } else {
            button.removeAttribute('disabled');
            button.style.cursor = '';
        }
    }
}

document.addEventListener('change', e => {
    if (e.target.matches('input[name^="user_item_selection["]')) {
        validateSelectionForm();
    }
});

validateSelectionForm();
</script>
