<?php

namespace mint\Hooks;

use mint\DbRepository\{BalanceOperations, BalanceTransfers, CurrencyTerminationPoints, InventoryTypes, Items, ItemTypes};

function misc_start()
{
    global $mybb, $lang, $db,
    $headerinclude, $header, $theme, $usercpnav, $footer;

    $lang->load('mint');

    if ($mybb->get_input('action') == 'economy') {
        if ($mybb->user['uid'] != 0) {
            \add_breadcrumb($lang->mint_hub, 'misc.php?action=economy');

            // currency
            $currentBalanceCounter = \mint\getFormattedCurrency(
                \mint\getUserBalance($mybb->user['uid']),
                true
            );

            $balanceServiceLinks = null;

            $links = [];

            if (\is_member(\mint\getCsvSettingValues('mint_groups'))) {
                $links['mint'] = [
                    'url' => 'misc.php?action=economy_balance_mint',
                    'title' => $lang->mint_mint,
                ];
            }

            $currencyServiceLinks = \mint\getRenderedServiceLinks($links);


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
                    'title' => $lang->mint_forge,
                ];
            }

            $itemsServiceLinks = \mint\getRenderedServiceLinks($links);

            eval('$page = "' . \mint\tpl('hub') . '";');
            \output_page($page);
        } else {
            \error_no_permission();
        }
    } elseif ($mybb->get_input('action') == 'economy_balance_mint') {
        if ($mybb->user['uid'] != 0 && \is_member(\mint\getCsvSettingValues('mint_groups'))) {
            \add_breadcrumb($lang->mint_hub, 'misc.php?action=economy');
            \add_breadcrumb($lang->mint_mint);

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

                eval('$form = "' . \mint\tpl('mint_form') . '";');
            } else {
                $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);

                $form = null;
            }

            eval('$page = "' . \mint\tpl('mint') . '";');
            \output_page($page);
        } else {
            \error_no_permission();
        }
    } elseif ($mybb->get_input('action') == 'economy_balance_transfer') {
        if ($mybb->user['uid'] != 0) {
            \add_breadcrumb($lang->mint_hub, 'misc.php?action=economy');
            \add_breadcrumb($lang->mint_balance_transfer);

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

                eval('$form = "' . \mint\tpl('transfer_form') . '";');
            } else {
                $messages .= \mint\getRenderedMessage($lang->mint_balance_operations_disabled);

                $form = null;
            }

            eval('$page = "' . \mint\tpl('transfer') . '";');
            \output_page($page);
        } else {
            \error_no_permission();
        }
    } elseif ($mybb->get_input('action') == 'economy_balance_operations') {
        \add_breadcrumb($lang->mint_hub, 'misc.php?action=economy');
        \add_breadcrumb($lang->mint_balance_operations);

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
                $lang->mint_user_balance_operations,
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
            $pageTitle = $lang->mint_balance_operations;
            $pagination = null;

            $entries = \mint\getRenderedMessage($lang->mint_user_not_found, 'error');
        }

        eval('$page = "' . \mint\tpl('balance_operations') . '";');
        \output_page($page);
    } elseif ($mybb->get_input('action') == 'economy_items_forge') {
        if ($mybb->user['uid'] != 0 && \is_member(\mint\getCsvSettingValues('forge_groups'))) {
            \add_breadcrumb($lang->mint_hub, 'misc.php?action=economy');
            \add_breadcrumb($lang->mint_forge);

            $maxAmount = 1000;

            $messages = null;

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
                                $result = \mint\createUserItemsWithTerminationPoint($itemType['id'], $amount, $user['uid'], 'forge');

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

            $currencyTitle = \mint\getSettingValue('currency_name');

            $minAmount = null;
            $maxAmount = null;

            eval('$form = "' . \mint\tpl('forge_form') . '";');

            eval('$page = "' . \mint\tpl('forge') . '";');
            \output_page($page);
        } else {
            \error_no_permission();
        }

    }
}

function xmlhttp()
{
    global $mybb, $db, $charset;

    if ($mybb->get_input('action') == 'mint_get_item_type') {
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
