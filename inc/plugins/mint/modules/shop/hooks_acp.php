<?php

namespace mint\modules\shop\Hooks;

use mint\AcpEntityManagementController;
use mint\DbRepository\ItemTypes;
use mint\modules\shop\DbRepository\ShopItems;

function mint_activate(): void
{
    \mint\createTables([
        ShopItems::class,
    ]);
}

function mint_deactivate(): void
{
    global $mybb;

    if ($mybb->get_input('uninstall') == 1) {
        \mint\dropTables([
            ShopItems::class,
        ], true, true);
    }
}

function mint_admin_config_mint_tabs(array &$tabs): void
{
    $tabs[] = 'shop_items';
}

function mint_admin_config_mint_begin(): void
{
    global $mybb, $db, $lang;

    if ($mybb->input['action'] == 'shop_items') {
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
            'mint_item_types' => [
                'title',
            ],
        ]);
        $controller->addEntityOptions([
            'update' => [],
            'delete' => [],
        ]);

        $controller->run();
    }
}
