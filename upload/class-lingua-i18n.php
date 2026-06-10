<?php
/**
 * Gère l'internationalisation (i18n) du plugin
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

// Si ce fichier est appelé directement, on abandonne.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_AI_i18n {
    /**
     * Charge le fichier de traduction du plugin.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'lingua-commerce-ai',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}