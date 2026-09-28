<?php

/**
 * Local Cloudflare provider availability check.
 *
 * Cloudflare's REST API does not expose an OpenAI-compatible `/models` endpoint at the configured
 * chat base URL. Availability therefore checks the two local prerequisites and leaves the first
 * inference request to validate the token and model.
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider\Provider;

if (!defined('ABSPATH')) {
    exit;
}

use CloudflareAiGateway\AiProvider\Util\CloudflareConfig;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;

final class CloudflareProviderAvailability implements ProviderAvailabilityInterface
{
    public function isConfigured(): bool
    {
        return CloudflareConfig::hasEndpoint() && CloudflareConfig::hasCredentials();
    }
}
