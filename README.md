# Google Tag Manager Tools

Google Tag Manager template tags and settings tool.

## Important: V2 Release breaking changes

Version 2 brings some breaking changes that may require you to update your implementation and any data layer variables you have set up in Tag Manager. The key differences are:

- The `HM_GTM` namespace has been changed to `HM\GTM`
- The `HM_GTM\tag()` function has been changed to `gtm_tag()`
- The default `dataLayer` has been completely overhauled

## Usage

Once the plugin is installed and activated there are 2 places you can configure:

1. On the general settings page in admin add your container ID eg. `GTM-123ABC`
2. For a multisite install you can set a network wide container ID on the network settings screen

### No script fallback

If you wish to support the fallback iframe for devices without javascript add the following code just after the opening `<body>` tag in your theme:

```php
<?php do_action( 'after_body' ); ?>
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
