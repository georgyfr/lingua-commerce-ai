<?php
/**
 * Class Lingua_Admin_AI
 * 
 * Gère l'administration et les appels AJAX pour les moteurs IA.
 *
 * @package LinguaCommerce_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Quitte si accès direct
}

class Lingua_Admin_AI {

    /**
     * Constructeur : Initialisation des hooks AJAX
     */
    public function __construct() {
        // Hook pour sauvegarder un moteur
        add_action( 'wp_ajax_lingua_save_engine', array( $this, 'ajax_save_engine' ) );
        // Hook pour tester un moteur
        add_action( 'wp_ajax_lingua_test_engine', array( $this, 'ajax_test_engine' ) );
        // Hook pour traduire un champ spécifique
        add_action( 'wp_ajax_lingua_translate_field', array( $this, 'ajax_translate_field' ) );
        add_action( 'wp_ajax_lingua_save_ai_settings', array( $this, 'ajax_save_ai_settings' ) );
        add_action( 'wp_ajax_lingua_get_queue_items', array( $this, 'ajax_get_queue_items' ) );
        add_action( 'wp_ajax_lingua_delete_queue_item', array( $this, 'ajax_delete_queue_item' ) );
        add_action( 'wp_ajax_lingua_get_logs', array( $this, 'ajax_get_logs' ) );
        add_action( 'wp_ajax_lingua_clear_logs', array( $this, 'ajax_clear_logs' ) );
    }

        /**
     * AJAX : Traduire un champ spécifique via IA
     */
    public function ajax_translate_field() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission non accordée.' );
        }

        $text_source   = isset( $_POST['text'] ) ? wp_kses_post( $_POST['text'] ) : ''; // Accepte HTML
        $target_lang   = isset( $_POST['target_lang'] ) ? sanitize_text_field( $_POST['target_lang'] ) : 'en_US';

        if ( empty( $text_source ) ) {
            wp_send_json_error( 'Texte vide.' );
        }

        // Récupérer la configuration
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_ai_engines';
        
        $requested_engine = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : '';
        
        if ( ! empty( $requested_engine ) ) {
             $engine_config = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE engine_name = %s AND status = 'active' LIMIT 1", $requested_engine ) );
        } else {
             $engine_config = $wpdb->get_row( "SELECT * FROM $table WHERE status = 'active' LIMIT 1" );
        }

        if ( ! $engine_config || empty( $engine_config->api_key ) ) {
            wp_send_json_error( 'Moteur non configuré ou inactif.' );
        }

        $engine_name = $engine_config->engine_name;
        $api_key     = $engine_config->api_key;
        $settings    = maybe_unserialize( $engine_config->settings );

        // --- LOGIQUE DEEPL ---
        if ( $engine_name === 'deepl' ) {
            $lang_map = array( 'fr_FR' => 'FR', 'fr_CA' => 'FR', 'en_US' => 'EN-US', 'en_GB' => 'EN-GB', 'es_ES' => 'ES', 'pt_BR' => 'PT-BR', 'pt_PT' => 'PT-PT', 'de_DE' => 'DE', 'it_IT' => 'IT', 'zh_CN' => 'ZH', 'ja_JP' => 'JA', 'ru_RU' => 'RU' );
            $deepl_lang = isset( $lang_map[ $target_lang ] ) ? $lang_map[ $target_lang ] : strtoupper( substr( $target_lang, 0, 2 ) );
            $url = 'https://api-free.deepl.com/v2/translate';
            $body = array( 'text' => $text_source, 'target_lang' => $deepl_lang, 'tag_handling' => 'html' );
            $response = wp_remote_post( $url, array( 'method' => 'POST', 'headers' => array( 'Authorization' => 'DeepL-Auth-Key ' . $api_key, 'Content-Type' => 'application/x-www-form-urlencoded' ), 'body' => http_build_query( $body ), 'timeout' => 20 ) );
            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau : ' . $response->get_error_message() );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $data['translations'][0]['text'] ) ) wp_send_json_success( array( 'translated_text' => $data['translations'][0]['text'] ) );
            else wp_send_json_error( 'Erreur DeepL.' );
        }
        
        // --- LOGIQUE YANDEX CLOUD ---
        elseif ( $engine_name === 'yandex' ) {
            $url = 'https://translate.api.cloud.yandex.net/translate/v2/translate';
            $target_code = strtolower( substr( $target_lang, 0, 2 ) );
            $body = array( 'texts' => array( $text_source ), 'targetLanguageCode' => $target_code, 'format' => 'HTML' );
            $response = wp_remote_post( $url, array( 'headers' => array( 'Authorization' => 'Api-Key ' . $api_key, 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $body ), 'timeout' => 20 ) );
            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau Yandex' );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $data['translations'][0]['text'] ) ) wp_send_json_success( array( 'translated_text' => $data['translations'][0]['text'] ) );
            else wp_send_json_error( 'Erreur Yandex.' );
        }

        // --- LOGIQUE BAIDU ---
        elseif ( $engine_name === 'baidu' ) {
            $url = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
            $app_id = $api_key;
            $secret_key = isset($settings['model_free']) ? $settings['model_free'] : '';
            if(empty($secret_key)) wp_send_json_error('Secret Key Baidu manquante.');
            $baidu_lang_map = array('en_US' => 'en', 'fr_FR' => 'fra', 'de_DE' => 'de', 'es_ES' => 'spa', 'pt_PT' => 'pt', 'pt_BR' => 'pt', 'ja_JP' => 'jp', 'zh_CN' => 'zh');
            $target_code = isset($baidu_lang_map[$target_lang]) ? $baidu_lang_map[$target_lang] : substr($target_lang, 0, 2);
            $salt = rand(10000, 99999);
            $sign = md5( $app_id . $text_source . $salt . $secret_key );
            $body = array( 'q' => $text_source, 'from' => 'auto', 'to' => $target_code, 'appid' => $app_id, 'salt' => $salt, 'sign' => $sign );
            $response = wp_remote_post( $url, array('body' => $body, 'timeout' => 20) );
            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau Baidu' );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $data['trans_result'][0]['dst'] ) ) wp_send_json_success( array( 'translated_text' => $data['trans_result'][0]['dst'] ) );
            else wp_send_json_error( 'Erreur Baidu.' );
        }

        // --- LOGIQUE MICROSOFT BING ---
        elseif ( $engine_name === 'microsoft' ) {
            $region = !empty($settings['model_paid']) ? $settings['model_paid'] : 'global';
            $url = 'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&to=' . substr($target_lang, 0, 2) . '&textType=html';
            $body = json_encode(array( array('Text' => $text_source) ));
            $response = wp_remote_post( $url, array( 'headers' => array( 'Ocp-Apim-Subscription-Key' => $api_key, 'Ocp-Apim-Subscription-Region' => $region, 'Content-Type' => 'application/json' ), 'body' => $body, 'timeout' => 20 ) );
            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau Microsoft' );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $data[0]['translations'][0]['text'] ) ) wp_send_json_success( array( 'translated_text' => $data[0]['translations'][0]['text'] ) );
            else wp_send_json_error( 'Erreur Microsoft.' );
        }

        // --- LOGIQUE GOOGLE GEMINI ---
        elseif ( $engine_name === 'google' ) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $api_key;
            // Récupération du nom de la langue
            $target_lang_name = $target_lang;
            if ( class_exists( 'LinguaCommerce_Language_Service' ) ) {
                $langs = LinguaCommerce_Language_Service::get_active_languages();
                foreach ( $langs as $l ) {
                    if ( $l->code === $target_lang ) { $target_lang_name = $l->native_name; break; }
                }
            }
            $system_prompt = "Tu es un traducteur expert. Traduis le texte HTML suivant en " . $target_lang_name . ". RÈGLES : Conserve les balises HTML, ne traduis pas les attributs. Renvoie uniquement le HTML.";
            $body = array(
                'contents' => array(
                    array( 'parts' => array( array('text' => $system_prompt . "\n\nTEXTE :\n" . $text_source) ) )
                )
            );
            $response = wp_remote_post( $url, array( 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => wp_json_encode( $body ), 'timeout' => 30 ) );
            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau Google' );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) wp_send_json_success( array( 'translated_text' => trim($data['candidates'][0]['content']['parts'][0]['text']) ) );
            else wp_send_json_error( 'Erreur Google : ' . (isset($data['error']['message']) ? $data['error']['message'] : 'Inconnue') );
        }

        // --- LOGIQUE AUTRES MOTEURS (Gpt, OpenRouter, DeepSeek, Mistral) ---
        else {
            $url = 'https://openrouter.ai/api/v1/chat/completions'; // Par défaut
            $model_name = 'meta-llama/llama-3.2-3b-instruct:free';
            $auth_header = 'Bearer ' . $api_key;

            if ( $engine_name === 'deepseek' ) {
                 $url = 'https://api.deepseek.com/chat/completions';
                 $model_name = 'deepseek-chat';
            } elseif ( $engine_name === 'mistral' ) {
                 $url = 'https://api.mistral.ai/v1/chat/completions';
                 $model_name = 'mistral-small-latest';
            } elseif ( $engine_name === 'openai' ) {
                 $url = 'https://api.openai.com/v1/chat/completions';
                 $model_name = 'gpt-3.5-turbo';
            }

            if ( ! empty( $settings['model_paid'] ) ) $model_name = $settings['model_paid'];
            elseif ( ! empty( $settings['model_free'] ) ) $model_name = $settings['model_free'];

            // Récupération du nom de la langue
            $target_lang_name = $target_lang;
            if ( class_exists( 'LinguaCommerce_Language_Service' ) ) {
                $langs = LinguaCommerce_Language_Service::get_active_languages();
                foreach ( $langs as $l ) {
                    if ( $l->code === $target_lang ) { $target_lang_name = $l->native_name; break; }
                }
            }

            // PROMPT AMÉLIORÉ POUR HTML
            $system_instruction = "Tu es un traducteur expert. Traduis le texte fourni en " . $target_lang_name . ". RÈGLES : 1. Conserve TOUTES les balises HTML telles quelles. 2. Ne traduis aucun contenu d'attributs HTML. 3. Renvoie uniquement le code HTML final avec les textes traduits, sans blocs Markdown, sans commentaires.";

            $body = array(
                'model'    => $model_name,
                'messages' => array(
                    array( 'role' => 'system', 'content' => $system_instruction ),
                    array( 'role' => 'user', 'content' => $text_source )
                )
            );

            $response = wp_remote_post( $url, array(
                'method'  => 'POST',
                'headers' => array( 'Authorization' => $auth_header, 'Content-Type' => 'application/json', 'HTTP-Referer' => site_url() ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 30
            ) );

            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau : ' . $response->get_error_message() );

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $data['choices'][0]['message']['content'] ) ) {
                wp_send_json_success( array( 'translated_text' => trim($data['choices'][0]['message']['content']) ) );
            } else {
                wp_send_json_error( 'Erreur API (' . $engine_name . ').' );
            }
        }
    }
    /**
     * AJAX : Sauvegarder un moteur IA
     */
    public function ajax_save_engine() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission non accordée.' );
        }

        $engine_name = isset( $_POST['engine_name'] ) ? sanitize_text_field( $_POST['engine_name'] ) : '';
        $api_key     = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
        $model_paid  = isset( $_POST['model_paid'] ) ? sanitize_text_field( $_POST['model_paid'] ) : '';
        $model_free  = isset( $_POST['model_free'] ) ? sanitize_text_field( $_POST['model_free'] ) : '';
        
        if ( empty( $engine_name ) ) {
            wp_send_json_error( 'Nom du moteur requis.' );
        }

        $settings_array = array(
            'model_paid' => $model_paid,
            'model_free' => $model_free
        );

        global $wpdb;
        $table = $wpdb->prefix . 'lingua_ai_engines';

        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE engine_name = %s", $engine_name ) );

        $data = array(
            'engine_name' => $engine_name,
            'api_key'     => $api_key, 
            'status'      => 'active',
            'priority'    => 10,
            'settings'    => maybe_serialize( $settings_array )
        );

        $format = array( '%s', '%s', '%s', '%d', '%s' );

        if ( $exists ) {
            $result = $wpdb->update( $table, $data, array( 'engine_name' => $engine_name ), $format, array( '%s' ) );
        } else {
            $result = $wpdb->insert( $table, $data, $format );
        }

        if ( $result === false ) {
            wp_send_json_error( 'Erreur lors de la sauvegarde en base de données.' );
        }

        wp_send_json_success( array( 'message' => 'Moteur sauvegardé avec succès.' ) );
    }

        /**
     * AJAX : Tester la connexion à un moteur IA
     */
    public function ajax_test_engine() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission non accordée.' );
        }

        $engine_name = isset( $_POST['engine_name'] ) ? sanitize_text_field( $_POST['engine_name'] ) : '';
        $api_key     = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
        $model_paid  = isset( $_POST['model_paid'] ) ? sanitize_text_field( $_POST['model_paid'] ) : '';
        $model_free  = isset( $_POST['model_free'] ) ? sanitize_text_field( $_POST['model_free'] ) : '';

        $target_lang_code = isset( $_POST['target_lang'] ) ? sanitize_text_field( $_POST['target_lang'] ) : 'en_US';
        $text_source  = isset( $_POST['text_source'] ) ? sanitize_textarea_field( $_POST['text_source'] ) : '';

        if ( empty( $engine_name ) || empty( $api_key ) ) {
            wp_send_json_error( 'Paramètres manquants.' );
        }

        if ( empty( $text_source ) ) {
            wp_send_json_error( 'Veuillez entrer du texte à traduire.' );
        }

        // --- LOGIQUE SPECIFIQUE DEEPL ---
        if ( $engine_name === 'deepl' ) {
            
            $url = 'https://api-free.deepl.com/v2/translate';

            $lang_map = array(
                'fr_FR' => 'FR', 'fr_CA' => 'FR',
                'en_US' => 'EN-US', 'en_GB' => 'EN-GB',
                'es_ES' => 'ES', 'pt_BR' => 'PT-BR', 'pt_PT' => 'PT-PT',
                'de_DE' => 'DE', 'it_IT' => 'IT',
                'zh_CN' => 'ZH', 'ja_JP' => 'JA', 'ru_RU' => 'RU'
            );

            if ( isset( $lang_map[ $target_lang_code ] ) ) {
                $deepl_lang = $lang_map[ $target_lang_code ];
            } else {
                $deepl_lang = strtoupper( substr( $target_lang_code, 0, 2 ) );
            }

            $body = array(
                'text' => $text_source,
                'target_lang' => $deepl_lang
            );

            $response = wp_remote_post( $url, array(
                'method'  => 'POST',
                'headers' => array(
                    'Authorization' => 'DeepL-Auth-Key ' . $api_key,
                    'Content-Type'  => 'application/x-www-form-urlencoded'
                ),
                'body'    => http_build_query( $body ),
                'timeout' => 20
            ) );

            if ( is_wp_error( $response ) ) {
                wp_send_json_error( 'Erreur réseau : ' . $response->get_error_message() );
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( isset( $data['message'] ) ) {
                wp_send_json_error( 'Erreur DeepL : ' . $data['message'] );
            }

            if ( isset( $data['translations'][0]['text'] ) ) {
                wp_send_json_success( array( 
                    'message' => 'Traduction DeepL réussie !',
                    'translated_text' => $data['translations'][0]['text'],
                    'model_used' => 'DeepL API' 
                ) );
            } else {
                wp_send_json_error( 'Réponse inattendue de DeepL.' );
            }

        } 
        
               // 2. YANDEX TRANSLATE (Version Cloud Moderne)
        elseif ( $engine_name === 'yandex' ) {
            // L'URL de l'API Cloud moderne
            $url = 'https://translate.api.cloud.yandex.net/translate/v2/translate';
            
            // Récupération du code langue cible (ex: 'en', 'fr')
            $target_code = strtolower( substr( $target_lang_code, 0, 2 ) );

            // Construction du corps de la requête JSON
            $body = array(
                'texts'              => array( $text_source ),
                'targetLanguageCode' => $target_code
            );

            // Appel API avec Authorization Header
            $response = wp_remote_post( $url, array(
                'headers' => array(
                    'Authorization' => 'Api-Key ' . $api_key, // Format requis par Yandex Cloud
                    'Content-Type'  => 'application/json'
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 20
            ) );

            if ( is_wp_error( $response ) ) {
                wp_send_json_error( 'Erreur réseau Yandex : ' . $response->get_error_message() );
            }

            $code = wp_remote_retrieve_response_code( $response );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            // Analyse de la réponse Cloud
            if ( $code === 200 && isset( $data['translations'][0]['text'] ) ) {
                wp_send_json_success( array( 
                    'message' => 'Traduction Yandex Cloud réussie !',
                    'translated_text' => $data['translations'][0]['text'],
                    'model_used' => 'Yandex Cloud'
                ) );
            } else {
                // Gestion des erreurs précises
                $error_msg = 'Erreur inconnue';
                if ( isset( $data['message'] ) ) $error_msg = $data['message'];
                if ( isset( $data['error']['message'] ) ) $error_msg = $data['error']['message'];
                
                wp_send_json_error( 'Erreur Yandex Cloud : ' . $error_msg . ' (Code HTTP: ' . $code . ')' );
            }
        }
        // 3. BAIDU TRANSLATE
        elseif ( $engine_name === 'baidu' ) {
            $url = 'https://fanyi-api.baidu.com/api/trans/vip/translate';
            
            $app_id = $api_key; // On a stocké l'App ID dans api_key
            $secret_key = $model_free; // On a stocké le secret dans model_free via le formulaire
            
            if(empty($secret_key)) wp_send_json_error('Secret Key Baidu manquante.');

            // Mapping codes langue Baidu (spécifique)
            $baidu_lang_map = array('en' => 'en', 'fr' => 'fra', 'de' => 'de', 'es' => 'spa', 'pt' => 'pt', 'jp' => 'jp', 'zh' => 'zh');
            $target_code = substr($target_lang_code, 0, 2);
            $target_baidu = isset($baidu_lang_map[$target_code]) ? $baidu_lang_map[$target_code] : $target_code;
            $source_baidu = 'auto'; // Détection auto pour simplifier

            $salt = rand(10000, 99999);
            $sign = md5( $app_id . $text_source . $salt . $secret_key );

            $body = array(
                'q'     => $text_source,
                'from'  => $source_baidu,
                'to'    => $target_baidu,
                'appid' => $app_id,
                'salt'  => $salt,
                'sign'  => $sign
            );

            $response = wp_remote_get( $url . '?' . http_build_query($body), array('timeout' => 20) );
            
            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau Baidu' );

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $data['trans_result'][0]['dst'] ) ) {
                wp_send_json_success( array( 
                    'message' => 'Traduction Baidu réussie !',
                    'translated_text' => $data['trans_result'][0]['dst'],
                    'model_used' => 'Baidu API'
                ) );
            } else {
                wp_send_json_error( 'Erreur Baidu : ' . (isset($data['error_msg']) ? $data['error_msg'] : 'Inconnue') );
            }
        }

        // 4. MICROSOFT BING
        elseif ( $engine_name === 'microsoft' ) {
            $region = !empty($model_paid) ? $model_paid : 'global'; // Région stockée dans model_paid
            $url = 'https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&to=' . substr($target_lang_code, 0, 2);

            $body = json_encode(array(
                array('Text' => $text_source)
            ));

            $response = wp_remote_post( $url, array(
                'headers' => array(
                    'Ocp-Apim-Subscription-Key' => $api_key,
                    'Ocp-Apim-Subscription-Region' => $region,
                    'Content-Type' => 'application/json'
                ),
                'body'    => $body,
                'timeout' => 20
            ) );

            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau Microsoft' );

            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            
            if ( isset( $data[0]['translations'][0]['text'] ) ) {
                wp_send_json_success( array( 
                    'message' => 'Traduction Microsoft réussie !',
                    'translated_text' => $data[0]['translations'][0]['text'],
                    'model_used' => 'Azure Translator'
                ) );
            } else {
                $error_msg = 'Erreur inconnue';
                if(isset($data['error']['message'])) $error_msg = $data['error']['message'];
                wp_send_json_error( 'Erreur Microsoft : ' . $error_msg );
            }
        }

        // 5. GOOGLE CLOUD (Gemini)
        elseif ( $engine_name === 'google' ) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key=' . $api_key;
            $body = array( 'contents' => array( array( 'parts' => array( array('text' => "Tu es un traducteur professionnel. Traduis exactement ce texte en " . $target_lang_code . " sans commentaire : \n\n" . $text_source) ) ) ) );
            $response = wp_remote_post( $url, array( 'headers' => array('Content-Type' => 'application/json'), 'body' => wp_json_encode( $body ), 'timeout' => 20 ) );
            if ( is_wp_error( $response ) ) wp_send_json_error( 'Erreur réseau Google' );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
                wp_send_json_success( array( 'message' => 'Traduction Google réussie !', 'translated_text' => trim($data['candidates'][0]['content']['parts'][0]['text']), 'model_used' => 'Gemini 1.5' ) );
            } else {
                wp_send_json_error( 'Erreur Google : ' . (isset($data['error']['message']) ? $data['error']['message'] : 'Inconnue') );
            }
        }

        // --- LOGIQUE AUTRES MOTEURS (Chat Completions) ---
        else {
            
            $url        = '';
            $model_name = '';
            $auth_header = '';

            // Détection du moteur et configuration
            if ( $engine_name === 'openrouter' ) {
                $url = 'https://openrouter.ai/api/v1/chat/completions';
                
                // Sécurité : Vérifier que la clé commence par sk-or-
                if ( strpos( $api_key, 'sk-or-' ) !== 0 ) {
                     wp_send_json_error( 'Clé OpenRouter invalide. Elle doit commencer par "sk-or-"' );
                }

                // Choix du modèle : On priorise le modèle payant, sinon le gratuit
                if ( ! empty( $model_paid ) ) {
                    $model_name = $model_paid; 
                } elseif ( ! empty( $model_free ) ) {
                    $model_name = $model_free; 
                } else {
                    // Mise à jour du modèle gratuit par défaut (Llama 3.2 remplace 3.0)
                    $model_name = 'meta-llama/llama-3.2-3b-instruct:free'; 
                }
                
                $auth_header = 'Bearer ' . $api_key;

            } elseif ( $engine_name === 'deepseek' ) {
                $url = 'https://api.deepseek.com/chat/completions';
                $model_name = 'deepseek-chat';
                $auth_header = 'Bearer ' . $api_key;

            } elseif ( $engine_name === 'mistral' ) {
                $url = 'https://api.mistral.ai/v1/chat/completions';
                $model_name = 'mistral-small-latest';
                $auth_header = 'Bearer ' . $api_key;

            } elseif ( $engine_name === 'openai' ) {
                $url = 'https://api.openai.com/v1/chat/completions';
                $model_name = 'gpt-3.5-turbo';
                $auth_header = 'Bearer ' . $api_key;
            } else {
                wp_send_json_error( 'Moteur non reconnu pour le test.' );
            }

            $target_lang_name = $target_lang_code;
            if ( class_exists( 'LinguaCommerce_Language_Service' ) ) {
                $lang_obj = LinguaCommerce_Language_Service::get_active_languages();
                foreach ( $lang_obj as $l ) {
                    if ( $l->code === $target_lang_code ) {
                        $target_lang_name = $l->native_name;
                        break;
                    }
                }
            }

            $body = array(
                'model'    => $model_name,
                'messages' => array(
                    array(
                        'role'    => 'system',
                        'content' => 'Tu es un traducteur professionnel. Traduis uniquement le texte fourni sans ajouter de commentaire.'
                    ),
                    array(
                        'role'    => 'user',
                        'content' => 'Traduis ce texte en ' . $target_lang_name . ' : ' . $text_source
                    )
                )
            );

            $response = wp_remote_post( $url, array(
                'method'  => 'POST',
                'headers' => array(
                    'Authorization' => $auth_header,
                    'Content-Type'  => 'application/json',
                    'HTTP-Referer'  => site_url(), // Requis par OpenRouter
                    'X-Title'       => 'LinguaCommerce AI' // Nom de l'application
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 30
            ) );

            if ( is_wp_error( $response ) ) {
                wp_send_json_error( 'Erreur réseau WordPress: ' . $response->get_error_message() );
            }

            $body_response = wp_remote_retrieve_body( $response );
            $data = json_decode( $body_response, true );

            // Gestion améliorée des erreurs API
            if ( isset( $data['error'] ) ) {
                $error_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Erreur inconnue';
                
                // Message spécifique pour "User not found"
                if ( strpos($error_msg, 'User not found') !== false ) {
                     wp_send_json_error( 'Erreur API : Clé API invalide ou compte introuvable. Vérifiez votre clé sur OpenRouter.' );
                }

                wp_send_json_error( 'Erreur API: ' . $error_msg );
            }

            if ( isset( $data['choices'][0]['message']['content'] ) ) {
                wp_send_json_success( array( 
                    'message' => 'Traduction réussie !',
                    'translated_text' => $data['choices'][0]['message']['content'],
                    'model_used' => $model_name 
                ) );
            } else {
                wp_send_json_error( 'Réponse inattendue de l\'API.' );
            }
        }
    }
              /**
     * Affiche la page IA (Charge la vue et les données nécessaires)
     */
    public function render() {
        // 1. Services de langue (CORRECTION DU CHEMIN)
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            // Utilisation de la constante définie dans le fichier principal du plugin
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-language-service.php';
        }
        $active_languages = LinguaCommerce_Language_Service::get_active_languages();
        $default_lang_obj = LinguaCommerce_Language_Service::get_default_language();

        // 2. Récupération des moteurs IA (Pour l'onglet "Moteurs")
        global $wpdb;
        $table = $wpdb->prefix . 'lingua_ai_engines';
        $engines_raw = $wpdb->get_results( "SELECT * FROM $table" );
        
        $engines = array();
        foreach ( $engines_raw as $e ) {
            $engines[ $e->engine_name ] = array(
                'api_key' => $e->api_key,
                'status' => $e->status,
                'settings' => maybe_unserialize( $e->settings )
            );
        }

        // 3. Récupération des paramètres sauvegardés
        $settings = get_option( 'lingua_commerce_ai_settings', array() );

        // 4. Définition des types de contenu (Synchronisé avec la page Traductions)
        $content_types = array(
            'page'            => 'Pages',
            'post'            => 'Articles',
            'product'         => 'Produits WooCommerce',
            'product_cat'     => 'Catégories Produits',
            'product_tag'     => 'Étiquettes Produits',
            'attachment'      => 'Images & Médias',
            'product_attributes' => 'Attributs Produits'
        );

        // 5. Chargement de la vue
        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-admin-ai-display.php';
    }
        /**
     * AJAX : Sauvegarder les paramètres généraux de l'IA
     */
    public function ajax_save_ai_settings() {
        // Vérification de sécurité
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission non accordée.' );
        }

        // Récupérer les options actuelles
        $options = get_option( 'lingua_commerce_ai_settings', array() );

        // Mise à jour des valeurs
        // On utilise directement les clés, la sanitization se fera via le hook 'sanitize_option' de WordPress
        // qui appellera notre fonction sanitize_settings corrigée ci-dessus.
        
        $options['ai_tone']             = isset( $_POST['ai_tone'] ) ? sanitize_text_field( $_POST['ai_tone'] ) : 'neutral';
        $options['default_engine']      = isset( $_POST['default_engine'] ) ? sanitize_text_field( $_POST['default_engine'] ) : 'openrouter';
        $options['custom_instructions'] = isset( $_POST['custom_instructions'] ) ? sanitize_textarea_field( $_POST['custom_instructions'] ) : '';
        
        // CORRECTION CASE À COCHER :
        // Si le JS envoie '1', on met 1. Sinon on met 0.
        $options['auto_validate']       = ( isset( $_POST['auto_validate'] ) && $_POST['auto_validate'] == '1' ) ? 1 : 0;

        // Sauvegarde en base de données
        update_option( 'lingua_commerce_ai_settings', $options );

        wp_send_json_success( 'Paramètres sauvegardés avec succès.' );
    }
    
        /**
     * AJAX : Récupérer les éléments de la file d'attente
     */
    public function ajax_get_queue_items() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission refusée.' );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translation_queue';
        
                // Filtres
        $status_filter = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'all';
        $type_filter   = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'all';
        
        $where = "1=1";
        if ( $status_filter !== 'all' ) {
            $where .= $wpdb->prepare( " AND status = %s", $status_filter );
        }
        // AJOUT : Filtre par type de contenu
        if ( $type_filter !== 'all' ) {
            $where .= $wpdb->prepare( " AND object_type = %s", $type_filter );
        }

        $items = $wpdb->get_results( "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT 50" );
        
        $rows_html = '';
        $counts = array( 'pending' => 0, 'processing' => 0, 'error' => 0 );

        if ( $items ) {
            foreach ( $items as $item ) {
                // Compteurs pour le tableau de bord
                if ( isset( $counts[ $item->status ] ) ) $counts[ $item->status ]++;

                // Récupération du titre du contenu (Post/Terme)
                $title = "ID: " . $item->object_id;
                if ( in_array( $item->object_type, array( 'post', 'page', 'product' ) ) ) {
                    $title = get_the_title( $item->object_id );
                } elseif ( taxonomy_exists( $item->object_type ) ) {
                    $term = get_term( $item->object_id );
                    if ( $term && ! is_wp_error( $term ) ) $title = $term->name;
                }

                // Badges Statut
                $status_class = 'status-pending';
                $status_label = 'En attente';
                $action_btn = '<button class="button button-small queue-btn-process" data-id="'.$item->id.'" title="Traiter">▶️</button> <button class="button button-small queue-btn-delete" data-id="'.$item->id.'" style="color:#b32d2e;" title="Supprimer">❌</button>';

                if ( $item->status === 'processing' ) {
                    $status_class = 'status-processing';
                    $status_label = 'En cours';
                    $action_btn = '<button class="button button-small" disabled>⏳</button>';
                } elseif ( $item->status === 'error' ) {
                    $status_class = 'status-error';
                    $status_label = 'Erreur';
                    $action_btn = '<button class="button button-small queue-btn-retry" data-id="'.$item->id.'" title="Réessayer">🔄</button> <button class="button button-small queue-btn-delete" data-id="'.$item->id.'" style="color:#b32d2e;" title="Supprimer">❌</button>';
                }

                // Drapeaux Langue
                $flag_from = '<img src="https://flagcdn.com/16x12/'.strtolower(substr($item->source_language, -2)).'.png" style="vertical-align:middle;">';
                $flag_to   = '<img src="https://flagcdn.com/16x12/'.strtolower(substr($item->target_language, -2)).'.png" style="vertical-align:middle;">';

                $rows_html .= '<tr>';
                $rows_html .= '<th scope="row" class="check-column"><input type="checkbox" name="item[]" value="'.$item->id.'"></th>';
                $rows_html .= '<td class="column-primary"><strong>'.esc_html($title).'</strong><div class="row-actions"><span class="type">'.esc_html($item->object_type).'</span></div></td>';
                $rows_html .= '<td>'.esc_html($item->field_key).'</td>';
                $rows_html .= '<td>'.$flag_from.' ➔ '.$flag_to.'</td>';
                $rows_html .= '<td>'.esc_html($item->ai_engine).'</td>';
                $rows_html .= '<td><span class="lingua-status-badge '.$status_class.'">'.$status_label.'</span></td>';
                $rows_html .= '<td>'.human_time_diff(strtotime($item->created_at), current_time('timestamp')) . ' min</td>';
                $rows_html .= '<td>'.$action_btn.'</td>';
                $rows_html .= '</tr>';
            }
        } else {
            $rows_html = '<tr><td colspan="8" style="text-align:center; padding: 20px;">Aucune tâche dans la file.</td></tr>';
        }

        wp_send_json_success( array( 'html' => $rows_html, 'counts' => $counts ) );
    }

    /**
     * AJAX : Supprimer une tâche de la file
     */
    public function ajax_delete_queue_item() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission refusée.' );
        }

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( ! $id ) wp_send_json_error( 'ID invalide.' );

        global $wpdb;
        $table = $wpdb->prefix . 'lingua_translation_queue';
        $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

        wp_send_json_success( 'Tâche supprimée.' );
    }
    
        /**
     * AJAX : Récupérer les logs
     */
    public function ajax_get_logs() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission refusée.' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'lingua_logs';
        
        // Création de la table si elle n'existe pas (auto-réparation)
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                timestamp datetime DEFAULT CURRENT_TIMESTAMP,
                action_type varchar(50) NOT NULL,
                object_type varchar(20),
                object_id bigint(20),
                engine varchar(50),
                tokens_input int(11) DEFAULT 0,
                tokens_output int(11) DEFAULT 0,
                cost_estimate decimal(10,6) DEFAULT 0.000000,
                status varchar(20) DEFAULT 'success',
                details text,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

        // Récupération des logs
        $items = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 100" );
        
        // Calcul des stats
        $total_tokens = $wpdb->get_var( "SELECT SUM(tokens_input + tokens_output) FROM $table_name" );
        $total_cost = $wpdb->get_var( "SELECT SUM(cost_estimate) FROM $table_name" );
        $error_count = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name WHERE status = 'error'" );

        $rows_html = '';
        if ( $items ) {
            foreach ( $items as $item ) {
                $status_class = ($item->status == 'error') ? 'status-error' : 'status-done';
                $status_label = ($item->status == 'error') ? 'Échec' : 'Succès';
                
                $rows_html .= '<tr>';
                $rows_html .= '<td>' . date('d/m/Y H:i', strtotime($item->timestamp)) . '</td>';
                $rows_html .= '<td><strong>' . esc_html($item->action_type) . '</strong><br><small style="color:#666;">' . esc_html($item->object_type) . ' #' . $item->object_id . '</small></td>';
                $rows_html .= '<td>' . esc_html($item->engine) . '</td>';
                $rows_html .= '<td>' . number_format($item->tokens_input + $item->tokens_output) . '</td>';
                $rows_html .= '<td>' . number_format($item->cost_estimate, 4) . ' $</td>';
                $rows_html .= '<td><span class="lingua-status-badge ' . $status_class . '">' . $status_label . '</span></td>';
                $rows_html .= '<td><button class="button button-small view-log-details" data-details="' . esc_attr($item->details) . '">Voir</button></td>';
                $rows_html .= '</tr>';
            }
        } else {
            $rows_html = '<tr><td colspan="7" style="text-align:center; padding: 20px;">Aucune activité enregistrée pour le moment.</td></tr>';
        }

        wp_send_json_success( array( 
            'html' => $rows_html, 
            'stats' => array(
                'tokens' => $total_tokens ? $total_tokens : 0,
                'cost' => $total_cost ? $total_cost : 0,
                'errors' => $error_count ? $error_count : 0
            )
        ) );
    }

    /**
     * AJAX : Vider les logs
     */
    public function ajax_clear_logs() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission refusée.' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'lingua_logs';
        $wpdb->query( "TRUNCATE TABLE $table_name" );

        wp_send_json_success( 'Logs effacés.' );
    }
}

