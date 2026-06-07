<?php
/**
 * Gère le positionnement automatique du sélecteur de langue
 * Injection dans les menus, footer, ou mode flottant.
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_Switcher_Manager {

    /**
     * Position configurée (menu, footer, floating, none)
     */
    private $position;

    public function __construct() {
        // Récupérer le réglage depuis la base de données (Options du plugin)
        $settings = get_option( 'lingua_commerce_ai_settings' );
        $this->position = isset( $settings['language_switcher_position'] ) ? $settings['language_switcher_position'] : 'none';

        // Si la position n'est pas "none" (donc manuel), on ajoute les hooks
        if ( $this->position !== 'none' ) {
            $this->init_hooks();
        }
    }

    /**
     * Enregistre les hooks WordPress selon la position choisie
     */
    private function init_hooks() {
        
        // 1. Injection dans les MENUS
        if ( $this->position === 'menu' ) {
            add_filter( 'wp_nav_menu_items', array( $this, 'inject_in_menu' ), 10, 2 );
        }

        // 2. Injection dans le FOOTER
        if ( $this->position === 'footer' ) {
            add_action( 'wp_footer', array( $this, 'inject_in_footer' ), 10 );
        }

        // 3. Mode FLOTTANT (Sticky)
        if ( $this->position === 'floating' ) {
            add_action( 'wp_footer', array( $this, 'inject_floating' ), 20 );
        }
    }

    /**
     * Génère le HTML du sélecteur
     * (Réutilise la logique de connexion BDD)
     */
    private function get_switcher_html( $layout = 'inline' ) {
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once plugin_dir_path( __FILE__ ) . 'class-lingua-language-service.php';
        }

        $active_languages = LinguaCommerce_Language_Service::get_active_languages();
        $current_lang = LinguaCommerce_Language_Service::get_current_language();

        if ( empty( $active_languages ) ) return '';

        ob_start();

        if ( $layout === 'floating' ) {
            // Layout simplifié pour le mode flottant (icone seule au repos)
            echo '<div class="lingua-frontend-switcher lingua-floating-mode">';
        } else {
            // Layout standard
            echo '<div class="lingua-frontend-switcher">';
        }

        // Bouton Principal
        $current_country = strtolower( substr( $current_lang->code, -2 ) );
        echo '<div class="lingua-current-wrapper">';
        echo '<img src="https://flagcdn.com/w20/' . $current_country . '.png" class="lingua-flag-img">';
        echo '<span class="lingua-text">' . esc_html( $current_lang->native_name ) . '</span>';
        echo '<span class="lingua-arrow"></span>';
        echo '</div>';

        // Liste Déroulante
        echo '<ul class="lingua-dropdown-list" style="display: none;">';
        foreach ( $active_languages as $lang ) {
            if ( $lang->code === $current_lang->code ) continue;
            $url = add_query_arg( 'lang', $lang->code, home_url( '/' ) );
            $country_code = strtolower( substr( $lang->code, -2 ) );
            
            echo '<li>';
            echo '<a href="' . esc_url( $url ) . '">';
            echo '<img src="https://flagcdn.com/w20/' . $country_code . '.png" class="lingua-flag-img">';
            echo '<span>' . esc_html( $lang->native_name ) . '</span>';
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';

        // On inclut le CSS spécifique au layout si ce n'est pas déjà fait
        if ( ! wp_style_is( 'lingua-commerce-ai', 'enqueued' ) ) {
            $this->print_css( $layout );
        }

        return ob_get_clean();
    }

    /**
     * Affiche le CSS (pour éviter de dépendre de l'enqueue si injecté via filtre)
     */
    private function print_css( $layout ) {
        echo '<style>
            .lingua-frontend-switcher { position: relative; display: inline-block; width: 180px; font-family: sans-serif; font-size: 14px; color: #333; background: #fff; border: 1px solid #ccc; border-radius: 4px; z-index: 9999; }
            .lingua-current-wrapper { display: flex; align-items: center; padding: 8px 10px; cursor: pointer; user-select: none; }
            .lingua-flag-img { width: 20px; height: 15px; object-fit: contain; display: inline-block; margin-right: 8px; box-shadow: 0 0 1px rgba(0,0,0,0.3); }
            .lingua-text { flex-grow: 1; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .lingua-arrow { width: 0; height: 0; border-left:5px solid transparent; border-right:5px solid transparent; border-top:5px solid #666; margin-left: 8px; transition: transform 0.2s; }
            .lingua-dropdown-list { position: absolute; top: 100%; left: 0; width: 100%; background: #fff; border: 1px solid #ccc; border-top: none; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px; list-style: none; margin: 0; padding: 0; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
            .lingua-dropdown-list li { border-bottom:1px solid #eee; }
            .lingua-dropdown-list li:last-child { border-bottom: none; }
            .lingua-dropdown-list a { display: flex; align-items: center; padding: 8px 10px; text-decoration: none; color: #333; transition: background 0.1s; }
            .lingua-dropdown-list a:hover { background-color: #f0f0f0; color: #000; }
            
            /* Mode Flottant spécifique */
            .lingua-floating-mode { position: fixed; bottom: 20px; right: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); border: none; }
            .lingua-floating-mode .lingua-current-wrapper { background: #fff; border-radius: 30px; padding: 5px 15px; }
            .lingua-floating-mode .lingua-dropdown-list { bottom: 100%; top: auto; right: 0; left: auto; border-bottom: 1px solid #ccc; border-top: none; }
        </style>';
    }

    /* --- ACTIONS D'INJECTION --- */

    /**
     * Injecter à la fin du menu
     */
    public function inject_in_menu( $items, $args ) {
        // Optionnel : On peut vérifier un nom de menu spécifique ici (ex: 'primary')
        // Pour l'instant, on l'ajoute à tous les menus.
        $items .= '<li class="menu-item lingua-menu-item">' . $this->get_switcher_html() . '</li>';
        return $items;
    }

    /**
     * Injecter dans le footer
     */
    public function inject_in_footer() {
        echo '<div class="lingua-footer-container" style="text-align: center; padding: 20px 0;">';
        echo $this->get_switcher_html();
        echo '</div>';
    }

    /**
     * Injecter en mode flottant
     */
    public function inject_floating() {
        echo $this->get_switcher_html( 'floating' );
    }
}