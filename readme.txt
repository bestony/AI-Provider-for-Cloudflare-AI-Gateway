=== AI Provider for Cloudflare AI Gateway ===
Contributors: bestony
Tags: ai, cloudflare, ai gateway, artificial-intelligence, connector
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
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

This plugin connects to Cloudflare AI Gateway, a service provided by Cloudflare, Inc. The connection
is required to generate text. The plugin sends your prompt to Cloudflare through its API and displays
the generated response in WordPress. No other external service is used.

Data is sent only when a generative AI request is made while this provider is selected or preferred,
and only after the site administrator has configured an Account ID or a complete API base URL. Each
request sends:

* The prompt, the conversation messages, and any system instruction.
* Attached image data, when the request contains an image and the selected model supports image input.
* Tool and function declarations, and the JSON schema used for structured output, when the request uses them.
* The configured model ID and the generation settings for the request, such as token limits, temperature, and stop sequences.
* The Cloudflare API token stored in **Settings > Connectors**, sent as the Authorization header.

Requests go to the Cloudflare API base URL configured by the site administrator. With only an Account
ID configured, that is `https://api.cloudflare.com/client/v4/accounts/{ACCOUNT_ID}/ai/v1`. A custom
base URL points to the compatible gateway route or domain the administrator chose. Nothing is sent to
the plugin author or to any other service.

Cloudflare AI Gateway documentation: https://developers.cloudflare.com/ai-gateway/usage/rest-api/
Cloudflare terms of use: https://www.cloudflare.com/terms/
Cloudflare privacy policy: https://www.cloudflare.com/privacypolicy/

== Frequently Asked Questions ==

= Why must I configure an Account ID or complete URL? =

The REST API URL contains the Cloudflare account. A complete URL is also supported for a compatible
gateway route or custom domain.

= Why are models entered manually? =

The current REST chat endpoint does not expose the usual OpenAI `/models` route at the configured base
URL. The plugin therefore uses the model IDs entered in its settings.

== Changelog ==

= 1.0.1 =
* Document the Cloudflare AI Gateway external service in the readme, including the data that is sent
  and the terms of use and privacy policy links.
* Remove the load_plugin_textdomain() call, which WordPress.org no longer requires.
* Add direct file access protection to every plugin PHP file.

= 1.0.0 =
* Initial release with Account ID and complete endpoint settings, model configuration, and an OpenAI
  Chat Completions provider.
