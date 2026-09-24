<?php

/**
 * OpenAI Chat Completions model for Cloudflare AI Gateway.
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider\Models;

use CloudflareAiGateway\AiProvider\Util\CloudflareConfig;
use CloudflareAiGateway\AiProvider\Util\CloudflareModelCatalog;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;

final class CloudflareTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel
{
    use CloudflareRequestTrait;

    protected function prepareGenerateTextParams(array $prompt): array
    {
        $params = parent::prepareGenerateTextParams($prompt);

        if (isset($params['response_format']) && $params['response_format'] === []) {
            unset($params['response_format']);
        }

        if (CloudflareModelCatalog::rejectsSamplingParameters($this->metadata()->getId())) {
            unset(
                $params['temperature'],
                $params['top_p'],
                $params['presence_penalty'],
                $params['frequency_penalty'],
                $params['logprobs'],
                $params['top_logprobs']
            );
        }

        return $params;
    }

    protected function prepareResponseFormatParam(?array $outputSchema): array
    {
        $mode = CloudflareConfig::getStructuredOutputMode();
        if ($mode === 'none') {
            return [];
        }

        if ($mode === 'json_object' || !is_array($outputSchema)) {
            return ['type' => 'json_object'];
        }

        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'cloudflare_response',
                'schema' => $outputSchema,
            ],
        ];
    }
}
