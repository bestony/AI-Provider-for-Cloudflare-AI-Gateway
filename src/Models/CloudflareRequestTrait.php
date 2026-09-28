<?php

/**
 * Request construction shared by Cloudflare AI Gateway models.
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider\Models;

if (!defined('ABSPATH')) {
    exit;
}

use CloudflareAiGateway\AiProvider\Provider\CloudflareProvider;
use CloudflareAiGateway\AiProvider\Util\CloudflareConfig;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;

trait CloudflareRequestTrait
{
    protected function createRequest(
        HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): Request {
        $headers['User-Agent'] = CloudflareConfig::getUserAgent();

        $gatewayId = CloudflareConfig::getGatewayId();
        if ($gatewayId !== '') {
            $headers['cf-aig-gateway-id'] = $gatewayId;
        }

        return new Request(
            $method,
            CloudflareProvider::url($path),
            $headers,
            $data,
            $this->getRequestOptions()
        );
    }
}
