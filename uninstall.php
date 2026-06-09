<?php
// Si ce fichier est appelé directement, on abandonne.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

// Suppression des options du plugin
delete_option( 'lingua_commerce_ai_settings' );
delete_option( 'lingua_commerce_ai_version' );

// Suppression des tables personnalisées
global $wpdb;
 $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lingua_translations" );
 $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lingua_languages" );
 $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lingua_ai_engines" );
 $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lingua_translation_queue" );

// Nettoyage des transients (si nous en utilisons)
 $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lingua_%'" );
 $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lingua_%'" );

// Suppression des rôles personnalisés (si nous en créons)
if ( get_role( 'lingua_translator' ) ) {
    remove_role( 'lingua_translator' );
}

// Suppression des capacités personnalisées (si nous en ajoutons)
global $wp_roles;
if ( isset( $wp_roles ) ) {
    $capabilities = array(
        'manage_lingua_translations',
        'manage_lingua_ai_settings',
        'manage_lingua_languages',
        'manage_lingua_seo'
    );
    
    foreach ( $wp_roles->role_objects as $role ) {
        foreach ( $capabilities as $cap ) {
            $role->remove_cap( $cap );
        }
    }
}
