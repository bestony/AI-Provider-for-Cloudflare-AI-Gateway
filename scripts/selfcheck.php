<?php

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/../');
require_once __DIR__ . '/../src/autoload.php';

use CloudflareAiGateway\AiProvider\Util\CloudflareConfig;
use CloudflareAiGateway\AiProvider\Util\CloudflareModelCatalog;
use CloudflareAiGateway\AiProvider\Admin\CloudflareSettings;

$checks = 0;
$failures = 0;

/**
 * @param bool $condition
 * @param string $description
 */
function check(bool $condition, string $description): void
{
    global $checks, $failures;
    $checks++;
    if ($condition) {
        fwrite(STDOUT, "ok    {$description}\n");
        return;
    }

    $failures++;
    fwrite(STDERR, "FAIL  {$description}\n");
}

putenv('CLOUDFLARE_ACCOUNT_ID');
putenv('CLOUDFLARE_AI_GATEWAY_BASE_URL');
putenv('CLOUDFLARE_AI_GATEWAY_MODELS');
putenv('CLOUDFLARE_AI_GATEWAY_ID');

check(!CloudflareConfig::isValidAccountId('not-an-account'), 'invalid account ID is rejected');
check(
    CloudflareConfig::isValidAccountId('0123456789abcdef0123456789abcdef'),
    '32-character account ID is accepted'
);
check(
    CloudflareConfig::isValidBaseUrl('https://api.cloudflare.com/client/v4/accounts/example/ai/v1'),
    'HTTPS base URL is accepted'
);
check(!CloudflareConfig::isValidBaseUrl('http://example.test/v1'), 'HTTP base URL is rejected');
check(!CloudflareConfig::isValidBaseUrl('https://example.test/v1?token=secret'), 'URLs with query strings are rejected');
check(
    CloudflareSettings::sanitizeBaseUrl('https://ai.example.test/openai/v1/chat/completions') === 'https://ai.example.test/openai/v1',
    'settings sanitizer stores a complete base URL without the operation path'
);

putenv('CLOUDFLARE_ACCOUNT_ID=0123456789abcdef0123456789abcdef');
check(
    CloudflareConfig::getBaseUrl() === 'https://api.cloudflare.com/client/v4/accounts/0123456789abcdef0123456789abcdef/ai/v1',
    'account ID builds the REST API base URL'
);
check(CloudflareConfig::hasEndpoint(), 'valid account ID enables the endpoint');

putenv('CLOUDFLARE_AI_GATEWAY_BASE_URL=https://ai.example.test/openai/v1');
check(
    CloudflareConfig::getBaseUrl() === 'https://ai.example.test/openai/v1',
    'complete base URL overrides account URL'
);
check(
    CloudflareConfig::normalizeBaseUrl('https://ai.example.test/openai/v1/chat/completions') === 'https://ai.example.test/openai/v1',
    'chat completions suffix is normalized'
);

putenv('CLOUDFLARE_AI_GATEWAY_MODELS=openai/gpt-4.1, anthropic/claude-sonnet-4');
check(
    CloudflareConfig::getModelIds() === ['openai/gpt-4.1', 'anthropic/claude-sonnet-4'],
    'model IDs are parsed from environment configuration'
);
check(
    CloudflareConfig::sanitizeModelIds("openai/gpt-4.1\nanthropic/claude-sonnet-4") === "openai/gpt-4.1\nanthropic/claude-sonnet-4",
    'model IDs are normalized for the settings field'
);
check(CloudflareModelCatalog::supportsImageInput('openai/gpt-4.1'), 'known vision model is marked as vision capable');
check(CloudflareModelCatalog::rejectsSamplingParameters('openai/gpt-5.1'), 'reasoning model sampling is disabled');
check(CloudflareModelCatalog::rejectsSamplingParameters('gpt-5.1'), 'unprefixed reasoning model sampling is disabled');
check(!CloudflareModelCatalog::rejectsSamplingParameters('openai/gpt-4.1'), 'regular model keeps sampling options');
check(CloudflareModelCatalog::isPreviewOrFree('model:free'), 'free model is classified');
check(CloudflareConfig::getUserAgent() === 'ai-provider-for-cloudflare-ai-gateway/1.0.0', 'user agent contains plugin version');
check(!CloudflareConfig::hasCredentials(), 'without the AI Client no credential is reported');

$sdkPath = null;
foreach ($argv as $argument) {
    if (strpos($argument, '--sdk=') === 0) {
        $sdkPath = substr($argument, 6);
    }
}

if ($sdkPath !== null && is_file($sdkPath . '/polyfills.php')) {
    require $sdkPath . '/polyfills.php';
    spl_autoload_register(static function (string $class) use ($sdkPath): void {
        $prefix = 'WordPress\\AiClient\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }

        $file = $sdkPath . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });

    use_sdk_checks();
} else {
    fwrite(STDOUT, "skip  SDK-dependent checks (pass --sdk=<path to php-ai-client/src> to run them)\n");
}

