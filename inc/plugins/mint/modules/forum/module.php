<?php

namespace mint\modules\forum;

// hook files
require_once __DIR__ . '/hooks.php';

// hooks
\mint\addHooksNamespace('mint\modules\forum\Hooks');

// init
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
    'user_activation' => [
        'contentType' => 'user_activation',
        'terminationPoint' => 'rewards.user_activation',
    ],
    'referred_user_activation' => [
        'contentType' => 'user_activation',
        'terminationPoint' => 'rewards.referred_user_activation',
    ],
    'post' => [
        'contentType' => 'post',
        'terminationPoint' => 'rewards.post',
    ],
    'thread' => [
        'contentType' => 'thread',
        'terminationPoint' => 'rewards.thread',
    ],
    'thread_reply' => [
        'contentType' => 'thread_reply',
        'terminationPoint' => 'rewards.thread_reply',
    ],
]);
