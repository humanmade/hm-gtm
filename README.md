# Google Tag Manager Tools

Google Tag Manager template tags and settings tool. Now supports [Server Side GTM](https://developers.google.com/tag-platform/tag-manager/server-side).

## v3 Breaking Changes

There are some breaking changes in v3.0.0:

* data layer no longer passes `post.author_id` by default
* data layer `post.ID` renamed to `post.id`
* data layer `term.ID` renamed to `term.id`
* data layer `author.ID` renamed to `author.id`
* data layer `user.role` is no longer an array but a comma separated list of roles
* data layer `post.<taxonomy>` is no longer an array but a comma separated list of term names
* data layer `post.author_slug` is no longer available by default, use `post.author_name` instead

## Usage

Once the plugin is installed and activated there are 2 places you can configure:

1. On the general settings page in admin add your container ID eg. `GTM-123ABC`
2. For a multisite install you can set a network wide container ID on the network settings screen

### Server Side GTM

Server-side GTM sends your tag data through a container that you host, instead of straight from the browser to Google. Google explains the container side in its [server-side tagging setup guide](https://developers.google.com/tag-platform/learn/sst-fundamentals/5-sst-setup-analytics). This section covers the plugin side.

#### Plugin settings

Go to Settings > General in the admin. On multisite, the network settings screen has the same fields for a network-wide container.

1. Container ID - the web container ID, for example `GTM-XXXXXXX`. This is the only required field.
2. Server container URL - the absolute URL that serves your server container, for example `https://sst.example.com`. The plugin loads `gtm.js` and the noscript iframe from this URL instead of `https://www.googletagmanager.com`. Leave it empty to use Google's default.
3. Custom code snippet - a replacement for the standard `<head>` snippet. Use it if your provider gives you a different one, or to use a [GTM environment](#gtm-environments).
4. Custom iframe code snippet - a replacement for the `<noscript>` iframe. Setting the server container URL is usually enough.
5. Cookie preservation - you can set a special UUID cookie that lasts a long time, some server side providers allow restoring cookies set by 3rd parties or client side code in this way.

Custom snippets can use these placeholders: `%1$s` is the container ID, `%2$s` is the server container URL (or `https://www.googletagmanager.com` when empty), and `%3$s` is the data layer name. The iframe snippet only supports `%1$s` and `%2$s`.

#### First-time setup

1. Create and configure the server container in GTM. Follow [Google's setup guide](https://developers.google.com/tag-platform/learn/sst-fundamentals/5-sst-setup-analytics) for the GA4 client, the GA4 tag and its trigger.
2. Serve the server container from your own domain. Use a subdomain such as `sst.example.com`, or a path on your site such as `https://example.com/metrics` that a reverse proxy forwards to the container. Both are first-party, which stops browsers and ad blockers treating the requests as third-party.
3. Enter the container ID and the server container URL in the plugin settings. Use the same URL that you gave the Google tag as `server_container_url` in the web container.
4. Check the setup with server-side Preview in GTM, and look for `gtm.js` and `collect?v=2` requests to your server container URL in the browser Network tab. See Google's [preview and debug guide](https://developers.google.com/tag-platform/tag-manager/server-side/debug).

#### Check data before you switch over

Google advises running server-side tagging alongside your existing setup until the numbers match.

1. Keep your current client-side tags as they are.
2. Create a separate GA4 property for the server-side data.
3. Copy your existing tags one at a time so that events go to both properties.
4. Compare the two properties. When the server-side data reaches parity, move collection to the server and remove the duplicate tags.

### No script fallback

**NOTE:** This is not needed for block themes.

If your theme is a classic theme, and you wish to support the fallback iframe for devices without javascript, add the following code just after the opening `<body>` tag in your theme:

```php
<?php wp_body_open(); ?>
```

## GTM environments

[GTM environments](https://support.google.com/tagmanager/answer/6311518) let you publish a container version to a staging or development site without touching live. Each environment has its own snippet. The snippet adds `gtm_auth`, `gtm_preview` and `gtm_cookies_win` query parameters that point to the environment.

To use one:

1. In GTM, go to Admin > Environments, open the Actions menu for the environment, and choose Get Snippet.
2. Paste the `<head>` snippet into Custom code snippet in the plugin settings.
3. Paste the `<noscript>` snippet into Custom iframe code snippet.
4. Set the container ID. The plugin needs it even when your snippet has its own.

Replace the container ID and URL in the pasted snippets with placeholders, so that one snippet works with the plugin settings. The plugin replaces `%1$s` with the container ID, `%2$s` with the server container URL, and `%3$s` with the data layer name. If you leave the server container URL empty, `%2$s` is `https://www.googletagmanager.com`.

Head snippet:

```html
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'%2$s/gtm.js?id='+i+dl+'&gtm_auth=AUTH_TOKEN&gtm_preview=env-2&gtm_cookies_win=x';f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','%1$s');</script>
```

Noscript snippet:

```html
<noscript><iframe src="%2$s/ns.html?id=%1$s&gtm_auth=AUTH_TOKEN&gtm_preview=env-2&gtm_cookies_win=x"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
```

Replace `AUTH_TOKEN` and `env-2` with the values from your own snippet. Keep the `'script','dataLayer'` part as it is, and the plugin swaps in a custom data layer name for you.

Things to know:

- The plugin passes snippets through PHP `sprintf()`. A literal `%` in a snippet must be written as `%%`. Environment snippets normally have none, because `gtm_auth` and `gtm_preview` are plain letters, numbers, `-` and `_`. Check any snippet that has URL-encoded values.
- The iframe snippet only gets the container ID and URL. Do not use `%3$s` in it.
- Use a different snippet on each site or environment. The plugin has no API for fetching the environment parameters, so you paste them by hand.
- On multisite, set the network snippets on the network settings screen. Site snippets apply to the site container.

## Data Layer

GTM offers a `dataLayer` which allows you pass arbitrary data that can be used to modify which tags are added to your site.

This plugin adds some default information such as page author, tags and categories and provides a simple filter for adding in your own custom key/value pairs.

```php
<?php

add_filter( 'hm_gtm_data_layer', function( $data ) {
    $data['my_var'] = 'hello';
    return $data;
} );

?>
```

Find out more about [using the `dataLayer` variable here](https://developers.google.com/tag-manager/devguide#datalayer).

You can explore and view the `dataLayer` variables by previewing your container and using the overlay on your website.

### Custom event tracking

In the block editor you can add event tracking to any block via the settings panel in the block sidebar. This calls `dataLayer.push()` with the event data specified.

All blocks are opted in to support this by default, but you can set block support to false with the following during block registration, or via `block.json`. For example:

```php
register_block_type( 'my-plugin/my-block', [
  'supports' => [
    'gtm' => false,
  ],
) );
```

By default the plugin will look for elements with special data attributes in your markup and listen to the specified event to push events to the data layer.

The data attributes are:

- `data-gtm-on`: _enum_ [click|submit|keyup|focusin|focusout|mouseenter|mouseleave] The JS event to listen for, defaults to 'click'.
- `data-gtm-event`: _string_ The name or action of the event eg. "play".
- `data-gtm-category`: _string_ Optional group the event belongs to.
- `data-gtm-label`: _string_ Optional human readable label for the event.
- `data-gtm-value`: _number_ Optional numeric value associated with the event eg. product price.
- `data-gtm-fields`: _string_ Optional extra data provided as encoded JSON.
- `data-gtm-var`: _string_ Optionally override the default dataLayer variable name for this event.

Example:

```html
<button
  data-gtm-on="click"
  data-gtm-event="play"
  data-gtm-category="videos"
  data-gtm-label="Featured Promotional Video"
>
  Play video
</button>
```

There is also a helper function to return these data attributes called `get_gtm_data_attributes()`.

To deactivate custom event tracking use the following code:

```php
add_filter( 'hm_gtm_enable_event_tracking', '__return_false' );
```
