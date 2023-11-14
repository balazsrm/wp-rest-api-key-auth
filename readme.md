# WP REST API Key Authentication
## Description
This plugin adds a new authentication method to the WP REST API. It uses a key (stored in the options table) to authenticate requests. This plugin is useful if you are looking for a simple way to use the WP REST API without having to deal with OAuth authentication.

## Installation
1. Install the plugin as you would with any WordPress plugin.
2. Activate the plugin.
3. Navigate to Settings » Rest API Key Authentication to see/set your key, and the user associated with the key.

## Usage
To make authenticated requests, send your key in the X-Api-Key header. For example, `X-Api-Key: abcdefghijklmnopqrstuvwxyz0123456789`.

## Changelog
### 1.0.0
* Initial release
