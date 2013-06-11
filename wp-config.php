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
define('DB_NAME', 'basewp');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'never');

/** MySQL hostname */
define('DB_HOST', 'localhost');

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
define('AUTH_KEY',         'rG-y1w8~^O,Yb$1vATE` :*q`7|zI&X2#9(nv(@|u`a`)Jxh=WW[*$Jox^u`=Hdf');
define('SECURE_AUTH_KEY',  '!QMm(+m.$ z iRDf{13vHx>v%oGYBoz#srf:p`M]_&K+j.&KwSM,27Ui4EM}s`$P');
define('LOGGED_IN_KEY',    ',cm{%dKv3nM-G$VE*L=W,FE}RgYQva2-L,0@0]pzL8lf-^@B_k/6|hn6L^pE;w>n');
define('NONCE_KEY',        '9N<uSPxX|KowXv<BkLbF7{oO[]gN>qo;v7|/9/VKh<0ZIS(V4EwT3wJk5-~r&_ v');
define('AUTH_SALT',        '@d 2/vrHdOwo3H7|zDp6`U[ICqnvzI8KuIok<BdgowcIJp*3(Hg@6< dFRAqz1`D');
define('SECURE_AUTH_SALT', ':$L=r7<#WIK~db v+i3W6TpH.Vv4aHEZpV2HgqXEd5KE+N*5Yy41:+DRIMH$fV^w');
define('LOGGED_IN_SALT',   ' +!nse8x=F_`{>ik=n`!]Y`do>?^CV<@Cy;yil+hEdrfI.h3e[*8,,fK$MH7b7c5');
define('NONCE_SALT',       'hLv{j}yEiivcUx0yco3i-+# -7]`1zuj0IYuK1~^sx<2@cu2Q30yy9/KWJFd#8UA');

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

/** The default location of the temp folder for updating plugins */
define('WP_TEMP_DIR', ABSPATH . 'wp-content/tmp/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
