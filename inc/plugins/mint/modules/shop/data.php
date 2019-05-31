<?php

namespace mint\modules\shop;

use mint\modules\shop\DbRepository\ShopItems;

function getShopItemById(int $id, bool $forUpdate = false)
{
    global $db;

    $conditions = 'id = ' . (int)$id;

    if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
        $conditions .= ' FOR UPDATE';
    }

    $query = $db->simple_select('mint_shop_items', '*', $conditions);

    if ($db->num_rows($query) == 1) {
        return $db->fetch_array($query);
    } else {
        return null;
    }
}

function getShopItemDetailsById(int $id): ?array
{
    global $db;

    $query = ShopItems::with($db)->get(
        '*',
        'WHERE t1.id = ' . (int)$id,
        [
            'mint_item_types' => [
                'title',
                'description',
                'image',
                'stacked',
                'discardable',
                'transferable',
            ],
        ]
    );

    if ($db->num_rows($query) == 1) {
        return $db->fetch_array($query);
    } else {
        return null;
    }
}

function getShopItemsByCategory(int $itemCategoryId, bool $availableOnly = false)
{
    global $db;

    $conditions = 'WHERE item_category_id = ' . (int)$itemCategoryId;

    if ($availableOnly) {
        $conditions .= ' AND (sales_limit = 0 OR sales_limit > times_purchased)';
    }

    $query = ShopItems::with($db)->get(
        '*',
        $conditions,
        [
            'mint_item_types' => [
                'title',
                'image',
                'stacked',
                'discardable',
                'transferable',
            ],
        ]
    );

    return \mint\queryResultAsArray($query);
}

function getShopItemCategories(bool $notEmptyOnly = false, bool $availableOnly = false): array
{
    global $db;

    $itemCountOn = null;
    $conditions = null;

    if ($notEmptyOnly || $availableOnly) {
        $conditions .= 'HAVING COUNT(si.id) != 0';
    }

    if ($availableOnly) {
        $itemCountOn .= 'AND (si.sales_limit = 0 OR si.sales_limit > si.times_purchased)';
    }

    $query = $db->query("
        SELECT
            ic.id, ic.title, ic.image, COUNT(si.id) AS shop_items_count
            FROM
                " . TABLE_PREFIX . "mint_item_categories ic
                LEFT JOIN " . TABLE_PREFIX . "mint_item_types iTy ON ic.id = iTy.item_category_id 
                LEFT JOIN " . TABLE_PREFIX . "mint_shop_items si ON iTy.id = si.item_type_id {$itemCountOn}
            GROUP BY ic.id, ic.title
            {$conditions}
    ");

    return \mint\queryResultAsArray($query);
}
