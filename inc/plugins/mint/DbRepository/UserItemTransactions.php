<?php

namespace mint\DbRepository;

class UserItemTransactions extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_user_item_transactions';
    public const COLUMNS = [
        'transaction_id' => [
            'type' => 'integer',
            'primaryKey' => true,
            'foreignKeys' => [
                [
                    'table' => 'mint_item_transactions',
                    'column' => 'id',
                ],
            ],
        ],
        'item_id' => [
            'type' => 'integer',
            'primaryKey' => true,
            'foreignKeys' => [
                [
                    'table' => 'mint_user_items',
                    'column' => 'id',
                ],
            ],
        ],
    ];
}
