=== AI Provider for Cloudflare AI Gateway ===
Contributors: bestony
Tags: ai, cloudflare, ai gateway, artificial-intelligence, connector
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cloudflare AI Gateway provider for the WordPress AI Client.

== Description ==

This plugin sends WordPress AI Client text generation through Cloudflare AI Gateway's OpenAI-compatible
REST API. It supports chat history, tools, structured JSON output, and optional vision input.

== Setup ==

1. Activate the plugin.
2. Open **Settings > Connectors** and save a Cloudflare API token with the account-level Workers AI
   Read permission.
3. Open **Settings > Cloudflare AI Gateway**, or click **Settings** in the plugin row.
4. Enter the Cloudflare Account ID, or enter a complete HTTPS API base URL for a compatible custom route.
5. Enter one or more model IDs such as `openai/gpt-4.1`.

The REST API base URL built from an Account ID is:
`https://api.cloudflare.com/client/v4/accounts/{ACCOUNT_ID}/ai/v1`.

== External Services ==

This plugin sends prompts, conversation messages, optional image data, tool definitions, and model
configuration to the configured Cloudflare AI Gateway endpoint. It receives generated responses and
token usage from that endpoint. The endpoint and model IDs are configured by the site administrator.

Cloudflare API documentation: https://developers.cloudflare.com/ai-gateway/usage/rest-api/
Cloudflare terms: https://www.cloudflare.com/terms/
Cloudflare privacy policy: https://www.cloudflare.com/privacypolicy/

== Frequently Asked Questions ==

= Why must I configure an Account ID or complete URL? =

The REST API URL contains the Cloudflare account. A complete URL is also supported for a compatible
gateway route or custom domain.

= Why are models entered manually? =

The current REST chat endpoint does not expose the usual OpenAI `/models` route at the configured base
URL. The plugin therefore uses the model IDs entered in its settings.

== Changelog ==

= 1.0.0 =
* Initial release with Account ID and complete endpoint settings, model configuration, and an OpenAI
  Chat Completions provider.
