<div class="mint__string-to-copy">
    <p>{$note}</p>
    <input type="text" value="{$string}" readonly="readonly" data-autocopy />
</div>

<script>
document.querySelectorAll('input[data-autocopy]').forEach(element => {
    element.addEventListener('click', function (e) {
        e.target.select();

        var command = document.execCommand('copy');

        if (command) {
            $.jGrowl("{$lang->mint_copied_to_clipboard}", {
                theme: 'jgrowl_success'
            });
        }
    });
});
</script>
