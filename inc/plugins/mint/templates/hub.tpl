<html>
<head>
    <title>{$mybb->settings['bbname']} - {$lang->mint_page_economy_hub}</title>
    {$headerinclude}
</head>
<body>
{$header}

<div class="mint">
    {$messages}

    <div class="mint__sections">
        <section class="mint__section mint__section--items">
            <header class="mint__section__header">
                <nav class="mint__section__header__nav">
                    <a href="misc.php?action=economy_new_items_transaction" class="mint__section__header__nav__link">{$lang->mint_item_transaction_new}</a>
                </nav>
                <div class="mint__section__header__status">
                    <p class="mint__section__header__status__counter">
                        <a href="misc.php?action=economy_user_inventory&amp;user_id={$mybb->user['uid']}">{$currentItemsCounter}</a>
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
            {$inventoryPreview}
            {$userActiveTransactions}
            <div class="mint__block">
                <p class="mint__block__title">{$lang->mint_recent_completed_public_item_transactions}</p>
                <div class="mint__table">
                    {$recentItemTransactions}
                </div>
            </div>
            <div class="mint__block">
                <p class="mint__block__title"><a href="misc.php?action=economy_active_transactions">{$lang->mint_recent_active_public_item_transactions}</a></p>
                <div class="mint__table">
                    {$recentActivePublicItemTransactions}
                </div>
            </div>
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
            {$recentBalanceOperations}
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