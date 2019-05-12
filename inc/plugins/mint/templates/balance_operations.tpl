<html>
<head>
    <title>{$mybb->settings['bbname']} - {$pageTitle}</title>
    {$headerinclude}
</head>
<body>
{$header}

<div class="mint">
    <div class="mint__page-title">{$pageTitle}</div>
    <div class="mint__table">
        {$entries}
    </div>

    {$pagination}
</div>

{$footer}
</body>
</html>