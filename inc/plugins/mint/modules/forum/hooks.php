<?php

namespace mint\modules\forum\Hooks;

function datahandler_post_insert_post_end(\PostDataHandler $PostDataHandler): void
{
    if (
        $PostDataHandler->data['uid'] != 0 &&
        $PostDataHandler->method == 'insert' &&
        $PostDataHandler->return_values['visible'] == 1
    ) {
        if (strlen($PostDataHandler->data['message']) > \mint\getSettingValue('reward_post_min_characters')) {
            \mint\addContentEntityReward(
                'post',
                $PostDataHandler->return_values['pid'],
                $PostDataHandler->data['uid']
            );
        }

        $thread = \get_thread($PostDataHandler->data['tid']);

        if ($thread['uid'] != $PostDataHandler->data['uid']) {
            \mint\addContentEntityReward(
                'thread_reply',
                $PostDataHandler->return_values['pid'],
                $thread['uid']
            );
        }
    }
}

function class_moderation_approve_posts(array $postIds): void
{
    global $db;

    if (!empty($postIds)) {
        $posts = \mint\queryResultAsArray(
            $db->simple_select('posts', '*', 'pid IN (' . \mint\getIntegerCsv($postIds) . ')'),
            'pid'
        );

        foreach ($postIds as $postId) {
            if (isset($posts[$postId])) {
                $post = $posts[$postId];

                if (
                    $post['uid'] != 0 &&
                    $post['visible'] == 1
                ) {
                    if (strlen($post['message']) > \mint\getSettingValue('reward_post_min_characters')) {
                        \mint\addContentEntityReward(
                            'post',
                            $post['pid'],
                            $post['uid']
                        );
                    }

                    $thread = \get_thread($post['tid']);

                    if ($thread['uid'] != $post['uid']) {
                        \mint\addContentEntityReward(
                            'thread_reply',
                            $post['pid'],
                            $thread['uid']
                        );
                    }
                }
            }
        }
    }
}

function class_moderation_restore_posts(array $postIds): void
{
    global $db;

    if (!empty($postIds)) {
        $posts = \mint\queryResultAsArray(
            $db->simple_select('posts', '*', 'pid IN (' . \mint\getIntegerCsv($postIds) . ')'),
            'pid'
        );

        foreach ($postIds as $postId) {
            if (isset($posts[$postId])) {
                $post = $posts[$postId];

                if (
                    $post['uid'] != 0 &&
                    $post['visible'] == 1
                ) {
                    if (strlen($post['message']) > \mint\getSettingValue('reward_post_min_characters')) {
                        \mint\addContentEntityReward(
                            'post',
                            $post['pid'],
                            $post['uid']
                        );
                    }

                    $thread = \get_thread($post['tid']);

                    if ($thread['uid'] != $post['uid']) {
                        \mint\addContentEntityReward(
                            'thread_reply',
                            $post['pid'],
                            $thread['uid']
                        );
                    }
                }
            }
        }
    }
}

function class_moderation_unapprove_posts(array $postIds): void
{
    if (!empty($postIds)) {
        foreach ($postIds as $postId) {
            \mint\voidContentEntityReward(
                'post',
                $postId
            );

            \mint\voidContentEntityReward(
                'thread_reply',
                $postId
            );
        }
    }
}

function class_moderation_soft_delete_posts(array $postIds): void
{
    if (!empty($postIds)) {
        foreach ($postIds as $postId) {
            \mint\voidContentEntityReward(
                'post',
                $postId
            );

            \mint\voidContentEntityReward(
                'thread_reply',
                $postId
            );
        }
    }
}

function class_moderation_delete_post(int $postId): void
{
    \mint\voidContentEntityReward(
        'post',
        $postId
    );

    \mint\voidContentEntityReward(
        'thread_reply',
        $postId
    );
}

function datahandler_post_insert_thread_end(\PostDataHandler $PostDataHandler): void
{
    if (
        $PostDataHandler->data['uid'] != 0 &&
        $PostDataHandler->method == 'insert' &&
        $PostDataHandler->return_values['visible'] == 1
    ) {
        if (strlen($PostDataHandler->data['message']) > \mint\getSettingValue('reward_thread_min_characters')) {
            \mint\addContentEntityReward(
                'thread',
                $PostDataHandler->return_values['tid'],
                $PostDataHandler->data['uid']
            );
        }
    }
}

