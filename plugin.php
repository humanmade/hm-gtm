<?php
/**
 * Plugin Name: Google Tag Manager tools
 * Description: Provides GTM integration per site or for an entire multisite network.
 * Author: Human Made Limited
 * Version: __VERSION__
 * Author URI: https://humanmade.com
 */

namespace HM\GTM;

const VERSION = '__VERSION__';

require_once __DIR__ . '/inc/namespace.php';
require_once __DIR__ . '/inc/template-tags.php';

bootstrap();
