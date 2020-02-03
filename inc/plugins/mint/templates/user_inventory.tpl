<form action="misc.php?action=economy_items_action" method="post">
    <div class="mint__form__header">
        <div class="mint__form__header__controls" id="inventoryActionControls">
            {$actionSelect}
        </div>
    </div>
    {$content}
</form>

<script>
window.onunload = function () {
    document.querySelector('#inventoryActionControls select').selectedIndex = 0;
};
document.querySelector('#inventoryActionControls select').addEventListener('change', function() {
    this.form.submit();
});
</script>
