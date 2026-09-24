<?php

/**
 * Cloudflare AI Gateway settings page.
 *
 * @package CloudflareAiGateway\AiProvider
 */

declare(strict_types=1);

namespace CloudflareAiGateway\AiProvider\Admin;

use CloudflareAiGateway\AiProvider\Util\CloudflareConfig;

final class CloudflareSettings
{
    public const OPTION_GROUP = 'cloudflare_ai_gateway_settings';
    public const PAGE_SLUG = 'cloudflare-ai-gateway-settings';

    public static function register(string $pluginFile): void
    {
        add_action('admin_init', [self::class, 'registerSettings']);
        add_action('admin_menu', [self::class, 'addPage']);
        add_filter(
            'plugin_action_links_' . plugin_basename($pluginFile),
            [self::class, 'addActionLink']
        );
    }

    public static function registerSettings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            CloudflareConfig::OPTION_ACCOUNT_ID,
            [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => [self::class, 'sanitizeAccountId'],
            ]
        );
        register_setting(
            self::OPTION_GROUP,
            CloudflareConfig::OPTION_BASE_URL,
            [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => [self::class, 'sanitizeBaseUrl'],
            ]
        );
        register_setting(
            self::OPTION_GROUP,
            CloudflareConfig::OPTION_GATEWAY_ID,
            [
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => [self::class, 'sanitizeGatewayId'],
            ]
        );
        register_setting(
            self::OPTION_GROUP,
            CloudflareConfig::OPTION_MODELS,
            [
                'type' => 'string',
                'default' => CloudflareConfig::DEFAULT_MODEL,
                'sanitize_callback' => [self::class, 'sanitizeModels'],
            ]
        );

        add_settings_section(
            'cloudflare_ai_gateway_endpoint',
            __('Cloudflare AI Gateway endpoint', 'ai-provider-for-cloudflare-ai-gateway'),
            [self::class, 'renderSection'],
            self::PAGE_SLUG
        );

        add_settings_field(
            CloudflareConfig::OPTION_ACCOUNT_ID,
            __('Account ID', 'ai-provider-for-cloudflare-ai-gateway'),
            [self::class, 'renderAccountIdField'],
            self::PAGE_SLUG,
            'cloudflare_ai_gateway_endpoint'
        );
        add_settings_field(
            CloudflareConfig::OPTION_BASE_URL,
            __('Complete API base URL', 'ai-provider-for-cloudflare-ai-gateway'),
            [self::class, 'renderBaseUrlField'],
            self::PAGE_SLUG,
            'cloudflare_ai_gateway_endpoint'
        );
        add_settings_field(
            CloudflareConfig::OPTION_GATEWAY_ID,
            __('Gateway ID', 'ai-provider-for-cloudflare-ai-gateway'),
            [self::class, 'renderGatewayIdField'],
            self::PAGE_SLUG,
            'cloudflare_ai_gateway_endpoint'
        );
        add_settings_field(
            CloudflareConfig::OPTION_MODELS,
            __('Model IDs', 'ai-provider-for-cloudflare-ai-gateway'),
            [self::class, 'renderModelsField'],
            self::PAGE_SLUG,
            'cloudflare_ai_gateway_endpoint'
        );
    }

    public static function addPage(): void
    {
        add_options_page(
            __('AI Provider for Cloudflare AI Gateway', 'ai-provider-for-cloudflare-ai-gateway'),
            __('Cloudflare AI Gateway', 'ai-provider-for-cloudflare-ai-gateway'),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    /**
     * Adds the requested shortcut to the Plugins page.
     *
     * @param array<string, string> $links
     * @return array<string, string>
     */
    public static function addActionLink(array $links): array
    {
        $url = admin_url('options-general.php?page=' . self::PAGE_SLUG);
        array_unshift(
            $links,
            sprintf(
                '<a href="%s">%s</a>',
                esc_url($url),
                esc_html__('Settings', 'ai-provider-for-cloudflare-ai-gateway')
            )
        );

        return $links;
    }

    public static function sanitizeAccountId($value): string
    {
        return CloudflareConfig::sanitizeAccountId($value);
    }

    public static function sanitizeBaseUrl($value): string
    {
        $value = is_string($value) ? CloudflareConfig::normalizeBaseUrl($value) : '';

        return CloudflareConfig::isValidBaseUrl($value) ? $value : '';
    }

    public static function sanitizeGatewayId($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^[A-Za-z0-9_-]+$/', $value) === 1 ? $value : '';
    }

    public static function sanitizeModels($value): string
    {
        return CloudflareConfig::sanitizeModelIds($value);
    }

    public static function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function renderSection(): void
    {
        echo '<p>' . esc_html__(
            'Enter your Cloudflare account ID to use the current REST API. A complete base URL can be supplied instead when you use a compatible custom domain or gateway route.',
            'ai-provider-for-cloudflare-ai-gateway'
        ) . '</p>';
        echo '<p><a href="https://developers.cloudflare.com/ai-gateway/usage/rest-api/" target="_blank" rel="noopener noreferrer">'
            . esc_html__('Read the Cloudflare REST API documentation.', 'ai-provider-for-cloudflare-ai-gateway')
            . '</a></p>';
    }

    public static function renderAccountIdField(): void
    {
        $value = get_option(CloudflareConfig::OPTION_ACCOUNT_ID, '');
        $value = is_string($value) ? $value : '';
        printf(
            '<input type="text" class="regular-text code" name="%1$s" id="%1$s" value="%2$s" placeholder="32-character Cloudflare account ID" autocomplete="off" />',
            esc_attr(CloudflareConfig::OPTION_ACCOUNT_ID),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__(
            'Used to build https://api.cloudflare.com/client/v4/accounts/{ACCOUNT_ID}/ai/v1. Leave this empty when a complete base URL is configured below.',
            'ai-provider-for-cloudflare-ai-gateway'
        ) . '</p>';
    }

    public static function renderBaseUrlField(): void
    {
        $value = get_option(CloudflareConfig::OPTION_BASE_URL, '');
        $value = is_string($value) ? $value : '';
        printf(
            '<input type="url" class="regular-text code" name="%1$s" id="%1$s" value="%2$s" placeholder="https://api.cloudflare.com/client/v4/accounts/{ACCOUNT_ID}/ai/v1" autocomplete="url" />',
            esc_attr(CloudflareConfig::OPTION_BASE_URL),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__(
            'Optional. This value takes precedence over Account ID. It must be an HTTPS base URL without /chat/completions. Custom domains must expose the same OpenAI-compatible route.',
            'ai-provider-for-cloudflare-ai-gateway'
        ) . '</p>';
    }

    public static function renderGatewayIdField(): void
    {
        $value = get_option(CloudflareConfig::OPTION_GATEWAY_ID, '');
        $value = is_string($value) ? $value : '';
        printf(
            '<input type="text" class="regular-text code" name="%1$s" id="%1$s" value="%2$s" placeholder="default" autocomplete="off" />',
            esc_attr(CloudflareConfig::OPTION_GATEWAY_ID),
            esc_attr($value)
        );
        echo '<p class="description">' . esc_html__(
            'Optional. Sent as cf-aig-gateway-id. Leave empty to use the account default gateway.',
            'ai-provider-for-cloudflare-ai-gateway'
        ) . '</p>';
    }

    public static function renderModelsField(): void
    {
        $value = get_option(CloudflareConfig::OPTION_MODELS, CloudflareConfig::DEFAULT_MODEL);
        $value = is_string($value) ? $value : CloudflareConfig::DEFAULT_MODEL;
        printf(
            '<textarea class="large-text code" rows="5" name="%1$s" id="%1$s" placeholder="openai/gpt-4.1">%2$s</textarea>',
            esc_attr(CloudflareConfig::OPTION_MODELS),
            esc_textarea($value)
        );
        echo '<p class="description">' . esc_html__(
            'Enter one model ID per line. Current REST API model IDs use the author/model format, for example openai/gpt-4.1. The first ID is preferred.',
            'ai-provider-for-cloudflare-ai-gateway'
        ) . '</p>';
    }
}
