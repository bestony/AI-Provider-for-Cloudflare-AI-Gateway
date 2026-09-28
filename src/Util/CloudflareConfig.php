<?php

/**
 * Cloudflare AI Gateway configuration.
 *
 * WordPress options are read only when WordPress is available. Environment variables and PHP
 * constants remain useful for immutable deployments and override the admin settings.
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider\Util;

if (!defined('ABSPATH')) {
    exit;
}

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;

final class CloudflareConfig
{
    public const VERSION = '1.0.1';
    public const PROVIDER_ID = 'cloudflare_ai_gateway';
    public const OPTION_ACCOUNT_ID = 'cloudflare_ai_gateway_account_id';
    public const OPTION_BASE_URL = 'cloudflare_ai_gateway_base_url';
    public const OPTION_MODELS = 'cloudflare_ai_gateway_models';
    public const OPTION_GATEWAY_ID = 'cloudflare_ai_gateway_gateway_id';

    /**
     * REST API base URL template used when only the account ID is configured.
     */
    public const DEFAULT_BASE_URL = 'https://api.cloudflare.com/client/v4/accounts/{ACCOUNT_ID}/ai/v1';
    public const DEFAULT_MODEL = 'openai/gpt-4.1';

    public static function env(string $name): string
    {
        $value = getenv($name);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (defined($name)) {
            $constant = constant($name);
            if (is_scalar($constant)) {
                return (string) $constant;
            }
        }

        return '';
    }

    public static function getAccountId(): string
    {
        $configured = self::env('CLOUDFLARE_ACCOUNT_ID');
        if ($configured === '' && function_exists('get_option')) {
            $stored = get_option(self::OPTION_ACCOUNT_ID, '');
            $configured = is_string($stored) ? $stored : '';
        }

        $configured = strtolower(trim($configured));

        return self::isValidAccountId($configured) ? $configured : '';
    }

    public static function isValidAccountId(string $accountId): bool
    {
        return preg_match('/^[a-f0-9]{32}$/i', trim($accountId)) === 1;
    }

    public static function sanitizeAccountId($value): string
    {
        $value = is_string($value) ? strtolower(trim($value)) : '';

        return self::isValidAccountId($value) ? $value : '';
    }

    public static function getBaseUrl(): string
    {
        $configured = self::env('CLOUDFLARE_AI_GATEWAY_BASE_URL');
        if ($configured !== '') {
            return self::isValidBaseUrl($configured) ? self::normalizeBaseUrl($configured) : '';
        }

        if (function_exists('get_option')) {
            $stored = get_option(self::OPTION_BASE_URL, '');
            if (is_string($stored) && self::isValidBaseUrl($stored)) {
                return self::normalizeBaseUrl($stored);
            }
        }

        $accountId = self::getAccountId();
        if ($accountId === '') {
            return '';
        }

        return 'https://api.cloudflare.com/client/v4/accounts/' . rawurlencode($accountId) . '/ai/v1';
    }

    public static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');
        if (substr($url, -17) === '/chat/completions') {
            $url = substr($url, 0, -17);
        }

        return rtrim($url, '/');
    }

    public static function isValidBaseUrl(string $url): bool
    {
        $url = self::normalizeBaseUrl($url);
        if (
            $url === ''
            || strpos($url, '{') !== false
            || strpos($url, '}') !== false
            || !filter_var($url, FILTER_VALIDATE_URL)
        ) {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return false;
        }

        return !isset($parts['query'])
            && !isset($parts['fragment'])
            && !isset($parts['user'])
            && !isset($parts['pass']);
    }

    /**
     * @return list<string> Configured model IDs in Cloudflare's author/model format.
     */
    public static function getModelIds(): array
    {
        $configured = self::env('CLOUDFLARE_AI_GATEWAY_MODELS');
        if ($configured === '' && function_exists('get_option')) {
            $stored = get_option(self::OPTION_MODELS, '');
            $configured = is_string($stored) ? $stored : '';
        }

        $values = preg_split('/[\s,]+/', trim($configured), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($values) || $values === []) {
            return [self::DEFAULT_MODEL];
        }

        $models = [];
        foreach ($values as $value) {
            $value = trim($value);
            if (self::isValidModelId($value) && !in_array($value, $models, true)) {
                $models[] = $value;
            }
        }

        return $models === [] ? [self::DEFAULT_MODEL] : $models;
    }

    public static function sanitizeModelIds($value): string
    {
        $values = is_string($value) ? preg_split('/[\s,]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) : [];
        $models = [];
        if (is_array($values)) {
            foreach ($values as $model) {
                $model = trim((string) $model);
                if (self::isValidModelId($model) && !in_array($model, $models, true)) {
                    $models[] = $model;
                }
            }
        }

        return implode("\n", $models === [] ? [self::DEFAULT_MODEL] : array_slice($models, 0, 50));
    }

    public static function isValidModelId(string $modelId): bool
    {
        return preg_match('/^[A-Za-z0-9_@.\/-]+(?::[A-Za-z0-9_.-]+)?$/', $modelId) === 1;
    }

    public static function getGatewayId(): string
    {
        $configured = self::env('CLOUDFLARE_AI_GATEWAY_ID');
        if ($configured === '' && function_exists('get_option')) {
            $stored = get_option(self::OPTION_GATEWAY_ID, '');
            $configured = is_string($stored) ? $stored : '';
        }

        return preg_match('/^[A-Za-z0-9_-]+$/', $configured) === 1 ? $configured : '';
    }

    public static function hasEndpoint(): bool
    {
        $override = self::env('CLOUDFLARE_AI_GATEWAY_BASE_URL');
        if ($override !== '') {
            return self::isValidBaseUrl($override);
        }

        if (function_exists('get_option')) {
            $stored = get_option(self::OPTION_BASE_URL, '');
            if (is_string($stored) && $stored !== '') {
                return self::isValidBaseUrl($stored);
            }
        }

        return self::getAccountId() !== '';
    }

    public static function hasCredentials(): bool
    {
        if (!class_exists(AiClient::class)) {
            return false;
        }

        $registry = AiClient::defaultRegistry();
        if (!$registry->hasProvider(self::PROVIDER_ID)) {
            return false;
        }

        return $registry->getProviderRequestAuthentication(self::PROVIDER_ID) !== null;
    }

    public static function createRequestOptions(): RequestOptions
    {
        $options = new RequestOptions();
        $timeout = (float) (self::env('CLOUDFLARE_AI_GATEWAY_REQUEST_TIMEOUT') ?: 120);
        $connectTimeout = (float) (self::env('CLOUDFLARE_AI_GATEWAY_CONNECT_TIMEOUT') ?: 10);
        $options->setTimeout(max(1.0, $timeout));
        $options->setConnectTimeout(max(1.0, $connectTimeout));

        return $options;
    }

    public static function getStructuredOutputMode(): string
    {
        $mode = strtolower(self::env('CLOUDFLARE_AI_GATEWAY_STRUCTURED_OUTPUT'));

        return in_array($mode, ['json_schema', 'json_object', 'none'], true) ? $mode : 'json_schema';
    }

    public static function declaresImageInput(): bool
    {
        $modalities = self::env('CLOUDFLARE_AI_GATEWAY_MODEL_INPUT_MODALITIES');
        $modalities = array_map('trim', explode(',', strtolower($modalities)));

        return in_array('image', $modalities, true);
    }

    public static function getDefaultModelId(): string
    {
        return self::getModelIds()[0] ?? self::DEFAULT_MODEL;
    }

    public static function getUserAgent(): string
    {
        return 'ai-provider-for-cloudflare-ai-gateway/' . self::VERSION;
    }
}
