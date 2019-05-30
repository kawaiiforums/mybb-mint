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
                        $lang->mint_items_shop_category,
                        \htmlspecialchars_uni($itemCategory['title']),
                    );

                    $items = \mint\queryResultAsArray(
                        ShopItems::with($db)->get(
                            '*',
                            'WHERE item_category_id = ' . (int)$itemCategory['id'] . ' AND (sales_limit = 0 OR sales_limit > times_purchased)',
                            [
                                'mint_item_types' => [
                                    'title',
                                    'image',
                                    'stacked',
                                    'discardable',
                                    'transferable',
                                ],
                            ]
                        )
                    );

                    if ($items) {
                        $content = \mint\modules\shop\getRenderedShopItemEntries($items);
                    } else {
                        $content = \mint\getRenderedMessage($lang->mint_no_entries);
                    }
                }
            }

            if (!isset($content)) {
                $pageTitle = $lang->mint_shop_item_categories;

                $itemCategories = \mint\queryResultAsArray(ItemCategories::with($db)->get(), 'id');

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
                $entry = current(\mint\queryResultAsArray(
                    ShopItems::with($db)->get(
                        '*',
                        'WHERE t1.id = ' . $mybb->get_input('id', \MyBB::INPUT_INT),
                        [
                            'mint_item_types' => [
                                'title',
                                'description',
                                'image',
                                'stacked',
                                'discardable',
                                'transferable',
                            ],
                        ]
                    )
                ));
            } else {
                $entry = null;
            }

            if (
                $entry && (
                    $entry['sales_limit'] == 0 ||
                    $entry['sales_limit'] > $entry['times_purchased']
                )
            ) {
                $actionSignatureJson = json_encode([
                    'ask_price' => $entry['ask_price'],
                ]);

                $messages = null;
                $actionLinks = null;
                $links = [];

                if ($mybb->user['uid'] != 0) {
                    if (\mint\getSettingValue('manual_balance_operations')) {
                        if (isset($mybb->input['purchase']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                            if ($mybb->get_input('action_signature') === $actionSignatureJson) {
                                if (\mint\getUserBalance($mybb->user['uid']) >= $entry['ask_price']) {
                                    $items = array_fill(0, 1, [
                                        'item_type_id' => $entry['item_type_id'],
                                        'item_type_stacked' => $entry['item_type_stacked'],
                                    ]);

                                    if (\mint\countAvailableUserInventorySlotsWithItems($mybb->user['uid'], $items) >= 0) {
                                        $result = ShopItems::with($db)->purchase($entry['id'], $mybb->user['uid']);

                                        if ($result) {
                                            \redirect('misc.php?action=economy_user_inventory', $lang->mint_shop_item_purchase_success);
                                        } else {
                                            $messages .= \mint\getRenderedMessage($lang->mint_shop_purchase_error, 'error');
                                        }
                                    } else {
                                        $messages .= \mint\getRenderedMessage($lang->mint_items_not_enough_inventory_slots, 'error');
                                    }
                                } else {
                                    $messages .= \mint\getRenderedMessage($lang->mint_currency_amount_exceeding_balance, 'error');
                                }
                            }
                        }

                        $links['purchase'] = [
                            'title' => $lang->sprintf(
                                $lang->mint_shop_item_action_buy_with,
                                \mint\getFormattedCurrency($entry['ask_price'], true)
                            ),
                        ];
                    } else {
                        $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);
                    }
                }

                if ($entry['sales_limit'] != 0) {
                    $inStock = $entry['sales_limit'] - $entry['times_purchased'];
                } else {
                    $inStock = '&infin;';
                }

                $pageTitle = \htmlspecialchars_uni($entry['item_type_title']);

                $itemCard = \mint\getRenderedItemCard($entry, [
                    'attributes' => [
                        [
                            'title' => $lang->mint_shop_item_ask_price,
                            'value' => \mint\getFormattedCurrency($entry['ask_price']),
                        ],
                        [
                            'title' => $lang->mint_shop_item_in_stock,
                            'value' => $inStock,
                        ],
                    ],
                ]);

                if ($links) {
                    $actionLinks = \mint\getRenderedActionLinks($links);
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
}
