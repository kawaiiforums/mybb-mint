<div class="mint__card mint__card--{$activeStatus} mint__swatch--items">
    <a href="{$url}" class="mint__card__heading">
            <p class="mint__card__heading__title">{$lang->mint_item_transaction} <strong>#{$id}</strong></p>
            <div class="mint__card__heading__meta">
                <p class="mint__tag">{$statusText}</p>
            </div>
    </a>
    <div class="mint__card__body">
        {$flags}
        <p class="mint__balance-operation__details">{$offerDetails}</p>
        {$offerPreview}
        <p class="mint__balance-operation__details">{$askDetails}</p>
        {$askPreview}
    </div>
</div>
