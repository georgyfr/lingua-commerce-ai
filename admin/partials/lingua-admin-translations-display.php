<?php
/**
 * Vue pour la page Traductions
 * Vues liste/masonry/cascade, éditeur modal, intégration traduction IA
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) { die; }

// Paramètres de filtrage
$current_view    = isset( $_GET['view'] ) ? sanitize_text_field( $_GET['view'] ) : 'list';
$current_status  = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
$current_lang    = isset( $_GET['lang'] ) ? sanitize_text_field( $_GET['lang'] ) : 'all';
$current_type    = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'all';
$current_page    = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page        = 20;

// Récupération des traductions
global $wpdb;
$table_translations = $wpdb->prefix . 'lingua_translations';

// Construction de la requête
$where = array( '1=1' );
if ( $current_status !== 'all' ) {
    $where[] = $wpdb->prepare( 't.status = %s', $current_status );
}
if ( $current_lang !== 'all' ) {
    $where[] = $wpdb->prepare( 't.language = %s', $current_lang );
}
if ( $current_type !== 'all' ) {
    $where[] = $wpdb->prepare( 't.object_type = %s', $current_type );
}

$where_clause = implode( ' AND ', $where );

$offset = ( $current_page - 1 ) * $per_page;

$translations = array();
$total_items = 0;

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $total_items = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_translations} t WHERE {$where_clause}" );
    $translations = $wpdb->get_results(
        "SELECT t.*, p.post_title as source_title
         FROM {$table_translations} t
         LEFT JOIN {$wpdb->posts} p ON t.object_id = p.ID
         WHERE {$where_clause}
         ORDER BY t.last_updated DESC
         LIMIT {$per_page} OFFSET {$offset}"
    );
}

$total_pages = ceil( $total_items / $per_page );

// Statistiques globales
$status_counts = array(
    'all'       => 0,
    'validated' => 0,
    'pending'   => 0,
    'processing'=> 0,
    'failed'    => 0,
);
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $status_counts['all']       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_translations}" );
    $status_counts['validated'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_translations} WHERE status = 'validated'" );
    $status_counts['pending']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_translations} WHERE status = 'pending'" );
    $status_counts['processing']= (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_translations} WHERE status = 'processing'" );
    $status_counts['failed']    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_translations} WHERE status = 'failed'" );
}

// Langues disponibles pour le filtre
$available_langs = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $lang_rows = $wpdb->get_results( "SELECT DISTINCT language FROM {$table_translations} ORDER BY language" );
    foreach ( $lang_rows as $row ) {
        $available_langs[] = $row->language;
    }
}

// Types de contenu disponibles
$available_types = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $type_rows = $wpdb->get_results( "SELECT DISTINCT object_type FROM {$table_translations} ORDER BY object_type" );
    foreach ( $type_rows as $row ) {
        $available_types[] = $row->object_type;
    }
}

// Drapeaux
$lang_flags = array(
    'en' => '🇬🇧', 'fr' => '🇫🇷', 'de' => '🇩🇪', 'es' => '🇪🇸', 'it' => '🇮🇹',
    'pt' => '🇵🇹', 'nl' => '🇳🇱', 'ru' => '🇷🇺', 'zh' => '🇨🇳', 'ja' => '🇯🇵',
    'ar' => '🇸🇦', 'ko' => '🇰🇷', 'tr' => '🇹🇷', 'pl' => '🇵🇱', 'sv' => '🇸🇪',
);

// Icônes de type
$type_icons = array(
    'post' => '📄', 'page' => '📃', 'product' => '🛒',
    'category' => '🏷️', 'product_cat' => '🏷️',
    'tag' => '🔖', 'product_tag' => '🔖',
    'nav_menu_item' => '📋', 'custom' => '⚙️',
);

$type_labels = array(
    'post' => 'Article', 'page' => 'Page', 'product' => 'Produit',
    'category' => 'Catégorie', 'product_cat' => 'Cat. Produit',
    'tag' => 'Étiquette', 'product_tag' => 'Étq. Produit',
);

?>

<style>
    .lingua-translations-page { max-width: 1200px; }
    .lingua-translations-page h1 { margin-bottom: 5px; }
    .lingua-translations-page .translations-subtitle { color: #666; margin-bottom: 20px; font-size: 14px; }

    /* STATUS TABS */
    .lingua-status-tabs {
        display: flex;
        gap: 0;
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .lingua-status-tab {
        flex: 1;
        padding: 12px 16px;
        text-align: center;
        font-size: 13px;
        font-weight: 500;
        color: #666;
        cursor: pointer;
        transition: all 0.2s;
        border-right: 1px solid #eee;
        text-decoration: none;
    }

    .lingua-status-tab:last-child { border-right: none; }
    .lingua-status-tab:hover { background: #f9f9f9; color: #1d2327; }
    .lingua-status-tab.active { background: #2271b1; color: #fff; }

    .lingua-status-tab .tab-count {
        display: inline-block;
        padding: 1px 7px;
        border-radius: 10px;
        font-size: 11px;
        margin-left: 6px;
        background: rgba(0,0,0,0.1);
    }

    .lingua-status-tab.active .tab-count { background: rgba(255,255,255,0.25); }

    /* TOOLBAR */
    .lingua-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .lingua-toolbar select,
    .lingua-toolbar input {
        font-size: 13px;
        padding: 6px 10px;
    }

    .lingua-toolbar .toolbar-search {
        flex: 1;
        min-width: 200px;
    }

    /* VIEW SWITCHER */
    .lingua-view-switcher {
        display: flex;
        gap: 2px;
        background: #f0f0f0;
        border-radius: 6px;
        padding: 2px;
    }

    .lingua-view-switcher .view-btn {
        padding: 6px 12px;
        border: none;
        background: transparent;
        cursor: pointer;
        border-radius: 4px;
        font-size: 13px;
        color: #666;
        transition: all 0.2s;
    }

    .lingua-view-switcher .view-btn.active {
        background: #fff;
        color: #1d2327;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* LIST VIEW */
    .lingua-list-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        overflow: hidden;
    }

    .lingua-list-table th {
        text-align: left;
        padding: 12px 14px;
        background: #f6f7f7;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #666;
        border-bottom: 2px solid #ddd;
    }

    .lingua-list-table td {
        padding: 10px 14px;
        font-size: 13px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .lingua-list-table tr:hover td { background: #f9fafb; }

    .lingua-list-table .col-cb { width: 30px; }
    .lingua-list-table .col-status { width: 110px; }
    .lingua-list-table .col-lang { width: 70px; }
    .lingua-list-table .col-type { width: 90px; }
    .lingua-list-table .col-engine { width: 90px; }
    .lingua-list-table .col-date { width: 110px; }
    .lingua-list-table .col-actions { width: 120px; }

    /* STATUS BADGE */
    .lingua-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        padding: 3px 10px;
        border-radius: 12px;
        font-weight: 500;
    }

    .lingua-status-badge .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
    }

    .lingua-status-badge.status-validated { background: #d1f7d1; color: #047857; }
    .lingua-status-badge.status-validated .status-dot { background: #00a32a; }
    .lingua-status-badge.status-pending { background: #fff3cd; color: #856404; }
    .lingua-status-badge.status-pending .status-dot { background: #dba617; }
    .lingua-status-badge.status-processing { background: #d1e4f7; color: #0073aa; }
    .lingua-status-badge.status-processing .status-dot { background: #2271b1; animation: pulse 1.5s infinite; }
    .lingua-status-badge.status-failed { background: #f7d1d1; color: #b91c1c; }
    .lingua-status-badge.status-failed .status-dot { background: #d63638; }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    /* MASONRY VIEW */
    .lingua-masonry-grid {
        columns: 3;
        column-gap: 16px;
        margin-bottom: 20px;
    }

    .lingua-masonry-item {
        break-inside: avoid;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
        transition: all 0.2s;
        cursor: pointer;
    }

    .lingua-masonry-item:hover {
        border-color: #2271b1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .lingua-masonry-item .masonry-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }

    .lingua-masonry-item .masonry-title {
        font-size: 14px;
        font-weight: 600;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .lingua-masonry-item .masonry-excerpt {
        font-size: 12px;
        color: #666;
        line-height: 1.5;
        margin-bottom: 10px;
        max-height: 80px;
        overflow: hidden;
    }

    .lingua-masonry-item .masonry-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 11px;
        color: #999;
    }

    /* CASCADE VIEW */
    .lingua-cascade-container {
        margin-bottom: 20px;
    }

    .lingua-cascade-group {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 16px;
        overflow: hidden;
    }

    .lingua-cascade-group-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 18px;
        background: #f6f7f7;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }

    .lingua-cascade-group-header .group-title {
        font-size: 14px;
        font-weight: 600;
        flex: 1;
    }

    .lingua-cascade-group-header .group-count {
        font-size: 12px;
        color: #666;
    }

    .lingua-cascade-group-header .group-arrow {
        font-size: 12px;
        color: #999;
        transition: transform 0.2s;
    }

    .lingua-cascade-group.open .group-arrow { transform: rotate(90deg); }

    .lingua-cascade-group-body {
        display: none;
        padding: 0;
    }

    .lingua-cascade-group.open .lingua-cascade-group-body { display: block; }

    .lingua-cascade-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 18px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }

    .lingua-cascade-row:last-child { border-bottom: none; }
    .lingua-cascade-row:hover { background: #f9fafb; }

    .lingua-cascade-row .cascade-flag { font-size: 18px; }
    .lingua-cascade-row .cascade-lang { font-weight: 500; min-width: 60px; }
    .lingua-cascade-row .cascade-status { min-width: 110px; }
    .lingua-cascade-row .cascade-actions { margin-left: auto; }

    /* EDITOR MODAL */
    .lingua-modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.6);
        z-index: 100000;
        align-items: center;
        justify-content: center;
    }

    .lingua-modal-overlay.active { display: flex; }

    .lingua-modal {
        background: #fff;
        border-radius: 12px;
        width: 90%;
        max-width: 800px;
        max-height: 85vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px rgba(0,0,0,0.25);
    }

    .lingua-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 24px;
        border-bottom: 1px solid #eee;
    }

    .lingua-modal-header h2 {
        margin: 0;
        font-size: 16px;
    }

    .lingua-modal-close {
        cursor: pointer;
        font-size: 20px;
        color: #999;
        background: none;
        border: none;
        padding: 4px 8px;
    }

    .lingua-modal-close:hover { color: #333; }

    .lingua-modal-body {
        padding: 24px;
        overflow-y: auto;
        flex: 1;
    }

    .lingua-modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 24px;
        border-top: 1px solid #eee;
        background: #f9f9f9;
    }

    /* EDITOR COMPARISON */
    .lingua-editor-comparison {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .lingua-editor-pane {
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
    }

    .lingua-editor-pane-header {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: #f6f7f7;
        border-bottom: 1px solid #ddd;
        font-size: 13px;
        font-weight: 600;
    }

    .lingua-editor-pane-body {
        padding: 14px;
    }

    .lingua-editor-pane-body textarea {
        width: 100%;
        min-height: 200px;
        font-size: 14px;
        line-height: 1.6;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        resize: vertical;
    }

    .lingua-editor-pane-body textarea:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }

    .lingua-editor-pane-body .readonly-text {
        font-size: 14px;
        line-height: 1.6;
        color: #333;
        min-height: 200px;
        padding: 10px;
        background: #fafafa;
        border-radius: 4px;
    }

    /* PAGINATION */
    .lingua-pagination {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin-top: 20px;
    }

    .lingua-pagination a,
    .lingua-pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
        text-decoration: none;
        color: #2271b1;
        transition: all 0.2s;
    }

    .lingua-pagination a:hover { background: #f0f6fc; border-color: #2271b1; }
    .lingua-pagination span.current { background: #2271b1; color: #fff; border-color: #2271b1; }
    .lingua-pagination span.dots { border: none; color: #666; }

    /* BULK ACTIONS */
    .lingua-bulk-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* AI TRANSLATE BUTTON */
    .lingua-ai-translate-btn {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .lingua-ai-translate-btn:hover { opacity: 0.9; transform: translateY(-1px); }

    @media (max-width: 782px) {
        .lingua-masonry-grid { columns: 1; }
        .lingua-editor-comparison { grid-template-columns: 1fr; }
        .lingua-toolbar { flex-direction: column; align-items: stretch; }
    }
</style>

<div class="wrap lingua-translations-page">
    <h1>📝 Traductions</h1>
    <p class="translations-subtitle">Gérez et éditez toutes les traductions de votre site.</p>

    <!-- STATUS TABS -->
    <div class="lingua-status-tabs">
        <a href="<?php echo esc_url( add_query_arg( 'status', 'all' ) ); ?>"
           class="lingua-status-tab <?php echo $current_status === 'all' ? 'active' : ''; ?>">
            Tout <span class="tab-count"><?php echo number_format( $status_counts['all'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'status', 'validated' ) ); ?>"
           class="lingua-status-tab <?php echo $current_status === 'validated' ? 'active' : ''; ?>">
            ✅ Complétées <span class="tab-count"><?php echo number_format( $status_counts['validated'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'status', 'pending' ) ); ?>"
           class="lingua-status-tab <?php echo $current_status === 'pending' ? 'active' : ''; ?>">
            ⏳ En attente <span class="tab-count"><?php echo number_format( $status_counts['pending'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'status', 'processing' ) ); ?>"
           class="lingua-status-tab <?php echo $current_status === 'processing' ? 'active' : ''; ?>">
            ⚡ En cours <span class="tab-count"><?php echo number_format( $status_counts['processing'] ); ?></span>
        </a>
        <a href="<?php echo esc_url( add_query_arg( 'status', 'failed' ) ); ?>"
           class="lingua-status-tab <?php echo $current_status === 'failed' ? 'active' : ''; ?>">
            ❌ Échouées <span class="tab-count"><?php echo number_format( $status_counts['failed'] ); ?></span>
        </a>
    </div>

    <!-- TOOLBAR -->
    <div class="lingua-toolbar">
        <div class="lingua-bulk-actions">
            <select id="lingua-bulk-action">
                <option value="">Actions groupées</option>
                <option value="translate">🤖 Traduire avec l'IA</option>
                <option value="retranslate">🔄 Re-traduire</option>
                <option value="delete">🗑️ Supprimer</option>
                <option value="export">📥 Exporter</option>
            </select>
            <button type="button" id="lingua-apply-bulk" class="button">Appliquer</button>
        </div>

        <select id="lingua-filter-lang">
            <option value="all" <?php selected( $current_lang, 'all' ); ?>>Toutes les langues</option>
            <?php foreach ( $available_langs as $lang ) :
                $short = substr( $lang, 0, 2 );
                $flag = isset( $lang_flags[ $short ] ) ? $lang_flags[ $short ] . ' ' : '';
            ?>
                <option value="<?php echo esc_attr( $lang ); ?>" <?php selected( $current_lang, $lang ); ?>>
                    <?php echo esc_html( $flag . strtoupper( $lang ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select id="lingua-filter-type">
            <option value="all" <?php selected( $current_type, 'all' ); ?>>Tous les types</option>
            <?php foreach ( $available_types as $type ) :
                $label = isset( $type_labels[ $type ] ) ? $type_labels[ $type ] : ucfirst( $type );
            ?>
                <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $current_type, $type ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" id="lingua-search-translations" class="toolbar-search" placeholder="🔍 Rechercher..." value="">

        <div class="lingua-view-switcher">
            <button class="view-btn <?php echo $current_view === 'list' ? 'active' : ''; ?>" data-view="list" title="Vue liste">📋</button>
            <button class="view-btn <?php echo $current_view === 'masonry' ? 'active' : ''; ?>" data-view="masonry" title="Vue masonry">🧱</button>
            <button class="view-btn <?php echo $current_view === 'cascade' ? 'active' : ''; ?>" data-view="cascade" title="Vue cascade">📚</button>
        </div>

        <button type="button" class="lingua-ai-translate-btn" id="lingua-ai-translate-all">
            🤖 Traduire tout avec l'IA
        </button>
    </div>

    <!-- LIST VIEW -->
    <?php if ( $current_view === 'list' ) : ?>
        <table class="lingua-list-table">
            <thead>
                <tr>
                    <th class="col-cb"><input type="checkbox" id="lingua-select-all"></th>
                    <th>Contenu source</th>
                    <th class="col-status">Statut</th>
                    <th class="col-lang">Langue</th>
                    <th class="col-type">Type</th>
                    <th class="col-engine">Moteur</th>
                    <th class="col-date">Date</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $translations ) ) : ?>
                    <?php foreach ( $translations as $tr ) :
                        $short_lang = substr( $tr->language, 0, 2 );
                        $flag = isset( $lang_flags[ $short_lang ] ) ? $lang_flags[ $short_lang ] : '🏳️';
                        $icon = isset( $type_icons[ $tr->object_type ] ) ? $type_icons[ $tr->object_type ] : '📄';
                        $type_label = isset( $type_labels[ $tr->object_type ] ) ? $type_labels[ $tr->object_type ] : ucfirst( $tr->object_type );
                        $title = $tr->source_title ? $tr->source_title : '(sans titre)';
                    ?>
                        <tr data-id="<?php echo esc_attr( $tr->id ); ?>">
                            <td class="col-cb">
                                <input type="checkbox" class="lingua-row-check" value="<?php echo esc_attr( $tr->id ); ?>">
                            </td>
                            <td>
                                <strong class="lingua-edit-translation" data-id="<?php echo esc_attr( $tr->id ); ?>" style="cursor:pointer; color:#2271b1;">
                                    <?php echo esc_html( mb_substr( $title, 0, 60 ) ); ?><?php echo strlen( $title ) > 60 ? '...' : ''; ?>
                                </strong>
                            </td>
                            <td>
                                <span class="lingua-status-badge status-<?php echo esc_attr( $tr->status ); ?>">
                                    <span class="status-dot"></span>
                                    <?php echo esc_html( ucfirst( $tr->status ) ); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( $flag . ' ' . strtoupper( $short_lang ) ); ?></td>
                            <td><?php echo esc_html( $icon . ' ' . $type_label ); ?></td>
                            <td><?php echo esc_html( ucfirst( $tr->source ?? 'ai' ) ); ?></td>
                            <td style="font-size:12px; color:#666;">
                                <?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $tr->last_updated ) ) ); ?>
                            </td>
                            <td>
                                <button class="button button-small lingua-edit-btn" data-id="<?php echo esc_attr( $tr->id ); ?>">✏️</button>
                                <?php if ( $tr->status === 'pending' || $tr->status === 'failed' ) : ?>
                                    <button class="button button-small lingua-translate-btn" data-id="<?php echo esc_attr( $tr->id ); ?>">🤖</button>
                                <?php endif; ?>
                                <button class="button button-small lingua-delete-btn" data-id="<?php echo esc_attr( $tr->id ); ?>" style="color:#d63638;">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px; color:#999;">
                            <p style="font-size:32px; margin:0;">📭</p>
                            <p>Aucune traduction trouvée pour ces critères.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <!-- MASONRY VIEW -->
    <?php elseif ( $current_view === 'masonry' ) : ?>
        <div class="lingua-masonry-grid">
            <?php if ( ! empty( $translations ) ) : ?>
                <?php foreach ( $translations as $tr ) :
                    $short_lang = substr( $tr->language, 0, 2 );
                    $flag = isset( $lang_flags[ $short_lang ] ) ? $lang_flags[ $short_lang ] : '🏳️';
                    $title = $tr->source_title ? $tr->source_title : '(sans titre)';
                    $excerpt = $tr->translated_text ? mb_substr( strip_tags( $tr->translated_text ), 0, 120 ) : 'Pas encore traduit...';
                ?>
                    <div class="lingua-masonry-item lingua-edit-translation" data-id="<?php echo esc_attr( $tr->id ); ?>">
                        <div class="masonry-header">
                            <span class="lingua-status-badge status-<?php echo esc_attr( $tr->status ); ?>">
                                <span class="status-dot"></span>
                                <?php echo esc_html( ucfirst( $tr->status ) ); ?>
                            </span>
                            <span class="masonry-title"><?php echo esc_html( $title ); ?></span>
                            <span><?php echo esc_html( $flag ); ?></span>
                        </div>
                        <div class="masonry-excerpt"><?php echo esc_html( $excerpt ); ?></div>
                        <div class="masonry-footer">
                            <span><?php echo esc_html( ucfirst( $tr->object_type ) ); ?></span>
                            <span><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $tr->last_updated ) ) ); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align:center; padding:60px; color:#999; column-span: all;">
                    <p style="font-size:32px;">📭</p>
                    <p>Aucune traduction trouvée.</p>
                </div>
            <?php endif; ?>
        </div>

    <!-- CASCADE VIEW -->
    <?php elseif ( $current_view === 'cascade' ) : ?>
        <div class="lingua-cascade-container">
            <?php
            // Group by source
            $cascade_groups = array();
            if ( ! empty( $translations ) ) {
                foreach ( $translations as $tr ) {
                    $key = $tr->object_id . '_' . $tr->object_type;
                    if ( ! isset( $cascade_groups[ $key ] ) ) {
                        $cascade_groups[ $key ] = array(
                            'title'   => $tr->source_title ? $tr->source_title : '(sans titre)',
                            'type'    => $tr->object_type,
                            'items'   => array(),
                        );
                    }
                    $cascade_groups[ $key ]['items'][] = $tr;
                }
            }

            if ( ! empty( $cascade_groups ) ) :
                foreach ( $cascade_groups as $key => $group ) :
                    $icon = isset( $type_icons[ $group['type'] ] ) ? $type_icons[ $group['type'] ] : '📄';
                    $completed_count = count( array_filter( $group['items'], function($i) { return $i->status === 'validated'; } ) );
                    $total_count = count( $group['items'] );
            ?>
                    <div class="lingua-cascade-group open">
                        <div class="lingua-cascade-group-header">
                            <span><?php echo esc_html( $icon ); ?></span>
                            <span class="group-title"><?php echo esc_html( $group['title'] ); ?></span>
                            <span class="group-count"><?php echo esc_html( $completed_count . '/' . $total_count . ' traduits' ); ?></span>
                            <span class="group-arrow">▶</span>
                        </div>
                        <div class="lingua-cascade-group-body">
                            <?php foreach ( $group['items'] as $tr ) :
                                $short_lang = substr( $tr->language, 0, 2 );
                                $flag = isset( $lang_flags[ $short_lang ] ) ? $lang_flags[ $short_lang ] : '🏳️';
                            ?>
                                <div class="lingua-cascade-row" data-id="<?php echo esc_attr( $tr->id ); ?>">
                                    <span class="cascade-flag"><?php echo esc_html( $flag ); ?></span>
                                    <span class="cascade-lang"><?php echo esc_html( strtoupper( $short_lang ) ); ?></span>
                                    <span class="cascade-status">
                                        <span class="lingua-status-badge status-<?php echo esc_attr( $tr->status ); ?>">
                                            <span class="status-dot"></span>
                                            <?php echo esc_html( ucfirst( $tr->status ) ); ?>
                                        </span>
                                    </span>
                                    <span style="font-size:12px; color:#999;">
                                        <?php echo esc_html( ucfirst( $tr->source ?? 'ai' ) ); ?>
                                    </span>
                                    <span class="cascade-actions">
                                        <button class="button button-small lingua-edit-btn" data-id="<?php echo esc_attr( $tr->id ); ?>">✏️ Éditer</button>
                                        <?php if ( $tr->status === 'pending' || $tr->status === 'failed' ) : ?>
                                            <button class="button button-small lingua-translate-btn" data-id="<?php echo esc_attr( $tr->id ); ?>">🤖 Traduire</button>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align:center; padding:60px; color:#999; background:#fff; border-radius:8px; border:1px solid #ddd;">
                    <p style="font-size:32px;">📭</p>
                    <p>Aucune traduction trouvée.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- PAGINATION -->
    <?php if ( $total_pages > 1 ) : ?>
        <div class="lingua-pagination">
            <?php if ( $current_page > 1 ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>">‹</a>
            <?php endif; ?>

            <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                <?php if ( $i === $current_page ) : ?>
                    <span class="current"><?php echo esc_html( $i ); ?></span>
                <?php else : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'paged', $i ) ); ?>"><?php echo esc_html( $i ); ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ( $current_page < $total_pages ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>">›</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- EDITOR MODAL -->
<div class="lingua-modal-overlay" id="lingua-editor-modal">
    <div class="lingua-modal">
        <div class="lingua-modal-header">
            <h2>✏️ Éditer la traduction</h2>
            <button class="lingua-modal-close" id="lingua-close-modal">✕</button>
        </div>
        <div class="lingua-modal-body">
            <div style="margin-bottom:16px; display:flex; gap:12px; align-items:center;">
                <span id="modal-flag" style="font-size:24px;"></span>
                <span id="modal-lang" style="font-weight:600;"></span>
                <span id="modal-type" style="font-size:12px; color:#666;"></span>
                <span id="modal-status" style="margin-left:auto;"></span>
            </div>

            <div class="lingua-editor-comparison">
                <div class="lingua-editor-pane">
                    <div class="lingua-editor-pane-header">
                        📄 Source (<span id="modal-source-lang">FR</span>)
                    </div>
                    <div class="lingua-editor-pane-body">
                        <div class="readonly-text" id="modal-source-content">Chargement...</div>
                    </div>
                </div>
                <div class="lingua-editor-pane">
                    <div class="lingua-editor-pane-header">
                        🌐 Traduction (<span id="modal-target-lang">EN</span>)
                    </div>
                    <div class="lingua-editor-pane-body">
                        <textarea id="modal-translated-content" placeholder="Entrez la traduction..."></textarea>
                    </div>
                </div>
            </div>

            <div style="margin-top:16px; display:flex; gap:8px;">
                <button type="button" class="lingua-ai-translate-btn" id="modal-ai-translate">
                    🤖 Traduire avec l'IA
                </button>
                <button type="button" class="button" id="modal-copy-source">
                    📋 Copier la source
                </button>
            </div>
        </div>
        <div class="lingua-modal-footer">
            <div style="font-size:12px; color:#666;">
                <span id="modal-word-count">0 mots</span> · <span id="modal-char-count">0 caractères</span>
            </div>
            <div style="display:flex; gap:8px;">
                <button class="button" id="modal-cancel">Annuler</button>
                <button class="button button-primary" id="modal-save">💾 Sauvegarder</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var nonce = '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>';
    var currentEditId = null;

    // View switcher
    $('.view-btn').on('click', function() {
        var view = $(this).data('view');
        var url = new URL(window.location.href);
        url.searchParams.set('view', view);
        window.location.href = url.toString();
    });

    // Select all
    $('#lingua-select-all').on('change', function() {
        $('.lingua-row-check').prop('checked', $(this).prop('checked'));
    });

    // Cascade group toggle
    $(document).on('click', '.lingua-cascade-group-header', function() {
        $(this).closest('.lingua-cascade-group').toggleClass('open');
    });

    // Open editor modal
    $(document).on('click', '.lingua-edit-btn, .lingua-edit-translation', function(e) {
        if ($(e.target).is('button')) return;
        var id = $(this).data('id') || $(this).closest('[data-id]').data('id');
        if (!id) return;

        currentEditId = id;
        $('#lingua-editor-modal').addClass('active');

        // Load translation data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_get_translation',
                id: id,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    var data = res.data;
                    $('#modal-flag').text(data.flag || '');
                    $('#modal-lang').text(data.language || '');
                    $('#modal-type').text(data.object_type || '');
                    $('#modal-status').html(
                        '<span class="lingua-status-badge status-' + data.status + '">' +
                        '<span class="status-dot"></span>' + data.status + '</span>'
                    );
                    $('#modal-source-lang').text(data.source_lang || 'FR');
                    $('#modal-target-lang').text(data.language ? data.language.substring(0, 2).toUpperCase() : 'EN');
                    $('#modal-source-content').text(data.source_content || 'Pas de contenu source');
                    $('#modal-translated-content').val(data.translated_text || '');
                    updateCounts();
                }
            }
        });
    });

    // Close modal
    $('#lingua-close-modal, #modal-cancel').on('click', function() {
        $('#lingua-editor-modal').removeClass('active');
        currentEditId = null;
    });

    // Save translation
    $('#modal-save').on('click', function() {
        if (!currentEditId) return;
        var btn = $(this);
        btn.prop('disabled', true).text('⏳ Sauvegarde...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_save_translation',
                id: currentEditId,
                content: $('#modal-translated-content').val(),
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    btn.text('✅ Sauvegardé !');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    btn.text('❌ Erreur');
                    setTimeout(function() { btn.prop('disabled', false).text('💾 Sauvegarder'); }, 2000);
                }
            },
            error: function() {
                btn.text('❌ Erreur serveur');
                setTimeout(function() { btn.prop('disabled', false).text('💾 Sauvegarder'); }, 2000);
            }
        });
    });

    // AI translate in modal
    $('#modal-ai-translate').on('click', function() {
        if (!currentEditId) return;
        var btn = $(this);
        btn.prop('disabled', true).html('⏳ Traduction IA...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_ai_translate_single',
                id: currentEditId,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    $('#modal-translated-content').val(res.data.translated_text);
                    btn.html('✅ Traduit !');
                    updateCounts();
                } else {
                    btn.html('❌ Erreur');
                }
            },
            error: function() {
                btn.html('❌ Erreur serveur');
            },
            complete: function() {
                setTimeout(function() {
                    btn.prop('disabled', false).html('🤖 Traduire avec l\'IA');
                }, 2000);
            }
        });
    });

    // Copy source to translation
    $('#modal-copy-source').on('click', function() {
        var sourceText = $('#modal-source-content').text();
        $('#modal-translated-content').val(sourceText);
        updateCounts();
    });

    // Word/char count
    function updateCounts() {
        var text = $('#modal-translated-content').val();
        var words = text.trim() ? text.trim().split(/\s+/).length : 0;
        var chars = text.length;
        $('#modal-word-count').text(words + ' mots');
        $('#modal-char-count').text(chars + ' caractères');
    }

    $('#modal-translated-content').on('input', updateCounts);

    // Delete translation
    $(document).on('click', '.lingua-delete-btn', function() {
        if (!confirm('Supprimer cette traduction ?')) return;
        var id = $(this).data('id');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_delete_translation',
                id: id,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    $('tr[data-id="' + id + '"]').fadeOut(300, function() { $(this).remove(); });
                    $('.lingua-cascade-row[data-id="' + id + '"]').fadeOut(300, function() { $(this).remove(); });
                }
            }
        });
    });

    // Translate single
    $(document).on('click', '.lingua-translate-btn', function() {
        var id = $(this).data('id');
        var btn = $(this);
        btn.prop('disabled', true).text('⏳');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_ai_translate_single',
                id: id,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    btn.text('✅');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    btn.text('❌');
                }
            }
        });
    });

    // Bulk actions
    $('#lingua-apply-bulk').on('click', function() {
        var action = $('#lingua-bulk-action').val();
        if (!action) return;

        var ids = [];
        $('.lingua-row-check:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            alert('Veuillez sélectionner au moins une traduction.');
            return;
        }

        if (action === 'delete' && !confirm('Supprimer les traductions sélectionnées ?')) return;
        if (action === 'translate' && !confirm('Lancer la traduction IA pour les éléments sélectionnés ?')) return;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_bulk_action',
                bulk_action: action,
                ids: ids,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    alert('✅ Action effectuée avec succès !');
                    location.reload();
                } else {
                    alert('❌ Erreur : ' + res.data);
                }
            }
        });
    });

    // AI translate all
    $('#lingua-ai-translate-all').on('click', function() {
        if (!confirm('Lancer la traduction IA pour tous les contenus en attente ?')) return;
        var btn = $(this);
        btn.prop('disabled', true).html('⏳ Traduction en cours...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_trigger_queue',
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    btn.html('✅ Traduction lancée !');
                    setTimeout(function() { location.reload(); }, 2000);
                }
            },
            complete: function() {
                setTimeout(function() {
                    btn.prop('disabled', false).html('🤖 Traduire tout avec l\'IA');
                }, 3000);
            }
        });
    });

    // Filters
    $('#lingua-filter-lang, #lingua-filter-type').on('change', function() {
        var url = new URL(window.location.href);
        url.searchParams.set('lang', $('#lingua-filter-lang').val());
        url.searchParams.set('type', $('#lingua-filter-type').val());
        url.searchParams.set('paged', '1');
        window.location.href = url.toString();
    });

    // Close modal on overlay click
    $('#lingua-editor-modal').on('click', function(e) {
        if ($(e.target).is('#lingua-editor-modal')) {
            $('#lingua-editor-modal').removeClass('active');
            currentEditId = null;
        }
    });

    // Close modal on Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#lingua-editor-modal').hasClass('active')) {
            $('#lingua-editor-modal').removeClass('active');
            currentEditId = null;
        }
    });
});
</script>
