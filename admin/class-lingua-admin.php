<?php
/**
 * Gère la partie administration du plugin
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_AI_Admin {

    /**
     * Identifiant du plugin
     *
     * @var string
     */
    public $plugin_name;

    /**
     * Version actuelle du plugin
     *
     * @var string
     */
    public $version;

    /**
     * Constructeur - Enregistrement des hooks et actions AJAX
     *
     * @param string $plugin_name Nom du plugin.
     * @param string $version     Version du plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        // Hooks d'administration
        add_action( 'admin_init', array( $this, 'handle_tools_actions' ) );

        // Actions AJAX
        add_action( 'wp_ajax_lingua_get_seo_audit', array( $this, 'ajax_get_seo_audit' ) );
        add_action( 'wp_ajax_lingua_quick_fix_item', array( $this, 'ajax_quick_fix_item' ) );
        add_action( 'wp_ajax_lingua_generate_sitemap', array( $this, 'ajax_generate_sitemap' ) );
        add_action( 'wp_ajax_lingua_purge_cache', array( $this, 'ajax_purge_cache' ) );
        add_action( 'wp_ajax_lingua_clean_orphans', array( $this, 'ajax_clean_orphans' ) );
        add_action( 'wp_ajax_lingua_check_tables', array( $this, 'ajax_check_tables' ) );
        add_action( 'wp_ajax_lingua_get_queue_stats', array( $this, 'ajax_get_queue_stats' ) );
        add_action( 'wp_ajax_lingua_retry_failed_tasks', array( $this, 'ajax_retry_failed_tasks' ) );
        add_action( 'wp_ajax_lingua_clear_all_queue', array( $this, 'ajax_clear_all_queue' ) );
        add_action( 'wp_ajax_lingua_trigger_cron', array( $this, 'ajax_trigger_cron' ) );
        add_action( 'wp_ajax_lingua_get_system_status', array( $this, 'ajax_get_system_status' ) );
    }

    // =========================================================================
    // ENQUEUE STYLES & SCRIPTS
    // =========================================================================

    /**
     * Enregistre les styles de l'admin
     * (Les styles sont gérés dans les vues partielles pour ce plugin)
     */
    public function enqueue_styles() {
        // Styles intégrés dans les partials - pas de fichier CSS externe à charger ici
    }

    /**
     * Enregistre les scripts de l'admin
     * (Le JS est injecté en inline dans les vues pour ce plugin)
     */
    public function enqueue_scripts() {
        // JS inline dans les vues - pas de fichier JS externe à charger ici
    }

    // =========================================================================
    // MENU D'ADMINISTRATION
    // =========================================================================

    /**
     * Ajoute le menu principal et les 12 sous-menus du plugin
     */
    public function add_plugin_admin_menu() {
        // Menu principal
        add_menu_page(
            __( 'LinguaCommerce AI', 'lingua-commerce-ai' ),
            __( 'LinguaCommerce AI', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai',
            array( $this, 'display_dashboard_page' ),
            'dashicons-translation',
            56
        );

        // 1. Tableau de bord
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Tableau de bord', 'lingua-commerce-ai' ),
            __( 'Dashboard', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai',
            array( $this, 'display_dashboard_page' )
        );

        // 2. Traductions
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Traductions', 'lingua-commerce-ai' ),
            __( 'Traductions', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-translations',
            array( $this, 'display_translations_page' )
        );

        // 3. IA & Automatisation
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'IA & Automatisation', 'lingua-commerce-ai' ),
            __( 'IA & Automatisation', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-ai',
            array( $this, 'display_ai_page' )
        );

        // 4. Langues
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Langues', 'lingua-commerce-ai' ),
            __( 'Langues', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-languages',
            array( $this, 'display_languages_page' )
        );

        // 5. SEO Multilingue
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'SEO Multilingue', 'lingua-commerce-ai' ),
            __( 'SEO Multilingue', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-seo',
            array( $this, 'display_seo_page' )
        );

        // 6. Paramètres
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Paramètres', 'lingua-commerce-ai' ),
            __( 'Paramètres', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-settings',
            array( $this, 'display_settings_page' )
        );

        // 7. Outils avancés
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Outils avancés', 'lingua-commerce-ai' ),
            __( 'Outils avancés', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-tools',
            array( $this, 'display_tools_page' )
        );

        // 8. Menus & Widgets
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Menus & Widgets', 'lingua-commerce-ai' ),
            __( 'Menus & Widgets', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-menus',
            array( $this, 'display_menus_page' )
        );

        // 9. Multivendeur
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Multivendeur', 'lingua-commerce-ai' ),
            __( 'Multivendeur', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-multivendor',
            array( $this, 'display_multivendor_page' )
        );

        // 10. Emails & Notifs
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Emails & Notifs', 'lingua-commerce-ai' ),
            __( 'Emails & Notifs', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-emails',
            array( $this, 'display_emails_page' )
        );

        // 11. Import/Export
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Import/Export', 'lingua-commerce-ai' ),
            __( 'Import/Export', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-import-export',
            array( $this, 'display_import_export_page' )
        );

        // 12. Aide
        add_submenu_page(
            'lingua-commerce-ai',
            __( 'Aide', 'lingua-commerce-ai' ),
            __( 'Aide', 'lingua-commerce-ai' ),
            'manage_options',
            'lingua-commerce-ai-help',
            array( $this, 'display_help_page' )
        );
    }

    /**
     * Ajoute les liens d'action sur la page des plugins
     *
     * @param array $links Liens existants.
     * @return array Liens modifiés.
     */
    public function add_plugin_action_links( $links ) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=lingua-commerce-ai-settings' ) ),
            esc_html__( 'Settings', 'lingua-commerce-ai' )
        );
        array_unshift( $links, $settings_link );
        return $links;
    }

    // =========================================================================
    // PAGES D'AFFICHAGE
    // =========================================================================

    /**
     * Affiche la page Tableau de bord
     */
    public function display_dashboard_page() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-dashboard.php';
    }

    /**
     * Affiche la page Traductions (délègue au module Traductions)
     */
    public function display_translations_page() {
        if ( ! class_exists( 'LinguaCommerce_AI_Admin_Translations' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-lingua-admin-translations.php';
        }
        $translations_admin = new LinguaCommerce_AI_Admin_Translations();
        $translations_admin->render();
    }

    /**
     * Affiche la page IA & Automatisation (délègue au module IA)
     */
    public function display_ai_page() {
        if ( ! class_exists( 'Lingua_Admin_AI' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-lingua-admin-ai.php';
        }
        $ai_admin = new Lingua_Admin_AI();
        $ai_admin->render();
    }

    /**
     * Affiche la page Langues (délègue au module Langues)
     */
    public function display_languages_page() {
        if ( ! class_exists( 'LinguaCommerce_AI_Admin_Languages' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-lingua-admin-languages.php';
        }
        $languages_admin = new LinguaCommerce_AI_Admin_Languages();
        $languages_admin->render();
    }

    /**
     * Affiche la page SEO Multilingue
     */
    public function display_seo_page() {
        // Charger les services nécessaires
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-language-service.php';
        }
        if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-translation-model.php';
        }

        $active_languages = LinguaCommerce_Language_Service::get_active_languages();
        $default_language = LinguaCommerce_Language_Service::get_default_language();
        $settings         = get_option( 'lingua_commerce_ai_settings', array() );

        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-seo.php';
    }

    /**
     * Affiche la page Paramètres
     */
    public function display_settings_page() {
        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-language-service.php';
        }
        $active_languages = LinguaCommerce_Language_Service::get_active_languages();

        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-settings.php';
    }

    /**
     * Affiche la page Outils avancés
     */
    public function display_tools_page() {
        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-tools.php';
    }

    /**
     * Affiche la page Menus & Widgets
     */
    public function display_menus_page() {
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-language-service.php';
        }
        $active_languages = LinguaCommerce_Language_Service::get_active_languages();

        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-menus.php';
    }

    /**
     * Affiche la page Multivendeur
     */
    public function display_multivendor_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Multivendeur', 'lingua-commerce-ai' ) . '</h1>';
        echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 30px; margin: 20px 0; border-radius: 4px;">';
        echo '<p style="font-size: 14px; color: #666;">';
        echo esc_html__( 'Le module Multivendeur permet de gérer les traductions spécifiques à chaque vendeur dans une configuration WooCommerce multivendeur (Dokan, WCFM, etc.).', 'lingua-commerce-ai' );
        echo '</p>';

        // Vérification des plugins multivendeur
        $multivendor_plugins = array(
            'Dokan'          => 'dokan-lite/dokan.php',
            'WCFM'           => 'wc-frontend-manager/wc_frontend_manager.php',
            'WC Vendors'     => 'wc-vendors/class-wc-vendors.php',
            'WC Marketplace'  => 'dc-woocommerce-multi-vendor/dc_product_vendor.php',
        );

        $active_mv = false;
        echo '<h3>' . esc_html__( 'Plugins multivendeur détectés', 'lingua-commerce-ai' ) . '</h3>';
        echo '<ul>';
        foreach ( $multivendor_plugins as $name => $path ) {
            $is_active = is_plugin_active( $path );
            if ( $is_active ) {
                $active_mv = true;
                echo '<li style="color: #00a32a;">✅ ' . esc_html( $name ) . ' — ' . esc_html__( 'Actif', 'lingua-commerce-ai' ) . '</li>';
            } else {
                echo '<li style="color: #999;">⬜ ' . esc_html( $name ) . ' — ' . esc_html__( 'Non détecté', 'lingua-commerce-ai' ) . '</li>';
            }
        }
        echo '</ul>';

        if ( ! $active_mv ) {
            echo '<div class="notice notice-warning inline" style="margin-top: 15px;">';
            echo '<p>' . esc_html__( 'Aucun plugin multivendeur détecté. Installez Dokan, WCFM, WC Vendors ou WC Marketplace pour utiliser cette fonctionnalité.', 'lingua-commerce-ai' ) . '</p>';
            echo '</div>';
        } else {
            echo '<p style="margin-top: 15px; color: #00a32a; font-weight: 600;">' . esc_html__( 'Module prêt à être configuré.', 'lingua-commerce-ai' ) . '</p>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Affiche la page Emails & Notifs
     */
    public function display_emails_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Emails & Notifications', 'lingua-commerce-ai' ) . '</h1>';
        echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 30px; margin: 20px 0; border-radius: 4px;">';

        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        $email_enabled = isset( $settings['email_notifications'] ) ? (bool) $settings['email_notifications'] : false;
        $email_on_error = isset( $settings['email_on_error'] ) ? (bool) $settings['email_on_error'] : true;
        $email_on_complete = isset( $settings['email_on_complete'] ) ? (bool) $settings['email_on_complete'] : false;

        echo '<h3>' . esc_html__( 'Configuration des notifications email', 'lingua-commerce-ai' ) . '</h3>';
        echo '<p style="color: #666;">' . esc_html__( 'Configurez les notifications automatiques liées aux traductions IA.', 'lingua-commerce-ai' ) . '</p>';

        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th>' . esc_html__( 'Notifications email', 'lingua-commerce-ai' ) . '</th>';
        echo '<td><label><input type="checkbox" name="lingua_email_notifications" value="1" ' . checked( $email_enabled, true, false ) . ' disabled> ' . esc_html__( 'Activer les notifications', 'lingua-commerce-ai' ) . '</label></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . esc_html__( 'Erreur de traduction', 'lingua-commerce-ai' ) . '</th>';
        echo '<td><label><input type="checkbox" name="lingua_email_on_error" value="1" ' . checked( $email_on_error, true, false ) . ' disabled> ' . esc_html__( 'Notifier en cas d\'erreur', 'lingua-commerce-ai' ) . '</label></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th>' . esc_html__( 'Traduction terminée', 'lingua-commerce-ai' ) . '</th>';
        echo '<td><label><input type="checkbox" name="lingua_email_on_complete" value="1" ' . checked( $email_on_complete, true, false ) . ' disabled> ' . esc_html__( 'Notifier quand la traduction est terminée', 'lingua-commerce-ai' ) . '</label></td>';
        echo '</tr>';
        echo '</table>';

        echo '<div class="notice notice-info inline" style="margin-top: 15px;">';
        echo '<p>' . esc_html__( 'La configuration complète des emails sera disponible dans une prochaine mise à jour.', 'lingua-commerce-ai' ) . '</p>';
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    /**
     * Affiche la page Import/Export
     */
    public function display_import_export_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Import / Export', 'lingua-commerce-ai' ) . '</h1>';
        echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 30px; margin: 20px 0; border-radius: 4px;">';

        echo '<h3>' . esc_html__( 'Exporter les traductions', 'lingua-commerce-ai' ) . '</h3>';
        echo '<p style="color: #666;">' . esc_html__( 'Exportez toutes vos traductions au format JSON pour sauvegarde ou migration.', 'lingua-commerce-ai' ) . '</p>';
        echo '<a href="' . esc_url( admin_url( 'admin.php?page=lingua-commerce-ai-tools&action=export_full_backup' ) ) . '" class="button button-primary">' . esc_html__( 'Exporter tout', 'lingua-commerce-ai' ) . '</a>';

        echo '<hr style="margin: 30px 0;">';

        echo '<h3>' . esc_html__( 'Importer des traductions', 'lingua-commerce-ai' ) . '</h3>';
        echo '<p style="color: #666;">' . esc_html__( 'Importez un fichier JSON de traductions précédemment exporté.', 'lingua-commerce-ai' ) . '</p>';
        echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin.php?page=lingua-commerce-ai-tools' ) ) . '">';
        echo '<input type="file" name="lingua_import_file" accept=".json" style="margin-right: 10px;">';
        wp_nonce_field( 'lingua_import_nonce', 'lingua_import_nonce_field' );
        echo '<button type="submit" name="action" value="import_full_backup" class="button">' . esc_html__( 'Importer', 'lingua-commerce-ai' ) . '</button>';
        echo '</form>';

        echo '</div>';
        echo '</div>';
    }

    /**
     * Affiche la page Aide
     */
    public function display_help_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Aide & Documentation', 'lingua-commerce-ai' ) . '</h1>';

        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">';

        // Carte Guide de démarrage
        echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 4px;">';
        echo '<h3 style="margin-top: 0;">🚀 ' . esc_html__( 'Guide de démarrage rapide', 'lingua-commerce-ai' ) . '</h3>';
        echo '<ol>';
        echo '<li>' . esc_html__( 'Ajoutez vos langues cibles dans la page "Langues"', 'lingua-commerce-ai' ) . '</li>';
        echo '<li>' . esc_html__( 'Configurez au moins un moteur IA dans "IA & Automatisation"', 'lingua-commerce-ai' ) . '</li>';
        echo '<li>' . esc_html__( 'Testez la connexion du moteur', 'lingua-commerce-ai' ) . '</li>';
        echo '<li>' . esc_html__( 'Allez dans "Traductions" pour traduire vos contenus', 'lingua-commerce-ai' ) . '</li>';
        echo '<li>' . esc_html__( 'Vérifiez le SEO multilingue dans la page dédiée', 'lingua-commerce-ai' ) . '</li>';
        echo '</ol>';
        echo '</div>';

        // Carte Moteurs IA supportés
        echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 4px;">';
        echo '<h3 style="margin-top: 0;">🤖 ' . esc_html__( 'Moteurs IA supportés', 'lingua-commerce-ai' ) . '</h3>';
        echo '<ul>';
        echo '<li><strong>DeepL</strong> — ' . esc_html__( 'Haute qualité, idéal pour l\'européen', 'lingua-commerce-ai' ) . '</li>';
        echo '<li><strong>Yandex Cloud</strong> — ' . esc_html__( 'Excellent pour les langues slaves', 'lingua-commerce-ai' ) . '</li>';
        echo '<li><strong>Baidu</strong> — ' . esc_html__( 'Spécialiste chinois, signature MD5', 'lingua-commerce-ai' ) . '</li>';
        echo '<li><strong>Microsoft Bing</strong> — ' . esc_html__( 'Large couverture linguistique', 'lingua-commerce-ai' ) . '</li>';
        echo '<li><strong>Google Gemini</strong> — ' . esc_html__( 'IA générative Google', 'lingua-commerce-ai' ) . '</li>';
        echo '<li><strong>OpenRouter</strong> — ' . esc_html__( 'Accès multi-modèles via API unique', 'lingua-commerce-ai' ) . '</li>';
        echo '<li><strong>DeepSeek</strong> — ' . esc_html__( 'Modèle chinois performant', 'lingua-commerce-ai' ) . '</li>';
        echo '<li><strong>Mistral</strong> — ' . esc_html__( 'IA française, open-weight', 'lingua-commerce-ai' ) . '</li>';
        echo '<li><strong>OpenAI</strong> — ' . esc_html__( 'GPT-3.5 / GPT-4', 'lingua-commerce-ai' ) . '</li>';
        echo '</ul>';
        echo '</div>';

        // Carte Support
        echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 4px;">';
        echo '<h3 style="margin-top: 0;">💬 ' . esc_html__( 'Support & Ressources', 'lingua-commerce-ai' ) . '</h3>';
        echo '<ul>';
        echo '<li><a href="https://lingua-commerce-ai.com/docs" target="_blank">' . esc_html__( 'Documentation complète', 'lingua-commerce-ai' ) . '</a></li>';
        echo '<li><a href="https://lingua-commerce-ai.com/faq" target="_blank">' . esc_html__( 'FAQ', 'lingua-commerce-ai' ) . '</a></li>';
        echo '<li><a href="https://wordpress.org/support/plugin/lingua-commerce-ai/" target="_blank">' . esc_html__( 'Forum WordPress.org', 'lingua-commerce-ai' ) . '</a></li>';
        echo '<li><a href="mailto:support@lingua-commerce-ai.com">' . esc_html__( 'Email support', 'lingua-commerce-ai' ) . '</a></li>';
        echo '</ul>';
        echo '</div>';

        // Carte Infos système
        echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 25px; border-radius: 4px;">';
        echo '<h3 style="margin-top: 0;">ℹ️ ' . esc_html__( 'Informations système', 'lingua-commerce-ai' ) . '</h3>';
        echo '<table style="width: 100%;">';
        echo '<tr><td style="padding: 4px 0; color: #666;">' . esc_html__( 'Version du plugin', 'lingua-commerce-ai' ) . '</td><td><strong>' . esc_html( LINGUA_COMMERCE_AI_VERSION ) . '</strong></td></tr>';
        echo '<tr><td style="padding: 4px 0; color: #666;">' . esc_html__( 'WordPress', 'lingua-commerce-ai' ) . '</td><td>' . esc_html( get_bloginfo( 'version' ) ) . '</td></tr>';
        echo '<tr><td style="padding: 4px 0; color: #666;">PHP</td><td>' . esc_html( phpversion() ) . '</td></tr>';
        echo '<tr><td style="padding: 4px 0; color: #666;">WooCommerce</td><td>' . ( class_exists( 'WooCommerce' ) ? esc_html( WC()->version ) : '—' ) . '</td></tr>';
        echo '</table>';
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    // =========================================================================
    // RÉGLAGES (SETTINGS)
    // =========================================================================

    /**
     * Enregistre les réglages du plugin
     */
    public function register_settings() {
        register_setting(
            'lingua_commerce_ai_settings_group',
            'lingua_commerce_ai_settings',
            array( $this, 'sanitize_settings' )
        );
    }

    /**
     * Nettoyage et validation complet des réglages pour tous les onglets
     *
     * @param array $input Réglages soumis.
     * @return array Réglages nettoyés.
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        // -----------------------------------------------------------------
        // Onglet Général
        // -----------------------------------------------------------------
        $sanitized['active_post_types'] = array();
        if ( isset( $input['active_post_types'] ) && is_array( $input['active_post_types'] ) ) {
            $allowed_post_types = array( 'post', 'page', 'product', 'attachment' );
            foreach ( $input['active_post_types'] as $pt ) {
                $clean_pt = sanitize_text_field( $pt );
                if ( in_array( $clean_pt, $allowed_post_types, true ) ) {
                    $sanitized['active_post_types'][] = $clean_pt;
                }
            }
        }

        $sanitized['active_taxonomies'] = array();
        if ( isset( $input['active_taxonomies'] ) && is_array( $input['active_taxonomies'] ) ) {
            $allowed_taxonomies = array( 'category', 'post_tag', 'product_cat', 'product_tag', 'product_brand' );
            foreach ( $input['active_taxonomies'] as $tax ) {
                $clean_tax = sanitize_text_field( $tax );
                if ( in_array( $clean_tax, $allowed_taxonomies, true ) || strpos( $clean_tax, 'pa_' ) === 0 ) {
                    $sanitized['active_taxonomies'][] = $clean_tax;
                }
            }
        }

        // Langue de l'interface admin
        $sanitized['admin_language'] = isset( $input['admin_language'] )
            ? sanitize_text_field( $input['admin_language'] )
            : get_locale();

        // -----------------------------------------------------------------
        // Onglet Traduction
        // -----------------------------------------------------------------
        $sanitized['default_engine'] = isset( $input['default_engine'] )
            ? sanitize_text_field( $input['default_engine'] )
            : '';

        $sanitized['ai_tone'] = isset( $input['ai_tone'] )
            ? sanitize_text_field( $input['ai_tone'] )
            : 'professional';

        $allowed_tones = array( 'professional', 'casual', 'formal', 'creative', 'technical' );
        if ( ! in_array( $sanitized['ai_tone'], $allowed_tones, true ) ) {
            $sanitized['ai_tone'] = 'professional';
        }

        $sanitized['custom_instructions'] = isset( $input['custom_instructions'] )
            ? sanitize_textarea_field( $input['custom_instructions'] )
            : '';

        $sanitized['auto_validate'] = isset( $input['auto_validate'] )
            ? (bool) $input['auto_validate']
            : false;

        $sanitized['translation_batch_size'] = isset( $input['translation_batch_size'] )
            ? min( 50, max( 1, intval( $input['translation_batch_size'] ) ) )
            : 5;

        // -----------------------------------------------------------------
        // Onglet SEO
        // -----------------------------------------------------------------
        $sanitized['seo_auto_translate'] = isset( $input['seo_auto_translate'] )
            ? (bool) $input['seo_auto_translate']
            : false;

        $sanitized['seo_title_template'] = isset( $input['seo_title_template'] )
            ? sanitize_text_field( $input['seo_title_template'] )
            : '';

        $sanitized['seo_description_template'] = isset( $input['seo_description_template'] )
            ? sanitize_textarea_field( $input['seo_description_template'] )
            : '';

        $sanitized['hreflang_enabled'] = isset( $input['hreflang_enabled'] )
            ? (bool) $input['hreflang_enabled']
            : true;

        $sanitized['canonical_enabled'] = isset( $input['canonical_enabled'] )
            ? (bool) $input['canonical_enabled']
            : true;

        // -----------------------------------------------------------------
        // Onglet Performance
        // -----------------------------------------------------------------
        $sanitized['cache_duration'] = isset( $input['cache_duration'] )
            ? min( 86400, max( 0, intval( $input['cache_duration'] ) ) )
            : 3600;

        $sanitized['lazy_loading'] = isset( $input['lazy_loading'] )
            ? (bool) $input['lazy_loading']
            : true;

        $sanitized['disable_on_mobile'] = isset( $input['disable_on_mobile'] )
            ? (bool) $input['disable_on_mobile']
            : false;

        // -----------------------------------------------------------------
        // Onglet Avancé
        // -----------------------------------------------------------------
        $sanitized['debug_mode'] = isset( $input['debug_mode'] )
            ? (bool) $input['debug_mode']
            : false;

        $sanitized['log_retention_days'] = isset( $input['log_retention_days'] )
            ? min( 365, max( 1, intval( $input['log_retention_days'] ) ) )
            : 30;

        $sanitized['uninstall_remove_data'] = isset( $input['uninstall_remove_data'] )
            ? (bool) $input['uninstall_remove_data']
            : false;

        // -----------------------------------------------------------------
        // Onglet Notifications
        // -----------------------------------------------------------------
        $sanitized['email_notifications'] = isset( $input['email_notifications'] )
            ? (bool) $input['email_notifications']
            : false;

        $sanitized['email_on_error'] = isset( $input['email_on_error'] )
            ? (bool) $input['email_on_error']
            : true;

        $sanitized['email_on_complete'] = isset( $input['email_on_complete'] )
            ? (bool) $input['email_on_complete']
            : false;

        $sanitized['notification_email'] = isset( $input['notification_email'] )
            ? sanitize_email( $input['notification_email'] )
            : get_option( 'admin_email' );

        // -----------------------------------------------------------------
        // Onglet URL
        // -----------------------------------------------------------------
        $sanitized['url_mode'] = isset( $input['url_mode'] )
            ? sanitize_text_field( $input['url_mode'] )
            : 'query';

        $allowed_url_modes = array( 'query', 'subdomain', 'subdirectory' );
        if ( ! in_array( $sanitized['url_mode'], $allowed_url_modes, true ) ) {
            $sanitized['url_mode'] = 'query';
        }

        // Préserver les réglages existants qui ne sont pas dans le formulaire actuel
        $existing = get_option( 'lingua_commerce_ai_settings', array() );
        if ( is_array( $existing ) ) {
            foreach ( $existing as $key => $value ) {
                if ( ! isset( $sanitized[ $key ] ) ) {
                    $sanitized[ $key ] = $value;
                }
            }
        }

        return $sanitized;
    }

    // =========================================================================
    // ACTIONS AJAX
    // =========================================================================

    /**
     * Récupère les données de l'audit SEO
     */
    public function ajax_get_seo_audit() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        $post_type  = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : 'product';
        $target_lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : '';

        if ( empty( $target_lang ) ) {
            wp_send_json_error( array( 'message' => __( 'Langue cible manquante.', 'lingua-commerce-ai' ) ) );
        }

        if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-translation-model.php';
        }

        $items = LinguaCommerce_Translation_Model::get_seo_audit_data( $post_type, $target_lang );

        wp_send_json_success( array(
            'items' => $items,
            'count' => count( $items ),
        ) );
    }

    /**
     * Quick fix avec IA : translate_all ou fix_title
     */
    public function ajax_quick_fix_item() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        $object_id   = isset( $_POST['object_id'] ) ? intval( $_POST['object_id'] ) : 0;
        $object_type = isset( $_POST['object_type'] ) ? sanitize_text_field( $_POST['object_type'] ) : '';
        $target_lang = isset( $_POST['target_lang'] ) ? sanitize_text_field( $_POST['target_lang'] ) : '';
        $action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : 'translate_all';
        $engine_slug = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : '';

        if ( ! $object_id || empty( $object_type ) || empty( $target_lang ) ) {
            wp_send_json_error( array( 'message' => __( 'Paramètres manquants.', 'lingua-commerce-ai' ) ) );
        }

        // Charger les dépendances
        if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-translation-model.php';
        }

        $settings      = get_option( 'lingua_commerce_ai_settings', array() );
        $default_engine = isset( $settings['default_engine'] ) ? $settings['default_engine'] : 'openrouter';
        if ( empty( $engine_slug ) ) {
            $engine_slug = $default_engine;
        }

        $translated_fields = array();

        if ( 'translate_all' === $action_type ) {
            // Traduire tous les champs non traduits
            $fields_map = LinguaCommerce_Translation_Model::get_translatable_fields( $object_type );
            $existing   = LinguaCommerce_Translation_Model::get_all_translations_for_object( $object_id, $object_type, $target_lang );

            foreach ( $fields_map as $key => $config ) {
                // Ne pas retraduire les champs déjà validés
                if ( isset( $existing[ $key ] ) && 'validated' === $existing[ $key ]['status'] ) {
                    continue;
                }

                $original_value = LinguaCommerce_Translation_Model::get_original_value( $object_id, $key, $object_type );
                if ( empty( $original_value ) ) {
                    continue;
                }

                // Appel à l'API IA
                $translated_text = $this->call_ai_api_seo( $original_value, $target_lang, $engine_slug );

                if ( ! empty( $translated_text ) && ! is_wp_error( $translated_text ) ) {
                    $auto_validate = isset( $settings['auto_validate'] ) ? (bool) $settings['auto_validate'] : false;
                    $status = $auto_validate ? 'validated' : 'draft';

                    LinguaCommerce_Translation_Model::save_translation(
                        $object_id,
                        $object_type,
                        $key,
                        $target_lang,
                        $translated_text,
                        $status
                    );

                    $translated_fields[] = $key;
                }
            }
        } elseif ( 'fix_title' === $action_type ) {
            // Corriger uniquement le titre SEO
            $seo_title_key = '_yoast_wpseo_title';
            if ( defined( 'RANK_MATH_VERSION' ) ) {
                $seo_title_key = 'rank_math_title';
            } elseif ( defined( 'AIOSEO_VERSION' ) ) {
                $seo_title_key = '_aioseo_title';
            }

            $original_title = get_post_meta( $object_id, $seo_title_key, true );
            if ( empty( $original_title ) ) {
                $original_title = get_the_title( $object_id );
            }

            if ( ! empty( $original_title ) ) {
                $translated_text = $this->call_ai_api_seo( $original_title, $target_lang, $engine_slug );

                if ( ! empty( $translated_text ) && ! is_wp_error( $translated_text ) ) {
                    $auto_validate = isset( $settings['auto_validate'] ) ? (bool) $settings['auto_validate'] : false;
                    $status = $auto_validate ? 'validated' : 'draft';

                    LinguaCommerce_Translation_Model::save_translation(
                        $object_id,
                        $object_type,
                        $seo_title_key,
                        $target_lang,
                        $translated_text,
                        $status
                    );

                    $translated_fields[] = $seo_title_key;
                }
            }
        }

        if ( empty( $translated_fields ) ) {
            wp_send_json_error( array( 'message' => __( 'Aucune traduction n\'a pu être effectuée. Vérifiez la configuration du moteur IA.', 'lingua-commerce-ai' ) ) );
        }

        wp_send_json_success( array(
            'message'           => sprintf(
                /* translators: %d: Number of translated fields */
                __( '%d champ(s) traduit(s) avec succès.', 'lingua-commerce-ai' ),
                count( $translated_fields )
            ),
            'translated_fields' => $translated_fields,
            'engine'            => $engine_slug,
        ) );
    }

    /**
     * Appelle l'API IA pour les corrections SEO
     * Supporte : yandex, deepl, microsoft, baidu, et moteurs LLM
     *
     * @param string $text        Texte à traduire.
     * @param string $target_lang Langue cible.
     * @param string $engine      Slug du moteur.
     * @return string|WP_Error Texte traduit ou erreur.
     */
    private function call_ai_api_seo( $text, $target_lang, $engine ) {
        global $wpdb;
        $table_engines = $wpdb->prefix . 'lingua_ai_engines';

        // Récupérer la configuration du moteur
        $engine_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_engines WHERE engine_name = %s AND status = 'active'",
                $engine
            )
        );

        if ( ! $engine_row ) {
            return new WP_Error( 'engine_not_found', __( 'Moteur IA introuvable ou inactif.', 'lingua-commerce-ai' ) );
        }

        $config = json_decode( $engine_row->engine_config, true );
        if ( ! is_array( $config ) ) {
            $config = array();
        }

        $source_lang = 'en_US'; // Langue source par défaut
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-language-service.php';
        }
        $default_lang = LinguaCommerce_Language_Service::get_default_language();
        if ( $default_lang && isset( $default_lang->code ) ) {
            $source_lang = $default_lang->code;
        }

        $translated = '';
        $api_source = substr( $source_lang, 0, 2 );
        $api_target = substr( $target_lang, 0, 2 );

        switch ( $engine ) {

            case 'yandex':
                $api_key   = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $folder_id = isset( $config['folder_id'] ) ? $config['folder_id'] : '';

                if ( empty( $api_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Clé API Yandex manquante.', 'lingua-commerce-ai' ) );
                }

                $yandex_body = array(
                    'sourceLanguageCode' => $api_source,
                    'targetLanguageCode' => $api_target,
                    'texts'             => array( $text ),
                    'format'            => 'HTML',
                );
                if ( ! empty( $folder_id ) ) {
                    $yandex_body['folderId'] = $folder_id;
                }

                $response = wp_remote_post( 'https://translate.api.cloud.yandex.net/translate/v2/translate', array(
                    'timeout' => 30,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type'  => 'application/json',
                    ),
                    'body'    => wp_json_encode( $yandex_body ),
                ) );

                if ( is_wp_error( $response ) ) {
                    return $response;
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['translations'][0]['text'] ) ) {
                    $translated = $body['translations'][0]['text'];
                }
                break;

            case 'deepl':
                $api_key  = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $api_host = isset( $config['api_host'] ) ? $config['api_host'] : 'api.deepl.com';

                if ( empty( $api_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Clé API DeepL manquante.', 'lingua-commerce-ai' ) );
                }

                $is_free   = ( strpos( $api_key, 'fx' ) === 0 );
                $endpoint  = $is_free ? 'https://api-free.deepl.com/v2/translate' : 'https://' . $api_host . '/v2/translate';

                $response = wp_remote_post( $endpoint, array(
                    'timeout' => 30,
                    'body'    => array(
                        'auth_key'    => $api_key,
                        'text'        => $text,
                        'source_lang' => strtoupper( $api_source ),
                        'target_lang' => strtoupper( $api_target ),
                    ),
                ) );

                if ( is_wp_error( $response ) ) {
                    return $response;
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['translations'][0]['text'] ) ) {
                    $translated = $body['translations'][0]['text'];
                }
                break;

            case 'microsoft':
                $api_key     = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $region      = isset( $config['region'] ) ? $config['region'] : 'global';
                $resource_id = isset( $config['resource_id'] ) ? $config['resource_id'] : '';

                if ( empty( $api_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Clé API Microsoft manquante.', 'lingua-commerce-ai' ) );
                }

                $ms_endpoint = 'https://api.cognitive.microsofttranslator.com/translate';
                $ms_url      = add_query_arg( array(
                    'api-version' => '3.0',
                    'from'        => $api_source,
                    'to'          => $api_target,
                    'textType'    => 'html',
                ), $ms_endpoint );

                $headers = array(
                    'Ocp-Apim-Subscription-Key' => $api_key,
                    'Content-Type'              => 'application/json',
                );
                if ( ! empty( $resource_id ) ) {
                    $headers['Ocp-Apim-Subscription-Resource'] = $resource_id;
                }
                if ( $region && 'global' !== $region ) {
                    $headers['Ocp-Apim-Subscription-Region'] = $region;
                }

                $response = wp_remote_post( $ms_url, array(
                    'timeout' => 30,
                    'headers' => $headers,
                    'body'    => wp_json_encode( array( array( 'Text' => $text ) ) ),
                ) );

                if ( is_wp_error( $response ) ) {
                    return $response;
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body[0]['translations'][0]['text'] ) ) {
                    $translated = $body[0]['translations'][0]['text'];
                }
                break;

            case 'baidu':
                $app_id  = isset( $config['app_id'] ) ? $config['app_id'] : '';
                $sec_key = isset( $config['secret_key'] ) ? $config['secret_key'] : '';

                if ( empty( $app_id ) || empty( $sec_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Identifiants Baidu manquants.', 'lingua-commerce-ai' ) );
                }

                $salt     = wp_rand( 10000, 99999 );
                $sign_str = $app_id . $text . $salt . $sec_key;
                $sign     = md5( $sign_str );

                $response = wp_remote_post( 'https://fanyi-api.baidu.com/api/trans/vip/translate', array(
                    'timeout' => 30,
                    'body'    => array(
                        'q'      => $text,
                        'from'   => $api_source,
                        'to'     => $api_target,
                        'appid'  => $app_id,
                        'salt'   => $salt,
                        'sign'   => $sign,
                    ),
                ) );

                if ( is_wp_error( $response ) ) {
                    return $response;
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['trans_result'][0]['dst'] ) ) {
                    $translated = $body['trans_result'][0]['dst'];
                }
                break;

            // Moteurs LLM (openrouter, deepseek, mistral, openai, google)
            default:
                $api_key = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $model   = isset( $config['model'] ) ? $config['model'] : '';

                if ( empty( $api_key ) ) {
                    return new WP_Error( 'missing_key', sprintf( __( 'Clé API %s manquante.', 'lingua-commerce-ai' ), ucfirst( $engine ) ) );
                }

                // Déterminer l'endpoint et le modèle
                if ( 'google' === $engine ) {
                    $model = empty( $model ) ? 'gemini-1.5-flash' : $model;
                    $endpoint = sprintf(
                        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateText?key=%s',
                        $model,
                        $api_key
                    );

                    $prompt = sprintf(
                        'Translate the following text to %s. Return ONLY the translated text, no explanations:\n\n%s',
                        $api_target,
                        $text
                    );

                    $body = array(
                        'contents' => array(
                            array( 'parts' => array( array( 'text' => $prompt ) ) ),
                        ),
                        'generationConfig' => array(
                            'temperature'     => 0.3,
                            'maxOutputTokens' => 2048,
                        ),
                    );

                    $response = wp_remote_post( $endpoint, array(
                        'timeout' => 60,
                        'headers' => array( 'Content-Type' => 'application/json' ),
                        'body'    => wp_json_encode( $body ),
                    ) );

                    if ( is_wp_error( $response ) ) {
                        return $response;
                    }
                    $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $resp_body['candidates'][0]['content']['parts'][0]['text'] ) ) {
                        $translated = trim( $resp_body['candidates'][0]['content']['parts'][0]['text'] );
                        // Nettoyage
                        $translated = preg_replace( '/^```(?:html|php|text)?\s*\n?/i', '', $translated );
                        $translated = preg_replace( '/\n?```\s*$/i', '', $translated );
                        $translated = trim( $translated );
                    }
                } else {
                    // Chat Completions (openrouter, deepseek, mistral, openai)
                    $endpoints = array(
                        'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
                        'deepseek'   => 'https://api.deepseek.com/v1/chat/completions',
                        'mistral'    => 'https://api.mistral.ai/v1/chat/completions',
                        'openai'     => 'https://api.openai.com/v1/chat/completions',
                    );

                    $default_models = array(
                        'openrouter' => 'openai/gpt-3.5-turbo',
                        'deepseek'   => 'deepseek-chat',
                        'mistral'    => 'mistral-small-latest',
                        'openai'     => 'gpt-3.5-turbo',
                    );

                    $endpoint = isset( $endpoints[ $engine ] ) ? $endpoints[ $engine ] : '';
                    if ( empty( $model ) ) {
                        $model = isset( $default_models[ $engine ] ) ? $default_models[ $engine ] : 'gpt-3.5-turbo';
                    }

                    if ( empty( $endpoint ) ) {
                        return new WP_Error( 'invalid_engine', __( 'Endpoint API introuvable.', 'lingua-commerce-ai' ) );
                    }

                    $prompt = sprintf(
                        'Translate the following text from %s to %s. Return ONLY the translated text, preserving HTML tags and shortcodes. No explanations or comments:\n\n%s',
                        $api_source,
                        $api_target,
                        $text
                    );

                    $chat_headers = array(
                        'Content-Type'  => 'application/json',
                        'Authorization' => 'Bearer ' . $api_key,
                    );

                    if ( 'openrouter' === $engine ) {
                        $chat_headers['HTTP-Referer'] = home_url();
                        $chat_headers['X-Title']      = get_bloginfo( 'name' ) . ' - LinguaCommerce AI';
                    }

                    $chat_body = array(
                        'model'       => $model,
                        'messages'    => array(
                            array( 'role' => 'system', 'content' => 'You are a professional e-commerce translator. Return ONLY the translated text.' ),
                            array( 'role' => 'user', 'content' => $prompt ),
                        ),
                        'temperature' => 0.3,
                        'max_tokens'  => 2048,
                    );

                    $response = wp_remote_post( $endpoint, array(
                        'timeout' => 60,
                        'headers' => $chat_headers,
                        'body'    => wp_json_encode( $chat_body ),
                    ) );

                    if ( is_wp_error( $response ) ) {
                        return $response;
                    }
                    $resp_body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $resp_body['choices'][0]['message']['content'] ) ) {
                        $translated = trim( $resp_body['choices'][0]['message']['content'] );
                        // Nettoyage
                        $translated = preg_replace( '/^```(?:html|php|text)?\s*\n?/i', '', $translated );
                        $translated = preg_replace( '/\n?```\s*$/i', '', $translated );
                        $translated = trim( $translated );
                    }
                }
                break;
        }

        if ( empty( $translated ) ) {
            return new WP_Error( 'empty_translation', __( 'La traduction a retourné un texte vide.', 'lingua-commerce-ai' ) );
        }

        return $translated;
    }

    /**
     * Génère le sitemap multilingue sitemap-lingua.xml
     */
    public function ajax_generate_sitemap() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-language-service.php';
        }

        $active_languages = LinguaCommerce_Language_Service::get_active_languages();
        $default_language = LinguaCommerce_Language_Service::get_default_language();

        if ( empty( $active_languages ) ) {
            wp_send_json_error( array( 'message' => __( 'Aucune langue active trouvée.', 'lingua-commerce-ai' ) ) );
        }

        // Récupérer les types de contenu actifs
        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        $active_post_types = isset( $settings['active_post_types'] ) ? $settings['active_post_types'] : array( 'post', 'page', 'product' );

        // Construction du XML
        $xml_lines = array();
        $xml_lines[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml_lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $xml_lines[] = '        xmlns:xhtml="http://www.w3.org/1999/xhtml">';

        $default_code = $default_language ? $default_language->code : 'en_US';

        // Pour chaque type de contenu, récupérer les posts publiés
        foreach ( $active_post_types as $post_type ) {
            $posts = get_posts( array(
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => 5000,
                'fields'         => 'ids',
            ) );

            foreach ( $posts as $post_id ) {
                $xml_lines[] = '  <url>';

                // URL par défaut
                $default_url = get_permalink( $post_id );
                $xml_lines[] = '    <loc>' . esc_url( $default_url ) . '</loc>';
                $xml_lines[] = '    <lastmod>' . esc_html( get_post_modified_time( 'c', false, $post_id ) ) . '</lastmod>';
                $xml_lines[] = '    <changefreq>weekly</changefreq>';
                $xml_lines[] = '    <priority>0.8</priority>';

                // Alternates hreflang pour chaque langue active
                foreach ( $active_languages as $lang ) {
                    $lang_url = add_query_arg( 'lang', $lang->code, $default_url );
                    $xml_lines[] = '    <xhtml:link rel="alternate" hreflang="' . esc_attr( $lang->code ) . '" href="' . esc_url( $lang_url ) . '"/>';
                }

                $xml_lines[] = '  </url>';
            }
        }

        $xml_lines[] = '</urlset>';

        $xml_content = implode( "\n", $xml_lines );

        // Sauvegarde dans le répertoire racine
        $filepath = ABSPATH . 'sitemap-lingua.xml';
        $result   = file_put_contents( $filepath, $xml_content );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => __( 'Impossible d\'écrire le fichier sitemap. Vérifiez les permissions du répertoire.', 'lingua-commerce-ai' ) ) );
        }

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %s: File path */
                __( 'Sitemap généré avec succès : %s', 'lingua-commerce-ai' ),
                $filepath
            ),
            'filepath' => $filepath,
            'size'     => size_format( strlen( $xml_content ) ),
        ) );
    }

    // =========================================================================
    // ACTIONS DES OUTILS (admin_init)
    // =========================================================================

    /**
     * Gère les actions des outils : export_full_backup, import_full_backup, reset_settings
     */
    public function handle_tools_actions() {
        // Vérifier qu'on est sur la page outils du plugin
        if ( ! isset( $_GET['page'] ) || 'lingua-commerce-ai-tools' !== $_GET['page'] ) {
            return;
        }

        $action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

        if ( empty( $action ) ) {
            return;
        }

        // -----------------------------------------------------------------
        // EXPORT COMPLET
        // -----------------------------------------------------------------
        if ( 'export_full_backup' === $action ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission refusée.', 'lingua-commerce-ai' ) );
            }

            check_admin_referer( 'lingua_tools_action', 'lingua_tools_nonce' );

            global $wpdb;

            $backup = array(
                'plugin'    => 'lingua-commerce-ai',
                'version'   => LINGUA_COMMERCE_AI_VERSION,
                'exported'  => current_time( 'mysql' ),
                'site_url'  => home_url(),
                'tables'    => array(),
            );

            // Tables à exporter
            $tables_to_export = array(
                'lingua_translations',
                'lingua_languages',
                'lingua_ai_engines',
                'lingua_translation_queue',
                'lingua_logs',
            );

            foreach ( $tables_to_export as $table_suffix ) {
                $full_table = $wpdb->prefix . $table_suffix;
                if ( $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) === $full_table ) {
                    $rows = $wpdb->get_results( "SELECT * FROM $full_table", ARRAY_A );
                    $backup['tables'][ $table_suffix ] = $rows ? $rows : array();
                }
            }

            // Options
            $backup['options'] = array(
                'lingua_commerce_ai_settings' => get_option( 'lingua_commerce_ai_settings', array() ),
            );

            // Envoyer en téléchargement
            $filename = 'lingua-backup-' . date( 'Y-m-d-His' ) . '.json';
            header( 'Content-Type: application/json' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            echo wp_json_encode( $backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            exit;
        }

        // -----------------------------------------------------------------
        // IMPORT COMPLET
        // -----------------------------------------------------------------
        if ( 'import_full_backup' === $action ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission refusée.', 'lingua-commerce-ai' ) );
            }

            if ( ! isset( $_FILES['lingua_import_file'] ) || $_FILES['lingua_import_file']['error'] !== UPLOAD_ERR_OK ) {
                add_settings_error( 'lingua_tools', 'import_error', __( 'Erreur lors de l\'upload du fichier.', 'lingua-commerce-ai' ) );
                return;
            }

            check_admin_referer( 'lingua_import_nonce', 'lingua_import_nonce_field' );

            $file_path = $_FILES['lingua_import_file']['tmp_name'];
            $content   = file_get_contents( $file_path );
            $data      = json_decode( $content, true );

            if ( ! is_array( $data ) || ! isset( $data['plugin'] ) || 'lingua-commerce-ai' !== $data['plugin'] ) {
                add_settings_error( 'lingua_tools', 'import_error', __( 'Fichier de sauvegarde invalide.', 'lingua-commerce-ai' ) );
                return;
            }

            global $wpdb;
            $imported_count = 0;

            // Importer chaque table
            if ( isset( $data['tables'] ) && is_array( $data['tables'] ) ) {
                foreach ( $data['tables'] as $table_suffix => $rows ) {
                    $full_table = $wpdb->prefix . $table_suffix;
                    if ( $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) !== $full_table ) {
                        continue;
                    }

                    foreach ( $rows as $row ) {
                        // Vérifier les colonnes de la table
                        $columns = $wpdb->get_col( "DESCRIBE $full_table", 0 );
                        $clean_row = array();
                        foreach ( $row as $col => $val ) {
                            if ( in_array( $col, $columns, true ) ) {
                                $clean_row[ $col ] = $val;
                            }
                        }

                        if ( ! empty( $clean_row ) ) {
                            $wpdb->replace( $full_table, $clean_row );
                            $imported_count++;
                        }
                    }
                }
            }

            // Importer les options
            if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
                foreach ( $data['options'] as $option_key => $option_value ) {
                    update_option( $option_key, $option_value );
                    $imported_count++;
                }
            }

            add_settings_error(
                'lingua_tools',
                'import_success',
                sprintf(
                    /* translators: %d: Number of imported items */
                    __( 'Import terminé avec succès. %d élément(s) importé(s).', 'lingua-commerce-ai' ),
                    $imported_count
                ),
                'success'
            );
        }

        // -----------------------------------------------------------------
        // RÉINITIALISATION DES RÉGLAGES
        // -----------------------------------------------------------------
        if ( 'reset_settings' === $action ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'Permission refusée.', 'lingua-commerce-ai' ) );
            }

            check_admin_referer( 'lingua_tools_action', 'lingua_tools_nonce' );

            // Réinitialiser aux valeurs par défaut
            $default_settings = array(
                'active_post_types'    => array( 'post', 'page', 'product' ),
                'active_taxonomies'    => array( 'category', 'product_cat' ),
                'default_engine'       => '',
                'ai_tone'              => 'professional',
                'custom_instructions'  => '',
                'auto_validate'        => false,
                'translation_batch_size' => 5,
                'seo_auto_translate'   => false,
                'hreflang_enabled'     => true,
                'canonical_enabled'    => true,
                'cache_duration'       => 3600,
                'lazy_loading'         => true,
                'disable_on_mobile'    => false,
                'debug_mode'           => false,
                'log_retention_days'   => 30,
                'uninstall_remove_data' => false,
                'url_mode'             => 'query',
                'email_notifications'  => false,
                'email_on_error'       => true,
                'email_on_complete'    => false,
                'notification_email'   => get_option( 'admin_email' ),
            );

            update_option( 'lingua_commerce_ai_settings', $default_settings );

            // Purger les caches
            wp_cache_flush();

            add_settings_error(
                'lingua_tools',
                'reset_success',
                __( 'Réglages réinitialisés aux valeurs par défaut.', 'lingua-commerce-ai' ),
                'success'
            );
        }
    }

    // =========================================================================
    // OUTILS AJAX
    // =========================================================================

    /**
     * Vérifie l'état des tables de la base de données
     */
    public function ajax_check_tables() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        $required_tables = array(
            'lingua_translations'      => __( 'Traductions', 'lingua-commerce-ai' ),
            'lingua_languages'         => __( 'Langues', 'lingua-commerce-ai' ),
            'lingua_ai_engines'        => __( 'Moteurs IA', 'lingua-commerce-ai' ),
            'lingua_translation_queue' => __( 'File d\'attente', 'lingua-commerce-ai' ),
            'lingua_logs'              => __( 'Journaux', 'lingua-commerce-ai' ),
        );

        $results = array();
        $all_ok  = true;

        foreach ( $required_tables as $suffix => $label ) {
            $full_table = $wpdb->prefix . $suffix;
            $exists = ( $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) === $full_table );

            $row_count = 0;
            if ( $exists ) {
                $row_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $full_table" );
            } else {
                $all_ok = false;
            }

            $results[] = array(
                'table'     => $full_table,
                'label'     => $label,
                'exists'    => $exists,
                'row_count' => $row_count,
                'status'    => $exists ? 'ok' : 'missing',
            );
        }

        wp_send_json_success( array(
            'tables'  => $results,
            'all_ok'  => $all_ok,
            'message' => $all_ok
                ? __( 'Toutes les tables sont présentes et fonctionnelles.', 'lingua-commerce-ai' )
                : __( 'Certaines tables sont manquantes. Réactivez le plugin pour les recréer.', 'lingua-commerce-ai' ),
        ) );
    }

    /**
     * Nettoie les traductions orphelines
     */
    public function ajax_clean_orphans() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        $table_trans = $wpdb->prefix . 'lingua_translations';
        $deleted_count = 0;

        // 1. Supprimer les traductions dont le post n'existe plus
        $orphan_posts = $wpdb->get_results(
            "SELECT DISTINCT t.object_id, t.object_type
             FROM $table_trans t
             WHERE t.object_type NOT IN ('product_cat', 'product_tag', 'product_brand', 'category', 'post_tag', 'product_attributes')
             AND t.object_id NOT REGEXP '^[a-zA-Z]'
             AND CAST(t.object_id AS UNSIGNED) > 0
             AND NOT EXISTS (
                 SELECT 1 FROM {$wpdb->posts} p WHERE p.ID = CAST(t.object_id AS UNSIGNED)
             )"
        );

        foreach ( $orphan_posts as $orphan ) {
            $deleted = $wpdb->delete(
                $table_trans,
                array(
                    'object_id'   => $orphan->object_id,
                    'object_type' => $orphan->object_type,
                ),
                array( '%s', '%s' )
            );
            $deleted_count += (int) $deleted;
        }

        // 2. Supprimer les traductions dont le terme n'existe plus
        $orphan_terms = $wpdb->get_results(
            "SELECT DISTINCT t.object_id, t.object_type
             FROM $table_trans t
             WHERE t.object_type IN ('product_cat', 'product_tag', 'product_brand', 'category', 'post_tag')
             AND CAST(t.object_id AS UNSIGNED) > 0
             AND NOT EXISTS (
                 SELECT 1 FROM {$wpdb->terms} tm WHERE tm.term_id = CAST(t.object_id AS UNSIGNED)
             )"
        );

        foreach ( $orphan_terms as $orphan ) {
            $deleted = $wpdb->delete(
                $table_trans,
                array(
                    'object_id'   => $orphan->object_id,
                    'object_type' => $orphan->object_type,
                ),
                array( '%s', '%s' )
            );
            $deleted_count += (int) $deleted;
        }

        // 3. Supprimer les traductions dont la langue n'existe plus
        $table_langs = $wpdb->prefix . 'lingua_languages';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_langs'" ) === $table_langs ) {
            $orphan_langs = $wpdb->get_results(
                "SELECT DISTINCT t.language
                 FROM $table_trans t
                 WHERE NOT EXISTS (
                     SELECT 1 FROM $table_langs l WHERE l.code = t.language
                 )"
            );

            foreach ( $orphan_langs as $orphan ) {
                $deleted = $wpdb->delete(
                    $table_trans,
                    array( 'language' => $orphan->language ),
                    array( '%s' )
                );
                $deleted_count += (int) $deleted;
            }
        }

        // Purger le cache
        wp_cache_flush();

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %d: Number of deleted items */
                __( '%d traduction(s) orpheline(s) supprimée(s).', 'lingua-commerce-ai' ),
                $deleted_count
            ),
            'deleted_count' => $deleted_count,
        ) );
    }

    /**
     * Purge tout le cache
     */
    public function ajax_purge_cache() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        // 1. Supprimer tous les transients du plugin
        $transients = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_lingua_%'
             OR option_name LIKE '_site_transient_lingua_%'"
        );

        $deleted_transients = 0;
        foreach ( $transients as $transient ) {
            $key = str_replace( array( '_transient_', '_site_transient_' ), '', $transient );
            if ( delete_transient( $key ) ) {
                $deleted_transients++;
            }
        }

        // 2. Vider le cache objet WordPress
        wp_cache_flush();

        // 3. Vider le cache de la base de données du plugin
        if ( class_exists( 'LinguaCommerce_AI_Database' ) ) {
            // Le cache mémoire statique sera recréé à la prochaine requête
        }

        // 4. Purger les caches tiers si disponibles
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
        }

        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
        }

        if ( function_exists( 'wp_rocket_clean_domain' ) ) {
            wp_rocket_clean_domain();
        }

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %d: Number of purged transients */
                __( 'Cache purgé avec succès. %d transient(s) supprimé(s).', 'lingua-commerce-ai' ),
                $deleted_transients
            ),
            'deleted_transients' => $deleted_transients,
        ) );
    }

    /**
     * Retourne les informations de diagnostic système
     */
    public function ajax_get_system_status() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        // Informations serveur
        $server_info = array(
            'php_version'        => phpversion(),
            'php_memory_limit'   => ini_get( 'memory_limit' ),
            'php_max_execution'  => ini_get( 'max_execution_time' ),
            'php_upload_max'     => ini_get( 'upload_max_filesize' ),
            'php_post_max'       => ini_get( 'post_max_size' ),
            'mysql_version'      => $wpdb->db_version(),
            'wp_version'         => get_bloginfo( 'version' ),
            'wp_memory_limit'    => WP_MEMORY_LIMIT,
            'wp_debug'           => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'site_url'           => home_url(),
            'admin_url'          => admin_url(),
            'server_software'    => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ) : 'N/A',
            'ssl_enabled'        => is_ssl(),
        );

        // Extensions PHP requises
        $required_extensions = array(
            'curl'      => extension_loaded( 'curl' ),
            'json'      => extension_loaded( 'json' ),
            'mbstring'  => extension_loaded( 'mbstring' ),
            'openssl'   => extension_loaded( 'openssl' ),
            'dom'       => extension_loaded( 'dom' ),
            'xml'       => extension_loaded( 'xml' ),
        );

        // Plugins actifs pertinents
        $relevant_plugins = array(
            'woocommerce'       => class_exists( 'WooCommerce' ),
            'yoast_seo'         => defined( 'WPSEO_VERSION' ),
            'rank_math'         => defined( 'RANK_MATH_VERSION' ),
            'aioseo'            => defined( 'AIOSEO_VERSION' ),
            'dokan'             => is_plugin_active( 'dokan-lite/dokan.php' ),
            'wcfm'              => is_plugin_active( 'wc-frontend-manager/wc_frontend_manager.php' ),
            'wpml'              => defined( 'ICL_SITEPRESS_VERSION' ),
            'polylang'          => defined( 'POLYLANG_VERSION' ),
        );

        // Version WooCommerce
        $wc_version = class_exists( 'WooCommerce' ) ? WC()->version : null;

        // Statistiques du plugin
        $table_trans  = $wpdb->prefix . 'lingua_translations';
        $table_langs  = $wpdb->prefix . 'lingua_languages';
        $table_engines = $wpdb->prefix . 'lingua_ai_engines';
        $table_queue  = $wpdb->prefix . 'lingua_translation_queue';

        $stats = array(
            'total_translations' => 0,
            'active_languages'   => 0,
            'active_engines'     => 0,
            'pending_queue'      => 0,
            'failed_queue'       => 0,
        );

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_trans'" ) === $table_trans ) {
            $stats['total_translations'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_trans" );
        }

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_langs'" ) === $table_langs ) {
            $stats['active_languages'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_langs WHERE is_active = 1" );
        }

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_engines'" ) === $table_engines ) {
            $stats['active_engines'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_engines WHERE status = 'active'" );
        }

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_queue'" ) === $table_queue ) {
            $stats['pending_queue'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_queue WHERE status = 'pending'" );
            $stats['failed_queue']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_queue WHERE status = 'failed'" );
        }

        // Cron jobs du plugin
        $crons = _get_cron_array();
        $plugin_crons = array();
        foreach ( $crons as $timestamp => $cron_group ) {
            foreach ( $cron_group as $hook => $events ) {
                if ( strpos( $hook, 'lingua' ) === 0 ) {
                    foreach ( $events as $event ) {
                        $plugin_crons[] = array(
                            'hook'      => $hook,
                            'next_run'  => date( 'Y-m-d H:i:s', $timestamp ),
                            'schedule'  => isset( $event['schedule'] ) ? $event['schedule'] : 'once',
                        );
                    }
                }
            }
        }

        wp_send_json_success( array(
            'server_info'         => $server_info,
            'required_extensions' => $required_extensions,
            'relevant_plugins'    => $relevant_plugins,
            'wc_version'          => $wc_version,
            'stats'               => $stats,
            'plugin_crons'        => $plugin_crons,
            'plugin_version'      => LINGUA_COMMERCE_AI_VERSION,
        ) );
    }

    /**
     * Retourne les statistiques de la file d'attente de traduction
     */
    public function ajax_get_queue_stats() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;
        $table_queue = $wpdb->prefix . 'lingua_translation_queue';

        $stats = array(
            'pending'   => 0,
            'processing' => 0,
            'completed' => 0,
            'failed'    => 0,
            'total'     => 0,
        );

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_queue'" ) === $table_queue ) {
            $stats['pending']    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_queue WHERE status = 'pending'" );
            $stats['processing'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_queue WHERE status = 'processing'" );
            $stats['completed']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_queue WHERE status = 'completed'" );
            $stats['failed']     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table_queue WHERE status = 'failed'" );
            $stats['total']      = $stats['pending'] + $stats['processing'] + $stats['completed'] + $stats['failed'];
        }

        wp_send_json_success( array( 'stats' => $stats ) );
    }

    /**
     * Relance les tâches échouées de la file d'attente
     */
    public function ajax_retry_failed_tasks() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;
        $table_queue = $wpdb->prefix . 'lingua_translation_queue';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_queue'" ) !== $table_queue ) {
            wp_send_json_error( array( 'message' => __( 'Table de file d\'attente introuvable.', 'lingua-commerce-ai' ) ) );
        }

        $result = $wpdb->update(
            $table_queue,
            array(
                'status'        => 'pending',
                'attempts'      => 0,
                'error_message' => '',
                'updated_at'    => current_time( 'mysql' ),
            ),
            array( 'status' => 'failed' ),
            array( '%s', '%d', '%s', '%s' ),
            array( '%s' )
        );

        $count = (int) $result;

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %d: Number of retried tasks */
                __( '%d tâche(s) échouée(s) replacée(s) en file d\'attente.', 'lingua-commerce-ai' ),
                $count
            ),
            'retried_count' => $count,
        ) );
    }

    /**
     * Vide complètement la file d'attente
     */
    public function ajax_clear_all_queue() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;
        $table_queue = $wpdb->prefix . 'lingua_translation_queue';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_queue'" ) === $table_queue ) {
            $wpdb->query( "TRUNCATE TABLE $table_queue" );
        }

        wp_send_json_success( array(
            'message' => __( 'File d\'attente vidée avec succès.', 'lingua-commerce-ai' ),
        ) );
    }

    /**
     * Déclenche manuellement le cron de traduction
     */
    public function ajax_trigger_cron() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;
        $table_queue = $wpdb->prefix . 'lingua_translation_queue';

        $processed = 0;
        $errors    = 0;

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_queue'" ) === $table_queue ) {
            // Récupérer les tâches en attente
            $pending_tasks = $wpdb->get_results(
                "SELECT * FROM $table_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10"
            );

            foreach ( $pending_tasks as $task ) {
                // Marquer comme en cours de traitement
                $wpdb->update(
                    $table_queue,
                    array(
                        'status'     => 'processing',
                        'updated_at' => current_time( 'mysql' ),
                    ),
                    array( 'id' => $task->id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );

                // Appeler l'API de traduction via call_ai_api_seo
                $result = $this->call_ai_api_seo(
                    $task->original_text ?? '',
                    $task->language ?? '',
                    $task->engine ?? ''
                );

                if ( is_wp_error( $result ) || empty( $result ) ) {
                    // Échec
                    $attempts = (int) $task->attempts + 1;
                    $wpdb->update(
                        $table_queue,
                        array(
                            'status'        => 'failed',
                            'attempts'      => $attempts,
                            'error_message' => is_wp_error( $result ) ? $result->get_error_message() : 'Empty translation',
                            'updated_at'    => current_time( 'mysql' ),
                        ),
                        array( 'id' => $task->id ),
                        array( '%s', '%d', '%s', '%s' ),
                        array( '%d' )
                    );
                    $errors++;
                } else {
                    // Succès - Sauvegarder la traduction
                    if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
                        require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-translation-model.php';
                    }

                    $settings = get_option( 'lingua_commerce_ai_settings', array() );
                    $auto_validate = isset( $settings['auto_validate'] ) ? (bool) $settings['auto_validate'] : false;
                    $status = $auto_validate ? 'validated' : 'draft';

                    LinguaCommerce_Translation_Model::save_translation(
                        $task->object_id,
                        $task->object_type,
                        $task->field_key,
                        $task->language,
                        $result,
                        $status
                    );

                    // Marquer comme complété
                    $wpdb->update(
                        $table_queue,
                        array(
                            'status'     => 'completed',
                            'updated_at' => current_time( 'mysql' ),
                        ),
                        array( 'id' => $task->id ),
                        array( '%s', '%s' ),
                        array( '%d' )
                    );

                    $processed++;
                }
            }
        }

        wp_send_json_success( array(
            'message'   => sprintf(
                /* translators: 1: Processed count, 2: Error count */
                __( 'Cron exécuté : %1$d traduction(s) traitée(s), %2$d erreur(s).', 'lingua-commerce-ai' ),
                $processed,
                $errors
            ),
            'processed' => $processed,
            'errors'    => $errors,
        ) );
    }
}
