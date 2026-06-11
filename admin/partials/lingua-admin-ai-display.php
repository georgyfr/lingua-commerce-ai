<?php
/**
 * Vue pour la page IA & Automatisation
 * 8 moteurs (OpenRouter, DeepSeek, DeepL, Google, Mistral, Yandex, Baidu, Microsoft)
 * Gestion de la file d'attente, journaux
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) { die; }

// Récupération des réglages IA
$ai_settings = get_option( 'lingua_commerce_ai_ai_settings', array() );

$primary_engine   = isset( $ai_settings['primary_engine'] ) ? $ai_settings['primary_engine'] : 'openrouter';
$fallback_engine  = isset( $ai_settings['fallback_engine'] ) ? $ai_settings['fallback_engine'] : 'deepl';
$auto_translate   = isset( $ai_settings['auto_translate'] ) ? $ai_settings['auto_translate'] : 1;
$quality_check    = isset( $ai_settings['quality_check'] ) ? $ai_settings['quality_check'] : 0;
$batch_size       = isset( $ai_settings['batch_size'] ) ? $ai_settings['batch_size'] : 5;
$retry_count      = isset( $ai_settings['retry_count'] ) ? $ai_settings['retry_count'] : 3;
$glossary_enabled = isset( $ai_settings['glossary_enabled'] ) ? $ai_settings['glossary_enabled'] : 0;
$custom_glossary  = isset( $ai_settings['custom_glossary'] ) ? $ai_settings['custom_glossary'] : '';
$rate_limit       = isset( $ai_settings['rate_limit'] ) ? $ai_settings['rate_limit'] : 60;

// Clés API
$api_keys = array(
    'zai'         => isset( $ai_settings['api_key_zai'] ) ? $ai_settings['api_key_zai'] : '',
    'openrouter'  => isset( $ai_settings['api_key_openrouter'] ) ? $ai_settings['api_key_openrouter'] : '',
    'deepseek'    => isset( $ai_settings['api_key_deepseek'] ) ? $ai_settings['api_key_deepseek'] : '',
    'deepl'       => isset( $ai_settings['api_key_deepl'] ) ? $ai_settings['api_key_deepl'] : '',
    'google'      => isset( $ai_settings['api_key_google'] ) ? $ai_settings['api_key_google'] : '',
    'mistral'     => isset( $ai_settings['api_key_mistral'] ) ? $ai_settings['api_key_mistral'] : '',
    'yandex'      => isset( $ai_settings['api_key_yandex'] ) ? $ai_settings['api_key_yandex'] : '',
    'baidu'       => isset( $ai_settings['api_key_baidu'] ) ? $ai_settings['api_key_baidu'] : '',
    'microsoft'   => isset( $ai_settings['api_key_microsoft'] ) ? $ai_settings['api_key_microsoft'] : '',
);

// Définition des 9 moteurs
$engines = array(
    'zai' => array(
        'name'        => 'Z.AI',
        'icon'        => '⚡',
        'desc'        => 'Moteur IA par Z.ai — plan gratuit avec clé API, traduction illimitée',
        'color'       => '#10b981',
        'gradient'    => 'linear-gradient(135deg, #059669, #34d399)',
        'type'        => 'llm',
        'lang_support' => '55+ langues',
        'quality'     => 85,
        'speed'       => 90,
        'cost'        => 'Gratuit',
        'docs_url'    => 'https://z.ai/docs',
        'free'        => true,
    ),
    'openrouter' => array(
        'name'        => 'OpenRouter',
        'icon'        => '🤖',
        'desc'        => 'Accès unifié aux meilleurs modèles LLM (GPT-4, Claude, Llama, etc.)',
        'color'       => '#6366f1',
        'gradient'    => 'linear-gradient(135deg, #4f46e5, #7c3aed)',
        'type'        => 'llm',
        'lang_support' => '100+ langues',
        'quality'     => 95,
        'speed'       => 80,
        'cost'        => 'Variable',
        'docs_url'    => 'https://openrouter.ai/docs',
    ),
    'deepseek' => array(
        'name'        => 'DeepSeek',
        'icon'        => '🧠',
        'desc'        => 'Modèle IA chinois open-source performant et économique',
        'color'       => '#0ea5e9',
        'gradient'    => 'linear-gradient(135deg, #0284c7, #06b6d4)',
        'type'        => 'llm',
        'lang_support' => '50+ langues',
        'quality'     => 88,
        'speed'       => 85,
        'cost'        => 'Très bas',
        'docs_url'    => 'https://platform.deepseek.com/docs',
    ),
    'deepl' => array(
        'name'        => 'DeepL',
        'icon'        => '📝',
        'desc'        => 'Traduction neuronale de référence, qualité exceptionnelle',
        'color'       => '#047857',
        'gradient'    => 'linear-gradient(135deg, #047857, #10b981)',
        'type'        => 'translation',
        'lang_support' => '30+ langues',
        'quality'     => 98,
        'speed'       => 92,
        'cost'        => 'Moyen',
        'docs_url'    => 'https://www.deepl.com/docs-api',
    ),
    'google' => array(
        'name'        => 'Google Translate',
        'icon'        => '🌐',
        'desc'        => 'Google Cloud Translation — couverture linguistique maximale',
        'color'       => '#ea4335',
        'gradient'    => 'linear-gradient(135deg, #ea4335, #fbbc04)',
        'type'        => 'translation',
        'lang_support' => '130+ langues',
        'quality'     => 82,
        'speed'       => 95,
        'cost'        => 'Bas',
        'docs_url'    => 'https://cloud.google.com/translate/docs',
    ),
    'mistral' => array(
        'name'        => 'Mistral AI',
        'icon'        => '🌀',
        'desc'        => 'LLM européen haute performance, conforme RGPD',
        'color'       => '#f97316',
        'gradient'    => 'linear-gradient(135deg, #ea580c, #f59e0b)',
        'type'        => 'llm',
        'lang_support' => '40+ langues',
        'quality'     => 90,
        'speed'       => 88,
        'cost'        => 'Moyen',
        'docs_url'    => 'https://docs.mistral.ai',
    ),
    'yandex' => array(
        'name'        => 'Yandex Translate',
        'icon'        => '🔴',
        'desc'        => 'Excellente pour les langues slaves et l\'Europe de l\'Est',
        'color'       => '#dc2626',
        'gradient'    => 'linear-gradient(135deg, #dc2626, #f87171)',
        'type'        => 'translation',
        'lang_support' => '90+ langues',
        'quality'     => 78,
        'speed'       => 90,
        'cost'        => 'Bas',
        'docs_url'    => 'https://cloud.yandex.com/docs/translate',
    ),
    'baidu' => array(
        'name'        => 'Baidu Translate',
        'icon'        => '🔵',
        'desc'        => 'Leader pour le chinois et les langues asiatiques',
        'color'       => '#2563eb',
        'gradient'    => 'linear-gradient(135deg, #1d4ed8, #3b82f6)',
        'type'        => 'translation',
        'lang_support' => '200+ langues',
        'quality'     => 80,
        'speed'       => 88,
        'cost'        => 'Bas',
        'docs_url'    => 'https://fanyi-api.baidu.com/doc',
    ),
    'microsoft' => array(
        'name'        => 'Microsoft Translator',
        'icon'        => '🔷',
        'desc'        => 'Azure Cognitive Services — fiabilité entreprise',
        'color'       => '#0078d4',
        'gradient'    => 'linear-gradient(135deg, #0078d4, #00bcf2)',
        'type'        => 'translation',
        'lang_support' => '100+ langues',
        'quality'     => 85,
        'speed'       => 90,
        'cost'        => 'Moyen',
        'docs_url'    => 'https://learn.microsoft.com/azure/ai-services/translator',
    ),
);

// Statistiques de la file d'attente
global $wpdb;
$table_queue = $wpdb->prefix . 'lingua_queue';
$queue_stats = array( 'pending' => 0, 'processing' => 0, 'completed' => 0, 'failed' => 0 );
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_queue}'" ) === $table_queue ) {
    $queue_stats['pending']    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_queue} WHERE status = 'pending'" );
    $queue_stats['processing'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_queue} WHERE status = 'processing'" );
    $queue_stats['completed']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_queue} WHERE status = 'completed'" );
    $queue_stats['failed']     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_queue} WHERE status = 'failed'" );
}

// Journaux récents
$recent_logs = array();
$log_file = plugin_dir_path( dirname( __FILE__ ) ) . 'logs/ai-translations.log';
if ( file_exists( $log_file ) ) {
    $log_content = file_get_contents( $log_file );
    $log_lines = array_reverse( explode( "\n", trim( $log_content ) ) );
    $recent_logs = array_slice( $log_lines, 0, 50 );
}

?>

<style>
    .lingua-ai-page { max-width: 1200px; }
    .lingua-ai-page h1 { margin-bottom: 5px; }
    .lingua-ai-page .ai-subtitle { color: #666; margin-bottom: 25px; font-size: 14px; }

    /* ENGINE CARDS */
    .lingua-engines-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .lingua-engine-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 18px;
        position: relative;
        transition: all 0.3s;
        cursor: pointer;
        overflow: hidden;
    }

    .lingua-engine-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--engine-gradient);
    }

    .lingua-engine-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }

    .lingua-engine-card.is-primary {
        border-color: var(--engine-color);
        box-shadow: 0 0 0 2px var(--engine-color);
    }

    .lingua-engine-card .engine-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .lingua-engine-card .engine-icon {
        font-size: 28px;
    }

    .lingua-engine-card .engine-name {
        font-size: 15px;
        font-weight: 700;
    }

    .lingua-engine-card .engine-type {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2px 8px;
        border-radius: 10px;
        background: #f0f0f0;
        color: #666;
    }

    .lingua-engine-card .engine-type.llm { background: #ede9fe; color: #6d28d9; }
    .lingua-engine-card .engine-type.translation { background: #d1fae5; color: #047857; }

    .lingua-engine-card .engine-desc {
        font-size: 12px;
        color: #666;
        margin-bottom: 12px;
        line-height: 1.4;
    }

    .lingua-engine-card .engine-badges {
        display: flex;
        gap: 6px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }

    .lingua-engine-card .engine-badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 10px;
        background: #f6f7f7;
        color: #555;
    }

    .lingua-engine-card .engine-meter {
        margin-bottom: 6px;
    }

    .lingua-engine-card .engine-meter-label {
        font-size: 11px;
        color: #888;
        display: flex;
        justify-content: space-between;
        margin-bottom: 3px;
    }

    .lingua-engine-card .engine-meter-track {
        height: 4px;
        background: #eee;
        border-radius: 2px;
        overflow: hidden;
    }

    .lingua-engine-card .engine-meter-fill {
        height: 100%;
        border-radius: 2px;
        transition: width 0.8s ease;
    }

    .lingua-engine-card .engine-primary-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 600;
        background: #2271b1;
        color: #fff;
    }

    .lingua-engine-card .engine-key-status {
        margin-top: 8px;
        font-size: 11px;
    }

    .lingua-engine-card .engine-key-status .key-set { color: #00a32a; }
    .lingua-engine-card .engine-key-status .key-missing { color: #d63638; }

    /* TWO COL LAYOUT */
    .lingua-ai-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .lingua-ai-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
    }

    .lingua-ai-card h2 {
        margin: 0 0 15px 0;
        font-size: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    /* QUEUE STATS */
    .lingua-queue-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }

    .lingua-queue-stat {
        text-align: center;
        padding: 15px;
        border-radius: 8px;
    }

    .lingua-queue-stat .stat-value {
        font-size: 24px;
        font-weight: 700;
    }

    .lingua-queue-stat .stat-label {
        font-size: 11px;
        text-transform: uppercase;
        color: #666;
        margin-top: 4px;
    }

    .lingua-queue-stat.stat-pending { background: #fff8e5; }
    .lingua-queue-stat.stat-pending .stat-value { color: #dba617; }
    .lingua-queue-stat.stat-processing { background: #f0f6fc; }
    .lingua-queue-stat.stat-processing .stat-value { color: #2271b1; }
    .lingua-queue-stat.stat-completed { background: #f1f8f1; }
    .lingua-queue-stat.stat-completed .stat-value { color: #00a32a; }
    .lingua-queue-stat.stat-failed { background: #fcf0f1; }
    .lingua-queue-stat.stat-failed .stat-value { color: #d63638; }

    /* LOG CONSOLE */
    .lingua-log-console {
        background: #1a1d23;
        color: #e1e4e8;
        padding: 15px;
        border-radius: 6px;
        font-family: 'Fira Code', 'Consolas', monospace;
        font-size: 12px;
        max-height: 300px;
        overflow-y: auto;
        line-height: 1.6;
    }

    .lingua-log-console .log-line { margin-bottom: 2px; }
    .lingua-log-console .log-time { color: #8b949e; }
    .lingua-log-console .log-info { color: #58a6ff; }
    .lingua-log-console .log-success { color: #3fb950; }
    .lingua-log-console .log-warning { color: #d29922; }
    .lingua-log-console .log-error { color: #f85149; }

    /* TOGGLE SWITCH */
    .lingua-toggle-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #eee;
    }

    .lingua-toggle-row:last-child { border-bottom: none; }

    .lingua-toggle-row label {
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
    }

    .lingua-toggle-row .description {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }

    .lingua-toggle-switch {
        position: relative;
        width: 44px;
        height: 22px;
        flex-shrink: 0;
    }

    .lingua-toggle-switch input { position: absolute; opacity: 0; width: 44px; height: 22px; cursor: pointer; z-index: 1; margin: 0; }

    .lingua-toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        border-radius: 22px;
        transition: 0.3s;
    }

    .lingua-toggle-slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: #fff;
        border-radius: 50%;
        transition: 0.3s;
    }

    .lingua-toggle-switch input:checked + .lingua-toggle-slider,
    .lingua-toggle-switch input:checked ~ .lingua-toggle-slider { background-color: #2271b1; }
    .lingua-toggle-switch input:checked + .lingua-toggle-slider:before,
    .lingua-toggle-switch input:checked ~ .lingua-toggle-slider:before { transform: translateX(22px); }

    /* API KEY INPUT */
    .lingua-api-key-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .lingua-api-key-row .key-engine-icon { font-size: 20px; min-width: 28px; }
    .lingua-api-key-row .key-engine-name { font-size: 13px; font-weight: 600; min-width: 100px; }

    .lingua-api-key-row input[type="password"],
    .lingua-api-key-row input[type="text"] {
        flex: 1;
        font-size: 12px;
        font-family: monospace;
    }

    .lingua-api-key-row .key-toggle-vis {
        cursor: pointer;
        font-size: 16px;
        color: #999;
        padding: 4px;
    }

    .lingua-api-key-row .key-toggle-vis:hover { color: #2271b1; }

    @media (max-width: 1200px) {
        .lingua-engines-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 782px) {
        .lingua-engines-grid { grid-template-columns: 1fr; }
        .lingua-ai-grid { grid-template-columns: 1fr; }
        .lingua-queue-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="wrap lingua-ai-page">
    <h1>🤖 IA & Automatisation</h1>
    <p class="ai-subtitle">Configurez les moteurs de traduction IA, gérez la file d'attente et surveillez les journaux.</p>

    <!-- ENGINE CARDS WITH INLINE TRANSLATION TEST -->
    <?php
    // Langues pour le menu déroulant
    $test_languages = array(
        'fr_FR' => 'Français', 'es_ES' => 'Espagnol', 'de_DE' => 'Allemand',
        'it_IT' => 'Italien', 'pt_BR' => 'Portugais (Brésil)', 'nl_NL' => 'Néerlandais',
        'ru_RU' => 'Russe', 'ja_JA' => 'Japonais', 'ko_KR' => 'Coréen',
        'zh_CN' => 'Chinois (simplifié)', 'ar_AR' => 'Arabe', 'tr_TR' => 'Turc',
        'pl_PL' => 'Polonais', 'sv_SE' => 'Suédois', 'da_DK' => 'Danois',
        'fi_FI' => 'Finnois', 'el_GR' => 'Grec', 'cs_CZ' => 'Tchèque',
        'ro_RO' => 'Roumain', 'hu_HU' => 'Hongrois', 'bg_BG' => 'Bulgare',
        'uk_UA' => 'Ukrainien', 'th_TH' => 'Thaï', 'vi_VN' => 'Vietnamien',
        'id_ID' => 'Indonésien', 'en_US' => 'Anglais', 'en_GB' => 'Anglais (UK)',
    );
    ?>
    <div class="lingua-engines-grid">
        <?php foreach ( $engines as $slug => $engine ) :
            $is_primary = ( $primary_engine === $slug );
            $key_set = ! empty( $api_keys[ $slug ] );
            $can_test = $key_set;
        ?>
            <div class="lingua-engine-card <?php echo $is_primary ? 'is-primary' : ''; ?>"
                 style="--engine-color: <?php echo esc_attr( $engine['color'] ); ?>; --engine-gradient: <?php echo esc_attr( $engine['gradient'] ); ?>"
                 data-engine="<?php echo esc_attr( $slug ); ?>">
                <?php if ( $is_primary ) : ?>
                    <span class="engine-primary-badge">⭐ Moteur principal</span>
                <?php endif; ?>
                <div class="engine-header">
                    <span class="engine-icon"><?php echo esc_html( $engine['icon'] ); ?></span>
                    <div>
                        <div class="engine-name"><?php echo esc_html( $engine['name'] ); ?></div>
                        <span class="engine-type <?php echo esc_attr( $engine['type'] ); ?>">
                            <?php echo $engine['type'] === 'llm' ? 'LLM' : 'Traduction'; ?>
                        </span>
                    </div>
                </div>
                <div class="engine-desc"><?php echo esc_html( $engine['desc'] ); ?></div>
                <div class="engine-badges">
                    <span class="engine-badge">🗣️ <?php echo esc_html( $engine['lang_support'] ); ?></span>
                    <span class="engine-badge">💰 <?php echo esc_html( $engine['cost'] ); ?></span>
                    <?php if ( isset( $engine['free'] ) && $engine['free'] ) : ?>
                        <span class="engine-badge" style="background:#d1fae5; color:#047857; font-weight:700;">✅ Gratuit</span>
                    <?php endif; ?>
                </div>
                <div class="engine-meter">
                    <div class="engine-meter-label">
                        <span>Qualité</span>
                        <span><?php echo esc_html( $engine['quality'] ); ?>%</span>
                    </div>
                    <div class="engine-meter-track">
                        <div class="engine-meter-fill" style="width: <?php echo esc_attr( $engine['quality'] ); ?>%; background: <?php echo esc_attr( $engine['color'] ); ?>;"></div>
                    </div>
                </div>
                <div class="engine-meter">
                    <div class="engine-meter-label">
                        <span>Rapidité</span>
                        <span><?php echo esc_html( $engine['speed'] ); ?>%</span>
                    </div>
                    <div class="engine-meter-track">
                        <div class="engine-meter-fill" style="width: <?php echo esc_attr( $engine['speed'] ); ?>%; background: <?php echo esc_attr( $engine['color'] ); ?>"></div>
                    </div>
                </div>
                <div class="engine-key-status">
                    <?php if ( isset( $engine['free'] ) && $engine['free'] ) : ?>
                        <span class="key-set" style="background:#fff8e5; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; color:#92400e;">🔑 Clé API requise (plan gratuit)</span>
                    <?php elseif ( $key_set ) : ?>
                        <span class="key-set">✅ Clé API configurée</span>
                    <?php else : ?>
                        <span class="key-missing">⚠️ Clé API manquante</span>
                    <?php endif; ?>
                </div>
                <div style="margin-top:8px; display:flex; gap:6px;">
                    <?php if ( ! $is_primary ) : ?>
                        <button type="button" class="button button-small lingua-set-primary-btn" data-engine="<?php echo esc_attr( $slug ); ?>">
                            ⭐ Définir principal
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( $engine['docs_url'] ); ?>" target="_blank" class="button button-small" style="text-decoration:none;">
                        📖 Docs
                    </a>
                </div>

                <!-- INLINE TRANSLATION TEST -->
                <div class="lingua-inline-test" style="margin-top:12px; border-top:1px dashed #ddd; padding-top:10px;">
                    <div style="font-size:12px; font-weight:600; margin-bottom:6px; color:<?php echo esc_attr( $engine['color'] ); ?>;">
                        🧪 Tester la traduction
                    </div>
                    <input type="text"
                           class="lingua-test-input"
                           data-engine="<?php echo esc_attr( $slug ); ?>"
                           placeholder="Entrez un texte à traduire..."
                           style="width:100%; padding:6px 8px; font-size:12px; border:1px solid #ddd; border-radius:4px; margin-bottom:6px; box-sizing:border-box;"
                           <?php echo ! $can_test ? 'disabled title="Clé API requise pour tester"' : ''; ?>>
                    <div style="display:flex; gap:6px; margin-bottom:6px;">
                        <select class="lingua-test-lang"
                                data-engine="<?php echo esc_attr( $slug ); ?>"
                                style="flex:1; padding:5px 6px; font-size:12px; border:1px solid #ddd; border-radius:4px;"
                                <?php echo ! $can_test ? 'disabled' : ''; ?>>
                            <?php foreach ( $test_languages as $code => $name ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, 'fr_FR' ); ?>><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button"
                                class="button button-small lingua-inline-translate-btn"
                                data-engine="<?php echo esc_attr( $slug ); ?>"
                                style="background:<?php echo esc_attr( $engine['color'] ); ?>; color:#fff; border:none; font-size:11px; white-space:nowrap;"
                                <?php echo ! $can_test ? 'disabled' : ''; ?>>
                            🌍 Traduire
                        </button>
                    </div>
                    <div class="lingua-test-output"
                         data-engine="<?php echo esc_attr( $slug ); ?>"
                         style="min-height:28px; padding:6px 8px; font-size:12px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:4px; color:#333; word-wrap:break-word;">
                        <span style="color:#aaa; font-style:italic;">La traduction apparaîtra ici...</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- API KEYS + SETTINGS -->
    <div class="lingua-ai-grid">
        <!-- API Keys -->
        <div class="lingua-ai-card">
            <h2>🔑 Clés API</h2>
            <p class="description" style="margin-bottom:12px;">Configurez les clés API pour chaque moteur de traduction.</p>

            <?php foreach ( $engines as $slug => $engine ) : ?>
                <div class="lingua-api-key-row">
                    <span class="key-engine-icon"><?php echo esc_html( $engine['icon'] ); ?></span>
                    <span class="key-engine-name"><?php echo esc_html( $engine['name'] ); ?></span>
                    <?php if ( isset( $engine['free'] ) && $engine['free'] ) : ?>
                        <input type="password"
                               value="<?php echo esc_attr( $api_keys[ $slug ] ); ?>"
                               placeholder="Clé API Z.AI (optionnel)..."
                               class="regular-text lingua-api-key-input-recap"
                               data-engine="<?php echo esc_attr( $slug ); ?>"
                               style="flex:1; font-size:12px; font-family:monospace;">
                        <span class="key-toggle-vis" data-target='input[data-engine="<?php echo esc_attr( $slug ); ?>"].lingua-api-key-input-recap'>👁️</span>
                        <span style="color:#92400e; font-size:11px; font-weight:600; white-space:nowrap;">🔑 Gratuit</span>
                    <?php else : ?>
                        <input type="password"
                               value="<?php echo esc_attr( $api_keys[ $slug ] ); ?>"
                               placeholder="Entrez votre clé API..."
                               class="regular-text lingua-api-key-input-recap"
                               data-engine="<?php echo esc_attr( $slug ); ?>">
                        <span class="key-toggle-vis" data-target='input[data-engine="<?php echo esc_attr( $slug ); ?>"].lingua-api-key-input-recap'>👁️</span>
                    <?php endif; ?>
                    <button type="button" class="button button-small lingua-test-key-btn" data-engine="<?php echo esc_attr( $slug ); ?>">
                        🧪 Tester
                    </button>
                </div>
            <?php endforeach; ?>

            <div style="margin-top:15px;">
                <button type="button" id="lingua-save-api-keys-btn" class="button button-primary">💾 Sauvegarder les clés API</button>
                <span id="lingua-api-keys-status" style="margin-left:10px;"></span>
            </div>
        </div>

        <!-- Settings -->
        <div class="lingua-ai-card">
            <h2>⚙️ Réglages IA</h2>

            <div class="lingua-toggle-row">
                <div>
                    <label for="toggle-auto-translate">Traduction automatique</label>
                    <div class="description">Envoie les nouveaux contenus en traduction dès leur publication</div>
                </div>
                <div class="lingua-toggle-switch">
                    <input type="checkbox" id="toggle-auto-translate" class="lingua-ai-setting" data-setting="auto_translate" value="1" <?php checked( $auto_translate, 1 ); ?>>
                    <label class="lingua-toggle-slider" for="toggle-auto-translate"></label>
                </div>
            </div>

            <div class="lingua-toggle-row">
                <div>
                    <label for="toggle-quality-check">Contrôle qualité IA</label>
                    <div class="description">Vérifie la cohérence des traductions avec un second passage</div>
                </div>
                <div class="lingua-toggle-switch">
                    <input type="checkbox" id="toggle-quality-check" class="lingua-ai-setting" data-setting="quality_check" value="1" <?php checked( $quality_check, 1 ); ?>>
                    <label class="lingua-toggle-slider" for="toggle-quality-check"></label>
                </div>
            </div>

            <div class="lingua-toggle-row">
                <div>
                    <label for="toggle-glossary">Glossaire personnalisé</label>
                    <div class="description">Force certaines traductions pour les termes techniques</div>
                </div>
                <div class="lingua-toggle-switch">
                    <input type="checkbox" id="toggle-glossary" class="lingua-ai-setting" data-setting="glossary_enabled" value="1" <?php checked( $glossary_enabled, 1 ); ?>>
                    <label class="lingua-toggle-slider" for="toggle-glossary"></label>
                </div>
            </div>

            <table class="form-table" style="margin-top:10px;">
                <tr>
                    <th style="padding:8px 0;">Moteur principal</th>
                    <td>
                        <select class="lingua-ai-setting" data-setting="primary_engine">
                            <?php foreach ( $engines as $slug => $engine ) : ?>
                                <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $primary_engine, $slug ); ?>>
                                    <?php echo esc_html( $engine['icon'] . ' ' . $engine['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th style="padding:8px 0;">Moteur de secours</th>
                    <td>
                        <select class="lingua-ai-setting" data-setting="fallback_engine">
                            <option value="">Aucun</option>
                            <?php foreach ( $engines as $slug => $engine ) : ?>
                                <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $fallback_engine, $slug ); ?>>
                                    <?php echo esc_html( $engine['icon'] . ' ' . $engine['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th style="padding:8px 0;">Taille des lots</th>
                    <td>
                        <input type="number" class="lingua-ai-setting" data-setting="batch_size" value="<?php echo esc_attr( $batch_size ); ?>" min="1" max="50" style="width:80px;">
                    </td>
                </tr>
                <tr>
                    <th style="padding:8px 0;">Tentatives de retry</th>
                    <td>
                        <input type="number" class="lingua-ai-setting" data-setting="retry_count" value="<?php echo esc_attr( $retry_count ); ?>" min="0" max="10" style="width:80px;">
                    </td>
                </tr>
                <tr>
                    <th style="padding:8px 0;">Limite (req/min)</th>
                    <td>
                        <input type="number" class="lingua-ai-setting" data-setting="rate_limit" value="<?php echo esc_attr( $rate_limit ); ?>" min="1" max="1000" style="width:80px;">
                    </td>
                </tr>
            </table>

            <div id="lingua-glossary-section" style="<?php echo $glossary_enabled ? '' : 'display:none;'; ?>">
                <h3 style="margin-top:15px;">📖 Glossaire personnalisé</h3>
                <p class="description">Un terme par ligne, format : source = traduction</p>
                <textarea class="lingua-ai-setting large-text" data-setting="custom_glossary" rows="5" style="font-family:monospace; font-size:12px;" placeholder="e-commerce = commerce électronique&#10;checkout = passage en caisse&#10;shopping cart = panier"><?php echo esc_textarea( $custom_glossary ); ?></textarea>
            </div>

            <div style="margin-top:15px;">
                <button type="button" id="lingua-save-settings-btn" class="button button-primary">💾 Sauvegarder les réglages IA</button>
                <span id="lingua-settings-status" style="margin-left:10px;"></span>
            </div>
        </div>
    </div>

    <!-- QUEUE MANAGEMENT + LOGS -->
    <div class="lingua-ai-grid">
        <!-- Queue -->
        <div class="lingua-ai-card">
            <h2>📦 File d'attente</h2>

            <div class="lingua-queue-grid">
                <div class="lingua-queue-stat stat-pending">
                    <div class="stat-value" id="queue-pending"><?php echo esc_html( $queue_stats['pending'] ); ?></div>
                    <div class="stat-label">En attente</div>
                </div>
                <div class="lingua-queue-stat stat-processing">
                    <div class="stat-value" id="queue-processing"><?php echo esc_html( $queue_stats['processing'] ); ?></div>
                    <div class="stat-label">En cours</div>
                </div>
                <div class="lingua-queue-stat stat-completed">
                    <div class="stat-value" id="queue-completed"><?php echo esc_html( $queue_stats['completed'] ); ?></div>
                    <div class="stat-label">Complétées</div>
                </div>
                <div class="lingua-queue-stat stat-failed">
                    <div class="stat-value" id="queue-failed"><?php echo esc_html( $queue_stats['failed'] ); ?></div>
                    <div class="stat-label">Échouées</div>
                </div>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" id="btn-trigger-queue" class="button button-primary">⚡ Lancer la traduction</button>
                <button type="button" id="btn-refresh-queue" class="button">🔄 Actualiser</button>
                <button type="button" id="btn-retry-failed" class="button">🔁 Relancer les échecs</button>
                <button type="button" id="btn-clear-queue" class="button button-link-delete">🗑️ Vider la file</button>
            </div>

            <div id="queue-action-status" style="margin-top:10px; font-size:13px;"></div>
        </div>

        <!-- Logs -->
        <div class="lingua-ai-card">
            <h2>📋 Journaux de traduction</h2>

            <div style="display:flex; gap:8px; margin-bottom:12px;">
                <button type="button" class="button button-small lingua-log-filter active" data-filter="all">Tout</button>
                <button type="button" class="button button-small lingua-log-filter" data-filter="info">Info</button>
                <button type="button" class="button button-small lingua-log-filter" data-filter="success">Succès</button>
                <button type="button" class="button button-small lingua-log-filter" data-filter="warning">Warnings</button>
                <button type="button" class="button button-small lingua-log-filter" data-filter="error">Erreurs</button>
                <button type="button" id="btn-clear-logs" class="button button-small button-link-delete" style="margin-left:auto;">🗑️ Vider</button>
            </div>

            <div class="lingua-log-console" id="ai-log-console">
                <?php if ( ! empty( $recent_logs ) ) : ?>
                    <?php foreach ( $recent_logs as $line ) :
                        if ( empty( trim( $line ) ) ) continue;
                        $log_class = 'log-info';
                        if ( stripos( $line, 'error' ) !== false || stripos( $line, 'ERREUR' ) !== false ) $log_class = 'log-error';
                        elseif ( stripos( $line, 'success' ) !== false || stripos( $line, 'SUCCÈS' ) !== false || stripos( $line, 'complét' ) !== false ) $log_class = 'log-success';
                        elseif ( stripos( $line, 'warning' ) !== false || stripos( $line, 'ATTENTION' ) !== false ) $log_class = 'log-warning';
                    ?>
                        <div class="log-line <?php echo esc_attr( $log_class ); ?>"><?php echo esc_html( $line ); ?></div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="log-line log-info">Aucun journal disponible. Les traductions apparaîtront ici.</div>
                <?php endif; ?>
            </div>

            <div style="margin-top:10px; font-size:12px; color:#666;">
                📂 Fichier : <code><?php echo esc_html( $log_file ); ?></code>
            </div>
        </div>
    </div>

    <!-- SAVE ALL (les sauvegardes se font via AJAX — ce bouton est un raccourci) -->
    <div class="lingua-ai-card" style="text-align:center; padding:15px;">
        <button type="button" id="lingua-save-all-btn" class="button button-primary button-hero">💾 Tout sauvegarder (Clés + Réglages)</button>
        <span id="lingua-save-all-status" style="margin-left:15px; font-size:14px;"></span>
    </div>

</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var nonce = '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>';

    // Toggle password visibility
    $('.key-toggle-vis').on('click', function() {
        var target = $(this).data('target');
        var input = $(target);
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).text('🙈');
        } else {
            input.attr('type', 'password');
            $(this).text('👁️');
        }
    });

    // Fonction utilitaire : rafraîchir le nonce si expiré
    function refreshNonce(callback) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'lingua_refresh_nonce', nonce: nonce },
            dataType: 'json',
            success: function(res) {
                if (res.success && res.data.nonce) {
                    nonce = res.data.nonce;
                }
                if (callback) callback();
            },
            error: function() {
                if (callback) callback();
            }
        });
    }

    // Fonction utilitaire : message d'erreur AJAX détaillé
    function getAjaxErrorMessage(xhr, status, error) {
        if (xhr.status === 403) {
            return '❌ Session expirée. Rechargez la page et réessayez.';
        }
        if (xhr.status === 500) {
            return '❌ Erreur interne du serveur (500). Consultez les logs WordPress.';
        }
        if (status === 'parsererror') {
            return '❌ Réponse serveur invalide (JSON corrompu). Vérifiez les erreurs PHP.';
        }
        if (status === 'timeout') {
            return '❌ Délai d\'attente dépassé. Le serveur met trop de temps à répondre.';
        }
        return '❌ Erreur serveur (' + (xhr.status || 'inconnu') + '). Vérifiez votre connexion et les logs.';
    }

    // Test API key (bouton Tester dans la section Clés API)
    $('.lingua-test-key-btn').on('click', function() {
        var btn = $(this);
        var engine = btn.data('engine');
        var input = btn.closest('.lingua-api-key-row').find('.lingua-api-key-input-recap[data-engine="' + engine + '"]');
        var apiKey = input.val() || '';

        btn.prop('disabled', true).text('⏳ Test...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'lingua_test_api_key',
                engine: engine,
                api_key: apiKey,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    btn.text('✅ OK').css('color', 'green');
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Échec';
                    btn.text('❌ Échec').css('color', 'red');
                    alert(msg);
                }
            },
            error: function(xhr, status, error) {
                btn.text('❌ Erreur').css('color', 'red');
                // Si nonce expiré, tenter un rafraîchissement
                if (xhr.status === 403) {
                    refreshNonce(function() {
                        btn.prop('disabled', false).text('🧪 Tester').css('color', '');
                    });
                } else {
                    console.error('AJAX Error (test_api_key):', status, error, xhr.status, xhr.responseText);
                }
            },
            complete: function() {
                setTimeout(function() {
                    btn.prop('disabled', false).text('🧪 Tester').css('color', '');
                }, 3000);
            }
        });
    });

    // Inline translate button (dans chaque carte moteur)
    $(document).on('click', '.lingua-inline-translate-btn', function() {
        var btn = $(this);
        var engine = btn.data('engine');
        var card = btn.closest('.lingua-engine-card');
        var text = card.find('.lingua-test-input').val();
        var targetLang = card.find('.lingua-test-lang').val();
        var outputDiv = card.find('.lingua-test-output');

        if (!text || !text.trim()) {
            outputDiv.html('<span style="color:#d63638;">⚠️ Veuillez entrer un texte à traduire.</span>');
            return;
        }

        btn.prop('disabled', true).html('⏳...');
        outputDiv.html('<span style="color:#2271b1;">⏳ Traduction en cours...</span>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'lingua_test_translate',
                engine: engine,
                text: text,
                target_lang: targetLang,
                source_lang: 'en_US',
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    var latency = res.data.latency ? ' <span style="color:#888; font-size:10px;">(' + res.data.latency + ' ms)</span>' : '';
                    outputDiv.html('<span style="color:#00a32a;">✅</span> ' + res.data.translated_text + latency);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Erreur inconnue';
                    outputDiv.html('<span style="color:#d63638;">❌ ' + msg + '</span>');
                }
            },
            error: function(xhr, status, error) {
                outputDiv.html('<span style="color:#d63638;">' + getAjaxErrorMessage(xhr, status, error) + '</span>');
                console.error('AJAX Error (test_translate):', status, error, xhr.status, xhr.responseText);
                // Si nonce expiré (403), rafraîchir automatiquement
                if (xhr.status === 403) {
                    refreshNonce(function() {
                        outputDiv.append('<br><span style="color:#2271b1; font-size:11px;">🔄 Session rafraîchie. Réessayez maintenant.</span>');
                    });
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('🌍 Traduire');
            }
        });
    });

    // Allow Enter key to trigger translation
    $(document).on('keypress', '.lingua-test-input', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).closest('.lingua-engine-card').find('.lingua-inline-translate-btn').trigger('click');
        }
    });

    // Set primary engine
    $('.lingua-set-primary-btn').on('click', function() {
        var engine = $(this).data('engine');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'lingua_set_primary_engine',
                engine: engine,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    location.reload();
                }
            }
        });
    });

    // Save API keys (bouton "Sauvegarder les clés API" dans le récapitulatif)
    $('#lingua-save-api-keys-btn').on('click', function() {
        var btn = $(this);
        var keys = {};
        // Lire les valeurs depuis les inputs du récapitulatif (classe .lingua-api-key-input-recap)
        <?php foreach ( $engines as $slug => $engine ) : ?>
            var recapInput_<?php echo esc_js( $slug ); ?> = $('.lingua-api-key-row .lingua-api-key-input-recap[data-engine="<?php echo esc_js( $slug ); ?>"]');
            keys['<?php echo esc_js( $slug ); ?>'] = recapInput_<?php echo esc_js( $slug ); ?>.length ? (recapInput_<?php echo esc_js( $slug ); ?>.val() || '') : '';
        <?php endforeach; ?>

        btn.prop('disabled', true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'lingua_save_api_keys',
                keys: keys,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    $('#lingua-api-keys-status').text('✅ Clés sauvegardées !').css('color', 'green');
                    // Recharger après un court délai pour mettre à jour les statuts des cartes
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    $('#lingua-api-keys-status').text('❌ ' + (res.data && res.data.message ? res.data.message : 'Erreur')).css('color', 'red');
                }
            },
            error: function(xhr, status, error) {
                $('#lingua-api-keys-status').text('❌ ' + getAjaxErrorMessage(xhr, status, error)).css('color', 'red');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Queue actions
    $('#btn-trigger-queue').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true);
        $('#queue-action-status').text('⚡ Traduction en cours...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'lingua_trigger_queue', nonce: nonce },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#queue-action-status').text('✅ ' + res.data.message);
                } else {
                    $('#queue-action-status').text('❌ ' + (res.data && res.data.message ? res.data.message : 'Erreur'));
                }
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    $('#btn-refresh-queue').on('click', function() { location.reload(); });

    $('#btn-retry-failed').on('click', function() {
        if (!confirm('Relancer toutes les traductions échouées ?')) return;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'lingua_retry_failed', nonce: nonce },
            dataType: 'json',
            success: function(res) {
                $('#queue-action-status').text(res.success ? '✅ ' + res.data.message : '❌ Erreur');
                setTimeout(function() { location.reload(); }, 1500);
            }
        });
    });

    $('#btn-clear-queue').on('click', function() {
        if (!confirm('Vider toute la file d\'attente ? Cette action est irréversible.')) return;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'lingua_clear_queue', nonce: nonce },
            dataType: 'json',
            success: function(res) {
                $('#queue-action-status').text(res.success ? '✅ ' + res.data.message : '❌ Erreur');
                setTimeout(function() { location.reload(); }, 1500);
            }
        });
    });

    // Log filters
    $('.lingua-log-filter').on('click', function() {
        $('.lingua-log-filter').removeClass('button-primary');
        $(this).addClass('button-primary');
        var filter = $(this).data('filter');
        if (filter === 'all') {
            $('#ai-log-console .log-line').show();
        } else {
            $('#ai-log-console .log-line').hide();
            $('#ai-log-console .log-' + filter).show();
        }
    });

    // Clear logs
    $('#btn-clear-logs').on('click', function() {
        if (!confirm('Vider les journaux ?')) return;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'lingua_clear_logs', nonce: nonce },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#ai-log-console').html('<div class="log-line log-info">Journaux vidés.</div>');
                }
            }
        });
    });

    // =========================================================================
    // SAUVEGARDE DES RÉGLAGES IA (toggles, selects, inputs)
    // =========================================================================

    // Toggle glossaire : afficher/masquer la section
    $('#toggle-glossary').on('change', function() {
        if ($(this).is(':checked')) {
            $('#lingua-glossary-section').slideDown(200);
        } else {
            $('#lingua-glossary-section').slideUp(200);
        }
    });

    // Collecter les réglages IA depuis les éléments .lingua-ai-setting
    function collectAISettings() {
        var settings = {};
        $('.lingua-ai-setting').each(function() {
            var setting = $(this).data('setting');
            if (!setting) return;
            if ($(this).is('input[type="checkbox"]')) {
                settings[setting] = $(this).is(':checked') ? 1 : 0;
            } else if ($(this).is('select') || $(this).is('input[type="number"]')) {
                settings[setting] = $(this).val();
            } else if ($(this).is('textarea')) {
                settings[setting] = $(this).val();
            }
        });
        return settings;
    }

    // Sauvegarder les réglages IA via AJAX
    $('#lingua-save-settings-btn').on('click', function() {
        var btn = $(this);
        var settings = collectAISettings();

        btn.prop('disabled', true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_save_ai_settings_ajax',
                settings: settings,
                nonce: nonce
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#lingua-settings-status').text('✅ Réglages sauvegardés !').css('color', 'green');
                } else {
                    $('#lingua-settings-status').text('❌ ' + (res.data && res.data.message ? res.data.message : 'Erreur')).css('color', 'red');
                }
            },
            error: function(xhr, status, error) {
                $('#lingua-settings-status').text('❌ ' + getAjaxErrorMessage(xhr, status, error)).css('color', 'red');
            },
            complete: function() {
                btn.prop('disabled', false);
                setTimeout(function() { $('#lingua-settings-status').text(''); }, 3000);
            }
        });
    });

    // Bouton "Tout sauvegarder" — sauvegarde clés + réglages
    $('#lingua-save-all-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true);
        $('#lingua-save-all-status').text('⏳ Sauvegarde en cours...').css('color', '#666');

        // 1. Collecter les clés API depuis les inputs du récapitulatif
        var keys = {};
        <?php foreach ( $engines as $slug => $engine ) : ?>
            var recap_<?php echo esc_js( $slug ); ?> = $('.lingua-api-key-input-recap[data-engine="<?php echo esc_js( $slug ); ?>"]');
            keys['<?php echo esc_js( $slug ); ?>'] = recap_<?php echo esc_js( $slug ); ?>.length ? (recap_<?php echo esc_js( $slug ); ?>.val() || '') : '';
        <?php endforeach; ?>

        // 2. Collecter les réglages
        var settings = collectAISettings();

        // 3. Sauvegarder les clés
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'lingua_save_api_keys', keys: keys, nonce: nonce },
            dataType: 'json'
        }).always(function() {
            // 4. Sauvegarder les réglages
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'lingua_save_ai_settings_ajax', settings: settings, nonce: nonce },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#lingua-save-all-status').text('✅ Tout sauvegardé ! Rechargement...').css('color', 'green');
                        setTimeout(function() { location.reload(); }, 1200);
                    } else {
                        $('#lingua-save-all-status').text('❌ ' + (res.data && res.data.message ? res.data.message : 'Erreur')).css('color', 'red');
                    }
                },
                error: function(xhr, status, error) {
                    $('#lingua-save-all-status').text('❌ ' + getAjaxErrorMessage(xhr, status, error)).css('color', 'red');
                },
                complete: function() {
                    btn.prop('disabled', false);
                }
            });
        });
    });
});
</script>
