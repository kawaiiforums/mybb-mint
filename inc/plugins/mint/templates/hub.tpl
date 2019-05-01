<html>
<head>
    <title>{$mybb->settings['bbname']} - {$lang->mint_hub}</title>
    {$headerinclude}
</head>
<body>
{$header}

<div class="mint-hub">
    <div class="mint-hub__sections">
        <section class="mint-hub__section mint-hub__section--balance">
            <header class="mint-hub__section__header">
                <nav class="mint-hub__section__header__nav">
                    <a href="misc.php?action=economy_balance_transfer" class="mint-hub__section__header__nav__link">{$lang->mint_balance_transfer_new}</a>
                </nav>
                <div class="mint-hub__section__header__status">
                    <p class="mint-hub__section__header__status__balance">
                        {$currentBalance}
                    </p>
                </div>
            </header>
            <nav class="mint-hub__service-nav">
                <div class="mint-hub__service-nav__container">{$balanceServiceLinks}</div>
            </nav>
            <div class="mint-hub__block">
                <p class="mint-hub__block__title"><a href="misc.php?action=economy_balance_operations">{$lang->mint_balance_operations_recent}</a></p>
                <div class="mint-hub__table">
                    {$recentBalanceOperations}
                </div>
            </div>
            {$rewardSourcesLegend}
        </section>
    </div>
</div>

{$footer}
</body>
</html>