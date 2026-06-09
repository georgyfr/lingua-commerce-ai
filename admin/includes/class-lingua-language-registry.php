<?php
/**
 * Registre des langues disponibles dans le monde
 * Contient une liste statique des codes ISO, noms et locales.
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/includes
 */

// Si ce fichier est appelé directement, on abandonne.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_Language_Registry {

    /**
     * Retourne la liste complète des langues disponibles
     * 
     * Format : 
     * 'code' => array(
     *      'name' => 'Nom en anglais',
     *      'native_name' => 'Nom natif',
     *      'locale' => 'Code locale pour PHP/WordPress'
     * )
     * 
     * @return array
     */
    public static function get_all() {
        return array(
            // --- AMÉRIQUES ---
            'en_US' => array( 'name' => 'English (United States)', 'native_name' => 'English (US)', 'locale' => 'en_US' ),
            'en_CA' => array( 'name' => 'English (Canada)', 'native_name' => 'English (Canada)', 'locale' => 'en_CA' ),
            'fr_CA' => array( 'name' => 'French (Canada)', 'native_name' => 'Français (Canada)', 'locale' => 'fr_CA' ),
            'es_MX' => array( 'name' => 'Spanish (Mexico)', 'native_name' => 'Español (México)', 'locale' => 'es_MX' ),
            'pt_BR' => array( 'name' => 'Portuguese (Brazil)', 'native_name' => 'Português (Brasil)', 'locale' => 'pt_BR' ),
            
            // --- EUROPE ---
            'en_GB' => array( 'name' => 'English (United Kingdom)', 'native_name' => 'English (UK)', 'locale' => 'en_GB' ),
            'fr_FR' => array( 'name' => 'French (France)', 'native_name' => 'Français', 'locale' => 'fr_FR' ),
            'de_DE' => array( 'name' => 'German (Germany)', 'native_name' => 'Deutsch', 'locale' => 'de_DE' ),
            'es_ES' => array( 'name' => 'Spanish (Spain)', 'native_name' => 'Español (España)', 'locale' => 'es_ES' ),
            'it_IT' => array( 'name' => 'Italian (Italy)', 'native_name' => 'Italiano', 'locale' => 'it_IT' ),
            'pt_PT' => array( 'name' => 'Portuguese (Portugal)', 'native_name' => 'Português', 'locale' => 'pt_PT' ),
            'nl_NL' => array( 'name' => 'Dutch (Netherlands)', 'native_name' => 'Nederlands', 'locale' => 'nl_NL' ),
            'nl_BE' => array( 'name' => 'Dutch (Belgium)', 'native_name' => 'Nederlands (België)', 'locale' => 'nl_BE' ),
            'fr_BE' => array( 'name' => 'French (Belgium)', 'native_name' => 'Français (Belgique)', 'locale' => 'fr_BE' ),
            'de_AT' => array( 'name' => 'German (Austria)', 'native_name' => 'Deutsch (Österreich)', 'locale' => 'de_AT' ),
            'de_CH' => array( 'name' => 'German (Switzerland)', 'native_name' => 'Deutsch (Schweiz)', 'locale' => 'de_CH' ),
            'ru_RU' => array( 'name' => 'Russian (Russia)', 'native_name' => 'Русский', 'locale' => 'ru_RU' ),
            'pl_PL' => array( 'name' => 'Polish (Poland)', 'native_name' => 'Polski', 'locale' => 'pl_PL' ),
            'tr_TR' => array( 'name' => 'Turkish (Turkey)', 'native_name' => 'Türkçe', 'locale' => 'tr_TR' ),
            'sv_SE' => array( 'name' => 'Swedish (Sweden)', 'native_name' => 'Svenska', 'locale' => 'sv_SE' ),
            'da_DK' => array( 'name' => 'Danish (Denmark)', 'native_name' => 'Dansk', 'locale' => 'da_DK' ),
            'no_NO' => array( 'name' => 'Norwegian (Norway)', 'native_name' => 'Norsk', 'locale' => 'no_NO' ),
            'fi_FI' => array( 'name' => 'Finnish (Finland)', 'native_name' => 'Suomi', 'locale' => 'fi_FI' ),
            'el_GR' => array( 'name' => 'Greek (Greece)', 'native_name' => 'Ελληνικά', 'locale' => 'el_GR' ),
            'cs_CZ' => array( 'name' => 'Czech (Czech Republic)', 'native_name' => 'Čeština', 'locale' => 'cs_CZ' ),
            'ro_RO' => array( 'name' => 'Romanian (Romania)', 'native_name' => 'Română', 'locale' => 'ro_RO' ),
            'hu_HU' => array( 'name' => 'Hungarian (Hungary)', 'native_name' => 'Magyar', 'locale' => 'hu_HU' ),
            'bg_BG' => array( 'name' => 'Bulgarian (Bulgaria)', 'native_name' => 'Български', 'locale' => 'bg_BG' ),
            'hr_HR' => array( 'name' => 'Croatian (Croatia)', 'native_name' => 'Hrvatski', 'locale' => 'hr_HR' ),
            'sk_SK' => array( 'name' => 'Slovak (Slovakia)', 'native_name' => 'Slovenčina', 'locale' => 'sk_SK' ),
            'sl_SI' => array( 'name' => 'Slovenian (Slovenia)', 'native_name' => 'Slovenščina', 'locale' => 'sl_SI' ),
            'et_EE' => array( 'name' => 'Estonian (Estonia)', 'native_name' => 'Eesti', 'locale' => 'et_EE' ),
            'lv_LV' => array( 'name' => 'Latvian (Latvia)', 'native_name' => 'Latviešu', 'locale' => 'lv_LV' ),
            'lt_LT' => array( 'name' => 'Lithuanian (Lithuania)', 'native_name' => 'Lietuvių', 'locale' => 'lt_LT' ),
            'uk_UA' => array( 'name' => 'Ukrainian (Ukraine)', 'native_name' => 'Українська', 'locale' => 'uk_UA' ),
            'sr_RS' => array( 'name' => 'Serbian (Serbia)', 'native_name' => 'Српски', 'locale' => 'sr_RS' ),
            
            // --- ASIE ---
            'zh_CN' => array( 'name' => 'Chinese (Simplified)', 'native_name' => '简体中文', 'locale' => 'zh_CN' ),
            'zh_TW' => array( 'name' => 'Chinese (Traditional)', 'native_name' => '繁體中文', 'locale' => 'zh_TW' ),
            'ja_JP' => array( 'name' => 'Japanese', 'native_name' => '日本語', 'locale' => 'ja_JP' ),
            'ko_KR' => array( 'name' => 'Korean', 'native_name' => '한국어', 'locale' => 'ko_KR' ),
            'hi_IN' => array( 'name' => 'Hindi (India)', 'native_name' => 'हिन्दी', 'locale' => 'hi_IN' ),
            'id_ID' => array( 'name' => 'Indonesian (Indonesia)', 'native_name' => 'Bahasa Indonesia', 'locale' => 'id_ID' ),
            'ms_MY' => array( 'name' => 'Malay (Malaysia)', 'native_name' => 'Bahasa Melayu', 'locale' => 'ms_MY' ),
            'th_TH' => array( 'name' => 'Thai (Thailand)', 'native_name' => 'ไทย', 'locale' => 'th_TH' ),
            'vi_VN' => array( 'name' => 'Vietnamese (Vietnam)', 'native_name' => 'Tiếng Việt', 'locale' => 'vi_VN' ),
            'fil_PH' => array( 'name' => 'Filipino (Philippines)', 'native_name' => 'Filipino', 'locale' => 'fil_PH' ),
            
            // - MOYEN-ORIENT -
            'ar_SA' => array( 'name' => 'Arabic (Saudi Arabia)', 'native_name' => 'العربية', 'locale' => 'ar_SA' ),
            'he_IL' => array( 'name' => 'Hebrew (Israel)', 'native_name' => 'עברית', 'locale' => 'he_IL' ),
            'fa_IR' => array( 'name' => 'Persian (Iran)', 'native_name' => 'فارسی', 'locale' => 'fa_IR' ),
            'ur_PK' => array( 'name' => 'Urdu (Pakistan)', 'native_name' => 'اردو', 'locale' => 'ur_PK' ),

            // -- AFRIQUE -
            'af_ZA' => array( 'name' => 'Afrikaans (South Africa)', 'native_name' => 'Afrikaans', 'locale' => 'af_ZA' ),
            'zu_ZA' => array( 'name' => 'Zulu (South Africa)', 'native_name' => 'isiZulu', 'locale' => 'zu_ZA' ),
            'sw_KE' => array( 'name' => 'Swahili (Kenya)', 'native_name' => 'Kiswahili', 'locale' => 'sw_KE' ),
            'ar_EG' => array( 'name' => 'Arabic (Egypt)', 'native_name' => 'العربية', 'locale' => 'ar_EG' ),
            
            // -- OCEANIE --
            'en_AU' => array( 'name' => 'English (Australia)', 'native_name' => 'English (Australia)', 'locale' => 'en_AU' ),
            'en_NZ' => array( 'name' => 'English (New Zealand)', 'native_name' => 'English (New Zealand)', 'locale' => 'en_NZ' ),
        );
    }

    /**
     * Helper pour récupérer une langue spécifique par son code
     */
    public static function get_by_code( $code ) {
        $all = self::get_all();
        return isset( $all[ $code ] ) ? $all[ $code ] : null;
    }

    /**
     * Retourne le drapeau HTML pour un code langue (ex: 'fr_FR' → '<span class="fi fi-fr">')
     * Utilise la librairie flag-icons (SVG) pour compatibilité Windows.
     *
     * @param string $lang_code Code langue complet (ex: fr_FR, en_US, pt_BR)
     * @param string $size      Taille du drapeau : 'sm' (16px), 'md' (24px), 'lg' (32px)
     * @return string HTML du drapeau
     */
    public static function get_flag( $lang_code, $size = 'md' ) {
        $country_code = self::get_country_code( $lang_code );
        $size_class = '';
        if ( $size === 'sm' ) {
            $size_class = ' lingua-flag-sm';
        } elseif ( $size === 'lg' ) {
            $size_class = ' lingua-flag-lg';
        }
        if ( $country_code ) {
            return '<span class="fi fi-' . esc_attr( $country_code ) . $size_class . '"></span>';
        }
        return '<span class="lingua-flag-placeholder' . $size_class . '">' . esc_html( strtoupper( substr( $lang_code, 0, 2 ) ) ) . '</span>';
    }

    /**
     * Retourne l'emoji drapeau pour un code langue (fallback pour les emails/API)
     *
     * @param string $lang_code Code langue complet
     * @return string Emoji drapeau
     */
    public static function get_flag_emoji( $lang_code ) {
        $flags = self::get_all_emoji_flags();
        if ( isset( $flags[ $lang_code ] ) ) {
            return $flags[ $lang_code ];
        }
        $short = substr( $lang_code, 0, 2 );
        if ( isset( $flags[ $short ] ) ) {
            return $flags[ $short ];
        }
        return '🏳️';
    }

    /**
     * Extrait le code pays ISO 3166-1 alpha-2 d'un locale
     *
     * @param string $lang_code Code langue (ex: fr_FR, en_US, pt_BR)
     * @return string|false Code pays en minuscules (ex: fr, us, br) ou false
     */
    public static function get_country_code( $lang_code ) {
        $mapping = self::get_country_code_mapping();
        // Essai avec le code complet d'abord
        if ( isset( $mapping[ $lang_code ] ) ) {
            return $mapping[ $lang_code ];
        }
        // Essai avec le code 2 lettres
        $short = substr( $lang_code, 0, 2 );
        if ( isset( $mapping[ $short ] ) ) {
            return $mapping[ $short ];
        }
        // Dernier recours : extraire la partie pays du locale
        $parts = explode( '_', $lang_code );
        if ( count( $parts ) === 2 && strlen( $parts[1] ) === 2 ) {
            return strtolower( $parts[1] );
        }
        return false;
    }

    /**
     * Mapping complet des codes langue → codes pays pour flag-icons
     *
     * @return array
     */
    public static function get_country_code_mapping() {
        return array(
            // Codes complets (locale WordPress)
            'en_US' => 'us', 'en_GB' => 'gb', 'en_CA' => 'ca', 'en_AU' => 'au', 'en_NZ' => 'nz',
            'fr_FR' => 'fr', 'fr_CA' => 'ca', 'fr_BE' => 'be',
            'de_DE' => 'de', 'de_AT' => 'at', 'de_CH' => 'ch',
            'es_ES' => 'es', 'es_MX' => 'mx',
            'it_IT' => 'it',
            'pt_PT' => 'pt', 'pt_BR' => 'br',
            'nl_NL' => 'nl', 'nl_BE' => 'be',
            'ru_RU' => 'ru',
            'pl_PL' => 'pl',
            'tr_TR' => 'tr',
            'sv_SE' => 'se',
            'da_DK' => 'dk',
            'no_NO' => 'no',
            'fi_FI' => 'fi',
            'el_GR' => 'gr',
            'cs_CZ' => 'cz',
            'ro_RO' => 'ro',
            'hu_HU' => 'hu',
            'bg_BG' => 'bg',
            'hr_HR' => 'hr',
            'sk_SK' => 'sk',
            'sl_SI' => 'si',
            'et_EE' => 'ee',
            'lv_LV' => 'lv',
            'lt_LT' => 'lt',
            'uk_UA' => 'ua',
            'sr_RS' => 'rs',
            'zh_CN' => 'cn', 'zh_TW' => 'tw',
            'ja_JP' => 'jp',
            'ko_KR' => 'kr',
            'hi_IN' => 'in',
            'id_ID' => 'id',
            'ms_MY' => 'my',
            'th_TH' => 'th',
            'vi_VN' => 'vn',
            'fil_PH' => 'ph',
            'ar_SA' => 'sa', 'ar_EG' => 'eg',
            'he_IL' => 'il',
            'fa_IR' => 'ir',
            'ur_PK' => 'pk',
            'af_ZA' => 'za',
            'zu_ZA' => 'za',
            'sw_KE' => 'ke',
            'bn_BD' => 'bd',
            'ca_ES' => 'es-ct',
            'eu_ES' => 'es-pv',
            'gl_ES' => 'es-ga',

            // Codes courts (fallback 2 lettres)
            'en' => 'gb', 'fr' => 'fr', 'de' => 'de', 'es' => 'es', 'it' => 'it',
            'pt' => 'pt', 'nl' => 'nl', 'ru' => 'ru', 'zh' => 'cn', 'ja' => 'jp',
            'ar' => 'sa', 'ko' => 'kr', 'tr' => 'tr', 'pl' => 'pl', 'sv' => 'se',
            'da' => 'dk', 'no' => 'no', 'fi' => 'fi', 'el' => 'gr', 'cs' => 'cz',
            'ro' => 'ro', 'hu' => 'hu', 'bg' => 'bg', 'hr' => 'hr', 'sk' => 'sk',
            'uk' => 'ua', 'hi' => 'in', 'id' => 'id', 'ms' => 'my', 'th' => 'th',
            'vi' => 'vn', 'he' => 'il', 'fa' => 'ir', 'ur' => 'pk', 'sw' => 'ke',
        );
    }

    /**
     * Retourne le mapping complet code langue → drapeau emoji
     * Utilisé comme fallback pour les emails/API où le HTML n'est pas supporté
     *
     * @return array
     */
    public static function get_all_emoji_flags() {
        return array(
            // Codes complets (locale WordPress)
            'en_US' => '🇺🇸', 'en_GB' => '🇬🇧', 'en_CA' => '🇨🇦', 'en_AU' => '🇦🇺', 'en_NZ' => '🇳🇿',
            'fr_FR' => '🇫🇷', 'fr_CA' => '🇨🇦', 'fr_BE' => '🇧🇪',
            'de_DE' => '🇩🇪', 'de_AT' => '🇦🇹', 'de_CH' => '🇨🇭',
            'es_ES' => '🇪🇸', 'es_MX' => '🇲🇽',
            'it_IT' => '🇮🇹',
            'pt_PT' => '🇵🇹', 'pt_BR' => '🇧🇷',
            'nl_NL' => '🇳🇱', 'nl_BE' => '🇧🇪',
            'ru_RU' => '🇷🇺',
            'pl_PL' => '🇵🇱',
            'tr_TR' => '🇹🇷',
            'sv_SE' => '🇸🇪',
            'da_DK' => '🇩🇰',
            'no_NO' => '🇳🇴',
            'fi_FI' => '🇫🇮',
            'el_GR' => '🇬🇷',
            'cs_CZ' => '🇨🇿',
            'ro_RO' => '🇷🇴',
            'hu_HU' => '🇭🇺',
            'bg_BG' => '🇧🇬',
            'hr_HR' => '🇭🇷',
            'sk_SK' => '🇸🇰',
            'sl_SI' => '🇸🇮',
            'et_EE' => '🇪🇪',
            'lv_LV' => '🇱🇻',
            'lt_LT' => '🇱🇹',
            'uk_UA' => '🇺🇦',
            'sr_RS' => '🇷🇸',
            'zh_CN' => '🇨🇳', 'zh_TW' => '🇹🇼',
            'ja_JP' => '🇯🇵',
            'ko_KR' => '🇰🇷',
            'hi_IN' => '🇮🇳',
            'id_ID' => '🇮🇩',
            'ms_MY' => '🇲🇾',
            'th_TH' => '🇹🇭',
            'vi_VN' => '🇻🇳',
            'fil_PH' => '🇵🇭',
            'ar_SA' => '🇸🇦', 'ar_EG' => '🇪🇬',
            'he_IL' => '🇮🇱',
            'fa_IR' => '🇮🇷',
            'ur_PK' => '🇵🇰',
            'af_ZA' => '🇿🇦',
            'zu_ZA' => '🇿🇦',
            'sw_KE' => '🇰🇪',

            // Codes courts (fallback 2 lettres)
            'en' => '🇬🇧', 'fr' => '🇫🇷', 'de' => '🇩🇪', 'es' => '🇪🇸', 'it' => '🇮🇹',
            'pt' => '🇵🇹', 'nl' => '🇳🇱', 'ru' => '🇷🇺', 'zh' => '🇨🇳', 'ja' => '🇯🇵',
            'ar' => '🇸🇦', 'ko' => '🇰🇷', 'tr' => '🇹🇷', 'pl' => '🇵🇱', 'sv' => '🇸🇪',
            'da' => '🇩🇰', 'no' => '🇳🇴', 'fi' => '🇫🇮', 'el' => '🇬🇷', 'cs' => '🇨🇿',
            'ro' => '🇷🇴', 'hu' => '🇭🇺', 'bg' => '🇧🇬', 'hr' => '🇭🇷', 'sk' => '🇸🇰',
            'uk' => '🇺🇦', 'hi' => '🇮🇳', 'id' => '🇮🇩', 'ms' => '🇲🇾', 'th' => '🇹🇭',
            'vi' => '🇻🇳', 'he' => '🇮🇱', 'fa' => '🇮🇷', 'ur' => '🇵🇰', 'sw' => '🇰🇪',
        );
    }

    /**
     * Retourne le mapping complet code langue → drapeau HTML (flag-icons)
     *
     * @return array
     */
    public static function get_all_flags() {
        $mapping = self::get_country_code_mapping();
        $flags = array();
        foreach ( $mapping as $lang_code => $country_code ) {
            $flags[ $lang_code ] = '<span class="fi fi-' . esc_attr( $country_code ) . '"></span>';
        }
        return $flags;
    }

    /**
     * Retourne le CSS nécessaire pour afficher les drapeaux (flag-icons)
     * À appeler dans le <head> de l'admin
     *
     * @return string CSS + link tag
     */
    public static function get_flag_css() {
        return '<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css">'
             . '<style>'
             . '.fi { display: inline-block; width: 1.333em; height: 1em; vertical-align: middle; border-radius: 2px; background-size: cover; }'
             . '.lingua-flag-sm .fi, .fi.lingua-flag-sm { width: 1em; height: 0.75em; }'
             . '.lingua-flag-lg .fi, .fi.lingua-flag-lg { width: 2em; height: 1.5em; }'
             . '.lingua-flag-placeholder { display: inline-flex; align-items: center; justify-content: center; width: 1.333em; height: 1em; background: #ddd; color: #555; font-size: 10px; font-weight: 700; border-radius: 2px; vertical-align: middle; }'
             . '.lingua-flag-placeholder.lingua-flag-sm { width: 1em; height: 0.75em; font-size: 8px; }'
             . '.lingua-flag-placeholder.lingua-flag-lg { width: 2em; height: 1.5em; font-size: 12px; }'
             . '</style>';
    }
}
