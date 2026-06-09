<?php
/**
 * Plugin Name:       LinguaCommerce AI
 * Plugin URI:        https://lingua-commerce-ai.com
 * Description:       Système de traduction multilingue pour WordPress et WooCommerce, basé sur l'IA et sans duplication de contenu.
 * Version:           1.0.0
 * Author:            LinguaCommerce Team
 * Author URI:        https://lingua-commerce-ai.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       lingua-commerce-ai
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   9.1
 */

// Si ce fichier est appelé directement, on abandonne.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Définition des constantes du plugin
define( 'LINGUA_COMMERCE_AI_VERSION', '1.0.0' );
define( 'LINGUA_COMMERCE_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LINGUA_COMMERCE_AI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LINGUA_COMMERCE_AI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Déclare la compatibilité avec WooCommerce.
 * 
 * CORRECTION PHASE 4 :
 * - Vérification que la classe Util existe avant usage (évite les crashs si WC est absent).
 * - Déclaration explicite de compatibilité pour HPOS, Product Lookup et Checkout Blocks.
 * - Cela supprime l'avertissement "Incompatibilité détectée" de WooCommerce.
 */
add_action( 'before_woocommerce_init', function() {
    // Sécurité : On vérifie que l'utilitaire de gestion des fonctionnalités WooCommerce est bien chargé
    if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        return;
    }
    
    // 1. Déclare la compatibilité avec HPOS (Custom Order Tables)
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
        'custom_order_tables', 
        __FILE__, 
        true
    );
    
    // 2. Déclare la compatibilité avec les Product Data Lookup Tables
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
        'product_data_lookup', 
        __FILE__, 
        true
    );
    
    // 3. Déclare la compatibilité avec le nouveau Checkout Block
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
        'cart_checkout_blocks', 
        __FILE__, 
        true
    );
});

// Fonction d'activation du plugin
function lingua_commerce_ai_activate() {
    // Inclure le fichier d'activation
    require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-activator.php';
    LinguaCommerce_AI_Activator::activate();
}
register_activation_hook( __FILE__, 'lingua_commerce_ai_activate' );

// Fonction de désactivation du plugin
function lingua_commerce_ai_deactivate() {
    // Inclure le fichier de désactivation
    require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-deactivator.php';
    LinguaCommerce_AI_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'lingua_commerce_ai_deactivate' );

// Exécuter le plugin
function lingua_commerce_ai_run() {
    require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-init.php';
    $plugin = new LinguaCommerce_AI_Init();
    $plugin->run();
}
lingua_commerce_ai_run();
