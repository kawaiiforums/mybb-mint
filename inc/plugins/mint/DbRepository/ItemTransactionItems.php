<?php

namespace mint\DbRepository;

class ItemTransactionItems extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_transaction_items';
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
