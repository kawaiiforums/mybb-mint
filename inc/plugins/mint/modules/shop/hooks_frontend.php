<?php

namespace mint\modules\shop\Hooks;

use mint\DbRepository\ItemCategories;
use mint\modules\shop\DbRepository\ShopItems;

function global_start(): void
{
    global $mybb;

    switch (\THIS_SCRIPT) {
        case 'misc.php':
            if (strpos($mybb->get_input('action'), 'economy_') === 0) {
                \mint\loadTemplates([
                    'item',
                    'item_category_menu',
                    'item_category_menu_entry',
                    'item_list',
                    'item_list_entry',
                ], 'mint.shop_');
            }

            break;
    }
}

function mint_economy_hub_items_service_links(array &$links): void
{
    $links['shop'] = [
        'url' => 'misc.php?action=economy_items_shop',
    ];
}

function mint_misc_pages(array &$pages): void
{
    global $mybb;

    $pages['economy_items_shop'] = [
        'parents' => [
            'economy_hub',
        ],
        'controller' => function (array $globals) {
            extract($globals);

            if (isset($mybb->input['category_id'])) {
                $itemCategory = ItemCategories::with($db)->getById($mybb->get_input('category_id', \MyBB::INPUT_INT));

                if ($itemCategory) {
                    $pageTitle = $lang->sprintf(
                        $lang->mint_shop_item_category,
                        \htmlspecialchars_uni($itemCategory['title'])
                    );

                    $items = \mint\modules\shop\getShopItemsByCategory($itemCategory['id'], \mint\getSettingValue('shop_list_available_items_only'));

                    if ($items) {
                        $content = \mint\modules\shop\getRenderedShopItemEntries($items);
                    } else {
                        $content = \mint\getRenderedMessage($lang->mint_no_entries);
                    }
                }
            }

            if (!isset($content)) {
                $pageTitle = $lang->mint_shop_item_categories;

                $itemCategories = \mint\modules\shop\getShopItemCategories(true, \mint\getSettingValue('shop_list_available_items_only'));

                if ($itemCategories) {
                    $content = \mint\modules\shop\getRenderedItemCategoryMenu($itemCategories);
                } else {
                    $content = \mint\getRenderedMessage($lang->mint_no_entries);
                }
            }

            eval('$page = "' . \mint\tpl('page') . '";');

            return $page;
        },
    ];
    $pages['economy_shop_item'] = [
        'parents' => [
            'economy_hub',
            'economy_items_shop',
        ],
        'controller' => function (array $globals) {
            extract($globals);

            if (isset($mybb->input['id'])) {
                $shopItem = \mint\modules\shop\getShopItemDetailsById($mybb->get_input('id', \MyBB::INPUT_INT));
            } else {
                $shopItem = null;
            }

            if ($shopItem) {
                $messages = null;
                $links = [];

                if (
                    $shopItem['sales_limit'] == 0 ||
                    $shopItem['sales_limit'] > $shopItem['times_purchased']
                ) {
                    if ($mybb->user['uid'] != 0) {
                        if (\mint\getSettingValue('manual_balance_operations')) {
                            $links['purchase'] = [
                                'title' => $lang->sprintf(
                                    $lang->mint_shop_item_action_purchase,
                                    \mint\getFormattedCurrency($shopItem['ask_price'], true)
                                ),
                                'url' => 'misc.php?action=economy_shop_item_purchase&id=' . (int)$shopItem['id'],
                            ];
                        } else {
                            $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);
                        }
                    }
                }

                if ($shopItem['sales_limit'] != 0) {
                    $inStock = $shopItem['sales_limit'] - $shopItem['times_purchased'];
                } else {
                    $inStock = '&infin;';
                }

                $pageTitle = \htmlspecialchars_uni($shopItem['item_type_title']);

                $itemCard = \mint\getRenderedItemCard($shopItem, [
                    'attributes' => [
                        [
                            'title' => $lang->mint_shop_item_ask_price,
                            'value' => \mint\getFormattedCurrency($shopItem['ask_price']),
                        ],
                        [
                            'title' => $lang->mint_shop_item_in_stock,
                            'value' => $inStock,
                        ],
                    ],
                ]);

                if ($links) {
                    $actionLinks = \mint\getRenderedActionLinks($links);
                } else {
                    $actionLinks = null;
                }

                eval('$content = "' . \mint\tpl('shop.item') . '";');
            } else {
                $pageTitle = $lang->mint_page_economy_items_shop;
                $content = \mint\getRenderedMessage($lang->mint_shop_item_not_found);
            }

            eval('$page = "' . \mint\tpl('page') . '";');

            return $page;
        }
    ];
    $pages['economy_shop_item_purchase'] = [
        'parents' => [
            'economy_hub',
            'economy_items_shop',
        ],
        'permission' => function () use ($mybb): bool {
            return $mybb->user['uid'] != 0;
        },
        'controller' => function (array $globals) {
            extract($globals);

            if (isset($mybb->input['id'])) {
                $shopItem = \mint\modules\shop\getShopItemDetailsById($mybb->get_input('id', \MyBB::INPUT_INT));
            } else {
                $shopItem = null;
            }

            if (
                $shopItem && (
                    $shopItem['sales_limit'] == 0 ||
                    $shopItem['sales_limit'] > $shopItem['times_purchased']
                )
            ) {
                if ($shopItem['sales_limit'] > 0) {
                    $maxAmount = $shopItem['sales_limit'] - $shopItem['times_purchased'];
                } else {
                    $maxAmount = 1000;
                }

                $messages = null;

                if (\mint\getSettingValue('manual_balance_operations')) {
                    $actionSignatureJson = json_encode([
                        'ask_price' => $shopItem['ask_price'],
                    ]);

                    if (isset($mybb->input['amount']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                        if ($mybb->get_input('action_signature') === $actionSignatureJson) {
                            $amount = $mybb->get_input('amount', \MyBB::INPUT_INT);

                            if ($amount > 0 && $amount <= $maxAmount) {
                                $priceTotal = $amount * $shopItem['ask_price'];

                                if (\mint\getUserBalance($mybb->user['uid']) >= $priceTotal) {
                                    $items = array_fill(0, $amount, [
                                        'item_type_id' => $shopItem['item_type_id'],
                                        'item_type_stacked' => $shopItem['item_type_stacked'],
                                    ]);

                                    if (\mint\countAvailableUserInventorySlotsWithItems($mybb->user['uid'], $items) >= 0) {
                                        $result = ShopItems::with($db)->purchase($shopItem['id'], $amount, $mybb->user['uid']);

                                        if ($result) {
                                            $messages .= \mint\getRenderedMessage(
                                                $lang->sprintf(
                                                    $lang->mint_shop_item_purchase_success,
                                                    (int)$amount
                                                ),
                                                'success'
                                            );
                                        } else {
                                            $messages .= \mint\getRenderedMessage($lang->mint_shop_item_purchase_error, 'error');
                                        }
                                    } else {
                                        $messages .= \mint\getRenderedMessage($lang->mint_items_not_enough_inventory_slots, 'error');
                                    }
                                } else {
                                    $messages .= \mint\getRenderedMessage($lang->mint_currency_amount_exceeding_balance, 'error');
                                }
                            }
                        }
                    }
                } else {
                    $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);
                }

                $askPrice = \mint\getFormattedCurrency($shopItem['ask_price']);
                $askPriceInt = $shopItem['ask_price'];
                $askPriceHtml = \mint\getFormattedCurrency($shopItem['ask_price'], false, true);

                $pageTitle = \htmlspecialchars_uni($shopItem['item_type_title']);

                eval('$content = "' . \mint\tpl('shop.item_purchase') . '";');

                eval('$page = "' . \mint\tpl('page') . '";');
            } else {
                \mint\redirect('misc.php?action=economy_shop_item&id=' . (int)$shopItem['id']);
            }

            return $page;
        }
    ];
}
