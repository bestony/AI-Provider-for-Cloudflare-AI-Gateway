<?php

/**
 * Plugin Name:       AI Provider for Cloudflare AI Gateway
 * Plugin URI:        https://github.com/bestony/AI-Provider-for-Cloudflare-AI-Gateway
 * Description:       Cloudflare AI Gateway provider for the WordPress AI Client.
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Version:           1.0.1
 * Author:            Bestony
 * Author URI:        https://github.com/bestony
 * License:           GPL-2.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain:       ai-provider-for-cloudflare-ai-gateway
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider;

use CloudflareAiGateway\AiProvider\Admin\CloudflareSettings;
use CloudflareAiGateway\AiProvider\Provider\CloudflareProvider;
use CloudflareAiGateway\AiProvider\Util\CloudflareConfig;
use WordPress\AiClient\AiClient;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/autoload.php';

CloudflareSettings::register(__FILE__);

function register_provider(): void
{
    if (!class_exists(AiClient::class)) {
        return;
    }

    $registry = AiClient::defaultRegistry();
    if (!$registry->hasProvider(CloudflareProvider::class)) {
        $registry->registerProvider(CloudflareProvider::class);
    }
}

add_action('init', __NAMESPACE__ . '\\register_provider', 5);

/**
 * Puts the configured Cloudflare model first while preserving other providers' preferences.
 *
 * @param mixed $preferredModels
 * @return array<int, array{string, string}>
 */
function prefer_cloudflare_models($preferredModels): array
{
    $preferredList = is_array($preferredModels) ? array_values($preferredModels) : [];
    if (!CloudflareConfig::hasEndpoint() || !CloudflareConfig::hasCredentials()) {
        return $preferredList;
    }

    $defaultModel = CloudflareConfig::getDefaultModelId();
    if ($defaultModel === '') {
        return $preferredList;
    }

    $preferred = [[CloudflareConfig::PROVIDER_ID, $defaultModel]];
    foreach ($preferredList as $entry) {
        if (!is_array($entry) || count($entry) < 2) {
            continue;
        }

        $entry = array_values($entry);
        if (CloudflareConfig::PROVIDER_ID === $entry[0] && $defaultModel === $entry[1]) {
            continue;
        }

        $preferred[] = [$entry[0], $entry[1]];
    }

    return $preferred;
}

add_filter('wpai_preferred_text_models', __NAMESPACE__ . '\\prefer_cloudflare_models');
add_filter('wpai_preferred_vision_models', __NAMESPACE__ . '\\prefer_cloudflare_models');
