<?php

namespace mint\Hooks;

use mint\DbRepository\BalanceTransfers;
use mint\DbRepository\ItemTypes;

function misc_start(): void
{
    global $mybb, $lang, $db,
    $headerinclude, $header, $theme, $usercpnav, $footer;

    $lang->load('mint');

    $pages = [
        'economy_hub' => [
            'permission' => function () use ($mybb): bool {
                return $mybb->user['uid'] != 0;
            },
            'controller' => function (array $globals) {
                extract($globals);

                // currency
                $currentBalanceCounter = \mint\getFormattedCurrency(
                    \mint\getUserBalance($mybb->user['uid']),
                    true
                );

                $balanceServiceLinks = null;

                $links = [];

                if (\is_member(\mint\getCsvSettingValues('mint_groups'))) {
                    $links['mint'] = [
                        'url' => 'misc.php?action=economy_currency_mint',
                    ];
                }

                $currencyServiceLinks = \mint\getRenderedServiceLinks($links, 'currency');


                $query = \mint\getRecentUserBalanceOperations(
                    $mybb->user['uid'],
                    \mint\getSettingValue('recent_balance_operations_entries')
                );

                if ($db->num_rows($query) != 0) {
                    $recentBalanceOperations = \mint\getRenderedBalanceOperationEntries($query, $mybb->user['uid']);
                } else {
                    $recentBalanceOperations = \mint\getRenderedMessage($lang->mint_no_entries);
                }


                $query = \mint\getRecentPublicBalanceTransfers(
                    \mint\getSettingValue('recent_public_balance_transfers_entries')
                );

                if ($db->num_rows($query) != 0) {
                    $recentPublicTransfers = \mint\getRenderedBalanceOperationEntries($query, null, false);
                } else {
                    $recentPublicTransfers = \mint\getRenderedMessage($lang->mint_no_entries);
                }


                if (\mint\getSettingValue('top_users_entries') > 0) {
                    $query = \mint\getTopUsersByBalance(\mint\getSettingValue('top_users_entries'));

                    if ($db->num_rows($query) != 0) {
                        $balanceTopUsers = \mint\getRenderedBalanceTopUserEntries($query);
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

                if ($userInventoryData) {
                    $itemsInventoryTitle = \htmlspecialchars_uni($userInventoryData['title']);
                    $userInventorySlots = (int)$userInventoryData['slots'];
                } else {
                    $itemsInventoryTitle = $lang->mint_items_no_inventory;
                    $userInventorySlots = 0;
                }

                $itemsInventory = null;

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

                $itemsServiceLinks = \mint\getRenderedServiceLinks($links, 'items');

                if ($userItemsCount > 0) {
                    $items = \mint\getItemOwnershipsWithDetailsByUser($mybb->user['uid'], null, 10);
                    $inventoryPreview = \mint\getRenderedInventory($items, 'small');
                } else {
                    $inventoryPreview = \mint\getRenderedMessage($lang->mint_no_entries);
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
                    if (isset($mybb->input['amount']) && \verify_post_check($mybb->get_input('my_post_key'))) {
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
                                        );

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

                eval('$page = "' . \mint\tpl('balance_operations') . '";');

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

                    if ($amount <= $maxAmount) {
                        $user = \get_user_by_username($mybb->get_input('user_name'));

                        if ($user) {
                            $itemType = ItemTypes::with($db)->getById($mybb->get_input('item_type_id', \MyBB::INPUT_INT));

                            if ($itemType) {
                                $items = array_fill(0, $amount, [
                                    'stacked' => $itemType['stacked'],
                                    'item_type_id' => $itemType['id'],
                                ]);

                                if (\mint\countAvailableUserInventorySlotsWithItems($user['uid'], $items) >= 0) {
                                    $result = \mint\createItemsWithTerminationPoint($itemType['id'], $amount, $user['uid'], 'forge');

                                    if ($result) {
                                        $messages .= \mint\getRenderedMessage($lang->sprintf(
                                            $lang->mint_forge_forged,
                                            (int)$amount
                                        ), 'success');
                                    } else {
                                        $messages .= \mint\getRenderedMessage($lang->sprintf(
                                            $lang->mint_forge_error,
                                            (int)$amount
                                        ), 'error');
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

                        if ($amount <= $maxAmount) {
                            $result = \mint\removeItemsWithTerminationPoint($item['id'], $amount, 'melt');

                            if ($result) {
                                $messages .= \mint\getRenderedMessage($lang->sprintf(
                                    $lang->mint_melt_melted,
                                    (int)$amount
                                ), 'success');
                            } else {
                                $messages .= \mint\getRenderedMessage($lang->sprintf(
                                    $lang->mint_melt_melted,
                                    (int)$amount
                                ), 'error');
                            }
                        }
                    }

                    $profileLink = \build_profile_link($item['user_username'], $item['user_id']);
                    $itemTypeTitle = \htmlspecialchars_uni($item['item_type_title']);

                    eval('$form = "' . \mint\tpl('items_melt_form') . '";');
                } else {
                    $messages = \mint\getRenderedMessage($lang->mint_user_item_not_found, 'error');
                }

                eval('$page = "' . \mint\tpl('items_melt') . '";');

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

                    $items = \mint\getItemOwnershipsWithDetailsByUser($user['uid']);

                    $itemsNum = count($items);

                    $pageTitle = $lang->sprintf(
                        $lang->mint_page_economy_user_inventory_user,
                        \htmlspecialchars_uni($user['username']),
                        $itemsNum
                    );

                    if ($itemsNum > 0) {
                        $content = \mint\getRenderedInventory($items, 'standard', $userInventoryData['slots']);
                    } else {
                        $content = \mint\getRenderedMessage($lang->mint_no_entries);
                    }

                } else {
                    $pageTitle = $lang->mint_page_economy_user_inventory;
                    $pagination = null;

                    $content = \mint\getRenderedMessage($lang->mint_user_not_found, 'error');
                }

                eval('$page = "' . \mint\tpl('user_inventory') . '";');

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

                    if (is_member(\mint\getSettingValue('forge_groups'))) {
                        $links['melt'] = [
                            'url' => 'misc.php?action=economy_items_melt&item_ownership_id=' . $item['id'],
                            'title' => $lang->mint_items_action_melt,
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
    ];

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
