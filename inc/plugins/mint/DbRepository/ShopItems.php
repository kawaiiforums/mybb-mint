<?php

namespace mint\DbRepository;

class ShopItems extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_shop_items';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'item_type_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_types',
                    'column' => 'id',
                    'onDelete' => 'cascade',
                ],
            ],
        ],
        'ask_price' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'sales_limit' => [
            'type' => 'integer',
        ],
        'times_purchased' => [
            'type' => 'integer',
            'default' => '0',
            'notNull' => true,
        ],
    ];
}
