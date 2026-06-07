<?php
/**
 * Gère la partie publique du plugin
 * Version : 3.1 - Correction Critique : Suppression des IDs dupliqués (Widgets)
 */

if ( ! defined( 'WPINC' ) ) { die; }

// Inclusions
if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
}
if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-translation-model.php';
}

class LinguaCommerce_AI_Public {

    private $plugin_name;
    private $version;
    private $current_translations = array();

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // 1. Assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_footer', array( $this, 'enqueue_scripts' ), 99 );
        
        // 2. Init
        add_action( 'init', array( $this, 'init' ), 1 );
        
        // 3. URL Persistence
        add_filter( 'post_link', array( $this, 'persist_language_in_url' ), 10, 2 );
        add_filter( 'page_link', array( $this, 'persist_language_in_url' ), 10, 2 );
        add_filter( 'post_type_link', array( $this, 'persist_language_in_url' ), 10, 2 );
        add_filter( 'term_link', array( $this, 'persist_language_in_url' ), 10, 2 );
        
        // 4. Affichage
        add_shortcode( 'lingua_selector', array( $this, 'render_custom_shortcode' ) );
        add_action( 'template_redirect', array( $this, 'render_selector_auto' ) );
        
        // ------------------------------------------------------------------
        // 5. LOGIQUE DE TRADUCTION (UNIQUEMENT IDS UNIQUES)
        // ------------------------------------------------------------------
        
        // Titres (Posts, Produits, Pages) -> IDs Uniques (ID du post)
        add_filter( 'the_title', array( $this, 'process_translate_title' ), 10, 2 );
        
        // Contenu & Extrait -> IDs Uniques (ID du post)
        add_filter( 'the_content', array( $this, 'process_translate_content' ), 10, 1 );
        add_filter( 'the_excerpt', array( $this, 'process_translate_excerpt' ), 10, 1 );
        
        // Header (Site Title & Tagline) -> IDs Uniques ('site_name', 'site_description')
        add_filter( 'bloginfo', array( $this, 'process_translate_bloginfo' ), 10, 2 );
        
        // Termes (Catégories, Tags, Attributs) -> IDs Uniques (term_id)
        add_filter( 'term_name', array( $this, 'process_translate_term_name' ), 10, 2 );
        
        // NOTE : Les Widgets sont retirés du Frontend Editor pour éviter les doublons d'ID.
        // Ils restent traduisibles via la liste Admin.
        
        // 6. SEO
        add_action( 'wp_head', array( $this, 'render_hreflang_tags' ), 1 );
        add_filter( 'get_canonical_url', array( $this, 'fix_canonical_url' ), 10, 2 );
        add_action( 'wp_head', array( $this, 'render_open_graph_tags' ), 2 );
        
        // 7. Debug
        add_action( 'wp_footer', array( $this, 'show_missing_translation_notice' ), 99 );

        // 8. Live Editor
        add_action( 'init', array( $this, 'init_live_editor' ) );
        
        // 9. Menus & Widgets
        add_filter( 'nav_menu_item_title', array( $this, 'process_translate_nav_menu_item' ), 10, 2 );
        add_filter( 'widget_title', array( $this, 'process_translate_widget' ), 10, 1 );

        // --- EXTENSION WOOCOMMERCE (Forçage IDs) ---
        add_filter( 'woocommerce_product_title', array( $this, 'process_translate_title' ), 10, 2 );
        add_filter( 'woocommerce_short_description', array( $this, 'process_translate_content' ), 10, 1 );
        add_filter( 'woocommerce_shop_loop_item_title', array( $this, 'process_translate_title' ), 10, 2 );
        
