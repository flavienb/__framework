<?php
/**
 * @author flavienb.com
 */

final class __locale
{
    public static $defaultLanguage;
    private static $connexion;
    public static $titleArray = array();
    private static $field_key = null;
    private static $field_module = null;
    private static $field_table = null;
    private static $initialized = false;
    private static $idToLoad = array();
    private static $moduleToLoad = array();

    /**
     * Se connecte à la db et charge les titres du module passé en paramètre (optionnel)
     * @param <type> $moduleNames
     */
    static public function init($default_lang = '', $moduleNames = array())
    {
        self::$field_key = __config::get('LOCALE_CONFIG')['FIELD_KEY'];
        self::$field_module = __config::get('LOCALE_CONFIG')['FIELD_MODULE'];
        self::$field_table = __config::get('LOCALE_CONFIG')['TABLE'];

        if ($default_lang) {
            self::$defaultLanguage = $default_lang;
        } else {
            self::$defaultLanguage = self::getDefaultLanguage();
        } //On définit le langage des titres qui vont être chargés
        __store::set('lang', self::$defaultLanguage, '__');

        self::$titleArray[self::$defaultLanguage]['byModule'] = array();
        self::$titleArray[self::$defaultLanguage]['raw'] = array();

        self::$connexion = __database::connect(__config::get('LOCALE_CONFIG')['DATABASE']);

        if ($moduleNames) {
            if (!is_array($moduleNames)) {
                $moduleNames = array($moduleNames);
            }
            self::$moduleToLoad = $moduleNames;
        }

        self::$initialized = true;
    }

    /**
     * Trouver la langue à définir par défaut
     * @return <type>
     */
    private static function getDefaultLanguage()
    {
        $defaultLanguage = null;
        $requestPath = __request::getPathArray();
        $urlMap = __config::get('LOCALE_URL_MAP');

        if (is_array($urlMap) && is_array($requestPath) && count($requestPath) > 0 ) {
            $locales = array_intersect($requestPath,$urlMap);

            if (!empty($locales)) {
                $locale = current($locales);
                if (false !== ($dashPos = strpos($locale, '-'))) {
                    $dashPos++;
                } else {
                    $dashPos = 0;
                }
                $defaultLanguage = substr($locale, $dashPos);
            }
        }

        if (!$defaultLanguage) {
            if (__store::exists('lang','__')) {
                $defaultLanguage = __store::get('lang', '__');
            }
            else {
                $defaultLanguage = __config::get('LOCALE_DEFAULT_LANGUAGE');
            }
        }

        return $defaultLanguage;
    }

    /**
     *  A appeller à chaque fois qu'on veut changer de langue
     * @param <type> $default_language
     */
    public static function set($default_language)
    {
        self::$defaultLanguage = $default_language;
        __store::set('lang', $default_language, '__');
    }

    public static function setOptions($field_key, $field_module)
    {
        self::$field_key = $field_key;
        self::$field_module = $field_module;
    }


    private static function loadLocales($toLoad=array())
    {
        if (self::$connexion && $toLoad) {
            $lang = self::$defaultLanguage;

            $locales = implode(',', __config::get('LOCALE_CONFIG')['COLUMNS']);

            $sql = 'SELECT ' . self::$field_key . ',' . self::$field_module . ',' . $locales . ' FROM ' . self::$field_table.' WHERE ';

            if ($toLoad) {
                $ids = "'" . implode("','", $toLoad) . "'";
                $sql .=  self::$field_key . " IN ($ids)  ";
            }

            if (self::$moduleToLoad) {
                $modules = "'" . implode("','", self::$moduleToLoad) . "'";
                $module_test = " AND (" . self::$field_module . " IN ($modules) )";
                $sql .= " $module_test";
            }

            $data = null;

            if (__config::get('CACHE_ENABLE_LOCALE')) {
                $cacheKey = md5($sql);
                $data = __cache::fetch($cacheKey);
            }

            if (!$data) {
                $result = self::$connexion->query($sql);
                $data = $result->fetchAll(PDO::FETCH_ASSOC);
            }


            foreach ((array)$data as $row) {
                self::$titleArray[$lang]['byModule'][$row[self::$field_module]][$row[self::$field_key]] = $row[$lang];
                self::$titleArray[$lang]['raw'][$row[self::$field_key]] = $row[$lang];
            }

            if (__config::get('CACHE_ENABLE_LOCALE')) {
                __cache::store($cacheKey, $data);
            }
        }
    }

