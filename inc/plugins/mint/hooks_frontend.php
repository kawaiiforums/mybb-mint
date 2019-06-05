<?php

namespace mint\Hooks;

use mint\DbRepository\BalanceTransfers;
use mint\DbRepository\ItemTransactions;
use mint\DbRepository\ItemTypes;

function global_start(): void
{
    global $mybb, $lang, $mintBalance, $mintInventoryStatus;

    $lang->load('mint');

    switch (\THIS_SCRIPT) {
        case 'misc.php':
            if (strpos($mybb->get_input('action'), 'economy_') === 0) {
                \mint\loadTemplates([
                    'action_link',
                    'action_link_button',
                    'balance_operations',
                    'balance_operations_entry',
                    'balance_top_users',
                    'balance_top_users_entry',
                    'balance_transfer',
                    'balance_transfer_form',
                    'currency_mint',
                    'currency_mint_form',
                    'flag',
                    'flowing_list_entry',
                    'hub',
                    'hub_service_link',
                    'inventory',
                    'inventory_entry',
                    'inventory_entry_options',
                    'inventory_entry_options_checkbox',
                    'inventory_entry_options_number',
                    'inventory_preview',
                    'inventory_slot',
                    'item_card',
                    'item_ownership',
                    'item_transaction',
                    'item_transactions_entry',
                    'items_discard_form',
                    'items_forge',
                    'items_forge_form',
                    'items_melt_form',
                    'items_transaction_new',
                    'items_transaction_new_form',
                    'items_transaction_new_inventory',
                    'message',
                    'page',
                    'recent_balance_operations',
                    'reward_sources',
                    'reward_sources_entry',
                    'reward_sources_legend',
                    'user_active_item_transactions',
                ], 'mint_');
            }

            break;
        case 'member.php':
            if ($mybb->get_input('action') == 'profile') {
                \mint\loadTemplates([
                    'balance_operations_entry',
                    'flag',
                    'inventory',
                    'inventory_preview',
                    'inventory_entry',
                    'message',
                    'recent_balance_operations',
                ], 'mint_');
            }
    }

    if ($mybb->user['uid'] != 0) {
        $mintBalance = \mint\getFormattedCurrency($mybb->user['mint_balance']);
        $mintInventoryStatus = $lang->sprintf(
            $lang->mint_items_count,
            (int)$mybb->user['mint_inventory_slots_occupied']
        );
    } else {
        $mintBalance = null;
        $mintInventoryStatus = null;
    }
}

