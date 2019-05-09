<?php

namespace mint\Hooks;

use mint\AcpEntityManagementController;
use mint\DbRepository\{BalanceOperations, BalanceTransfers, ContentEntityRewards, InventoryTypes, ItemCategories, ItemTransactions, ItemTypes, ShopItems, Items};

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
            $itemCategories = \mint\queryResultAsArray(ItemCategories::with($db)->get(), 'id', 'title');

            $controller = new AcpEntityManagementController('item_types', ItemTypes::class);

            $controller->setColumns([
                'id' => [],
                'category_id' => [
                    'listed' => false,
                    'formElement' => function (\Form $form, array $entity) use ($itemCategories) {
                        return $form->generate_select_box(
                            'category_id',
                            $itemCategories,
                            $entity['category_id'] ?? 0
                        );
                    },
                    'validator' => function (?string $value) use ($lang, $itemCategories): array {
                        $errors = [];

                        if (!array_key_exists($value, $itemCategories)) {
                            $errors['item_category_invalid'] = [];
                        }

                        return $errors;
                    },
                ],
                'category' => [
                    'customizable' => false,
                    'dataColumn' => 'category_title',
                ],
                'name' => [],
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
            $itemTypes = \mint\queryResultAsArray(ItemTypes::with($db)->get(), 'id', 'title');

            $controller = new AcpEntityManagementController('shop_items', ShopItems::class);

            $controller->setColumns([
                'id' => [],
                'item_type_id' => [
                    'listed' => false,
                    'formElement' => function (\Form $form, array $entity) use ($itemTypes) {
                        return $form->generate_select_box(
                            'item_type_id',
                            $itemTypes,
                            $entity['item_type_id'] ?? 0
                        );
                    },
                    'validator' => function (?string $value) use ($lang, $itemTypes): array {
                        $errors = [];

                        if (!array_key_exists($value, $itemTypes)) {
                            $errors['item_type_invalid'] = [];
                        }

                        return $errors;
                    },
                ],
                'item_type' => [
                    'customizable' => false,
                    'dataColumn' => 'item_type_title',
                ],
                'ask_price' => [
                    'formMethod' => 'generate_numeric_field',
                ],
                'sales_limit' => [
                    'formMethod' => 'generate_numeric_field',
                ],
                'times_purchased' => [
                    'customizable' => false,
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
                'slots' => [
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
                    'filter' => true,
                    'filterConditionColumn' => 't2.username',
                ],
                'value' => [
                    'formMethod' => 'generate_numeric_field',
                    'filter' => true,
                ],
                'result_balance' => [
                    'formMethod' => 'generate_numeric_field',
                    'filter' => true,
                ],
                'balance_transfer_id' => [
                    'presenter' => function (?int $value) {
                        if ($value !== null) {
                            return '#' . (int)$value;
                        } else {
                            return null;
                        }
                    },
                    'formMethod' => 'generate_numeric_field',
                    'filter' => true,
                ],
                'termination_point' => [
                    'dataColumn' => 'currency_termination_point_name',
                    'presenter' => function (?string $value) {
                        if ($value !== null) {
                            return '<code>' . \htmlspecialchars_uni($value) . '</code>';
                        } else {
                            return null;
                        }
                    },
                    'filter' => true,
                    'filterConditionColumn' => 't3.name',
                ],
            ]);
            $controller->addForeignKeyData([
                'users' => [
                    'username',
                ],
                'mint_currency_termination_points' => [
                    'name'
                ],
            ]);
            $controller->insertAllowed(false);
            $controller->listManagerOptions([
                'order_dir' => 'desc',
            ]);

            $controller->run();
        } elseif ($mybb->input['action'] == 'balance_transfers') {
            $controller = new AcpEntityManagementController('balance_transfers', BalanceTransfers::class);

            $controller->setColumns([
                'id' => [],
                'date' => [
                    'presenter' => function (string $value) {
                        return \my_date('normal', $value);
                    },
                ],
                'from_user' => [
                    'dataColumn' => 'from_user_username',
                    'presenter' => function (?string $value, array $row) {
                        if ($value !== null) {
                            return \build_profile_link($value, $row['from_user_id']);
                        } else {
                            return null;
                        }
                    },
                    'filter' => true,
                    'filterConditionColumn' => 't2.username',
                ],
                'to_user' => [
                    'dataColumn' => 'to_user_username',
                    'presenter' => function (?string $value, array $row) {
                        if ($value !== null) {
                            return \build_profile_link($value, $row['to_user_id']);
                        } else {
                            return null;
                        }
                    },
                    'filter' => true,
                    'filterConditionColumn' => 't3.username',
                ],
                'value' => [
                    'formMethod' => 'generate_numeric_field',
                    'filter' => true,
                ],
                'note' => [],
                'private' => [
                    'presenter' => function (string $value) use ($lang) {
                        return $value ? $lang->yes : $lang->no;
                    },
                    'formElement' => function (\Form $form, array $entity, string $name) use ($lang) {
                        $output = null;

                        $output .= $form->generate_radio_button($name, '1', $lang->yes);
                        $output .= '<br />';
                        $output .= $form->generate_radio_button($name, '0', $lang->no);

                        return $output;
                    },
                    'filter' => true,
                ],
            ]);
            $controller->addForeignKeyData([
                'users' => [
                    'username',
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
            'class' => Items::class,
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
