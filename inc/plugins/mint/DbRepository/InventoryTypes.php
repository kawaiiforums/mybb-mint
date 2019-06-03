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

    public function deleteById(int $id): bool
    {
        $query = $this->db->update_query('users', [
            'mint_inventory_type_id' => 'NULL',
        ], 'mint_inventory_type_id = ' . (int)$id, null, true);

        return parent::deleteById($id);
    }
}
