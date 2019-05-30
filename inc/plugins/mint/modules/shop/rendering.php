<?php

namespace mint\modules\shop;

function getRenderedItemCategoryMenu(array $itemCategories): ?string
{
    $entries = null;

    foreach ($itemCategories as $entry) {
        $url = 'misc.php?action=economy_items_shop&amp;category_id=' . (int)$entry['id'];
        $title = \htmlspecialchars_uni($entry['title']);

        eval('$entries .= "' . \mint\tpl('shop.item_category_menu_entry') . '";');
    }

    eval('$output = "' . \mint\tpl('shop.item_category_menu') . '";');

    return $output;
}

function getRenderedShopItemEntries(array $items): ?string
{
    global $mybb, $lang;

    $entries = null;

    foreach ($items as $entry) {
        $url = 'misc.php?action=economy_shop_item&amp;id=' . (int)$entry['id'];
        $title = \htmlspecialchars_uni($entry['item_type_title']);

        $askPrice = \mint\getFormattedCurrency($entry['ask_price']);

        if ($entry['sales_limit'] != 0) {
            $itemsLeft = $lang->sprintf(
                $lang->mint_items_shop_items_left,
                $entry['sales_limit'] - $entry['times_purchased']
            );
        } else {
            $itemsLeft = null;
        }

        $flags = null;

        $elementClass = 'mint__inventory__item';

        $classes = [
            $elementClass,
        ];

        if ($entry['item_type_stacked']) {
            $classes[] = $elementClass . '--stacked';
        } else {
            $classes[] = $elementClass . '--standard';
        }

        if (!$entry['item_type_discardable']) {
            $classes[] = $elementClass . '--non-discardable';

            $flagType = 'non-discardable';
            $flagContent = $lang->mint_item_non_discardable;

            eval('$flags .= "' . \mint\tpl('flag') . '";');
        }

        if (!$entry['item_type_transferable']) {
            $classes[] = $elementClass . '--non-transferable';

            $flagType = 'non-transferable';
            $flagContent = $lang->mint_item_non_transferable;

            eval('$flags .= "' . \mint\tpl('flag') . '";');
        }

        $classes = implode(' ', $classes);

        if ($entry['item_type_image']) {
            $imageUrl = $mybb->get_asset_url($entry['item_type_image']);
        } else {
            $imageUrl = null;
        }

        eval('$entries .= "' . \mint\tpl('shop.item_list_entry') . '";');
    }

    eval('$output = "' . \mint\tpl('shop.item_list') . '";');

    return $output;
}
