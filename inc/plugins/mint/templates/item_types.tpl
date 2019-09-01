<div class="mint__form__header">
    <p class="mint__form__header__title" id="matchingEntriesCountString">{$itemsCount}</p>
    <div class="mint__form__header__controls">
        <input type="text" id="itemTypesFilter" class="textbox" placeholder="{$lang->search}" value="{$filterValue}">
    </div>
</div>

<div id="itemTypesFiltered" class="mint__list mint__list--large">
{$entries}
</div>

<script data-input="{$inputJson}">
let filterableEntries = document.querySelectorAll('#itemTypesFiltered > div');
let input = JSON.parse(document.currentScript.getAttribute('data-input'));
let searchInput = document.querySelector('#itemTypesFilter');
let matchingEntriesCountString = document.querySelector('#matchingEntriesCountString');

let filterEntries = e => {
    let matchingEntriesCount = 0;

    let compareValue = searchInput.value.toLowerCase();

    for (let i = 0; i < filterableEntries.length; i++) {
        let referenceValue = filterableEntries[i].getAttribute('data-matchable-string').toLowerCase();

        let displayStyle;

        if (referenceValue.indexOf(compareValue) === -1) {
            displayStyle = 'none';
        } else {
            displayStyle = '';
            matchingEntriesCount++;
        }

        matchingEntriesCountString.innerHTML = input.matchingEntriesCountStringTemplate.replace('{1}', matchingEntriesCount);
        filterableEntries[i].style.display = displayStyle;
    }
};

let updateUrl = e => {
    let url = new URL(window.location);
    let searchParams = new URLSearchParams(window.location.search);

    if (searchInput.value !== '') {
        searchParams.set('q', searchInput.value);
    } else {
        searchParams.delete('q');
    }

    url.search = searchParams.toString();

    window.history.replaceState(null, null, url.toString());

};

window.addEventListener('load', filterEntries);
searchInput.addEventListener('keyup', filterEntries);
searchInput.addEventListener('keyup', updateUrl);
</script>