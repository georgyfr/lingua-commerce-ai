<?php
/**
 * Vue pour la page SEO Multilingue
 * Audit SEO, génération de sitemap, détection de plugins, hreflang
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) { die; }

// Récupération des réglages
$settings     = get_option( 'lingua_commerce_ai_settings', array() );
$seo_settings = get_option( 'lingua_commerce_ai_seo_settings', array() );

$hreflang_enabled    = isset( $seo_settings['hreflang_enabled'] ) ? $seo_settings['hreflang_enabled'] : 1;
$og_enabled          = isset( $seo_settings['og_enabled'] ) ? $seo_settings['og_enabled'] : 1;
$sitemap_enabled     = isset( $seo_settings['sitemap_enabled'] ) ? $seo_settings['sitemap_enabled'] : 1;
$canonical_enabled   = isset( $seo_settings['canonical_enabled'] ) ? $seo_settings['canonical_enabled'] : 1;
$auto_meta_translate = isset( $seo_settings['auto_meta_translate'] ) ? $seo_settings['auto_meta_translate'] : 1;
$auto_alt_translate  = isset( $seo_settings['auto_alt_translate'] ) ? $seo_settings['auto_alt_translate'] : 1;
$sitemap_frequency   = isset( $seo_settings['sitemap_frequency'] ) ? $seo_settings['sitemap_frequency'] : 'daily';

// Détection des plugins SEO
$detected_plugins = array();
if ( is_plugin_active( 'yoast-seo/wp-seo.php' ) || is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
    $detected_plugins[] = array( 'name' => 'Yoast SEO', 'slug' => 'yoast', 'status' => 'active', 'icon' => '🎯', 'compat' => 'full' );
} elseif ( file_exists( WP_PLUGIN_DIR . '/yoast-seo/wp-seo.php' ) || file_exists( WP_PLUGIN_DIR . '/wordpress-seo/wp-seo.php' ) ) {
    $detected_plugins[] = array( 'name' => 'Yoast SEO', 'slug' => 'yoast', 'status' => 'inactive', 'icon' => '🎯', 'compat' => 'full' );
}

if ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
    $detected_plugins[] = array( 'name' => 'All in One SEO', 'slug' => 'aioseo', 'status' => 'active', 'icon' => '🔧', 'compat' => 'full' );
} elseif ( file_exists( WP_PLUGIN_DIR . '/all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
    $detected_plugins[] = array( 'name' => 'All in One SEO', 'slug' => 'aioseo', 'status' => 'inactive', 'icon' => '🔧', 'compat' => 'full' );
}

if ( is_plugin_active( 'rank-math/rank-math.php' ) || is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
    $detected_plugins[] = array( 'name' => 'Rank Math', 'slug' => 'rankmath', 'status' => 'active', 'icon' => '📊', 'compat' => 'full' );
} elseif ( file_exists( WP_PLUGIN_DIR . '/rank-math/rank-math.php' ) || file_exists( WP_PLUGIN_DIR . '/seo-by-rank-math/rank-math.php' ) ) {
    $detected_plugins[] = array( 'name' => 'Rank Math', 'slug' => 'rankmath', 'status' => 'inactive', 'icon' => '📊', 'compat' => 'full' );
}

if ( is_plugin_active( 'wp-seopress/seopress.php' ) ) {
    $detected_plugins[] = array( 'name' => 'SEOPress', 'slug' => 'seopress', 'status' => 'active', 'icon' => '🚀', 'compat' => 'partial' );
} elseif ( file_exists( WP_PLUGIN_DIR . '/wp-seopress/seopress.php' ) ) {
    $detected_plugins[] = array( 'name' => 'SEOPress', 'slug' => 'seopress', 'status' => 'inactive', 'icon' => '🚀', 'compat' => 'partial' );
}

// Audit SEO — vérification des problèmes courants
$audit_issues = array();

// Vérifier hreflang
if ( ! $hreflang_enabled ) {
    $audit_issues[] = array(
        'severity' => 'critical',
        'icon'     => '🔴',
        'title'    => 'Balises hreflang désactivées',
        'desc'     => 'Les moteurs de recherche ne pourront pas identifier les versions linguistiques de vos pages.',
        'fix'      => 'Activez les balises hreflang dans les réglages ci-dessous.',
    );
}

// Vérifier le sitemap
if ( ! $sitemap_enabled ) {
    $audit_issues[] = array(
        'severity' => 'warning',
        'icon'     => '🟡',
        'title'    => 'Sitemap multilingue désactivé',
        'desc'     => 'Google ne pourra pas découvrir toutes vos pages traduites facilement.',
        'fix'      => 'Activez la génération du sitemap multilingue.',
    );
}

// Vérifier les métadonnées traduites
if ( ! $auto_meta_translate ) {
    $audit_issues[] = array(
        'severity' => 'warning',
        'icon'     => '🟡',
        'title'    => 'Méta-descriptions non traduites',
        'desc'     => 'Les titres SEO et méta-descriptions restent dans la langue source.',
        'fix'      => 'Activez la traduction automatique des métadonnées.',
    );
}

// Vérifier les ALT d'images
if ( ! $auto_alt_translate ) {
    $audit_issues[] = array(
        'severity' => 'info',
        'icon'     => '🔵',
        'title'    => 'Textes ALT non traduits',
        'desc'     => 'Les attributs alt des images ne sont pas traduits automatiquement.',
        'fix'      => 'Activez la traduction des textes alternatifs.',
    );
}

// Vérifier la structure d'URL
$url_mode = isset( $settings['url_mode'] ) ? $settings['url_mode'] : 'param';
if ( $url_mode === 'param' ) {
    $audit_issues[] = array(
        'severity' => 'info',
        'icon'     => '🔵',
        'title'    => 'Structure URL en paramètre',
        'desc'     => 'La structure ?lang=en est fonctionnelle mais /en/ est préférable pour le SEO.',
        'fix'      => 'Passez en structure de répertoire dans Paramètres > Général.',
    );
}

// Si aucun problème
if ( empty( $audit_issues ) ) {
    $audit_issues[] = array(
        'severity' => 'success',
        'icon'     => '🟢',
        'title'    => 'Aucun problème détecté',
        'desc'     => 'Votre configuration SEO multilingue semble optimale !',
        'fix'      => '',
    );
}

// Statistiques SEO
global $wpdb;
$table_translations = $wpdb->prefix . 'lingua_translations';
$seo_translated_count = 0;
$seo_total_count = 0;
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $seo_translated_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_translations} WHERE content_type IN ('post', 'page', 'product') AND status = 'completed'" );
    $seo_total_count = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('post', 'page', 'product')" );
}

?>

<style>
    .lingua-seo-page { max-width: 1200px; }
    .lingua-seo-page h1 { margin-bottom: 5px; }
    .lingua-seo-page .seo-subtitle { color: #666; margin-bottom: 25px; font-size: 14px; }

    .lingua-seo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .lingua-seo-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
    }

    .lingua-seo-card h2 {
        margin: 0 0 15px 0;
        font-size: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .lingua-seo-card h3 {
        margin: 15px 0 10px 0;
        font-size: 14px;
    }

    /* AUDIT TABLE */
    .lingua-audit-table {
        width: 100%;
        border-collapse: collapse;
    }

    .lingua-audit-table th {
        text-align: left;
        padding: 10px;
        background: #f6f7f7;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #ddd;
    }

    .lingua-audit-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
        vertical-align: top;
    }

    .lingua-audit-table tr:hover td { background: #f9f9f9; }

    .audit-severity-critical { color: #d63638; font-weight: 600; }
    .audit-severity-warning { color: #dba617; font-weight: 600; }
    .audit-severity-info { color: #2271b1; font-weight: 600; }
    .audit-severity-success { color: #00a32a; font-weight: 600; }

    /* PLUGIN DETECTION */
    .lingua-plugin-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #eee;
    }

    .lingua-plugin-item:last-child { border-bottom: none; }

    .lingua-plugin-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .lingua-plugin-icon { font-size: 28px; }

    .lingua-plugin-name { font-weight: 600; font-size: 14px; }
    .lingua-plugin-status { font-size: 12px; color: #666; }

    .lingua-plugin-badge {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .lingua-plugin-badge.active { background: #d1f7d1; color: #00a32a; }
    .lingua-plugin-badge.inactive { background: #f0f0f0; color: #666; }
    .lingua-plugin-badge.compat-full { background: #d1e4f7; color: #0073aa; }
    .lingua-plugin-badge.compat-partial { background: #fff3cd; color: #856404; }

    /* SITEMAP PREVIEW */
    .lingua-sitemap-preview {
        background: #1a1d23;
        color: #e1e4e8;
        padding: 15px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 12px;
        overflow-x: auto;
        max-height: 200px;
        overflow-y: auto;
        line-height: 1.6;
    }

    .lingua-sitemap-preview .tag { color: #58a6ff; }
    .lingua-sitemap-preview .attr { color: #d29922; }
    .lingua-sitemap-preview .url { color: #3fb950; }
    .lingua-sitemap-preview .lang { color: #bc8cff; }

    /* SEO SCORE */
    .lingua-seo-score {
        text-align: center;
        padding: 20px;
    }

    .lingua-seo-score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px auto;
        font-size: 32px;
        font-weight: 700;
        color: #fff;
    }

    .lingua-seo-score-label {
        font-size: 14px;
        color: #666;
    }

    /* HREFLANG PREVIEW */
    .lingua-hreflang-preview {
        background: #f6f7f7;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 15px;
        font-family: monospace;
        font-size: 12px;
        line-height: 1.8;
        overflow-x: auto;
    }

    .lingua-hreflang-preview .hl-tag { color: #d63638; }
    .lingua-hreflang-preview .hl-attr { color: #2271b1; }
    .lingua-hreflang-preview .hl-val { color: #00a32a; }

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

    .lingua-toggle-switch input { opacity: 0; width: 0; height: 0; }

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

    .lingua-toggle-switch input:checked + .lingua-toggle-slider { background-color: #2271b1; }
    .lingua-toggle-switch input:checked + .lingua-toggle-slider:before { transform: translateX(22px); }

    @media (max-width: 782px) {
        .lingua-seo-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="wrap lingua-seo-page">
    <h1>🔍 SEO Multilingue</h1>
    <p class="seo-subtitle">Optimisez le référencement de vos contenus traduits pour les moteurs de recherche internationaux.</p>

    <!-- SCORE + AUDIT -->
    <div class="lingua-seo-grid">
        <!-- SEO Score -->
        <div class="lingua-seo-card">
            <h2>📊 Score SEO Multilingue</h2>
            <?php
            $score = 0;
            if ( $hreflang_enabled ) $score += 25;
            if ( $sitemap_enabled ) $score += 25;
            if ( $auto_meta_translate ) $score += 20;
            if ( $auto_alt_translate ) $score += 10;
            if ( $canonical_enabled ) $score += 10;
            if ( $url_mode === 'directory' ) $score += 10;
            $score_color = $score >= 80 ? '#00a32a' : ( $score >= 50 ? '#dba617' : '#d63638' );
            ?>
            <div class="lingua-seo-score">
                <div class="lingua-seo-score-circle" style="background: <?php echo esc_attr( $score_color ); ?>;">
                    <?php echo esc_html( $score ); ?>%
                </div>
                <div class="lingua-seo-score-label">
                    <?php
                    if ( $score >= 80 ) echo '✅ Configuration excellente';
                    elseif ( $score >= 50 ) echo '⚠️ Améliorations recommandées';
                    else echo '🔴 Configuration insuffisante';
                    ?>
                </div>
            </div>
        </div>

        <!-- Audit -->
        <div class="lingua-seo-card">
            <h2>🩺 Audit SEO</h2>
            <table class="lingua-audit-table">
                <thead>
                    <tr>
                        <th>Sévérité</th>
                        <th>Problème</th>
                        <th>Recommandation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $audit_issues as $issue ) : ?>
                        <tr>
                            <td>
                                <span class="audit-severity-<?php echo esc_attr( $issue['severity'] ); ?>">
                                    <?php echo esc_html( $issue['icon'] ); ?> <?php echo esc_html( ucfirst( $issue['severity'] ) ); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo esc_html( $issue['title'] ); ?></strong>
                                <br><span style="color:#666; font-size:12px;"><?php echo esc_html( $issue['desc'] ); ?></span>
                            </td>
                            <td style="font-size:12px; color:#2271b1;">
                                <?php echo esc_html( $issue['fix'] ); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RÉGLAGES SEO -->
    <div class="lingua-seo-card" style="margin-bottom: 20px;">
        <h2>⚙️ Réglages SEO</h2>
        <form method="post" action="options.php">
            <?php settings_fields( 'lingua_commerce_ai_seo_settings_group' ); ?>

            <div class="lingua-seo-grid">
                <div>
                    <h3>🏷️ Balises & Métadonnées</h3>
                    <div class="lingua-toggle-row">
                        <div>
                            <label for="hreflang-enabled">Balises hreflang</label>
                            <div class="description">Insère automatiquement les balises link rel="alternate" hreflang</div>
                        </div>
                        <div class="lingua-toggle-switch">
                            <input type="checkbox" id="hreflang-enabled" name="lingua_commerce_ai_seo_settings[hreflang_enabled]" value="1" <?php checked( $hreflang_enabled, 1 ); ?>>
                            <span class="lingua-toggle-slider"></span>
                        </div>
                    </div>
                    <div class="lingua-toggle-row">
                        <div>
                            <label for="og-enabled">Open Graph multilingue</label>
                            <div class="description">Adapte les balises og:title, og:description, og:locale</div>
                        </div>
                        <div class="lingua-toggle-switch">
                            <input type="checkbox" id="og-enabled" name="lingua_commerce_ai_seo_settings[og_enabled]" value="1" <?php checked( $og_enabled, 1 ); ?>>
                            <span class="lingua-toggle-slider"></span>
                        </div>
                    </div>
                    <div class="lingua-toggle-row">
                        <div>
                            <label for="canonical-enabled">URL canonique multilingue</label>
                            <div class="description">Définit la bonne URL canonique pour chaque langue</div>
                        </div>
                        <div class="lingua-toggle-switch">
                            <input type="checkbox" id="canonical-enabled" name="lingua_commerce_ai_seo_settings[canonical_enabled]" value="1" <?php checked( $canonical_enabled, 1 ); ?>>
                            <span class="lingua-toggle-slider"></span>
                        </div>
                    </div>
                    <div class="lingua-toggle-row">
                        <div>
                            <label for="meta-translate">Traduction auto des métadonnées</label>
                            <div class="description">Traduit automatiquement title, meta description, et titres SEO</div>
                        </div>
                        <div class="lingua-toggle-switch">
                            <input type="checkbox" id="meta-translate" name="lingua_commerce_ai_seo_settings[auto_meta_translate]" value="1" <?php checked( $auto_meta_translate, 1 ); ?>>
                            <span class="lingua-toggle-slider"></span>
                        </div>
                    </div>
                    <div class="lingua-toggle-row">
                        <div>
                            <label for="alt-translate">Traduction auto des ALT</label>
                            <div class="description">Traduit les textes alternatifs des images</div>
                        </div>
                        <div class="lingua-toggle-switch">
                            <input type="checkbox" id="alt-translate" name="lingua_commerce_ai_seo_settings[auto_alt_translate]" value="1" <?php checked( $auto_alt_translate, 1 ); ?>>
                            <span class="lingua-toggle-slider"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3>🗺️ Sitemap multilingue</h3>
                    <div class="lingua-toggle-row">
                        <div>
                            <label for="sitemap-enabled">Générer le sitemap</label>
                            <div class="description">Crée un sitemap XML multilingue pour Google Search Console</div>
                        </div>
                        <div class="lingua-toggle-switch">
                            <input type="checkbox" id="sitemap-enabled" name="lingua_commerce_ai_seo_settings[sitemap_enabled]" value="1" <?php checked( $sitemap_enabled, 1 ); ?>>
                            <span class="lingua-toggle-slider"></span>
                        </div>
                    </div>
                    <div style="padding: 12px 0;">
                        <label style="font-size:13px; font-weight:500;">Fréquence de mise à jour</label>
                        <select name="lingua_commerce_ai_seo_settings[sitemap_frequency]" style="margin-left:10px;">
                            <option value="hourly" <?php selected( $sitemap_frequency, 'hourly' ); ?>>Chaque heure</option>
                            <option value="daily" <?php selected( $sitemap_frequency, 'daily' ); ?>>Quotidienne</option>
                            <option value="weekly" <?php selected( $sitemap_frequency, 'weekly' ); ?>>Hebdomadaire</option>
                        </select>
                    </div>
                    <div style="margin-top:10px;">
                        <button type="button" id="lingua-generate-sitemap-btn" class="button button-primary">🗺️ Générer le sitemap maintenant</button>
                        <span id="lingua-sitemap-status" style="margin-left:10px; color:green;"></span>
                    </div>

                    <h3 style="margin-top:20px;">👁️ Aperçu du sitemap</h3>
                    <div class="lingua-sitemap-preview" id="sitemap-preview">
<span class="tag">&lt;?xml</span> version="1.0" encoding="UTF-8"<span class="tag">?&gt;</span>
<span class="tag">&lt;urlset</span> <span class="attr">xmlns</span>=<span class="url">"http://www.sitemaps.org/schemas/sitemap/0.9"</span>
         <span class="attr">xmlns:xhtml</span>=<span class="url">"http://www.w3.org/1999/xhtml"</span><span class="tag">&gt;</span>

  <span class="tag">&lt;url&gt;</span>
    <span class="tag">&lt;loc&gt;</span><span class="url">https://example.com/</span><span class="tag">&lt;/loc&gt;</span>
    <span class="tag">&lt;xhtml:link</span> <span class="attr">rel</span>=<span class="val">"alternate"</span> <span class="attr">hreflang</span>=<span class="lang">"fr"</span> <span class="attr">href</span>=<span class="url">"https://example.com/"</span><span class="tag">/&gt;</span>
    <span class="tag">&lt;xhtml:link</span> <span class="attr">rel</span>=<span class="val">"alternate"</span> <span class="attr">hreflang</span>=<span class="lang">"en"</span> <span class="attr">href</span>=<span class="url">"https://example.com/en/"</span><span class="tag">/&gt;</span>
    <span class="tag">&lt;xhtml:link</span> <span class="attr">rel</span>=<span class="val">"alternate"</span> <span class="attr">hreflang</span>=<span class="lang">"es"</span> <span class="attr">href</span>=<span class="url">"https://example.com/es/"</span><span class="tag">/&gt;</span>
  <span class="tag">&lt;/url&gt;</span>

<span class="tag">&lt;/urlset&gt;</span>
                    </div>
                </div>
            </div>

            <p class="submit" style="margin-top: 20px;">
                <?php submit_button( 'Sauvegarder les réglages SEO', 'primary', 'submit', false ); ?>
            </p>
        </form>
    </div>

    <!-- HREFLANG PREVIEW + PLUGIN DETECTION -->
    <div class="lingua-seo-grid">
        <!-- Hreflang Preview -->
        <div class="lingua-seo-card">
            <h2>🔗 Aperçu hreflang</h2>
            <p class="description" style="margin-bottom:12px;">Voici un exemple des balises qui seront injectées dans le head de vos pages :</p>
            <div class="lingua-hreflang-preview">
                <span class="hl-tag">&lt;link</span> <span class="hl-attr">rel=</span><span class="hl-val">"alternate"</span> <span class="hl-attr">hreflang=</span><span class="hl-val">"fr"</span> <span class="hl-attr">href=</span><span class="hl-val">"https://example.com/"</span> <span class="hl-tag">/&gt;</span><br>
                <span class="hl-tag">&lt;link</span> <span class="hl-attr">rel=</span><span class="hl-val">"alternate"</span> <span class="hl-attr">hreflang=</span><span class="hl-val">"en"</span> <span class="hl-attr">href=</span><span class="hl-val">"https://example.com/en/"</span> <span class="hl-tag">/&gt;</span><br>
                <span class="hl-tag">&lt;link</span> <span class="hl-attr">rel=</span><span class="hl-val">"alternate"</span> <span class="hl-attr">hreflang=</span><span class="hl-val">"es"</span> <span class="hl-attr">href=</span><span class="hl-val">"https://example.com/es/"</span> <span class="hl-tag">/&gt;</span><br>
                <span class="hl-tag">&lt;link</span> <span class="hl-attr">rel=</span><span class="hl-val">"alternate"</span> <span class="hl-attr">hreflang=</span><span class="hl-val">"de"</span> <span class="hl-attr">href=</span><span class="hl-val">"https://example.com/de/"</span> <span class="hl-tag">/&gt;</span><br>
                <span class="hl-tag">&lt;link</span> <span class="hl-attr">rel=</span><span class="hl-val">"alternate"</span> <span class="hl-attr">hreflang=</span><span class="hl-val">"x-default"</span> <span class="hl-attr">href=</span><span class="hl-val">"https://example.com/"</span> <span class="hl-tag">/&gt;</span>
            </div>
            <p style="margin-top:10px; font-size:12px; color:#666;">
                📌 La balise <code>x-default</code> pointe vers la langue source par défaut.
            </p>
        </div>

        <!-- Plugin Detection -->
        <div class="lingua-seo-card">
            <h2>🔌 Détection de plugins SEO</h2>
            <p class="description" style="margin-bottom:12px;">LinguaCommerce AI s'intègre automatiquement avec les plugins SEO détectés.</p>
            <?php if ( ! empty( $detected_plugins ) ) : ?>
                <?php foreach ( $detected_plugins as $plugin ) : ?>
                    <div class="lingua-plugin-item">
                        <div class="lingua-plugin-info">
                            <span class="lingua-plugin-icon"><?php echo esc_html( $plugin['icon'] ); ?></span>
                            <div>
                                <div class="lingua-plugin-name"><?php echo esc_html( $plugin['name'] ); ?></div>
                                <div class="lingua-plugin-status">
                                    <?php echo $plugin['status'] === 'active' ? '✅ Actif' : '⏸️ Inactif'; ?>
                                </div>
                            </div>
                        </div>
                        <div>
                            <span class="lingua-plugin-badge <?php echo esc_attr( $plugin['status'] ); ?>">
                                <?php echo $plugin['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                            </span>
                            <span class="lingua-plugin-badge compat-<?php echo esc_attr( $plugin['compat'] ); ?>" style="margin-left:5px;">
                                <?php echo $plugin['compat'] === 'full' ? 'Compatibilité complète' : 'Compatibilité partielle'; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align:center; padding:30px; color:#999;">
                    <p style="font-size:32px; margin:0;">🔍</p>
                    <p>Aucun plugin SEO tiers détecté.</p>
                    <p style="font-size:12px;">LinguaCommerce AI gérera les balises SEO directement.</p>
                </div>
            <?php endif; ?>

            <div style="margin-top:15px; padding:12px; background:#f0f6fc; border-radius:5px; border-left:4px solid #2271b1; font-size:12px;">
                <strong>💡 Conseil :</strong> Pour une compatibilité optimale, nous recommandons <strong>Yoast SEO</strong> ou <strong>Rank Math</strong>. LinguaCommerce AI synchronisera automatiquement les métadonnées SEO traduites.
            </div>
        </div>
    </div>

    <!-- STATISTIQUES SEO -->
    <div class="lingua-seo-card" style="margin-bottom: 20px;">
        <h2>📈 Statistiques SEO</h2>
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin-top:15px;">
            <div style="text-align:center; padding:15px; background:#f0f6fc; border-radius:8px;">
                <div style="font-size:24px; font-weight:700; color:#2271b1;"><?php echo number_format( $seo_translated_count ); ?></div>
                <div style="font-size:12px; color:#666; text-transform:uppercase;">Pages traduites</div>
            </div>
            <div style="text-align:center; padding:15px; background:#f1f8f1; border-radius:8px;">
                <div style="font-size:24px; font-weight:700; color:#00a32a;"><?php echo number_format( $seo_total_count ); ?></div>
                <div style="font-size:12px; color:#666; text-transform:uppercase;">Pages totales</div>
            </div>
            <div style="text-align:center; padding:15px; background:#fff8e5; border-radius:8px;">
                <div style="font-size:24px; font-weight:700; color:#dba617;"><?php echo esc_html( $active_languages ); ?></div>
                <div style="font-size:12px; color:#666; text-transform:uppercase;">Langues indexées</div>
            </div>
            <div style="text-align:center; padding:15px; background:#f9f0ff; border-radius:8px;">
                <div style="font-size:24px; font-weight:700; color:#7e5bef;">
                    <?php echo $seo_total_count > 0 ? number_format( ( $seo_translated_count / $seo_total_count ) * 100, 1 ) : '0'; ?>%
                </div>
                <div style="font-size:12px; color:#666; text-transform:uppercase;">Couverture SEO</div>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Génération du sitemap
    $('#lingua-generate-sitemap-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Génération...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_generate_sitemap',
                nonce: '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>'
            },
            success: function(res) {
                if (res.success) {
                    $('#lingua-sitemap-status').text('✅ Sitemap généré avec succès !');
                } else {
                    $('#lingua-sitemap-status').text('❌ Erreur : ' + res.data).css('color', 'red');
                }
            },
            error: function() {
                $('#lingua-sitemap-status').text('❌ Erreur serveur').css('color', 'red');
            },
            complete: function() {
                btn.prop('disabled', false).text('🗺️ Générer le sitemap maintenant');
            }
        });
    });
});
</script>
