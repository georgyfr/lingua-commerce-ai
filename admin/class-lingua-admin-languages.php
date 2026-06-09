<?php
/**
 * Gère la logique de la page d'administration des Langues
 * SÉCURISÉ ET VALIDÉ (Phase 3)
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin
 */

if ( ! defined( 'WPINC' ) ) { die; }

class LinguaCommerce_AI_Admin_Languages {

    private $db;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table_name = $this->db->prefix . 'lingua_languages';
        if ( ! did_action( 'lingua_languages_hooks_registered' ) ) {
            add_action( 'wp_ajax_lingua_search_installed_languages', array( $this, 'ajax_search_installed_languages' ) );
            add_action( 'wp_ajax_lingua_search_available_languages', array( $this, 'ajax_search_available_languages' ) );
            add_action( 'wp_ajax_lingua_add_language', array( $this, 'ajax_add_language' ) );
            add_action( 'wp_ajax_lingua_toggle_language_status', array( $this, 'ajax_toggle_language_status' ) );
            add_action( 'wp_ajax_lingua_set_default_language', array( $this, 'ajax_set_default_language' ) );
            add_action( 'wp_ajax_lingua_delete_language', array( $this, 'ajax_delete_language' ) );
            do_action( 'lingua_languages_hooks_registered' );
        }
    }

    public function render() {
        $installed_languages = $this->get_installed_languages();
        $all_registry = $this->get_available_languages_registry();
        $installed_codes = wp_list_pluck( $installed_languages, 'code' );
        $available_languages = array();
        foreach ( $all_registry as $code => $data ) {
            if ( ! in_array( $code, $installed_codes ) ) { $available_languages[ $code ] = $data; }
        }
        uasort( $available_languages, function( $a, $b ) { return strcmp( $a['name'], $b['name'] ); });
        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-admin-languages-display.php';
    }

    private function get_installed_languages( $search = '' ) {
        $sql = "SELECT * FROM {$this->table_name}";
        if ( ! empty( $search ) ) {
            $sql .= $this->db->prepare( " WHERE name LIKE %s OR native_name LIKE %s OR code LIKE %s", '%' . $this->db->esc_like( $search ) . '%', '%' . $this->db->esc_like( $search ) . '%', '%' . $this->db->esc_like( $search ) . '%' );
        }
        $sql .= " ORDER BY is_default DESC, name ASC";
        return $this->db->get_results( $sql );
    }

    private function verify_ajax_request() {
        if ( ! is_user_logged_in() ) wp_send_json_error( 'Session expirée ou non connecté.' );
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'lingua_admin_nonce' ) ) wp_send_json_error( 'Erreur de sécurité : Nonce invalide.' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Vous n\'avez pas les permissions nécessaires.' );
    }

    public function ajax_search_installed_languages() {
        $this->verify_ajax_request();
        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $languages = $this->get_installed_languages( $search );
        $rows_html = '';
        if ( ! empty( $languages ) ) {
            foreach ( $languages as $lang ) {
                $is_active = (bool) $lang->is_active;
                $is_default = (bool) $lang->is_default;
                $status_class = $is_active ? 'active' : 'inactive';
                $status_label = $is_active ? 'Actif' : 'Inactif';
                $status_color = $is_active ? 'green' : 'red';
                $country_code = strtolower( substr( $lang->code, -2 ) );
                $flag_img = '<img src="https://flagcdn.com/w40/' . $country_code . '.png" class="lingua-flag" alt="' . esc_attr($lang->code) . '">';
                $rows_html .= '<tr data-id="' . esc_attr( $lang->id ) . '" data-status="' . esc_attr( $status_class ) . '" data-code="' . esc_attr( $lang->code ) . '" data-name="' . esc_attr( $lang->name ) . '" data-native="' . esc_attr( $lang->native_name ) . '">';
                $rows_html .= '<td><div style="display:flex; align-items:center;">' . $flag_img . '<div style="margin-left: 10px;"><strong>' . esc_html( $lang->name ) . '</strong><div style="font-size: 12px; color: #666;">' . esc_html( $lang->native_name ) . '</div></div></div></td>';
                $rows_html .= '<td><code>' . esc_html( $lang->code ) . '</code></td>';
                $rows_html .= '<td>' . esc_html( $lang->locale ) . '</td>';
                $rows_html .= '<td><span class="lingua-badge lingua-badge-' . esc_attr( $status_color ) . '">' . esc_html( $status_label ) . '</span></td>';
                $rows_html .= '<td>';
                if ( $is_default ) { $rows_html .= '<span class="lingua-badge lingua-badge-blue">Source</span>'; }
                else { $rows_html .= '<button class="button button-small lingua-set-default" data-id="' . esc_attr( $lang->id ) . '">Définir Source</button>'; }
                $rows_html .= '</td><td style="text-align: right;">';
                $rows_html .= '<label class="switch"><input type="checkbox" class="lingua-toggle-status" data-id="' . esc_attr( $lang->id ) . '" ' . checked( $is_active, true, false ) . ' ' . disabled( $is_default, true, false ) . '><span class="slider round"></span></label>';
                if ( ! $is_default ) { $rows_html .= '<button class="button button-small lingua-delete-lang" data-id="' . esc_attr( $lang->id ) . '" title="Supprimer" style="color: #a00; margin-left: 10px; border-color: transparent; background: transparent; box-shadow: none;"><span class="dashicons dashicons-trash"></span></button>'; }
                else { $rows_html .= '<span style="display:inline-block; width: 30px; margin-left: 10px;"></span>'; }
                $rows_html .= '</td></tr>';
            }
        } else { $rows_html .= '<tr><td colspan="6" style="text-align: center; padding: 20px;">Aucune langue trouvée.</td></tr>'; }
        wp_send_json_success( array( 'html' => $rows_html ) );
    }

    public function ajax_search_available_languages() {
        $this->verify_ajax_request();
        $search = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
        $available = $this->get_available_languages_registry();
        $installed_codes = $this->get_installed_codes();
        $results = array();
        foreach ( $available as $code => $data ) {
            if ( ! empty( $search ) ) { if ( stripos( $data['name'], $search ) === false && stripos( $code, $search ) === false ) continue; }
            if ( in_array( $code, $installed_codes ) ) continue;
            $results[] = array( 'code' => $code, 'name' => $data['name'], 'native_name' => $data['native_name'], 'locale' => isset( $data['locale'] ) ? $data['locale'] : $code );
        }
        wp_send_json_success( $results );
    }

    public function ajax_add_language() {
        $this->verify_ajax_request();
        $code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : '';
        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $native_name = isset( $_POST['native_name'] ) ? sanitize_text_field( $_POST['native_name'] ) : '';
        $locale = isset( $_POST['locale'] ) ? sanitize_text_field( $_POST['locale'] ) : $code;
        if ( empty( $code ) || strlen( $code ) > 10 ) wp_send_json_error( 'Code langue invalide.' );
        $exists = $this->db->get_var( $this->db->prepare( "SELECT id FROM {$this->table_name} WHERE code = %s", $code ) );
        if ( $exists ) wp_send_json_error( 'Cette langue est déjà installée.' );
        $result = $this->db->insert( $this->table_name, array( 'code' => $code, 'name' => $name, 'native_name' => $native_name, 'locale' => $locale, 'is_active' => 0, 'is_default' => 0 ), array( '%s', '%s', '%s', '%s', '%d', '%d' ) );
        if ( $result ) wp_send_json_success( 'Langue ajoutée avec succès' );
        else wp_send_json_error( 'Erreur lors de l\'ajout en base de données.' );
    }

    public function ajax_toggle_language_status() {
        $this->verify_ajax_request();
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $status = isset( $_POST['status'] ) ? intval( $_POST['status'] ) : 0;
        if ( $id <= 0 ) wp_send_json_error( 'ID invalide.' );
        if ( ! in_array( $status, array( 0, 1 ) ) ) wp_send_json_error( 'Statut invalide.' );
        $lang = $this->db->get_row( $this->db->prepare( "SELECT is_default FROM {$this->table_name} WHERE id = %d", $id ) );
        if ( ! $lang ) wp_send_json_error( 'Langue introuvable.' );
        if ( $lang->is_default && $status == 0 ) wp_send_json_error( 'Impossible de désactiver la langue source.' );
        $this->db->update( $this->table_name, array( 'is_active' => $status ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
        wp_send_json_success( 'Statut mis à jour' );
    }

    public function ajax_set_default_language() {
        $this->verify_ajax_request();
        $input = isset( $_POST['id'] ) ? $_POST['id'] : 0;
        $target_lang = null;
        if ( is_numeric( $input ) && intval( $input ) > 0 ) { $id = intval( $input ); $target_lang = $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) ); }
        else { $code = sanitize_text_field( $input ); $target_lang = $this->db->get_row( $this->db->prepare( "SELECT * FROM {$this->table_name} WHERE code = %s", $code ) ); }
        if ( ! $target_lang ) wp_send_json_error( 'Langue introuvable dans la base de données.' );
        $this->db->update( $this->table_name, array( 'is_default' => 0 ), array( 'is_default' => 1 ), array( '%d' ), array( '%d' ) );
        $updated = $this->db->update( $this->table_name, array( 'is_default' => 1, 'is_active' => 1 ), array( 'id' => $target_lang->id ), array( '%d', '%d' ), array( '%d' ) );
        if ( false === $updated ) wp_send_json_error( 'Erreur base de données lors de la mise à jour.' );
        delete_transient( 'lingua_default_language' );
        wp_cache_delete( 'lingua_languages', 'lingua' );
        wp_send_json_success( array( 'message' => 'Langue source définie avec succès.', 'lang_name' => $target_lang->native_name, 'lang_id' => $target_lang->id, 'lang_code' => $target_lang->code ) );
    }

    public function ajax_delete_language() {
        $this->verify_ajax_request();
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( $id <= 0 ) wp_send_json_error( 'ID invalide.' );
        $lang = $this->db->get_row( $this->db->prepare( "SELECT is_default, code FROM {$this->table_name} WHERE id = %d", $id ) );
        if ( ! $lang ) wp_send_json_error( 'Langue introuvable.' );
        if ( $lang->is_default ) wp_send_json_error( 'Impossible de supprimer la langue source.' );
        $this->db->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
        wp_send_json_success( 'Langue supprimée' );
    }

    private function get_installed_codes() { $results = $this->db->get_col( "SELECT code FROM {$this->table_name}" ); return $results ? $results : array(); }
    private function get_available_languages_registry() { if ( ! class_exists( 'LinguaCommerce_Language_Registry' ) ) { require_once plugin_dir_path( __FILE__ ) . 'includes/class-lingua-language-registry.php'; } return LinguaCommerce_Language_Registry::get_all(); }
}