    private static function mapTokens($str,$tokens,$isHTML=true) {
        if ($tokens) {
            $mapFunction = function ($key) {
                return '/\{' . $key . '\}/';
            };

            return preg_replace(array_map($mapFunction, array_keys($tokens)), $tokens, $str);
        }

        return $isHTML?str_replace(array('\'','"'),array('&#039;','&quot;'),$str):$str;
    }

    /**
     * Obtenir le titre du module par défaut ou bien du module passé en paramètre
     * @param <type> $id
     * @param <type> $moduleNames
     * @return <type>
     */
    public static function get($id, $tokens = array(), $module=null)
    {
        if (self::$initialized) {

            if (!isset(__locale::$titleArray[__locale::$defaultLanguage]['raw'][$id])) {
                self::loadLocales(array($id));
            }

            if (isset(__locale::$titleArray[__locale::$defaultLanguage]['raw'][$id])) {
                if ($module !== null) {
                    if (isset(__locale::$titleArray[__locale::$defaultLanguage]['byModule'][$module])
                        && isset(__locale::$titleArray[__locale::$defaultLanguage]['byModule'][$module][$id])) {
                        return self::mapTokens(__locale::$titleArray[__locale::$defaultLanguage]['byModule'][$module][$id],$tokens,false);
                    }
                }
                else {
                    return self::mapTokens(__locale::$titleArray[__locale::$defaultLanguage]['raw'][$id],$tokens,false);
                }
            }
        }

        return self::markup($id);
    }

    /**
     * Générer le markup à remplacer par le titre lors de l'affichage de la vue
     * @param $id
     * @return string
     */
    public static function markup($id,$tokens = array(),$module=null)
    {
        if ($module && !in_array($module,self::$moduleToLoad)) {
            array_push(self::$moduleToLoad,$module);
        }

        self::$idToLoad[$id] = array(
            'id'        =>  $id,
            'module'    =>  $module,
            'tokens'    =>  $tokens
        );

        return '{' . $id . ($module?'{'.$module.'}':'').'}';
    }

    public static function processHTML($html)
    {
        self::loadLocales(array_keys(self::$idToLoad));

        $mapFunction = function ($toLoad) {

            if (isset(__locale::$titleArray[__locale::$defaultLanguage]['raw'][$toLoad['id']])) {
                if ($toLoad['module']) {
                    if (isset(__locale::$titleArray[__locale::$defaultLanguage]['byModule'][$toLoad['module']])
                        && isset(__locale::$titleArray[__locale::$defaultLanguage]['byModule'][$toLoad['module']][$toLoad['id']])) {
                        return self::mapTokens(__locale::$titleArray[__locale::$defaultLanguage]['byModule'][$toLoad['module']][$toLoad['id']],$toLoad['tokens']);
                    }
                }
                else {
                    return self::mapTokens(__locale::$titleArray[__locale::$defaultLanguage]['raw'][$toLoad['id']],$toLoad['tokens']);
                }
            }

            return '{' . $toLoad['id'] . ($toLoad['module']?'{'.$toLoad['module'].'}':'') . '}';
        };

        foreach((array)self::$idToLoad as $idToLoad) {
            $html = str_replace(
                array_map(function ($toLoad) {
                    return '{' . $toLoad['id'] . ($toLoad['module']?'{'.$toLoad['module'].'}':'') . '}';
                }, self::$idToLoad),
                array_map($mapFunction, self::$idToLoad),
                $html
            );
        }

        return $html;
    }

    /**
     * Obtenir la langue de localisation définie
     */
    public static function getLang()
    {
        return self::$defaultLanguage;
    }

    /**
     * Obtenir le nom de la vue localisée
     * @param <type> $view_name
     */
    public static function view($view_name)
    {
        return $view_name . '_' . self::getLang();
    }

    public static function isInitialized()
    {
        return self::$initialized;
    }

}

class_alias('__locale', '__localization');
