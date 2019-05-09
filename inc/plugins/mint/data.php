<?php

namespace mint;

use mint\DbRepository\BalanceOperations;
use mint\DbRepository\ContentEntityRewards;
use mint\DbRepository\CurrencyTerminationPoints;
use mint\DbRepository\InventoryTypes;
use mint\DbRepository\Items;
use mint\DbRepository\ItemTerminationPoints;
use mint\DbRepository\ItemTypes;
use mint\DbRepository\ItemUsers;

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

function verifyBalanceOperationsDataIntegrity(bool $attemptToFix = false)
{
    global $db;

    $events = [];

    $db->write_query('BEGIN');

    if (in_array($db->type, ['pgsql', 'mysql'])) {
        if ($attemptToFix) {
            $transactionCharacteristics = 'ISOLATION LEVEL SERIALIZABLE';
        } else {
            $transactionCharacteristics = 'ISOLATION LEVEL SERIALIZABLE, READ ONLY';
        }

        $db->write_query('SET TRANSACTION ' . $transactionCharacteristics);
    }

    // sum of balance operations
    $query = BalanceOperations::with($db)->get(
        'user_id, SUM(value) AS balance_sum',
        'GROUP BY user_id'
    );

    $userBalanceSums = \mint\queryResultAsArray($query, 'user_id', 'balance_sum');

    $userIds = array_keys($userBalanceSums);

    // verify result_balance of most recent operation
    $query = $db->write_query("
        SELECT
            bo.user_id, bo.result_balance
        FROM
            " . TABLE_PREFIX . "mint_balance_operations bo
            LEFT JOIN " . TABLE_PREFIX . "mint_balance_operations bo2
                ON bo.user_id = bo2.user_id AND bo2.id > bo.id
         WHERE bo2.id IS NULL
    ");

    while ($row = $db->fetch_array($query)) {
        $balanceSum = $userBalanceSums[$row['user_id']];

        if ($row['result_balance'] != $balanceSum) {
            $events['result_balance_inconsistency'] = [
                'balance_sum' => $balanceSum,
                'latest_result_balance' => $row['result_balance'],
            ];

            break;
        }
    }

    // verify users.mint_balance
    $query = $db->simple_select('users', 'uid, mint_balance', 'uid IN (' . implode(',', array_map('intval', $userIds)) . ')');

    while ($row = $db->fetch_array($query)) {
        $balanceSum = $userBalanceSums[$row['uid']];

        if ($row['mint_balance'] != $balanceSum) {
            $events['user_balance_inconsistency'] = [
                'balance_sum' => $balanceSum,
                'user_balance' => $row['mint_balance'],
            ];

            if ($attemptToFix) {
                $db->update_query('users', [
                    'mint_balance' => $balanceSum,
                ], 'uid = ' . (int)$row['uid']);
            }

            break;
        }
    }

    $db->write_query('COMMIT');

    if (!empty($events)) {
        foreach ($events as $eventType => $data) {
            \mint\addUniqueLogEvent($eventType, $data);
        }

        if (\mint\getSettingValue('disable_manual_balance_operations_on_inconsistency')) {
            \mint\updateSettingValue('manual_balance_operations', 0);
        }

        return false;
    } else {
        $events = \mint\getCacheValue('unique_log_events');

        unset($events['result_balance_inconsistency']);
        unset($events['user_balance_inconsistency']);

        \mint\updateCache([
            'unique_log_events' => $events,
        ]);

        return true;
    }
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

function countOccupiedUserInventorySlots(int $userId, bool $cached = true, bool $forUpdate = false): ?int
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
        $query = $db->query("
            SELECT SUM(n) AS n FROM
            (
                SELECT
                    COUNT(iu.id) AS n
                    FROM
                        " . TABLE_PREFIX . "mint_item_users iu
                        INNER JOIN " . TABLE_PREFIX . "mint_items i ON iu.item_id = i.id
                        INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                    WHERE user_id = " . (int)$userId . " AND it.stacked = 0 AND iu.active = 1
                UNION ALL
                SELECT
                    COUNT(DISTINCT item_type_id) AS n
                    FROM
                        " . TABLE_PREFIX . "mint_item_users iu
                        INNER JOIN " . TABLE_PREFIX . "mint_items i ON iu.item_id = i.id
                        INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                    WHERE user_id = " . (int)$userId . " AND it.stacked = 1 AND iu.active = 1
            ) itemCountsByStackedStatus
        ");

        $count = $db->fetch_field($query, 'n');
    }

    return $count;
}

// items
function getItemsById(array $itemIds, bool $forUpdate = false)
{
    global $db;

    if (!empty($itemIds)) {
        $conditions = 'id IN (' . implode(',', array_map('intval', $itemIds)) . ')';

        if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
            $conditions .= ' FOR UPDATE';
        }

        $query = $db->simple_select('mint_items', '*', $conditions);

        return $query;
    } else {
        return null;
    }
}

// user items
function getDistinctUserItemTypeIds(int $userId): array
{
    global $db;

    return \mint\queryResultAsArray(
        $db->query("
            SELECT
                DISTINCT item_type_id
                FROM
                    " . TABLE_PREFIX . "mint_item_users iu
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON iu.item_id = i.id
                WHERE user_id = " . (int)$userId . " AND iu.active = 1
        "),
        null,
        'item_type_id'
    );
}

function getRequiredUserInventorySlotsForItems(int $userId, array $items): int
{
    $slotsRequired = 0;

    $distinctUserItemTypeIds = \mint\getDistinctUserItemTypeIds($userId);

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

function createUserItemsWithTerminationPoint(int $itemTypeId, int $amount, int $userId, string $terminationPointName, bool $useDbTransaction = true): bool
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
                $result &= ItemUsers::with($db)->assign($items, $userId);
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
                iti.item_id, i.item_type_id, iu.user_id, it.stacked
                FROM
                    " . TABLE_PREFIX . "mint_item_transaction_items iti
                    LEFT JOIN " . TABLE_PREFIX . "mint_items i ON iti.item_id = i.id
                    LEFT JOIN " . TABLE_PREFIX . "mint_items_users iu ON iti.item_id = i.id AND iu.active = 1
                    LEFT JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                WHERE item_transaction_id = " . (int)$transactionId . "
        ")
    );
}
