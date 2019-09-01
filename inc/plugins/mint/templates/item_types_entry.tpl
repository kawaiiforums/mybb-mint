<div class="mint__item-card mint__list__entry" data-matchable-string="{$matchableString}" id="itemType{$itemTypeId}">
    <div class="mint__item-card__visual">
        <div class="mint__item-icon">
            <div class="{$classes}" style="background-image: url({$imageUrl})">
                <div class="mint__inventory__item__stacked-amount" title="{$stackedAmountText}">{$stackedAmount}</div>
            </div>
        </div>
    </div>

    <div class="mint__item-card__details">
        <p class="mint__item-card__category">{$categoryTitle}</p>
        <p class="mint__item-card__title">
            <a href="misc.php?action=economy_item_types#itemType{$itemTypeId}">{$itemTitle}</a>
        </p>
        {$flags}
        <p class="mint__item-card__description">{$itemDescription}</p>

        <div class="mint__flowing-list">
            {$attributesHtml}
        </div>
    </div>
</div>