<?php

namespace mint\DbRepository;

class ItemTransactionItemTypes extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_transaction_item_types';
    public const COLUMNS = [
        'item_transaction_id' => [
            'type' => 'integer',
            'primaryKey' => true,
            'foreignKeys' => [
                [
                    'table' => 'mint_item_transactions',
                    'column' => 'id',
                    'onDelete' => 'cascade',
                ],
            ],
        ],
        'item_type_id' => [
            'type' => 'integer',
            'primaryKey' => true,
            'foreignKeys' => [
                [
                    'table' => 'mint_item_types',
                    'column' => 'id',
                    'onDelete' => 'cascade',
                ],
            ],
        ],
        'amount' => [
            'type' => 'integer',
            'notNull' => true,
        ],
    ];
}
