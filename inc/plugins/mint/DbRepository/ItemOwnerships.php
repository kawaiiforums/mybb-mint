<?php

namespace mint\DbRepository;

class ItemOwnerships extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_ownerships';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
        ],
        'item_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'mint_items',
                    'column' => 'id',
                    'onDelete' => 'cascade',
                ],
            ],
        ],
        'user_id' => [
            'type' => 'integer',
            'foreignKeys' => [
                [
                    'table' => 'users',
                    'column' => 'uid',
                    'noReference' => true,
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
        'inventory_slot' => [
            'type' => 'integer',
            'notNull' => true,
            'default' => 0,
        ],
    ];

    public function assign(array $items, int $userId): bool
    {
        $userInventoryData = \mint\getUserInventoryData($userId);

        if ($userInventoryData === null) {
            return false;
        } else {
            $requiredSlots = \mint\getRequiredUserInventorySlotsForItems($userId, $items);

            if ($requiredSlots > $userInventoryData['slotsAvailable']) {
                return false;
            } else {
                $result = true;

                foreach ($items as $item) {
                    $result &= (bool)$this->insert([
                        'item_id' => $item['item_id'],
                        'user_id' => $userId,
                        'active' => 1,
                        'activation_date' => TIME_NOW,
                    ]);
                }

                \mint\recountOccupiedUserInventorySlots([
                    $userId,
                ]);

                return $result;
            }
        }
    }

    public function remove(array $items, int $userId): bool
    {
        $result = true;

        foreach ($items as $item) {
            $result &= (bool)$this->update([
                'active' => 0,
                'deactivation_date' => TIME_NOW,
            ], 'item_id = ' . (int)$item['item_id'] . ' AND user_id = ' . (int)$userId);
        }

        \mint\recountOccupiedUserInventorySlots([
            $userId,
        ]);

        return $result;
    }

    public function removeByItemId(int $itemId): bool
    {
        $result = true;

        $activeItemUsers = \mint\queryResultAsArray(
            $this->get(
                'user_id',
                'WHERE item_id = ' . (int)$itemId . ' AND active = 1'
            )
        );

        $items = [
            [
                'item_id' => $itemId,
            ],
        ];

        foreach ($activeItemUsers as $entry) {
            $result &= ItemOwnerships::with($this->db)->remove($items, $entry['user_id']);
        }

        return $result;
    }
}