putenv('CLOUDFLARE_ACCOUNT_ID');
putenv('CLOUDFLARE_AI_GATEWAY_BASE_URL');
putenv('CLOUDFLARE_AI_GATEWAY_MODELS');
putenv('CLOUDFLARE_AI_GATEWAY_ID');

fwrite(STDOUT, sprintf("\n%d checks, %d failure(s)\n", $checks, $failures));
exit($failures === 0 ? 0 : 1);

/**
 * Exercises the provider against the real SDK classes with a fake transport.
 *
 * @return void
 */
function use_sdk_checks(): void
{
    $provider = \CloudflareAiGateway\AiProvider\Provider\CloudflareProvider::class;
    $directory = $provider::modelMetadataDirectory();
    $models = $directory->listModelMetadata();
    check(count($models) === 2, 'configured model list becomes SDK metadata');
    check($models[0]->getId() === 'openai/gpt-4.1', 'first configured model is preferred');
    putenv('CLOUDFLARE_AI_GATEWAY_MODELS=openai/gpt-4.1');
    $updatedDirectory = new \CloudflareAiGateway\AiProvider\Metadata\CloudflareModelMetadataDirectory();
    check(count($updatedDirectory->listModelMetadata()) === 1, 'model metadata cache changes when settings change');
    putenv('CLOUDFLARE_AI_GATEWAY_MODELS=openai/gpt-4.1, anthropic/claude-sonnet-4');

    $transporter = new class implements \WordPress\AiClient\Providers\Http\Contracts\HttpTransporterInterface {
        /** @var \WordPress\AiClient\Providers\Http\DTO\Request|null */
        public $request = null;

        public function send(
            \WordPress\AiClient\Providers\Http\DTO\Request $request,
            ?\WordPress\AiClient\Providers\Http\DTO\RequestOptions $options = null
        ): \WordPress\AiClient\Providers\Http\DTO\Response {
            $this->request = $request;

            return new \WordPress\AiClient\Providers\Http\DTO\Response(
                200,
                [],
                json_encode([
                    'id' => 'chatcmpl-selfcheck',
                    'choices' => [[
                        'message' => ['role' => 'assistant', 'content' => 'ok'],
                        'finish_reason' => 'stop',
                    ]],
                    'usage' => ['prompt_tokens' => 2, 'completion_tokens' => 1, 'total_tokens' => 3],
                ])
            );
        }
    };

    $registry = \WordPress\AiClient\AiClient::defaultRegistry();
    $registry->setHttpTransporter($transporter);
    if (!$registry->hasProvider($provider)) {
        $registry->registerProvider($provider);
    }
    check(!CloudflareConfig::hasCredentials(), 'registered provider has no credential by default');
    $registry->setProviderRequestAuthentication(
        CloudflareConfig::PROVIDER_ID,
        new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication('selfcheck-token')
    );
    check(CloudflareConfig::hasCredentials(), 'credential presence comes from the AI Client registry');

    $model = new \CloudflareAiGateway\AiProvider\Models\CloudflareTextGenerationModel(
        $directory->getModelMetadata('openai/gpt-4.1'),
        $provider::metadata()
    );
    $model->setHttpTransporter($transporter);
    $model->setRequestAuthentication(
        new \WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication('selfcheck-token')
    );
    $model->setRequestOptions(CloudflareConfig::createRequestOptions());
    $model->setConfig(\WordPress\AiClient\Providers\Models\DTO\ModelConfig::fromArray([
        'maxTokens' => 64,
        'outputMimeType' => 'application/json',
        'outputSchema' => ['type' => 'object', 'properties' => ['answer' => ['type' => 'string']]],
    ]));
    $model->generateTextResult([
        new \WordPress\AiClient\Messages\DTO\Message(
            \WordPress\AiClient\Messages\Enums\MessageRoleEnum::user(),
            [new \WordPress\AiClient\Messages\DTO\MessagePart('Hello')]
        ),
    ]);

    check(
        $transporter->request->getUri() === 'https://ai.example.test/openai/v1/chat/completions',
        'generation request appends chat completions to the configured URL'
    );
    $headers = $transporter->request->getHeaders();
    check(
        isset($headers['Authorization'][0]) && $headers['Authorization'][0] === 'Bearer selfcheck-token',
        'generation request carries the AI Client credential'
    );
    $body = json_decode((string) $transporter->request->getBody(), true);
    check(
        isset($body['response_format']['json_schema']['name'], $body['response_format']['json_schema']['schema']),
        'structured output uses the named OpenAI JSON schema wrapper'
    );
}
