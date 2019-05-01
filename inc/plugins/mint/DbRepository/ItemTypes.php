<?php

namespace mint\DbRepository;

class ItemTypes extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_types';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'category_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_categories',
                    'column' => 'id',
                ],
            ],
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'image' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'stacked' => [
            'type' => 'bool',
        ],
    ];
}