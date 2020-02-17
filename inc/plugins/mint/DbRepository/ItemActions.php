<?php

namespace mint\DbRepository;

class ItemActions extends \mint\DbEntityRepository
{
    public const TABLE_NAME = 'mint_item_actions';
    public const COLUMNS = [
        'id' => [
            'type' => 'integer',
            'primaryKey' => true,
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
            'notNull' => true,
        ],
        'date' => [
            'type' => 'integer',
            'notNull' => true,
        ],
        'name' => [
            'type' => 'varchar',
            'length' => 255,
            'notNull' => true,
        ],
    ];

    public function execute(array $data, bool $useDbTransaction = true): array
    {
        if ($useDbTransaction) {
            $this->db->write_query('BEGIN');
        }

        try {
            $result = true;

            $action = array_merge(
                \mint\getArraySubset($data, [
                    'user_id',
                    'name',
                ]),
                [
                    'date' => TIME_NOW,
                ]
            );

            // lock, validate ask items
            \mint\getItemsById(array_column($data['items'], 'item_id'), true);

            $itemOwnershipsDetails = \mint\getItemOwnershipsDetails(
                array_column($data['items'], 'item_ownership_id')
            );

            if ($itemOwnershipsDetails === null || count($itemOwnershipsDetails) != count($data['items'])) {
                throw new \RuntimeException('Could not fetch selected Items for Item Action');
            }

            foreach ($itemOwnershipsDetails as $item) {
                if (
                    $item['user_id'] != $action['user_id'] ||
                    $item['item_ownership_active'] == 0 ||
                    $item['item_active'] == 0 ||
                    $item['item_transaction_id']
                ) {
                    throw new \RuntimeException('Item Action not executable with selected Items for user');
                }
            }

            // insert entry
            $actionId = $this->insert($action);

            if (!$actionId) {
                throw new \RuntimeException('Could not insert Item Action record');
            } else {
                $action['id'] = $actionId;
            }

            // insert item entries
            foreach ($itemOwnershipsDetails as $itemOwnershipDetails) {
                $result = ItemActionItems::with($this->db)->insert([
                    'item_action_id' => $actionId,
                    'item_id' => $itemOwnershipDetails['item_id'],
                ]) !== false;

                if ($result == false) {
                    throw new \RuntimeException('Could not insert Item Action Item');
                }
            }

            // execute action
            $action = \mint\executeItemAction($action, $itemOwnershipsDetails);
        } catch (\RuntimeException $e) {
            $result = false;
        } finally {
            if ($useDbTransaction) {
                if ($result == true) {
                    $this->db->write_query('COMMIT');
                } else {
                    $this->db->write_query('ROLLBACK');
                }
            }
        }

        return [
            'success' => $result,
            'createdItemTypeIds' => $action['createdItemTypeIds'] ?? [],
        ];
    }
}
