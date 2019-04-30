<html>
<head>
    <title>{$mybb->settings['bbname']} - {$lang->mint_mint_currency}</title>
    {$headerinclude}
    <link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css">
</head>
<body>
{$header}

<div class="mint-hub">
    <div class="mint-hub__page-title">{$lang->mint_mint_currency}</div>

    {$messages}

    {$form}
</div>

{$footer}
</body>
</html>