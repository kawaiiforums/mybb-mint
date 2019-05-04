<html>
<head>
    <title>{$mybb->settings['bbname']} - {$pageTitle}</title>
    {$headerinclude}
</head>
<body>
{$header}

<div class="mint-hub">
    <div class="mint-hub__page-title">{$pageTitle}</div>
    <div class="mint-hub__table">
        {$entries}
    </div>

    {$pagination}
</div>

{$footer}
</body>
</html>