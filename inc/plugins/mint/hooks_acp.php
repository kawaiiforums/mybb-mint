<?php

namespace mint\Hooks;

use mint\AcpEntityManagementController;
use mint\DbRepository\{BalanceOperations, BalanceTransfers, ContentEntityRewards, InventoryTypes, ItemCategories, ItemTransactions, ItemTypes, ShopItems, UserItems};

function admin_load()
{
    global $mybb, $db, $lang, $run_module, $action_file, $page, $sub_tabs, $pageUrl;

    if ($run_module == 'config' && $action_file == 'mint') {
        $pageUrl = 'index.php?module=' . $run_module . '-' . $action_file;

        $lang->load('mint');

        $page->add_breadcrumb_item($lang->mint_admin, $pageUrl);

        $sub_tabs = [];

        $tabControllers = [
            'item_categories',
            'item_types',
            'shop_items',
            'inventory_types',
        ];

        foreach ($tabControllers as $tabName) {
            $sub_tabs[$tabName] = [
                'link'        => $pageUrl . '&amp;action=' . $tabName,
                'title'       => $lang->{'mint_admin_' . $tabName},
                'description' => $lang->{'mint_admin_' . $tabName . '_description'},
            ];
        }

        if ($mybb->input['action'] == 'item_categories' || empty($mybb->input['action'])) {
            $controller = new AcpEntityManagementController('item_categories', ItemCategories::class);

            $controller->addEntityOptions([
                'update' => [],
                'delete' => [],
            ]);

            $controller->run();
        } elseif ($mybb->input['action'] == 'item_types') {
            $controller = new AcpEntityManagementController('item_types', ItemTypes::class);

            $controller->setColumns([
                'id' => [],
                'category_id' => [
                    'listed' => false,
                    'formElement' => function (\Form $form, array $entity) use ($db) {
                        return $form->generate_select_box(
                            'category_id',
                            \mint\queryResultAsArray(ItemCategories::with($db)->get(), 'id', 'title'),
                            $entity['category_id'] ?? 0
                        );
                    },
                ],
                'category' => [
                    'customizable' => false,
                    'dataColumn' => 'category_title',
                ],
                'title' => [],
                'image' => [],
                'stacked' => [
                    'formElement' => function (\Form $form, array $entity) use ($db) {
                        return $form->generate_yes_no_radio(
                            'stacked',
                            $entity['stacked'] ?? 0
                        );
                    },
                    'presenter' => function (string $value) use ($lang) {
                        return $value ? $lang->yes : $lang->no;
                    },
                ],
            ]);
            $controller->addForeignKeyData([
                'mint_item_categories' => [
                    'title',
                ],
            ]);
            $controller->addEntityOptions([
                'update' => [],
                'delete' => [],
            ]);

            $controller->run();
        } elseif ($mybb->input['action'] == 'shop_items') {
            $controller = new AcpEntityManagementController('shop_items', ShopItems::class);

            $controller->setColumns([
                'id' => [],
                'item_type' => [
                    'dataColumn' => 'item_type_title',
                    'formElement' => function (\Form $form, array $entity) use ($db) {
                        return $form->generate_select_box(
                            'item_type_id',
                            \mint\queryResultAsArray(ItemTypes::with($db)->get(), 'id', 'title'),
                            $entity['item_type_id'] ?? 0
                        );
                    },
                ],
                'ask_price' => [
                    'formMethod' => 'generate_numeric_field',
                ],
            ]);
            $controller->addForeignKeyData([
                'mint_shop_items' => [
                    'title',
                ],
            ]);
            $controller->addEntityOptions([
                'update' => [],
                'delete' => [],
            ]);

            $controller->run();
        } elseif ($mybb->input['action'] == 'inventory_types') {
            $controller = new AcpEntityManagementController('inventory_types', InventoryTypes::class);

            $controller->setColumns([
                'id' => [],
                'title' => [],
                'size' => [
                    'formMethod' => 'generate_numeric_field',
                ],
            ]);
            $controller->addEntityOptions([
                'update' => [],
                'delete' => [],
            ]);

            $controller->run();
        } else {
            exit;
        }


    } elseif ($run_module == 'tools' && $action_file == 'mint_logs') {
        $pageUrl = 'index.php?module=' . $run_module . '-' . $action_file;

        $lang->load('mint');

        $page->add_breadcrumb_item($lang->mint_admin_logs, $pageUrl);

        $sub_tabs = [];

        $tabControllers = [
            'balance_operations',
            'balance_transfers',
        ];

        foreach ($tabControllers as $tabName) {
            $sub_tabs[$tabName] = [
                'link'        => $pageUrl . '&amp;action=' . $tabName,
                'title'       => $lang->{'mint_admin_' . $tabName},
                'description' => $lang->{'mint_admin_' . $tabName . '_description'},
            ];
        }

        if ($mybb->input['action'] == 'balance_operations' || empty($mybb->input['action'])) {
            $controller = new AcpEntityManagementController('balance_operations', BalanceOperations::class);

            $controller->setColumns([
                'id' => [],
                'date' => [
                    'presenter' => function (string $value) {
                        return \my_date('normal', $value);
                    },
                ],
                'user' => [
                    'dataColumn' => 'user_username',
                    'presenter' => function (?string $value, array $row) {
                        if ($value !== null) {
                            return \build_profile_link($value, $row['user_id']);
                        } else {
                            return null;
                        }
                    },
                ],
                'value' => [],
                'result_balance' => [],
                'transfer_id' => [
                    'presenter' => function (?int $value) {
                        if ($value !== null) {
                            return '#' . (int)$value;
                        } else {
                            return null;
                        }
                    },
                ],
                'termination_point' => [
                    'dataColumn' => 'termination_point_name',
                    'presenter' => function (?string $value) {
                        if ($value !== null) {
                            return '<code>' . \htmlspecialchars_uni($value) . '</code>';
                        } else {
                            return null;
                        }
                    },
                ],
            ]);
            $controller->addForeignKeyData([
                'users' => [
                    'username',
                ],
                'mint_termination_points' => [
                    'name'
                ],
            ]);
            $controller->insertAllowed(false);
            $controller->listManagerOptions([
                'order_dir' => 'desc',
            ]);

            $controller->run();
        }
    }
}