        // Nouveaux Hooks WooCommerce
        add_filter( 'woocommerce_get_availability_text', array( $this, 'process_translate_woocommerce_text' ), 10, 2 );
        add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'process_translate_woocommerce_text' ), 10, 2 );
        add_filter( 'woocommerce_order_button_text', array( $this, 'process_translate_woocommerce_text' ), 10, 1 );
        add_filter( 'woocommerce_cart_item_name', array( $this, 'process_translate_woocommerce_text' ), 10, 2 );
        add_filter( 'woocommerce_product_tabs', array( $this, 'process_translate_woocommerce_tabs' ), 10, 1 );
        add_filter( 'woocommerce_attribute_label', array( $this, 'process_translate_woocommerce_text' ), 10, 3 );
    }

    // ------------------------------------------------------------------
    // LOGIQUE DE TRADUCTION & INJECTION
    // ------------------------------------------------------------------

    private function get_cached_translation( $object_id, $object_type, $field_key ) {
        $lang = $GLOBALS['lingua_current_lang'] ?? '';
        if(!$lang) return false;
        
        $cache_key = $object_id . '_' . $object_type . '_' . $field_key . '_' . $lang;

        if ( isset( $this->current_translations[ $cache_key ] ) ) {
            return $this->current_translations[ $cache_key ];
        }

        $trans = LinguaCommerce_Translation_Model::get_translation( $object_id, $object_type, $field_key, $lang );
        
        if ( $trans && isset( $trans->translated_text ) && ! empty( $trans->translated_text ) ) {
            $this->current_translations[ $cache_key ] = $trans->translated_text;
            return $trans->translated_text;
        }

        $this->current_translations[ $cache_key ] = false;
        return false;
    }

    public function process_translate_title( $title, $id = 0 ) {
        if ( is_admin() || ! $id ) return $title;
        
        // Normalisation de l'ID (Support WooCommerce Product Object)
        if ( is_object( $id ) && method_exists( $id, 'get_id' ) ) { $id = $id->get_id(); }
        elseif ( is_object( $id ) && isset( $id->ID ) ) { $id = $id->ID; }

        if ( ! isset( $GLOBALS['lingua_current_lang'] ) || ! isset( $GLOBALS['lingua_default_lang'] ) ) return $title;
        
        $type = get_post_type( $id );
        $translation = $this->get_cached_translation( $id, $type, 'post_title' );
        $display_text = $translation ? $translation : $title;

        // Injection pour Live Editor (uniquement dans la boucle principale pour éviter de casser les attributs et le <head>)
        if ( current_user_can('manage_options') && ( in_the_loop() || is_singular() ) ) {
            return sprintf(
                '<span class="lingua-injected" data-id="%s" data-type="%s" data-field="post_title">%s</span>',
                esc_attr( $id ),
                esc_attr( $type ),
                $display_text
            );
        }
        
        return $display_text;
    }

    public function process_translate_content( $content ) {
        if ( is_admin() || ! is_singular() ) return $content;
        if ( ! isset( $GLOBALS['lingua_current_lang'] ) || ! isset( $GLOBALS['lingua_default_lang'] ) ) return $content;

        $id = get_the_ID();
        $type = get_post_type( $id );
        $translation = $this->get_cached_translation( $id, $type, 'post_content' );
        $display_text = $translation ? $translation : $content;

        if ( current_user_can('manage_options') ) {
            return sprintf(
                '<div class="lingua-injected" data-id="%s" data-type="%s" data-field="post_content">%s</div>',
                esc_attr( $id ),
                esc_attr( $type ),
                $display_text
            );
        }
        return $display_text;
    }

    public function process_translate_excerpt( $excerpt ) {
        if ( is_admin() ) return $excerpt;
        if ( ! isset( $GLOBALS['lingua_current_lang'] ) || ! isset( $GLOBALS['lingua_default_lang'] ) ) return $excerpt;

        $id = get_the_ID();
        $type = get_post_type( $id );
        $translation = $this->get_cached_translation( $id, $type, 'post_excerpt' );
        $display_text = $translation ? $translation : $excerpt;

        if ( current_user_can('manage_options') ) {
            return sprintf(
                '<div class="lingua-injected" data-id="%s" data-type="%s" data-field="post_excerpt">%s</div>',
                esc_attr( $id ),
                esc_attr( $type ),
                $display_text
            );
        }
        return $display_text;
    }

    // Header (Nom du site, Slogan) - IDs textuels uniques
    public function process_translate_bloginfo( $output, $show ) {
        if ( is_admin() ) return $output;
        if ( ! in_array( $show, array( 'name', 'description' ) ) ) return $output;
        if ( ! isset( $GLOBALS['lingua_current_lang'] ) || ! isset( $GLOBALS['lingua_default_lang'] ) ) return $output;

        $id = 'site_' . $show; // ID Unique : 'site_name' ou 'site_description'
        $type = 'option';
        $translation = $this->get_cached_translation( $id, $type, $show );
        $display_text = $translation ? $translation : $output;

        if ( current_user_can('manage_options') ) {
            return sprintf(
                '<span class="lingua-injected" data-id="%s" data-type="%s" data-field="%s">%s</span>',
                esc_attr( $id ),
                esc_attr( $type ),
                esc_attr( $show ),
                $display_text
            );
        }
        return $display_text;
    }

    // Termes (Catégories, Tags) - IDs numériques uniques
    public function process_translate_term_name( $name, $term = null ) {
        if ( is_admin() || ! $term || is_wp_error( $term ) ) return $name;
        if ( ! isset( $GLOBALS['lingua_current_lang'] ) || ! isset( $GLOBALS['lingua_default_lang'] ) ) return $name;

        $id = $term->term_id; // ID Unique
        $type = $term->taxonomy;
        $translation = $this->get_cached_translation( $id, $type, 'name' );
        $display_text = $translation ? $translation : $name;

        if ( current_user_can('manage_options') ) {
            return sprintf(
                '<span class="lingua-injected" data-id="%s" data-type="%s" data-field="name">%s</span>',
                esc_attr( $id ),
                esc_attr( $type ),
                $display_text
            );
        }
        return $display_text;
    }

    // Menus (Navigation)
    public function process_translate_nav_menu_item( $title, $item ) {
        if ( is_admin() || ! isset( $item->ID ) ) return $title;
        if ( ! isset( $GLOBALS['lingua_current_lang'] ) || ! isset( $GLOBALS['lingua_default_lang'] ) ) return $title;

        $id = $item->ID;
        $type = 'nav_menu_item';
        $translation = $this->get_cached_translation( $id, $type, 'title' );
        $display_text = $translation ? $translation : $title;

        if ( current_user_can('manage_options') ) {
            return sprintf(
                '<span class="lingua-injected" data-id="%s" data-type="%s" data-field="title">%s</span>',
                esc_attr( $id ),
                $type,
                $display_text
            );
        }
        return $display_text;
    }

    // Widgets (Titres)
    public function process_translate_widget( $title ) {
        if ( is_admin() || empty($title) ) return $title;
        if ( ! isset( $GLOBALS['lingua_current_lang'] ) || ! isset( $GLOBALS['lingua_default_lang'] ) ) return $title;

        // Utilisation du hash du texte comme ID pour les widgets (cas particulier)
        $id = 'widget_' . md5($title); 
        $type = 'widget';
        $translation = $this->get_cached_translation( $id, $type, 'title' );
        $display_text = $translation ? $translation : $title;

        if ( current_user_can('manage_options') ) {
            return sprintf(
                '<span class="lingua-injected" data-id="%s" data-type="%s" data-field="title">%s</span>',
                esc_attr( $id ),
                $type,
                $display_text
            );
        }
        return $display_text;
    }

    /**
     * Gère la traduction des textes WooCommerce génériques (Boutons, Disponibilité, etc.)
     */
    public function process_translate_woocommerce_text( $text, $product = null, $extra = null ) {
        if ( is_admin() || empty($text) ) return $text;
        
        $current_filter = current_filter();
        $type = 'woocommerce';
        $field = 'text';
        
        // On génère un ID basé sur le filtre ou le produit
        if ( $product && is_object( $product ) && method_exists( $product, 'get_id' ) ) {
            $id = $product->get_id();
            $type = 'product';
            $field = $current_filter;
        } else {
            // Pour les textes globaux (ex: bouton commande), on utilise le hash du texte
            $id = 'woo_' . md5($text);
            $field = $current_filter;
        }

        $translation = $this->get_cached_translation( $id, $type, $field );
        $display_text = $translation ? $translation : $text;

        if ( current_user_can('manage_options') ) {
             return sprintf(
                '<span class="lingua-injected" data-id="%s" data-type="%s" data-field="%s">%s</span>',
                esc_attr( $id ),
                esc_attr( $type ),
                esc_attr( $field ),
                $display_text
            );
        }
        return $display_text;
    }

    /**
     * Gère la traduction des onglets produits de WooCommerce
     */
    public function process_translate_woocommerce_tabs( $tabs ) {
        if ( is_admin() || empty($tabs) ) return $tabs;

        foreach ( $tabs as $key => &$tab ) {
            if ( isset( $tab['title'] ) ) {
                $id = 'tab_' . $key;
                $type = 'woocommerce_tab';
                $translation = $this->get_cached_translation( $id, $type, 'title' );
                
                if ( $translation ) {
                    $tab['title'] = $translation;
                }
                
                if ( current_user_can('manage_options') ) {
                    $tab['title'] = sprintf(
                        '<span class="lingua-injected" data-id="%s" data-type="%s" data-field="title">%s</span>',
                        esc_attr( $id ),
                        esc_attr( $type ),
                        $tab['title']
                    );
                }
            }
        }
        return $tabs;
    }

    // ------------------------------------------------------------------
    // LIVE EDITOR SETUP
    // ------------------------------------------------------------------

    public function init_live_editor() {
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_link' ), 999 );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_live_editor_assets' ) );
            add_action( 'wp_ajax_lingua_get_translation_frontend', array( $this, 'ajax_get_translation_frontend' ) );
            add_action( 'wp_ajax_lingua_save_translation_frontend', array( $this, 'ajax_save_translation_frontend' ) );
            add_action( 'wp_ajax_lingua_ai_suggest_frontend', array( $this, 'ajax_ai_suggest_frontend' ) );
            
            // Nouveaux handlers batch
            add_action( 'wp_ajax_lingua_batch_translate_frontend', array( $this, 'ajax_batch_translate_frontend' ) );
            add_action( 'wp_ajax_lingua_bulk_save_translations', array( $this, 'ajax_bulk_save_translations' ) );
        }
    }

    public function add_admin_bar_link( $admin_bar ) {
        $admin_bar->add_node( array(
            'id'    => 'lingua-toggle-mode',
            'title' => '✏️ Mode Traduction',
            'href'  => '#',
            'meta'  => array( 'class' => 'lingua-admin-bar-btn' )
        ));

        $admin_bar->add_node( array(
            'id'    => 'lingua-translate-all',
            'title' => '✨ Tout Traduire (IA)',
            'href'  => '#',
            'meta'  => array( 
                'class' => 'lingua-admin-bar-btn lingua-btn-highlight',
                'title' => 'Traduit tous les éléments de la page en un clic'
            )
        ));
    }

    // ------------------------------------------------------------------
    // ASSETS
    // ------------------------------------------------------------------

    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name . '-public', plugin_dir_url( __FILE__ ) . 'css/lingua-commerce-ai-public.css', array(), $this->version, 'all' );
        
        if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
             wp_enqueue_style( 'lingua-live-editor', plugin_dir_url( __FILE__ ) . 'css/lingua-live-editor.css', array(), $this->version, 'all' );
        }
    }
    
    public function enqueue_scripts() {
        wp_localize_script( 'jquery', 'lingua_vars', array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'current_lang'  => $GLOBALS['lingua_current_lang'] ?? '',
            'default_lang'  => $GLOBALS['lingua_default_lang'] ?? '',
            'is_admin'      => current_user_can( 'manage_options' ) ? '1' : '0'
        ));
        ?>
        <script type="text/javascript">
        (function() { 'use strict'; function initLinguaSwitcher() { var dropdownBtns = document.querySelectorAll('.lingua-dropdown-btn'); dropdownBtns.forEach(function(btn) { btn.addEventListener('click', function(e) { e.preventDefault(); this.parentElement.classList.toggle('open'); }); }); document.addEventListener('click', function(e) { if (!e.target.closest('.lingua-selector')) { document.querySelectorAll('.lingua-dropdown.open').forEach(function(dd) { dd.classList.remove('open'); }); } }); document.querySelectorAll('.lingua-floating-btn').forEach(function(btn) { btn.addEventListener('click', function() { this.parentElement.classList.toggle('open'); }); }); } if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initLinguaSwitcher); } else { initLinguaSwitcher(); } })();
        </script>
        <?php
    }

    public function enqueue_live_editor_assets() {
        wp_enqueue_script( 'lingua-live-editor', plugin_dir_url( __FILE__ ) . 'js/lingua-live-editor.js', array('jquery'), $this->version, true );
        
        global $wpdb;
        $engines = $wpdb->get_results( "SELECT engine_name FROM {$wpdb->prefix}lingua_ai_engines WHERE status = 'active'" );
        $engine_list = $engines ? wp_list_pluck( $engines, 'engine_name' ) : array();
        $selectors = array( 
            '.lingua-injected', 
            'h1', 'h2', 'h3', 
            '.product_title', 
            '.woocommerce-loop-product__title',
            'button', 
            'input[type="submit"]', 
            'input[placeholder]', 
            'img[alt]', 
            '.woocommerce-product-details__short-description', 
            '.description' 
        );

        wp_localize_script( 'lingua-live-editor', 'lingua_live_vars', array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'lingua_live_editor_nonce' ),
            'current_lang'  => $GLOBALS['lingua_current_lang'] ?? '',
            'default_lang'  => $GLOBALS['lingua_default_lang'] ?? '',
            'post_id'       => get_the_ID(), 
            'selectors'     => implode(', ', $selectors),
            'is_admin'      => '1',
            'engines'       => $engine_list
        ));
    }

    // ------------------------------------------------------------------
    // INITIALISATION LANGUE
    // ------------------------------------------------------------------

    public function init() {
        $default_lang_obj = LinguaCommerce_Language_Service::get_default_language();
        $default_lang_code = ( $default_lang_obj && isset($default_lang_obj->code) ) ? $default_lang_obj->code : 'en_US';
        $GLOBALS['lingua_default_lang'] = $default_lang_code;

        $current_lang_code = '';
        
        if ( isset( $_GET['lang'] ) && ! empty( $_GET['lang'] ) ) {
            $current_lang_code = sanitize_text_field( $_GET['lang'] );
        } else {
            $settings = get_option( 'lingua_commerce_ai_settings', array() );
            $auto_detect = isset( $settings['browser_redirect'] ) && $settings['browser_redirect'] == 1;

            if ( $auto_detect && isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
                $browser_string = sanitize_text_field( $_SERVER['HTTP_ACCEPT_LANGUAGE'] );
                $parts = explode( ',', $browser_string );
                $browser_locale = str_replace( '-', '_', $parts[0] );
                $active_languages = LinguaCommerce_Language_Service::get_active_languages();
                
                if ( ! empty( $active_languages ) ) {
                    foreach ( $active_languages as $lang ) {
                        if ( isset($lang->code) && $lang->code === $browser_locale ) { $current_lang_code = $lang->code; break; }
                        if ( isset($lang->code) && substr( $lang->code, 0, 2 ) === substr( $browser_locale, 0, 2 ) ) { $current_lang_code = $lang->code; break; }
                    }
                }
            }
        }

        if ( empty( $current_lang_code ) ) { $current_lang_code = $default_lang_code; }
        $GLOBALS['lingua_current_lang'] = $current_lang_code;
    }

    // ------------------------------------------------------------------
    // PERSISTANCE URL
    // ------------------------------------------------------------------

    public function persist_language_in_url( $url, $id_or_data = null ) {
        if ( is_admin() ) { return $url; }
        
        $current_lang = $GLOBALS['lingua_current_lang'] ?? null;
        $default_lang = $GLOBALS['lingua_default_lang'] ?? null;
        
        if ( ! $current_lang || ! $default_lang ) { return $url; }
        if ( $current_lang === $default_lang ) { return $url; }

        return add_query_arg( 'lang', $current_lang, $url );
    }

    // ------------------------------------------------------------------
    // RENDU SWITCHER
    // ------------------------------------------------------------------

    public function render_selector_auto() {
        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        $position = isset( $settings['selector_position'] ) ? $settings['selector_position'] : 'manual';
        if ( $position === 'header' ) { add_action( 'wp_body_open', array( $this, 'print_global_html' ) ); }
        elseif ( $position === 'footer' ) { add_action( 'wp_footer', array( $this, 'print_global_html' ), 5 ); }
    }
    
    public function print_global_html() { echo $this->get_html( 'global' ); }
    public function render_custom_shortcode( $atts ) { return $this->get_html( 'shortcode' ); }

    private function get_html( $context = 'global' ) {
        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        $languages = LinguaCommerce_Language_Service::get_active_languages();
        $current_lang_code = $GLOBALS['lingua_current_lang'] ?? '';
        
        global $wp;
        $current_url = home_url( '/' . ltrim( $wp->request ?? '', '/' ) );

        if ( $context === 'shortcode' ) {
            $template = isset( $settings['sc_template'] ) && $settings['sc_template'] !== 'default' ? $settings['sc_template'] : ( $settings['selector_template'] ?? 'dropdown' );
            $flag_style = $settings['flag_style'] ?? 'rectangular';
            $show_name = isset( $settings['sc_show_name'] ) ? ! empty( $settings['sc_show_name'] ) : true;
            $hide_current = ! empty( $settings['sc_hide_current'] );
            $align = $settings['sc_align'] ?? ( $settings['selector_align'] ?? 'left' );
            $margin = $settings['sc_margin'] ?? ( $settings['selector_margin'] ?? '0px' );
        } else {
            $template = $settings['selector_template'] ?? 'dropdown';
            $flag_style = $settings['flag_style'] ?? 'rectangular';
            $show_name = true; $hide_current = false;
            $align = $settings['selector_align'] ?? 'left';
            $margin = $settings['selector_margin'] ?? '0px';
        }

        ob_start();
        
        if ( ! empty( $languages ) ) {
            $valid_languages = array();
            foreach ( $languages as $lang ) {
                if ( ! empty( $lang->code ) && ! empty( $lang->native_name ) && strlen( $lang->native_name ) > 2 ) { $valid_languages[] = $lang; }
            }
            $languages = $valid_languages;
            if ( empty( $languages ) ) return '';

            $style_container = "padding:" . esc_attr( $margin ) . "; position: relative;";
            if ( $template === 'flags_only' || $template === 'text_pills' || $template === 'nav_menu' ) {
                $style_container .= "display: flex;";
                if ( $align === 'center' ) $style_container .= "justify-content: center;";
                elseif ( $align === 'right' ) $style_container .= "justify-content: flex-end;";
                else $style_container .= "justify-content: flex-start;";
            } else { $style_container .= "text-align:" . esc_attr( $align ) . ";"; }
            if ( $template === 'floating_bubble' ) { $style_container = ""; }

            echo '<div class="lingua-selector lingua-template-' . esc_attr( $template ) . '" style="' . $style_container . '">';
            
            switch ( $template ) {
                case 'flags_only':
                    foreach ( $languages as $lang ) {
                        if ( $hide_current && $lang->code === $current_lang_code ) continue;
                        $url = add_query_arg( 'lang', $lang->code, $current_url );
                        echo '<a href="' . esc_url( $url ) . '" title="' . esc_attr( $lang->native_name ) . '" style="display:inline-block; margin:2px;">';
                        $this->render_flag_html( $lang->code, $flag_style );
                        echo '</a>';
                    }
                    break;

                case 'text_pills':
                    foreach ( $languages as $lang ) {
                        if ( $hide_current && $lang->code === $current_lang_code ) continue;
                        $url = add_query_arg( 'lang', $lang->code, $current_url );
                        $is_active = ( $lang->code === $current_lang_code );
                        echo '<a href="' . esc_url( $url ) . '" class="' . ( $is_active ? 'active' : '' ) . '">';
                        echo esc_html( strtoupper( substr( $lang->code, 0, 2 ) ) );
                        echo '</a>';
                    }
                    break;

                case 'dropdown':
                default:
                    $current_name = 'Select';
                    foreach ( $languages as $l ) { if ( isset( $l->code, $l->native_name ) && $l->code === $current_lang_code ) { $current_name = $l->native_name; break; } }
                    
                    echo '<div class="lingua-dropdown" style="z-index: 100;"><div class="lingua-dropdown-btn">';
                    $this->render_flag_html( $current_lang_code, $flag_style );
                    if ( $show_name ) { echo '<span>' . esc_html( $current_name ) . '</span>'; }
                    echo '</div><div class="lingua-dropdown-list">';
                    
                    foreach ( $languages as $lang ) {
                        if ( $hide_current && $lang->code === $current_lang_code ) continue;
                        $url = add_query_arg( 'lang', $lang->code, $current_url );
                        echo '<a href="' . esc_url( $url ) . '">';
                        $this->render_flag_html( $lang->code, $flag_style );
                        if ( $show_name ) { echo '<span>' . esc_html( $lang->native_name ) . '</span>'; }
                        echo '</a>';
                    }
                    echo '</div></div>';
                    break;
            }
            echo '</div>';
        }
        
        return ob_get_clean();
    }

    private function render_flag_html( $lang_code, $style = 'rectangular' ) {
        $country_map = array(
            'en' => 'gb', 'en_US' => 'us', 'en_GB' => 'gb', 'en_AU' => 'au', 'en_CA' => 'ca',
            'fr' => 'fr', 'fr_FR' => 'fr', 'fr_CA' => 'ca', 'fr_BE' => 'be',
            'es' => 'es', 'es_ES' => 'es', 'es_MX' => 'mx',
            'de' => 'de', 'de_DE' => 'de', 'de_AT' => 'at', 'de_CH' => 'ch',
            'pt' => 'pt', 'pt_PT' => 'pt', 'pt_BR' => 'br',
            'zh' => 'cn', 'zh_CN' => 'cn', 'zh_TW' => 'tw',
            'ja' => 'jp', 'ko' => 'kr', 'ru' => 'ru', 'ar' => 'sa',
        );

        $country_code = $country_map[ $lang_code ] ?? '';
        if ( empty( $country_code ) && strlen( $lang_code ) > 2 && strpos( $lang_code, '_' ) !== false ) { $country_code = strtolower( substr( $lang_code, -2 ) ); }
        if ( empty( $country_code ) ) { $country_code = strtolower( substr( $lang_code, 0, 2 ) ); }

        $flag_url = 'https://flagcdn.com/w20/' . $country_code . '.png';
        $style_css = ( $style === 'round' ) ? 'border-radius: 50%; width: 20px; height: 20px; object-fit: cover;' : 'border-radius: 2px; width: 20px; height: 14px; object-fit: cover;';
            
        echo '<img src="' . esc_url( $flag_url ) . '" alt="' . esc_attr( $lang_code ) . '" style="' . esc_attr( $style_css ) . ' display:inline-block; vertical-align:middle; margin-right:5px;" loading="lazy" />';
    }

    // ------------------------------------------------------------------
    // SEO
    // ------------------------------------------------------------------

    public function render_hreflang_tags() {
        if ( is_404() || is_search() || is_admin() ) { return; }
        
        $languages = LinguaCommerce_Language_Service::get_active_languages();
        $default_lang = LinguaCommerce_Language_Service::get_default_language();
        if ( ! $languages || ! $default_lang ) return;

        $current_url = '';
        if ( is_front_page() ) { $current_url = home_url( '/' ); }
        elseif ( is_singular() ) { $current_url = get_permalink(); }
        elseif ( is_post_type_archive() ) { $post_type = get_query_var( 'post_type' ); if ( is_array( $post_type ) ) { $post_type = reset( $post_type ); } if ( $post_type ) { $current_url = get_post_type_archive_link( $post_type ); } }
        elseif ( is_tax() || is_category() || is_tag() ) { $term = get_queried_object(); if ( $term ) { $current_url = get_term_link( $term ); } }
        elseif ( is_author() ) { $current_url = get_author_posts_url( get_queried_object_id() ); }
        elseif ( is_date() ) { $year = get_query_var( 'year' ); $month = get_query_var( 'monthnum' ); $day = get_query_var( 'day' ); if ( $day && $month && $year ) { $current_url = get_day_link( $year, $month, $day ); } elseif ( $month && $year ) { $current_url = get_month_link( $year, $month ); } elseif ( $year ) { $current_url = get_year_link( $year ); } }
        elseif ( is_archive() ) { global $wp; $current_url = home_url( $wp->request ); }

        if ( empty( $languages ) || ! $current_url || is_wp_error( $current_url ) ) { return; }

        foreach ( $languages as $lang ) {
            if ( ! isset($lang->code) ) continue;
            $url = ( $lang->code !== $default_lang->code ) ? add_query_arg( 'lang', $lang->code, $current_url ) : $current_url;
            printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $lang->code ), esc_url( $url ) );
        }
    }

    public function fix_canonical_url( $canonical ) {
        $current_lang_code = $GLOBALS['lingua_current_lang'] ?? '';
        $default_lang = LinguaCommerce_Language_Service::get_default_language();
        if ( $default_lang && $current_lang_code !== $default_lang->code && ! empty( $canonical ) ) { return add_query_arg( 'lang', $current_lang_code, $canonical ); }
        return $canonical;
    }

    public function render_open_graph_tags() {
        if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || ! is_singular() ) return;
        global $post; if ( ! $post ) return;
        echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( wp_trim_words( get_the_excerpt(), 25 ) ) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '" />' . "\n";
        echo '<meta property="og:type" content="article" />' . "\n";
        if ( has_post_thumbnail() ) echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( $post->ID, 'large' ) ) . '" />' . "\n";
    }

    // ------------------------------------------------------------------
    // AJAX HANDLERS (LIVE EDITOR)
    // ------------------------------------------------------------------

    public function ajax_get_translation_frontend() {
        check_ajax_referer( 'lingua_live_editor_nonce', 'nonce' );
        
        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : 0; // String support for site_name
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
        $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : '';

        if ( empty($id) || empty($field) ) wp_send_json_error( 'Data missing' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'lingua_translations';
        
        $text = $wpdb->get_var( $wpdb->prepare( 
            "SELECT translated_text FROM $table_name WHERE object_id = %s AND object_type = %s AND field_key = %s AND language = %s", 
            $id, $type, $field, $lang 
        ) );
        
        wp_send_json_success( array( 'text' => $text ? $text : '' ) );
    }

    public function ajax_save_translation_frontend() {
        check_ajax_referer( 'lingua_live_editor_nonce', 'nonce' );

        $id = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : 0;
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
        $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : '';
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';

        if ( empty($id) || empty($field) ) wp_send_json_error( 'Data missing' );

        $res = LinguaCommerce_Translation_Model::save_translation( $id, $type, $field, $lang, $content, 'validated' );

        if ( $res ) { wp_send_json_success( 'Saved' ); } 
        else { wp_send_json_error( 'DB Error' ); }
    }

    public function ajax_ai_suggest_frontend() {
        check_ajax_referer( 'lingua_live_editor_nonce', 'nonce' );
        
        $text = isset($_POST['text']) ? wp_kses_post($_POST['text']) : '';
        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : '';
        $engine_name = isset($_POST['engine']) ? sanitize_text_field($_POST['engine']) : 'openrouter';

        if ( empty($text) ) wp_send_json_error( 'Texte vide.' );

        global $wpdb;
        $engine_config = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lingua_ai_engines WHERE engine_name = %s AND status = 'active'", $engine_name ) );

        if ( ! $engine_config ) wp_send_json_error( 'Moteur introuvable.' );
        
        $translation = '';

        if ( $engine_config->engine_name === 'deepl' ) {
            $url = 'https://api-free.deepl.com/v2/translate';
            $response = wp_remote_post( $url, array(
                'headers'=>array('Authorization'=>'DeepL-Auth-Key '.$engine_config->api_key, 'Content-Type'=>'application/x-www-form-urlencoded'),
                'body'=>http_build_query(array('text'=>$text, 'target_lang'=>strtoupper(substr($target_lang,0,2)))),
                'timeout'=>30
            ));
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if(isset($data['translations'][0]['text'])) $translation = $data['translations'][0]['text'];
        } else {
             $url = ( $engine_config->engine_name === 'deepseek' ) ? 'https://api.deepseek.com/chat/completions' : 'https://openrouter.ai/api/v1/chat/completions';
             $settings = maybe_unserialize($engine_config->settings);
             $model = $settings['model_free'] ?? 'auto';
             $response = wp_remote_post( $url, array(
                'headers'=>array('Authorization'=>'Bearer '.$engine_config->api_key, 'Content-Type'=>'application/json', 'HTTP-Referer'=>site_url()), 
                'body'=>json_encode(array('model'=>$model, 'messages'=>array(array('role'=>'system', 'content'=>'Translate to '.$target_lang), array('role'=>'user', 'content'=>$text)))), 
                'timeout'=>60));
             $data = json_decode(wp_remote_retrieve_body($response), true);
             if(isset($data['choices'][0]['message']['content'])) $translation = trim($data['choices'][0]['message']['content']);
        }
        if(!empty($translation)) wp_send_json_success( array( 'translation' => $translation ) );
        else wp_send_json_error( 'Erreur API' );
    }

    /**
     * Traduction en lot pour toute la page
     */
    public function ajax_batch_translate_frontend() {
        check_ajax_referer( 'lingua_live_editor_nonce', 'nonce' );
        
        $texts = isset($_POST['texts']) ? (array)$_POST['texts'] : array();
        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : '';
        $engine_name = isset($_POST['engine']) ? sanitize_text_field($_POST['engine']) : 'openrouter';

        if ( empty($texts) ) wp_send_json_error( 'Aucun texte à traduire.' );

        global $wpdb;
        $engine_config = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lingua_ai_engines WHERE engine_name = %s AND status = 'active'", $engine_name ) );
        if ( ! $engine_config ) wp_send_json_error( 'Moteur IA non trouvé.' );

        // On prépare une liste numérotée pour l'IA pour plus de fiabilité
        $prompt_list = "";
        foreach($texts as $i => $t) {
            $prompt_list .= ($i + 1) . ". " . wp_strip_all_tags($t) . "\n";
        }

        $system_prompt = "Translate the following numbered list exactly as is into " . $target_lang . ". Return ONLY the translated list items, one per line, starting with their numbers.";
        
        $translation = '';
        $url = ( $engine_config->engine_name === 'deepseek' ) ? 'https://api.deepseek.com/chat/completions' : 'https://openrouter.ai/api/v1/chat/completions';
        $settings = maybe_unserialize($engine_config->settings);
        $model = $settings['model_free'] ?? 'auto';

        $response = wp_remote_post( $url, array(
            'headers'=>array('Authorization'=>'Bearer '.$engine_config->api_key, 'Content-Type'=>'application/json', 'HTTP-Referer'=>site_url()), 
            'body'=>json_encode(array(
                'model'=>$model, 
                'messages'=>array(
                    array('role'=>'system', 'content' => $system_prompt), 
                    array('role'=>'user', 'content' => $prompt_list)
                ),
                'temperature' => 0.1
            )), 
            'timeout'=>120));

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if(isset($data['choices'][0]['message']['content'])) {
            $raw_lines = explode("\n", trim($data['choices'][0]['message']['content']));
            $results = array();
            foreach($raw_lines as $line) {
                // On retire le numéro du début (ex: "1. Bonjour" -> "Bonjour")
                $clean = preg_replace('/^\d+\.\s*/', '', $line);
                if(!empty($clean)) $results[] = $clean;
            }
            wp_send_json_success( array( 'translations' => $results ) );
        } else {
            wp_send_json_error( 'Erreur API' );
        }
    }

    /**
     * Sauvegarde massive de plusieurs traductions
     */
    public function ajax_bulk_save_translations() {
        check_ajax_referer( 'lingua_live_editor_nonce', 'nonce' );
        
        $batch = isset($_POST['batch']) ? (array)$_POST['batch'] : array();
        $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : '';

        if ( empty($batch) || empty($lang) ) wp_send_json_error( 'Données vides.' );

        $count = 0;
        foreach ( $batch as $item ) {
            $res = LinguaCommerce_Translation_Model::save_translation( 
                $item['id'], 
                $item['type'], 
                $item['field'], 
                $lang, 
                $item['content'], 
                'validated' 
            );
            if($res) $count++;
        }

        wp_send_json_success( "Enregistré : $count éléments." );
    }

    // ------------------------------------------------------------------
    // DEBUG NOTICE
    // ------------------------------------------------------------------

    public function show_missing_translation_notice() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $current_lang = $GLOBALS['lingua_current_lang'] ?? 'N/A';
        ?>
        <div style="position: fixed; bottom: 20px; left: 20px; background: #2271b1; color: #fff; padding: 8px 15px; border-radius: 20px; font-size: 12px; font-family: sans-serif; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
            🌐 Langue active : <strong><?php echo esc_html( $current_lang ); ?></strong>
        </div>
        <?php
    }
}