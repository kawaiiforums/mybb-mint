<?php

namespace mint\DbRepository;

class ItemActionItems extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_action_items';
    public const COLUMNS = [
        'item_action_id' => [
            'type' => 'integer',
            'primaryKey' => true,
            'foreignKeys' => [
                [
                    'table' => 'mint_item_actions',
                    'column' => 'id',
                    'onDelete' => 'cascade',
                ],
            ],
        ],
        'item_id' => [
            'type' => 'integer',
            'primaryKey' => true,
            'foreignKeys' => [
                [
                    'table' => 'mint_items',
                    'column' => 'id',
                    'onDelete' => 'cascade',
                ],
            ],
        ],
    ];
}
