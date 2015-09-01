<?php

// this allows us to require files rather easy
define('API_BASE_PATH', realpath(__DIR__ . '/../src/api'));

//version
define('VERSION_PREFIX', 'v');
define('VERSION_NUMBER', '1');
define('VERSION', VERSION_PREFIX.VERSION_NUMBER);

// setup our database constants
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '12345');
define('DB_DATABASE', 'auth');
define('TEST_DB_DATABASE', 'testauth');
define('TESTMODE', getenv('TESTMODE'));

// allow us to debug at times
define('API_DEBUG', true);


define('AUTH_CONFIG_FAILED_LOGIN_ATTEMPTS', 50);
define('AUTH_CONFIG_ACCOUNT_LOCKED_TIME', 20 * 60);  // 20 minutes
define('AUTH_CONFIG_FAILED_LOGINS_WARNING', 35);
define('AUTH_CONFIG_HASH_COST', 14);
define('AUTH_PASSWORD_TOKEN_EXPIRATION_TIME', 60 * 60 * 24 * 3); // 3 days
define('AUTH_SIGNUP_TOKEN_EXPIRATION_TIME', 60 * 60 * 24 * 3); // 3 days
define('AUTH_DOMAIN', 'https://audienceengine.net');

define('MANDRIL_API_KEY', 'XXXXXXXXXXXXXXXXXXXXXX');