function class_moderation_approve_threads(array $threadIds): void
{
    global $db;

    if (!empty($threadIds)) {
        $threads = \mint\queryResultAsArray(
            $db->simple_select('threads', '*', 'tid IN (' . \mint\getIntegerCsv($threadIds) . ')'),
            'tid'
        );

        foreach ($threadIds as $threadId) {
            if (isset($threads[$threadId])) {
                $thread = $threads[$threadId];

                if (
                    $thread['uid'] != 0 &&
                    $thread['visible'] == 1
                ) {
                    \mint\addContentEntityReward(
                        'thread',
                        $thread['tid'],
                        $thread['uid']
                    );
                }
            }
        }
    }
}

function class_moderation_restore_threads(array $threadIds): void
{
    global $db;

    if (!empty($threadIds)) {
        $threads = \mint\queryResultAsArray(
            $db->simple_select('threads', '*', 'tid IN (' . \mint\getIntegerCsv($threadIds) . ')'),
            'tid'
        );

        foreach ($threadIds as $threadId) {
            if (isset($threads[$threadId])) {
                $thread = $threads[$threadId];

                if (
                    $thread['uid'] != 0 &&
                    $thread['visible'] == 1
                ) {
                    \mint\addContentEntityReward(
                        'thread',
                        $thread['tid'],
                        $thread['uid']
                    );
                }
            }
        }
    }
}

function class_moderation_unapprove_threads(array $threadIds): void
{
    if (!empty($threadIds)) {
        foreach ($threadIds as $threadId) {
            \mint\voidContentEntityReward(
                'thread',
                $threadId
            );
        }
    }
}

function class_moderation_soft_delete_threads(array $threadIds): void
{
    if (!empty($threadIds)) {
        foreach ($threadIds as $threadId) {
            \mint\voidContentEntityReward(
                'thread',
                $threadId
            );
        }
    }
}

function class_moderation_delete_thread(int $threadId): void
{
    \mint\voidContentEntityReward(
        'thread',
        $threadId
    );
}

function datahandler_user_insert_end(\UserDataHandler $UserDataHandler): void
{
    if ($UserDataHandler->user_insert_data['usergroup'] != 5) {
        \mint\addContentEntityReward(
            'user_activation',
            $UserDataHandler->return_values['uid'],
            $UserDataHandler->return_values['uid']
        );

        if (!empty($UserDataHandler->user_insert_data['referrer'])) {
            \mint\addContentEntityReward(
                'referred_user_activation',
                $UserDataHandler->return_values['uid'],
                $UserDataHandler->return_values['referrer']
            );
        }
    }
}

function member_activate_accountactivated(): void
{
    global $user;

    \mint\addContentEntityReward(
        'user_activation',
        $user['uid'],
        $user['uid']
    );

    if (!empty($user['referrer'])) {
        \mint\addContentEntityReward(
            'referred_user_activation',
            $user['uid'],
            $user['referrer']
        );
    }
}

function admin_user_users_coppa_activate_commit(): void
{
    global $user;

    \mint\addContentEntityReward(
        'user_activation',
        $user['uid'],
        $user['uid']
    );

    if (!empty($user['referrer'])) {
        \mint\addContentEntityReward(
            'referred_user_activation',
            $user['uid'],
            $user['referrer']
        );
    }
}

function admin_user_awaiting_activation_activate_commit()
{
    global $user_ids;

    $userIds = explode(', ', $user_ids);

    $users = \mint\getUsersById($userIds, 'uid,referrer');

    foreach ($userIds as $id) {
        if (isset($users[$id])) {
            \mint\addContentEntityReward(
                'user_activation',
                $id,
                $id
            );

            if (!empty($users[$id]['referrer'])) {
                \mint\addContentEntityReward(
                    'referred_user_activation',
                    $id,
                    $users[$id]['referrer']
                );
            }
        }
    }
}