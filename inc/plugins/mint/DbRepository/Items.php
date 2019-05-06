<?php

namespace mint\DbRepository;

class Items extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_items';
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
        'user_id' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'inventory_slot' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'active' => [
            'type' => 'bool',
            'notNull' => true,
        ],
    ];
}
