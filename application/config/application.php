<?php
// Default timezone
date_default_timezone_set('Europe/Brussels');

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization,content-type,Cache-Control,X-Requested-With,X-CSRF-TOKEN');

// Do not process Options requests
if (!empty($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    die();
}

// Application state
$appStateFile = __DIR__.'/applicationState.conf';
if (!file_exists($appStateFile)) {
    throw new Exception('No applcationState file found.');
}

//Dynamic Config
return Array(
    /**
     * Global
     */
    'DEFAULT_MODULE'    =>  'example', // default routing module
    'DEFAULT_CONTROLLER' => 'index', // default routing controller
    'DEFAULT_ACTION'    =>  'index',// default called action
    'APPLICATION_STATE' => file_get_contents($appStateFile),
    'DEFAULT_ENCODING' => 'UTF-8',
    /**
     * Authentication
     */
    'AUTHENTICATION_TIMEOUT' => 604800,
    'AUTHENTICATION_SECRET' => 'FZMvEZkJEG5+Oi76ryvcJ6HQEeyRHrpI5rIxHe7dULVMXgkKf8d/HmHXblIwDw7V2M6Tf5UQ',
    'AUTHENTICATION_CONFIG' => array(
      'DATABASE' => 'MY_DATABASE',
        'TABLE' => 'user',
        'FIELD_IDUSER' => 'user_id',
        'FIELD_USERNAME' => 'email',
        'FIELD_PASSWORD' => 'password',
        'FIELDS_SELECT' =>  array('firstname','lastname','email'),
        'CUSTOM_SQL'    =>  ''
        ),
    'AUTHORIZATION_HEADER' => 'Authorization',
    /**
     * Locales
     */
    'LOCALE_URL_MAP' => array('be-fr','be-nl','fr-fr','be-en'),
    'LOCALE_DEFAULT_LANGUAGE' => 'fr',
    'LOCALE_CONFIG' => array(
        'DATABASE' => 'MY_DATABASE',
        'TABLE' => 'translations',
        'FIELD_MODULE' => 'module',
        'FIELD_KEY' => 'token',
        'COLUMNS' => array('fr', 'nl', 'en')
    ),
    /**
     * Views
     */
    'VIEW_DEFAULT_JSON' => true,
    'VIEW_ENABLE_COMPRESSION' => false,
    'VIEW_CSS' => array(),
    'VIEW_JS' => array(),
    /**
     * Actions
     */
    'ACTION_ENABLE' => true,
    'ACTION_DATABASE' => 'MY_DATABASE',
    /**
     * Cache
     */
    'CACHE_ENABLE_VIEW' => false,
    'CACHE_ENABLE_LOCALE' => false,
    'CACHE_ENABLE_APC' => false,
    'CACHE_TTL' => 120,
    'CACHE_TTL_VIEW' => 60,
    /**
     * Session
     */
    'SESSION_PATH' =>  null,
    'SESSION_TIMEOUT' => 604800, // 7 days
    'SESSION_NAME'  =>  '__SESSID',
    'XSS_PREFIX_STRING' => '', // ")]}',\n",
    /**
     * Mail
     */
    'MAIL_SMTP' =>  false,
    'MAIL_SMTP_HOST' => '',
    'MAIL_SMTP_PORT'  =>  587,
    'MAIL_SMTP_USERNAME' => '',
    'MAIL_SMTP_PASSWORD' => '',
    /**
     * ERRORS
     */
    'DISPLAY_ERRORS' => true,
    'ERROR_MAIL' => false,
    'ERROR_MAIL_FROM' => 'error@example.org',
    'ERROR_MAIL_FROMNAME' => '__Framework',
    'ERROR_MAIL_TO' => 'developer@example.org',
);
