<html>
<head>
    <title>{$mybb->settings['bbname']} - {$lang->mint_page_economy_hub}</title>
    {$headerinclude}
</head>
<body>
{$header}

<div class="mint">
    <div class="mint__sections">
        <section class="mint__section mint__section--items">
            <header class="mint__section__header">
                <nav class="mint__section__header__nav">
                    <a href="misc.php?action=economy_new_items_transaction" class="mint__section__header__nav__link">{$lang->mint_item_transaction_new}</a>
                </nav>
                <div class="mint__section__header__status">
                    <p class="mint__section__header__status__counter">
                        {$currentItemsCounter}
                    </p>
                    <div class="mint__progress-bar">
                        <div class="mint__progress-bar__bar" style="width:{$inventorySlotsFilledPercent}%"></div>
                    </div>
                    <p class="mint__section__header__status__note">
                        {$itemsInventoryTitle}
                    </p>
                </div>
            </header>
            <nav class="mint__service-nav">
                <div class="mint__service-nav__container">{$itemsServiceLinks}</div>
            </nav>
            <div class="mint__block">
                <p class="mint__block__title"><a href="misc.php?action=economy_user_inventory">{$lang->mint_items_inventory_preview}</a></p>
                <div class="mint__table">
                    {$inventoryPreview}
                </div>
            </div>
            {$userActiveTransactions}
        </section>

        <section class="mint__section mint__section--currency">
            <header class="mint__section__header">
                <nav class="mint__section__header__nav">
                    <a href="misc.php?action=economy_balance_transfer" class="mint__section__header__nav__link">{$lang->mint_balance_transfer_new}</a>
                </nav>
                <div class="mint__section__header__status">
                    <p class="mint__section__header__status__counter">
                        {$currentBalanceCounter}
                    </p>
                </div>
            </header>
            <nav class="mint__service-nav">
                <div class="mint__service-nav__container">{$currencyServiceLinks}</div>
            </nav>
            <div class="mint__block">
                <p class="mint__block__title"><a href="misc.php?action=economy_balance_operations">{$lang->mint_balance_operations_recent}</a></p>
                <div class="mint__table">
                    {$recentBalanceOperations}
                </div>
            </div>
            {$balanceTopUsers}

            <div class="mint__block">
                <p class="mint__block__title">{$lang->mint_recent_public_balance_transfers}</p>
                <div class="mint__table mint__recent-public-transfers">
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