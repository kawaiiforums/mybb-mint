<?php

namespace mint\modules\shop;

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
