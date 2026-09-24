# AI Provider for Cloudflare AI Gateway

This plugin registers Cloudflare AI Gateway as a provider for the WordPress AI Client. It sends text,
chat history, tool calls, structured output, and optional image input through the OpenAI-compatible
`chat/completions` endpoint.

## Configuration

1. Install and activate the plugin.
2. Open **Settings > Connectors** and save a Cloudflare API token with the account-level Workers AI
   Read permission.
3. Open **Settings > Cloudflare AI Gateway**, or use the **Settings** link on the Plugins screen.
4. Enter either your 32-character Cloudflare **Account ID**, or a complete HTTPS API base URL.

With only an Account ID, the plugin uses:

```text
https://api.cloudflare.com/client/v4/accounts/{ACCOUNT_ID}/ai/v1
```

The complete base URL field takes precedence. It is intended for a compatible gateway route or a
custom domain. The plugin appends `/chat/completions` and does not store an API token itself.

Cloudflare's current REST API uses `author/model` IDs such as `openai/gpt-4.1`. Enter one model ID per
line. The first model is preferred. The optional Gateway ID is sent as `cf-aig-gateway-id`; an empty
value uses the account's default gateway.

## Environment variables

Environment variables override the matching settings field:

| Variable | Meaning |
| --- | --- |
| `CLOUDFLARE_ACCOUNT_ID` | 32-character account ID |
| `CLOUDFLARE_AI_GATEWAY_BASE_URL` | Complete HTTPS base URL |
| `CLOUDFLARE_AI_GATEWAY_ID` | Gateway ID header value |
| `CLOUDFLARE_AI_GATEWAY_MODELS` | Space, comma, or newline separated model IDs |
| `CLOUDFLARE_AI_GATEWAY_MODEL_INPUT_MODALITIES` | Include `image` to declare image input |
| `CLOUDFLARE_AI_GATEWAY_STRUCTURED_OUTPUT` | `json_schema`, `json_object`, or `none` |
| `CLOUDFLARE_AI_GATEWAY_REQUEST_TIMEOUT` | Request timeout in seconds |
| `CLOUDFLARE_AI_GATEWAY_CONNECT_TIMEOUT` | Connection timeout in seconds |

## Lessons from the StepFun and Command Code histories

The provider follows the fixes that were required in both repositories:

* Credential presence is read from the AI Client registry. The plugin never reads the
  `connectors_ai_*_api_key` option or handles the token value.
* Long request and connection timeouts are applied to every generation request.
* Structured output wraps the JSON schema in the OpenAI `name` and `schema` object. The SDK's raw
  shape is rejected by several OpenAI-compatible gateways.
* Endpoint configuration is validated before it is used. Account ID and complete URL configuration
  are both supported, and the Plugins screen has a direct settings link.
* Cloudflare AI Gateway REST does not provide a normal OpenAI `/models` endpoint at the chat base URL,
  so the plugin uses the explicit model list from settings instead of making a failing model-list
  request.

## Development

Run the standalone checks:

```sh
php scripts/selfcheck.php
```

The plugin depends on the WordPress AI Client supplied by WordPress core or the AI plugin. It does not
bundle a second copy of that SDK.
