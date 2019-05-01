<?php

namespace mint;

function getFormattedCurrency(int $value, bool $useHtml = false): string
{
    $formattedValue = my_number_format($value);

    if ($useHtml) {
        $formattedValue = '<span class="mint__currency__value">' . $formattedValue . '</span>';
    }

    $string = \mint\getSettingValue('currency_prefix') . $formattedValue . \mint\getSettingValue('currency_suffix');

    return $string;
}

function getRenderedMessage(string $content, string $type = 'note'): string
{
    eval('$message = "' . \mint\tpl('message') . '";');

    return $message;
}

function getRenderedBalanceOperationEntries($query): ?string
{
    global $mybb, $db, $lang;

    $output = null;

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

        $details = [];

        if ($entry['termination_point_name']) {
            $details[] = $lang->sprintf(
                $lang->mint_balance_operations_termination_point,
                \htmlspecialchars_uni($entry['termination_point_name'])
            );
        }

        if ($entry['to_user_id'] && $entry['to_user_id'] != $mybb->user['uid']) {
            $details[] = $lang->sprintf(
                $lang->mint_balance_operations_to_user,
                \build_profile_link($entry['to_username'], $entry['to_user_id'])
            );
        }

        if ($entry['from_user_id'] && $entry['from_user_id'] != $mybb->user['uid']) {
            $details[] = $lang->sprintf(
                $lang->mint_balance_operations_from_user,
                \build_profile_link($entry['from_username'], $entry['from_user_id'])
            );
        }

        $details = implode(' &middot; ', $details);

        $note = \htmlspecialchars_uni($entry['note']);

        eval('$output .= "' . \mint\tpl('balance_operations_entry') . '";');
    }

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

function getRenderedServiceLinks(array $links): ?string
{
    $output = null;

    foreach ($links as $serviceName => $service) {
        $url = \htmlspecialchars_uni($service['url']);
        $title = \htmlspecialchars_uni($service['title']);

        eval('$output .= "' . \mint\tpl('hub_service_link') . '";');
    }

    return $output;
}
