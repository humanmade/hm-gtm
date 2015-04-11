# Google Tag Manager Tools

Google Tag Manager template tags and settings tool

## Usage

Once the plugin is installed and activated there are 2 steps:

1. Add the template tag `HM_GTM\tag()` immediately after the opening `<body>` tag
2. On the general settings page in admin add your container ID eg. `GTM-123ABC`

## Example code

```php
<?php if ( function_exists( 'HM_GTM\tag' ) ) { HM_GTM\tag(); } ?>
```

## Data Layer

GTM offers a dataLayer which allows you pass arbitrary data that can be used to modify which tags are added to your site.

hm-gtm adds some default options and provides a simple filter for adding in custom key/value pairs.

```php
<?php

add_filter( 'hm_gtm_data_layer', function( $data ) {
    $data['my_var'] = 'hello';
    return $data;
} );

?>
```