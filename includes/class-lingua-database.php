<?php
/**
 * Gère les interactions avec la base de données
 * VERSION OPTIMISÉE : Utilise un cache Transitoire (1h) + Cache Mémoire (Requête)
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

// Si ce fichier est appelé directement, on abandonne.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_AI_Database {

    /**
     * Cache mémoire au niveau de la requête (Request Cache).
     * Permet d'éviter de demander à la BDD ou au Transient plusieurs fois pour le même objet sur la même page.
     * Structure : [object_id][lang_code] = array( 'field_key' => 'translated_text' )
     */
    private static $request_cache = array();

    /**
     * Liste des clés Meta SEO connues
     */
    private $seo_meta_keys = array(
        'yoast' => array('_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_focuskw'),
        'rankmath' => array('rank_math_title', 'rank_math_description', 'rank_math_focus_keyword'),
        'aioseo' => array('_aioseo_title', '_aioseo_description'),
    );

    /**
     * Initialisation des hooks
     */
    public function init() {
        // 1. Filtres standards
        add_filter( 'the_title', array( $this, 'translate_text' ), 10, 2 );
        add_filter( 'the_content', array( $this, 'translate_text' ), 10, 1 );
        add_filter( 'get_the_excerpt', array( $this, 'translate_text' ), 10, 1 );
        add_filter( 'the_excerpt', array( $this, 'translate_text' ), 10, 1 );
        add_filter( 'get_term', array( $this, 'translate_term' ), 10, 2 );

        // 2. Filtre SEO (Métadonnées) - C'EST LA PRIORITÉ
        add_filter( 'get_post_metadata', array( $this, 'translate_post_meta' ), 10, 4 );
    }

       /**
     * Traduit le texte standard (Titre, Contenu, Extrait)
     * VERSION OPTIMISÉE : Utilise le cache batch (get_object_translations)
     * pour charger toutes les traductions d'un objet en une seule fois.
     *
     * @param string $text Le texte original.
     * @param int|null $id L'ID de l'objet (optionnel).
     * @return string Le texte traduit ou original.
     */
    public function translate_text( $text, $id = null ) {
        // 1. Optimisation basique : Si le texte est vide, on retourne vide.
        if ( empty( $text ) ) {
            return $text;
        }

        // 2. Détection de la langue courante.
        // La méthode get_current_lang() retourne false si on est sur la langue par défaut.
        $current_lang = $this->get_current_lang();
        if ( ! $current_lang ) {
            return $text;
        }

        // 3. Récupération de l'ID si non fourni.
        if ( ! $id ) {
            $id = get_the_ID();
        }

        // Normalisation de l'ID (Support objets)
        if ( is_object( $id ) && method_exists( $id, 'get_id' ) ) { $id = $id->get_id(); }
        elseif ( is_object( $id ) && isset( $id->ID ) ) { $id = $id->ID; }

        // Sécurité : Si pas d'ID (ex: widgets, menus contextuels), on ne peut pas traduire.
        if ( ! $id ) {
            return $text;
        }

        // 4. Mapping du filtre actuel vers la clé de champ en base de données.
        $current_filter = current_filter();
        $field_key = '';

        if ( $current_filter === 'the_title' || $current_filter === 'single_post_title' ) {
            $field_key = 'post_title';
        } elseif ( $current_filter === 'the_content' ) {
            $field_key = 'post_content';
        } elseif ( $current_filter === 'get_the_excerpt' || $current_filter === 'the_excerpt' ) {
            $field_key = 'post_excerpt';
        }

        // Si le filtre n'est pas géré, on retourne le texte original.
        if ( ! $field_key ) {
            return $text;
        }

        // 5. Récupération du type de contenu (post, page, product...).
        $object_type = get_post_type( $id );
        if ( ! $object_type ) {
            return $text;
        }

        // 6. CORRECTION PERFORMANCE :
        // On récupère TOUTES les traductions pour cet objet/langue d'un coup.
        // Cette méthode utilise le cache mémoire ($request_cache) et le transient.
        // Cela évite 3 requêtes SQL si on affiche Titre + Contenu + Extrait.
        $translations = $this->get_object_translations( $id, $object_type, $current_lang );

        // 7. Si la traduction existe pour ce champ spécifique, on la retourne.
        if ( isset( $translations[ $field_key ] ) && ! empty( $translations[ $field_key ] ) ) {
            return $translations[ $field_key ];
        }

        // 8. Fallback : On retourne le texte original.
        return $text;
    }



    /**
     * Traduit les Métadonnées (SEO, Attributs Produits...)
     * VERSION OPTIMISÉE : Charge TOUTES les métas de l'objet en une seule fois.
     */
    public function translate_post_meta( $value, $object_id, $meta_key, $single ) {
        if ( ! is_string( $meta_key ) ) return $value;

        $current_lang = $this->get_current_lang();
        if ( ! $current_lang ) return $value;

        // Vérification rapide si c'est une clé SEO (On pourrait optimiser ça aussi, mais gardons le pour la sécurité)
        $is_seo_key = false;
        foreach ( $this->seo_meta_keys as $plugin_keys ) {
            if ( in_array( $meta_key, $plugin_keys ) ) {
                $is_seo_key = true;
                break;
            }
        }

        // Si ce n'est pas une clé qu'on gère, on laisse tomber
        if ( ! $is_seo_key ) return $value;

        // Normalisation de l'ID (Support objets)
        if ( is_object( $object_id ) && method_exists( $object_id, 'get_id' ) ) { $object_id = $object_id->get_id(); }
        elseif ( is_object( $object_id ) && isset( $object_id->ID ) ) { $object_id = $object_id->ID; }

        $object_type = get_post_type( $object_id );
        if ( ! $object_type ) return $value;

        // --- MAGIE ICI : On récupère TOUTES les traductions pour cet objet/langue d'un coup ---
        $all_translations = $this->get_object_translations( $object_id, $object_type, $current_lang );

        // On vérifie si la traduction existe pour ce champ précis
        if ( isset( $all_translations[ $meta_key ] ) ) {
            $translated_text = $all_translations[ $meta_key ];
            return $single ? $translated_text : array( $translated_text );
        }

        return $value;
    }


        /**
     * Traduit les Termes (Catégories, Tags, Marques, etc.)
     * Hook : 'get_term'
     *
     * @param WP_Term $term L'objet terme récupéré de la base.
     * @param string  $taxonomy Le nom de la taxonomie associée.
     * @return WP_Term Le terme avec les données traduites si disponibles.
     */
    public function translate_term( $term, $taxonomy ) {
        // 1. Sécurité : Vérifier que l'objet est valide
        if ( ! is_object( $term ) || is_wp_error( $term ) || ! isset( $term->term_id ) ) {
            return $term;
        }

        // 2. Vérifier si on doit traduire (Langue courante != Langue par défaut)
        $current_lang = $this->get_current_lang();
        if ( ! $current_lang ) {
            return $term;
        }

        // 3. Récupération des traductions via le cache optimisé
        // NOTE : Pour les termes, l'object_type est le nom de la taxonomie (ex: 'product_cat', 'category')
        $translations = $this->get_object_translations( $term->term_id, $taxonomy, $current_lang );

        // 4. Application des traductions aux champs standard

        // Nom du terme
        if ( isset( $translations['name'] ) ) {
            $term->name = $translations['name'];
        }

        // Description du terme
        if ( isset( $translations['description'] ) ) {
            $term->description = $translations['description'];
        }

        // Note : Le slug n'est pas modifié ici pour éviter de casser les redirections d'URL,
        // sauf si vous avez une logique spécifique pour le slug dans vos définitions de champs.

        return $term;
    }

    /* -----------------------------------------------------------
     *  NOUVELLE MÉTHODE CŒUR : Gestionnaire de Cache Intelligent
     * ----------------------------------------------------------- */

    /**
     * Récupère les traductions pour un objet donné en utilisant le système de cache double.
     *
     * @param int    $object_id   ID du post/terme
     * @param string $object_type Type de l'objet (post, product, product_cat...)
     * @param string $lang_code   Code langue (ex: en_US)
     * @return array Tableau associatif ['field_key' => 'translated_text']
     */
    private function get_object_translations( $object_id, $object_type, $lang_code ) {

        // 1. Vérification du Cache Mémoire (Ultra Rapide)
        // Si on a déjà chargé les traductions pour cet objet sur cette page, on les renvoie direct.
        if ( isset( self::$request_cache[ $object_id ][ $lang_code ] ) ) {
            return self::$request_cache[ $object_id ][ $lang_code ];
        }

        // 2. Vérification du Cache Transitoire (Rapide)
        // Si ça existe dans la BDD (table wp_options) et qu'il n'est pas expiré, on le prend.
        $transient_key = 'lingua_trans_' . $object_id . '_' . $object_type . '_' . $lang_code;
        $cached_data = get_transient( $transient_key );

        if ( false !== $cached_data ) {
            // On le met dans le cache mémoire pour la suite de la page
            self::$request_cache[ $object_id ][ $lang_code ] = $cached_data;
            return $cached_data;
        }

        // 3. Requête BDD (Le coup le plus lourd, mais on ne le fait qu'une fois par heure max)
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translations';

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT field_key, translated_text FROM $table
             WHERE object_id = %s AND object_type = %s AND language = %s AND status = 'validated'",
            $object_id, $object_type, $lang_code
        ) );

        $translations = array();
        foreach ( $results as $row ) {
            $translations[ $row->field_key ] = $row->translated_text;
        }

               // 4. Mise à jour des Caches (DURÉE DYNAMIQUE)
        // On lit la durée définie dans les réglages, sinon 1 heure par défaut
        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        $cache_time = isset( $settings['cache_duration'] ) ? intval( $settings['cache_duration'] ) : 3600;

        set_transient( $transient_key, $translations, $cache_time );

        // On sauvegarde dans la variable statique pour la suite du chargement de page actuel
        self::$request_cache[ $object_id ][ $lang_code ] = $translations;

        return $translations;
    }

    /* -----------------------------------------------------------
     *  UTILITAIRES (Sécurisés)
     * ----------------------------------------------------------- */

    private function get_current_lang() {
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
        }

        $current_lang = LinguaCommerce_Language_Service::get_current_language_code();
        $default_lang_obj = LinguaCommerce_Language_Service::get_default_language();
        $default_lang_code = $default_lang_obj ? $default_lang_obj->code : 'en_US';

        if ( $current_lang === $default_lang_code ) return false;
        return $current_lang;
    }

    /**
     * Récupère une traduction unique (Méthode legacy pour the_title, etc)
     * Note: Pour optimiser davantage, on pourrait aussi la faire passer par get_object_translations,
     * mais pour l'instant on laisse la requête unique pour ne pas casser l'existant.
     */
    private function fetch_translation( $object_id, $object_type, $field_key, $lang_code, $original_text ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translations';

        $translated_text = $wpdb->get_var( $wpdb->prepare(
            "SELECT translated_text FROM $table
             WHERE object_id = %s AND object_type = %s AND field_key = %s
             AND language = %s AND status = 'validated' LIMIT 1",
            $object_id, $object_type, $field_key, $lang_code
        ) );

        return $translated_text ? $translated_text : $original_text;
    }
}
