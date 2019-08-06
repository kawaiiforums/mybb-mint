<?php

namespace mint;

// common
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

// blocks
function getRenderedRecentBalanceOperations($query, ?int $contextUserId = null): ?string
{
    global $db, $lang;

    $output = null;

    if ($db->num_rows($query) != 0) {
        $entries = \mint\getRenderedBalanceOperationEntries($query, $contextUserId);
    } else {
        $entries = \mint\getRenderedMessage($lang->mint_no_entries);
    }

    $url = 'misc.php?action=economy_balance_operations';

    if ($contextUserId) {
        $url .= '&amp;user_id=' . (int)$contextUserId;
    }

    eval('$output = "' . \mint\tpl('recent_balance_operations') . '";');

    return $output;
}

function getRenderedRewardSourceLegend(array $legendEntries, ?int $contextUserId = null): ?string
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

            if ($contextUserId) {
                $reward = \mint\getMultipliedRewardValue(
                    $reward,
                    \mint\getUserRewardMultiplier($contextUserId, $legendEntry)
                );
            }

            $value = \mint\getFormattedCurrency($reward);
        } else {
            $value = null;
        }

        eval('$entries .= "' . \mint\tpl('flowing_list_entry') . '";');
    }

    eval('$output = "' . \mint\tpl('reward_sources_legend') . '";');

    return $output;
}

