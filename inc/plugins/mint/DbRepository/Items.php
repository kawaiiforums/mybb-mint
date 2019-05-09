<?php

namespace mint\DbRepository;

class Items extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_items';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'item_type_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_types',
                    'column' => 'id',
                    'onDelete' => 'cascade',
                ],
            ],
        ],
        'active' => [
            'type' => 'bool',
            'notNull' => true,
        ],
        'activation_date' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'deactivation_date' => [
            'type' => 'integer',
        ],
        'activation_item_termination_point_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_termination_points',
                    'column' => 'id',
                ],
            ],
        ],
        'deactivation_item_termination_point_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_item_termination_points',
                    'column' => 'id',
                ],
            ],
        ],
    ];

    public function create(int $itemTypeId, ?int $terminationPointId = null)
    {
        $id = $this->insert([
            'item_type_id' => $itemTypeId,
            'active' => 1,
            'activation_date' => TIME_NOW,
            'activation_item_termination_point_id' => $terminationPointId,
        ]);

        return $id;
    }

    public function remove(int $itemId, ?int $terminationPointId = null): bool
    {
        $result = Items::with($this->db)->updateById($itemId, [
            'active' => 0,
            'deactivation_date' => TIME_NOW,
            'deactivation_item_termination_point_id' => $terminationPointId,
        ]);

        $result &= ItemUsers::with($this->db)->removeByItemId($itemId);

        return $result;
    }
}
