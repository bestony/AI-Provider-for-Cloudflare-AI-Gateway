<?php

/**
 * Cloudflare AI Gateway provider.
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider\Provider;

use CloudflareAiGateway\AiProvider\Metadata\CloudflareModelMetadataDirectory;
use CloudflareAiGateway\AiProvider\Models\CloudflareTextGenerationModel;
use CloudflareAiGateway\AiProvider\Util\CloudflareConfig;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

final class CloudflareProvider extends AbstractApiProvider
{
    protected static function baseUrl(): string
    {
        return CloudflareConfig::getBaseUrl();
    }

    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        foreach ($modelMetadata->getSupportedCapabilities() as $capability) {
            if ($capability->isTextGeneration()) {
                $model = new CloudflareTextGenerationModel($modelMetadata, $providerMetadata);
                $model->setRequestOptions(CloudflareConfig::createRequestOptions());

                return $model;
            }
        }

        throw new RuntimeException(
            sprintf(
                /* translators: %s: model ID. */
                esc_html__('The model "%s" has no supported capability for Cloudflare AI Gateway.', 'ai-provider-for-cloudflare-ai-gateway'),
                esc_html($modelMetadata->getId())
            )
        );
    }

    protected static function createProviderMetadata(): ProviderMetadata
    {
        $args = [
            CloudflareConfig::PROVIDER_ID,
            'Cloudflare AI Gateway',
            ProviderTypeEnum::cloud(),
            'https://dash.cloudflare.com/profile/api-tokens',
            RequestAuthenticationMethod::apiKey(),
        ];

        if (version_compare(AiClient::VERSION, '1.2.0', '>=')) {
            $description = 'OpenAI-compatible chat completions through Cloudflare AI Gateway.';
            $args[] = function_exists('__')
                ? __('OpenAI-compatible chat completions through Cloudflare AI Gateway.', 'ai-provider-for-cloudflare-ai-gateway')
                : $description;
        }

        if (version_compare(AiClient::VERSION, '1.3.0', '>=')) {
            $args[] = dirname(__DIR__, 2) . '/assets/images/cloudflare.svg';
        }

        return new ProviderMetadata(...$args);
    }

    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        return new CloudflareProviderAvailability();
    }

    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new CloudflareModelMetadataDirectory();
    }
}
