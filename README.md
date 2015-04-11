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
