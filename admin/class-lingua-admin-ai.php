<?php
/**
 * Class Lingua_Admin_AI
 *
 * Gère l'administration et les appels AJAX pour les moteurs IA.
 *
 * @package LinguaCommerce_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lingua_Admin_AI {

    /**
     * Instance unique (singleton)
     *
     * @var Lingua_Admin_AI|null
     */
    private static $instance = null;

    /**
     * Table des moteurs IA
     *
     * @var string
     */
    private $table_engines;

    /**
     * Table de la file d'attente
     *
     * @var string
     */
    private $table_queue;

    /**
     * Table des logs
     *
     * @var string
     */
    private $table_logs;

    /**
     * Constructeur - Enregistrement des hooks AJAX
     */
    public function __construct() {
        global $wpdb;
        $this->table_engines = $wpdb->prefix . 'lingua_ai_engines';
        $this->table_queue   = $wpdb->prefix . 'lingua_translation_queue';
        $this->table_logs    = $wpdb->prefix . 'lingua_logs';

        // S'assurer que les colonnes manquantes existent dans la table des moteurs
        $this->ensure_engine_columns();

        // Enregistrement unique des hooks AJAX
        if ( ! did_action( 'lingua_ai_hooks_registered' ) ) {
            add_action( 'wp_ajax_lingua_save_engine', array( $this, 'ajax_save_engine' ) );
            add_action( 'wp_ajax_lingua_test_engine', array( $this, 'ajax_test_engine' ) );
            add_action( 'wp_ajax_lingua_translate_field', array( $this, 'ajax_translate_field' ) );
            add_action( 'wp_ajax_lingua_save_ai_settings', array( $this, 'ajax_save_ai_settings' ) );
            add_action( 'wp_ajax_lingua_get_queue_items', array( $this, 'ajax_get_queue_items' ) );
            add_action( 'wp_ajax_lingua_delete_queue_item', array( $this, 'ajax_delete_queue_item' ) );
            add_action( 'wp_ajax_lingua_get_logs', array( $this, 'ajax_get_logs' ) );
            add_action( 'wp_ajax_lingua_clear_logs', array( $this, 'ajax_clear_logs' ) );
            // Hooks manquants utilisés par le JS de la page IA
            add_action( 'wp_ajax_lingua_test_api_key', array( $this, 'ajax_test_api_key' ) );
            add_action( 'wp_ajax_lingua_set_primary_engine', array( $this, 'ajax_set_primary_engine' ) );
            add_action( 'wp_ajax_lingua_save_api_keys', array( $this, 'ajax_save_api_keys' ) );
            add_action( 'wp_ajax_lingua_trigger_queue', array( $this, 'ajax_trigger_queue' ) );
            add_action( 'wp_ajax_lingua_retry_failed', array( $this, 'ajax_retry_failed' ) );
            add_action( 'wp_ajax_lingua_clear_queue', array( $this, 'ajax_clear_queue' ) );
            add_action( 'wp_ajax_lingua_test_translate', array( $this, 'ajax_test_translate' ) );
            add_action( 'wp_ajax_lingua_refresh_nonce', array( $this, 'ajax_refresh_nonce' ) );
            do_action( 'lingua_ai_hooks_registered' );
        }
    }

    /**
     * Vérifie et ajoute les colonnes engine_config, created_at, last_updated
     * si elles n'existent pas dans la table lingua_ai_engines.
     * Cela assure la compatibilité sans obliger l'utilisateur à réactiver le plugin.
     */
    private function ensure_engine_columns() {
        global $wpdb;
        $table = $this->table_engines;

        // Vérifier si la table existe
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
            return;
        }

        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );

        if ( ! in_array( 'engine_config', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN engine_config longtext AFTER settings" );
        }
        if ( ! in_array( 'created_at', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL" );
        }
        if ( ! in_array( 'last_updated', $columns ) ) {
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL" );
        }
    }

    /**
     * Retourne l'instance unique
     *
     * @return Lingua_Admin_AI
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // =========================================================================
    // RENDU DE LA PAGE
    // =========================================================================

    /**
     * Affiche la page IA & Automatisation
     * Charge les langues actives, les moteurs, les réglages et les types de contenu
     */
    public function render() {
        global $wpdb;

        // 1. Charger le service des langues
        if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
            require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-language-service.php';
        }

        // 2. Récupérer les langues actives
        $active_languages = LinguaCommerce_Language_Service::get_active_languages();
        $default_language = LinguaCommerce_Language_Service::get_default_language();

        // 3. Récupérer les moteurs IA configurés
        $engines = $wpdb->get_results(
            "SELECT * FROM {$this->table_engines} ORDER BY engine_name ASC"
        );

        // 4. Récupérer les réglages IA
        $ai_settings = get_option( 'lingua_commerce_ai_settings', array() );
        $ai_tone           = isset( $ai_settings['ai_tone'] ) ? $ai_settings['ai_tone'] : 'professional';
        $default_engine    = isset( $ai_settings['default_engine'] ) ? $ai_settings['default_engine'] : '';
        $custom_instructions = isset( $ai_settings['custom_instructions'] ) ? $ai_settings['custom_instructions'] : '';
        $auto_validate     = isset( $ai_settings['auto_validate'] ) ? (bool) $ai_settings['auto_validate'] : false;

        // 5. Récupérer les types de contenu actifs
        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        $content_types = array(
            'post'    => __( 'Articles', 'lingua-commerce-ai' ),
            'page'    => __( 'Pages', 'lingua-commerce-ai' ),
            'product' => __( 'Produits', 'lingua-commerce-ai' ),
        );
        $active_post_types = isset( $settings['active_post_types'] ) ? $settings['active_post_types'] : array( 'post', 'page', 'product' );

        // 6. Statistiques de la file d'attente
        $queue_stats = array( 'pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0 );
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_queue}'" ) === $this->table_queue ) {
            $queue_stats['pending']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_queue} WHERE status = 'pending'" );
            $queue_stats['processing'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_queue} WHERE status = 'processing'" );
            $queue_stats['completed'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_queue} WHERE status = 'completed'" );
            $queue_stats['failed']    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_queue} WHERE status = 'failed'" );
        }

        // 7. Inclure la vue partielle
        require_once plugin_dir_path( __FILE__ ) . 'partials/lingua-admin-ai-display.php';
    }

    // =========================================================================
    // TRADUCTION D'UN CHAMP VIA IA
    // =========================================================================

    /**
     * Traduit un champ via le moteur IA sélectionné
     * Supporte : deepl, yandex, baidu, microsoft, google (Gemini), et LLM (openrouter, deepseek, mistral, openai)
     */
    public function ajax_translate_field() {
        // Vérification de sécurité
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        // Récupération et nettoyage des paramètres
        $text       = isset( $_POST['text'] ) ? wp_kses_post( stripslashes( $_POST['text'] ) ) : '';
        $source_lang = isset( $_POST['source_lang'] ) ? sanitize_text_field( $_POST['source_lang'] ) : '';
        $target_lang = isset( $_POST['target_lang'] ) ? sanitize_text_field( $_POST['target_lang'] ) : '';
        $engine_slug = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : '';
        $field_key   = isset( $_POST['field_key'] ) ? sanitize_text_field( $_POST['field_key'] ) : '';
        $object_id   = isset( $_POST['object_id'] ) ? sanitize_text_field( $_POST['object_id'] ) : '';
        $object_type = isset( $_POST['object_type'] ) ? sanitize_text_field( $_POST['object_type'] ) : '';

        if ( empty( $text ) || empty( $source_lang ) || empty( $target_lang ) || empty( $engine_slug ) ) {
            wp_send_json_error( array( 'message' => __( 'Paramètres manquants pour la traduction.', 'lingua-commerce-ai' ) ) );
        }

        // Récupération de la configuration du moteur
        global $wpdb;
        $engine = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_engines} WHERE engine_name = %s AND status = 'active'",
                $engine_slug
            )
        );

        if ( ! $engine ) {
            wp_send_json_error( array( 'message' => __( 'Moteur IA introuvable ou inactif.', 'lingua-commerce-ai' ) ) );
        }

        // engine_config (nouvelle colonne) ou settings (ancienne colonne) comme fallback
        $config_json = isset( $engine->engine_config ) ? $engine->engine_config : '';
        if ( empty( $config_json ) && isset( $engine->settings ) ) {
            $config_json = $engine->settings;
            $maybe_unserialized = @maybe_unserialize( $config_json );
            if ( is_array( $maybe_unserialized ) ) {
                $config = $maybe_unserialized;
            } else {
                $config = json_decode( $config_json, true );
            }
        } else {
            $config = json_decode( $config_json, true );
        }
        if ( ! is_array( $config ) ) {
            $config = array();
        }
        // S'assurer que api_key du moteur est dans le config
        if ( isset( $engine->api_key ) && ! empty( $engine->api_key ) && ! isset( $config['api_key'] ) ) {
            $config['api_key'] = $engine->api_key;
        }

        // Récupérer les instructions personnalisées
        $ai_settings       = get_option( 'lingua_commerce_ai_settings', array() );
        $ai_tone           = isset( $ai_settings['ai_tone'] ) ? $ai_settings['ai_tone'] : 'professional';
        $custom_instructions = isset( $ai_settings['custom_instructions'] ) ? $ai_settings['custom_instructions'] : '';

        // Conversion des codes langue pour les API
        $api_source = $this->convert_lang_code( $source_lang, $engine_slug );
        $api_target = $this->convert_lang_code( $target_lang, $engine_slug );

        $translated_text = '';
        $error_message   = '';

        // =========================================================================
        // DÉLÉGATION AU MOTEUR DE TRADUCTION
        // =========================================================================
        switch ( $engine_slug ) {

            // -----------------------------------------------------------------
            // DEEPL
            // -----------------------------------------------------------------
            case 'deepl':
                $api_key  = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $api_host = isset( $config['api_host'] ) ? $config['api_host'] : 'api.deepl.com';

                if ( empty( $api_key ) ) {
                    $error_message = __( 'Clé API DeepL manquante.', 'lingua-commerce-ai' );
                    break;
                }

                $is_free = ( strpos( $api_key, 'fx' ) === 0 );
                $endpoint = $is_free ? 'https://api-free.deepl.com/v2/translate' : 'https://' . $api_host . '/v2/translate';

                $response = wp_remote_post( $endpoint, array(
                    'timeout' => 30,
                    'body'    => array(
                        'auth_key'    => $api_key,
                        'text'        => $text,
                        'source_lang' => strtoupper( substr( $api_source, 0, 2 ) ),
                        'target_lang' => strtoupper( substr( $api_target, 0, 2 ) ),
                    ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['translations'][0]['text'] ) ) {
                        $translated_text = $body['translations'][0]['text'];
                    } elseif ( isset( $body['message'] ) ) {
                        $error_message = $body['message'];
                    } else {
                        $error_message = __( 'Réponse DeepL invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // YANDEX CLOUD
            // -----------------------------------------------------------------
            case 'yandex':
                $api_key    = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $folder_id  = isset( $config['folder_id'] ) ? $config['folder_id'] : '';

                if ( empty( $api_key ) ) {
                    $error_message = __( 'Clé API Yandex manquante.', 'lingua-commerce-ai' );
                    break;
                }

                $yandex_body = array(
                    'sourceLanguageCode' => substr( $api_source, 0, 2 ),
                    'targetLanguageCode' => substr( $api_target, 0, 2 ),
                    'texts'             => array( $text ),
                    'format'            => 'HTML',
                );

                if ( ! empty( $folder_id ) ) {
                    $yandex_body['folderId'] = $folder_id;
                }

                $response = wp_remote_post( 'https://translate.api.cloud.yandex.net/translate/v2/translate', array(
                    'timeout' => 30,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type'  => 'application/json',
                    ),
                    'body'    => wp_json_encode( $yandex_body ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['translations'][0]['text'] ) ) {
                        $translated_text = $body['translations'][0]['text'];
                    } elseif ( isset( $body['message'] ) ) {
                        $error_message = $body['message'];
                    } else {
                        $error_message = __( 'Réponse Yandex invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // BAIDU (avec signature MD5)
            // -----------------------------------------------------------------
            case 'baidu':
                $app_id  = isset( $config['app_id'] ) ? $config['app_id'] : '';
                $sec_key = isset( $config['secret_key'] ) ? $config['secret_key'] : '';

                if ( empty( $app_id ) || empty( $sec_key ) ) {
                    $error_message = __( 'Identifiants Baidu manquants (App ID + Secret Key).', 'lingua-commerce-ai' );
                    break;
                }

                $salt     = wp_rand( 10000, 99999 );
                $sign_str = $app_id . $text . $salt . $sec_key;
                $sign     = md5( $sign_str );

                $response = wp_remote_post( 'https://fanyi-api.baidu.com/api/trans/vip/translate', array(
                    'timeout' => 30,
                    'body'    => array(
                        'q'      => $text,
                        'from'   => substr( $api_source, 0, 2 ),
                        'to'     => substr( $api_target, 0, 2 ),
                        'appid'  => $app_id,
                        'salt'   => $salt,
                        'sign'   => $sign,
                    ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['trans_result'][0]['dst'] ) ) {
                        $translated_text = $body['trans_result'][0]['dst'];
                    } elseif ( isset( $body['error_code'] ) ) {
                        $error_message = sprintf(
                            /* translators: %s: Baidu error code */
                            __( 'Erreur Baidu (code %s).', 'lingua-commerce-ai' ),
                            $body['error_code']
                        );
                    } else {
                        $error_message = __( 'Réponse Baidu invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // MICROSOFT BING (avec région)
            // -----------------------------------------------------------------
            case 'microsoft':
                $api_key     = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $region      = isset( $config['region'] ) ? $config['region'] : 'global';
                $resource_id = isset( $config['resource_id'] ) ? $config['resource_id'] : '';

                if ( empty( $api_key ) ) {
                    $error_message = __( 'Clé API Microsoft manquante.', 'lingua-commerce-ai' );
                    break;
                }

                // Étape 1 : Obtenir le jeton d'autorisation ou utiliser la clé directement
                $ms_endpoint = 'https://api.cognitive.microsofttranslator.com/translate';
                $ms_params  = array(
                    'api-version' => '3.0',
                    'from'        => substr( $api_source, 0, 2 ),
                    'to'          => substr( $api_target, 0, 2 ),
                    'textType'    => 'html',
                );

                $ms_url = add_query_arg( $ms_params, $ms_endpoint );

                $headers = array(
                    'Ocp-Apim-Subscription-Key' => $api_key,
                    'Content-Type'              => 'application/json',
                );

                if ( ! empty( $resource_id ) ) {
                    $headers['Ocp-Apim-Subscription-Resource'] = $resource_id;
                }

                if ( $region && 'global' !== $region ) {
                    $headers['Ocp-Apim-Subscription-Region'] = $region;
                }

                $response = wp_remote_post( $ms_url, array(
                    'timeout' => 30,
                    'headers' => $headers,
                    'body'    => wp_json_encode( array( array( 'Text' => $text ) ) ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body[0]['translations'][0]['text'] ) ) {
                        $translated_text = $body[0]['translations'][0]['text'];
                    } elseif ( isset( $body['error']['message'] ) ) {
                        $error_message = $body['error']['message'];
                    } else {
                        $error_message = __( 'Réponse Microsoft invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // GOOGLE GEMINI
            // -----------------------------------------------------------------
            case 'google':
                $api_key = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $model   = isset( $config['model'] ) ? $config['model'] : 'gemini-1.5-flash';

                if ( empty( $api_key ) ) {
                    $error_message = __( 'Clé API Google manquante.', 'lingua-commerce-ai' );
                    break;
                }

                $gemini_endpoint = sprintf(
                    'https://generativelanguage.googleapis.com/v1beta/models/%s:generateText?key=%s',
                    $model,
                    $api_key
                );

                $prompt = $this->build_translation_prompt( $text, $source_lang, $target_lang, $ai_tone, $custom_instructions );

                $gemini_body = array(
                    'contents' => array(
                        array(
                            'parts' => array(
                                array( 'text' => $prompt ),
                            ),
                        ),
                    ),
                    'generationConfig' => array(
                        'temperature'     => 0.3,
                        'maxOutputTokens' => 8192,
                    ),
                );

                $response = wp_remote_post( $gemini_endpoint, array(
                    'timeout' => 60,
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'body'    => wp_json_encode( $gemini_body ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
                        $translated_text = trim( $body['candidates'][0]['content']['parts'][0]['text'] );
                        // Nettoyage des balises de code éventuelles
                        $translated_text = $this->clean_ai_output( $translated_text );
                    } elseif ( isset( $body['error']['message'] ) ) {
                        $error_message = $body['error']['message'];
                    } else {
                        $error_message = __( 'Réponse Google Gemini invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // LLM : OPENROUTER, DEEPSEEK, MISTRAL, OPENAI, Z.AI
            // (Tous utilisent l'API Chat Completions)
            // -----------------------------------------------------------------
            case 'openrouter':
            case 'deepseek':
            case 'mistral':
            case 'openai':
            case 'zai':
                $api_key = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $model   = isset( $config['model'] ) ? $config['model'] : $this->get_default_llm_model( $engine_slug );

                // Z.AI est gratuit — pas de clé API obligatoire
                if ( empty( $api_key ) && 'zai' !== $engine_slug ) {
                    $error_message = sprintf(
                        /* translators: %s: Engine name */
                        __( 'Clé API %s manquante.', 'lingua-commerce-ai' ),
                        ucfirst( $engine_slug )
                    );
                    break;
                }

                $chat_endpoint = $this->get_llm_endpoint( $engine_slug );
                $prompt        = $this->build_translation_prompt( $text, $source_lang, $target_lang, $ai_tone, $custom_instructions );

                $chat_body = array(
                    'model'       => $model,
                    'messages'    => array(
                        array(
                            'role'    => 'system',
                            'content' => $this->get_system_prompt( $ai_tone, $custom_instructions ),
                        ),
                        array(
                            'role'    => 'user',
                            'content' => $prompt,
                        ),
                    ),
                    'temperature' => 0.3,
                    'max_tokens'  => 4096,
                );

                $chat_headers = array(
                    'Content-Type'  => 'application/json',
                );

                // Ajouter Authorization seulement si une clé API est disponible
                if ( ! empty( $api_key ) ) {
                    $chat_headers['Authorization'] = 'Bearer ' . $api_key;
                }

                // OpenRouter nécessite des headers supplémentaires
                if ( 'openrouter' === $engine_slug ) {
                    $chat_headers['HTTP-Referer'] = home_url();
                    $chat_headers['X-Title']      = get_bloginfo( 'name' ) . ' - LinguaCommerce AI';
                }

                // Z.AI headers supplémentaires
                if ( 'zai' === $engine_slug ) {
                    $chat_headers['HTTP-Referer'] = home_url();
                    $chat_headers['X-Title']      = get_bloginfo( 'name' ) . ' - LinguaCommerce AI';
                }

                $response = wp_remote_post( $chat_endpoint, array(
                    'timeout' => 60,
                    'headers' => $chat_headers,
                    'body'    => wp_json_encode( $chat_body ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['choices'][0]['message']['content'] ) ) {
                        $translated_text = trim( $body['choices'][0]['message']['content'] );
                        $translated_text = $this->clean_ai_output( $translated_text );
                    } elseif ( isset( $body['error']['message'] ) ) {
                        $error_message = $body['error']['message'];
                    } elseif ( isset( $body['message'] ) ) {
                        $error_message = $body['message'];
                    } else {
                        $error_message = sprintf(
                            /* translators: %s: Engine name */
                            __( 'Réponse %s invalide.', 'lingua-commerce-ai' ),
                            ucfirst( $engine_slug )
                        );
                    }
                }
                break;

            default:
                $error_message = sprintf(
                    /* translators: %s: Engine slug */
                    __( 'Moteur "%s" non supporté.', 'lingua-commerce-ai' ),
                    esc_html( $engine_slug )
                );
                break;
        }

        // =========================================================================
        // TRAITEMENT DU RÉSULTAT
        // =========================================================================
        if ( ! empty( $error_message ) ) {
            // Journaliser l'erreur
            $this->log_event( 'error', $engine_slug, $error_message, $object_id, $object_type, $field_key );
            wp_send_json_error( array( 'message' => $error_message ) );
        }

        if ( empty( $translated_text ) ) {
            $this->log_event( 'error', $engine_slug, __( 'Traduction vide retournée.', 'lingua-commerce-ai' ), $object_id, $object_type, $field_key );
            wp_send_json_error( array( 'message' => __( 'La traduction a retourné un texte vide.', 'lingua-commerce-ai' ) ) );
        }

        // Sauvegarder automatiquement la traduction si les infos sont complètes
        $auto_validate = isset( $ai_settings['auto_validate'] ) ? (bool) $ai_settings['auto_validate'] : false;
        $status = $auto_validate ? 'validated' : 'draft';

        if ( ! empty( $object_id ) && ! empty( $object_type ) && ! empty( $field_key ) ) {
            if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
                require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-translation-model.php';
            }
            LinguaCommerce_Translation_Model::save_translation(
                $object_id,
                $object_type,
                $field_key,
                $target_lang,
                $translated_text,
                $status
            );
        }

        // Journaliser le succès
        $this->log_event( 'success', $engine_slug, sprintf(
            /* translators: 1: field key, 2: target language */
            __( 'Traduction réussie du champ %1$s vers %2$s', 'lingua-commerce-ai' ),
            $field_key,
            $target_lang
        ), $object_id, $object_type, $field_key );

        wp_send_json_success( array(
            'translated_text' => $translated_text,
            'engine'          => $engine_slug,
            'status'          => $status,
        ) );
    }

    // =========================================================================
    // SAUVEGARDE D'UN MOTEUR IA
    // =========================================================================

    /**
     * Sauvegarde la configuration d'un moteur IA dans wp_lingua_ai_engines
     */
    public function ajax_save_engine() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        $engine_name   = isset( $_POST['engine_name'] ) ? sanitize_text_field( $_POST['engine_name'] ) : '';
        $engine_config = isset( $_POST['engine_config'] ) ? $_POST['engine_config'] : array();
        $status        = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'inactive';

        if ( empty( $engine_name ) ) {
            wp_send_json_error( array( 'message' => __( 'Nom du moteur manquant.', 'lingua-commerce-ai' ) ) );
        }

        // Validation du statut
        if ( ! in_array( $status, array( 'active', 'inactive' ), true ) ) {
            $status = 'inactive';
        }

        // Nettoyage et encodage de la configuration
        if ( is_string( $engine_config ) ) {
            $engine_config = json_decode( stripslashes( $engine_config ), true );
        }

        if ( ! is_array( $engine_config ) ) {
            $engine_config = array();
        }

        // Nettoyage des valeurs de configuration
        $clean_config = array();
        foreach ( $engine_config as $key => $value ) {
            $clean_key          = sanitize_text_field( $key );
            $clean_config[ $clean_key ] = sanitize_text_field( $value );
        }

        // Masquer les clés API dans les logs
        $log_config = $clean_config;
        foreach ( array( 'api_key', 'secret_key', 'app_id' ) as $sensitive_key ) {
            if ( isset( $log_config[ $sensitive_key ] ) && ! empty( $log_config[ $sensitive_key ] ) ) {
                $log_config[ $sensitive_key ] = '****' . substr( $log_config[ $sensitive_key ], -4 );
            }
        }

        $config_json = wp_json_encode( $clean_config );

        // Vérifier si le moteur existe déjà
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_engines} WHERE engine_name = %s",
                $engine_name
            )
        );

        if ( $existing ) {
            // Mise à jour
            $result = $wpdb->update(
                $this->table_engines,
                array(
                    'engine_config' => $config_json,
                    'status'        => $status,
                    'last_updated'    => current_time( 'mysql' ),
                ),
                array( 'id' => $existing->id ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            // Insertion
            $result = $wpdb->insert(
                $this->table_engines,
                array(
                    'engine_name'   => $engine_name,
                    'engine_config' => $config_json,
                    'status'        => $status,
                    'created_at'    => current_time( 'mysql' ),
                    'last_updated'    => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s' )
            );
        }

        if ( false === $result ) {
            $this->log_event( 'error', $engine_name, sprintf(
                /* translators: %s: Database error */
                __( 'Erreur BDD lors de la sauvegarde du moteur : %s', 'lingua-commerce-ai' ),
                $wpdb->last_error
            ) );
            wp_send_json_error( array( 'message' => __( 'Erreur lors de la sauvegarde en base de données.', 'lingua-commerce-ai' ) ) );
        }

        $this->log_event( 'success', $engine_name, sprintf(
            /* translators: %s: Engine status */
            __( 'Moteur IA sauvegardé (statut : %s)', 'lingua-commerce-ai' ),
            $status
        ) );

        wp_send_json_success( array(
            'message'     => __( 'Moteur IA sauvegardé avec succès.', 'lingua-commerce-ai' ),
            'engine_name' => $engine_name,
            'status'      => $status,
        ) );
    }

    // =========================================================================
    // TEST D'UN MOTEUR IA
    // =========================================================================

    /**
     * Teste la connexion à un moteur IA avec une traduction réelle
     * Supporte : deepl, yandex, baidu, microsoft, google, openrouter, deepseek, mistral, openai
     */
    public function ajax_test_engine() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        $engine_slug = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : '';
        $config_raw  = isset( $_POST['config'] ) ? $_POST['config'] : array();

        if ( empty( $engine_slug ) ) {
            wp_send_json_error( array( 'message' => __( 'Nom du moteur manquant.', 'lingua-commerce-ai' ) ) );
        }

        // Nettoyage de la configuration
        if ( is_string( $config_raw ) ) {
            $config = json_decode( stripslashes( $config_raw ), true );
        } else {
            $config = $config_raw;
        }

        if ( ! is_array( $config ) ) {
            $config = array();
        }

        // Texte de test
        $test_text     = __( 'Hello, this is a test translation for LinguaCommerce AI plugin.', 'lingua-commerce-ai' );
        $source_lang   = 'en_US';
        $target_lang   = 'fr_FR';
        $api_source    = $this->convert_lang_code( $source_lang, $engine_slug );
        $api_target    = $this->convert_lang_code( $target_lang, $engine_slug );
        $translated    = '';
        $error_message = '';
        $latency_ms    = 0;

        $start_time = microtime( true );

        switch ( $engine_slug ) {

            // -----------------------------------------------------------------
            // DEEPL
            // -----------------------------------------------------------------
            case 'deepl':
                $api_key  = isset( $config['api_key'] ) ? sanitize_text_field( $config['api_key'] ) : '';
                $api_host = isset( $config['api_host'] ) ? sanitize_text_field( $config['api_host'] ) : 'api.deepl.com';

                if ( empty( $api_key ) ) {
                    $error_message = __( 'Clé API DeepL manquante.', 'lingua-commerce-ai' );
                    break;
                }

                $is_free   = ( strpos( $api_key, 'fx' ) === 0 );
                $endpoint  = $is_free ? 'https://api-free.deepl.com/v2/translate' : 'https://' . $api_host . '/v2/translate';

                $response = wp_remote_post( $endpoint, array(
                    'timeout' => 30,
                    'body'    => array(
                        'auth_key'    => $api_key,
                        'text'        => $test_text,
                        'source_lang' => strtoupper( substr( $api_source, 0, 2 ) ),
                        'target_lang' => strtoupper( substr( $api_target, 0, 2 ) ),
                    ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['translations'][0]['text'] ) ) {
                        $translated = $body['translations'][0]['text'];
                    } elseif ( isset( $body['message'] ) ) {
                        $error_message = $body['message'];
                    } else {
                        $error_message = __( 'Réponse DeepL invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // YANDEX CLOUD
            // -----------------------------------------------------------------
            case 'yandex':
                $api_key   = isset( $config['api_key'] ) ? sanitize_text_field( $config['api_key'] ) : '';
                $folder_id = isset( $config['folder_id'] ) ? sanitize_text_field( $config['folder_id'] ) : '';

                if ( empty( $api_key ) ) {
                    $error_message = __( 'Clé API Yandex manquante.', 'lingua-commerce-ai' );
                    break;
                }

                $yandex_body = array(
                    'sourceLanguageCode' => substr( $api_source, 0, 2 ),
                    'targetLanguageCode' => substr( $api_target, 0, 2 ),
                    'texts'             => array( $test_text ),
                    'format'            => 'HTML',
                );

                if ( ! empty( $folder_id ) ) {
                    $yandex_body['folderId'] = $folder_id;
                }

                $response = wp_remote_post( 'https://translate.api.cloud.yandex.net/translate/v2/translate', array(
                    'timeout' => 30,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type'  => 'application/json',
                    ),
                    'body'    => wp_json_encode( $yandex_body ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['translations'][0]['text'] ) ) {
                        $translated = $body['translations'][0]['text'];
                    } elseif ( isset( $body['message'] ) ) {
                        $error_message = $body['message'];
                    } else {
                        $error_message = __( 'Réponse Yandex invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // BAIDU
            // -----------------------------------------------------------------
            case 'baidu':
                $app_id  = isset( $config['app_id'] ) ? sanitize_text_field( $config['app_id'] ) : '';
                $sec_key = isset( $config['secret_key'] ) ? sanitize_text_field( $config['secret_key'] ) : '';

                if ( empty( $app_id ) || empty( $sec_key ) ) {
                    $error_message = __( 'Identifiants Baidu manquants.', 'lingua-commerce-ai' );
                    break;
                }

                $salt     = wp_rand( 10000, 99999 );
                $sign_str = $app_id . $test_text . $salt . $sec_key;
                $sign     = md5( $sign_str );

                $response = wp_remote_post( 'https://fanyi-api.baidu.com/api/trans/vip/translate', array(
                    'timeout' => 30,
                    'body'    => array(
                        'q'      => $test_text,
                        'from'   => substr( $api_source, 0, 2 ),
                        'to'     => substr( $api_target, 0, 2 ),
                        'appid'  => $app_id,
                        'salt'   => $salt,
                        'sign'   => $sign,
                    ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['trans_result'][0]['dst'] ) ) {
                        $translated = $body['trans_result'][0]['dst'];
                    } elseif ( isset( $body['error_code'] ) ) {
                        $error_message = sprintf( 'Erreur Baidu (code %s)', $body['error_code'] );
                    } else {
                        $error_message = __( 'Réponse Baidu invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // MICROSOFT BING
            // -----------------------------------------------------------------
            case 'microsoft':
                $api_key     = isset( $config['api_key'] ) ? sanitize_text_field( $config['api_key'] ) : '';
                $region      = isset( $config['region'] ) ? sanitize_text_field( $config['region'] ) : 'global';
                $resource_id = isset( $config['resource_id'] ) ? sanitize_text_field( $config['resource_id'] ) : '';

                if ( empty( $api_key ) ) {
                    $error_message = __( 'Clé API Microsoft manquante.', 'lingua-commerce-ai' );
                    break;
                }

                $ms_endpoint = 'https://api.cognitive.microsofttranslator.com/translate';
                $ms_params   = array(
                    'api-version' => '3.0',
                    'from'        => substr( $api_source, 0, 2 ),
                    'to'          => substr( $api_target, 0, 2 ),
                    'textType'    => 'html',
                );
                $ms_url = add_query_arg( $ms_params, $ms_endpoint );

                $headers = array(
                    'Ocp-Apim-Subscription-Key' => $api_key,
                    'Content-Type'              => 'application/json',
                );

                if ( ! empty( $resource_id ) ) {
                    $headers['Ocp-Apim-Subscription-Resource'] = $resource_id;
                }

                if ( $region && 'global' !== $region ) {
                    $headers['Ocp-Apim-Subscription-Region'] = $region;
                }

                $response = wp_remote_post( $ms_url, array(
                    'timeout' => 30,
                    'headers' => $headers,
                    'body'    => wp_json_encode( array( array( 'Text' => $test_text ) ) ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body[0]['translations'][0]['text'] ) ) {
                        $translated = $body[0]['translations'][0]['text'];
                    } elseif ( isset( $body['error']['message'] ) ) {
                        $error_message = $body['error']['message'];
                    } else {
                        $error_message = __( 'Réponse Microsoft invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // GOOGLE GEMINI
            // -----------------------------------------------------------------
            case 'google':
                $api_key = isset( $config['api_key'] ) ? sanitize_text_field( $config['api_key'] ) : '';
                $model   = isset( $config['model'] ) ? sanitize_text_field( $config['model'] ) : 'gemini-1.5-flash';

                if ( empty( $api_key ) ) {
                    $error_message = __( 'Clé API Google manquante.', 'lingua-commerce-ai' );
                    break;
                }

                $gemini_endpoint = sprintf(
                    'https://generativelanguage.googleapis.com/v1beta/models/%s:generateText?key=%s',
                    $model,
                    $api_key
                );

                $prompt = $this->build_translation_prompt( $test_text, $source_lang, $target_lang, 'professional', '' );

                $gemini_body = array(
                    'contents' => array(
                        array(
                            'parts' => array(
                                array( 'text' => $prompt ),
                            ),
                        ),
                    ),
                    'generationConfig' => array(
                        'temperature'     => 0.3,
                        'maxOutputTokens' => 2048,
                    ),
                );

                $response = wp_remote_post( $gemini_endpoint, array(
                    'timeout' => 60,
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'body'    => wp_json_encode( $gemini_body ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
                        $translated = trim( $body['candidates'][0]['content']['parts'][0]['text'] );
                        $translated = $this->clean_ai_output( $translated );
                    } elseif ( isset( $body['error']['message'] ) ) {
                        $error_message = $body['error']['message'];
                    } else {
                        $error_message = __( 'Réponse Google Gemini invalide.', 'lingua-commerce-ai' );
                    }
                }
                break;

            // -----------------------------------------------------------------
            // LLM : OPENROUTER, DEEPSEEK, MISTRAL, OPENAI, Z.AI
            // -----------------------------------------------------------------
            case 'openrouter':
            case 'deepseek':
            case 'mistral':
            case 'openai':
            case 'zai':
                $api_key = isset( $config['api_key'] ) ? sanitize_text_field( $config['api_key'] ) : '';
                $model   = isset( $config['model'] ) ? sanitize_text_field( $config['model'] ) : $this->get_default_llm_model( $engine_slug );

                // Z.AI est gratuit — pas de clé API obligatoire
                if ( empty( $api_key ) && 'zai' !== $engine_slug ) {
                    $error_message = sprintf( __( 'Clé API %s manquante.', 'lingua-commerce-ai' ), ucfirst( $engine_slug ) );
                    break;
                }

                $chat_endpoint = $this->get_llm_endpoint( $engine_slug );
                $prompt        = $this->build_translation_prompt( $test_text, $source_lang, $target_lang, 'professional', '' );

                $chat_body = array(
                    'model'       => $model,
                    'messages'    => array(
                        array(
                            'role'    => 'system',
                            'content' => $this->get_system_prompt( 'professional', '' ),
                        ),
                        array(
                            'role'    => 'user',
                            'content' => $prompt,
                        ),
                    ),
                    'temperature' => 0.3,
                    'max_tokens'  => 1024,
                );

                $chat_headers = array(
                    'Content-Type'  => 'application/json',
                );

                // Ajouter Authorization seulement si une clé API est disponible
                if ( ! empty( $api_key ) ) {
                    $chat_headers['Authorization'] = 'Bearer ' . $api_key;
                }

                if ( 'openrouter' === $engine_slug ) {
                    $chat_headers['HTTP-Referer'] = home_url();
                    $chat_headers['X-Title']      = get_bloginfo( 'name' ) . ' - LinguaCommerce AI';
                }

                if ( 'zai' === $engine_slug ) {
                    $chat_headers['HTTP-Referer'] = home_url();
                    $chat_headers['X-Title']      = get_bloginfo( 'name' ) . ' - LinguaCommerce AI';
                }

                $response = wp_remote_post( $chat_endpoint, array(
                    'timeout' => 60,
                    'headers' => $chat_headers,
                    'body'    => wp_json_encode( $chat_body ),
                ) );

                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                } else {
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $body['choices'][0]['message']['content'] ) ) {
                        $translated = trim( $body['choices'][0]['message']['content'] );
                        $translated = $this->clean_ai_output( $translated );
                    } elseif ( isset( $body['error']['message'] ) ) {
                        $error_message = $body['error']['message'];
                    } elseif ( isset( $body['message'] ) ) {
                        $error_message = $body['message'];
                    } else {
                        $error_message = sprintf( __( 'Réponse %s invalide.', 'lingua-commerce-ai' ), ucfirst( $engine_slug ) );
                    }
                }
                break;

            default:
                $error_message = sprintf( __( 'Moteur "%s" non supporté pour le test.', 'lingua-commerce-ai' ), esc_html( $engine_slug ) );
                break;
        }

        $end_time   = microtime( true );
        $latency_ms = round( ( $end_time - $start_time ) * 1000 );

        if ( ! empty( $error_message ) ) {
            $this->log_event( 'error', $engine_slug, sprintf(
                /* translators: %s: Error message */
                __( 'Test de connexion échoué : %s', 'lingua-commerce-ai' ),
                $error_message
            ) );
            wp_send_json_error( array(
                'message'   => $error_message,
                'latency_ms' => $latency_ms,
            ) );
        }

        wp_send_json_success( array(
            'message'       => sprintf(
                /* translators: %s: Engine name */
                __( 'Connexion %s réussie !', 'lingua-commerce-ai' ),
                ucfirst( $engine_slug )
            ),
            'translated'    => $translated,
            'original'      => $test_text,
            'latency_ms'    => $latency_ms,
            'engine'        => $engine_slug,
        ) );
    }

    // =========================================================================
    // SAUVEGARDE DES RÉGLAGES IA
    // =========================================================================

    /**
     * Sauvegarde les réglages IA : ai_tone, default_engine, custom_instructions, auto_validate
     */
    public function ajax_save_ai_settings() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        $settings = get_option( 'lingua_commerce_ai_settings', array() );
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        // Ton IA
        if ( isset( $_POST['ai_tone'] ) ) {
            $tone = sanitize_text_field( $_POST['ai_tone'] );
            $allowed_tones = array( 'professional', 'casual', 'formal', 'creative', 'technical' );
            $settings['ai_tone'] = in_array( $tone, $allowed_tones, true ) ? $tone : 'professional';
        }

        // Moteur par défaut
        if ( isset( $_POST['default_engine'] ) ) {
            $settings['default_engine'] = sanitize_text_field( $_POST['default_engine'] );
        }

        // Instructions personnalisées
        if ( isset( $_POST['custom_instructions'] ) ) {
            $settings['custom_instructions'] = sanitize_textarea_field( $_POST['custom_instructions'] );
        }

        // Validation automatique
        if ( isset( $_POST['auto_validate'] ) ) {
            $settings['auto_validate'] = (bool) $_POST['auto_validate'];
        }

        $result = update_option( 'lingua_commerce_ai_settings', $settings );

        if ( $result ) {
            $this->log_event( 'success', 'settings', __( 'Réglages IA sauvegardés.', 'lingua-commerce-ai' ) );
            wp_send_json_success( array( 'message' => __( 'Réglages IA sauvegardés avec succès.', 'lingua-commerce-ai' ) ) );
        } else {
            wp_send_json_success( array( 'message' => __( 'Aucune modification détectée.', 'lingua-commerce-ai' ) ) );
        }
    }

    // =========================================================================
    // FILE D'ATTENTE DE TRADUCTION
    // =========================================================================

    /**
     * Récupère les éléments de la file d'attente de traduction
     */
    public function ajax_get_queue_items() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        // Vérifier que la table existe
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_queue}'" ) !== $this->table_queue ) {
            wp_send_json_success( array( 'items' => array(), 'total' => 0 ) );
        }

        $status_filter = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'all';
        $page          = isset( $_POST['page'] ) ? max( 1, intval( $_POST['page'] ) ) : 1;
        $per_page      = isset( $_POST['per_page'] ) ? min( 100, max( 1, intval( $_POST['per_page'] ) ) ) : 20;
        $offset        = ( $page - 1 ) * $per_page;

        // Construction de la requête
        $where = '';
        if ( 'all' !== $status_filter && in_array( $status_filter, array( 'pending', 'processing', 'completed', 'failed' ), true ) ) {
            $where = $wpdb->prepare( " WHERE status = %s", $status_filter );
        }

        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_queue}{$where}" );

        $items = $wpdb->get_results(
            "SELECT * FROM {$this->table_queue}{$where}
             ORDER BY created_at DESC
             LIMIT {$per_page} OFFSET {$offset}"
        );

        // Formatage des résultats
        $formatted_items = array();
        foreach ( $items as $item ) {
            $formatted_items[] = array(
                'id'          => (int) $item->id,
                'object_id'   => $item->object_id,
                'object_type' => $item->object_type,
                'field_key'   => $item->field_key,
                'language'    => $item->language,
                'engine'      => isset( $item->engine ) ? $item->engine : '',
                'status'      => $item->status,
                'attempts'    => isset( $item->attempts ) ? (int) $item->attempts : 0,
                'error_message' => isset( $item->error_message ) ? $item->error_message : '',
                'created_at'  => $item->created_at,
                'last_updated'  => isset( $item->last_updated ) ? $item->last_updated : '',
            );
        }

        wp_send_json_success( array(
            'items'     => $formatted_items,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $per_page,
            'total_pages' => (int) ceil( $total / $per_page ),
        ) );
    }

    /**
     * Supprime un élément de la file d'attente
     */
    public function ajax_delete_queue_item() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        $item_id = isset( $_POST['item_id'] ) ? intval( $_POST['item_id'] ) : 0;

        if ( $item_id <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'ID d\'élément invalide.', 'lingua-commerce-ai' ) ) );
        }

        $result = $wpdb->delete(
            $this->table_queue,
            array( 'id' => $item_id ),
            array( '%d' )
        );

        if ( false === $result ) {
            wp_send_json_error( array( 'message' => __( 'Erreur lors de la suppression.', 'lingua-commerce-ai' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'Élément supprimé de la file d\'attente.', 'lingua-commerce-ai' ) ) );
    }

    // =========================================================================
    // JOURNAUX (LOGS)
    // =========================================================================

    /**
     * Récupère les journaux de la table wp_lingua_logs
     * Crée la table si elle n'existe pas
     */
    public function ajax_get_logs() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        // Créer la table si elle n'existe pas
        $this->ensure_logs_table();

        $type_filter = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'all';
        $engine_filter = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : 'all';
        $page        = isset( $_POST['page'] ) ? max( 1, intval( $_POST['page'] ) ) : 1;
        $per_page    = isset( $_POST['per_page'] ) ? min( 100, max( 1, intval( $_POST['per_page'] ) ) ) : 50;
        $offset      = ( $page - 1 ) * $per_page;

        // Construction des conditions WHERE
        $where_parts = array();
        $where_args  = array();

        if ( 'all' !== $type_filter && in_array( $type_filter, array( 'success', 'error', 'warning', 'info' ), true ) ) {
            $where_parts[] = 'type = %s';
            $where_args[]  = $type_filter;
        }

        if ( 'all' !== $engine_filter ) {
            $where_parts[] = 'engine = %s';
            $where_args[]  = $engine_filter;
        }

        $where_clause = '';
        if ( ! empty( $where_parts ) ) {
            $where_clause = ' WHERE ' . implode( ' AND ', $where_parts );
        }

        // Comptage total
        if ( ! empty( $where_args ) ) {
            $count_sql = "SELECT COUNT(*) FROM {$this->table_logs}{$where_clause}";
            $total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $where_args ) );
        } else {
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_logs}" );
        }

        // Récupération des logs
        if ( ! empty( $where_args ) ) {
            $query_args = array_merge( $where_args, array( $per_page, $offset ) );
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_logs}{$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                    $query_args
                )
            );
        } else {
            $items = $wpdb->get_results(
                "SELECT * FROM {$this->table_logs} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}"
            );
        }

        // Formatage des résultats
        $formatted_logs = array();
        foreach ( $items as $item ) {
            $formatted_logs[] = array(
                'id'          => (int) $item->id,
                'type'        => $item->type,
                'engine'      => $item->engine,
                'message'     => $item->message,
                'object_id'   => isset( $item->object_id ) ? $item->object_id : '',
                'object_type' => isset( $item->object_type ) ? $item->object_type : '',
                'field_key'   => isset( $item->field_key ) ? $item->field_key : '',
                'created_at'  => $item->created_at,
            );
        }

        wp_send_json_success( array(
            'logs'        => $formatted_logs,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil( $total / $per_page ),
        ) );
    }

    /**
     * Vide la table des journaux
     */
    public function ajax_clear_logs() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        // Vérifier que la table existe
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_logs}'" ) === $this->table_logs ) {
            $wpdb->query( "TRUNCATE TABLE {$this->table_logs}" );
        }

        wp_send_json_success( array( 'message' => __( 'Journaux vidés avec succès.', 'lingua-commerce-ai' ) ) );
    }

    // =========================================================================
    // HANDLERS AJAX MANQUANTS (utilisés par le JS de la page IA)
    // =========================================================================

    /**
     * Teste une clé API pour un moteur donné (appelé depuis le bouton "Tester")
     * Récupère la clé depuis le champ input du formulaire ou la config sauvegardée.
     */
    public function ajax_test_api_key() {
        // Capturer tout output parasite (warnings PHP, notices) qui corromprait le JSON
        ob_start();

        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            ob_end_clean();
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        $engine_slug = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : '';
        $api_key     = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';

        if ( empty( $engine_slug ) ) {
            wp_send_json_error( array( 'message' => __( 'Nom du moteur manquant.', 'lingua-commerce-ai' ) ) );
        }

        // Z.AI est gratuit — pas de clé nécessaire, test direct
        if ( 'zai' === $engine_slug ) {
            $api_key = 'free';
        }

        // Si pas de clé fournie, chercher dans les réglages sauvegardés
        if ( empty( $api_key ) ) {
            $ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );
            $key_field   = 'api_key_' . $engine_slug;
            $api_key     = isset( $ai_settings[ $key_field ] ) ? $ai_settings[ $key_field ] : '';
        }

        // Si toujours pas de clé, chercher dans la table des moteurs
        if ( empty( $api_key ) ) {
            global $wpdb;
            $engine_row = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM {$this->table_engines} WHERE engine_name = %s", $engine_slug )
            );
            if ( $engine_row ) {
                $config_json = isset( $engine_row->engine_config ) ? $engine_row->engine_config : '';
                if ( empty( $config_json ) && isset( $engine_row->settings ) ) {
                    $config_json = $engine_row->settings;
                }
                $config = json_decode( $config_json, true );
                if ( is_array( $config ) && isset( $config['api_key'] ) ) {
                    $api_key = $config['api_key'];
                }
                if ( empty( $api_key ) && isset( $engine_row->api_key ) ) {
                    $api_key = $engine_row->api_key;
                }
            }
        }

        // Construire la config pour le test
        $config = array( 'api_key' => $api_key );

        // Cas spéciaux : Baidu nécessite app_id + secret_key
        if ( 'baidu' === $engine_slug ) {
            $ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );
            $config['app_id']     = isset( $ai_settings['api_key_baidu_app_id'] ) ? $ai_settings['api_key_baidu_app_id'] : '';
            $config['secret_key'] = isset( $ai_settings['api_key_baidu_secret'] ) ? $ai_settings['api_key_baidu_secret'] : '';
            if ( empty( $config['app_id'] ) || empty( $config['secret_key'] ) ) {
                wp_send_json_error( array( 'message' => __( 'Identifiants Baidu manquants (App ID + Secret Key).', 'lingua-commerce-ai' ) ) );
            }
        }

        // Microsoft : region + resource_id
        if ( 'microsoft' === $engine_slug ) {
            $ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );
            $config['region']      = isset( $ai_settings['api_key_microsoft_region'] ) ? $ai_settings['api_key_microsoft_region'] : 'global';
            $config['resource_id'] = isset( $ai_settings['api_key_microsoft_resource'] ) ? $ai_settings['api_key_microsoft_resource'] : '';
        }

        // Yandex : folder_id
        if ( 'yandex' === $engine_slug ) {
            $ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );
            $config['folder_id'] = isset( $ai_settings['api_key_yandex_folder'] ) ? $ai_settings['api_key_yandex_folder'] : '';
        }

        // Déléguer au moteur de test existant
        $test_text   = __( 'Hello, this is a test translation for LinguaCommerce AI plugin.', 'lingua-commerce-ai' );
        $source_lang = 'en_US';
        $target_lang = 'fr_FR';
        $api_source  = $this->convert_lang_code( $source_lang, $engine_slug );
        $api_target  = $this->convert_lang_code( $target_lang, $engine_slug );
        $translated  = '';
        $error_message = '';

        $start_time = microtime( true );

        // Effectuer le test via le switch de traduction
        $result = $this->do_engine_translate( $engine_slug, $config, $test_text, $source_lang, $target_lang );

        $latency_ms = round( ( microtime( true ) - $start_time ) * 1000 );

        // Nettoyer tout output parasite avant d'envoyer le JSON
        ob_end_clean();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message'  => $result->get_error_message(),
                'latency'  => $latency_ms,
            ) );
        }

        wp_send_json_success( array(
            'translated' => $result,
            'latency'    => $latency_ms,
            'message'    => sprintf(
                /* translators: %d: latency in milliseconds */
                __( 'Connexion réussie ! Latence : %d ms', 'lingua-commerce-ai' ),
                $latency_ms
            ),
        ) );
    }

    /**
     * Rafraîchit le nonce AJAX pour éviter les erreurs de session expirée
     */
    public function ajax_refresh_nonce() {
        // Pas de vérification de nonce stricte ici — on utilise une vérification souple
        // car le but est précisément de rafraîchir un nonce qui pourrait être expiré
        $old_nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
        if ( empty( $old_nonce ) || ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        wp_send_json_success( array(
            'nonce' => wp_create_nonce( 'lingua_admin_nonce' ),
        ) );
    }

    /**
     * Définit le moteur principal de traduction
     */
    public function ajax_set_primary_engine() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        $engine = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : '';
        if ( empty( $engine ) ) {
            wp_send_json_error( array( 'message' => __( 'Moteur non spécifié.', 'lingua-commerce-ai' ) ) );
        }

        $ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );
        if ( ! is_array( $ai_settings ) ) {
            $ai_settings = array();
        }
        $ai_settings['primary_engine'] = $engine;
        update_option( 'lingua_commerce_ai_ai_settings', $ai_settings );

        wp_send_json_success( array( 'message' => sprintf( __( 'Moteur principal défini : %s', 'lingua-commerce-ai' ), $engine ) ) );
    }

    /**
     * Sauvegarde toutes les clés API depuis le formulaire
     */
    public function ajax_save_api_keys() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        $keys = isset( $_POST['keys'] ) ? $_POST['keys'] : array();
        if ( ! is_array( $keys ) ) {
            wp_send_json_error( array( 'message' => __( 'Données invalides.', 'lingua-commerce-ai' ) ) );
        }

        $ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );
        if ( ! is_array( $ai_settings ) ) {
            $ai_settings = array();
        }

        // Sauvegarder chaque clé
        $saved_engines = array();
        foreach ( $keys as $slug => $key_value ) {
            $slug = sanitize_text_field( $slug );
            $key_value = sanitize_text_field( $key_value );
            $field_name = 'api_key_' . $slug;
            $ai_settings[ $field_name ] = $key_value;
            $saved_engines[] = $slug;

            // Synchroniser avec la table lingua_ai_engines
            $this->sync_engine_to_db( $slug, $key_value );
        }

        update_option( 'lingua_commerce_ai_ai_settings', $ai_settings );

        $this->log_event( 'success', 'settings', sprintf(
            __( 'Clés API sauvegardées : %s', 'lingua-commerce-ai' ),
            implode( ', ', $saved_engines )
        ) );

        wp_send_json_success( array( 'message' => __( 'Clés API sauvegardées avec succès.', 'lingua-commerce-ai' ) ) );
    }

    /**
     * Lance le traitement de la file d'attente de traduction
     */
    public function ajax_trigger_queue() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        // Vérifier que la table existe
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_queue}'" ) !== $this->table_queue ) {
            wp_send_json_error( array( 'message' => __( 'Table de file d\'attente introuvable.', 'lingua-commerce-ai' ) ) );
        }

        // Récupérer les éléments en attente
        $pending = $wpdb->get_results(
            "SELECT * FROM {$this->table_queue} WHERE status = 'pending' ORDER BY created_at ASC LIMIT 20"
        );

        if ( empty( $pending ) ) {
            wp_send_json_success( array( 'message' => __( 'Aucune traduction en attente.', 'lingua-commerce-ai' ), 'processed' => 0 ) );
        }

        $processed = 0;
        $errors    = 0;

        foreach ( $pending as $item ) {
            // Marquer comme en cours
            $wpdb->update(
                $this->table_queue,
                array( 'status' => 'processing', 'last_updated' => current_time( 'mysql' ) ),
                array( 'id' => $item->id ),
                array( '%s', '%s' ),
                array( '%d' )
            );

            // Effectuer la traduction
            $ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );
            $engine_slug = isset( $ai_settings['primary_engine'] ) ? $ai_settings['primary_engine'] : 'zai';

            $config = $this->get_engine_config( $engine_slug );

            $result = $this->do_engine_translate(
                $engine_slug,
                $config,
                $item->source_text,
                isset( $item->source_lang ) ? $item->source_lang : 'en_US',
                $item->language
            );

            if ( is_wp_error( $result ) ) {
                $wpdb->update(
                    $this->table_queue,
                    array(
                        'status'        => 'failed',
                        'error_message' => $result->get_error_message(),
                        'last_updated'  => current_time( 'mysql' ),
                    ),
                    array( 'id' => $item->id ),
                    array( '%s', '%s', '%s' ),
                    array( '%d' )
                );
                $errors++;
            } else {
                // Sauvegarder la traduction
                if ( ! class_exists( 'LinguaCommerce_Translation_Model' ) ) {
                    require_once LINGUA_COMMERCE_AI_PLUGIN_DIR . 'includes/class-lingua-translation-model.php';
                }
                LinguaCommerce_Translation_Model::save_translation(
                    $item->object_id,
                    $item->object_type,
                    $item->field_key,
                    $item->language,
                    $result,
                    'validated'
                );

                $wpdb->update(
                    $this->table_queue,
                    array( 'status' => 'completed', 'last_updated' => current_time( 'mysql' ) ),
                    array( 'id' => $item->id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
                $processed++;
            }
        }

        wp_send_json_success( array(
            'message'   => sprintf( __( '%d traduites, %d erreurs.', 'lingua-commerce-ai' ), $processed, $errors ),
            'processed' => $processed,
            'errors'    => $errors,
        ) );
    }

    /**
     * Relance les traductions échouées
     */
    public function ajax_retry_failed() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_queue}'" ) !== $this->table_queue ) {
            wp_send_json_error( array( 'message' => __( 'Table introuvable.', 'lingua-commerce-ai' ) ) );
        }

        $result = $wpdb->update(
            $this->table_queue,
            array( 'status' => 'pending', 'last_updated' => current_time( 'mysql' ) ),
            array( 'status' => 'failed' ),
            array( '%s', '%s' ),
            array( '%s' )
        );

        wp_send_json_success( array(
            'message' => sprintf( __( '%d traductions replanifiées.', 'lingua-commerce-ai' ), (int) $result ),
            'count'   => (int) $result,
        ) );
    }

    /**
     * Vide la file d'attente
     */
    public function ajax_clear_queue() {
        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        global $wpdb;

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_queue}'" ) === $this->table_queue ) {
            $wpdb->query( "TRUNCATE TABLE {$this->table_queue}" );
        }

        wp_send_json_success( array( 'message' => __( 'File d\'attente vidée.', 'lingua-commerce-ai' ) ) );
    }

    /**
     * Traduction de test inline : texte personnalisé + langue cible + moteur
     * Ne nécessite PAS que le moteur soit dans la table lingua_ai_engines.
     */
    public function ajax_test_translate() {
        // Capturer tout output parasite (warnings PHP, notices) qui corromprait le JSON
        ob_start();

        check_ajax_referer( 'lingua_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            ob_end_clean();
            wp_send_json_error( array( 'message' => __( 'Permission refusée.', 'lingua-commerce-ai' ) ) );
        }

        $engine_slug = isset( $_POST['engine'] ) ? sanitize_text_field( $_POST['engine'] ) : '';
        $text        = isset( $_POST['text'] ) ? wp_kses_post( stripslashes( $_POST['text'] ) ) : '';
        $target_lang = isset( $_POST['target_lang'] ) ? sanitize_text_field( $_POST['target_lang'] ) : 'fr_FR';
        $source_lang = isset( $_POST['source_lang'] ) ? sanitize_text_field( $_POST['source_lang'] ) : 'en_US';

        if ( empty( $engine_slug ) || empty( $text ) ) {
            ob_end_clean();
            wp_send_json_error( array( 'message' => __( 'Moteur et texte requis.', 'lingua-commerce-ai' ) ) );
        }

        // Récupérer la config du moteur
        $config = $this->get_engine_config( $engine_slug );

        $start_time = microtime( true );
        $result = $this->do_engine_translate( $engine_slug, $config, $text, $source_lang, $target_lang );
        $latency_ms = round( ( microtime( true ) - $start_time ) * 1000 );

        // Nettoyer tout output parasite avant d'envoyer le JSON
        ob_end_clean();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
                'latency' => $latency_ms,
            ) );
        }

        wp_send_json_success( array(
            'translated_text' => $result,
            'engine'          => $engine_slug,
            'latency'         => $latency_ms,
        ) );
    }

    // =========================================================================
    // MÉTHODES UTILITAIRES PUBLIQUES POUR LA TRADUCTION
    // =========================================================================

    /**
     * Effectue une traduction via un moteur donné avec une config fournie.
     * Méthode centralisée utilisée par ajax_test_api_key, ajax_test_translate et ajax_trigger_queue.
     *
     * @param string $engine_slug Slug du moteur.
     * @param array  $config      Configuration (api_key, model, etc.).
     * @param string $text        Texte à traduire.
     * @param string $source_lang Langue source (ex: en_US).
     * @param string $target_lang Langue cible (ex: fr_FR).
     * @return string|WP_Error    Texte traduit ou erreur.
     */
    private function do_engine_translate( $engine_slug, $config, $text, $source_lang, $target_lang ) {
        $api_source    = $this->convert_lang_code( $source_lang, $engine_slug );
        $api_target    = $this->convert_lang_code( $target_lang, $engine_slug );
        $error_message = '';
        $translated    = '';

        // Instructions par défaut pour les tests
        $ai_settings         = get_option( 'lingua_commerce_ai_settings', array() );
        $ai_tone             = isset( $ai_settings['ai_tone'] ) ? $ai_settings['ai_tone'] : 'professional';
        $custom_instructions = isset( $ai_settings['custom_instructions'] ) ? $ai_settings['custom_instructions'] : '';

        switch ( $engine_slug ) {

            case 'deepl':
                $api_key  = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $api_host = isset( $config['api_host'] ) ? $config['api_host'] : 'api.deepl.com';
                if ( empty( $api_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Clé API DeepL manquante.', 'lingua-commerce-ai' ) );
                }
                $is_free  = ( strpos( $api_key, 'fx' ) === 0 );
                $endpoint = $is_free ? 'https://api-free.deepl.com/v2/translate' : 'https://' . $api_host . '/v2/translate';
                $response = wp_remote_post( $endpoint, array(
                    'timeout' => 30,
                    'body'    => array(
                        'auth_key'    => $api_key,
                        'text'        => $text,
                        'source_lang' => strtoupper( substr( $api_source, 0, 2 ) ),
                        'target_lang' => strtoupper( substr( $api_target, 0, 2 ) ),
                    ),
                ) );
                if ( is_wp_error( $response ) ) {
                    return new WP_Error( 'request_error', $response->get_error_message() );
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['translations'][0]['text'] ) ) {
                    return $body['translations'][0]['text'];
                } elseif ( isset( $body['message'] ) ) {
                    return new WP_Error( 'api_error', $body['message'] );
                }
                return new WP_Error( 'invalid_response', __( 'Réponse DeepL invalide.', 'lingua-commerce-ai' ) );

            case 'yandex':
                $api_key   = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $folder_id = isset( $config['folder_id'] ) ? $config['folder_id'] : '';
                if ( empty( $api_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Clé API Yandex manquante.', 'lingua-commerce-ai' ) );
                }
                $yandex_body = array(
                    'sourceLanguageCode' => substr( $api_source, 0, 2 ),
                    'targetLanguageCode' => substr( $api_target, 0, 2 ),
                    'texts'             => array( $text ),
                    'format'            => 'HTML',
                );
                if ( ! empty( $folder_id ) ) {
                    $yandex_body['folderId'] = $folder_id;
                }
                $response = wp_remote_post( 'https://translate.api.cloud.yandex.net/translate/v2/translate', array(
                    'timeout' => 30,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type'  => 'application/json',
                    ),
                    'body'    => wp_json_encode( $yandex_body ),
                ) );
                if ( is_wp_error( $response ) ) {
                    return new WP_Error( 'request_error', $response->get_error_message() );
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['translations'][0]['text'] ) ) {
                    return $body['translations'][0]['text'];
                } elseif ( isset( $body['message'] ) ) {
                    return new WP_Error( 'api_error', $body['message'] );
                }
                return new WP_Error( 'invalid_response', __( 'Réponse Yandex invalide.', 'lingua-commerce-ai' ) );

            case 'baidu':
                $app_id  = isset( $config['app_id'] ) ? $config['app_id'] : '';
                $sec_key = isset( $config['secret_key'] ) ? $config['secret_key'] : '';
                if ( empty( $app_id ) || empty( $sec_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Identifiants Baidu manquants (App ID + Secret Key).', 'lingua-commerce-ai' ) );
                }
                $salt     = wp_rand( 10000, 99999 );
                $sign_str = $app_id . $text . $salt . $sec_key;
                $sign     = md5( $sign_str );
                $response = wp_remote_post( 'https://fanyi-api.baidu.com/api/trans/vip/translate', array(
                    'timeout' => 30,
                    'body'    => array(
                        'q'      => $text,
                        'from'   => substr( $api_source, 0, 2 ),
                        'to'     => substr( $api_target, 0, 2 ),
                        'appid'  => $app_id,
                        'salt'   => $salt,
                        'sign'   => $sign,
                    ),
                ) );
                if ( is_wp_error( $response ) ) {
                    return new WP_Error( 'request_error', $response->get_error_message() );
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['trans_result'][0]['dst'] ) ) {
                    return $body['trans_result'][0]['dst'];
                } elseif ( isset( $body['error_code'] ) ) {
                    return new WP_Error( 'api_error', sprintf( __( 'Erreur Baidu (code %s).', 'lingua-commerce-ai' ), $body['error_code'] ) );
                }
                return new WP_Error( 'invalid_response', __( 'Réponse Baidu invalide.', 'lingua-commerce-ai' ) );

            case 'microsoft':
                $api_key     = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $region      = isset( $config['region'] ) ? $config['region'] : 'global';
                $resource_id = isset( $config['resource_id'] ) ? $config['resource_id'] : '';
                if ( empty( $api_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Clé API Microsoft manquante.', 'lingua-commerce-ai' ) );
                }
                $ms_endpoint = 'https://api.cognitive.microsofttranslator.com/translate';
                $ms_params   = array(
                    'api-version' => '3.0',
                    'from'        => substr( $api_source, 0, 2 ),
                    'to'          => substr( $api_target, 0, 2 ),
                    'textType'    => 'html',
                );
                $ms_url   = add_query_arg( $ms_params, $ms_endpoint );
                $headers  = array(
                    'Ocp-Apim-Subscription-Key' => $api_key,
                    'Content-Type'              => 'application/json',
                );
                if ( ! empty( $resource_id ) ) {
                    $headers['Ocp-Apim-Subscription-Resource'] = $resource_id;
                }
                if ( $region && 'global' !== $region ) {
                    $headers['Ocp-Apim-Subscription-Region'] = $region;
                }
                $response = wp_remote_post( $ms_url, array(
                    'timeout' => 30,
                    'headers' => $headers,
                    'body'    => wp_json_encode( array( array( 'Text' => $text ) ) ),
                ) );
                if ( is_wp_error( $response ) ) {
                    return new WP_Error( 'request_error', $response->get_error_message() );
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body[0]['translations'][0]['text'] ) ) {
                    return $body[0]['translations'][0]['text'];
                } elseif ( isset( $body['error']['message'] ) ) {
                    return new WP_Error( 'api_error', $body['error']['message'] );
                }
                return new WP_Error( 'invalid_response', __( 'Réponse Microsoft invalide.', 'lingua-commerce-ai' ) );

            case 'google':
                $api_key = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $model   = isset( $config['model'] ) ? $config['model'] : 'gemini-1.5-flash';
                if ( empty( $api_key ) ) {
                    return new WP_Error( 'missing_key', __( 'Clé API Google manquante.', 'lingua-commerce-ai' ) );
                }
                $gemini_endpoint = sprintf(
                    'https://generativelanguage.googleapis.com/v1beta/models/%s:generateText?key=%s',
                    $model, $api_key
                );
                $prompt = $this->build_translation_prompt( $text, $source_lang, $target_lang, $ai_tone, $custom_instructions );
                $gemini_body = array(
                    'contents' => array(
                        array( 'parts' => array( array( 'text' => $prompt ) ) ),
                    ),
                    'generationConfig' => array( 'temperature' => 0.3, 'maxOutputTokens' => 8192 ),
                );
                $response = wp_remote_post( $gemini_endpoint, array(
                    'timeout' => 60,
                    'headers' => array( 'Content-Type' => 'application/json' ),
                    'body'    => wp_json_encode( $gemini_body ),
                ) );
                if ( is_wp_error( $response ) ) {
                    return new WP_Error( 'request_error', $response->get_error_message() );
                }
                $body = json_decode( wp_remote_retrieve_body( $response ), true );
                if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
                    return $this->clean_ai_output( trim( $body['candidates'][0]['content']['parts'][0]['text'] ) );
                } elseif ( isset( $body['error']['message'] ) ) {
                    return new WP_Error( 'api_error', $body['error']['message'] );
                }
                return new WP_Error( 'invalid_response', __( 'Réponse Google Gemini invalide.', 'lingua-commerce-ai' ) );

            case 'openrouter':
            case 'deepseek':
            case 'mistral':
            case 'openai':
            case 'zai':
                $api_key = isset( $config['api_key'] ) ? $config['api_key'] : '';
                $model   = isset( $config['model'] ) ? $config['model'] : $this->get_default_llm_model( $engine_slug );
                // Z.AI est gratuit — pas de clé obligatoire
                if ( empty( $api_key ) && 'zai' !== $engine_slug ) {
                    return new WP_Error( 'missing_key', sprintf( __( 'Clé API %s manquante.', 'lingua-commerce-ai' ), ucfirst( $engine_slug ) ) );
                }
                $chat_endpoint = $this->get_llm_endpoint( $engine_slug );
                $prompt        = $this->build_translation_prompt( $text, $source_lang, $target_lang, $ai_tone, $custom_instructions );
                $chat_body     = array(
                    'model'       => $model,
                    'messages'    => array(
                        array( 'role' => 'system', 'content' => $this->get_system_prompt( $ai_tone, $custom_instructions ) ),
                        array( 'role' => 'user',   'content' => $prompt ),
                    ),
                    'temperature' => 0.3,
                    'max_tokens'  => 4096,
                );
                $chat_headers = array( 'Content-Type' => 'application/json' );
                if ( ! empty( $api_key ) && 'free' !== $api_key ) {
                    $chat_headers['Authorization'] = 'Bearer ' . $api_key;
                }
                if ( in_array( $engine_slug, array( 'openrouter', 'zai' ), true ) ) {
                    $chat_headers['HTTP-Referer'] = home_url();
                    $chat_headers['X-Title']      = get_bloginfo( 'name' ) . ' - LinguaCommerce AI';
                }
                $response = wp_remote_post( $chat_endpoint, array(
                    'timeout' => 60,
                    'headers' => $chat_headers,
                    'body'    => wp_json_encode( $chat_body ),
                ) );
                if ( is_wp_error( $response ) ) {
                    // Log l'erreur de connexion pour le débogage
                    error_log( sprintf( '[Lingua AI] Erreur connexion %s : %s', $engine_slug, $response->get_error_message() ) );
                    return new WP_Error( 'request_error', sprintf(
                        /* translators: 1: Engine name 2: Error message */
                        __( 'Erreur de connexion à %1$s : %2$s', 'lingua-commerce-ai' ),
                        ucfirst( $engine_slug ),
                        $response->get_error_message()
                    ) );
                }
                $http_code = wp_remote_retrieve_response_code( $response );
                $raw_body  = wp_remote_retrieve_body( $response );
                $body = json_decode( $raw_body, true );

                // Log la réponse HTTP si elle n'est pas 200
                if ( 200 !== $http_code ) {
                    error_log( sprintf( '[Lingua AI] %s a retourné HTTP %d : %s', $engine_slug, $http_code, $raw_body ) );
                }

                if ( isset( $body['choices'][0]['message']['content'] ) ) {
                    return $this->clean_ai_output( trim( $body['choices'][0]['message']['content'] ) );
                } elseif ( isset( $body['error']['message'] ) ) {
                    return new WP_Error( 'api_error', sprintf(
                        /* translators: 1: Engine name 2: API error message */
                        __( 'Erreur API %1$s : %2$s', 'lingua-commerce-ai' ),
                        ucfirst( $engine_slug ),
                        $body['error']['message']
                    ) );
                } elseif ( isset( $body['message'] ) ) {
                    return new WP_Error( 'api_error', sprintf(
                        /* translators: 1: Engine name 2: API error message */
                        __( 'Erreur API %1$s : %2$s', 'lingua-commerce-ai' ),
                        ucfirst( $engine_slug ),
                        $body['message']
                    ) );
                }
                // Log la réponse brute pour le débogage si le format est inattendu
                error_log( sprintf( '[Lingua AI] Réponse %s inattendue (HTTP %d) : %s', $engine_slug, $http_code, substr( $raw_body, 0, 500 ) ) );
                return new WP_Error( 'invalid_response', sprintf(
                    /* translators: 1: Engine name 2: HTTP status code */
                    __( 'Réponse %1$s invalide (HTTP %2$d). Vérifiez les logs WordPress pour plus de détails.', 'lingua-commerce-ai' ),
                    ucfirst( $engine_slug ),
                    $http_code
                ) );

            default:
                return new WP_Error( 'unsupported', sprintf( __( 'Moteur "%s" non supporté.', 'lingua-commerce-ai' ), $engine_slug ) );
        }
    }

    /**
     * Récupère la config d'un moteur (depuis les réglages sauvegardés ou la table lingua_ai_engines)
     *
     * @param string $engine_slug Slug du moteur.
     * @return array Configuration du moteur.
     */
    private function get_engine_config( $engine_slug ) {
        $config = array();

        // 1. Chercher dans les réglages AI (lingua_commerce_ai_ai_settings)
        $ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );
        $key_field   = 'api_key_' . $engine_slug;
        if ( isset( $ai_settings[ $key_field ] ) && ! empty( $ai_settings[ $key_field ] ) ) {
            $config['api_key'] = $ai_settings[ $key_field ];
        }

        // 2. Chercher dans la table lingua_ai_engines
        global $wpdb;
        $engine_row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->table_engines} WHERE engine_name = %s", $engine_slug )
        );
        if ( $engine_row ) {
            $config_json = isset( $engine_row->engine_config ) ? $engine_row->engine_config : '';
            if ( empty( $config_json ) && isset( $engine_row->settings ) ) {
                $config_json = $engine_row->settings;
                $maybe_unserialized = @maybe_unserialize( $config_json );
                if ( is_array( $maybe_unserialized ) ) {
                    $db_config = $maybe_unserialized;
                } else {
                    $db_config = json_decode( $config_json, true );
                }
            } else {
                $db_config = json_decode( $config_json, true );
            }
            if ( is_array( $db_config ) ) {
                $config = array_merge( $db_config, $config );
            }
            if ( isset( $engine_row->api_key ) && ! empty( $engine_row->api_key ) && ! isset( $config['api_key'] ) ) {
                $config['api_key'] = $engine_row->api_key;
            }
        }

        // 3. Cas spéciaux
        if ( 'zai' === $engine_slug && empty( $config['api_key'] ) ) {
            $config['api_key'] = 'free';
        }
        if ( 'baidu' === $engine_slug ) {
            if ( empty( $config['app_id'] ) && isset( $ai_settings['api_key_baidu_app_id'] ) ) {
                $config['app_id'] = $ai_settings['api_key_baidu_app_id'];
            }
            if ( empty( $config['secret_key'] ) && isset( $ai_settings['api_key_baidu_secret'] ) ) {
                $config['secret_key'] = $ai_settings['api_key_baidu_secret'];
            }
        }
        if ( 'microsoft' === $engine_slug ) {
            if ( empty( $config['region'] ) && isset( $ai_settings['api_key_microsoft_region'] ) ) {
                $config['region'] = $ai_settings['api_key_microsoft_region'];
            }
            if ( empty( $config['resource_id'] ) && isset( $ai_settings['api_key_microsoft_resource'] ) ) {
                $config['resource_id'] = $ai_settings['api_key_microsoft_resource'];
            }
        }
        if ( 'yandex' === $engine_slug ) {
            if ( empty( $config['folder_id'] ) && isset( $ai_settings['api_key_yandex_folder'] ) ) {
                $config['folder_id'] = $ai_settings['api_key_yandex_folder'];
            }
        }

        return $config;
    }

    /**
     * Synchronise un moteur (slug + clé API) dans la table lingua_ai_engines
     *
     * @param string $slug    Slug du moteur.
     * @param string $api_key Clé API.
     */
    private function sync_engine_to_db( $slug, $api_key ) {
        global $wpdb;

        $existing = $wpdb->get_row(
            $wpdb->prepare( "SELECT id FROM {$this->table_engines} WHERE engine_name = %s", $slug )
        );

        $config = array( 'api_key' => $api_key );

        // Garder les configs existantes
        if ( $existing ) {
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT engine_config FROM {$this->table_engines} WHERE id = %d", $existing->id ) );
            if ( $row && ! empty( $row->engine_config ) ) {
                $existing_config = json_decode( $row->engine_config, true );
                if ( is_array( $existing_config ) ) {
                    $config = array_merge( $existing_config, $config );
                }
            }
        }

        $config_json = wp_json_encode( $config );
        $status      = ( 'zai' === $slug || ! empty( $api_key ) ) ? 'active' : 'inactive';

        if ( $existing ) {
            $wpdb->update(
                $this->table_engines,
                array(
                    'engine_config' => $config_json,
                    'status'        => $status,
                    'last_updated'  => current_time( 'mysql' ),
                ),
                array( 'id' => $existing->id ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $wpdb->insert(
                $this->table_engines,
                array(
                    'engine_name'   => $slug,
                    'engine_config' => $config_json,
                    'status'        => $status,
                    'created_at'    => current_time( 'mysql' ),
                    'last_updated'  => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s' )
            );
        }
    }

    // =========================================================================
    // MÉTHODES UTILITAIRES PRIVÉES
    // =========================================================================

    /**
     * Construit le prompt de traduction pour les moteurs LLM
     *
     * @param string $text               Texte à traduire.
     * @param string $source_lang        Langue source (ex: en_US).
     * @param string $target_lang        Langue cible (ex: fr_FR).
     * @param string $tone               Ton de la traduction.
     * @param string $custom_instructions Instructions personnalisées.
     * @return string Prompt complet.
     */
    private function build_translation_prompt( $text, $source_lang, $target_lang, $tone = 'professional', $custom_instructions = '' ) {
        $source_name = $this->get_language_name( $source_lang );
        $target_name = $this->get_language_name( $target_lang );

        $prompt = sprintf(
            /* translators: 1: Source language name, 2: Target language name */
            __( 'Translate the following text from %1$s to %2$s.', 'lingua-commerce-ai' ),
            $source_name,
            $target_name
        );

        // Ajout du ton
        $tone_instructions = array(
            'professional' => __( 'Use a professional and business-appropriate tone.', 'lingua-commerce-ai' ),
            'casual'       => __( 'Use a casual and friendly tone.', 'lingua-commerce-ai' ),
            'formal'       => __( 'Use a formal and academic tone.', 'lingua-commerce-ai' ),
            'creative'     => __( 'Use a creative and engaging tone.', 'lingua-commerce-ai' ),
            'technical'    => __( 'Use a precise and technical tone, preserving terminology accurately.', 'lingua-commerce-ai' ),
        );

        if ( isset( $tone_instructions[ $tone ] ) ) {
            $prompt .= ' ' . $tone_instructions[ $tone ];
        }

        // Instructions personnalisées
        if ( ! empty( $custom_instructions ) ) {
            $prompt .= ' ' . sprintf(
                /* translators: %s: Custom instructions */
                __( 'Additional instructions: %s', 'lingua-commerce-ai' ),
                $custom_instructions
            );
        }

        $prompt .= "\n\n" . __( 'IMPORTANT: Return ONLY the translated text, without any explanations, comments, or formatting markers.', 'lingua-commerce-ai' );
        $prompt .= "\n\n" . __( 'Text to translate:', 'lingua-commerce-ai' ) . "\n" . $text;

        return $prompt;
    }

    /**
     * Retourne le prompt système pour les moteurs LLM
     *
     * @param string $tone               Ton souhaité.
     * @param string $custom_instructions Instructions personnalisées.
     * @return string Prompt système.
     */
    private function get_system_prompt( $tone = 'professional', $custom_instructions = '' ) {
        $system = __( 'You are a professional translator for an e-commerce website. Your task is to translate content accurately while preserving HTML tags, shortcodes, and placeholders (like %s, {variable}). You maintain the formatting and structure of the original text.', 'lingua-commerce-ai' );

        if ( ! empty( $custom_instructions ) ) {
            $system .= ' ' . sprintf(
                /* translators: %s: Custom instructions */
                __( 'Additional context: %s', 'lingua-commerce-ai' ),
                $custom_instructions
            );
        }

        return $system;
    }

    /**
     * Nettoie la sortie des moteurs IA (supprime les blocs de code markdown)
     *
     * @param string $text Texte produit par l'IA.
     * @return string Texte nettoyé.
     */
    private function clean_ai_output( $text ) {
        // Supprimer les blocs de code markdown (```html ... ```)
        $text = preg_replace( '/^```(?:html|php|text)?\s*\n?/i', '', $text );
        $text = preg_replace( '/\n?```\s*$/i', '', $text );

        // Supprimer les balises <translate> ou similaires que l'IA pourrait ajouter
        $text = preg_replace( '/<\/?translate[^>]*>/i', '', $text );

        return trim( $text );
    }

    /**
     * Convertit un code langue WordPress (en_US) vers le format attendu par l'API
     *
     * @param string $wp_lang  Code langue WordPress (ex: en_US, fr_FR).
     * @param string $engine   Slug du moteur.
     * @return string Code langue converti.
     */
    private function convert_lang_code( $wp_lang, $engine ) {
        // Table de correspondance pour les cas spéciaux
        $mapping = array(
            'zh_CN' => 'zh',   // Chinois simplifié
            'zh_TW' => 'zh-TW', // Chinois traditionnel
            'pt_BR' => 'pt-BR', // Portugais brésilien
            'en_US' => 'en',
            'fr_FR' => 'fr',
            'de_DE' => 'de',
            'es_ES' => 'es',
            'it_IT' => 'it',
            'ja_JA' => 'ja',
            'ko_KR' => 'ko',
            'ru_RU' => 'ru',
            'ar_AR' => 'ar',
            'nl_NL' => 'nl',
            'pl_PL' => 'pl',
            'tr_TR' => 'tr',
            'sv_SE' => 'sv',
            'da_DK' => 'da',
            'no_NO' => 'no',
            'fi_FI' => 'fi',
            'cs_CZ' => 'cs',
            'el_GR' => 'el',
            'hu_HU' => 'hu',
            'ro_RO' => 'ro',
            'bg_BG' => 'bg',
            'uk_UA' => 'uk',
            'id_ID' => 'id',
            'th_TH' => 'th',
            'vi_VN' => 'vi',
        );

        // DeepL utilise des codes spéciaux pour l'anglais
        if ( 'deepl' === $engine ) {
            if ( 'en_US' === $wp_lang || 'en_GB' === $wp_lang ) {
                return 'EN'; // DeepL accepte EN-US et EN-GB aussi
            }
            if ( 'pt_BR' === $wp_lang ) {
                return 'PT-BR';
            }
        }

        // Microsoft utilise des codes ISO 639-1
        if ( 'microsoft' === $engine ) {
            if ( 'zh_CN' === $wp_lang ) return 'zh-Hans';
            if ( 'zh_TW' === $wp_lang ) return 'zh-Hant';
        }

        if ( isset( $mapping[ $wp_lang ] ) ) {
            return $mapping[ $wp_lang ];
        }

        // Fallback : extraire le code avant le underscore
        $parts = explode( '_', $wp_lang );
        return $parts[0];
    }

    /**
     * Retourne le nom humain d'une langue à partir de son code
     *
     * @param string $lang_code Code langue (ex: en_US).
     * @return string Nom de la langue.
     */
    private function get_language_name( $lang_code ) {
        $names = array(
            'en_US' => 'English',
            'fr_FR' => 'French',
            'de_DE' => 'German',
            'es_ES' => 'Spanish',
            'it_IT' => 'Italian',
            'pt_BR' => 'Portuguese (Brazil)',
            'nl_NL' => 'Dutch',
            'ru_RU' => 'Russian',
            'ja_JA' => 'Japanese',
            'ko_KR' => 'Korean',
            'zh_CN' => 'Chinese (Simplified)',
            'zh_TW' => 'Chinese (Traditional)',
            'ar_AR' => 'Arabic',
            'pl_PL' => 'Polish',
            'tr_TR' => 'Turkish',
            'sv_SE' => 'Swedish',
            'da_DK' => 'Danish',
            'no_NO' => 'Norwegian',
            'fi_FI' => 'Finnish',
            'cs_CZ' => 'Czech',
            'el_GR' => 'Greek',
            'hu_HU' => 'Hungarian',
            'ro_RO' => 'Romanian',
            'bg_BG' => 'Bulgarian',
            'uk_UA' => 'Ukrainian',
            'id_ID' => 'Indonesian',
            'th_TH' => 'Thai',
            'vi_VN' => 'Vietnamese',
        );

        return isset( $names[ $lang_code ] ) ? $names[ $lang_code ] : $lang_code;
    }

    /**
     * Retourne l'endpoint API pour un moteur LLM donné
     *
     * @param string $engine Slug du moteur LLM.
     * @return string URL de l'endpoint.
     */
    private function get_llm_endpoint( $engine ) {
        $endpoints = array(
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            'deepseek'   => 'https://api.deepseek.com/v1/chat/completions',
            'mistral'    => 'https://api.mistral.ai/v1/chat/completions',
            'openai'     => 'https://api.openai.com/v1/chat/completions',
            'zai'        => 'https://api.zai.chat/v1/chat/completions',
        );

        return isset( $endpoints[ $engine ] ) ? $endpoints[ $engine ] : '';
    }

    /**
     * Retourne le modèle par défaut pour un moteur LLM
     *
     * @param string $engine Slug du moteur LLM.
     * @return string Nom du modèle par défaut.
     */
    private function get_default_llm_model( $engine ) {
        $models = array(
            'openrouter' => 'openai/gpt-3.5-turbo',
            'deepseek'   => 'deepseek-chat',
            'mistral'    => 'mistral-small-latest',
            'openai'     => 'gpt-3.5-turbo',
            'zai'        => 'glm-4-flash',
        );

        return isset( $models[ $engine ] ) ? $models[ $engine ] : 'gpt-3.5-turbo';
    }

    /**
     * Journalise un événement dans la table des logs
     *
     * @param string $type        Type d'événement (success, error, warning, info).
     * @param string $engine      Nom du moteur concerné.
     * @param string $message     Message descriptif.
     * @param string $object_id   ID de l'objet (optionnel).
     * @param string $object_type Type de l'objet (optionnel).
     * @param string $field_key   Clé du champ (optionnel).
     */
    private function log_event( $type, $engine, $message, $object_id = '', $object_type = '', $field_key = '' ) {
        global $wpdb;

        // S'assurer que la table existe
        $this->ensure_logs_table();

        $wpdb->insert(
            $this->table_logs,
            array(
                'type'        => sanitize_text_field( $type ),
                'engine'      => sanitize_text_field( $engine ),
                'message'     => sanitize_text_field( $message ),
                'object_id'   => sanitize_text_field( (string) $object_id ),
                'object_type' => sanitize_text_field( $object_type ),
                'field_key'   => sanitize_text_field( $field_key ),
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        // Limiter le nombre de logs (conserver les 10000 plus récents)
        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_logs}" );
        if ( $count > 10000 ) {
            $wpdb->query(
                "DELETE FROM {$this->table_logs}
                 WHERE id NOT IN (
                     SELECT id FROM (
                         SELECT id FROM {$this->table_logs}
                         ORDER BY created_at DESC
                         LIMIT 10000
                     ) AS keep_ids
                 )"
            );
        }
    }

    /**
     * Crée la table des logs si elle n'existe pas
     */
    private function ensure_logs_table() {
        global $wpdb;

        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_logs}'" ) === $this->table_logs ) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL DEFAULT 'info',
            engine varchar(50) NOT NULL DEFAULT '',
            message text NOT NULL,
            object_id varchar(50) NOT NULL DEFAULT '',
            object_type varchar(50) NOT NULL DEFAULT '',
            field_key varchar(100) NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY type (type),
            KEY engine (engine),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}
