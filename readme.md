# WP REST API Key Authentication
## Description
This plugin adds a new authentication method to the WP REST API. It uses a key (stored in the options table) to authenticate requests. This plugin is useful if you are looking for a simple way to use the WP REST API without having to deal with OAuth authentication.

## Installation
1. Install the plugin as you would with any WordPress plugin.
2. Activate the plugin.
3. Navigate to Settings » Rest API Key Authentication to see/set your key, and the user associated with the key.

## Usage
To make authenticated requests, send your key in the X-Api-Key header. For example, `X-Api-Key: abcdefghijklmnopqrstuvwxyz0123456789`.

## Security considerations
**Keep the API key safe.** Anyone with the key can make authenticated requests to your site. If you think your key has been compromised, you can regenerate it by emptying the key field and saving the settings page. This will generate a new key and associate it with the user you have selected. You will need to update your client to use the new key.
Make sure you are using HTTPS when making requests to the API. Otherwise, your key will be sent in plain text.

## About
This plugin was created using the WP Plugin Architect GPT. For more information, visit: [WP Plugin Architect GPT](https://chat.openai.com/g/g-6cqBCrKTn-wp-plugin-architect)

## License
This plugin is open source and licensed under the GPL v2 or later.