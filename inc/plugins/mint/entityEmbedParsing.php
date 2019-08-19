<?php

namespace mint\entityEmbedParsing;

function getMatches(string $message, string $name, ?int $limit = null): array
{
    $messageContent = $message;

    // strip default tags
    $messageContent = preg_replace('/\[(code|php)(=[^\]]*)?\](.*?)\[\/\1\]/si', null, $message);

    $regex = '/\[' . $name . '=(\d+)\]/u';

    preg_match_all($regex, $messageContent, $regexMatchSets, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $matches = [];

    if (
        !empty($regexMatchSets) &&
        ($limit === null || count($regexMatchSets) <= $limit)
    ) {
        foreach ($regexMatchSets as $regexMatchSet) {
            $matches[] = [
                'full' => $regexMatchSet[0][0],
                'offset' => $regexMatchSet[0][1],
                'id' => $regexMatchSet[1][0],
            ];
        }
    }

    return $matches;
}

function getMessageWithPlaceholders(string $message, string $name, array $matches, array &$placeholders = []): string
{
    foreach ($matches as &$match) {
        $fingerprint = [
            'id' => $match['id'],
            'full' => $match['full'],
        ];

        $placeholderId = array_search($fingerprint, $placeholders);

        if ($placeholderId === false) {
            $placeholderId = count($placeholders);
            $placeholders[] = $fingerprint;
        }

        $match['replacement'] = '<' . $name . '#' . $placeholderId . '>';
    }

    $message = \mint\entityEmbedParsing\replaceMatchesInMessage($message, $matches);

    return $message;
}

function replaceMatchesInMessage(string $message, array $matches): string
{
    $correction = 0;

    foreach ($matches as $match) {
        // offset, call character, correction
        $start = $match['offset'] + $correction;

        $length = strlen($match['full']);

        $message = substr_replace($message, $match['replacement'], $start, $length);

        $correction += strlen($match['replacement']) - $length;
    }

    return $message;
}

function getFormattedMessageFromPlaceholders(string $message, string $name, array $placeholders, callable $entityProvider, callable $formatter, int $limit = null): string
{
    $ids = array_unique(array_column($placeholders, 'id'));

    $entities = [];

    if (
        !empty($ids) &&
        ($limit === null || count($ids) <= $limit)
    ) {
        if (!empty($ids)) {
            $entities = $entityProvider($ids);
        }
    }

    return \mint\entityEmbedParsing\getFormattedMessageFromPlaceholdersAndEntities($message, $name, $placeholders, $entities, $formatter);
}

function getFormattedMessageFromPlaceholdersAndEntities(string $content, string $name, array $placeholders, array $entities, callable $formatter): string
{
    foreach ($placeholders as $index => $fingerprint) {
        $entity = $entities[ (int)$fingerprint['id'] ] ?? null;

        if ($entity) {
            $replacement = $formatter($entity);
        } else {
            $replacement = $fingerprint['full'];
        }

        $content = str_replace('<' . $name . '#' . $index . '>', $replacement, $content);
    }

    return $content;
}
