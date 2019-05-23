<?php

// core files
require MYBB_ROOT . 'inc/plugins/mint/common.php';
require MYBB_ROOT . 'inc/plugins/mint/data.php';
require MYBB_ROOT . 'inc/plugins/mint/core.php';
require MYBB_ROOT . 'inc/plugins/mint/rendering.php';

// hook files
require MYBB_ROOT . 'inc/plugins/mint/hooks_frontend.php';
require MYBB_ROOT . 'inc/plugins/mint/hooks_acp.php';

// autoloading
spl_autoload_register(function ($path) {
    $prefix = 'mint\\';
    $baseDir = MYBB_ROOT . 'inc/plugins/mint/';

    if (strpos($path, $prefix) === 0) {
        $className = str_replace('\\', '/', substr($path, strlen($prefix)));
        $file = $baseDir . $className . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
});

// init
define('mint\DEVELOPMENT_MODE', 1);

\mint\loadModules(
    \mint\getModuleNames()
);

\mint\resolveRegisteredRewardSources();

// hooks
\mint\addHooksNamespace('mint\Hooks');

function mint_info()
{
    global $lang;

    $lang->load('mint');

    return [
        'name'          => 'Mint',
        'description'   => $lang->mint_description,
        'website'       => '',
        'author'        => 'kawaiiforums',
        'authorsite'    => 'https://github.com/kawaiiforums',
        'version'       => 'dev',
        'codename'      => 'mint',
        'compatibility' => '18*',
    ];
}

function mint_install()
{
    global $db, $cache;

    \mint\loadPluginLibrary();

    // database
    \mint\createColumns([
        'users' => [
            'mint_balance' => 'integer NOT NULL DEFAULT 0',
            'mint_inventory_type_id' => 'integer',
            'mint_inventory_slots_bonus' => 'integer NOT NULL DEFAULT 0',
            'mint_inventory_slots_occupied' => 'integer NOT NULL DEFAULT 0',
        ],
    ], true);

    \mint\createTables([
        \mint\DbRepository\BalanceTransfers::class,
        \mint\DbRepository\CurrencyTerminationPoints::class,
        \mint\DbRepository\BalanceOperations::class,
        \mint\DbRepository\ContentEntityRewards::class,

        \mint\DbRepository\InventoryTypes::class,
        \mint\DbRepository\ItemTerminationPoints::class,
        \mint\DbRepository\ItemCategories::class,
        \mint\DbRepository\ItemTypes::class,
        \mint\DbRepository\Items::class,
        \mint\DbRepository\ItemOwnerships::class,
        \mint\DbRepository\ShopItems::class,
        \mint\DbRepository\ItemTransactions::class,
        \mint\DbRepository\ItemTransactionItems::class,
    ]);

    // datacache
    require_once MYBB_ROOT . '/inc/functions_task.php';

    $cache->update('mint', [
        'version' => null,
        'modules' => [],
    ]);

    // tasks
    $new_task = [
        'title'       => 'Mint: Integrity Check',
        'description' => 'Performs data integrity verification for the Mint extension.',
        'file'        => 'mint_integrity',
        'minute'      => '0',
        'hour'        => '0',
        'day'         => '*',
        'month'       => '*',
        'weekday'     => '*',
        'enabled'     => '1',
        'logging'     => '1',
    ];

    $new_task['nextrun'] = fetch_next_run($new_task);

    $db->insert_query('tasks', $new_task);
    $cache->update_tasks();
}

function mint_uninstall()
{
    global $db, $cache, $PL;

    \mint\loadPluginLibrary();

    // database
    if ($db->type == 'sqlite') {
        $db->close_cursors();
    }

    \mint\dropColumns([
        'users' => [
            'mint_balance',
            'mint_inventory_type_id',
            'mint_inventory_slots_bonus',
            'mint_inventory_slots_occupied',
        ],
    ]);

    \mint\dropTables([
        \mint\DbRepository\BalanceTransfers::class,
        \mint\DbRepository\CurrencyTerminationPoints::class,
        \mint\DbRepository\BalanceOperations::class,
        \mint\DbRepository\ContentEntityRewards::class,

        \mint\DbRepository\InventoryTypes::class,
        \mint\DbRepository\ItemTerminationPoints::class,
        \mint\DbRepository\ItemCategories::class,
        \mint\DbRepository\ItemTypes::class,
        \mint\DbRepository\Items::class,
        \mint\DbRepository\ItemOwnerships::class,
        \mint\DbRepository\ShopItems::class,
        \mint\DbRepository\ItemTransactions::class,
        \mint\DbRepository\ItemTransactionItems::class,
    ], true, true);

    // settings
    $PL->settings_delete('mint', true);

    // datacache
    $cache->delete('mint');

    // tasks
    $db->delete_query('tasks', "file='mint_integrity'");
    $cache->update_tasks();
}

function mint_is_installed()
{
    global $db;

    // manual check to avoid caching issues
    $query = $db->simple_select('settinggroups', 'gid', "name='mint'");

    return (bool)$db->num_rows($query);
}

function mint_activate()
{
    global $PL;

    \mint\loadPluginLibrary();

    $moduleNames = \mint\getModuleNames(false);

    \mint\loadModules($moduleNames);

    \mint\resolveRegisteredRewardSources();

    // termination points
    \mint\registerCurrencyTerminationPoints([
        'mint',
        'burn',
    ]);

    \mint\registerItemTerminationPoints([
        'forge',
        'melt',
        'discard',
    ]);

    \mint\addNewTerminationPoints(
        \mint\getRegisteredCurrencyTerminationPoints(),
        \mint\DbRepository\CurrencyTerminationPoints::class
    );

    \mint\addNewTerminationPoints(
        \mint\getRegisteredItemTerminationPoints(),
        \mint\DbRepository\ItemTerminationPoints::class
    );

    // settings
    $settings = [
        'currency_name' => [
            'title'       => 'Currency Name',
            'description' => '',
            'optionscode' => 'text',
            'value'       => 'Coin',
        ],
        'currency_prefix' => [
            'title'       => 'Currency Prefix',
            'description' => 'Supports HTML.',
            'optionscode' => 'text',
            'value'       => '',
        ],
        'currency_suffix' => [
            'title'       => 'Currency Suffix',
            'description' => 'Supports HTML.',
            'optionscode' => 'text',
            'value'       => ' C',
        ],
        'management_groups' => [
            'title'       => 'Management Groups',
            'description' => 'Select which user groups are allowed to manage individual users\' items.',
            'optionscode' => 'groupselect',
            'value'       => '4',
        ],
        'mint_groups' => [
            'title'       => 'Mint Groups',
            'description' => 'Select which user groups are allowed to create and remove currency on request.',
            'optionscode' => 'groupselect',
            'value'       => '4',
        ],
        'forge_groups' => [
            'title'       => 'Forge Groups',
            'description' => 'Select which user groups are allowed to create and remove items on request.',
            'optionscode' => 'groupselect',
            'value'       => '4',
        ],
        'manual_balance_operations' => [
            'title'       => 'Allow Manual Balance Operations',
            'description' => 'Choose whether to allow manual balance operations like transfers and transactions.',
            'optionscode' => 'yesno',
            'value'       => '1',
        ],
        'disable_manual_balance_operations_on_inconsistency' => [
            'title'       => 'Disable Manual Balance Operations on Inconsistency Detection',
            'description' => 'Choose whether to automatically disable the <i>Allow Manual Balance Operations</i> setting when inconsistency of stored data is detected by the Mint Integrity task.',
            'optionscode' => 'yesno',
            'value'       => '1',
        ],
        'private_balance_transfer_groups' => [
            'title'       => 'Private Balance Transfer Groups',
            'description' => 'Select which user groups are allowed to perform non-public balance transfers.',
            'optionscode' => 'groupselect',
            'value'       => '-1',
        ],
        'private_balance_transfer_by_default' => [
            'title'       => 'Private Balance Transfers by Default',
            'description' => 'Choose whether Balance Transfers should be assumed non-public (when possible).',
            'optionscode' => 'yesno',
            'value'       => '1',
        ],
        'default_inventory_type_id' => [
            'title'       => 'Default Inventory Type ID',
            'description' => 'Provide the ID of a fallback Inventory Type that will be assigned to users by default (if any).',
            'optionscode' => 'numeric',
            'value'       => '1',
        ],
        'recent_balance_operations_entries' => [
            'title'       => 'Recent Balance Operations to Display',
            'description' => 'Choose how many users\' recent balance operations should be shown on the Hub page.',
            'optionscode' => 'numeric',
            'value'       => '3',
        ],
        'recent_item_transactions_entries' => [
            'title'       => 'Recent Item Transactions to Display',
            'description' => 'Choose how many recently completed item transactions should be shown on the Hub page.',
            'optionscode' => 'numeric',
            'value'       => '3',
        ],
        'inventory_preview_entries' => [
            'title'       => 'Inventory Preview Items to Display',
            'description' => 'Choose how many items should be shown in Inventory preview blocks.',
            'optionscode' => 'numeric',
            'value'       => '10',
        ],
        'top_users_entries' => [
            'title'       => 'Top Users by Balance to Display',
            'description' => 'Choose how many users should be listed in the <i>Top Users by Balance</i> ranking.',
            'optionscode' => 'numeric',
            'value'       => '3',
        ],
    ];

    $settings = array_merge($settings, \mint\getRegisteredSettings());

    $PL->settings(
        'mint',
        'Mint',
        'Settings for the Mint extension.',
        $settings
    );

    // templates
    $PL->templates(
        'mint',
        'Mint',
        \mint\getFilesContentInDirectory(MYBB_ROOT . 'inc/plugins/mint/templates', '.tpl')
    );

    // stylesheets
    $stylesheets = [
        'mint' => [
            'attached_to' => [],
        ],
    ];

    foreach ($stylesheets as $stylesheetName => $stylesheet) {
        $PL->stylesheet(
            $stylesheetName,
            file_get_contents(MYBB_ROOT . 'inc/plugins/mint/stylesheets/' . $stylesheetName . '.css'),
            $stylesheet['attached_to']
        );
    }

    // datacache
    \mint\updateCache([
        'version' => mint_info()['version'],
        'modules' => $moduleNames,
    ]);
}

function mint_deactivate()
{
    global $PL;

    \mint\loadPluginLibrary();

    // templates
    $PL->templates_delete('mint', true);

    // stylesheets
    $PL->stylesheet_delete('mint', true);
}
