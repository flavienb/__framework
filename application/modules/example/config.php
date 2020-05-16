<?php
// Here you can override the default application.php config file properties

switch(__config::get('APPLICATION_STATE')) {
    case 'production' :
        __config::set('DISPLAY_ERRORS', false);
        __config::set('ERROR_MAIL', true);
        break;
    default:
        // development
}
