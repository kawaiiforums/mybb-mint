<?php

namespace mint\Hooks;

use mint\DbRepository\{BalanceOperations, BalanceTransfers, TerminationPoints};

function misc_start()
{
    global $mybb, $lang, $db,
    $headerinclude, $header, $theme, $usercpnav, $footer;

    $lang->load('mint');

    if ($mybb->get_input('action') == 'economy') {
        if ($mybb->user['uid'] != 0) {
            \add_breadcrumb($lang->mint_hub, 'misc.php?action=economy');

            $currentBalance = \mint\getFormattedCurrency(
                \mint\getUserBalance($mybb->user['uid']),
                true
            );

            $inventoryServiceLinks = null;
            $balanceServiceLinks = null;

            $links = [
                'mint' => [
                    'url' => 'misc.php?action=economy_balance_mint',
                    'title' => $lang->mint_mint,
                ],
            ];

            foreach ($links as $serviceName => $service) {
                $url = \htmlspecialchars_uni($service['url']);
                $title = \htmlspecialchars_uni($service['title']);

                eval('$balanceServiceLinks .= "' . \mint\tpl('hub_service_link') . '";');
            }


            $recentBalanceOperations = null;

            $query = \mint\getUserBalanceOperations($mybb->user['uid'], "ORDER BY id DESC LIMIT 3");

            if ($db->num_rows($query) != 0) {
                while ($entry = $db->fetch_array($query)) {
                    $amount = \mint\getFormattedCurrency(abs($entry['value']));
                    $date = \my_date('normal', $entry['date']);

                    if ($entry['value'] < 0) {
                        $sign = '-';
                        $type = 'negative';
                    } else {
                        $sign = '+';
                        $type = 'positive';
                    }

                    $notes = [];

                    if ($entry['termination_point_name']) {
                        $notes[] = $lang->sprintf(
                            $lang->mint_balance_operations_termination_point,
                            \htmlspecialchars_uni($entry['termination_point_name'])
                        );
                    }

                    if ($entry['to_user_id'] && $entry['to_user_id'] != $mybb->user['uid']) {
                        $notes[] = $lang->sprintf(
                            $lang->mint_balance_operations_to_user,
                            \build_profile_link($entry['to_username'], $entry['to_user_id'])
                        );
                    }

                    if ($entry['from_user_id'] && $entry['from_user_id'] != $mybb->user['uid']) {
                        $notes[] = $lang->sprintf(
                            $lang->mint_balance_operations_from_user,
                            \build_profile_link($entry['from_username'], $entry['from_user_id'])
                        );
                    }

                    $notes = implode(' &middot; ', $notes);

                    eval('$recentBalanceOperations .= "' . \mint\tpl('balance_operations_entry') . '";');
                }
            } else {
                $recentBalanceOperations = \mint\getRenderedMessage($lang->mint_no_entries);
            }


            $legendEntries = \mint\getRegisteredRewardSourceLegendEntries();

            if ($legendEntries) {
                $entries = null;

                foreach ($legendEntries as $legendEntry) {
                    $title = \htmlspecialchars_uni($legendEntry['title']);

                    if (isset($legendEntry['reward'])) {
                        if (is_callable($legendEntry['reward'])) {
                            $reward = $legendEntry['reward']();
                        } else {
                            $reward = $legendEntry['reward'];
                        }

                        $reward = my_number_format($reward);
                    } else {
                        $reward = null;
                    }

                    eval('$entries .= "' . \mint\tpl('reward_sources_entry') . '";');
                }

                eval('$rewardSources = "' . \mint\tpl('reward_sources') . '";');
            } else {
                $rewardSources = null;
            }

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
                if (isset($mybb->input['amount']) && verify_post_check($mybb->get_input('my_post_key'))) {
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

                eval('$form = "' . \mint\tpl('transfer_form') . '";');
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
                if (isset($mybb->input['amount']) && verify_post_check($mybb->get_input('my_post_key'))) {
                    $amount = $mybb->get_input('amount', \MyBB::INPUT_INT);

                    if ($amount > 0) {
                        if ($amount <= \mint\getUserBalance($mybb->user['uid'])) {
                            $user = \get_user_by_username($mybb->get_input('user_name'));

                            if ($user) {
                                $result = BalanceTransfers::with($db)->execute(
                                    $mybb->user['uid'],
                                    $user['uid'],
                                    $amount
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
        if ($mybb->user['uid'] != 0) {
            \add_breadcrumb($lang->mint_hub, 'misc.php?action=economy');
            \add_breadcrumb($lang->mint_balance_operations);

            $itemsNum = BalanceOperations::with($db)->count('user_id = ' . (int)$mybb->user['uid']);

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

                $query = \mint\getUserBalanceOperations($mybb->user['uid'], $listManager->sql());

                while ($entry = $db->fetch_array($query)) {
                    $amount = \mint\getFormattedCurrency(abs($entry['value']));
                    $date = \my_date('normal', $entry['date']);

                    if ($entry['value'] < 0) {
                        $sign = '-';
                        $type = 'negative';
                    } else {
                        $sign = '+';
                        $type = 'positive';
                    }

                    $notes = [];

                    if ($entry['termination_point_name']) {
                        $notes[] = $lang->sprintf(
                            $lang->mint_balance_operations_termination_point,
                            \htmlspecialchars_uni($entry['termination_point_name'])
                        );
                    }

                    if ($entry['to_user_id'] && $entry['to_user_id'] != $mybb->user['uid']) {
                        $notes[] = $lang->sprintf(
                            $lang->mint_balance_operations_to_user,
                            \build_profile_link($entry['to_username'], $entry['to_user_id'])
                        );
                    }

                    if ($entry['from_user_id'] && $entry['from_user_id'] != $mybb->user['uid']) {
                        $notes[] = $lang->sprintf(
                            $lang->mint_balance_operations_from_user,
                            \build_profile_link($entry['from_username'], $entry['from_user_id'])
                        );
                    }

                    $notes = implode(' &middot; ', $notes);

                    eval('$entries .= "' . \mint\tpl('balance_operations_entry') . '";');
                }
            } else {
                $entries = \mint\getRenderedMessage($lang->mint_no_entries);
            }

            $pagination = $listManager->pagination();

            eval('$page = "' . \mint\tpl('balance_operations') . '";');
            \output_page($page);
        } else {
            \error_no_permission();
        }
    }
}
