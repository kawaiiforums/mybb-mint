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
