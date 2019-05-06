<?php

namespace mint\DbRepository;

class ItemOperations extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_operations';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'item_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_items',
                    'column' => 'id',
                ],
            ],
            'notNull' => true,
        ],
        'state' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'date' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'item_transaction_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_transactions',
                    'column' => 'id',
                ],
            ],
        ],
        'item_termination_point_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_termination_points',
                    'column' => 'id',
                ],
            ],
        ],
    ];
}
