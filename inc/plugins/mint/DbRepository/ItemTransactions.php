<?php

namespace mint\DbRepository;

class ItemTransactions extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_transactions';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'ask_user_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'users',
                    'column' => 'uid',
                    'noReference' => true,
                ],
            ],
            'notNull' => true,
        ],
        'bid_user_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'users',
                    'column' => 'uid',
                    'noReference' => true,
                ],
            ],
        ],
        'ask_price' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'ask_date' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'active' => [
            'type' => 'bool',
            'notNull' => true,
        ],
        'completed' => [
            'type' => 'bool',
            'notNull' => true,
        ],
        'completion_date' => [
            'type' => 'integer',
        ],
    ];
}
