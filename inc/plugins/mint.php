<?php

// core files
require MYBB_ROOT . 'inc/plugins/mint/common.php';
require MYBB_ROOT . 'inc/plugins/mint/data.php';
require MYBB_ROOT . 'inc/plugins/mint/core.php';
require MYBB_ROOT . 'inc/plugins/mint/ui.php';

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
        'author'        => 'Tomasz \'Devilshakerz\' Mlynski',
        'authorsite'    => 'https://devilshakerz.com/',
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
            'mint_inventory_size_bonus' => 'integer NOT NULL DEFAULT 0',
            'mint_inventory_type_id' => 'integer NOT NULL DEFAULT 0',
        ],
    ]);

    \mint\createTables([
        \mint\DbRepository\BalanceTransfers::class,
        \mint\DbRepository\TerminationPoints::class,
        \mint\DbRepository\BalanceOperations::class,
        \mint\DbRepository\ContentEntityRewards::class,
        \mint\DbRepository\InventoryTypes::class,
        \mint\DbRepository\ItemCategories::class,
        \mint\DbRepository\ItemTypes::class,
        \mint\DbRepository\ShopItems::class,
        \mint\DbRepository\UserItems::class,
        \mint\DbRepository\ItemTransactions::class,
        \mint\DbRepository\UserItemTransactions::class,
    ]);

    // datacache
    require_once MYBB_ROOT . '/inc/functions_task.php';

    $cache->update('mint', [
        'version' => null,
        'modules' => [],
    ]);

    // tasks
    $new_task = [
        'title'       => 'Mint: Integrity',
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
            'mint_inventory_size_bonus',
            'mint_inventory_type_id',
        ],
    ]);

    \mint\dropTables([
        \mint\DbRepository\BalanceTransfers::class,
        \mint\DbRepository\TerminationPoints::class,
        \mint\DbRepository\BalanceOperations::class,
        \mint\DbRepository\ContentEntityRewards::class,
        \mint\DbRepository\InventoryTypes::class,
        \mint\DbRepository\ItemCategories::class,
        \mint\DbRepository\ItemTypes::class,
        \mint\DbRepository\ShopItems::class,
        \mint\DbRepository\UserItems::class,
        \mint\DbRepository\ItemTransactions::class,
        \mint\DbRepository\UserItemTransactions::class,
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
    \mint\registerTerminationPoints([
        'mint',
        'burn',
    ]);

    \mint\addNewTerminationPoints(
        \mint\getRegisteredTerminationPoints()
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
            'description' => '',
            'optionscode' => 'text',
            'value'       => '',
        ],
        'currency_suffix' => [
            'title'       => 'Currency Suffix',
            'description' => '',
            'optionscode' => 'text',
            'value'       => ' C',
        ],
        'mint_groups' => [
            'title'       => 'Mint Groups',
            'description' => 'Select which user groups are allowed to create currency on request.',
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
            'description' => 'Choose whether to automatically disable the <i>Manual Balance Operations</i> setting when data inconsistency is detected by the Mint Integrity task.',
            'optionscode' => 'yesno',
            'value'       => '1',
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
