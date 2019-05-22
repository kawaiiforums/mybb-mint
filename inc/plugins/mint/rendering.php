<?php

namespace mint;

function getFormattedCurrency(int $value, bool $simple = false, bool $useHtml = false): string
{
    global $lang;

    $formattedValue = my_number_format($value);

    if ($simple) {
        $string = $lang->sprintf(
            $lang->mint_currency_simple,
            $formattedValue,
            \mint\getSettingValue('currency_name')
        );
    } else {
        if ($useHtml) {
            $formattedValue = '<span class="mint__currency__value">' . $formattedValue . '</span>';
        }

        $string = \mint\getSettingValue('currency_prefix') . $formattedValue . \mint\getSettingValue('currency_suffix');
    }

    return $string;
}

function getRenderedMessage(string $content, string $type = 'note'): string
{
    eval('$message = "' . \mint\tpl('message') . '";');

    return $message;
}

function getRenderedBalanceOperationEntries($query, ?int $contextUserId = null, bool $showSigns = true): ?string
{
    global $mybb, $db, $lang;

    $output = null;

    while ($entry = $db->fetch_array($query)) {
        $amount = \mint\getFormattedCurrency(abs($entry['value']));
        $date = \my_date('normal', $entry['date']);
        $note = \htmlspecialchars_uni($entry['note']);

        if ($entry['value'] < 0) {
            $sign = '-';
            $type = 'negative';
        } else {
            $sign = '+';
            $type = 'positive';
        }

        if (!$showSigns) {
            $sign = null;
        }

        $details = [];

        if ($entry['currency_termination_point_name']) {
            $details[] = $lang->sprintf(
                $lang->mint_balance_operations_termination_point,
                \htmlspecialchars_uni($entry['currency_termination_point_name'])
            );
        }

        if ($entry['from_user_id'] && $entry['from_user_id'] != $contextUserId) {
            $details[] = $lang->sprintf(
                $lang->mint_balance_operations_from_user,
                \build_profile_link($entry['from_username'], $entry['from_user_id'])
            );
        }

        if ($entry['to_user_id'] && $entry['to_user_id'] != $contextUserId) {
            $details[] = $lang->sprintf(
                $lang->mint_balance_operations_to_user,
                \build_profile_link($entry['to_username'], $entry['to_user_id'])
            );
        }

        if ($entry['item_transaction_id']) {
            $details[] = '<a href="misc.php?action=economy_item_transaction&amp;id=' . $entry['item_transaction_id'] . '">' . $lang->mint_balance_operations_item_transaction . '</a>';
        }

        $details = implode(' &middot; ', $details);

        $flags = null;

        if ($entry['private'] == true) {
            $flagType = 'private';
            $flagContent = $lang->mint_balance_transfer_private;

            eval('$flags .= "' . \mint\tpl('flag') . '";');
        }

        if (!empty($entry['handler'])) {
            $flagType = 'automated';
            $flagContent = $lang->mint_balance_transfer_automated;

            eval('$flags .= "' . \mint\tpl('flag') . '";');
        }

        eval('$output .= "' . \mint\tpl('balance_operations_entry') . '";');
    }

    return $output;
}

function getRenderedBalanceTopUserEntries($query): ?string
{
    global $db, $lang;

    $entries = null;

    while ($entry = $db->fetch_array($query)) {
        $profileLink = \build_profile_link(
            \format_name($entry['username'], $entry['usergroup'], $entry['displaygroup']),
            $entry['uid']
        );
        $balance = \mint\getFormattedCurrency($entry['mint_balance'], false, true);

        eval('$entries .= "' . \mint\tpl('balance_top_users_entry') . '";');
    }

    eval('$output = "' . \mint\tpl('balance_top_users') . '";');

    return $output;
}

function getRenderedRewardSourceLegend(array $legendEntries): ?string
{
    global $lang;

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

        eval('$entries .= "' . \mint\tpl('reward_sources_legend_entry') . '";');
    }

    eval('$output = "' . \mint\tpl('reward_sources_legend') . '";');

    return $output;
}

function getRenderedUserActiveTransactions($query): ?string
{
    global $db, $lang;

    $entries = null;

    while ($entry = $db->fetch_array($query)) {
        $id = (int)$entry['id'];
        $url = 'misc.php?action=economy_item_transaction&amp;id=' . $id;
        $askDate = \my_date('normal', $entry['ask_date']);

        eval('$entries .= "' . \mint\tpl('user_active_transactions_entry') . '";');
    }

    eval('$output = "' . \mint\tpl('user_active_transactions') . '";');

    return $output;
}

function getRenderedServiceLinks(array $links, string $sectionName): ?string
{
    global $lang;

    $output = null;

    foreach ($links as $serviceName => $service) {
        $url = \htmlspecialchars_uni($service['url']);
        $title = $lang->{'mint_service_' . $sectionName . '_' . $serviceName};

        eval('$output .= "' . \mint\tpl('hub_service_link') . '";');
    }

    return $output;
}

