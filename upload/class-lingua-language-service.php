<?php
/**
 * Service de gestion des langues pour le Frontend et l'Admin
 * Fait le lien entre la BDD wp_lingua_languages et le reste du plugin
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_Language_Service {

    /**
     * Retourne la langue par défaut (Source) définie en admin
     * Sécurisé avec fallback sur la locale WordPress
     */
    public static function get_default_language() {
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_languages';
        
        // On cherche la langue marquée comme par défaut
        $default = $wpdb->get_row( "SELECT * FROM $table WHERE is_default = 1 LIMIT 1" );
        
        // Fallback Sécurité : Si aucune langue par défaut n'est définie (ex: BDD vide ou corrompue)
        // on prend la première langue active.
        if ( ! $default ) {
            $default = $wpdb->get_row( "SELECT * FROM $table WHERE is_active = 1 LIMIT 1" );
        }

        // Dernier recours : Utiliser la locale de WordPress si la table est vide
        if ( ! $default ) {
            $wp_locale = get_locale();
            // On crée un objet factice pour éviter les erreurs "Trying to get property of non-object"
            return (object) array(
                'id' => 0,
                'code' => $wp_locale,
                'name' => 'Default',
                'native_name' => 'Default',
                'locale' => $wp_locale,
                'is_active' => 1,
                'is_default' => 1
            );
        }
        
        return $default;
    }

    /**
     * Retourne la liste des langues ACTIVES (celles que le visiteur peut voir)
     */
    public static function get_active_languages() {
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_languages';
        
        return $wpdb->get_results( "SELECT * FROM $table WHERE is_active = 1 ORDER BY name ASC" );
    }

    /**
     * Détermine la langue actuelle du visiteur
     * LOGIQUE DE SÉCURITÉ RENFORCÉE :
     * 1. Vérifie si un paramètre URL (?lang=xx) est présent.
     * 2. Sanitize l'entrée.
     * 3. Vérifie en BDD si cette langue existe ET est active.
     * 4. Si valide -> l'utilise. Sinon -> Fallback sur la langue par défaut.
     */
    public static function get_current_language() {
        $current_lang = null;

        // 1. Détection via URL (Priorité)
        if ( isset( $_GET['lang'] ) ) {
            // Sanitization de l'entrée utilisateur
            $code = sanitize_text_field( $_GET['lang'] );
            
            // Requête sécurisée avec Vérification d'activité
            global $wpdb;
            $table = $wpdb->prefix . 'lingua_languages';
            
            $requested_lang = $wpdb->get_row( 
                $wpdb->prepare( "SELECT * FROM $table WHERE code = %s AND is_active = 1 LIMIT 1", $code ) 
            );

            // Si la langue demandée existe et est active, on l'utilise
            if ( $requested_lang ) {
                $current_lang = $requested_lang;
            }
            // Sinon (langue inactive ou inexistante), on ignore le paramètre et on passe au fallback ci-dessous
        }

        // 2. Fallback sur la langue par défaut
        if ( ! $current_lang ) {
            $current_lang = self::get_default_language();
        }

        return $current_lang;
    }

    /**
     * Retourne uniquement le code ISO de la langue courante (ex: 'en_US')
     * Gère les cas où l'objet pourrait être null (sécurité defensive)
     */
    public static function get_current_language_code() {
        $lang = self::get_current_language();
        
        // Vérification défensive : si pour une raison l'objet est null
        if ( $lang && isset( $lang->code ) ) {
            return $lang->code;
        }
        
        // Ultime fallback
        return get_locale();
    }
}