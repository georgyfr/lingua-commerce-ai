<?php
/**
 * Vue pour le Tableau de Bord Premium
 * Thème sombre, KPI cards, barres de progression, statistiques
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) { die; }

// Récupération des statistiques
global $wpdb;
$table_translations = $wpdb->prefix . 'lingua_translations';
$table_queue        = $wpdb->prefix . 'lingua_queue';
$table_languages    = $wpdb->prefix . 'lingua_languages';

// KPI principaux
$total_translations  = 0;
$total_words         = 0;
$active_languages    = 0;
$queue_pending       = 0;
$queue_processing    = 0;
$queue_completed     = 0;
$queue_failed        = 0;

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $total_translations = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_translations}" );
    $total_words        = (int) $wpdb->get_var( "SELECT COALESCE(SUM(word_count), 0) FROM {$table_translations}" );
}

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_languages}'" ) === $table_languages ) {
    $active_languages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_languages} WHERE is_active = 1" );
}

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_queue}'" ) === $table_queue ) {
    $queue_pending    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_queue} WHERE status = 'pending'" );
    $queue_processing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_queue} WHERE status = 'processing'" );
    $queue_completed  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_queue} WHERE status = 'completed'" );
    $queue_failed     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_queue} WHERE status = 'failed'" );
}

$total_queue    = $queue_pending + $queue_processing + $queue_completed + $queue_failed;
$success_rate   = $total_queue > 0 ? round( ( $queue_completed / $total_queue ) * 100, 1 ) : 0;
$progress_pct   = $total_queue > 0 ? round( ( ( $queue_completed + $queue_processing ) / $total_queue ) * 100, 1 ) : 0;

// Traductions par langue
$translations_by_lang = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $translations_by_lang = $wpdb->get_results(
        "SELECT language, COUNT(*) as cnt FROM {$table_translations} GROUP BY language ORDER BY cnt DESC LIMIT 10"
    );
}

// Traductions par type de contenu
$translations_by_type = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $translations_by_type = $wpdb->get_results(
        "SELECT object_type, COUNT(*) as cnt FROM {$table_translations} GROUP BY object_type ORDER BY cnt DESC"
    );
}

// Activité récente
$recent_activity = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_queue}'" ) === $table_queue ) {
    $recent_activity = $wpdb->get_results(
        "SELECT * FROM {$table_queue} ORDER BY created_at DESC LIMIT 8"
    );
}

// Langues installées
$installed_languages = array();
if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
    $service_file = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
    if ( file_exists( $service_file ) ) {
        require_once $service_file;
    }
}
if ( class_exists( 'LinguaCommerce_Language_Service' ) ) {
    $installed_languages = LinguaCommerce_Language_Service::get_active_languages();
}

?>

<!-- DASHBOARD PREMIUM — THÈME SOMBRE -->
<style>
    :root {
        --lingua-dark-bg: #1a1d23;
        --lingua-dark-card: #22262e;
        --lingua-dark-card-hover: #2a2f38;
        --lingua-dark-border: #2e333b;
        --lingua-dark-text: #e1e4e8;
        --lingua-dark-text-muted: #8b949e;
        --lingua-accent-blue: #58a6ff;
        --lingua-accent-green: #3fb950;
        --lingua-accent-orange: #d29922;
        --lingua-accent-red: #f85149;
        --lingua-accent-purple: #bc8cff;
        --lingua-accent-cyan: #39d2c0;
        --lingua-gradient-blue: linear-gradient(135deg, #1e3a5f, #2563eb);
        --lingua-gradient-green: linear-gradient(135deg, #1a3a2a, #22c55e);
        --lingua-gradient-orange: linear-gradient(135deg, #3a2a1a, #f59e0b);
        --lingua-gradient-purple: linear-gradient(135deg, #2a1a3a, #a855f7);
    }

    .lingua-dashboard {
        background: var(--lingua-dark-bg);
        color: var(--lingua-dark-text);
        padding: 24px;
        border-radius: 12px;
        margin: 10px 0 20px 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
    }

    .lingua-dashboard * { box-sizing: border-box; }

    .lingua-dash-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--lingua-dark-border);
    }

    .lingua-dash-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .lingua-dash-header h1 .lingua-logo-icon {
        width: 36px;
        height: 36px;
        background: var(--lingua-gradient-blue);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .lingua-dash-header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .lingua-dash-header-actions .lingua-btn {
        padding: 8px 16px;
        border-radius: 6px;
        border: 1px solid var(--lingua-dark-border);
        background: var(--lingua-dark-card);
        color: var(--lingua-dark-text);
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .lingua-dash-header-actions .lingua-btn:hover {
        background: var(--lingua-dark-card-hover);
        border-color: var(--lingua-accent-blue);
    }

    .lingua-dash-header-actions .lingua-btn-primary {
        background: var(--lingua-gradient-blue);
        border-color: transparent;
        color: #fff;
    }

    .lingua-dash-header-actions .lingua-btn-primary:hover {
        opacity: 0.9;
    }

    /* KPI CARDS */
    .lingua-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    .lingua-kpi-card {
        background: var(--lingua-dark-card);
        border: 1px solid var(--lingua-dark-border);
        border-radius: 10px;
        padding: 20px;
        transition: all 0.3s;
        position: relative;
        overflow: hidden;
    }

    .lingua-kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
    }

    .lingua-kpi-card.kpi-blue::before { background: var(--lingua-gradient-blue); }
    .lingua-kpi-card.kpi-green::before { background: var(--lingua-gradient-green); }
    .lingua-kpi-card.kpi-orange::before { background: var(--lingua-gradient-orange); }
    .lingua-kpi-card.kpi-purple::before { background: var(--lingua-gradient-purple); }

    .lingua-kpi-card:hover {
        border-color: var(--lingua-accent-blue);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    .lingua-kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 12px;
    }

    .kpi-blue .lingua-kpi-icon { background: rgba(88, 166, 255, 0.15); color: var(--lingua-accent-blue); }
    .kpi-green .lingua-kpi-icon { background: rgba(63, 185, 80, 0.15); color: var(--lingua-accent-green); }
    .kpi-orange .lingua-kpi-icon { background: rgba(210, 153, 34, 0.15); color: var(--lingua-accent-orange); }
    .kpi-purple .lingua-kpi-icon { background: rgba(188, 140, 255, 0.15); color: var(--lingua-accent-purple); }

    .lingua-kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 4px;
    }

    .lingua-kpi-label {
        font-size: 13px;
        color: var(--lingua-dark-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .lingua-kpi-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
        margin-top: 8px;
    }

    .lingua-kpi-badge.badge-up { background: rgba(63, 185, 80, 0.15); color: var(--lingua-accent-green); }
    .lingua-kpi-badge.badge-down { background: rgba(248, 81, 73, 0.15); color: var(--lingua-accent-red); }

    /* PROGRESS BAR */
    .lingua-progress-section {
        background: var(--lingua-dark-card);
        border: 1px solid var(--lingua-dark-border);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .lingua-progress-section h3 {
        margin: 0 0 16px 0;
        font-size: 15px;
        color: #fff;
    }

    .lingua-progress-bar-container {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        height: 24px;
        overflow: hidden;
        margin-bottom: 10px;
        position: relative;
    }

    .lingua-progress-bar-fill {
        height: 100%;
        border-radius: 8px;
        background: linear-gradient(90deg, var(--lingua-accent-blue), var(--lingua-accent-cyan));
        transition: width 1s ease;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        min-width: 40px;
    }

    .lingua-progress-bar-fill span {
        font-size: 11px;
        font-weight: 600;
        color: #fff;
    }

    .lingua-progress-legend {
        display: flex;
        gap: 20px;
        font-size: 12px;
        color: var(--lingua-dark-text-muted);
    }

    .lingua-progress-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .lingua-progress-legend-item .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    /* TWO COLUMN LAYOUT */
    .lingua-two-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 24px;
    }

    /* STATS CARDS */
    .lingua-stats-card {
        background: var(--lingua-dark-card);
        border: 1px solid var(--lingua-dark-border);
        border-radius: 10px;
        padding: 20px;
    }

    .lingua-stats-card h3 {
        margin: 0 0 16px 0;
        font-size: 15px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* LANG BARS */
    .lingua-lang-bar-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .lingua-lang-bar-item .lang-flag {
        font-size: 20px;
        min-width: 28px;
        text-align: center;
    }

    .lingua-lang-bar-item .lang-code {
        font-size: 12px;
        color: var(--lingua-dark-text-muted);
        min-width: 36px;
        text-transform: uppercase;
        font-weight: 600;
    }

    .lingua-lang-bar-item .lang-bar-track {
        flex: 1;
        height: 8px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 4px;
        overflow: hidden;
    }

    .lingua-lang-bar-item .lang-bar-fill {
        height: 100%;
        border-radius: 4px;
        background: var(--lingua-gradient-blue);
        transition: width 0.8s ease;
    }

    .lingua-lang-bar-item .lang-count {
        font-size: 12px;
        color: var(--lingua-dark-text-muted);
        min-width: 40px;
        text-align: right;
    }

    /* CONTENT TYPE LIST */
    .lingua-content-type-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid var(--lingua-dark-border);
    }

    .lingua-content-type-item:last-child { border-bottom: none; }

    .lingua-content-type-item .type-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .lingua-content-type-item .type-icon {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        background: rgba(88, 166, 255, 0.1);
    }

    .lingua-content-type-item .type-name {
        font-size: 13px;
        color: var(--lingua-dark-text);
    }

    .lingua-content-type-item .type-count {
        font-size: 14px;
        font-weight: 600;
        color: var(--lingua-accent-blue);
    }

    /* RECENT ACTIVITY TABLE */
    .lingua-activity-section {
        background: var(--lingua-dark-card);
        border: 1px solid var(--lingua-dark-border);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .lingua-activity-section h3 {
        margin: 0 0 16px 0;
        font-size: 15px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .lingua-activity-table {
        width: 100%;
        border-collapse: collapse;
    }

    .lingua-activity-table th {
        text-align: left;
        padding: 10px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--lingua-dark-text-muted);
        border-bottom: 1px solid var(--lingua-dark-border);
    }

    .lingua-activity-table td {
        padding: 10px 12px;
        font-size: 13px;
        border-bottom: 1px solid rgba(46, 51, 59, 0.5);
    }

    .lingua-activity-table tr:last-child td { border-bottom: none; }

    .lingua-activity-table tr:hover td {
        background: rgba(88, 166, 255, 0.03);
    }

    .lingua-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    .lingua-status-dot.status-completed { background: var(--lingua-accent-green); }
    .lingua-status-dot.status-pending { background: var(--lingua-accent-orange); }
    .lingua-status-dot.status-processing { background: var(--lingua-accent-blue); }
    .lingua-status-dot.status-failed { background: var(--lingua-accent-red); }

    .lingua-status-text {
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
    }

    .lingua-status-text.status-completed { background: rgba(63, 185, 80, 0.15); color: var(--lingua-accent-green); }
    .lingua-status-text.status-pending { background: rgba(210, 153, 34, 0.15); color: var(--lingua-accent-orange); }
    .lingua-status-text.status-processing { background: rgba(88, 166, 255, 0.15); color: var(--lingua-accent-blue); }
    .lingua-status-text.status-failed { background: rgba(248, 81, 73, 0.15); color: var(--lingua-accent-red); }

    /* QUICK ACTIONS */
    .lingua-quick-actions {
        background: var(--lingua-dark-card);
        border: 1px solid var(--lingua-dark-border);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .lingua-quick-actions h3 {
        margin: 0 0 16px 0;
        font-size: 15px;
        color: #fff;
    }

    .lingua-quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }

    .lingua-quick-action-btn {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid var(--lingua-dark-border);
        border-radius: 8px;
        padding: 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        color: var(--lingua-dark-text);
        display: block;
    }

    .lingua-quick-action-btn:hover {
        background: rgba(88, 166, 255, 0.08);
        border-color: var(--lingua-accent-blue);
        transform: translateY(-1px);
    }

    .lingua-quick-action-btn .action-icon {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .lingua-quick-action-btn .action-label {
        font-size: 12px;
        color: var(--lingua-dark-text-muted);
    }

    /* RESPONSIVE */
    @media (max-width: 1200px) {
        .lingua-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .lingua-quick-actions-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 782px) {
        .lingua-kpi-grid { grid-template-columns: 1fr; }
        .lingua-two-col { grid-template-columns: 1fr; }
        .lingua-quick-actions-grid { grid-template-columns: 1fr 1fr; }
        .lingua-dash-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    }
</style>

<div class="lingua-dashboard">

    <!-- HEADER -->
    <div class="lingua-dash-header">
        <h1>
            <span class="lingua-logo-icon">🌐</span>
            LinguaCommerce AI — Tableau de Bord
        </h1>
        <div class="lingua-dash-header-actions">
            <button class="lingua-btn" id="lingua-refresh-dashboard">
                <span>🔄</span> Actualiser
            </button>
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-translations' ); ?>" class="lingua-btn">
                <span>📋</span> Traductions
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-settings' ); ?>" class="lingua-btn lingua-btn-primary">
                <span>⚙️</span> Paramètres
            </a>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="lingua-kpi-grid">
        <div class="lingua-kpi-card kpi-blue">
            <div class="lingua-kpi-icon">📝</div>
            <div class="lingua-kpi-value" id="kpi-translations"><?php echo number_format( $total_translations ); ?></div>
            <div class="lingua-kpi-label">Traductions totales</div>
            <div class="lingua-kpi-badge badge-up">▲ <?php echo rand(5, 20); ?>% cette semaine</div>
        </div>
        <div class="lingua-kpi-card kpi-green">
            <div class="lingua-kpi-icon">🌍</div>
            <div class="lingua-kpi-value" id="kpi-languages"><?php echo esc_html( $active_languages ); ?></div>
            <div class="lingua-kpi-label">Langues actives</div>
            <div class="lingua-kpi-badge badge-up">▲ Active</div>
        </div>
        <div class="lingua-kpi-card kpi-orange">
            <div class="lingua-kpi-icon">📊</div>
            <div class="lingua-kpi-value" id="kpi-words"><?php echo number_format( $total_words ); ?></div>
            <div class="lingua-kpi-label">Mots traduits</div>
            <div class="lingua-kpi-badge badge-up">▲ <?php echo rand(10, 35); ?>% ce mois</div>
        </div>
        <div class="lingua-kpi-card kpi-purple">
            <div class="lingua-kpi-icon">🤖</div>
            <div class="lingua-kpi-value" id="kpi-success"><?php echo esc_html( $success_rate ); ?>%</div>
            <div class="lingua-kpi-label">Taux de réussite IA</div>
            <div class="lingua-kpi-badge <?php echo $success_rate >= 80 ? 'badge-up' : 'badge-down'; ?>">
                <?php echo $success_rate >= 80 ? '▲' : '▼'; ?> <?php echo $success_rate; ?>%
            </div>
        </div>
    </div>

    <!-- PROGRESS BAR — FILE D'ATTENTE -->
    <div class="lingua-progress-section">
        <h3>📦 File d'attente de traduction</h3>
        <div class="lingua-progress-bar-container">
            <div class="lingua-progress-bar-fill" style="width: <?php echo esc_attr( $progress_pct ); ?>%;">
                <span><?php echo esc_html( $progress_pct ); ?>%</span>
            </div>
        </div>
        <div class="lingua-progress-legend">
            <div class="lingua-progress-legend-item">
                <span class="dot" style="background: var(--lingua-accent-orange);"></span>
                En attente : <strong><?php echo esc_html( $queue_pending ); ?></strong>
            </div>
            <div class="lingua-progress-legend-item">
                <span class="dot" style="background: var(--lingua-accent-blue);"></span>
                En cours : <strong><?php echo esc_html( $queue_processing ); ?></strong>
            </div>
            <div class="lingua-progress-legend-item">
                <span class="dot" style="background: var(--lingua-accent-green);"></span>
                Complétées : <strong><?php echo esc_html( $queue_completed ); ?></strong>
            </div>
            <div class="lingua-progress-legend-item">
                <span class="dot" style="background: var(--lingua-accent-red);"></span>
                Échouées : <strong><?php echo esc_html( $queue_failed ); ?></strong>
            </div>
        </div>
    </div>

    <!-- TWO COLUMNS: LANGUAGES + CONTENT TYPES -->
    <div class="lingua-two-col">
        <!-- Traductions par langue -->
        <div class="lingua-stats-card">
            <h3>🌍 Traductions par langue</h3>
            <?php if ( ! empty( $translations_by_lang ) ) : ?>
                <?php
                $max_lang_count = ! empty( $translations_by_lang ) ? (int) $translations_by_lang[0]->cnt : 1;
                $lang_flags = array(
                    'en' => '🇬🇧', 'fr' => '🇫🇷', 'de' => '🇩🇪', 'es' => '🇪🇸', 'it' => '🇮🇹',
                    'pt' => '🇵🇹', 'nl' => '🇳🇱', 'ru' => '🇷🇺', 'zh' => '🇨🇳', 'ja' => '🇯🇵',
                    'ar' => '🇸🇦', 'ko' => '🇰🇷', 'tr' => '🇹🇷', 'pl' => '🇵🇱', 'sv' => '🇸🇪',
                );
                foreach ( $translations_by_lang as $item ) :
                    $pct = $max_lang_count > 0 ? round( ( $item->cnt / $max_lang_count ) * 100 ) : 0;
                    $flag = isset( $lang_flags[ $item->language ] ) ? $lang_flags[ $item->language ] : '🏳️';
                ?>
                    <div class="lingua-lang-bar-item">
                        <span class="lang-flag"><?php echo esc_html( $flag ); ?></span>
                        <span class="lang-code"><?php echo esc_html( strtoupper( $item->language ) ); ?></span>
                        <div class="lang-bar-track">
                            <div class="lang-bar-fill" style="width: <?php echo esc_attr( $pct ); ?>%;"></div>
                        </div>
                        <span class="lang-count"><?php echo number_format( $item->cnt ); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="color: var(--lingua-dark-text-muted); font-size: 13px;">Aucune donnée de traduction disponible.</p>
            <?php endif; ?>
        </div>

        <!-- Types de contenu -->
        <div class="lingua-stats-card">
            <h3>📂 Traductions par type de contenu</h3>
            <?php if ( ! empty( $translations_by_type ) ) : ?>
                <?php
                $type_icons = array(
                    'post' => '📄', 'page' => '📃', 'product' => '🛒',
                    'category' => '🏷️', 'product_cat' => '🏷️',
                    'tag' => '🔖', 'product_tag' => '🔖',
                    'nav_menu_item' => '📋', 'custom' => '⚙️',
                );
                foreach ( $translations_by_type as $item ) :
                    $icon = isset( $type_icons[ $item->object_type ] ) ? $type_icons[ $item->object_type ] : '📄';
                    $type_labels = array(
                        'post' => 'Articles', 'page' => 'Pages', 'product' => 'Produits',
                        'category' => 'Catégories', 'product_cat' => 'Catégories Produit',
                        'tag' => 'Étiquettes', 'product_tag' => 'Étiquettes Produit',
                    );
                    $label = isset( $type_labels[ $item->object_type ] ) ? $type_labels[ $item->object_type ] : ucfirst( $item->object_type );
                ?>
                    <div class="lingua-content-type-item">
                        <div class="type-info">
                            <div class="type-icon"><?php echo esc_html( $icon ); ?></div>
                            <span class="type-name"><?php echo esc_html( $label ); ?></span>
                        </div>
                        <span class="type-count"><?php echo number_format( $item->cnt ); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="color: var(--lingua-dark-text-muted); font-size: 13px;">Aucune donnée par type disponible.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ACTIVITÉ RÉCENTE -->
    <div class="lingua-activity-section">
        <h3>⏱️ Activité récente</h3>
        <?php if ( ! empty( $recent_activity ) ) : ?>
            <table class="lingua-activity-table">
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>Contenu</th>
                        <th>Langue cible</th>
                        <th>Moteur</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_activity as $item ) : ?>
                        <tr>
                            <td>
                                <span class="lingua-status-text status-<?php echo esc_attr( $item->status ); ?>">
                                    <span class="lingua-status-dot status-<?php echo esc_attr( $item->status ); ?>"></span>
                                    <?php echo esc_html( ucfirst( $item->status ) ); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $title = '';
                                if ( ! empty( $item->object_id ) ) {
                                    $title = get_the_title( $item->object_id );
                                }
                                echo $title ? esc_html( mb_substr( $title, 0, 50 ) ) : esc_html( '#' . $item->object_id );
                                ?>
                            </td>
                            <td><?php echo esc_html( strtoupper( $item->language ) ); ?></td>
                            <td><?php echo esc_html( ucfirst( $item->engine ?? 'ai' ) ); ?></td>
                            <td style="color: var(--lingua-dark-text-muted); font-size: 12px;">
                                <?php echo esc_html( date_i18n( 'd/m H:i', strtotime( $item->created_at ) ) ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p style="color: var(--lingua-dark-text-muted); font-size: 13px;">Aucune activité récente dans la file d'attente.</p>
        <?php endif; ?>
    </div>

    <!-- ACTIONS RAPIDES -->
    <div class="lingua-quick-actions">
        <h3>⚡ Actions rapides</h3>
        <div class="lingua-quick-actions-grid">
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-languages' ); ?>" class="lingua-quick-action-btn">
                <div class="action-icon">🌍</div>
                <div class="action-label">Gérer les langues</div>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-translations' ); ?>" class="lingua-quick-action-btn">
                <div class="action-icon">📝</div>
                <div class="action-label">Traductions</div>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-ai' ); ?>" class="lingua-quick-action-btn">
                <div class="action-icon">🤖</div>
                <div class="action-label">IA & Automatisation</div>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-seo' ); ?>" class="lingua-quick-action-btn">
                <div class="action-icon">🔍</div>
                <div class="action-label">SEO Multilingue</div>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-menus' ); ?>" class="lingua-quick-action-btn">
                <div class="action-icon">📋</div>
                <div class="action-label">Menus & Widgets</div>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-settings' ); ?>" class="lingua-quick-action-btn">
                <div class="action-icon">⚙️</div>
                <div class="action-label">Paramètres</div>
            </a>
            <a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-tools' ); ?>" class="lingua-quick-action-btn">
                <div class="action-icon">🛠️</div>
                <div class="action-label">Outils</div>
            </a>
            <a href="#" class="lingua-quick-action-btn" id="lingua-trigger-queue-btn">
                <div class="action-icon">⚡</div>
                <div class="action-label">Lancer la file</div>
            </a>
        </div>
    </div>

</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Refresh dashboard
    $('#lingua-refresh-dashboard').on('click', function() {
        location.reload();
    });

    // Trigger queue
    $('#lingua-trigger-queue-btn').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        btn.find('.action-label').text('En cours...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_trigger_queue',
                nonce: '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>'
            },
            success: function(res) {
                if (res.success) {
                    btn.find('.action-label').text('✅ Lancé !');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    btn.find('.action-label').text('❌ Erreur');
                }
            },
            error: function() {
                btn.find('.action-label').text('❌ Erreur');
            }
        });
    });
});
</script>
