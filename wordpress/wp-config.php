<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', $_SERVER["DBWPNAME"]);

/** MySQL database username */
define('DB_USER', $_SERVER["DBUSER"]);

/** MySQL database password */
define('DB_PASSWORD', $_SERVER["DBPASS"]);

/** MySQL hostname */
define('DB_HOST', $_SERVER["DBHOST"]);

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');



/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'SbV@woG,:V9lA85/j?nbq=%/;-f-wMII6Nc4bF[uW*^NZWtm=X8*O!3o!gWv_Ke&');
define('SECURE_AUTH_KEY',  'h!*6n`#v~_WG+hrzUC-qvK^(T~~mVZx2NFSFzR+D0$a{|DZDrdZSi4zO;,r;_u|w');
define('LOGGED_IN_KEY',    '5$K9^--DPIyjC@|uh7]MdilM2zea-D1_EUdk](?8h@{LMA@/:&:VR/SdZim<E|;8');
define('NONCE_KEY',        'NR-LLTj[jBtxa^fB4.w=8{QxDy/M*G|#7_$ouL1[$LF6Qu;|@UHd S[T V20-3/F');
define('AUTH_SALT',        'VW=vhjJJ/pmC5QxI-~$^E`y)Cz-]u[&tv1Mbn-v/yF7$,sr7@;DP#pj665s}S9WN');
define('SECURE_AUTH_SALT', 'OktzF-K5K_7($Y`w7^4SSfb+mu<4Z[EkkpR1eX6&1EX5!s+:!|D:dgO6.m~3&%D3');
define('LOGGED_IN_SALT',   'NS]ON|p60Jo%]fGq+7J6O#qZ,+6ZCU9%Kj}1_m]Mzrrla k|zW}Kj$Wz@#-fJ#4z');
define('NONCE_SALT',       'GX+2g}--smKCMPq20K2Ul)/-71XFAHG5.;|{x:Rm9jkt-87ouc?>sPy7Xa;27{|j');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
