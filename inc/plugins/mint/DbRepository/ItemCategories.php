<?php

namespace mint\DbRepository;

class ItemCategories extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_categories';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
    ];
}
