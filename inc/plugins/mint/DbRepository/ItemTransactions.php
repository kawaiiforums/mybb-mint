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
        ],
        'bid_user_id' => [
            'type' => 'integer',
        ],
        'ask_price' => [
            'type' => 'integer',
        ],
        'ask_date' => [
            'type' => 'integer',
        ],
        'active' => [
            'type' => 'bool',
        ],
        'completed' => [
            'type' => 'bool',
        ],
        'completion_date' => [
            'type' => 'integer',
        ],
    ];
}
