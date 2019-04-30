<?php

namespace mint\DbRepository;

class UserItems extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_user_items';
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
        ],
        'inventory_slot' => [
            'type' => 'integer',
        ],
        'active' => [
            'type' => 'bool',
        ],
    ];
}
