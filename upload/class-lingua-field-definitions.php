<?php
/**
 * Définition des champs traduisibles
 * Ce fichier agit comme un dictionnaire : il dit au plugin quels champs sont disponibles pour chaque type de contenu.
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_Field_Definitions {

    /**
     * Retourne la liste des champs pour un type d'objet donné
     */
    public static function get_fields( $object_type ) {
        
        // On récupère d'abord les champs de base
        $fields = self::get_core_fields_for_type( $object_type );

        // --- AJOUT CRITIQUE : DÉTECTION SEO ---
        $seo_fields = self::get_seo_fields();
        if ( ! empty( $seo_fields ) ) {
            $fields = array_merge( $fields, $seo_fields );
        }

        return $fields;
    }

    /* -----------------------------------------------------------
     *  DÉTECTION DES CHAMPS SEO
     * ----------------------------------------------------------- */

    /**
     * Retourne les clés Meta des plugins SEO actifs
     */
    private static function get_seo_fields() {
        $fields = array();

        // 1. Yoast SEO
        if ( defined( 'WPSEO_VERSION' ) ) {
            $fields['_yoast_wpseo_title'] = array( 'label' => '⚡ [Yoast] Titre SEO', 'type' => 'input' );
            $fields['_yoast_wpseo_metadesc'] = array( 'label' => '⚡ [Yoast] Méta Description', 'type' => 'textarea' );
        }

        // 2. RankMath
        if ( defined( 'RANK_MATH_VERSION' ) ) {
            $fields['rank_math_title'] = array( 'label' => '⚡ [RankMath] Titre SEO', 'type' => 'input' );
            $fields['rank_math_description'] = array( 'label' => '⚡ [RankMath] Méta Description', 'type' => 'textarea' );
        }

        // 3. All in One SEO
        if ( defined( 'AIOSEO_VERSION' ) ) {
            $fields['_aioseo_title'] = array( 'label' => '⚡ [AIOSEO] Titre SEO', 'type' => 'input' );
            $fields['_aioseo_description'] = array( 'label' => '⚡ [AIOSEO] Méta Description', 'type' => 'textarea' );
        }

        return $fields;
    }

    /* -----------------------------------------------------------
     *  CHAMPS PAR TYPE DE CONTENU
     * ----------------------------------------------------------- */

    private static function get_core_fields_for_type( $object_type ) {
        switch ( $object_type ) {
            
            // --- 1. CONTENU STANDARD (Pages, Articles) ---
            case 'page':
            case 'post':
                return self::get_core_fields();
                break;

            // --- 2. WOOCOMMERCE (Produits) ---
            case 'product':
                return self::get_product_fields();
                break;

            // --- 3. MÉDIAS (Images) ---
            case 'attachment':
                return self::get_media_fields();
                break;

            // --- 4. TAXONOMIES (Catégories, Tags, Marques) ---
            case 'product_cat':
            case 'product_tag':
            case 'product_brand':
            case ( strpos( $object_type, 'pa_' ) === 0 ? true : false ):
                return self::get_taxonomy_fields();
                break;
            
            // --- 5. NOMS D'ATTRIBUTS (Globaux) ---
            case 'product_attributes':
                return array(
                    'attribute_label' => array( 'label' => 'Nom de l\'attribut (ex: Couleur)', 'type' => 'input' ),
                );
                break;

            // --- DÉFAUT ---
            default:
                return self::get_core_fields(); 
                break;
        }
    }

    private static function get_core_fields() {
        return array(
            'post_title'   => array( 'label' => 'Titre de la page/article', 'type' => 'input' ),
            'post_content' => array( 'label' => 'Contenu principal', 'type' => 'textarea' ),
            'post_excerpt' => array( 'label' => 'Extrait (Résumé)', 'type' => 'textarea' ),
        );
    }

        private static function get_product_fields() {
        // On récupère les champs de base (Titre, Contenu, Extrait)
        $fields = self::get_core_fields();
        
        // On renomme les labels pour qu'ils soient clairs pour les vendeurs
        $fields['post_title']['label'] = 'Nom du produit';
        $fields['post_excerpt']['label'] = 'Description courte (Aperçu)';
        $fields['post_excerpt']['type'] = 'textarea'; // On force le type textarea
        
        // Note: 'post_content' est la description longue
        $fields['post_content']['label'] = 'Description longue';
        
        return $fields;
    }

    private static function get_media_fields() {
        return array(
            'post_title'   => array( 'label' => 'Titre de l\'image', 'type' => 'input' ),
            'post_excerpt' => array( 'label' => 'Légende', 'type' => 'input' ),
            '_wp_attachment_image_alt' => array( 'label' => 'Texte alternatif (Alt Text)', 'type' => 'input' ),
        );
    }

    private static function get_taxonomy_fields() {
        return array(
            'name'        => array( 'label' => 'Nom de la catégorie / étiquette', 'type' => 'input' ),
            'description' => array( 'label' => 'Description', 'type' => 'textarea' ),
            'slug'        => array( 'label' => 'Slug (URL)', 'type' => 'input' ),
        );
    }
}