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
    }
}

function class_moderation_approve_posts(array $postIds): void
{
    global $db;

    if (!empty($postIds)) {
        $posts = \mint\queryResultAsArray(
            $db->simple_select('posts', '*', 'pid IN (' . implode(',', array_map('intval', $postIds)) . ')'),
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
            $db->simple_select('posts', '*', 'pid IN (' . implode(',', array_map('intval', $postIds)) . ')'),
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
        }
    }
}

function class_moderation_delete_post(int $postId): void
{
    \mint\voidContentEntityReward(
        'post',
        $postId
    );
}
