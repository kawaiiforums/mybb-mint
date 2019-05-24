<?php

namespace mint\modules\forum;

require_once __DIR__ . '/hooks.php';

\mint\addHooksNamespace('mint\modules\forum\Hooks');

\mint\loadModuleLanguageFile('forum', 'forum');

\mint\registerSettings([
    'reward_post_min_characters' => [
        'title' => 'Content Entity Reward: Post - Minimum Characters',
        'description' => 'Choose how many characters are required to award authors.',
        'optionscode' => 'numeric',
        'value' => '0',
    ],
    'reward_thread_min_characters' => [
        'title' => 'Content Entity Reward: Thread - Minimum Characters',
        'description' => 'Choose how many characters are required to award authors.',
        'optionscode' => 'numeric',
        'value' => '0',
    ],
]);

\mint\registerRewardSources([
    'post' => [
        'contentType' => 'post',
        'terminationPoint' => 'rewards.post',
    ],
    'thread' => [
        'contentType' => 'thread',
        'terminationPoint' => 'rewards.thread',
    ],
]);
