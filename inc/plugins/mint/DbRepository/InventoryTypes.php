<?php

namespace mint\DbRepository;

class InventoryTypes extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_inventory_types';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'slots' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
    ];
}
