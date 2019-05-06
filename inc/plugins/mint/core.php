<?php

namespace mint;

use \mint\DbRepository\{
    CurrencyTerminationPoints
};

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

    $mintRuntimeRegistry['rewardSourceLegendEntries'] = array_unique(
        array_merge($mintRuntimeRegistry['rewardSourceLegendEntries'] ?? [], $rewardSources)
    );
}

function getRegisteredRewardSourceLegendEntries(): array
{
    global $mintRuntimeRegistry;

    return $mintRuntimeRegistry['rewardSourceLegendEntries'] ?? [];
}

function loadModuleLanguageFile(string $moduleName, string $section): void
{
    \mint\loadExternalLanguageFile('inc/plugins/mint/modules/' . $moduleName . '/languages', $section);
}

function registerRewardSources(array $rewardSources): void
{
    global $mintRuntimeRegistry;

    $mintRuntimeRegistry['rewardSources'] = array_unique(
        array_merge($mintRuntimeRegistry['rewardSources'] ?? [], $rewardSources)
    );
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
                    'post' => [
                        'title' => $contentEntityTitle,
                        'reward' => $rewardSource['reward'],
                    ],
                ]);
            }
        }
    }

    \mint\registerRewardSources($rewardSources);
}

// users
function userOnIgnoreList(int $subjectUserId, $targetUser): bool
{
    if (!is_array($targetUser)) {
        $targetUser = \get_user($targetUser);
    }

    return (
        !empty($targetUser['ignorelist']) &&
        strpos(',' . $targetUser['ignorelist'] . ',', ',' . $subjectUserId . ',') !== false
    );
}
