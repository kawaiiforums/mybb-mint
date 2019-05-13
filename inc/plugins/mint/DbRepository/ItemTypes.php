<?php

namespace mint\DbRepository;

class ItemTypes extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_types';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'item_category_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_categories',
                    'column' => 'id',
                    'onDelete' => 'restrict',
                ],
            ],
        ],
        'name' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'image' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'stacked' => [
            'type' => 'bool',
            'notNull' => true,
        ],
    ];

    public function updateById(int $id, array $data): bool
    {
        $result = parent::updateById($id, $data);

        if (array_key_exists('stacked', $data)) {
            \mint\recountOccupiedUserInventorySlots(null, [
                $id,
            ]);
        }

        return $result;
    }

    public function deleteById(int $id): bool
    {
        $result = parent::deleteById($id);

        \mint\recountOccupiedUserInventorySlots(null, [
            $id,
        ]);

        return $result;
    }
}
