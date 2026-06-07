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
     * Le nom du plugin
     */
    private $plugin_name;

    /**
     * La version du plugin
     */
    private $version;

       /**
     * Constructeur
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // NOTE : L'instanciation des sous-modules (comme Languages) est gérée dynamiquement par class-lingua-init.php pour éviter les conflits.
        
        // Enregistrement des actions AJAX pour le module SEO
        add_action( 'wp_ajax_lingua_get_seo_audit', array( $this, 'ajax_get_seo_audit' ) );
        add_action( 'wp_ajax_lingua_quick_fix_item', array( $this, 'ajax_quick_fix_item' ) );
         add_action( 'wp_ajax_lingua_generate_sitemap', array( $this, 'ajax_generate_sitemap' ) );
                 // Enregistrement de l'action pour vider le cache
        add_action( 'wp_ajax_lingua_purge_cache', array( $this, 'ajax_purge_cache' ) );
        
                // -- NOUVEAUX HOOKS POUR LA PAGE OUTILS --
        add_action( 'admin_init', array( $this, 'handle_tools_actions' ) );
        add_action( 'wp_ajax_lingua_clean_orphans', array( $this, 'ajax_clean_orphans' ) );
        add_action( 'wp_ajax_lingua_check_tables', array( $this, 'ajax_check_tables' ) );
        
                // --- HOOKS AJAX POUR LE CENTRE DE CONTROLE IA ---
        add_action( 'wp_ajax_lingua_get_queue_stats', array( $this, 'ajax_get_queue_stats' ) );
        add_action( 'wp_ajax_lingua_retry_failed_tasks', array( $this, 'ajax_retry_failed_tasks' ) );
        add_action( 'wp_ajax_lingua_clear_all_queue', array( $this, 'ajax_clear_all_queue' ) );
        add_action( 'wp_ajax_lingua_trigger_cron', array( $this, 'ajax_trigger_cron' ) );
        
                // --- HOOKS AJAX MANQUANTS (DIAGNOSTIC & LOGS) ---
        add_action( 'wp_ajax_lingua_get_system_status', array( $this, 'ajax_get_system_status' ) );
    }
    /**
     * Enregistre les stylesheets pour l'administration
     */
    public function enqueue_styles() {
        // NOTE : Les styles sont gérés directement dans les fichiers 'partials' pour l'instant.
    }

    /**
     * Enregistre les scripts pour l'administration
     */
       public function enqueue_scripts() {
        // Désactivé car le JS est actuellement inline dans les vues (partials)
        // wp_enqueue_script(
        //     $this->plugin_name,
        //     plugin_dir_url( __FILE__ ) . 'js/lingua-commerce-ai-admin.js',
        //     array( 'jquery' ),
        //     $this->version,
        //     false
        // );
    }

    /**
     * Ajoute le menu d'administration et ses sous-menus pour le plugin
     */
    public function add_plugin_admin_menu() {
        // Ajout du menu principal
        add_menu_page(
            'LinguaCommerce AI', // Page Title
            'LinguaCommerce AI', // Menu Title
            'manage_options',    // Capability
            $this->plugin_name,  // Menu Slug
            array( $this, 'display_dashboard_page' ), // Fonction pour le premier sous-menu
            'dashicons-translation', // Icon
            25 // Position
        );

        // Sous-menu 1 : Tableau de bord
        add_submenu_page(
            $this->plugin_name,
            'Tableau de bord',
            'Tableau de bord',
            'manage_options',
            $this->plugin_name,
            array( $this, 'display_dashboard_page' )
        );

        // Sous-menu 2 : Traductions
        add_submenu_page(
            $this->plugin_name,
            'Traductions',
            'Traductions',
            'manage_options',
            $this->plugin_name . '-translations',
            array( $this, 'display_translations_page' )
        );

        // Sous-menu 3 : IA & Automatisation
        add_submenu_page(
            $this->plugin_name,
            'IA & Automatisation',
            'IA & Automatisation',
            'manage_options',
            $this->plugin_name . '-ai',
            array( $this, 'display_ai_page' )
        );

        // Sous-menu 4 : Langues
        add_submenu_page(
            $this->plugin_name,
            'Langues',
            'Langues',
            'manage_options',
            $this->plugin_name . '-languages',
            array( $this, 'display_languages_page' )
        );

        // Sous-menu 5 : SEO Multilingue
        add_submenu_page(
            $this->plugin_name,
            'SEO Multilingue',
            'SEO Multilingue',
            'manage_options',
            $this->plugin_name . '-seo',
            array( $this, 'display_seo_page' )
        );

        // Sous-menu 6 : Paramètres
        add_submenu_page(
            $this->plugin_name,
            'Paramètres',
            'Paramètres',
            'manage_options',
            $this->plugin_name . '-settings',
            array( $this, 'display_settings_page' )
        );
        
        // Sous-menu 7 : Outils avancés
        add_submenu_page(
            $this->plugin_name,
            'Outils avancés',
            'Outils avancés',
            'manage_options',
            $this->plugin_name . '-tools',
            array( $this, 'display_tools_page' )
        );

        // --- NOUVEAUX MENUS MANQUANTS ---

        // Sous-menu 8 : Menus & Widgets
        add_submenu_page(
            $this->plugin_name,
            'Menus & Widgets',
            'Menus & Widgets',
            'manage_options',
            $this->plugin_name . '-menus',
            array( $this, 'display_menus_page' )
        );

        // Sous-menu 9 : Multivendeur
        add_submenu_page(
            $this->plugin_name,
            'Multivendeur',
            'Multivendeur',
            'manage_options',
            $this->plugin_name . '-multivendor',
            array( $this, 'display_multivendor_page' )
        );

        // Sous-menu 10 : Emails / Notifications
        add_submenu_page(
            $this->plugin_name,
            'Emails & Notifs',
            'Emails & Notifs',
            'manage_options',
            $this->plugin_name . '-emails',
            array( $this, 'display_emails_page' )
        );

        // Sous-menu 11 : Import / Export
        add_submenu_page(
            $this->plugin_name,
            'Import / Export',
            'Import / Export',
            'manage_options',
            $this->plugin_name . '-import-export',
            array( $this, 'display_import_export_page' )
        );

        // Sous-menu 12 : Aide & Documentation
        add_submenu_page(
            $this->plugin_name,
            'Aide',
            'Aide',
            'manage_options',
            $this->plugin_name . '-help',
            array( $this, 'display_help_page' )
        );
    }

    /**
     * Affiche la page du Tableau de bord
     */
    public function display_dashboard_page() {
        $file_path = plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-dashboard.php';
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="notice notice-error"><p>Erreur : Le fichier d\'affichage du tableau de bord est introuvable.</p></div>';
        }
    }
    
    
        /**
     * Ajoute des liens d'action sur la page des plugins (page extensions).
     * Ajoute un lien "Réglages" à côté de "Désactiver".
     *
     * @param array $links Les liens existants.
     * @return array Les liens modifiés.
     */
    public function add_plugin_action_links( $links ) {
        $settings_link = '<a href="admin.php?page=' . esc_attr( $this->plugin_name ) . '">' . esc_html__( 'Réglages', 'lingua-commerce-ai' ) . '</a>';
        
        // On place le lien de réglage au début du tableau
        array_unshift( $links, $settings_link );
        
        return $links;
    }

    /**
     * Affiche la page des Traductions
     */
    public function display_translations_page() {
        if ( class_exists( 'LinguaCommerce_AI_Admin_Translations' ) ) {
            $translations_page = new LinguaCommerce_AI_Admin_Translations();
            
            if ( method_exists( $translations_page, 'render' ) ) {
                $translations_page->render();
            }
        } else {
            echo '<div class="notice notice-warning"><p>Erreur : Le module de gestion des traductions n\'a pas pu être chargé.</p></div>';
        }
    }

         /**
     * Affiche la page IA & Automatisation
     * DÉLÉGUÉ au contrôleur dédié
     */
    public function display_ai_page() {
        // Vérification si la classe existe (au cas où le fichier n'est pas chargé)
        // NOTE : Le nom de la classe réelle dans le fichier est 'Lingua_Admin_AI'
        if ( ! class_exists( 'Lingua_Admin_AI' ) ) {
            // On charge le fichier manuellement pour être sûr
            require_once plugin_dir_path( __FILE__ ) . 'class-lingua-admin-ai.php';
        }

        // On instancie avec le BON nom de classe
        $ai_page = new Lingua_Admin_AI();
        
        if ( method_exists( $ai_page, 'render' ) ) {
            $ai_page->render();
        } else {
            echo '<div class="notice notice-error"><p>Erreur : Le module IA n\'a pas pu être chargé.</p></div>';
        }
    }

    /**
     * Affiche la page de gestion des Langues
     */
    public function display_languages_page() {
        if ( ! class_exists( 'LinguaCommerce_AI_Admin_Languages' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-lingua-admin-languages.php';
        }

        $languages_page = new LinguaCommerce_AI_Admin_Languages();
        
        if ( method_exists( $languages_page, 'render' ) ) {
            $languages_page->render();
        }
    }

    /**
     * Affiche la page SEO Multilingue
     */
    public function display_seo_page() {
        // Chargement des services nécessaires pour cette page
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
        }

        $default_lang = LinguaCommerce_Language_Service::get_default_language();
        $active_languages = LinguaCommerce_Language_Service::get_active_languages();
        $settings = get_option( 'lingua_commerce_ai_settings', array() );

        // Détection SEO
        $has_yoast = defined( 'WPSEO_VERSION' );
        $has_rankmath = defined( 'RANK_MATH_VERSION' );
        $has_aioseo = defined( 'AIOSEO_VERSION' );

        // Chargement du fichier de vue
        $file_path = plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-seo.php';
        if ( file_exists( $file_path ) ) {
            include $file_path;
        } else {
            echo '<div class="notice notice-error"><p>Erreur : Le fichier d\'affichage SEO est introuvable.</p></div>';
        }
    }

        /**
     * Affiche la page des Paramètres
     */
    public function display_settings_page() {
        // Récupération des options existantes
        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        
        // Récupération de la liste des langues pour le sélecteur "Langue par défaut"
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
        }
        $installed_languages = LinguaCommerce_Language_Service::get_active_languages();

        // Chargement de la vue
        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-settings.php';
    }
    
          /**
     * Affiche la page des Outils avancés
     */
    public function display_tools_page() {
        $file_path = plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-tools.php';
        
        // Vérification de sécurité : si le fichier n'existe pas, on affiche un message au lieu de planter
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="wrap"><h1>Erreur de Configuration</h1>';
            echo '<div class="notice notice-error"><p>Le fichier de vue <code>admin/partials/lingua-commerce-ai-admin-display-tools.php</code> est manquant. Veuillez le créer.</p></div>';
            echo '</div>';
        }
    }
        /**
     * Affiche la page Menus & Widgets
     */
    public function display_menus_page() {
        // Chargement du fichier de vue dédié
        $file_path = plugin_dir_path( __FILE__ ) . 'partials/lingua-commerce-ai-admin-display-menus.php';
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        } else {
            echo '<div class="notice notice-error"><p>Erreur : Le fichier d\'affichage Menus est introuvable.</p></div>';
        }
    }

    /**
     * Affiche la page Multivendeur
     */
    public function display_multivendor_page() {
        echo '<div class="wrap"><h1>'. esc_html( get_admin_page_title() ).'</h1>';
        echo '<p><strong>Gestion Multivendeur (Dokan / WCFM)</strong></p>';
        echo '<table class="form-table">';
        echo '<tr><th><label>Autoriser les vendeurs à traduire</label></th><td><select><option>Oui, tous les vendeurs</option><option>Non, admin uniquement</option></select></td></tr>';
        echo '<tr><th><label>Quota IA par vendeur</label></th><td><input type="number" value="5000" disabled></td></tr>';
        echo '</table>';
        echo '<p class="description">Fonctionnalité en développement.</p>';
        echo '</div>';
    }

    /**
     * Affiche la page Emails / Notifications
     */
    public function display_emails_page() {
        echo '<div class="wrap"><h1>'. esc_html( get_admin_page_title() ).'</h1>';
        echo '<p><strong>Traduction des Emails WooCommerce et Notifications</strong></p>';
        echo '<p>Permet de traduire les emails de confirmation de commande, de création de compte, etc.</p>';
        echo '<p class="description">Fonctionnalité en développement.</p>';
        echo '</div>';
    }

    /**
     * Affiche la page Import / Export
     */
    public function display_import_export_page() {
        echo '<div class="wrap"><h1>'. esc_html( get_admin_page_title() ).'</h1>';
        echo '<p><strong>Exportation et Sauvegarde</strong></p>';
        echo '<table class="form-table">';
        echo '<tr><th>Exporter les traductions (CSV)</th><td><button class="button button-primary">Télécharger CSV</button></td></tr>';
        echo '<tr><th>Sauvegarder la configuration</th><td><button class="button">Sauvegarder</button></td></tr>';
        echo '</table>';
        echo '<p class="description">Fonctionnalité en développement.</p>';
        echo '</div>';
    }

    /**
     * Affiche la page Aide
     */
    public function display_help_page() {
        echo '<div class="wrap"><h1>'. esc_html( get_admin_page_title() ).'</h1>';
        echo '<h2>Documentation</h2>';
        echo '<p>Guide d\'utilisation rapide.</p>';
        echo '<ul><li><a href="#" target="_blank">Comment configurer ma langue par défaut ?</a></li>';
        echo '<li><a href="#" target="_blank">Optimiser le SEO multilingue</a></li></ul>';
        echo '</div>';
    }

       public function register_settings() {
        register_setting(
            'lingua_commerce_ai_settings_group', // Nom du groupe (doit matcher settings_fields())
            'lingua_commerce_ai_settings',       // Nom de l'option
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => array(
                    'default_language' => 'fr_FR',
                    'url_mode'         => 'param',
                    'cache_duration'   => 3600,
                    'fallback_mode'    => 'original',
                    'batch_size'       => 5,
                    'active_post_types'=> array('post', 'page', 'product')
                )
            )
        );
    }

         /**
     * Nettoie et valide les entrées du formulaire
     * VERSION : Consolidation SEO & Amélioration de la gestion des cases à cocher.
     *
     * @param array $input Le tableau des entrées brutes ($_POST).
     * @return array Le tableau nettoyé et validé.
     */
    public function sanitize_settings( $input ) {
        $new_input = array();

        // --- 1. ONGLET GÉNÉRAL ---
        if ( isset( $input['default_language'] ) ) {
            $new_input['default_language'] = sanitize_text_field( $input['default_language'] );
        }
        if ( isset( $input['url_mode'] ) ) {
            $new_input['url_mode'] = sanitize_text_field( $input['url_mode'] );
        }
        
        // Gestion des cases à cocher (Checkbox : 0 ou 1)
        $checkboxes_general = array(
            'browser_redirect',
            'admin_translation',
            'media_translation'
        );
        foreach ( $checkboxes_general as $key ) {
            $new_input[ $key ] = isset( $input[ $key ] ) ? 1 : 0;
        }

        // --- 6. ONGLET AFFICHAGE (Positionnement & Style) ---
        if ( isset( $input['selector_template'] ) ) {
            $new_input['selector_template'] = sanitize_text_field( $input['selector_template'] );
        }
        if ( isset( $input['selector_position'] ) ) {
            $new_input['selector_position'] = sanitize_text_field( $input['selector_position'] );
        }
        if ( isset( $input['flag_style'] ) ) {
            $new_input['flag_style'] = sanitize_text_field( $input['flag_style'] );
        }
        if ( isset( $input['selector_align'] ) ) {
            $new_input['selector_align'] = sanitize_text_field( $input['selector_align'] );
        }
        if ( isset( $input['selector_margin'] ) ) {
            $new_input['selector_margin'] = sanitize_text_field( $input['selector_margin'] );
        }

        // --- 7. ONGLET SHORTCODE (Paramètres spécifiques) ---
        if ( isset( $input['sc_template'] ) ) {
            $new_input['sc_template'] = sanitize_text_field( $input['sc_template'] );
        }
        
        $checkboxes_shortcode = array(
            'sc_show_name',
            'sc_hide_current'
        );
        foreach ( $checkboxes_shortcode as $key ) {
            $new_input[ $key ] = isset( $input[ $key ] ) ? 1 : 0;
        }
        
        if ( isset( $input['sc_align'] ) ) {
            $new_input['sc_align'] = sanitize_text_field( $input['sc_align'] );
        }

        // --- 8. ONGLET PERFORMANCE (Cache & Exclusions) ---
        if ( isset( $input['cache_duration'] ) ) {
            $new_input['cache_duration'] = absint( $input['cache_duration'] );
        }
        if ( isset( $input['excluded_ids'] ) ) {
            $new_input['excluded_ids'] = sanitize_text_field( $input['excluded_ids'] );
        }

        // --- 9. ONGLET CONTENUS (Types de contenus & Taxonomies) ---
        if ( isset( $input['active_post_types'] ) && is_array( $input['active_post_types'] ) ) {
            $new_input['active_post_types'] = array_map( 'sanitize_text_field', $input['active_post_types'] );
        } else {
            $new_input['active_post_types'] = array();
        }

        if ( isset( $input['active_taxonomies'] ) && is_array( $input['active_taxonomies'] ) ) {
            $new_input['active_taxonomies'] = array_map( 'sanitize_text_field', $input['active_taxonomies'] );
        } else {
            $new_input['active_taxonomies'] = array();
        }

        if ( isset( $input['custom_fields'] ) ) {
            $new_input['custom_fields'] = sanitize_textarea_field( $input['custom_fields'] );
        }

        // --- 10. ONGLET AUTOMATISATION (Lots & Cron) ---
        if ( isset( $input['batch_size'] ) ) {
            $new_input['batch_size'] = absint( $input['batch_size'] );
        }
        
        $new_input['auto_publish_queue'] = isset( $input['auto_publish_queue'] ) ? 1 : 0;
        
        if ( isset( $input['cron_frequency'] ) ) {
            $new_input['cron_frequency'] = sanitize_text_field( $input['cron_frequency'] );
        }

        // --- 11. ONGLET IA (Ton & Moteur par défaut) ---
        if ( isset( $input['ai_tone'] ) ) {
            $new_input['ai_tone'] = sanitize_text_field( $input['ai_tone'] );
        }
        if ( isset( $input['default_engine'] ) ) {
            $new_input['default_engine'] = sanitize_text_field( $input['default_engine'] );
        }
        if ( isset( $input['custom_instructions'] ) ) {
            $new_input['custom_instructions'] = sanitize_textarea_field( $input['custom_instructions'] );
        }
        
        $new_input['auto_validate'] = isset( $input['auto_validate'] ) ? 1 : 0;

        // --- 12. ONGLET SEO (Moteur IA SEO & Paramètres) ---
        $new_input['seo_ai_enabled'] = isset( $input['seo_ai_enabled'] ) ? 1 : 0;
        
        if ( isset( $input['seo_engine'] ) ) {
            $new_input['seo_engine'] = sanitize_text_field( $input['seo_engine'] );
        }
        
        // Gestion du champ 'content_tone' (peut être utilisé pour IA ou SEO)
        if ( isset( $input['content_tone'] ) ) {
            $new_input['content_tone'] = sanitize_text_field( $input['content_tone'] );
        }
        
        if ( isset( $input['keyword_strategy'] ) ) {
            $new_input['keyword_strategy'] = sanitize_text_field( $input['keyword_strategy'] );
        }
        
        if ( isset( $input['seo_title_length'] ) ) {
            $new_input['seo_title_length'] = absint( $input['seo_title_length'] );
        }

        // --- 13. ONGLET MENUS & WIDGETS (Gestion dynamique) ---
        $new_input['menu_switcher_id'] = isset( $input['menu_switcher_id'] ) ? absint( $input['menu_switcher_id'] ) : 0;
        $new_input['menu_switcher_style'] = isset( $input['menu_switcher_style'] ) ? sanitize_text_field( $input['menu_switcher_style'] ) : 'flags_only';
        $new_input['auto_adjust_menu_links'] = isset( $input['auto_adjust_menu_links'] ) ? 1 : 0;
        
        // Elementor Templates (Sauvegarde dynamique pour chaque langue)
        // On boucle sur les clés commençant par 'elementor_header_' ou 'elementor_footer_'
        if ( ! empty( $input ) ) {
            foreach ( $input as $key => $val ) {
                if ( strpos( $key, 'elementor_header_' ) === 0 || strpos( $key, 'elementor_footer_' ) === 0 ) {
                    $new_input[ $key ] = absint( $val );
                }
                // Checkboxes génériques pour les widgets/Filtres
                if ( strpos( $key, 'widget_filter_' ) === 0 ) {
                    $new_input[ $key ] = 1;
                }
            }
        }

        // Options d'affichage globales
        $new_input['translate_site_title'] = isset( $input['translate_site_title'] ) ? 1 : 0;
        $new_input['translate_tagline'] = isset( $input['translate_tagline'] ) ? 1 : 0;
        $new_input['translate_wc_buttons'] = isset( $input['translate_wc_buttons'] ) ? 1 : 0;
        $new_input['translate_wc_notices'] = isset( $input['translate_wc_notices'] ) ? 1 : 0;
        $new_input['admin_filter_untranslated'] = isset( $input['admin_filter_untranslated'] ) ? 1 : 0;
        
        // Formats de date (Dynamique : date_format_XX)
        if ( ! empty( $input ) ) {
            foreach ( $input as $key => $val ) {
                if ( strpos( $key, 'date_format_' ) === 0 ) {
                    $new_input[ $key ] = sanitize_text_field( $val );
                }
                // RTL (Dynamique : is_rtl_XX)
                if ( strpos( $key, 'is_rtl_' ) === 0 ) {
                    $new_input[ $key ] = 1;
                }
                // Custom CSS (Sécurité : wp_strip_all_tags)
                if ( strpos( $key, 'custom_css_' ) === 0 ) {
                    $new_input[ $key ] = wp_strip_all_tags( $val );
                }
            }
        }

        // Support Body Class
        $new_input['enable_body_class'] = isset( $input['enable_body_class'] ) ? 1 : 0;
        
        // Titres des widgets WooCommerce
        if ( isset( $input['wc_widget_titles'] ) ) {
            $new_input['wc_widget_titles'] = sanitize_textarea_field( $input['wc_widget_titles'] );
        }

        return $new_input;
    }
    // ------------------------------------------------------------------
    // HANDLERS AJAX (AJOUTÉS POUR LA SYMCHRONISATION)
    // ------------------------------------------------------------------

       /**
     * AJAX : Récupérer le tableau d'Audit SEO
     * Version avec boutons Quick Fix IA intégrés
     */
    public function ajax_get_seo_audit() {
        // Vérification de sécurité
        if ( ! check_ajax_referer( 'lingua_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Erreur de sécurité.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission refusée.' );
        }

        $post_type  = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : 'page';
        $target_lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : 'en_US';
        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-translation-model.php';
        $items = LinguaCommerce_Translation_Model::get_seo_audit_data( $post_type, $target_lang );

        $rows_html = '';
        
        if ( empty( $items ) ) {
            $rows_html = '<tr><td colspan="6" style="text-align:center; padding: 40px;">Aucun contenu trouvé.</td></tr>';
        } else {
            foreach ( $items as $item ) {
                $rows_html .= '<tr>';
                
                // Colonne 1 : Titre
                $rows_html .= '<td class="lingua-sticky-col" style="position: sticky; left: 0; background: #fff; border-right: 1px solid #ddd; z-index: 10; padding: 10px;">';
                $rows_html .= '<strong>' . esc_html( $item->post_title ) . '</strong><br><small style="color:#666;">ID: ' . $item->ID . ' | ' . ucfirst( $item->post_type ) . '</small>';
                $rows_html .= '</td>';
                
                // Colonne 2 : Statut Traduction
                $rows_html .= '<td style="text-align:center;">';
                if ( $item->progress_percent < 100 ) {
                    $rows_html .= '<span style="color: #d63638; font-weight:bold;">❌ Non traduit</span>';
                } else {
                    $rows_html .= '<span style="color: #00a32a; font-weight:bold;">✓ Traduit</span>';
                }
                $rows_html .= '</td>';
                
                // Colonne 3 : Qualité SEO (Score + Bouton IA)
                $rows_html .= '<td style="text-align:center;">';
                
                // LOGIQUE D'AFFICHAGE DES BOUTONS
                if ( $item->progress_percent < 100 ) {
                    // CAS ROUGE : Pas traduit
                    $rows_html .= '<span class="dashicons dashicons-dismiss" style="color: #d63638; font-size:20px;"></span><br>';
                    // Bouton Quick Fix : Traduire tout
                    $rows_html .= '<button class="button button-small lingua-quick-fix-btn" 
                                    data-id="' . $item->ID . '" 
                                    data-type="' . $item->post_type . '" 
                                    data-action="translate_all" 
                                    style="margin-top:5px; font-size: 11px;">
                                    🤖 Traduire (IA)
                                   </button>';
                } elseif ( ! $item->has_seo_title ) {
                    // CAS ORANGE : Traduit mais pas de Titre SEO
                    $rows_html .= '<span class="dashicons dashicons-warning" style="color: #dba617; font-size:20px;"></span><br>';
                    // Bouton Quick Fix : Générer Titre
                    $rows_html .= '<button class="button button-small lingua-quick-fix-btn" 
                                    data-id="' . $item->ID . '" 
                                    data-type="' . $item->post_type . '" 
                                    data-action="fix_title" 
                                    style="margin-top:5px; font-size: 11px;">
                                    ⚡ Générer Titre SEO
                                   </button>';
                } else {
                    // CAS VERT : Tout est bon
                    $rows_html .= '<span class="dashicons dashicons-yes-alt" style="color: #00a32a; font-size:20px;"></span>';
                }
                
                $rows_html .= '</td>';
                
                // Colonne 4 : Meta Title
                $rows_html .= '<td>' . ( $item->has_seo_title ? esc_html( $item->seo_title_text ) : '<span style="color:#ccc;">--</span>' ) . '</td>';
                
                // Colonne 5 : Meta Desc
                $rows_html .= '<td>' . ( $item->has_seo_desc ? '<span style="color:green;">✓</span>' : '<span style="color:#ccc;">--</span>' ) . '</td>';

                // Colonne 6 : Actions (Lien édition manuelle)
                $rows_html .= '<td style="text-align: right;">';
                $rows_html .= '<a href="' . admin_url( 'admin.php?page=lingua-commerce-ai-translations' ) . '" class="button button-small" style="color:#2271b1;">Éditer manuellement</a>';
                $rows_html .= '</td>';
                
                $rows_html .= '</tr>';
            }
        }

        wp_send_json_success( array( 'html' => $rows_html ) );
    }

       /**
     * AJAX : Action Rapide / Quick Fix (Connecté à l'IA)
     * Gère la traduction complète ou la génération de Titre SEO.
     */
    public function ajax_quick_fix_item() {
        // 1. Sécurité
        if ( ! check_ajax_referer( 'lingua_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Erreur de sécurité.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission refusée.' );
        }

        // 2. Récupération des paramètres
        $object_id    = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $object_type  = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'page';
        $target_lang  = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : 'en_US';
        $action_type  = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : '';
        $engine_name  = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : 'default';

        if ( ! $object_id ) {
            wp_send_json_error( 'ID invalide.' );
        }

        // 3. Récupération de la configuration IA
        $api_key = '';
        $model_name = '';
        $settings = array();
        
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_ai_engines';
        
        // Si un moteur spécifique est demandé
        if ( $engine_name && $engine_name !== 'default' ) {
            $engine_config = $wpdb->get_row( $wpdb->prepare( "SELECT api_key, settings FROM $table WHERE engine_name = %s AND status = 'active'", $engine_name ) );
            if ( $engine_config ) {
                $api_key = $engine_config->api_key;
                $settings = maybe_unserialize( $engine_config->settings );
            }
        } 
        
        // Sinon, on prend le moteur par défaut global
        if ( empty($api_key) ) {
            $global_settings = get_option('lingua_commerce_ai_settings');
            $default_slug = isset($global_settings['default_engine']) ? $global_settings['default_engine'] : 'openrouter';
            
            // On vérifie si le slug par défaut est valide
            $engine_config = $wpdb->get_row( $wpdb->prepare( "SELECT api_key, settings FROM $table WHERE engine_name = %s AND status = 'active'", $default_slug ) );
            if($engine_config) {
                $api_key = $engine_config->api_key;
                $settings = maybe_unserialize( $engine_config->settings );
                // On force le engine_name pour la logique de l'API
                $engine_name = $default_slug;
            }
        }

        // Détermination du modèle
        if ( ! empty( $settings ) ) {
             if ( ! empty( $settings['model_paid'] ) ) {
                $model_name = $settings['model_paid'];
            } elseif ( ! empty( $settings['model_free'] ) ) {
                $model_name = $settings['model_free'];
            }
        }
        
        // Si toujours pas de clé, on abandonne
        if ( empty($api_key) ) {
             wp_send_json_error('Aucune clé API active trouvée pour ce moteur. Vérifiez la page "IA & Automatisation".');
        }

        // Inclusion du modèle
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-translation-model.php';
        
        $msg = 'Action effectuée.';
        $result = false;

        // --- CAS 1 : TRADUIRE TOUT (Champs standards) ---
        if ( $action_type === 'translate_all' ) {
            $fields = LinguaCommerce_Translation_Model::get_translatable_fields( $object_type );
            $count = 0;
            
            foreach ( $fields as $key => $config ) {
                $original = LinguaCommerce_Translation_Model::get_original_value( $object_id, $key, $object_type );
                if ( empty($original) ) continue;

                // Appel IA (Traduction standard)
                $translation = $this->call_ai_api_seo( $original, $target_lang, $api_key, $engine_name, $model_name );
                
                if ( $translation ) {
                    LinguaCommerce_Translation_Model::save_translation( $object_id, $object_type, $key, $target_lang, $translation, 'validated' );
                    $count++;
                }
            }
            $msg = "$count champs traduits par IA.";
            $result = true;
        }

        // --- CAS 2 : GÉNÉRER TITRE SEO (CORRECTION AIOSEO) ---
        elseif ( $action_type === 'fix_title' ) {
            // 1. Récupération du titre original
            $original_title = get_the_title( $object_id );
            if ( empty($original_title) ) {
                 wp_send_json_error('Impossible de récupérer le titre original.');
            }

            // 2. Préparation du Prompt IA (SEO Optimisé)
            $prompt = "Génère un titre SEO optimisé (max 60 caractères, percutant, incluant des mots-clés si pertinent) pour ce contenu en " . $target_lang . ". Titre original : " . $original_title . ". Renvoie uniquement le titre, sans guillemets ni commentaires.";
            
            // 3. Appel à l'IA (Mode Génération)
            // Note : Pour DeepL, qui n'a pas de mode "génération", on peut désactiver ce bouton ou utiliser une traduction simple.
            // Ici on suppose que call_ai_api_seo gère le mode "génération" pour les modèles type GPT/LLM.
            $seo_title = $this->call_ai_api_seo( $prompt, $target_lang, $api_key, $engine_name, $model_name, true );
            
            if ( $seo_title ) {
                // 4. Détection dynamique de la clé SEO (CORRECTION AIOSEO)
                $seo_key = '_yoast_wpseo_title'; // Défaut (Yoast SEO)

                if ( defined( 'RANK_MATH_VERSION' ) ) {
                    $seo_key = 'rank_math_title';
                } elseif ( defined( 'AIOSEO_VERSION' ) ) {
                    // Support officiel pour All in One SEO
                    $seo_key = '_aioseo_title';
                }
                
                // 5. Sauvegarde
                LinguaCommerce_Translation_Model::save_translation( $object_id, $object_type, $seo_key, $target_lang, $seo_title, 'validated' );
                
                $msg = "Titre SEO généré : " . $seo_title;
                $result = true;
            } else {
                $msg = "Erreur lors de la génération IA du titre.";
            }
        }

        if ( $result ) {
            wp_send_json_success( array( 'message' => $msg ) );
        } else {
            wp_send_json_error( $msg );
        }
    }

     /**
     * Helper interne pour appeler l'API (Sécurisé)
     * Gère Yandex, DeepL, Microsoft Bing et les LLMs (OpenAI, DeepSeek, OpenRouter).
     */
    private function call_ai_api_seo( $text, $lang, $api_key, $engine, $model, $is_generation = false ) {
        if( empty($api_key) ) return false;

        // -----------------------------------------------------------
        // 1. LOGIQUE YANDEX CLOUD
        // -----------------------------------------------------------
        if ( $engine === 'yandex' ) {
            $url = 'https://translate.api.cloud.yandex.net/translate/v2/translate';
            $target_code = strtolower( substr( $lang, 0, 2 ) );

            $body = array(
                'texts' => array( $text ),
                'targetLanguageCode' => $target_code
            );

            $response = wp_remote_post( $url, array(
                'headers' => array(
                    'Authorization' => 'Api-Key ' . $api_key,
                    'Content-Type'  => 'application/json'
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 45
            ));

            if( is_wp_error($response) ) return false;
            
            $data = json_decode( wp_remote_retrieve_body($response), true );
            
            if( isset($data['translations'][0]['text']) ) {
                return $data['translations'][0]['text'];
            }
            
            return false;
        }

        // -----------------------------------------------------------
        // 2. LOGIQUE DEEPL
        // -----------------------------------------------------------
        elseif ( $engine === 'deepl' ) {
            // On essaie l'URL Pro d'abord, puis Free si erreur, ou on se base sur une option.
            // Ici on utilise l'URL Free par défaut pour la sécurité, mais on peut adapter.
            $url = 'https://api-free.deepl.com/v2/translate';
            
            $lang_map = array(
                'en_US' => 'EN-US', 'en_GB' => 'EN-GB', 
                'pt_BR' => 'PT-BR', 'pt_PT' => 'PT-PT'
            );
            $target_code = isset($lang_map[$lang]) ? $lang_map[$lang] : strtoupper( substr( $lang, 0, 2 ) );

            $body = array(
                'text' => $text,
                'target_lang' => $target_code,
                'tag_handling' => 'html'
            );

            $response = wp_remote_post( $url, array(
                'headers' => array(
                    'Authorization' => 'DeepL-Auth-Key ' . $api_key,
                    'Content-Type'  => 'application/x-www-form-urlencoded'
                ),
                'body'    => http_build_query( $body ),
                'timeout' => 45
            ));

            if( is_wp_error($response) ) return false;
            
            $data = json_decode( wp_remote_retrieve_body($response), true );
            if( isset($data['translations'][0]['text']) ) {
                return $data['translations'][0]['text'];
            }
            return false;
        }

        // -----------------------------------------------------------
        // 3. LOGIQUE MICROSOFT BING
        // -----------------------------------------------------------
        elseif ( $engine === 'microsoft' ) {
            $region = !empty($model) ? $model : 'global'; // La région est stockée dans $model
            $target_code = strtolower( substr( $lang, 0, 2 ) );
            $url = 'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&to=' . $target_code;

            $body = json_encode(array(
                array('Text' => $text)
            ));

            $response = wp_remote_post( $url, array(
                'headers' => array(
                    'Ocp-Apim-Subscription-Key' => $api_key,
                    'Ocp-Apim-Subscription-Region' => $region,
                    'Content-Type' => 'application/json'
                ),
                'body'    => $body,
                'timeout' => 45
            ));

            if( is_wp_error($response) ) return false;
            
            $data = json_decode( wp_remote_retrieve_body($response), true );
            if( isset($data[0]['translations'][0]['text']) ) {
                return $data[0]['translations'][0]['text'];
            }
            return false;
        }

              // -----------------------------------------------------------
        // 4. LOGIQUE BAIDU TRANSLATE
        // -----------------------------------------------------------
        elseif ( $engine === 'baidu' ) {
            // Récupération des identifiants
            $app_id = $api_key;     // L'App ID est stocké dans api_key
            $secret_key = $model;   // Le Secret est stocké dans model (via model_free/paid)
            
            if ( empty($secret_key) ) return false;

            // URL de l'API Baidu
            $url = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
            
            // Mapping des codes langue (Baidu utilise des codes spécifiques)
            $lang_map = array(
                'en' => 'en',    'en_US' => 'en', 'en_GB' => 'en',
                'fr' => 'fra',   'fr_FR' => 'fra', 'fr_CA' => 'fra', // Baidu utilise 'fra' pour français
                'de' => 'de',    'de_DE' => 'de',
                'es' => 'spa',   'es_ES' => 'spa',
                'pt' => 'pt',    'pt_BR' => 'pt', 'pt_PT' => 'pt',
                'it' => 'it',    'it_IT' => 'it',
                'ja' => 'jp',    'ja_JP' => 'jp', // Japonais
                'ko' => 'kor',   'ko_KR' => 'kor', // Coréen
                'ru' => 'ru',    'ru_RU' => 'ru',
                'zh' => 'zh',    'zh_CN' => 'zh', 'zh_TW' => 'cht' // Chinois traditionnel
            );

            // Détection du code langue cible
            $target_code = 'en';
            if ( isset( $lang_map[ $lang ] ) ) {
                $target_code = $lang_map[ $lang ];
            } else {
                // Fallback simple sur les 2 premiers caractères
                $code_short = strtolower( substr( $lang, 0, 2 ) );
                if ( isset( $lang_map[ $code_short ] ) ) $target_code = $lang_map[ $code_short ];
            }

            // Génération du Salt et de la Signature (MD5)
            $salt = rand( 10000, 99999 );
            // Signature = MD5(appid + query + salt + secret_key)
            $sign = md5( $app_id . $text . $salt . $secret_key );

            // Paramètres de la requête
            $params = array(
                'q'     => $text,
                'from'  => 'auto', // Détection automatique de la langue source
                'to'    => $target_code,
                'appid' => $app_id,
                'salt'  => $salt,
                'sign'  => $sign
            );

            // Appel API
            $response = wp_remote_get( $url . '?' . http_build_query( $params ), array(
                'timeout' => 45
            ));

            if( is_wp_error( $response ) ) return false;

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            // Analyse de la réponse
            if ( isset( $data['trans_result'][0]['dst'] ) ) {
                return $data['trans_result'][0]['dst'];
            }
            
            // Log l'erreur si présente
            if ( isset( $data['error_code'] ) ) {
                error_log( "Baidu API Error: " . $data['error_code'] . " - " . $data['error_msg'] );
            }
            
            return false;
        }

        // -----------------------------------------------------------
        // 5. LOGIQUE LLM (OpenAI, DeepSeek, OpenRouter...)
        // -----------------------------------------------------------
        else {
            $url = 'https://openrouter.ai/api/v1/chat/completions';
            if ( $engine === 'deepseek' ) $url = 'https://api.deepseek.com/chat/completions';
            if ( $engine === 'openai' ) $url = 'https://api.openai.com/v1/chat/completions';
            
            // Modèle par défaut sécurisé
            if(empty($model)) {
                if($engine === 'deepseek') $model = 'deepseek-chat';
                else $model = 'meta-llama/llama-3.2-3b-instruct:free';
            }

            $system_msg = $is_generation 
                ? "Tu es un expert SEO e-commerce. Réponds uniquement par le résultat demandé, sans texte autour." 
                : "Tu es un traducteur expert. Traduis le texte en conservant le formatage HTML si présent.";

            $body = array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'system', 'content' => $system_msg),
                    array('role' => 'user', 'content' => $text)
                )
            );

            $response = wp_remote_post( $url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => site_url()
                ),
                'body' => wp_json_encode($body),
                'timeout' => 45
            ));

            if( is_wp_error($response) ) return false;
            
            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) return false;

            $data = json_decode( wp_remote_retrieve_body($response), true );
            
            if( isset($data['choices'][0]['message']['content']) ) {
                return trim($data['choices'][0]['message']['content']);
            }
            
            return false;
        }
    }
        /**
     * AJAX : Générer le Sitemap XML multilingue
     */
    public function ajax_generate_sitemap() {
        // Vérification de sécurité
        if ( ! check_ajax_referer( 'lingua_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Erreur de sécurité.' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission refusée.' );
        }

        // Ici, logique simple pour l'instant (simulation ou création de fichier basique)
        // Pour une vraie solution, on utiliserait une classe dédiée au Sitemap.
        $filename = ABSPATH . 'sitemap-lingua.xml';
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . PHP_EOL;
        
        // Récupérer les pages publiques
        $args = array( 'post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => 100 );
        $posts = get_posts( $args );
        $languages = LinguaCommerce_Language_Service::get_active_languages();
        $default_lang = LinguaCommerce_Language_Service::get_default_language();

        foreach ( $posts as $post ) {
            $url = get_permalink( $post );
            $content .= "\t<url>" . PHP_EOL;
            $content .= "\t\t<loc>" . esc_url( $url ) . "</loc>" . PHP_EOL;
            
            // Ajouter les hreflang dans le sitemap
            foreach ( $languages as $lang ) {
                 $lang_url = ( $lang->code === $default_lang->code ) ? $url : add_query_arg( 'lang', $lang->code, $url );
                 $content .= "\t\t<xhtml:link rel=\"alternate\" hreflang=\"" . esc_attr( $lang->code ) . "\" href=\"" . esc_url( $lang_url ) . "\"/>" . PHP_EOL;
            }
            
            $content .= "\t\t<lastmod>" . get_the_modified_date( 'c', $post ) . "</lastmod>" . PHP_EOL;
            $content .= "\t</url>" . PHP_EOL;
        }

        $content .= '</urlset>';

        if ( file_put_contents( $filename, $content ) ) {
            wp_send_json_success( 'Sitemap généré avec succès (' . count($posts) . ' URLs).' );
        } else {
            wp_send_json_error( 'Impossible d\'écrire le fichier sitemap à la racine du site. Vérifiez les permissions FTP.' );
        }
    }
    
    
    
       /**
     * Traite les actions de la page Outils (Export, Import, Reset)
     */
    public function handle_tools_actions() {
        if ( ! isset( $_POST['lingua_action'] ) || ! isset( $_POST['lingua_tools_nonce'] ) ) {
            return;
        }

        $action = sanitize_text_field( $_POST['lingua_action'] );

        if ( ! wp_verify_nonce( $_POST['lingua_tools_nonce'], 'lingua_tools_' . $action ) ) {
            wp_die( 'Action non autorisée.' );
        }

        // --- ACTION : EXPORTATION COMPLÈTE ---
        if ( $action === 'export_full_backup' ) {
            global $wpdb;
            
            // 1. Récupération des réglages
            $settings = get_option( 'lingua_commerce_ai_settings', array() );
            
            // 2. Récupération des langues configurées
            $table_langs = $wpdb->prefix . 'lingua_languages';
            $languages = $wpdb->get_results( "SELECT * FROM $table_langs" );
            
            // 3. Récupération des traductions
            $table_trans = $wpdb->prefix . 'lingua_translations';
            $translations = $wpdb->get_results( "SELECT * FROM $table_trans" );
            
            // 4. Récupération des moteurs IA (On masque les clés API par sécurité)
            $table_engines = $wpdb->prefix . 'lingua_ai_engines';
            $engines = $wpdb->get_results( "SELECT engine_name, status, priority, settings FROM $table_engines" );

            // Structure du fichier JSON
            $backup_data = array(
                'version'     => LINGUA_COMMERCE_AI_VERSION,
                'date'        => date('Y-m-d H:i:s'),
                'type'        => 'linguacommerce_full_backup',
                'settings'    => $settings,
                'languages'   => $languages,
                'engines'     => $engines,
                'translations'=> $translations
            );

            // Envoi du fichier
            $filename = 'lingua-backup-' . date('Y-m-d_H-i') . '.json';
            header( 'Content-Type: application/json' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            echo json_encode( $backup_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
            exit;
        }

        // --- ACTION : IMPORTATION COMPLÈTE ---
        if ( $action === 'import_full_backup' ) {
            if ( isset( $_FILES['import_file'] ) && $_FILES['import_file']['error'] == 0 ) {
                $file = $_FILES['import_file']['tmp_name'];
                $content = file_get_contents( $file );
                $data = json_decode( $content, true );

                // Vérification du type de fichier
                if ( ! isset( $data['type'] ) || $data['type'] !== 'linguacommerce_full_backup' ) {
                    wp_redirect( admin_url( 'admin.php?page=lingua-commerce-ai-tools&import=invalid_format' ) );
                    exit;
                }

                global $wpdb;

                // 1. Restauration des réglages
                if ( isset( $data['settings'] ) ) {
                    update_option( 'lingua_commerce_ai_settings', $data['settings'] );
                }

                // 2. Restauration des langues
                if ( isset( $data['languages'] ) && is_array( $data['languages'] ) ) {
                    $table_langs = $wpdb->prefix . 'lingua_languages';
                    $wpdb->query( "TRUNCATE TABLE $table_langs" ); // On vide avant
                    foreach ( $data['languages'] as $lang ) {
                        $wpdb->insert( $table_langs, $lang );
                    }
                }

                // 3. Restauration des traductions
                if ( isset( $data['translations'] ) && is_array( $data['translations'] ) ) {
                    $table_trans = $wpdb->prefix . 'lingua_translations';
                    // On utilise REPLACE pour éviter les doublons
                    foreach ( $data['translations'] as $trans ) {
                        $wpdb->replace( $table_trans, $trans );
                    }
                }

                wp_redirect( admin_url( 'admin.php?page=lingua-commerce-ai-tools&import=success' ) );
                exit;
            }
            wp_redirect( admin_url( 'admin.php?page=lingua-commerce-ai-tools&import=error' ) );
            exit;
        }

        // --- ACTION : RÉINITIALISATION ---
        if ( $action === 'reset_settings' ) {
            delete_option( 'lingua_commerce_ai_settings' );
            wp_redirect( admin_url( 'admin.php?page=lingua-commerce-ai-tools&reset=success' ) );
            exit;
        }
    }

      /**
     * AJAX : Vérification détaillée des tables
     */
    public function ajax_check_tables() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        global $wpdb;
        
        $table_translations = $wpdb->prefix . 'lingua_translations';
        $table_queue = $wpdb->prefix . 'lingua_translation_queue';
        
        $output = array();
        
        // 1. Vérifier table Traductions
        $exists_trans = $wpdb->get_var( "SHOW TABLES LIKE '$table_translations'" );
        if ( $exists_trans === $table_translations ) {
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_translations" );
            $output[] = "Table Traductions : <span style='color:green;'>✅ OK</span> ($count entrées)";
        } else {
            $output[] = "Table Traductions : <span style='color:red;'>❌ Manquante</span>";
        }

        // 2. Vérifier table Queue (si utilisée)
        $exists_queue = $wpdb->get_var( "SHOW TABLES LIKE '$table_queue'" );
        if ( $exists_queue === $table_queue ) {
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_queue WHERE status = 'pending'" );
            $output[] = "Table Queue IA : <span style='color:green;'>✅ OK</span> ($count tâches en attente)";
        }

        wp_send_json_success( implode('<br>', $output) );
    }

    /**
     * AJAX : Nettoyage réel des orphelins
     */
    public function ajax_clean_orphans() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        global $wpdb;

        $table_name = $wpdb->prefix . 'lingua_translations';
        $deleted_count = 0;

        // Sécurité : Vérifier si la table existe
        if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
            wp_send_json_error("Table de traduction introuvable.");
        }

        // Récupérer tous les IDs distincts présents dans la table de trad
        $ids = $wpdb->get_col( "SELECT DISTINCT object_id FROM $table_name" );
        
        if ( !empty($ids) ) {
            // Vérifier quels IDs n'existent plus dans la table posts
            // On utilise une requête NOT IN optimisée
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            
            $existing_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE ID IN ($placeholders)", 
                ...$ids
            ));
            
            // Calculer les orphelins
            $orphans = array_diff($ids, $existing_ids);
            
            if ( !empty($orphans) ) {
                // Supprimer les traductions de ces orphelins
                $orphans_placeholders = implode(',', array_fill(0, count($orphans), '%d'));
                $deleted_count = $wpdb->query( $wpdb->prepare(
                    "DELETE FROM $table_name WHERE object_id IN ($orphans_placeholders)",
                    ...array_values($orphans)
                ) );
            }
        }

        // Nettoyer aussi la table des transients
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lingua_%'" );

        wp_send_json_success( "Nettoyage terminé. $deleted_count entrées orphelines supprimées." );
    }

    /**
     * AJAX : Purge complète du cache
     */
    public function ajax_purge_cache() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        global $wpdb;

        // 1. Transients WordPress
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lingua_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_lingua_%'" );
        
        // 2. Cache objet si possible
        wp_cache_flush();
        
        // 3. Suppression fichier sitemap dynamique si existant
        $sitemap = ABSPATH . 'sitemap-lingua.xml';
        if ( file_exists( $sitemap ) ) {
            unlink( $sitemap );
        }

        wp_send_json_success( "Cache système, transients et sitemap purgés avec succès." );
    }
    
        /**
     * AJAX : Récupérer le Diagnostic Système
     */
    public function ajax_get_system_status() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        
        global $wpdb;
        
        $data = array(
            'WordPress' => array(
                'Version' => get_bloginfo('version'),
                'Langue' => get_locale(),
                'Multisite' => is_multisite() ? 'Oui' : 'Non',
            ),
            'Serveur' => array(
                'Version PHP' => phpversion(),
                'Limite Mémoire (WP)' => WP_MEMORY_LIMIT,
                'Limite Mémoire (PHP)' => ini_get('memory_limit'),
                'Max Execution Time' => ini_get('max_execution_time') . 's',
                'Upload Max Size' => ini_get('upload_max_filesize'),
            ),
            'Base de données' => array(
                'Version MySQL' => $wpdb->db_version(),
                'Encodage' => $wpdb->charset,
            ),
            'LinguaCommerce' => array(
                'Version Plugin' => LINGUA_COMMERCE_AI_VERSION,
                'Tables Créées' => ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}lingua_translations'") ? 'Oui' : 'Non'),
            ),
        );

        wp_send_json_success( $data );
    }

    /**
     * AJAX : Vider la file d'attente IA
     */
    public function ajax_clear_queue() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        global $wpdb;
        
        $table = $wpdb->prefix . 'lingua_translation_queue';
        // On supprime les tâches 'pending' ou 'failed'
        $count = $wpdb->query( "DELETE FROM $table WHERE status IN ('pending', 'failed')" );
        
        wp_send_json_success( "File d'attente nettoyée. $count tâches supprimées." );
    }
    
    
        /**
     * AJAX : Tableau de bord de la File d'Attente IA
     */
    public function ajax_get_queue_stats() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        global $wpdb;

        $table_name = $wpdb->prefix . 'lingua_translation_queue';
        
        // Vérifier si la table existe
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
            wp_send_json_success( array(
                'pending' => 0,
                'failed' => 0,
                'processing' => 0,
                'completed' => 0,
                'total' => 0,
                'error' => 'Table non détectée.'
            ) );
        }

        // Comptage précis par statut
        // On regroupe par statut pour optimiser la requête
        $stats = $wpdb->get_results( "SELECT status, COUNT(*) as count FROM $table_name GROUP BY status", OBJECT_K );
        
        // On définit les valeurs par défaut à 0
        $data = array(
            'pending'    => 0,
            'processing' => 0,
            'failed'     => 0,
            'completed'  => 0
        );

        // On remplit avec les résultats réels
        if(isset($stats['pending'])) $data['pending'] = intval($stats['pending']->count);
        if(isset($stats['processing'])) $data['processing'] = intval($stats['processing']->count);
        if(isset($stats['failed'])) $data['failed'] = intval($stats['failed']->count);
        
        // Le statut "Terminé" peut être 'completed' ou 'validated' selon votre système
        if(isset($stats['completed'])) $data['completed'] = intval($stats['completed']->count);
        if(isset($stats['validated'])) $data['completed'] += intval($stats['validated']->count); // On cumule si les deux existent

        $data['total'] = array_sum($data);

        wp_send_json_success( $data );
    }
    /**
     * AJAX : Relancer les tâches échouées
     */
    public function ajax_retry_failed_tasks() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lingua_translation_queue';
        
        // Remettre le statut 'failed' à 'pending'
        $updated = $wpdb->update( 
            $table_name, 
            array( 'status' => 'pending', 'error_message' => '' ), 
            array( 'status' => 'failed' ), 
            array( '%s', '%s' ), 
            array( '%s' ) 
        );

        if ( $updated === false ) {
            wp_send_json_error( "Erreur base de données." );
        } else {
            wp_send_json_success( "$updated tâches échouées ont été remises en file d'attente." );
        }
    }

    /**
     * AJAX : Nettoyer toute la file (Terminer)
     */
    public function ajax_clear_all_queue() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'lingua_translation_queue';
        // On supprime tout sauf ce qui est "processing" (en cours) pour éviter les conflits
        $deleted = $wpdb->query( "DELETE FROM $table_name WHERE status != 'processing'" );
        
        wp_send_json_success( "File d'attente nettoyée. $deleted tâches supprimées." );
    }

    /**
     * AJAX : Déclencher manuellement le Cron de traduction
     */
    public function ajax_trigger_cron() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        
        // Tenter de lancer le moteur de traduction si la classe existe
        if ( class_exists( 'LinguaCommerce_Translation_Model' ) ) {
            // On appelle la méthode de traitement (simulé ici, car normalement c'est un background process)
            // Dans un vrai contexte, on ferait un spawn de curl ou un appel direct à la fonction de process
            do_action( 'lingua_process_queue' ); 
            wp_send_json_success( "Signal envoyé au moteur de traduction." );
        } else {
            wp_send_json_error( "Moteur de traduction non chargé." );
        }
    }
}