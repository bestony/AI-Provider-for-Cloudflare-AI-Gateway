<?php

/**
 * Model capability helpers.
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider\Util;

if (!defined('ABSPATH')) {
    exit;
}

final class CloudflareModelCatalog
{
    public static function supportsImageInput(string $modelId): bool
    {
        if (CloudflareConfig::declaresImageInput()) {
            return true;
        }

        $shortId = self::shortId($modelId);

        return preg_match('/(?:vision|vl|gpt-4o|gpt-4\.1|gemini|claude-3|claude-sonnet-4)/', $shortId) === 1;
    }

    public static function rejectsSamplingParameters(string $modelId): bool
    {
        $shortId = self::shortId($modelId);

        return preg_match('/^(?:gpt-5(?:\.|-|$)|o[134](?:-|$)|codex(?:-|$))/', $shortId) === 1;
    }

    public static function compareModelIds(string $first, string $second): int
    {
        $firstPreview = self::isPreviewOrFree($first) ? 1 : 0;
        $secondPreview = self::isPreviewOrFree($second) ? 1 : 0;
        if ($firstPreview !== $secondPreview) {
            return $firstPreview <=> $secondPreview;
        }

        return strnatcasecmp($first, $second);
    }

    public static function isPreviewOrFree(string $modelId): bool
    {
        return stripos($modelId, 'preview') !== false
            || stripos($modelId, 'experimental') !== false
            || strpos($modelId, ':free') !== false;
    }

    private static function shortId(string $modelId): string
    {
        $position = strrpos($modelId, '/');

        return strtolower($position === false ? $modelId : substr($modelId, $position + 1));
    }
}
