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
            'uniqueKey' => 1,
        ],
        'content_type' => [
            'type' => 'varchar',
            'length' => 255,
            'uniqueKey' => 1,
        ],
        'content_entity_id' => [
            'type' => 'integer',
            'uniqueKey' => 1,
        ],
        'termination_point_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_termination_points',
                    'column' => 'id',
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
