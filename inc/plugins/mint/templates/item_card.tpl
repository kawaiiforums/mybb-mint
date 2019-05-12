<div class="mint__item-card">
    <div class="mint__item-card__visual">
        <img class="mint__item-card__image" src="{$imageUrl}" />
        <br />
        <p class="mint__item-card__stacked-amount" title="{$stackedAmountText}">{$stackedAmount}</p>
    </div>
    <div class="mint__item-card__details">
        <p class="mint__item-card__category">{$categoryTitle}</p>
        <p class="mint__item-card__title">{$title} </p>

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