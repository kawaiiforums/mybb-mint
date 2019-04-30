<?php

namespace mint\DbRepository;

class TerminationPoints extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_termination_points';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'name' => [
            'type' => 'varchar',
            'length' => 255,
            'uniqueKey' => 1,
        ],
    ];
}
