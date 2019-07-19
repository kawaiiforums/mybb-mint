<form action="{$actionUrl}" method="post">
    <div class="mint__form__header">
        <p class="mint__form__header__title">{$actionText}</p>
        <div class="mint__form__header__controls">
            <input type="submit" class="button" value="{$lang->mint_continue}" />
        </div>
    </div>
    {$content}
    <div class="mint__form__header">
        <div class="mint__form__header__controls">
            <input type="submit" class="button" value="{$lang->mint_continue}" />
        </div>
    </div>
    <input type="hidden" name="selection_type" value="{$selectionType}" />
    <input type="hidden" name="selected_ask_item_types" value="{$selectedAskItemTypesJson}" />
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
