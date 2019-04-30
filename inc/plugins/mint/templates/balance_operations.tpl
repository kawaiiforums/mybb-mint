<html>
<head>
    <title>{$mybb->settings['bbname']} - {$lang->mint_balance_operations}</title>
    {$headerinclude}
</head>
<body>
{$header}

<div class="mint-hub">
    <div class="mint-hub__page-title">{$lang->mint_balance_operations} ({$itemsNum})</div>
    <div class="mint-hub__table">
        {$entries}
    </div>

    {$pagination}
</div>

{$footer}
</body>
</html>