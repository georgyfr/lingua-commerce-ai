<?php
/**
 * Modèle de données pour les traductions
 * Gère les interactions avec la table wp_lingua_translations
 * VERSION OPTIMISÉE : Requêtes SQL unifiées pour éviter les boucles (Problème N+1)
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_Translation_Model {

    /**
     * Retourne la liste des champs traduisibles pour un type d'objet
     */
    public static function get_translatable_fields( $object_type ) {
        if ( ! class_exists( 'LinguaCommerce_Field_Definitions' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-field-definitions.php';
        }
        return LinguaCommerce_Field_Definitions::get_fields( $object_type );
    }

    /**
     * Récupère la liste des éléments (OPTIMISÉ)
     * Utilise un LEFT JOIN pour compter les traductions en une seule requête.
     */
    public static function get_translations_list( $post_type, $target_lang, $status_filter = 'all' ) {
        global $wpdb;
        $table_trans = $wpdb->prefix . 'lingua_translations';
        $items = array();

        // On récupère la liste des champs UNE SEULE FOIS avant la boucle pour le calcul du pourcentage
        $translatable_fields = self::get_translatable_fields( $post_type );
        $total_fields_count = count( $translatable_fields );

        // --- CAS A : MÉDIAS ---
        if ( $post_type === 'attachment' ) {
            $table_posts = $wpdb->posts;
            // OPTIMISATION : Jointure LEFT pour compter direct
            $sql = "SELECT p.ID, p.post_title, p.post_parent, p.post_mime_type,
                        par.post_title as parent_title,
                        COUNT(t.id) as translation_count
                     FROM $table_posts p
                     LEFT JOIN $table_posts par ON p.post_parent = par.ID
                     LEFT JOIN $table_trans t 
                        ON p.ID = t.object_id 
                        AND t.language = %s 
                        AND t.object_type = 'attachment' 
                        AND t.status = 'validated'
                     WHERE p.post_type = 'attachment' 
                     AND (p.post_mime_type LIKE 'image%' OR p.post_mime_type LIKE 'video%')
                     GROUP BY p.ID
                     ORDER BY p.post_date DESC";
            $query = $wpdb->prepare( $sql, $target_lang );
            $items = $wpdb->get_results( $query );
        }

        // --- CAS B : TERMES (Catégories, Tags, Attributs) ---
        elseif ( in_array( $post_type, array( 'product_cat', 'product_tag', 'product_brand' ) ) || ( strpos( $post_type, 'pa_' ) === 0 ) ) {
            $table_terms = $wpdb->terms;
            $table_term_taxonomy = $wpdb->term_taxonomy;
            
            // OPTIMISATION : Jointure LEFT
            $sql = "SELECT t.term_id as ID, t.name as post_title, tt.taxonomy as post_type,
                        COUNT(lt.id) as translation_count
                     FROM $table_term_taxonomy tt
                     INNER JOIN $table_terms t ON t.term_id = tt.term_id
                     LEFT JOIN $table_trans lt 
                        ON t.term_id = lt.object_id 
                        AND lt.language = %s 
                        AND lt.object_type = %s
                        AND lt.status = 'validated'
                     WHERE tt.taxonomy = %s
                     GROUP BY t.term_id
                     ORDER BY t.name ASC";
            $query = $wpdb->prepare( $sql, $target_lang, $post_type, $post_type );
            $items = $wpdb->get_results( $query );
        } 
        
        // --- CAS C : ATTRIBUTS PRODUITS (Globaux) ---
        elseif ( $post_type === 'product_attributes' ) {
            $table_attrs = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
            $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_attrs'" );
            
            if ( $table_exists ) {
                // OPTIMISATION : Jointure LEFT
                $sql = "SELECT t.attribute_id as ID, 
                           t.attribute_label as post_title, 
                           %s as post_type,
                           'publish' as post_status,
                           COUNT(lt.id) as translation_count
                    FROM $table_attrs t
                    LEFT JOIN $table_trans lt 
                        ON t.attribute_id = lt.object_id 
                        AND lt.language = %s 
                        AND lt.object_type = %s
                        AND lt.status = 'validated'
                    GROUP BY t.attribute_id
                    ORDER BY t.attribute_label ASC";
                $query = $wpdb->prepare( $sql, $post_type, $target_lang, $post_type );
                $items = $wpdb->get_results( $query );
            } else {
                $items = array();
            }
        }
        
        // --- CAS D : POSTS STANDARDS (Pages, Articles, Produits) ---
        else {
            $table_posts = $wpdb->posts;
            // OPTIMISATION : Jointure LEFT
            $sql = "SELECT p.ID, p.post_title, p.post_type, p.post_status,
                        COUNT(t.id) as translation_count
                     FROM $table_posts p
                     LEFT JOIN $table_trans t 
                        ON p.ID = t.object_id 
                        AND t.language = %s 
                        AND t.object_type = %s
                        AND t.status = 'validated'
                     WHERE p.post_type = %s
                     GROUP BY p.ID
                     ORDER BY p.post_date DESC";
            $query = $wpdb->prepare( $sql, $target_lang, $post_type, $post_type );
            $items = $wpdb->get_results( $query );
        }

        // Traitement des résultats (PHP)
        $filtered_items = array();

        foreach ( $items as $item ) {
            $count = ( isset( $item->translation_count ) ) ? intval( $item->translation_count ) : 0;
            $is_translated = ( $count > 0 );
            
            // Filtre rapide
            if ( $status_filter === 'translated' && ! $is_translated ) continue;
            if ( $status_filter === 'untranslated' && $is_translated ) continue;

            // Calcul du pourcentage (Math simple, plus de requête SQL ici !)
            $percent = 0;
            if ( $total_fields_count > 0 ) {
                $percent = ( $count / $total_fields_count ) * 100;
            }

            $item->progress_percent = round( $percent, 0 );
            $item->status_label = ( $item->progress_percent == 100 ) ? 'Validé' : ( $item->progress_percent > 0 ? 'En cours' : 'À traduire' );
            
            $filtered_items[] = $item;
        }

        return $filtered_items;
    }

    /**
     * Récupère une traduction spécifique
     */
    public static function get_translation( $object_id, $object_type, $field_key, $lang_code ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translations';
        $sql = $wpdb->prepare( 
            "SELECT translated_text, status, source FROM $table 
             WHERE object_id = %s AND object_type = %s AND field_key = %s AND language = %s 
             LIMIT 1", 
            $object_id, $object_type, $field_key, $lang_code 
        );
        return $wpdb->get_row( $sql );
    }

        /**
     * Récupère la VALEUR ORIGINALE d'un champ
     * CORRECTION : Gestion précise SEO pour Produits vs Catégories
     */
    public static function get_original_value( $object_id, $field_key, $object_type = null ) {
        
        // 1. C'est un Attribut Produit Global
        if ( $object_type === 'product_attributes' || $field_key === 'attribute_label' ) {
            global $wpdb;
            $table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
            return $wpdb->get_var( $wpdb->prepare( "SELECT attribute_label FROM $table WHERE attribute_id = %s", $object_id ) );
        }
        
        // 2. C'est un champ de Terme Standard (Nom, Description, Slug)
        if ( in_array( $field_key, array( 'name', 'description', 'slug' ) ) ) {
            $term = get_term( $object_id );
            if ( ! is_wp_error( $term ) && $term ) {
                if ( $field_key === 'name' ) return $term->name;
                if ( $field_key === 'description' ) return $term->description;
                if ( $field_key === 'slug' ) return $term->slug;
            }
            return '';
        }

        // 3. C'est un champ POST STANDARD (Titre, Contenu, Extrait)
        $post_fields = array( 'post_title', 'post_content', 'post_excerpt', 'post_name' );
        if ( in_array( $field_key, $post_fields ) ) {
            return get_post_field( $field_key, $object_id );
        }

        // 4. META FIELDS (SEO, Custom Fields)
        // LOGIQUE SEO AMÉLIORÉE :
        // Si l'objet est une taxonomie (catégorie, tag...), on cherche dans termmeta
        if ( taxonomy_exists( $object_type ) ) {
            return get_term_meta( $object_id, $field_key, true );
        } 
        // Sinon, c'est un post (produit, page...), on cherche dans postmeta
        else {
            return get_post_meta( $object_id, $field_key, true );
        }
    }
         /**
     * Sauvegarde ou met à jour une traduction (Sécurisé & Universel)
     * Supporte les IDs numériques (123) ET textuels (site_name, widget_title).
     */
    public static function save_translation( $object_id, $object_type, $field_key, $lang_code, $translated_text, $status = 'draft' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translations';

        // 1. Sécurisation des entrées
        // Normalisation de l'ID (Support objets)
        if ( is_object( $object_id ) && method_exists( $object_id, 'get_id' ) ) { $object_id = $object_id->get_id(); }
        elseif ( is_object( $object_id ) && isset( $object_id->ID ) ) { $object_id = $object_id->ID; }

        // On cast en string pour supporter les IDs textuels (ex: 'site_name')
        $object_id      = (string) sanitize_text_field( $object_id );
        $object_type    = sanitize_text_field( $object_type );
        $field_key      = sanitize_text_field( $field_key );
        $lang_code      = sanitize_text_field( $lang_code );
        
        // 2. Récupération du texte original
        // On tente de récupérer l'original si possible
        $original_text = self::get_original_value( $object_id, $field_key, $object_type );

        // 3. Nettoyage HTML du texte traduit
        $allowed_html = wp_kses_allowed_html( 'post' );
        $clean_text = wp_kses( $translated_text, $allowed_html );

        // 4. Préparation des données
        $data = array(
            'object_type'    => $object_type,
            'object_id'      => $object_id,
            'field_key'      => $field_key,
            'language'       => $lang_code,
            'original_text'  => $original_text,
            'translated_text' => $clean_text,
            'status'         => $status,
            'source'         => 'manual',
            'last_updated'   => current_time( 'mysql' )
        );

        // 5. Formats SQL
        // IMPORTANT : On utilise %s (string) pour object_id pour supporter "site_name"
        // Si votre colonne object_id est BIGINT, les IDs textuels seront stockés comme 0.
        // Assurez-vous que la colonne object_id est de type VARCHAR ou TEXT.
        $formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
        
        // 6. Nettoyage du cache
        $transient_key = 'lingua_trans_' . $object_id . '_' . $object_type . '_' . $lang_code;
        delete_transient( $transient_key );

        // 7. Remplacement (Insert si nouveau, Update si existe)
        return $wpdb->replace( $table, $data, $formats );
    }
    
    /**
     * Calcule le pourcentage de traduction
     * Note : Cette fonction est conservée pour compatibilité ou usage unitaire, 
     * mais n'est plus utilisée dans les boucles de liste pour la performance.
     */
    public static function calculate_progress( $object_id, $object_type, $target_lang ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translations';
        $fields_map = self::get_translatable_fields( $object_type );
        $total_fields = count( $fields_map );

        if ( $total_fields === 0 ) return 0;

        $count = $wpdb->get_var( $wpdb->prepare( 
            "SELECT COUNT(id) FROM $table 
             WHERE object_id = %s AND object_type = %s AND language = %s AND status = 'validated'", 
            $object_id, $object_type, $target_lang 
        ));

        return ( $count / $total_fields ) * 100;
    }

    /**
     * Récupère toutes les traductions d'un objet
     */
    public static function get_all_translations_for_object( $object_id, $object_type, $target_lang ) {
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translations';
        $results = $wpdb->get_results( $wpdb->prepare( 
            "SELECT field_key, translated_text, status FROM $table 
             WHERE object_id = %s AND object_type = %s AND language = %s", 
            $object_id, $object_type, $target_lang 
        ));

        $translations = array();
        foreach ( $results as $row ) {
            $translations[ $row->field_key ] = array(
                'text' => $row->translated_text,
                'status' => $row->status
            );
        }
        return $translations;
    }
    
        /**
     * Récupère les données pour l'audit SEO (Version Optimisée)
     * Centralisée pour être utilisée par l'Admin principal et le module Traductions
     */
    public static function get_seo_audit_data( $post_type, $target_lang ) {
        global $wpdb;
        $table_posts = $wpdb->posts;
        $table_trans = $wpdb->prefix . 'lingua_translations';

        // Détection de la clé SEO selon le plugin actif
        $seo_title_key = '_yoast_wpseo_title';
        $seo_desc_key = '_yoast_wpseo_metadesc';

        if ( defined( 'RANK_MATH_VERSION' ) ) {
            $seo_title_key = 'rank_math_title';
            $seo_desc_key = 'rank_math_description';
        } elseif ( defined( 'AIOSEO_VERSION' ) ) {
            $seo_title_key = '_aioseo_title';
            $seo_desc_key = '_aioseo_description';
        }

        $items = array();

        // --- Requête Optimisée avec JOINS pour récupérer tout en 1 fois ---
        
        // Préparation des conditions communes
        $limit = 50; // On garde une limite pour la sécurité

        if ( $post_type === 'attachment' ) {
            $sql = $wpdb->prepare( 
                "SELECT p.ID, p.post_title, p.post_type, p.post_mime_type,
                        COUNT(t_all.id) as trans_count,
                        t_title.translated_text as seo_title_text,
                        t_desc.id as has_seo_desc_id
                 FROM $table_posts p
                 LEFT JOIN $table_trans t_all 
                    ON p.ID = t_all.object_id AND t_all.language = %s AND t_all.object_type = 'attachment' AND t_all.status = 'validated'
                 LEFT JOIN $table_trans t_title 
                    ON p.ID = t_title.object_id AND t_title.language = %s AND t_title.object_type = 'attachment' AND t_title.field_key = %s
                 LEFT JOIN $table_trans t_desc 
                    ON p.ID = t_desc.object_id AND t_desc.language = %s AND t_desc.object_type = 'attachment' AND t_desc.field_key = %s
                 WHERE p.post_type = 'attachment' AND p.post_status != 'trash'
                 GROUP BY p.ID
                 ORDER BY p.post_date DESC LIMIT %d", 
                $target_lang, $target_lang, $seo_title_key, $target_lang, $seo_desc_key, $limit
            );
            $results = $wpdb->get_results( $sql );
        } else {
            // Pour les posts et produits
            $sql = $wpdb->prepare( 
                "SELECT p.ID, p.post_title, p.post_type, p.post_status,
                        COUNT(t_all.id) as trans_count,
                        t_title.translated_text as seo_title_text,
                        t_desc.id as has_seo_desc_id
                 FROM $table_posts p
                 LEFT JOIN $table_trans t_all 
                    ON p.ID = t_all.object_id AND t_all.language = %s AND t_all.object_type = %s AND t_all.status = 'validated'
                 LEFT JOIN $table_trans t_title 
                    ON p.ID = t_title.object_id AND t_title.language = %s AND t_title.object_type = %s AND t_title.field_key = %s
                 LEFT JOIN $table_trans t_desc 
                    ON p.ID = t_desc.object_id AND t_desc.language = %s AND t_desc.object_type = %s AND t_desc.field_key = %s
                 WHERE p.post_type = %s AND p.post_status = 'publish'
                 GROUP BY p.ID
                 ORDER BY p.post_modified_gmt DESC LIMIT %d", 
                $target_lang, $post_type, 
                $target_lang, $post_type, $seo_title_key, 
                $target_lang, $post_type, $seo_desc_key, 
                $post_type, $limit
            );
            $results = $wpdb->get_results( $sql );
        }

        // Traitement des résultats
        if ( $results ) {
            foreach ( $results as $row ) {
                $trans_count = ( isset( $row->trans_count ) ) ? intval( $row->trans_count ) : 0;
                
                // Estimation simple : si > 0 champs traduits sur ~5 standards
                $percent = ( $trans_count > 0 ) ? 100 : 0; 
                if ( $trans_count > 0 && $trans_count < 5 ) $percent = 60;

                $has_seo_title = ! empty( $row->seo_title_text );
                $seo_title_text = $row->seo_title_text;
                $has_seo_desc = ! empty( $row->has_seo_desc_id );

                $items[] = (object) array(
                    'ID' => $row->ID,
                    'post_title' => $row->post_title,
                    'post_type' => $row->post_type,
                    'progress_percent' => $percent,
                    'has_seo_title' => $has_seo_title,
                    'seo_title_text' => $seo_title_text,
                    'has_seo_desc' => $has_seo_desc
                );
            }
        }

        return $items;
    }
}