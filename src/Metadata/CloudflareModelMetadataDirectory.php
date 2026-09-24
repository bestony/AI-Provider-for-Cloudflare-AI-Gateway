<?php

/**
 * Model metadata for the configured Cloudflare model IDs.
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider\Metadata;

use CloudflareAiGateway\AiProvider\Provider\CloudflareProvider;
use CloudflareAiGateway\AiProvider\Util\CloudflareConfig;
use CloudflareAiGateway\AiProvider\Util\CloudflareModelCatalog;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;

final class CloudflareModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory
{
    /**
     * The REST API model catalog is account-scoped and is not an OpenAI `/models` endpoint. The
     * settings page therefore owns the explicit model list used by this provider.
     *
     * @return array<string, ModelMetadata>
     */
    protected function sendListModelsRequest(): array
    {
        $models = [];
        foreach (CloudflareConfig::getModelIds() as $modelId) {
            $models[$modelId] = $this->createMetadata($modelId);
        }

        uksort(
            $models,
            static function (string $first, string $second): int {
                $preferred = CloudflareConfig::getDefaultModelId();
                if ($first === $preferred || $second === $preferred) {
                    return ($first === $preferred ? 0 : 1) <=> ($second === $preferred ? 0 : 1);
                }

                return CloudflareModelCatalog::compareModelIds($first, $second);
            }
        );

        return $models;
    }

    /**
     * Required by the OpenAI-compatible base class. Cloudflare model metadata is configured locally,
     * so the parser is intentionally unused unless a future endpoint implementation opts in.
     *
     * @param Response $response
     * @return list<ModelMetadata>
     */
    protected function parseResponseToModelMetadataList(Response $response): array
    {
        return [];
    }

    private function createMetadata(string $modelId): ModelMetadata
    {
        $inputModalities = [[ModalityEnum::text()]];
        if (CloudflareModelCatalog::supportsImageInput($modelId)) {
            $inputModalities[] = [ModalityEnum::text(), ModalityEnum::image()];
        }

        return new ModelMetadata(
            $modelId,
            $modelId,
            [
                CapabilityEnum::textGeneration(),
                CapabilityEnum::chatHistory(),
            ],
            [
                new SupportedOption(OptionEnum::systemInstruction()),
                new SupportedOption(OptionEnum::maxTokens()),
                new SupportedOption(OptionEnum::stopSequences()),
                new SupportedOption(OptionEnum::outputMimeType(), ['text/plain', 'application/json']),
                new SupportedOption(OptionEnum::outputSchema()),
                new SupportedOption(OptionEnum::functionDeclarations()),
                new SupportedOption(OptionEnum::customOptions()),
                new SupportedOption(OptionEnum::inputModalities(), $inputModalities),
                new SupportedOption(OptionEnum::outputModalities(), [[ModalityEnum::text()]]),
                new SupportedOption(OptionEnum::candidateCount()),
                new SupportedOption(OptionEnum::temperature()),
                new SupportedOption(OptionEnum::topP()),
                new SupportedOption(OptionEnum::presencePenalty()),
                new SupportedOption(OptionEnum::frequencyPenalty()),
                new SupportedOption(OptionEnum::logprobs()),
                new SupportedOption(OptionEnum::topLogprobs()),
            ]
        );
    }

    /**
     * Creates metadata for a model ID supplied directly by a caller. This keeps explicit model use
     * working even when the model was added after the settings page was last saved.
     *
     * @param list<string> $modelIds
     * @return array<string, ModelMetadata>
     */
    protected function createModelMetadataForExplicitModelIds(array $modelIds): array
    {
        $metadata = [];
        foreach ($modelIds as $modelId) {
            if (CloudflareConfig::isValidModelId($modelId)) {
                $metadata[$modelId] = $this->createMetadata($modelId);
            }
        }

        return $metadata;
    }

    /**
     * This method is required by the OpenAI-compatible base class. Model listing is handled locally,
     * so no request is sent here.
     */
    protected function createRequest(
        \WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum $method,
        string $path,
        array $headers = [],
        $data = null
    ): \WordPress\AiClient\Providers\Http\DTO\Request {
        return new \WordPress\AiClient\Providers\Http\DTO\Request(
            $method,
            CloudflareProvider::url($path),
            $headers,
            $data,
            CloudflareConfig::createRequestOptions()
        );
    }
}