function getRenderedInventory(array $items, string $type = 'standard', ?int $placeholders = null, ?string $selectInputName = 'item_selection'): ?string
{
    global $mybb;

    $entries = null;

    $inventoryType = $type;

    foreach ($items as $item) {
        $elementClass = 'mint__inventory__item';

        $classes = [
            $elementClass,
        ];

        $attributes = null;

        if (!empty($item['item_ownership_id'])) {
            $userItemId = (int)$item['item_ownership_id'];

            $attributes .= ' href="misc.php?action=economy_item_ownership&amp;id=' . $userItemId . '"';
        } else {
            $userItemId = null;
        }

        $title = \htmlspecialchars_uni($item['item_type_title']);

        if ($item['item_type_image']) {
            $imageUrl = $mybb->get_asset_url($item['item_type_image']);
        } else {
            $imageUrl = null;
        }

        if ($item['item_transaction_id']) {
            $classes[] = $elementClass . '--in-transaction';
        }

        if (isset($item['item_active']) && $item['item_active'] == 0) {
            $classes[] = $elementClass . '--deactivated';
        }

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

        $classes = implode(' ', $classes);

        if (!empty($item['stacked_amount']) && $item['stacked_amount'] > 1) {
            $stackedAmount = \my_number_format($item['stacked_amount']);
        } else {
            $stackedAmount = null;
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

function getRenderedInventoryPreview(array $items, ?int $contextUserId = null): ?string
{
    global $lang;

    $output = null;

    if (count($items) > 0) {
        $entries = \mint\getRenderedInventory($items, 'small');
    } else {
        $entries = \mint\getRenderedMessage($lang->mint_no_entries);
    }

    $url = 'misc.php?action=economy_user_inventory';

    if ($contextUserId) {
        $url .= '&amp;user_id=' . (int)$contextUserId;
    }

    eval('$output = "' . \mint\tpl('inventory_preview') . '";');

    return $output;
}

// block elements
function getRenderedItemCard(array $item, array $details = []): ?string
{
    global $mybb, $lang;

    require_once MYBB_ROOT."inc/class_parser.php";
    
    $parser = new \postParser;

    $itemTitle = \htmlspecialchars_uni($item['item_type_title']);
    $categoryTitle = \htmlspecialchars_uni($item['item_category_title']);

    $itemDescription = $parser->parse_message($item['item_type_description'], [
        'allow_html' => false,
        'allow_mycode' => true,
        'allow_imgcode' => true,
        'allow_videocode' => true,
        'filter_badwords' => true,
        'allow_smilies' => true,
    ]);

    $flags = null;

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

    if (!empty($item['item_transaction_id'])) {
        $classes[] = $elementClass . '--in-transaction';

        $flagType = 'in-transaction';
        $flagContent = $lang->mint_item_in_transaction;

        eval('$flags .= "' . \mint\tpl('flag') . '";');
    }

    if (isset($item['item_active']) && !$item['item_active']) {
        $classes[] = $elementClass . '--deactivated';

        $flagType = 'deactivated';
        $flagContent = $lang->mint_item_deactivated;

        eval('$flags .= "' . \mint\tpl('flag') . '";');
    }

    if ($item['item_type_stacked']) {
        $flagType = 'stacked';
        $flagContent = $lang->mint_item_stacked;

        eval('$flags .= "' . \mint\tpl('flag') . '";');
    }

    $classes = implode(' ', $classes);

    if (isset($item['stacked_amount'])) {
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

    $attributes = [];

    if (isset($item['user_username'])) {
        $profileLink = \build_profile_link($item['user_username'], $item['user_id']);

        $value = $lang->sprintf(
            $lang->mint_item_owned_by_since,
            $profileLink,
            \my_date('normal', $item['activation_date'])
        );

        $attributes[] = [
            'title' => $lang->mint_item_owner,
            'value' => $value,
        ];
    }

    if (isset($item['item_activation_date'])) {
        $attributes[] = [
            'title' => $lang->mint_item_activation_date,
            'value' => \my_date('normal', $item['item_activation_date']),
        ];
    }

    $attributes = array_merge($attributes, $details['attributes'] ?? []);

    $attributesHtml = null;

    foreach ($attributes as $attribute) {
        $title = $attribute['title'];
        $value = $attribute['value'];

        eval('$attributesHtml .= "' . \mint\tpl('flowing_list_entry') . '<br />";');
    }

    eval('$output = "' . \mint\tpl('item_card') . '";');

    return $output;
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
                $lang->mint_from_user,
                \build_profile_link($entry['from_username'], $entry['from_user_id'])
            );
        }

        if ($entry['to_user_id'] && $entry['to_user_id'] != $contextUserId) {
            $details[] = $lang->sprintf(
                $lang->mint_to_user,
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

function getRenderedTransactionEntries(array $entries): ?string
{
    global $db, $lang;

    $output = null;

    foreach ($entries as $entry) {
        $flags = null;

        $id = (int)$entry['id'];
        $url = 'misc.php?action=economy_item_transaction&amp;id=' . $id;

        if ($entry['token']) {
            $url .= '&token=' . urlencode($entry['token']);
        }

        $askDate = \my_date('normal', $entry['ask_date']);

        if ($entry['completed_date']) {
            $completedDate = \my_date('normal', $entry['completed_date']);
            $date = $completedDate;
        } else {
            $completedDate = null;
            $date = $askDate;
        }

        if ($entry['active'] == 1 && $entry['unlisted'] == true) {
            $flagType = 'unlisted';
            $flagContent = $lang->mint_item_transaction_unlisted;

            eval('$flags .= "' . \mint\tpl('flag') . '";');
        }

        $offerObjects = [];
        $askObjects = [];

        if (isset($entry['offered_items'])) {
            $offerObjects[] = $lang->sprintf(
                $lang->mint_items_count,
                count($entry['offered_items'])
            );
        }

        if ($entry['completed'] == 1 && isset($entry['bid_items'])) {
            $askItems = $entry['bid_items'];
        } elseif (isset($entry['ask_item_types'])) {
            $askItems = $entry['ask_item_types'];
        } else {
            $askItems = [];
        }

        if ($askItems) {
            $askObjects[] = $lang->sprintf(
                $lang->mint_items_count,
                count($askItems)
            );
        }

        if ($offerObjects) {
            $offerDetails = $lang->sprintf(
                $lang->mint_objects_offered_by_user,
                implode($lang->comma, $offerObjects),
                \build_profile_link($entry['ask_user_username'], $entry['ask_user_id'])
            );
        }

        if ($entry['ask_price']) {
            $askObjects[] = \mint\getFormattedCurrency(abs($entry['ask_price']));
        }

        $askDetails = null;

        if ($askObjects) {
            $askDetails .= $lang->sprintf(
                $lang->mint_for_objects,
                implode($lang->comma, $askObjects)
            );
        }

        if ($entry['bid_user_id']) {
            $askDetails .= ' ' . $lang->sprintf(
                $lang->mint_to_user,
                \build_profile_link($entry['bid_user_username'], $entry['bid_user_id'])
            );
        }


        if (!empty($entry['offered_items'])) {
            $previewItems = array_slice(
                $entry['offered_items'],
                0,
                \mint\getSettingValue('item_transaction_preview_items')
            );
            $offerPreview = \mint\getRenderedInventory($previewItems, 'preview');
        } else {
            $offerPreview = null;
        }

        if ($askItems) {
            $previewItems = array_slice(
                $askItems,
                0,
                \mint\getSettingValue('item_transaction_preview_items')
            );
            $askPreview = \mint\getRenderedInventory($previewItems, 'preview');
        } else {
            $askPreview = null;
        }

        eval('$output .= "' . \mint\tpl('item_transactions_entry') . '";');
    }

    return $output;
}

function getRenderedBalanceTopUserEntries($query): ?string
{
    global $db;

    $output = null;

    while ($entry = $db->fetch_array($query)) {
        $profileLink = \build_profile_link(
            \format_name($entry['username'], $entry['usergroup'], $entry['displaygroup']),
            $entry['uid']
        );
        $balance = \mint\getFormattedCurrency($entry['mint_balance'], false, true);

        eval('$output .= "' . \mint\tpl('balance_top_users_entry') . '";');
    }

    return $output;
}
