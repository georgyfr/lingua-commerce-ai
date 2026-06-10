<?php
/**
 * Initialise le plugin de manière robuste et modulaire
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_AI_Init {

    /**
     * Le chargeur de hooks du plugin
     */
    protected $loader;

    /**
     * Le nom du plugin
     */
    protected $plugin_name;

    /**
     * La version du plugin
     */
    protected $version;

    public function __construct() {
        $this->plugin_name = 'lingua-commerce-ai';
        $this->version = defined( 'LINGUA_COMMERCE_AI_VERSION' ) ? LINGUA_COMMERCE_AI_VERSION : '1.0.0';

        // 1. Chargement des fichiers de base (Dépendances)
        $this->load_dependencies();

        // 2. Définition des hooks principaux (Admin / Public / DB)
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_database_hooks();
    }

    /**
     * Charge les fichiers de classes fondamentaux
     * Note : Les sous-modules admin spécifiques sont chargés plus tard via 'admin_init' (Lazy Loading).
     */
    private function load_dependencies() {
        require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-loader.php';
        require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-i18n.php';
        
        // Classes Principales
        require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'admin/class-lingua-admin.php';
        require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'public/class-lingua-public.php';
        require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-database.php';
        
        // Services et Gestionnaire
        require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-language-service.php';
        require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-switcher-manager.php';

        $this->loader = new LinguaCommerce_AI_Loader();
    }

    /**
     * Définit la locale pour l'internationalisation
     */
    private function set_locale() {
        $plugin_i18n = new LinguaCommerce_AI_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Enregistre les hooks de l'administration principale
     */
    private function define_admin_hooks() {
        // Instanciation de la classe Admin générique
        $plugin_admin = new LinguaCommerce_AI_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        $this->loader->add_filter( 'plugin_action_links_' . LINGUA_COMMERCE_AI_PLUGIN_BASENAME, $plugin_admin, 'add_plugin_action_links' );

        // Hook pour le chargement différé des sous-modules (AJAX ou Pages spécifiques)
        $this->loader->add_action( 'admin_init', $this, 'load_admin_submodules' );
    }

    /**
     * Charge dynamiquement les sous-modules Admin
     * Optimise les performances en ne chargeant que ce qui est nécessaire.
     */
    public function load_admin_submodules() {
        // Cas 1 : Requête AJAX (Indispensable pour que les actions AJAX fonctionnent)
        if ( $this->is_ajax_request() ) {
            $this->load_languages_module();
            $this->load_translations_module(); 
            $this->load_ai_module(); // Correction: Chargement du module IA pour AJAX
            return;
        }

        // Cas 2 : Page "Langues"
        if ( $this->is_plugin_page( 'lingua-commerce-ai-languages' ) ) {
            $this->load_languages_module();
        }

        // Cas 3 : Page "Traductions"
        if ( $this->is_plugin_page( 'lingua-commerce-ai-translations' ) ) {
            $this->load_translations_module();
        }
        
        // Cas 4 : Page "IA & Automatisation"
        if ( $this->is_plugin_page( 'lingua-commerce-ai-ai' ) ) {
            $this->load_ai_module();
        }
    }

    /**
     * Charge et instancie le module de gestion des langues
     */
    private function load_languages_module() {
        if ( ! class_exists( 'LinguaCommerce_AI_Admin_Languages' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'admin/class-lingua-admin-languages.php';
        }
        new LinguaCommerce_AI_Admin_Languages();
    }

    /**
     * Charge et instancie le module de gestion des traductions
     */
    private function load_translations_module() {
        if ( ! class_exists( 'LinguaCommerce_AI_Admin_Translations' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'admin/class-lingua-admin-translations.php';
        }
        new LinguaCommerce_AI_Admin_Translations();
    }

    /**
     * Charge et instancie le module IA
     * CORRECTION ICI : On utilise le nom de classe 'Lingua_Admin_AI' qui correspond au fichier fourni précédemment.
     */
    private function load_ai_module() {
        if ( ! class_exists( 'Lingua_Admin_AI' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-lingua-admin-ai.php';
        }
        new Lingua_Admin_AI();
    }

        /**
     * Enregistre tous les hooks publics
     */
    private function define_public_hooks() {
        $plugin_public = new LinguaCommerce_AI_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'init', $plugin_public, 'init' );
                
        // SEO & Référencement
        $this->loader->add_filter( 'get_canonical_url', $plugin_public, 'fix_canonical_url', 10, 2 );
        $this->loader->add_action( 'wp_head', $plugin_public, 'render_open_graph_tags', 1 );
        $this->loader->add_action( 'wp_head', $plugin_public, 'render_hreflang_tags', 5 );

        // --- CORRECTION SHORTCODE ---
        // On enregistre le shortcode [lingua_switcher] avec la méthode existante 'render_custom_shortcode'
        // Note : Le constructeur de class-lingua-public.php enregistre déjà [lingua_selector], 
        // ce qui couvre les deux noms pour la compatibilité.
        add_shortcode( 'lingua_switcher', array( $plugin_public, 'render_custom_shortcode' ) );
    }

    /**
     * Enregistre les hooks liés à la base de données
     */
    private function define_database_hooks() {
        $plugin_database = new LinguaCommerce_AI_Database();
        $this->loader->add_action( 'init', $plugin_database, 'init' );
    }

    /**
     * Exécute le chargeur pour exécuter tous les hooks
     */
    public function run() {
        $this->loader->run();

        // Initialisation du Gestionnaire de Switcher
        new LinguaCommerce_Switcher_Manager();
    }

    /* --- UTILITAIRES --- */

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }

    /**
     * Vérifie si la requête actuelle est une requête AJAX
     */
    private function is_ajax_request() {
        return defined( 'DOING_AJAX' ) && DOING_AJAX;
    }

    /**
     * Vérifie si nous sommes sur une page d'administration spécifique
     */
    private function is_plugin_page( $page_slug ) {
        if ( ! is_admin() ) {
            return false;
        }
        // Vérification sécurisée via la superglobale $_GET
        return isset( $_GET['page'] ) && $_GET['page'] === $page_slug;
    }
}