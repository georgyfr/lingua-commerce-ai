<?php
/**
 * Gère l'activation du plugin
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_AI_Activator {

    /**
     * Version du schéma de base de données
     * À incrémenter (ex: 1.0.1) si vous modifiez la structure des tables dans le futur.
     */
    const DB_VERSION = '1.0.2';

    /**
     * Méthode d'activation
     * Vérifie la version avant de lancer les opérations lourdes (Idempotence).
     */
    public static function activate() {
        // Récupérer la version actuelle stockée en base
        $current_db_version = get_option( 'lingua_commerce_ai_db_version' );

        // Si la version actuelle est différente de celle du code, on lance la mise à jour/création
        if ( $current_db_version !== self::DB_VERSION ) {

            // 1. Création ou Mise à jour des tables
            self::create_tables();

            // 2. Définition des options par défaut
            // NOTE : Ici, on force la mise à jour. Idéalement pour une V2, on ferait un merge avec les options existantes.
            self::set_default_options();

            // 3. Planification des tâches CRON
            self::schedule_cron_jobs();

            // 4. Mise à jour du numéro de version en base pour ne pas refaire ça au prochain chargement
            update_option( 'lingua_commerce_ai_db_version', self::DB_VERSION );
        }

        // Ajouter un marqueur temporaire pour d'autres hooks si nécessaire
        update_option( 'lingua_commerce_ai_just_activated', true );

        // Journaliser l'activation
        error_log( 'LinguaCommerce AI plugin activated (DB Version: ' . self::DB_VERSION . ')' );
    }

    /**
     * Crée les tables nécessaires pour le plugin
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table des traductions
        $table_translations = $wpdb->prefix . 'lingua_translations';
        $sql = "CREATE TABLE $table_translations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            object_type varchar(20) NOT NULL,
            object_id varchar(100) NOT NULL,
            field_key varchar(100) NOT NULL,
            language varchar(10) NOT NULL,
            original_text longtext NOT NULL,
            translated_text longtext,
            status varchar(20) DEFAULT 'draft',
            source varchar(20) DEFAULT 'manual',
            last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_translation (object_type, object_id, field_key, language),
            KEY language (language),
            KEY status (status)
        ) $charset_collate;";

        // Table des langues
        $table_languages = $wpdb->prefix . 'lingua_languages';
        $sql .= "CREATE TABLE $table_languages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            code varchar(10) NOT NULL,
            name varchar(100) NOT NULL,
            native_name varchar(100) NOT NULL,
            flag varchar(10) DEFAULT '',
            is_active tinyint(1) DEFAULT 0,
            is_default tinyint(1) DEFAULT 0,
            locale varchar(10) DEFAULT '',
            PRIMARY KEY  (id),
            UNIQUE KEY code (code)
        ) $charset_collate;";

        // Table des moteurs IA
        $table_ai_engines = $wpdb->prefix . 'lingua_ai_engines';
        $sql .= "CREATE TABLE $table_ai_engines (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            engine_name varchar(50) NOT NULL,
            api_key varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'inactive',
            priority int(11) DEFAULT 10,
            supported_languages longtext,
            settings longtext,
            engine_config longtext,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY engine_name (engine_name)
        ) $charset_collate;";

        // Table de la file d'attente de traduction
        $table_queue = $wpdb->prefix . 'lingua_translation_queue';
        $sql .= "CREATE TABLE $table_queue (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            object_type varchar(20) NOT NULL,
            object_id varchar(100) NOT NULL,
            field_key varchar(100) NOT NULL,
            source_language varchar(10) NOT NULL,
            target_language varchar(10) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            ai_engine varchar(50) DEFAULT '',
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            processed_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Définit les options par défaut
     */
    private static function set_default_options() {
        $default_options = array(
            'default_language' => get_locale(),
            'enabled_languages' => array( get_locale() ),
            'translation_method' => 'manual', // manual, ai, hybrid
            'ai_engine' => 'openrouter',
            'auto_translation' => false,
            'seo_friendly_urls' => true,
            'cache_translations' => true,
            'cache_expiry' => 3600, // 1 heure
            'show_language_switcher' => true,
            'language_switcher_position' => 'footer',
            'debug_mode' => false
        );

        // Si l'option n'existe pas, on l'ajoute. Si elle existe, on la met à jour (pour l'instant).
        // Pour une v2, on utiliserait wp_parse_args pour fusionner avec les existantes.
       add_option( 'lingua_commerce_ai_settings', $default_options );
        update_option( 'lingua_commerce_ai_version', LINGUA_COMMERCE_AI_VERSION );
    }

    /**
     * Planifie les tâches cron nécessaires
     */
    private static function schedule_cron_jobs() {
        // Tâche pour nettoyer les traductions périmées (Tous les jours)
        if ( ! wp_next_scheduled( 'lingua_commerce_ai_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'lingua_commerce_ai_cleanup' );
        }

        // Tâche pour traiter la file d'attente de traduction IA (Toutes les heures)
        if ( ! wp_next_scheduled( 'lingua_commerce_ai_process_queue' ) ) {
            wp_schedule_event( time(), 'hourly', 'lingua_commerce_ai_process_queue' );
        }
    }
}
