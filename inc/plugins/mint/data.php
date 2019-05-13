<?php

namespace mint;

use mint\DbRepository\BalanceOperations;
use mint\DbRepository\ContentEntityRewards;
use mint\DbRepository\CurrencyTerminationPoints;
use mint\DbRepository\InventoryTypes;
use mint\DbRepository\Items;
use mint\DbRepository\ItemTerminationPoints;
use mint\DbRepository\ItemTypes;
use mint\DbRepository\ItemOwnerships;

// balance
function getUserBalance(int $userId, bool $forUpdate = false): ?int
{
    global $mybb, $db;

    if (!$forUpdate && $userId === $mybb->user['uid']) {
        return $mybb->user['mint_balance'];
    } else {
        $conditions = 'uid = ' . (int)$userId;

        if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
            $conditions .= ' FOR UPDATE';
        }

        $query = $db->simple_select('users', 'mint_balance', $conditions);

        if ($db->num_rows($query) == 1) {
            return (int)$db->fetch_field($query, 'mint_balance');
        } else {
            return null;
        }
    }
}

function getTopUsersByBalance(int $limit)
{
    global $db;

    return $db->simple_select('users', 'uid, username, usergroup, displaygroup, mint_balance', null, [
        'order_by' => 'mint_balance',
        'order_dir' => 'desc',
        'limit' => (int)$limit,
    ]);
}

// balance operations
function getBalanceOperations(?string $conditions = null)
{
    global $db;

    $query = $db->query("
        SELECT
            bo.*,
            bt.note, bt.private,
            tp.name AS currency_termination_point_name,
            u_from.uid AS from_user_id, u_from.username AS from_username,
            u_to.uid AS to_user_id, u_to.username AS to_username
            FROM
                " . TABLE_PREFIX . "mint_balance_operations bo
                LEFT JOIN " . TABLE_PREFIX . "mint_currency_termination_points tp ON bo.currency_termination_point_id = tp.id
                LEFT JOIN " . TABLE_PREFIX . "mint_balance_transfers bt ON bo.balance_transfer_id = bt.id 
                LEFT JOIN " . TABLE_PREFIX . "users u_from ON bt.from_user_id = u_from.uid
                LEFT JOIN " . TABLE_PREFIX . "users u_to ON bt.to_user_id = u_to.uid
            {$conditions}
    ");

    return $query;
}

function countBalanceOperations(?string $conditions = null): int
{
    global $db;

    return $db->fetch_field(
        $db->query("
            SELECT
                COUNT(bo.id) AS n
                FROM
                    " . TABLE_PREFIX . "mint_balance_operations bo
                    LEFT JOIN " . TABLE_PREFIX . "mint_currency_termination_points tp ON bo.currency_termination_point_id = tp.id
                    LEFT JOIN " . TABLE_PREFIX . "mint_balance_transfers bt ON bo.balance_transfer_id = bt.id 
                    LEFT JOIN " . TABLE_PREFIX . "users u_from ON bt.from_user_id = u_from.uid
                    LEFT JOIN " . TABLE_PREFIX . "users u_to ON bt.to_user_id = u_to.uid
                {$conditions}
        "),
        'n'
    );
}

function getUserBalanceOperations(int $userId, ?string $conditions = null)
{
    return \mint\getBalanceOperations('WHERE bo.user_id = ' . (int)$userId . ' ' . $conditions);
}

function getRecentUserBalanceOperations(int $userId, int $limit)
{
    return \mint\getUserBalanceOperations(
        $userId,
        "ORDER BY id DESC LIMIT " . (int)$limit
    );
}

function getUserPublicBalanceOperations(int $userId, array $includePrivateWithUserIds = [], ?string $conditions = null)
{
    $whereString = 'bo.user_id = ' . (int)$userId;

    if (!empty($includePrivateWithUserIds)) {
        $csv = implode(
            ',',
            array_map('intval', $includePrivateWithUserIds)
        );

        $whereString .= ' AND (
            private IS NULL OR
            private = 0 OR
            from_user_id IN (' . $csv . ') OR
            to_user_id IN(' . $csv . ')
        )';
    } else {
         $whereString .= ' AND private IS NULL OR private = 0';
    }

    return \mint\getBalanceOperations('WHERE ' . $whereString . ' ' . $conditions);
}

function countUserPublicBalanceOperations(int $userId, array $includePrivateWithUserIds = [], ?string $conditions = null): int
{
    $whereString = 'bo.user_id = ' . (int)$userId;

    if (!empty($includePrivateWithUserIds)) {
        $csv = implode(
            ',',
            array_map('intval', $includePrivateWithUserIds)
        );

        $whereString .= ' AND (
            private IS NULL OR
            private = 0 OR
            from_user_id IN (' . $csv . ') OR
            to_user_id IN(' . $csv . ')
        )';
    } else {
         $whereString .= ' AND private IS NULL OR private = 0';
    }

    return \mint\countBalanceOperations('WHERE ' . $whereString . ' ' . $conditions);
}

