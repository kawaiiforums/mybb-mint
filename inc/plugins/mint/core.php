<?php

namespace mint;

use mint\DbRepository\BalanceOperations;

// modules
function getModuleNames(bool $useCache = true): array
{
    if ($useCache) {
        $moduleNames = \mint\getCacheValue('modules') ?? [];
    } else {
        $moduleNames = [];

        $directory = new \DirectoryIterator(MYBB_ROOT . 'inc/plugins/mint/modules');

        foreach ($directory as $file) {
            if (!$file->isDot() && $file->isDir()) {
                $moduleNames[] = $file->getFilename();
            }
        }
    }

    return $moduleNames;
}

function loadModules(array $moduleNames): void
{
    foreach ($moduleNames as $moduleName) {
        require_once MYBB_ROOT . 'inc/plugins/mint/modules/' . $moduleName . '/module.php';
    }
}

/**
 * @param array|callable $settings
 */
function registerSettings($settings): void
{
    global $mintRuntimeRegistry;

    if (is_callable($settings)) {
        $mintRuntimeRegistry['settingCallables'][] = $settings;
    } else {
        $mintRuntimeRegistry['settings'] = array_merge($mintRuntimeRegistry['settings'] ?? [], $settings);
    }
}

function getRegisteredSettings(): array
{
    global $mintRuntimeRegistry;

    $settings = $mintRuntimeRegistry['settings'] ?? [];

    if (!empty($mintRuntimeRegistry['settingCallables'])) {
        foreach ($mintRuntimeRegistry['settingCallables'] as $callable) {
            $settings = array_merge($settings, $callable());
        }
    }

    return $settings;
}

function loadModuleLanguageFile(string $moduleName, string $section): void
{
    \mint\loadExternalLanguageFile('inc/plugins/mint/modules/' . $moduleName . '/languages', $section);
}

function registerCurrencyTerminationPoints(array $names): void
{
    global $mintRuntimeRegistry;

    $mintRuntimeRegistry['currencyTerminationPoints'] = array_unique(
        array_merge($mintRuntimeRegistry['currencyTerminationPoints'] ?? [], $names)
    );
}

function getRegisteredCurrencyTerminationPoints(): array
{
    global $mintRuntimeRegistry;

    return $mintRuntimeRegistry['currencyTerminationPoints'] ?? [];
}

function registerItemTerminationPoints(array $names): void
{
    global $mintRuntimeRegistry;

    $mintRuntimeRegistry['itemTerminationPoints'] = array_unique(
        array_merge($mintRuntimeRegistry['itemTerminationPoints'] ?? [], $names)
    );
}

function getRegisteredItemTerminationPoints(): array
{
    global $mintRuntimeRegistry;

    return $mintRuntimeRegistry['itemTerminationPoints'] ?? [];
}

function addNewTerminationPoints(array $names, $dbEntityRepositoryClass): void
{
    global $db;

    /* @var $dbEntityRepository \mint\DbEntityRepository */
    $dbEntityRepository = $dbEntityRepositoryClass::with($db);

    $terminationPoints = \mint\queryResultAsArray(
        $dbEntityRepository->get(),
        'id',
        'name'
    ) ?? [];

    $newValues = array_diff($names, $terminationPoints);

    if ($newValues) {
        $rows = [];

        foreach ($newValues as $newValue) {
            $rows[] = [
                'name' => $newValue,
            ];
        }

        $dbEntityRepository->insertMultiple($rows);
    }
}

function registerRewardSourceLegendEntries(array $rewardSources): void
{
    global $mintRuntimeRegistry;

    foreach ($rewardSources as $rewardSourceName => $rewardSource) {
        $rewardSource['name'] = $rewardSourceName;

        $mintRuntimeRegistry['rewardSourceLegendEntries'][$rewardSourceName] = $rewardSource;
    }
}

function getRegisteredRewardSourceLegendEntries(): array
{
    global $mintRuntimeRegistry;

    return $mintRuntimeRegistry['rewardSourceLegendEntries'] ?? [];
}

function registerRewardSources(array $rewardSources): void
{
    global $mintRuntimeRegistry;

    foreach ($rewardSources as $rewardSourceName => $rewardSource) {
        $rewardSource['name'] = $rewardSourceName;

        $mintRuntimeRegistry['rewardSources'][$rewardSourceName] = $rewardSource;
    }
}

function getRegisteredRewardSources(): array
{
    global $mintRuntimeRegistry;

    return $mintRuntimeRegistry['rewardSources'] ?? [];
}

function resolveRegisteredRewardSources(): void
{
    global $lang;

    $rewardSources = \mint\getRegisteredRewardSources();

    foreach ($rewardSources as $rewardSourceName => &$rewardSource) {
        if (!empty($rewardSource['terminationPoint'])) {
            \mint\registerCurrencyTerminationPoints([
                $rewardSource['terminationPoint'],
            ]);
        }

        if (!isset($rewardSource['reward'])) {
            $rewardSource['reward'] = function () use ($rewardSourceName) {
                return \mint\getSettingValue('reward_' . $rewardSourceName . '_value');
            };
        }

        $contentEntityTitle = &$lang->{'mint_content_entity_' . $rewardSourceName};

        if (isset($contentEntityTitle)) {
            if (!isset($rewardSource['registerValueSetting']) || $rewardSource['registerValueSetting'] === true) {
                \mint\registerSettings([
                    'reward_' . $rewardSourceName . '_value' => [
                        'title' => 'Content Entity Reward: ' . $contentEntityTitle,
                        'description' => '',
                        'optionscode' => 'numeric',
                        'value' => '0',
                    ],
                ]);
            }

            if (!isset($rewardSource['listInLegend']) || $rewardSource['listInLegend'] === true) {
                \mint\registerRewardSourceLegendEntries([
                    $rewardSourceName => [
                        'title' => $contentEntityTitle,
                        'reward' => $rewardSource['reward'],
                    ],
                ]);
            }
        }
    }

    \mint\registerRewardSources($rewardSources);
}

function registerItemTypesInteraction(array $itemTypeNames, ?array $details = null): void
{
    global $mintRuntimeRegistry;

    foreach ($itemTypeNames as $itemTypeName) {
        $mintRuntimeRegistry['itemInteractions'][$itemTypeName] = array_merge(
            $mintRuntimeRegistry['itemInteractions'][$itemTypeName] ?? [],
            [
                $details ?? [],
            ]
        );
    }
}

function getRegisteredItemTypeInteractions(): array
{
    global $mintRuntimeRegistry;

    return $mintRuntimeRegistry['itemInteractions'] ?? [];
}

// processing
function getMultipliedRewardValue(int $baseValue, ?float $multiplier): int
{
    $value = $baseValue;

    if (!is_null($multiplier)) {
        $value *= $multiplier;
    }

    $value = round($value);

    return $value;
}

// actions
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
    $query = $db->simple_select('users', 'uid, mint_balance', 'uid IN (' . \mint\getIntegerCsv($userIds) . ')');

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