function admin_config_action_handler(array &$actions): void
{
    $actions['mint'] = [
        'active' => 'mint',
        'file' => 'mint',
    ];
}

function admin_config_menu(array &$sub_menu): void
{
    global $lang;

    $lang->load('mint');

    $sub_menu[] = [
        'id' => 'mint',
        'link' => 'index.php?module=config-mint',
        'title' => $lang->mint_admin,
    ];
}

function admin_tools_action_handler(array &$actions): void
{
    $actions['mint_logs'] = [
        'active' => 'mint_logs',
        'file' => 'mint_logs',
    ];
}

function admin_tools_menu_logs(array &$sub_menu): void
{
    global $lang;

    $lang->load('mint');

    $sub_menu[] = [
        'id' => 'mint_logs',
        'link' => 'index.php?module=tools-mint_logs',
        'title' => $lang->mint_admin_logs,
    ];
}

function admin_user_users_merge_commit(): void
{
    global $db, $source_user, $destination_user;

    $dbRepositories = [
        [
            'class' => BalanceOperations::class,
            'columns' => [
                'user_id',
            ],
        ],
        [
            'class' => BalanceTransfers::class,
            'columns' => [
                'from_user_id',
                'to_user_id',
            ],
        ],
        [
            'class' => ContentEntityRewards::class,
            'columns' => [
                'user_id',
            ],
        ],
        [
            'class' => ItemTransactions::class,
            'columns' => [
                'ask_user_id',
                'bid_user_id',
            ],
        ],
        [
            'class' => UserItems::class,
            'columns' => [
                'user_id',
            ],
        ],
    ];

    foreach ($dbRepositories as $repository) {
        foreach ($repository['columns'] as $columnName) {
            $updates = [];
            $updates[$columnName] = $destination_user['uid'];

            $repository['class']::with($db)->update($updates, $columnName . ' = ' . (int)$source_user['uid']);
        }
    }
}