# Google Tag Manager Tools

Google Tag Manager template tags and settings tool. Also supports Server Side GTM.

## Usage

Once the plugin is installed and activated there are 2 places you can configure:

1. On the general settings page in admin add your container ID eg. `GTM-123ABC`
2. For a multisite install you can set a network wide container ID on the network settings screen

### Server Side GTM

You can optionally specify the following:

1. Server container URL - this should the absolute path to the server container, even if you are using a reverse proxy.
2. Custom code snippet - this is a block of code that will be added to the `<head>` of the page. Some server side container providers have subtle or extensive changes to the standard GTM snippet so you can override it fully if needed.
3. Cookie preservation - you can set a special UUID cookie that lasts a long time, some server side providers allow restoring cookies set by 3rd parties or client side code in this way.

### No script fallback

**NOTE:** This is not needed for block themes.

If your theme is a classic theme, and you wish to support the fallback iframe for devices without javascript, add the following code just after the opening `<body>` tag in your theme:

```php
do_action( 'wp_body_open' );
```

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

By default the plugin will look for elements with special data attributes in your markup and listen to the specified event to push events to the data layer.

The data attributes are:

- `data-gtm-on`: _enum_ [click|submit|keyup|focus|blur] The JS event to listen for, defaults to 'click'.
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
