<?php

namespace mint\DbRepository;

class ContentEntityRewards extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_content_entity_rewards';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'user_id' => [
            'type' => 'integer',
            'notNull' => true,
            'uniqueKey' => 1,
        ],
        'content_type' => [
            'type' => 'varchar',
            'length' => 255,
            'notNull' => true,
            'uniqueKey' => 1,
        ],
        'content_entity_id' => [
            'type' => 'integer',
            'notNull' => true,
            'uniqueKey' => 1,
        ],
        'currency_termination_point_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_currency_termination_points',
                    'column' => 'id',
                    'onDelete' => 'restrict',
                ],
            ],
            'uniqueKey' => 1,
        ],
        'value' => [
            'type' => 'integer',
        ],
        'last_action_date' => [
            'type' => 'integer',
        ],
        'void' => [
            'type' => 'bool',
        ],
    ];
}
