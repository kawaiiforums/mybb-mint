<html>
<head>
    <title>{$mybb->settings['bbname']} - {$pageTitle}</title>
    {$headerinclude}
</head>
<body>
{$header}

<div class="mint-hub">
    <div class="mint-hub__page-title">{$pageTitle}</div>
    {$content}
    <div class="mint-hub__action-links">{$actionLinks}</div>
</div>

{$footer}
</body>
</html>