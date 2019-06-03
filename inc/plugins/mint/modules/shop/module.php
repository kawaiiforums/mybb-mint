<?php

namespace mint\modules\shop;

// core files
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/rendering.php';

// hook files
require_once __DIR__ . '/hooks_acp.php';
require_once __DIR__ . '/hooks_frontend.php';

// hooks
\mint\addHooksNamespace('mint\modules\shop\Hooks');

// init
\mint\loadModuleLanguageFile('shop', 'shop');

\mint\registerSettings([
    'shop_list_available_items_only' => [
        'title' => 'Shop: List Available Items Only',
        'description' => 'Choose whether to hide limited items that were sold out.',
        'optionscode' => 'yesno',
        'value' => '1',
    ],
]);

\mint\registerCurrencyTerminationPoints([
    'shop',
]);

\mint\registerItemTerminationPoints([
    'shop',
]);
