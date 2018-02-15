# Google Tag Manager Tools

Google Tag Manager template tags and settings tool

## Usage

Once the plugin is installed and activated there are 2 places you can configure:

1. On the general settings page in admin add your container ID eg. `GTM-123ABC`
2. For multisite install you can set a network wide container ID on the network settings screen

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