function misc_start(): void
{
    global $mybb, $lang, $db, $plugins,
    $headerinclude, $header, $theme, $usercpnav, $footer;

    $pages = [
        'economy_hub' => [
            'permission' => function () use ($mybb): bool {
                return $mybb->user['uid'] != 0;
            },
            'controller' => function (array $globals) {
                extract($globals);

                $messages = null;

                if (!\mint\getSettingValue('manual_balance_operations')) {
                    $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);
                }

                // currency
                $currentBalanceCounter = \mint\getFormattedCurrency(
                    \mint\getUserBalance($mybb->user['uid']),
                    false,
                    true
                );

                $balanceServiceLinks = null;

                $links = [];

                if (\is_member(\mint\getCsvSettingValues('mint_groups'))) {
                    $links['mint'] = [
                        'url' => 'misc.php?action=economy_currency_mint',
                    ];
                }

                $plugins->run_hooks('mint_economy_hub_balance_service_links', $links);

                $currencyServiceLinks = \mint\getRenderedServiceLinks($links, 'currency');


                $query = \mint\getRecentUserBalanceOperations(
                    $mybb->user['uid'],
                    \mint\getSettingValue('recent_balance_operations_entries')
                );

                $recentBalanceOperations = \mint\getRenderedRecentBalanceOperations($query);


                $query = \mint\getRecentPublicBalanceTransfers(
                    \mint\getSettingValue('recent_balance_operations_entries')
                );

                if ($db->num_rows($query) != 0) {
                    $recentPublicTransfers = \mint\getRenderedBalanceOperationEntries($query, null, false);
                } else {
                    $recentPublicTransfers = \mint\getRenderedMessage($lang->mint_no_entries);
                }


                if (\mint\getSettingValue('top_users_entries') > 0) {
                    $query = \mint\getTopUsersByBalance(\mint\getSettingValue('top_users_entries'));

                    if ($db->num_rows($query) != 0) {
                        $entries = \mint\getRenderedBalanceTopUserEntries($query);

                        eval('$balanceTopUsers = "' . \mint\tpl('balance_top_users') . '";');
                    }
                } else {
                    $balanceTopUsers = null;
                }


                $legendEntries = \mint\getRegisteredRewardSourceLegendEntries();

                if ($legendEntries) {
                    $rewardSourcesLegend = \mint\getRenderedRewardSourceLegend($legendEntries);
                } else {
                    $rewardSourcesLegend = null;
                }

                // items
                $userInventoryData = \mint\getUserInventoryData($mybb->user);

                $userItemsCount = $userInventoryData['slotsOccupied'];

                if ($userInventoryData['id'] !== null) {
                    $itemsInventoryTitle = \htmlspecialchars_uni($userInventoryData['title']);
                    $userInventorySlots = (int)$userInventoryData['slots'];
                } else {
                    $itemsInventoryTitle = $lang->mint_items_no_inventory;
                    $userInventorySlots = 0;
                }

                $currentItemsCounter = $lang->sprintf(
                    $lang->mint_items_in_slots,
                    $userItemsCount,
                    $userInventorySlots
                );

                if ($userItemsCount > $userInventorySlots) {
                    $inventorySlotsFilledPercent = 100;
                } elseif ($userInventorySlots == 0) {
                    $inventorySlotsFilledPercent = 0;
                } else {
                    $inventorySlotsFilledPercent = round($userItemsCount / $userInventorySlots, 2) * 100;
                }


                $itemsServiceLinks = null;

                $links = [];

                if (\is_member(\mint\getCsvSettingValues('forge_groups'))) {
                    $links['forge'] = [
                        'url' => 'misc.php?action=economy_items_forge',
                    ];
                }

                $plugins->run_hooks('mint_economy_hub_items_service_links', $links);

                $itemsServiceLinks = \mint\getRenderedServiceLinks($links, 'items');


                $items = \mint\getItemOwnershipsWithDetails(
                    $mybb->user['uid'],
                    null,
                    \mint\getSettingValue('inventory_preview_entries'),
                    true
                );
                $inventoryPreview = \mint\getRenderedInventoryPreview($items, $mybb->user['uid']);


                $entries = \mint\getUserActiveTransactions($mybb->user['uid']);

                if ($entries) {
                    $entries = \mint\getRenderedTransactionEntries($entries);

                    eval('$userActiveTransactions = "' . \mint\tpl('user_active_item_transactions') . '";');
                } else {
                    $userActiveTransactions = null;
                }


                $entries = \mint\getRecentPublicItemTransactions(
                    \mint\getSettingValue('recent_item_transactions_entries')
                );

                if ($entries) {
                    $recentItemTransactions = \mint\getRenderedTransactionEntries($entries);
                } else {
                    $recentItemTransactions = \mint\getRenderedMessage($lang->mint_no_entries);
                }

                eval('$page = "' . \mint\tpl('hub') . '";');

                return $page;
            },
        ],
        'economy_currency_mint' => [
            'parents' => [
                'economy_hub',
            ],
            'permission' => function () use ($mybb): bool {
                return $mybb->user['uid'] != 0 && \is_member(\mint\getCsvSettingValues('mint_groups'));
            },
            'controller' => function (array $globals) {
                extract($globals);

                $messages = null;

                if (\mint\getSettingValue('manual_balance_operations')) {
                    if (isset($mybb->input['amount']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                        $amount = $mybb->get_input('amount', \MyBB::INPUT_INT);

                        $user = \get_user_by_username($mybb->get_input('user_name'));

                        if ($user) {
                            if ($amount >= 0) {
                                $terminationPoint = 'mint';
                            } else {
                                $terminationPoint = 'burn';
                            }

                            $result = \mint\userBalanceOperationWithTerminationPoint($user['uid'], $amount, $terminationPoint, false);

                            if ($result) {
                                if ($amount >= 0) {
                                    $messages .= \mint\getRenderedMessage($lang->sprintf(
                                        $lang->mint_mint_minted,
                                        (int)$amount
                                    ), 'success');
                                } else {
                                    $messages .= \mint\getRenderedMessage($lang->sprintf(
                                        $lang->mint_mint_burned,
                                        -(int)$amount
                                    ), 'success');
                                }
                            } else {
                                $messages .= \mint\getRenderedMessage($lang->sprintf(
                                    $lang->mint_mint_error,
                                    (int)$amount
                                ), 'error');
                            }
                        } else {
                            $messages .= \mint\getRenderedMessage($lang->mint_user_not_found, 'error');
                        }
                    }

                    $currencyTitle = \mint\getSettingValue('currency_name');

                    $minAmount = null;
                    $maxAmount = null;

                    eval('$form = "' . \mint\tpl('currency_mint_form') . '";');
                } else {
                    $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);

                    $form = null;
                }

                eval('$page = "' . \mint\tpl('currency_mint') . '";');

                return $page;
            },
        ],
        'economy_balance_transfer' => [
            'parents' => [
                'economy_hub',
            ],
            'permission' => function () use ($mybb): bool {
                return $mybb->user['uid'] != 0;
            },
            'controller' => function (array $globals) {
                extract($globals);

                $messages = null;

                if (\mint\getSettingValue('manual_balance_operations')) {
                    if ($mybb->request_method == 'post' && isset($mybb->input['amount']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                        $amount = $mybb->get_input('amount', \MyBB::INPUT_INT);

                        if ($amount > 0) {
                            if ($amount <= \mint\getUserBalance($mybb->user['uid'])) {
                                $user = \get_user_by_username($mybb->get_input('user_name'), [
                                    'fields' => [
                                        'ignorelist',
                                    ],
                                ]);

                                if ($user) {
                                    if (!\mint\userOnIgnoreList($mybb->user['uid'], $user)) {
                                        $details = [];

                                        if (!empty($mybb->input['note'])) {
                                            $details['note'] = $mybb->get_input('note');
                                        }

                                        if (
                                            !empty($mybb->input['private']) &&
                                            \is_member(\mint\getSettingValue('private_balance_transfer_groups'))
                                        ) {
                                            $details['private'] = true;
                                        }

                                        $result = BalanceTransfers::with($db)->execute(
                                            $mybb->user['uid'],
                                            $user['uid'],
                                            $amount,
                                            $details
                                        ) !== null;

                                        if ($result) {
                                            $messages .= \mint\getRenderedMessage($lang->sprintf(
                                                $lang->mint_balance_transfer_transferred,
                                                (int)$amount
                                            ), 'success');
                                        } else {
                                            $messages .= \mint\getRenderedMessage($lang->mint_balance_transfer_error, 'error');
                                        }
                                    } else {
                                        $messages .= \mint\getRenderedMessage($lang->mint_user_on_ignored_list, 'error');
                                    }
                                } else {
                                    $messages .= \mint\getRenderedMessage($lang->mint_user_not_found, 'error');
                                }
                            } else {
                                $messages .= \mint\getRenderedMessage($lang->mint_currency_amount_exceeding_balance, 'error');
                            }
                        }
                    }

                    $username = \htmlspecialchars_uni($mybb->input['user_name'] ?? null);
                    $amount = (int)($mybb->input['amount'] ?? 1);
                    $note = \htmlspecialchars_uni($mybb->input['note'] ?? null);

                    $currencyTitle = \mint\getSettingValue('currency_name');

                    $minAmount = 1;
                    $maxAmount = \mint\getUserBalance($mybb->user['uid']);

                    $privateCheckboxAttributes = null;

                    if (\is_member(\mint\getSettingValue('private_balance_transfer_groups'))) {
                        if (\mint\getSettingValue('private_balance_transfer_by_default')) {
                            $privateCheckboxAttributes .= ' checked="checked"';
                        }
                    } else {
                        $privateCheckboxAttributes .= ' disabled="disabled"';
                    }

                    eval('$form = "' . \mint\tpl('balance_transfer_form') . '";');
                } else {
                    $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);

                    $form = null;
                }

                eval('$page = "' . \mint\tpl('balance_transfer') . '";');

                return $page;
            },
        ],
        'economy_balance_operations' => [
            'parents' => [
                'economy_hub',
            ],
            'controller' => function (array $globals) {
                extract($globals);

                if (isset($mybb->input['user_id'])) {
                    $user = get_user($mybb->get_input('user_id', \MyBB::INPUT_INT));
                } elseif ($mybb->user['uid'] != 0) {
                    $user = $mybb->user;
                } else {
                    $user = null;
                }

                if ($user) {
                    $includePrivateWithUserIds = [
                        $mybb->user['uid'],
                    ];

                    $itemsNum = \mint\countUserPublicBalanceOperations($user['uid'], $includePrivateWithUserIds);

                    $pageTitle = $lang->sprintf(
                        $lang->mint_page_economy_balance_operations_user,
                        \htmlspecialchars_uni($user['username']),
                        $itemsNum
                    );

                    $listManager = new \mint\ListManager([
                        'mybb' => $mybb,
                        'baseurl' => 'misc.php?action=economy_balance_operations',
                        'order_columns' => [
                            'id' => 'bo.id',
                        ],
                        'order_dir' => 'desc',
                        'items_num' => $itemsNum,
                        'per_page' => $mybb->settings['threadsperpage'],
                    ]);

                    if ($itemsNum > 0) {
                        $entries = null;

                        $query = \mint\getUserPublicBalanceOperations(
                            $user['uid'],
                            $includePrivateWithUserIds,
                            $listManager->sql()
                        );

                        $entries = \mint\getRenderedBalanceOperationEntries($query, $user['uid']);
                    } else {
                        $entries = \mint\getRenderedMessage($lang->mint_no_entries);
                    }

                    $pagination = $listManager->pagination();
                } else {
                    $pageTitle = $lang->mint_page_economy_balance_operations;
                    $pagination = null;

                    $entries = \mint\getRenderedMessage($lang->mint_user_not_found, 'error');
                }

                eval('$content = "' . \mint\tpl('balance_operations') . '";');
                eval('$page = "' . \mint\tpl('page') . '";');

                return $page;
            },
        ],
        'economy_items_forge' => [
            'parents' => [
                'economy_hub',
            ],
            'permission' => function () use ($mybb): bool {
                return $mybb->user['uid'] != 0 && \is_member(\mint\getCsvSettingValues('forge_groups'));
            },
            'controller' => function (array $globals) {
                extract($globals);

                $maxAmount = 1000;

                $messages = null;
                $form = null;

                if (isset($mybb->input['item_type_id']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                    $amount = $mybb->get_input('amount', \MyBB::INPUT_INT);

                    if ($amount > 0 && $amount <= $maxAmount) {
                        $user = \get_user_by_username($mybb->get_input('user_name'));

                        if ($user) {
                            $itemType = ItemTypes::with($db)->getById($mybb->get_input('item_type_id', \MyBB::INPUT_INT));

                            if ($itemType) {
                                $items = array_fill(0, $amount, [
                                    'item_type_id' => $itemType['id'],
                                    'item_type_stacked' => $itemType['stacked'],
                                ]);

                                if (\mint\countAvailableUserInventorySlotsWithItems($user['uid'], $items) >= 0) {
                                    $result = \mint\createItemsWithTerminationPoint($itemType['id'], $amount, $user['uid'], 'forge');

                                    if ($result) {
                                        $messages .= \mint\getRenderedMessage($lang->sprintf(
                                            $lang->mint_items_forge_success_amount,
                                            (int)$amount
                                        ), 'success');
                                    } else {
                                        $messages .= \mint\getRenderedMessage($lang->mint_items_forge_error, 'error');
                                    }
                                } else {
                                    $messages .= \mint\getRenderedMessage($lang->mint_items_not_enough_inventory_slots, 'error');
                                }
                            } else {
                                $messages .= \mint\getRenderedMessage($lang->mint_item_type_not_found, 'error');
                            }
                        } else {
                            $messages .= \mint\getRenderedMessage($lang->mint_user_not_found, 'error');
                        }
                    }
                }

                $maxAmount = null;

                eval('$form = "' . \mint\tpl('items_forge_form') . '";');

                eval('$page = "' . \mint\tpl('items_forge') . '";');

                return $page;
            },
        ],
        'economy_items_melt' => [
            'parents' => [
                'economy_hub',
            ],
            'permission' => function () use ($mybb): bool {
                return $mybb->user['uid'] != 0 && \is_member(\mint\getCsvSettingValues('forge_groups'));
            },
            'controller' => function (array $globals) {
                extract($globals);

                $pageTitle = $lang->mint_page_economy_items_melt;

                $messages = null;
                $form = null;

                $item = \mint\getItemOwnershipWithDetails($mybb->get_input('item_ownership_id', \MyBB::INPUT_INT));

                if ($item !== null) {
                    if ($item['item_type_stacked']) {
                        $maxAmount = $item['stacked_amount'];
                        $amountFieldAttributes = null;
                    } else {
                        $maxAmount = 1;
                        $amountFieldAttributes = 'readonly="readonly"';
                    }

                    if (isset($mybb->input['amount']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                        $amount = $mybb->get_input('amount', \MyBB::INPUT_INT);

                        if ($amount > 0 && $amount <= $maxAmount) {
                            $result = \mint\removeItemsWithTerminationPoint($item['item_ownership_id'], $amount, 'melt');

                            if ($result) {
                                $messages .= \mint\getRenderedMessage($lang->sprintf(
                                    $lang->mint_items_melt_success_amount,
                                    (int)$amount
                                ), 'success');
                            } else {
                                $messages .= \mint\getRenderedMessage($lang->mint_items_melt_error, 'error');
                            }
                        }
                    }

                    $profileLink = \build_profile_link($item['user_username'], $item['user_id']);
                    $itemTypeTitle = \htmlspecialchars_uni($item['item_type_title']);

                    eval('$form = "' . \mint\tpl('items_melt_form') . '";');
                } else {
                    $messages = \mint\getRenderedMessage($lang->mint_user_item_not_found, 'error');
                }

                $content = $messages . $form;

                eval('$page = "' . \mint\tpl('page') . '";');

                return $page;
            },
        ],
        'economy_items_discard' => [
            'parents' => [
                'economy_hub',
            ],
            'permission' => function () use ($mybb): bool {
                return $mybb->user['uid'] != 0;
            },
            'controller' => function (array $globals) {
                extract($globals);

                $pageTitle = $lang->mint_page_economy_items_discard;

                $messages = null;
                $form = null;

                $item = \mint\getItemOwnershipWithDetails($mybb->get_input('item_ownership_id', \MyBB::INPUT_INT));

                if ($item !== null && $item['user_id'] == $mybb->user['uid'] && $item['item_type_discardable'] && !$item['item_transaction_id']) {
                    if ($item['item_type_stacked']) {
                        $maxAmount = $item['stacked_amount'];
                        $amountFieldAttributes = null;
                    } else {
                        $maxAmount = 1;
                        $amountFieldAttributes = 'readonly="readonly"';
                    }

                    if (isset($mybb->input['amount']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                        $amount = $mybb->get_input('amount', \MyBB::INPUT_INT);

                        if ($amount > 0 && $amount <= $maxAmount) {
                            $result = \mint\removeItemsWithTerminationPoint($item['item_ownership_id'], $amount, 'discard');

                            if ($result) {
                                $messages .= \mint\getRenderedMessage($lang->sprintf(
                                    $lang->mint_items_discard_success_amount,
                                    (int)$amount
                                ), 'success');
                            } else {
                                $messages .= \mint\getRenderedMessage($lang->mint_items_discard_error, 'error');
                            }
                        }
                    }

                    $itemTypeTitle = \htmlspecialchars_uni($item['item_type_title']);

                    eval('$form = "' . \mint\tpl('items_discard_form') . '";');
                } else {
                    $messages = \mint\getRenderedMessage($lang->mint_user_item_not_found, 'error');
                }

                $content = $messages . $form;

                eval('$page = "' . \mint\tpl('page') . '";');

                return $page;
            },
        ],
        'economy_user_inventory' => [
            'parents' => [
                'economy_hub',
            ],
            'controller' => function (array $globals) {
                extract($globals);

                if (isset($mybb->input['user_id'])) {
                    $user = get_user($mybb->get_input('user_id', \MyBB::INPUT_INT));
                } elseif ($mybb->user['uid'] != 0) {
                    $user = $mybb->user;
                } else {
                    $user = null;
                }

                if ($user) {
                    $userInventoryData = \mint\getUserInventoryData($user);

                    $items = \mint\getItemOwnershipsWithDetails($user['uid'], null, null, true);

                    $itemsNum = count($items);

                    $pageTitle = $lang->sprintf(
                        $lang->mint_page_economy_user_inventory_user,
                        \htmlspecialchars_uni($user['username']),
                        $userInventoryData['slotsOccupied']
                    );

                    if ($itemsNum > 0) {
                        $content = \mint\getRenderedInventory($items, 'standard', $userInventoryData['slots']);
                    } else {
                        $content = \mint\getRenderedMessage($lang->mint_no_entries);
                    }
                } else {
                    $pageTitle = $lang->mint_page_economy_user_inventory;

                    $content = \mint\getRenderedMessage($lang->mint_user_not_found, 'error');
                }

                eval('$page = "' . \mint\tpl('page') . '";');

                return $page;
            },
        ],
        'economy_item_ownership' => [
            'parents' => [
                'economy_hub',
            ],
            'controller' => function (array $globals) {
                extract($globals);

                $item = \mint\getItemOwnershipWithDetails($mybb->get_input('id', \MyBB::INPUT_INT));

                $actionLinks = null;

                if ($item !== null) {
                    $content = \mint\getRenderedItemCard($item);

                    $links = [];

                    if ($item['user_id'] == $mybb->user['uid'] && $item['item_type_discardable'] == true && $item['item_transaction_id'] == null) {
                        $links['discard'] = [
                            'url' => 'misc.php?action=economy_items_discard&item_ownership_id=' . $item['item_ownership_id'],
                            'title' => $lang->mint_items_action_discard,
                        ];
                    }

                    if (is_member(\mint\getSettingValue('forge_groups'))) {
                        $links['melt'] = [
                            'url' => 'misc.php?action=economy_items_melt&item_ownership_id=' . $item['item_ownership_id'],
                            'title' => $lang->mint_items_action_melt,
                        ];
                    }

                    if ($item['item_transaction_id']) {
                        $links['transaction'] = [
                            'url' => 'misc.php?action=economy_item_transaction&id=' . $item['item_transaction_id'],
                            'title' => $lang->mint_items_action_active_transaction,
                        ];
                    }

                    if ($links) {
                        $actionLinks = \mint\getRenderedActionLinks($links);
                    }
                } else {
                    $content = \mint\getRenderedMessage($lang->mint_user_item_not_found, 'error');
                }

                eval('$page = "' . \mint\tpl('item_ownership') . '";');

                return $page;
            },
        ],
        'economy_new_items_transaction' => [
            'parents' => [
                'economy_hub',
            ],
            'permission' => function () use ($mybb): bool {
                return $mybb->user['uid'] != 0;
            },
            'controller' => function (array $globals) {
                extract($globals);

                $user = $mybb->user;

                $userInventoryData = \mint\getUserInventoryData($user);

                $items = \mint\getItemOwnershipsWithDetails($user['uid'], null, null, false, true, true, true);

                $itemsNum = count($items);

                if (isset($mybb->input['user_item_selection']) || isset($mybb->input['selected_items'])) {
                    $messages = null;

                    if (isset($mybb->input['selected_items']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                        $userItemSelection = json_decode($mybb->get_input('selected_items'), true, 2);

                        if ($userItemSelection) {
                            $selectedItems = \mint\getItemIdsByResolvedStackedAmount($userItemSelection, true);
                            $askPrice = $mybb->get_input('ask_price', \MyBB::INPUT_INT);

                            if (!empty($selectedItems)) {
                                if ($askPrice >= 0) {
                                    $selectedItemsOwnerships = \mint\getItemOwnershipsById(
                                        array_column($selectedItems, 'item_ownership_id')
                                    );

                                    if (
                                        $selectedItemsOwnerships &&
                                        array_unique(
                                            array_column($selectedItemsOwnerships, 'user_id')
                                        ) == [$user['uid']]
                                    ) {
                                        $transactionId = ItemTransactions::with($db)->create([
                                            'ask_user_id' => $user['uid'],
                                            'ask_price' => $askPrice,
                                        ], $selectedItems);

                                        if ($transactionId !== null) {
                                            \redirect('misc.php?action=economy_item_transaction&id=' . (int)$transactionId, $lang->mint_item_transaction_new_success);
                                        } else {
                                            $messages .= \mint\getRenderedMessage($lang->mint_item_transaction_new_error, 'error');
                                        }
                                    }
                                }
                            }
                        }

                        $selectedItemsJson = \htmlspecialchars_uni($mybb->get_input('selected_items'));
                    } else {
                        $selectedItemsJson = \htmlspecialchars_uni(
                            json_encode($mybb->get_input('user_item_selection', \MyBB::INPUT_ARRAY), 0, 1)
                        );
                    }

                    $pageTitle = $lang->mint_page_economy_new_items_transaction;

                    eval('$form = "' . \mint\tpl('items_transaction_new_form') . '";');

                    $content = $messages . $form;

                    eval('$page = "' . \mint\tpl('page') . '";');

                    return $page;
                } else {
                    $pageTitle = $lang->sprintf(
                        $lang->mint_page_economy_user_inventory_user,
                        \htmlspecialchars_uni($user['username']),
                        $userInventoryData['slotsOccupied']
                    );

                    if ($itemsNum > 0) {
                        $content = \mint\getRenderedInventory($items, 'transaction-select');
                    } else {
                        $content = \mint\getRenderedMessage($lang->mint_no_entries);
                    }

                    eval('$content = "' . \mint\tpl('items_transaction_new_inventory') . '";');
                    eval('$page = "' . \mint\tpl('page') . '";');

                    return $page;
                }
            },
        ],
        'economy_item_transaction' => [
            'parents' => [
                'economy_hub',
            ],
            'controller' => function (array $globals) {
                extract($globals);

                $pageTitle = $lang->mint_page_economy_item_transaction;

                $transaction = \mint\getItemTransactionDetails($mybb->get_input('id', \MyBB::INPUT_INT));

                if ($transaction !== null) {
                    $transactionItems = \mint\getItemTransactionItems($transaction['id']);

                    $actionSignatureJson = json_encode([
                        'item_ownership_ids' => array_column($transactionItems, 'item_ownership_id'),
                    ]);

                    $messages = null;
                    $actionLinks = null;

                    if ($transaction['completed'] == 1) {
                        $status = $lang->mint_item_transaction_status_completed;
                    } elseif ($transaction['active'] == 1) {
                        $status = $lang->mint_item_transaction_status_active;
                    } else {
                        $status = $lang->mint_item_transaction_status_canceled;
                    }

                    $askPrice = \mint\getFormattedCurrency($transaction['ask_price']);
                    $askPriceSimple = \mint\getFormattedCurrency($transaction['ask_price'], true);

                    $askUser = \build_profile_link($transaction['ask_user_username'], $transaction['ask_user_id']);
                    $bidUser = \build_profile_link($transaction['bid_user_username'], $transaction['bid_user_id']);

                    $askDate = \my_date('normal', $transaction['ask_date']);

                    if ($transaction['completed'] == 1) {
                        $completedDate = \my_date('normal', $transaction['completed_date']);
                    } else {
                        $completedDate = null;
                    }

                    $itemsDetails = \mint\getItemsDetails(
                        array_column(
                            $transactionItems,
                            'item_id'
                        )
                    );

                    $links = [];

                    if ($mybb->user['uid'] != 0) {
                        if ($transaction['active'] == 1 && $transaction['ask_user_id'] == $mybb->user['uid']) {
                            if (isset($mybb->input['cancel']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                                $result = ItemTransactions::with($db)->cancel($transaction['id']);

                                if ($result) {
                                    \redirect('misc.php?action=economy_user_inventory', $lang->mint_item_transaction_cancel_success);
                                }
                            }

                            $links['cancel'] = [
                                'title' => $lang->mint_item_transaction_action_cancel,
                            ];
                        }

                        if ($transactionItems) {
                            if ($transaction['active'] == 1 && $transaction['ask_user_id'] != $mybb->user['uid']) {
                                $transactionPossible = !in_array(0, array_column($itemsDetails, 'item_ownership_active'));

                                if ($transactionPossible) {
                                    if (\mint\getSettingValue('manual_balance_operations')) {
                                        if (isset($mybb->input['complete']) && \verify_post_check($mybb->get_input('my_post_key'))) {
                                            if ($mybb->get_input('action_signature') === $actionSignatureJson) {
                                                if (\mint\getUserBalance($mybb->user['uid']) >= $transaction['ask_price']) {
                                                    $result = ItemTransactions::with($db)->execute($transaction['id'], $mybb->user['uid']);

                                                    if ($result) {
                                                        \redirect('misc.php?action=economy_user_inventory', $lang->mint_item_transaction_complete_success);
                                                    }
                                                } else {
                                                    $messages .= \mint\getRenderedMessage($lang->mint_currency_amount_exceeding_balance, 'error');
                                                }
                                            }
                                        }

                                        $links['complete'] = [
                                            'title' => $lang->sprintf(
                                                $lang->mint_item_transaction_action_buy_with,
                                                $askPriceSimple
                                            ),
                                        ];
                                    } else {
                                        $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);
                                    }
                                }
                            }
                        }
                    }

                    $items = \mint\getRenderedInventory($itemsDetails);

                    if ($links) {
                        $actionLinks = \mint\getRenderedActionLinks($links);
                    }

                    eval('$content = "' . \mint\tpl('item_transaction') . '";');
                } else {
                    $content = \mint\getRenderedMessage($lang->mint_item_transaction_not_found, 'error');
                }

                eval('$page = "' . \mint\tpl('page') . '";');

                return $page;
            },
        ],
    ];

    $plugins->run_hooks('mint_misc_pages', $pages);

    if (array_key_exists($mybb->get_input('action'), $pages)) {
        $pageName = $mybb->get_input('action');
        $page = $pages[$mybb->get_input('action')];

        if (isset($page['permission'])) {
            if ($page['permission']() === false) {
                \error_no_permission();
            }
        }

        if (!empty($page['parents'])) {
            foreach ($page['parents'] as $parent) {
                \add_breadcrumb($lang->{'mint_page_' . $parent}, 'misc.php?action=' . $parent);
            }
        }

        \add_breadcrumb($lang->{'mint_page_' . $pageName});

        // https://wiki.php.net/rfc/arrow_functions_v2
        $globals = compact([
            'mybb',
            'lang',
            'db',
            'plugins',
            'headerinclude',
            'header',
            'theme',
            'usercpnav',
            'footer',
        ]);

        $content = $page['controller']($globals);

        \output_page($content);
    }
}

function member_profile_end(): void
{
    global $mybb, $db, $lang, $memprofile, $theme,
    $mintContextUserBalance, $mintContextUserInventoryStatus, $mintRecentBalanceOperations, $mintInventoryPreview;

    $mintContextUserBalance = \mint\getFormattedCurrency($memprofile['mint_balance']);
    $mintContextUserInventoryStatus = $lang->sprintf(
        $lang->mint_items_count,
        (int)$memprofile['mint_inventory_slots_occupied']
    );

    $query = \mint\getUserPublicBalanceOperations(
        $memprofile['uid'],
        [
            $mybb->user['uid'],
        ],
        'LIMIT ' . \mint\getSettingValue('recent_balance_operations_entries')
    );
    $mintRecentBalanceOperations = \mint\getRenderedRecentBalanceOperations($query, $memprofile['uid']);

    $items = \mint\getItemOwnershipsWithDetails(
        $memprofile['uid'],
        null,
        \mint\getSettingValue('inventory_preview_entries'),
        true
    );
    $mintInventoryPreview = \mint\getRenderedInventoryPreview($items, $memprofile['uid']);
}

function postbit(array $post): array
{
    global $lang;

    if ($post['uid'] != 0) {
        $post['mintBalance'] = \mint\getFormattedCurrency($post['mint_balance']);
        $post['mintInventoryStatus'] = $lang->sprintf(
            $lang->mint_items_count,
            (int)$post['mint_inventory_slots_occupied']
        );
    } else {
        $post['mintBalance'] = null;
        $post['mintInventoryStatus'] = null;
    }

    return $post;
}

function postbit_prev(array $post): array
{
    return postbit($post);
}

function postbit_pm(array $post): array
{
    return postbit($post);
}

function postbit_announcement(array $post): array
{
    return postbit($post);
}

function xmlhttp(): void
{
    global $mybb, $db, $charset;

    if ($mybb->get_input('action') == 'economy_get_item_type') {
        $query = ltrim($mybb->get_input('query'));
        $searchType = $mybb->get_input('search_type', \MyBB::INPUT_INT);

        if (\my_strlen($query) < 2) {
            exit;
        }

        if ($mybb->get_input('getone', \MyBB::INPUT_INT) == 1) {
            $limit = 1;
        } else {
            $limit = 15;
        }

        $likeString = $db->escape_string_like($query);

        if ($searchType == 1) {
            $likeString .= '%';
        } elseif ($searchType == 2) {
            $likeString = '%' . $likeString;
        } else {
            $likeString = '%' . $likeString . '%';
        }

        header('Content-type: application/json; charset=' . $charset);

        $data = [];

        $query = $db->simple_select('mint_item_types', 'id, title', "LOWER(title) LIKE '" . $likeString . "'", [
            'order_by' => 'title',
            'order_dir' => 'asc',
            'limit' => $limit,
        ]);

        while ($row = $db->fetch_array($query)) {
            $data[] = [
                'id' => $row['id'],
                'text' => $row['title'],
            ];
        }

        echo json_encode($data);

        exit;
    }
}
