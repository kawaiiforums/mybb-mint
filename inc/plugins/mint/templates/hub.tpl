<html>
<head>
    <title>{$mybb->settings['bbname']} - {$lang->mint_hub}</title>
    {$headerinclude}
</head>
<body>
{$header}

<div class="mint-hub">
    <div class="mint-hub__sections">
        <section class="mint-hub__section mint-hub__section--items">
            <header class="mint-hub__section__header">
                <nav class="mint-hub__section__header__nav">
                    <a href="misc.php?action=economy_item_transaction" class="mint-hub__section__header__nav__link">{$lang->mint_items_transaction_new}</a>
                </nav>
                <div class="mint-hub__section__header__status">
                    <p class="mint-hub__section__header__status__counter">
                        {$currentItemsCounter}
                    </p>
                    <div class="mint-hub__progress-bar">
                        <div class="mint-hub__progress-bar__bar" style="width:{$inventorySlotsFilledPercent}%"></div>
                    </div>
                    <p class="mint-hub__section__header__status__note">
                        {$itemsInventoryTitle}
                    </p>
                </div>
            </header>
            <nav class="mint-hub__service-nav">
                <div class="mint-hub__service-nav__container">{$itemsServiceLinks}</div>
            </nav>
            <div class="mint-hub__block">
                <p class="mint-hub__block__title"><a href="misc.php?action=economy_user_inventory">{$lang->mint_items_inventory_preview}</a></p>
                <div class="mint-hub__table">
                    {$inventoryPreview}
                </div>
            </div>
        </section>

        <section class="mint-hub__section mint-hub__section--currency">
            <header class="mint-hub__section__header">
                <nav class="mint-hub__section__header__nav">
                    <a href="misc.php?action=economy_balance_transfer" class="mint-hub__section__header__nav__link">{$lang->mint_balance_transfer_new}</a>
                </nav>
                <div class="mint-hub__section__header__status">
                    <p class="mint-hub__section__header__status__counter">
                        {$currentBalanceCounter}
                    </p>
                </div>
            </header>
            <nav class="mint-hub__service-nav">
                <div class="mint-hub__service-nav__container">{$currencyServiceLinks}</div>
            </nav>
            <div class="mint-hub__block">
                <p class="mint-hub__block__title"><a href="misc.php?action=economy_balance_operations">{$lang->mint_balance_operations_recent}</a></p>
                <div class="mint-hub__table">
                    {$recentBalanceOperations}
                </div>
            </div>
            {$balanceTopUsers}

            <div class="mint-hub__block">
                <p class="mint-hub__block__title">{$lang->mint_recent_public_balance_transfers}</p>
                <div class="mint-hub__table mint-hub__recent-public-transfers">
                    {$recentPublicTransfers}
                </div>
            </div>
            {$rewardSourcesLegend}
        </section>
    </div>
</div>

{$footer}
</body>
</html>