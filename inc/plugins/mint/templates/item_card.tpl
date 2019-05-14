<div class="mint__item-card">
    <div class="mint__item-card__visual">
        <div class="mint__grid__item">
            <div class="{$classes}" style="background-image: url({$imageUrl})">
                <div class="mint__inventory__item__stacked-amount" title="{$stackedAmountText}">{$stackedAmount}</div>
            </div>
        </div>
    </div>

    <div class="mint__item-card__details">
        <p class="mint__item-card__category">{$categoryTitle}</p>
        <p class="mint__item-card__title">{$title}</p>{$flags}

        <div class="mint__flowing-list">
            <div class="mint__flowing-list__entry">
                <span class="mint__flowing-list__entry__title">{$lang->mint_item_activation_date}</span>
                <span class="mint__flowing-list__entry__value">{$itemActivationDate}</span>
            </div>
            <br />
            <div class="mint__flowing-list__entry">
                <span class="mint__flowing-list__entry__title">{$lang->mint_item_owner}</span>
                <span class="mint__flowing-list__entry__value">{$ownedBy}</span>
            </div>
        </div>
    </div>
</div>