function userBalanceOperationWithTerminationPoint($user, int $value, string $terminationPointName, bool $allowOverdraft = true): bool
{
    global $db;

    if (is_array($user)) {
        $userId = (int)$user['uid'];
    } else {
        $userId = (int)$user;
    }

    $terminationPointId = CurrencyTerminationPoints::with($db)->getByColumn('name', $terminationPointName)['id'] ?? null;

    if ($terminationPointId !== null) {
        $result = BalanceOperations::with($db)->execute($userId, $value, [
            'currency_termination_point_id' => $terminationPointId,
        ], true, $allowOverdraft);

        return $result;
    } else {
        return false;
    }
}

// balance transfers
function getBalanceTransfers(?string $conditions = null)
{
    global $db;

    $query = $db->query("
        SELECT
            bt.*,
            u_from.uid AS from_user_id, u_from.username AS from_username,
            u_to.uid AS to_user_id, u_to.username AS to_username
            FROM
                " . TABLE_PREFIX . "mint_balance_transfers bt
                LEFT JOIN " . TABLE_PREFIX . "users u_from ON bt.from_user_id = u_from.uid
                LEFT JOIN " . TABLE_PREFIX . "users u_to ON bt.to_user_id = u_to.uid
            {$conditions}
    ");

    return $query;
}

function getRecentPublicBalanceTransfers(int $limit)
{
    return \mint\getBalanceTransfers(
        "WHERE private = 0 ORDER BY id DESC LIMIT " . (int)$limit
    );
}

// content entity rewards
function addContentEntityReward(string $rewardSourceName, int $contentEntityId, int $userId): bool
{
    global $db;

    $rewardSource = \mint\getRegisteredRewardSources()[$rewardSourceName] ?? null;

    if ($rewardSource && $rewardSource['reward']() != 0) {
        $terminationPointId = CurrencyTerminationPoints::with($db)->getByColumn('name', $rewardSource['terminationPoint'])['id'] ?? null;

        if ($terminationPointId !== null) {
            $entry = $db->fetch_array(
                ContentEntityRewards::with($db)->get('*', "WHERE
                    user_id = " . (int)$userId . " AND
                    content_type = '" . $db->escape_string($rewardSource['contentType']) . "' AND
                    content_entity_id = " . (int)$contentEntityId . " AND
                    termination_point_id = " . (int)$terminationPointId . "
                ")
            );

            if ($entry) {
                if ($entry['void'] == 1) {
                    $value = $entry['value'];

                    ContentEntityRewards::with($db)->updateById($entry['id'], [
                        'last_action_date' => \TIME_NOW,
                        'void' => false,
                    ]);
                } else {
                    return false;
                }
            } else {
                $value = $rewardSource['reward']();

                ContentEntityRewards::with($db)->insert([
                    'user_id' => $userId,
                    'content_type' => $rewardSource['contentType'],
                    'content_entity_id' => $contentEntityId,
                    'currency_termination_point_id' => $terminationPointId,
                    'value' => $value,
                    'last_action_date' => \TIME_NOW,
                    'void' => false,
                ]);
            }

            \mint\userBalanceOperationWithTerminationPoint($userId, $value, $rewardSource['terminationPoint']);

            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function voidContentEntityReward(string $rewardSourceName, int $contentEntityId): bool
{
    global $db;

    $rewardSource = \mint\getRegisteredRewardSources()[$rewardSourceName] ?? null;

    if ($rewardSource) {
        $terminationPointId = CurrencyTerminationPoints::with($db)->getByColumn('name', $rewardSource['terminationPoint'])['id'] ?? null;

        if ($terminationPointId !== null) {
            $entries = \mint\queryResultAsArray(
                ContentEntityRewards::with($db)->get('*', "WHERE
                    content_type = '" . $db->escape_string($rewardSource['contentType']) . "' AND
                    content_entity_id = " . (int)$contentEntityId . " AND
                    currency_termination_point_id = " . (int)$terminationPointId . "
                ")
            );

            foreach ($entries as $entry) {
                if ($entry) {
                    if ($entry['void'] == 0) {
                        $result = ContentEntityRewards::with($db)->update([
                            'last_action_date' => \TIME_NOW,
                            'void' => true,
                        ], "
                            user_id = " . (int)$entry['user_id'] . " AND
                            content_type = '" . $db->escape_string($rewardSource['contentType']) . "' AND
                            content_entity_id = " . (int)$contentEntityId . " AND
                            currency_termination_point_id = " . (int)$terminationPointId . " 
                        ");

                        \mint\userBalanceOperationWithTerminationPoint($entry['user_id'], -$entry['value'], $rewardSource['terminationPoint']);
                    }
                }
            }

            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// inventory
function getUserInventoryType($user): ?array
{
    global $db;

    if ($user['mint_inventory_type_id']) {
        $inventoryTypeId = $user['mint_inventory_type_id'];
    } else {
        $inventoryTypeId = \mint\getSettingValue('default_inventory_type_id');
    }

    $userInventoryType = InventoryTypes::with($db)->getById($inventoryTypeId);

    return $userInventoryType;
}

function getUserInventoryData($user): ?array
{
    if (!is_array($user)) {
        $user = \get_user($user);

        if (!$user) {
            return null;
        }
    }

    $userInventoryType = \mint\getUserInventoryType($user);

    if ($userInventoryType) {
        $slots = $userInventoryType['slots'] + $user['mint_inventory_slots_bonus'];

        $userInventoryData = [
            'title' => $userInventoryType['title'],
            'slots' => $slots,
            'slotsOccupied' => $user['mint_inventory_slots_occupied'],
            'slotsAvailable' => $slots - $user['mint_inventory_slots_occupied'],
        ];
    } else {
        $userInventoryData = null;
    }

    return $userInventoryData;
}

function getOccupiedUserInventorySlots(int $userId, bool $cached = true, bool $forUpdate = false): ?int
{
    global $mybb, $db;

    if ($cached || $forUpdate) {
        if (!$forUpdate && $userId === $mybb->user['uid']) {
            $count = (int)$mybb->user['mint_inventory_slots_occupied'];
        } else {
            $conditions = 'uid = ' . (int)$userId;

            if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
                $conditions .= ' FOR UPDATE';
            }

            $query = $db->simple_select('users', 'mint_inventory_slots_occupied', $conditions);

            if ($db->num_rows($query) == 1) {
                $count = (int)$db->fetch_field($query, 'mint_inventory_slots_occupied');
            } else {
                $count = null;
            }
        }
    } else {
        $query = \mint\countOccupiedUserInventorySlots([
            $userId,
        ]);

        $count = $db->fetch_array($query, 'n');
    }

    return $count;
}

function recountOccupiedUserInventorySlots(?array $userIds = null, ?array $itemTypeId = null): void
{
    global $db;

    $query = \mint\countOccupiedUserInventorySlots($userIds, $itemTypeId);

    if ($query !== null) {
        while ($entry = $db->fetch_array($query)) {
            \mint\updateUser($entry['user_id'], [
                'mint_inventory_slots_occupied' => $entry['n'],
            ]);
        }
    }
}

function countOccupiedUserInventorySlots(?array $userIds = null, ?array $itemTypeId = null)
{
    global $db;

    if ($userIds === [] || $itemTypeId === []) {
        return null;
    }

    $whereConditions = [
        'io.active = 1',
    ];

    if ($userIds !== null) {
        $where[] = 'io.user_id IN (' . implode(',', array_map('intval', $userIds)) . ')';
    }

    if ($itemTypeId !== null) {
        $where[] = 'it.id IN (' . implode(',', array_map('intval', $itemTypeId)) . ')';
    }

    $where = implode(' AND ', $whereConditions);

    $query = $db->query("
        SELECT
            user_id, SUM(n) AS n
            FROM
                (
                    SELECT
                        io.user_id, COUNT(io.id) AS n
                        FROM
                            " . TABLE_PREFIX . "mint_item_ownerships io
                            INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                            INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                        WHERE it.stacked = 0 AND {$where}
                        GROUP BY io.user_id
                    UNION ALL
                    SELECT
                        io.user_id, COUNT(DISTINCT it.id) AS n
                        FROM
                            " . TABLE_PREFIX . "mint_item_ownerships io
                            INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                            INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                        WHERE it.stacked = 1 AND {$where}
                        GROUP BY io.user_id
                ) itemCountsByStackedStatus
            GROUP BY user_id
    ");

    return $query;
}

// items
function getItemsById(array $ids, bool $forUpdate = false)
{
    global $db;

    if (!empty($ids)) {
        $conditions = 'id IN (' . implode(',', array_map('intval', $ids)) . ')';

        if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
            $conditions .= ' FOR UPDATE';
        }

        $query = $db->simple_select('mint_items', '*', $conditions);

        return $query;
    } else {
        return null;
    }
}

// item ownerships
function getItemOwnershipsById(array $ids, bool $forUpdate = false)
{
    global $db;

    if (!empty($ids)) {
        $conditions = 'id IN (' . implode(',', array_map('intval', $ids)) . ')';

        if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
            $conditions .= ' FOR UPDATE';
        }

        $query = $db->simple_select('mint_item_ownerships', '*', $conditions);

        return $query;
    } else {
        return null;
    }
}

function getItemOwnershipWithDetails(int $id): ?array
{
    $items = \mint\getItemOwnershipsWithDetailsByUser(null, [
        $id,
    ]);

    if (count($items) == 1) {
        $item = current($items);
    } else {
        $item = null;
    }

    return $item;
}

function getItemOwnershipsWithDetailsByUser(?int $userId, ?array $itemOwnershipIds = null, ?int $mostRecent = null): array
{
    $itemOwnerships = [];

    $userItemOwnershipsWithStackedAmount = \mint\getUserItemOwnershipsWithStackedAmount($userId, $itemOwnershipIds, $mostRecent);

    $userItemOwnershipsDetails = \mint\getUserItemOwnershipsDetails(
        array_keys($userItemOwnershipsWithStackedAmount)
    );

    foreach ($userItemOwnershipsWithStackedAmount as $entry) {
        $itemOwnerships[] = array_merge($entry, $userItemOwnershipsDetails[$entry['id']]);
    }

    return $itemOwnerships;
}

function getUserItemOwnershipsWithStackedAmount(?int $userId, ?array $itemOwnershipIds = null, ?int $mostRecent = null): array
{
    global $db;

    if (!empty($itemOwnershipIds)) {
        $csv = implode(',', array_map('intval', $itemOwnershipIds));

        $query = $db->query("
            SELECT
                io.id, io.user_id, i.item_type_id, it.stacked
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                    WHERE io.id IN (" . $csv . ")
        ");

        $stackedTypeIds = [];
        $userIds = [];
        $nonStackedItemUserIds = [];

        while ($row = $db->fetch_array($query)) {
            if ($row['stacked']) {
                $stackedTypeIds[] = (int)$row['item_type_id'];
                $userIds[] = (int)$row['user_id'];
            } else {
                $nonStackedItemUserIds[] = (int)$row['id'];
            }
        }

        if ($stackedTypeIds && $userIds) {
            $stackedWhereConditions = 'AND (
            it.id IN (' . implode(',', $stackedTypeIds) . ') AND io.user_id IN (' . implode(',', $userIds) . ')
        )';
        } else {
            $stackedWhereConditions = null;
        }

        if ($nonStackedItemUserIds) {
            $nonStackedWhereConditions = 'AND io.id IN (' . implode(',', $nonStackedItemUserIds) . ')';
        } else {
            $nonStackedWhereConditions = null;
        }
    } else {
        $stackedWhereConditions = 'AND user_id = ' . (int)$userId;
        $nonStackedWhereConditions = 'AND user_id = ' . (int)$userId;
    }

    $unionQueries = [];

    if ($nonStackedWhereConditions) {
        $unionQueries[] = "(
            SELECT
                io.id, io.activation_date, NULL AS stacked_amount
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                WHERE it.stacked = 0 AND io.active = 1 {$nonStackedWhereConditions}
        )";
    }

    if ($stackedWhereConditions) {
        $unionQueries[] = "(
            SELECT
                MIN(io.id) AS id, MAX(io.activation_date) AS activation_date, COUNT(io.id) AS stacked_amount
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                WHERE it.stacked = 1 AND io.active = 1 {$stackedWhereConditions}
                GROUP BY i.item_type_id
        )";
    }

    if ($unionQueries) {
        if ($mostRecent !== null) {
            $conditions = 'ORDER BY activation_date DESC LIMIT ' . (int)$mostRecent;
        } else {
            $conditions = null;
        }

        $query = implode(' UNION ALL ', $unionQueries);

        $result = \mint\queryResultAsArray(
            $db->query($query . $conditions),
            'id'
        );
    } else {
        $result = [];
    }

    return $result;
}

function getUserItemOwnershipsDetails(array $itemOwnershipIds): array
{
    global $db;

    if (!empty($itemOwnershipIds)) {
        $csv = implode(',', array_map('intval', $itemOwnershipIds));

        return \mint\queryResultAsArray(
            $db->query("
                SELECT
                    io.id AS item_user_id, io.item_id, io.user_id, io.active AS item_user_active, io.activation_date, io.deactivation_date,
                    u.username AS user_username,
                    i.item_type_id, i.active AS item_active, i.activation_date AS item_activation_date,
                    it.title AS item_type_title, it.image AS item_type_image, it.stacked AS item_type_stacked,
                    ic.title AS item_category_title 
                    FROM
                        " . TABLE_PREFIX . "mint_item_ownerships io
                        INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                        INNER JOIN " . TABLE_PREFIX . "users u ON io.user_Id = u.uid
                        INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                        INNER JOIN " . TABLE_PREFIX . "mint_item_categories ic ON it.item_category_id = ic.id
                    WHERE io.id IN (" . $csv . ")
            "),
            'item_user_id'
        );
    } else {
        return [];
    }
}

function getItemOwnershipsByItemTypeAndUser(int $itemTypeId, int $userId): array
{
    global $db;

    return \mint\queryResultAsArray(
        $db->query("
            SELECT
                io.id, i.id AS item_id, it.id AS item_type_id
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                WHERE it.id = " . (int)$itemTypeId . " AND io.user_id = " . (int)$userId . " AND io.active = 1
                ORDER BY io.activation_date DESC
        "),
        'id'
    );
}

function getDistinctItemTypeIdsByUser(int $userId): array
{
    global $db;

    return \mint\queryResultAsArray(
        $db->query("
            SELECT
                DISTINCT item_type_id
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                WHERE user_id = " . (int)$userId . " AND io.active = 1
        "),
        null,
        'item_type_id'
    );
}

function getRequiredUserInventorySlotsForItems(int $userId, array $items): int
{
    $slotsRequired = 0;

    $distinctUserItemTypeIds = \mint\getDistinctItemTypeIdsByUser($userId);

    foreach ($items as $item) {
        $inArray = in_array($item['item_type_id'], $distinctUserItemTypeIds);

        if (!$item['stacked'] || !$inArray) {
            $slotsRequired++;
        }

        if (!$inArray) {
            $distinctUserItemTypeIds[] = $item['item_type_id'];
        }
    }

    return $slotsRequired;
}

function countAvailableUserInventorySlotsWithItems(int $userId, array $items): int
{
    $bidUserInventory = \mint\getUserInventoryData($userId);
    $slotsRequired = \mint\getRequiredUserInventorySlotsForItems($userId, $items);

    $slotsWithItems = $bidUserInventory['slotsAvailable'] - $slotsRequired;

    return $slotsWithItems;
}

function createItemsWithTerminationPoint(int $itemTypeId, int $amount, int $userId, string $terminationPointName, bool $useDbTransaction = true): bool
{
    global $db;

    $terminationPointId = ItemTerminationPoints::with($db)->getByColumn('name', $terminationPointName)['id'] ?? null;

    if ($terminationPointId !== null) {
        $itemType = ItemTypes::with($db)->getById($itemTypeId);

        if ($itemType !== null) {
            if ($useDbTransaction) {
                $db->write_query('BEGIN');
            }

            $result = true;

            $items = [];

            for ($i = 1; $i <= $amount; $i++) {
                $itemId = Items::with($db)->create($itemTypeId, $terminationPointId);

                if ($itemId) {
                    $items[] = [
                        'item_id' => $itemId,
                        'item_type_id' => $itemTypeId,
                        'stacked' => $itemType['stacked'],
                    ];
                } else {
                    $result &= false;
                    break;
                }
            }

            if ($result == true) {
                $result &= ItemOwnerships::with($db)->assign($items, $userId);
            }

            if ($useDbTransaction) {
                if ($result == true) {
                    $db->write_query('COMMIT');
                } else {
                    $db->write_query('ROLLBACK');
                }
            }

            return $result;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function removeItemsWithTerminationPoint(int $itemOwnershipId, int $stackedAmount, string $terminationPointName, bool $useDbTransaction = true): bool
{
    global $db;

    $terminationPointId = ItemTerminationPoints::with($db)->getByColumn('name', $terminationPointName)['id'] ?? null;

    if ($terminationPointId !== null) {
        $itemOwnershipDetails = \mint\getItemOwnershipWithDetails($itemOwnershipId);

        if ($itemOwnershipDetails !== null) {
            if ($useDbTransaction) {
                $db->write_query('BEGIN');
            }

            if ($itemOwnershipDetails['item_type_stacked']) {
                $itemTypeOwnerships = \mint\getItemOwnershipsByItemTypeAndUser($itemOwnershipDetails['item_type_id'], $itemOwnershipDetails['user_id']);

                $itemIds = array_slice(array_column($itemTypeOwnerships, 'item_id'), 0, $stackedAmount);
            } else {
                $itemIds = [
                    $itemOwnershipDetails['item_id']
                ];
            }

            $result = (bool)\mint\getItemsById($itemIds, true);

            if ($result === true) {
                foreach ($itemIds as $itemId) {
                    $result &= Items::with($db)->remove($itemId, $terminationPointId);
                }
            }

            if ($useDbTransaction) {
                if ($result == true) {
                    $db->write_query('COMMIT');
                } else {
                    $db->write_query('ROLLBACK');
                }
            }

            return $result;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// item transactions
function getItemTransactionById(int $transactionId, bool $forUpdate = false): ?array
{
    global $db;

    $conditions = 'id = ' . (int)$transactionId;

    if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
        $conditions .= ' FOR UPDATE';
    }

    $query = $db->simple_select('mint_item_transactions', '*', $conditions);

    if ($db->num_rows($query) == 1) {
        return $db->fetch_array($query);
    } else {
        return null;
    }
}

function getItemTransactionItems(int $transactionId): array
{
    global $db;

    return \mint\queryResultAsArray(
        $db->query("
            SELECT
                iti.item_id, i.item_type_id, io.user_id, it.stacked
                FROM
                    " . TABLE_PREFIX . "mint_item_transaction_items iti
                    LEFT JOIN " . TABLE_PREFIX . "mint_items i ON iti.item_id = i.id
                    LEFT JOIN " . TABLE_PREFIX . "mint_items_users iu ON iti.item_id = i.id AND io.active = 1
                    LEFT JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                WHERE item_transaction_id = " . (int)$transactionId . "
        ")
    );
}