function getRenderedActionLinks(array $links): ?string
{
    $output = null;

    foreach ($links as $linkName => $link) {
        $title = \htmlspecialchars_uni($link['title']);

        if (isset($link['url'])) {
            $url = \htmlspecialchars_uni($link['url']);

            eval('$output .= "' . \mint\tpl('action_link') . '";');
        } else {
            $name = \htmlspecialchars_uni($linkName);

            eval('$output .= "' . \mint\tpl('action_link_button') . '";');
        }
    }

    return $output;
}

function getRenderedInventory(array $items, string $type = 'standard', ?int $placeholders = null): ?string
{
    global $mybb;

    $entries = null;

    $inventoryType = $type;

    foreach ($items as $item) {
        $userItemId = $item['item_ownership_id'];
        $title = \htmlspecialchars_uni($item['item_type_title']);

        $elementClass = 'mint__inventory__item';

        $classes = [
            $elementClass,
        ];

        if ($item['item_type_stacked']) {
            $classes[] = $elementClass . '--stacked';
        } else {
            $classes[] = $elementClass . '--standard';
        }

        if (!$item['item_type_discardable']) {
            $classes[] = $elementClass . '--non-discardable';
        }

        if (!$item['item_type_transferable']) {
            $classes[] = $elementClass . '--non-transferable';
        }

        if ($item['item_transaction_id']) {
            $classes[] = $elementClass . '--in-transaction';
        }

        $classes = implode(' ', $classes);

        if ($item['stacked_amount']) {
            $stackedAmount = \my_number_format($item['stacked_amount']);
        } else {
            $stackedAmount = null;
        }

        if ($item['item_type_image']) {
            $imageUrl = $mybb->get_asset_url($item['item_type_image']);
        } else {
            $imageUrl = null;
        }

        if ($type == 'transaction-select' && $item['item_type_transferable'] && !$item['item_transaction_id']) {
            if ($item['item_type_stacked']) {
                $max = (int)$item['stacked_amount'];
                eval('$fields = "' . \mint\tpl('inventory_entry_options_number') . '";');
            } else {
                eval('$fields = "' . \mint\tpl('inventory_entry_options_checkbox') . '";');
            }

            eval('$options = "' . \mint\tpl('inventory_entry_options') . '";');
        } else {
            $options = null;
        }

        eval('$entries .= "' . \mint\tpl('inventory_entry') . '";');
    }

    if ($placeholders !== null) {
        $slotsAvailable = $placeholders - count($items);

        for ($i = 1; $i <= $slotsAvailable; $i++) {
            eval('$entries .= "' . \mint\tpl('inventory_slot') . '";');
        }
    }

    eval('$output = "' . \mint\tpl('inventory') . '";');

    return $output;
}

function getRenderedItemCard(array $item): ?string
{
    global $mybb, $lang;

    $title = \htmlspecialchars_uni($item['item_type_title']);
    $description = \htmlspecialchars_uni($item['item_type_description']);

    $categoryTitle = \htmlspecialchars_uni($item['item_category_title']);

    $flags = null;

    $elementClass = 'mint__inventory__item';

    $classes = [
        $elementClass,
    ];

    if ($item['item_type_stacked']) {
        $classes[] = $elementClass . '--stacked';
    } else {
        $classes[] = $elementClass . '--standard';

        if ($item['item_transaction_id']) {
            $classes[] = $elementClass . '--in-transaction';

            $flagType = 'in-transaction';
            $flagContent = $lang->mint_item_in_transaction;

            eval('$flags .= "' . \mint\tpl('flag') . '";');
        }
    }

    if (!$item['item_type_discardable']) {
        $classes[] = $elementClass . '--non-discardable';

        $flagType = 'non-discardable';
        $flagContent = $lang->mint_item_non_discardable;

        eval('$flags .= "' . \mint\tpl('flag') . '";');
    }

    if (!$item['item_type_transferable']) {
        $classes[] = $elementClass . '--non-transferable';

        $flagType = 'non-transferable';
        $flagContent = $lang->mint_item_non_transferable;

        eval('$flags .= "' . \mint\tpl('flag') . '";');
    }

    $classes = implode(' ', $classes);

    if ($item['stacked_amount']) {
        $stackedAmount = \my_number_format($item['stacked_amount']);
        $stackedAmountText = $lang->sprintf(
            $lang->mint_items_in_stack,
            $stackedAmount
        );
    } else {
        $stackedAmount = null;
        $stackedAmountText = null;
    }

    if ($item['item_type_image']) {
        $imageUrl = $mybb->get_asset_url($item['item_type_image']);
    } else {
        $imageUrl = null;
    }

    $profileLink = \build_profile_link($item['user_username'], $item['user_id']);

    $ownedBy = $lang->sprintf(
        $lang->mint_item_owned_by_since,
        $profileLink,
        \my_date('normal', $item['activation_date'])
    );

    $itemActivationDate = \my_date('normal', $item['item_activation_date']);

    eval('$output = "' . \mint\tpl('item_card') . '";');

    return $output;
}
