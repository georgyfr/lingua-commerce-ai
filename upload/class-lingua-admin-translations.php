<?php
/**
 * Contrôleur pour la page d'administration des Traductions
 * Supporte : Listes, Mosaïque (Grid), Cascade et Audit SEO
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_AI_Admin_Translations {

    /**
     * Constructeur
     */
    public function __construct() {
        // Protection contre la double instanciation des hooks
        if ( ! did_action( 'lingua_translations_hooks_registered' ) ) {
            
            // Hooks standards
            add_action( 'wp_ajax_lingua_get_translations_list', array( $this, 'ajax_get_list' ) );
            add_action( 'wp_ajax_lingua_get_editor_data', array( $this, 'ajax_get_editor_data' ) );
            add_action( 'wp_ajax_lingua_save_translation', array( $this, 'ajax_save_translation' ) );
            add_action( 'wp_ajax_lingua_delete_translation', array( $this, 'ajax_delete_translation' ) );

            do_action( 'lingua_translations_hooks_registered' );
        }
    }

        /**
     * Affiche la page (Charge la vue)
     */
    public function render() {
                // --- WIDGET SYNCHRO IA ---
        // On récupère les stats manuellement pour l'affichage initial
        global $wpdb;
        $table_name = $wpdb->prefix . 'lingua_translation_queue';
        $pending = 0; $failed = 0;
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $pending = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status='pending'");
            $failed = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status='failed'");
        }
        
        echo '<div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px 20px; margin: 20px 0; border-radius: 5px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">';
        
        echo '<div style="display:flex; gap:20px;">';
        echo '<span><strong style="font-size:18px; color:#0073aa;">' . intval($pending) . '</strong> En attente</span>';
        echo '<span><strong style="font-size:18px; color:#d63638;">' . intval($failed) . '</strong> Échouées</span>';
        echo '</div>';
        
        echo '<div style="display:flex; gap:10px;">';
        // Lien rapide vers les outils
        echo '<a href="' . admin_url('admin.php?page=lingua-commerce-ai-tools') . '" class="button">🛠️ Centre de Contrôle IA</a>';
        echo '</div>';
        
        echo '</div>';
        // --- FIN WIDGET ---
        // Chargement du Service
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
        }

        $default_lang = LinguaCommerce_Language_Service::get_default_language();
        $active_languages = LinguaCommerce_Language_Service::get_active_languages();
        
        if ( ! $default_lang ) {
            $default_lang = (object) array('code' => 'unknown', 'native_name' => 'Inconnu');
        }

        // --- RÉCUPÉRATION DES RÉGLAGES ---
        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        
        // 1. Types de contenus (Produits, Pages...)
        $active_post_types = isset( $settings['active_post_types'] ) ? $settings['active_post_types'] : array('post', 'page', 'product');
        
        // 2. Taxonomies (Catégories, Tags...)
        $active_taxonomies = isset( $settings['active_taxonomies'] ) ? $settings['active_taxonomies'] : array('category', 'product_cat');

        // 3. Moteurs IA
        global $wpdb;
        $table_engines = $wpdb->prefix . 'lingua_ai_engines';
        $settings_global = get_option( 'lingua_commerce_ai_settings', array() );
        $default_engine_slug = isset( $settings_global['default_engine'] ) ? $settings_global['default_engine'] : 'openrouter';
        
        $engines_results = $wpdb->get_results( "SELECT engine_name FROM $table_engines WHERE status = 'active'" );
        $active_engines = array();
        
        if ( $engines_results ) {
            foreach ( $engines_results as $e ) {
                $name = $e->engine_name;
                $label = ucfirst( $name );
                $is_default = ($name === $default_engine_slug); 
                
                $active_engines[] = array(
                    'slug' => $name,
                    'label' => $label,
                    'is_default' => $is_default
                );
            }
        }
        
        if ( empty( $active_engines ) ) {
            $active_engines[] = array(
                'slug' => '',
                'label' => '⚠️ Aucun moteur configuré',
                'is_default' => true
            );
        }

        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-admin-translations-display.php';
    }
    /* -----------------------------------------------------------
     *  HANDLERS AJAX
     * ----------------------------------------------------------- */
    /**
     * AJAX : Récupérer la liste des contenus à traduire
     * Gère le mode d'affichage (Liste vs Mosaïque vs Cascade)
     */
    public function ajax_get_list() {
        // 1. Sécurité
        if ( ! check_ajax_referer( 'lingua_admin_nonce', 'nonce', false ) ) wp_send_json_error( 'Erreur sécurité (Nonce).' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission refusée.' );

        // 2. Récupération des entrées
        $post_type  = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : 'page';
        $target_lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : 'en_US';
        $status     = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'all';
        $view_mode  = isset( $_POST['view_mode'] ) ? sanitize_text_field( $_POST['view_mode'] ) : 'list';

        // Chargement du modèle
        if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-translation-model.php';
        }

        $items = LinguaCommerce_Translation_Model::get_translations_list( $post_type, $target_lang, $status );

        // 3. Génération du HTML selon la vue choisie
        ob_start();

        if ( empty( $items ) ) {
            if($view_mode === 'list') {
                echo '<tr><td colspan="3" style="text-align:center; padding: 40px;">Aucun contenu trouvé pour ce type.</td></tr>';
            } else {
                echo '<p style="text-align:center; color: #666; padding: 20px;">Aucun contenu trouvé.</p>';
            }
        } else {
            
            if ( $view_mode === 'masonry' || $view_mode === 'cascade' ) {
                // --- MODE MOSAÏQUE OU CASCADE ---
                foreach ( $items as $item ) {
                    $badge_color = ( $item->progress_percent == 100 ) ? 'green' : ( $item->progress_percent > 0 ? 'orange' : 'red' );
                    
                    echo '<div class="lingua-card-item" data-id="' . esc_attr( $item->ID ) . '">';
                    echo '<div class="lingua-card">';
                    
                    if ( $post_type === 'attachment' ) {
                        // Affichage spécial pour les médias (déjà géré)
                        $parent_name = ( ! empty( $item->parent_title ) ) ? $item->parent_title : 'Sans Parent';
                        
                        echo '<div class="lingua-card-thumb">';
                        echo '<img src="' . esc_url( wp_get_attachment_image_url( $item->ID, 'medium' ) ) . '" alt="">';
                        echo '</div>';
                        
                        echo '<div class="lingua-card-content">';
                        echo '<h3>' . esc_html( $item->post_title ) . '</h3>';
                        echo '<div class="lingua-card-meta">Type: ' . esc_html( $item->post_type ) . ' | Parent: ' . esc_html( $parent_name ) . '</div>';
                        
                        if ( isset( $item->progress_percent ) ) {
                            echo '<div class="lingua-progress-bar" style="width:100%; background:#eee; height:4px; border-radius:2px; margin:10px 0;">';
                            echo '<div style="width:' . esc_attr( $item->progress_percent ) . '%; background:' . ( $badge_color == 'green' ? '#00a32a' : ( $badge_color == 'orange' ? '#dba617' : '#d63638' ) ) . '; height:100%; border-radius:2px;"></div>';
                            echo '</div>';
                        }
                        
                        echo '<div class="lingua-card-actions">';
                        if( isset( $item->progress_percent ) && $item->progress_percent == 100 ) {
                            echo '<span class="dashicons dashicons-yes-alt" style="color:green;"></span> Traduit';
                        } else {
                            echo '<button class="button button-small lingua-btn-edit" data-id="' . esc_attr( $item->ID ) . '" data-type="' . esc_attr( $item->post_type ) . '">';
                            echo '✏️ Traduire';
                            echo '</button>';
                        }
                        echo '</div>';
                        
                    } else {
                        // --- NOUVEAU : RÉCUPÉRATION IMAGE PRODUIT/ARTICLE ---
                        // On essaie de récupérer l'image mise en avant (Thumbnail)
                        $thumb_url = get_the_post_thumbnail_url( $item->ID, 'medium' );
                        
                        echo '<div class="lingua-card-thumb" style="background:#f0f0f0; display:flex; align-items:center; justify-content:center; min-height: 160px;">';
                        
                        if ( $thumb_url ) {
                            // Si une image existe, on l'affiche
                            echo '<img src="' . esc_url( $thumb_url ) . '" style="width:100%; height:100%; object-fit:cover;" alt="">';
                        } else {
                            // Sinon on garde l'icône par défaut
                            echo '<span class="dashicons dashicons-admin-post" style="font-size:48px; color:#ccc;"></span>';
                        }
                        
                        echo '</div>';
                        
                        echo '<div class="lingua-card-content">';
                        echo '<h3>' . esc_html( $item->post_title ) . '</h3>';
                        echo '<div class="lingua-card-meta">Type: ' . esc_html( $item->post_type ) . ' | ID: ' . esc_html( $item->ID ) . '</div>';
                        
                        echo '<div class="lingua-progress-bar" style="width:100%; background:#eee; height:4px; border-radius:2px; margin:10px 0;">';
                        echo '<div style="width:' . esc_attr( $item->progress_percent ) . '%; background:' . ( $badge_color == 'green' ? '#00a32a' : ( $badge_color == 'orange' ? '#dba617' : '#d63638' ) ) . '; height:100%; border-radius:2px;"></div>';
                        echo '</div>';
                        
                        echo '<div class="lingua-card-actions">';
                        if( $item->progress_percent == 100 ) {
                            echo '<span class="dashicons dashicons-yes-alt" style="color:green;"></span> Traduit';
                        } else {
                            echo '<button class="button button-small lingua-btn-edit" data-id="' . esc_attr( $item->ID ) . '" data-type="' . esc_attr( $item->post_type ) . '">';
                            echo '✏️ Traduire';
                            echo '</button>';
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>'; // Fin lingua-card-content
                    echo '</div>'; // Fin lingua-card
                    echo '</div>'; // Fin lingua-card-item
                }

            } else {
                // --- MODE LISTE (Tableau Standard) ---
                if ( $post_type === 'attachment' ) {
                    // Logique Groupée pour les médias
                    $current_parent = '';
                    foreach ( $items as $item ) {
                        if ( $item->parent_title !== $current_parent ) {
                            $current_parent = $item->parent_title;
                            echo '<tr class="lingua-media-group-header">';
                            echo '<td colspan="3" style="background: #f0f0f1; font-weight:bold; padding: 8px 10px; border-bottom: 2px solid #ddd; color: #444; font-size: 13px;">';
                            echo '📁 ' . esc_html( $current_parent );
                            echo '</td></tr>';
                        }

                        $badge_color = ( isset( $item->translation_count ) && $item->translation_count > 0 ) ? 'green' : 'red';
                        echo '<tr>';
                        echo '<td class="lingua-sticky-col" style="position: sticky; left: 0; background: #fff; border-right: 1px solid #ddd; z-index: 9; padding: 8px 10px;">';
                        echo '<div style="display: flex; align-items: center; gap: 12px;">';
                        echo '<img src="' . esc_url( wp_get_attachment_thumb_url( $item->ID ) ) . '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #eee;">';
                        echo '<div>';
                        echo '<strong style="display:block;">' . esc_html( $item->post_title ) . '</strong>';
                        $mime_type = isset( $item->post_mime_type ) ? $item->post_mime_type : '';
                        echo '<div style="font-size: 11px; color: #666; margin-top:2px;">Type: ' . esc_html( str_replace('image/', '', $mime_type ) ) . '</div>';
                        echo '</div>';
                        echo '</div></td>';
                        echo '<td style="text-align: center;">';
                        echo '<span class="lingua-badge lingua-badge-' . $badge_color . '">' . ( ( isset( $item->translation_count ) && $item->translation_count > 0 ) ? 'Traduit' : 'À traduire' ) . '</span>';
                        echo '</td>';
                        echo '<td style="text-align: right;">';
                        echo '<button class="button button-small lingua-btn-edit" data-id="' . esc_attr( $item->ID ) . '" data-type="' . esc_attr( $post_type ) . '">✏️ Modifier Alt Texte</button>';
                        echo '</td></tr>';
                    }

                } else {
                    // Logique Standard pour les autres types
                    foreach ( $items as $item ) {
                        $badge_color = ( $item->progress_percent == 100 ) ? 'green' : ( $item->progress_percent > 0 ? 'orange' : 'red' );
                        $status_label = isset( $item->status_label ) ? $item->status_label : ( $item->progress_percent == 100 ? 'Traduit' : ( $item->progress_percent > 0 ? 'En cours' : 'À traduire' ) );
                        
                        echo '<tr>';
                        echo '<td class="lingua-sticky-col" style="position: sticky; left: 0; background: #fff; border-right: 1px solid #ddd; z-index: 10; padding: 12px 10px;">';
                        echo '<strong>' . esc_html( $item->post_title ) . '</strong>';
                        echo '<div class="row-actions">';
                        echo '<span>ID: ' . $item->ID . ' | Type: ' . esc_html( $item->post_type ) . '</span>';
                        echo '</div>';
                        echo '<div style="width: 100%; background: #eee; height: 4px; margin-top: 5px; border-radius: 2px;">';
                        echo '<div style="width: ' . $item->progress_percent . '%; background: ' . ( $badge_color == 'green' ? '#00a32a' : ( $badge_color == 'orange' ? '#dba617' : '#d63638' ) ) . '; height: 100%; border-radius: 2px;"></div>';
                        echo '</div>';
                        echo '</td>';
                        echo '<td style="text-align: center;">';
                        echo '<span class="lingua-badge lingua-badge-' . $badge_color . '">' . esc_html( $status_label ) . '</span>';
                        echo '</td>';
                        echo '<td style="text-align: right;">';
                        echo '<button class="button button-small lingua-btn-edit" data-id="' . esc_attr( $item->ID ) . '" data-type="' . esc_attr( $item->post_type ) . '">✏️ Traduire</button>';
                        echo '</td></tr>';
                    }
                }
            }
        }
        
        $html = ob_get_clean();
        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * AJAX : Récupérer les données pour la modale d'édition
     */
    public function ajax_get_editor_data() {
        if ( ! check_ajax_referer( 'lingua_admin_nonce', 'nonce', false ) ) wp_send_json_error( 'Erreur sécurité.' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission refusée.' );

        $object_id  = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $object_type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'page';
        $target_lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : 'en_US';

        if ( ! $object_id ) wp_send_json_error( 'ID invalide.' );

        if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-translation-model.php';
        }

        $fields_map = LinguaCommerce_Translation_Model::get_translatable_fields( $object_type );
        $existing_translations = LinguaCommerce_Translation_Model::get_all_translations_for_object( $object_id, $object_type, $target_lang );

        $editor_data = array();
        foreach ( $fields_map as $key => $config ) {
             $original_value = LinguaCommerce_Translation_Model::get_original_value( $object_id, $key, $object_type );
            $trans_data = isset( $existing_translations[ $key ] ) ? $existing_translations[ $key ] : null;
            $translation_text = $trans_data ? $trans_data['text'] : '';
            $status = $trans_data ? $trans_data['status'] : 'new';

            $editor_data[] = array(
                'key'          => $key,
                'label'        => $config['label'],
                'type'         => $config['type'],
                'original'     => $original_value,
                'translated'   => $translation_text,
                'status'       => $status,
                'is_editable'  => true
            );
        }
        wp_send_json_success( array( 'fields' => $editor_data ) );
    }

       /**
     * AJAX : Sauvegarder une traduction (Avec logs)
     */
    public function ajax_save_translation() {
        if ( ! check_ajax_referer( 'lingua_admin_nonce', 'nonce', false ) ) wp_send_json_error( 'Erreur sécurité.' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission refusée.' );

        $object_id    = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $object_type  = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'page';
        $field_key    = isset( $_POST['field'] ) ? sanitize_text_field( $_POST['field'] ) : '';
        $target_lang  = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : '';
        $content      = isset( $_POST['content'] ) ? wp_kses_post( $_POST['content'] ) : '';
        $status       = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'draft';

        if ( ! $object_id || ! $field_key || ! $target_lang ) {
            wp_send_json_error( 'Données manquantes (ID, Champ ou Langue).' );
        }

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-translation-model.php';
        
        $result = LinguaCommerce_Translation_Model::save_translation( $object_id, $object_type, $field_key, $target_lang, $content, $status );

        if ( $result === false ) {
            // Log l'erreur SQL si possible
            global $wpdb;
            error_log( "LinguaCommerce SQL Error: " . $wpdb->last_error );
            wp_send_json_error( 'Erreur base de données. Vérifiez les logs.' );
        } else {
            wp_send_json_success( 'Traduction sauvegardée.' );
        }
    }

    /**
     * AJAX : Supprimer une traduction
     */
    public function ajax_delete_translation() {
        if ( ! check_ajax_referer( 'lingua_admin_nonce', 'nonce', false ) ) wp_send_json_error( 'Erreur sécurité.' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission refusée.' );

        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translations';

        $object_id    = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $object_type  = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'page';
        $field_key    = isset( $_POST['field'] ) ? sanitize_text_field( $_POST['field'] ) : '';
        $target_lang  = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : '';

        $wpdb->delete( $table, 
            array( 'object_id' => $object_id, 'object_type' => $object_type, 'field_key' => $field_key, 'language' => $target_lang ), 
            array( '%d', '%s', '%s', '%s' ) 
        );
        wp_send_json_success( 'Traduction supprimée.' );
    }
}