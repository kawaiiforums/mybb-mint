<?php

namespace mint\modules\shop;

function getRenderedItemCategoryMenu(array $itemCategories): ?string
{
    global $mybb, $lang;

    $entries = null;

    foreach ($itemCategories as $entry) {
        $url = 'misc.php?action=economy_items_shop&amp;category_id=' . (int)$entry['id'];
        $title = \htmlspecialchars_uni($entry['title']);
        $count = $lang->sprintf(
            $lang->mint_items_count,
            (int)$entry['shop_items_count']
        );

        if (!empty($entry['image'])) {
            $image = '<img class="mint__grid-menu__entry__image" src="' . $mybb->get_asset_url(\htmlspecialchars_uni($entry['image'])) . '">';
        } else {
            $image = null;
        }

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
            $availableItems = $entry['sales_limit'] - $entry['times_purchased'];

            if ($availableItems != 0) {
                $itemsLeft = $lang->sprintf(
                    $lang->mint_shop_items_left,
                    $availableItems
                );
            } else {
                $itemsLeft = $lang->mint_shop_items_none_left;
            }
        } else {
            $itemsLeft = null;
        }

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
        }

        if (!$entry['item_type_transferable']) {
            $classes[] = $elementClass . '--non-transferable';
        }

        $classes = implode(' ', $classes);

        $entryElementClass = 'mint__grid-detail__entry';

        $entryClasses = [
            $entryElementClass,
        ];

        if ($entry['sales_limit'] != 0 && $entry['sales_limit'] == $entry['times_purchased']) {
            $entryClasses[] = $entryElementClass . '--unavailable';
        }

        $entryClasses = implode(' ', $entryClasses);

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
