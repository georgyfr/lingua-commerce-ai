<?php
/**
 * Gère la désactivation du plugin
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

// Si ce fichier est appelé directement, on abandonne.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_AI_Deactivator {
    /**
     * Méthode de désactivation
     */
    public static function deactivate() {
        self::unschedule_cron_jobs();
        self::flush_rewrite_rules();

        // Journaliser la désactivation
        error_log( 'LinguaCommerce AI plugin deactivated' );
    }

    /**
     * Annule les tâches cron planifiées
     */
    private static function unschedule_cron_jobs() {
        wp_clear_scheduled_hook( 'lingua_commerce_ai_cleanup' );
        wp_clear_scheduled_hook( 'lingua_commerce_ai_process_queue' );
    }

    /**
     * Régénère les règles de réécriture d'URL
     */
    private static function flush_rewrite_rules() {
        flush_rewrite_rules();
    }
}
