<?php
/**
 * Vue pour la page Menus, Widgets & Builders
 * Intégration Elementor, support RTL, CSS personnalisé
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) { die; }

// Récupération des réglages
$menus_settings = get_option( 'lingua_commerce_ai_menus_settings', array() );
$settings       = get_option( 'lingua_commerce_ai_settings', array() );

$menu_sync_enabled     = isset( $menus_settings['menu_sync_enabled'] ) ? $menus_settings['menu_sync_enabled'] : 1;
$widget_translate      = isset( $menus_settings['widget_translate'] ) ? $menus_settings['widget_translate'] : 1;
$auto_menu_create      = isset( $menus_settings['auto_menu_create'] ) ? $menus_settings['auto_menu_create'] : 0;
$rtl_auto_detect       = isset( $menus_settings['rtl_auto_detect'] ) ? $menus_settings['rtl_auto_detect'] : 1;
$rtl_custom_css        = isset( $menus_settings['rtl_custom_css'] ) ? $menus_settings['rtl_custom_css'] : '';
$builder_integration   = isset( $menus_settings['builder_integration'] ) ? $menus_settings['builder_integration'] : 'full';
$elementor_translate   = isset( $menus_settings['elementor_translate'] ) ? $menus_settings['elementor_translate'] : 1;
$elementor_templates   = isset( $menus_settings['elementor_templates'] ) ? $menus_settings['elementor_templates'] : 0;
$custom_css            = isset( $menus_settings['custom_css'] ) ? $menus_settings['custom_css'] : '';
$nav_menu_label_sync   = isset( $menus_settings['nav_menu_label_sync'] ) ? $menus_settings['nav_menu_label_sync'] : 1;

// Détection des builders
$detected_builders = array();

if ( is_plugin_active( 'elementor/elementor.php' ) ) {
    $detected_builders[] = array( 'name' => 'Elementor', 'slug' => 'elementor', 'status' => 'active', 'icon' => '🏗️', 'desc' => 'Page Builder complet' );
} elseif ( file_exists( WP_PLUGIN_DIR . '/elementor/elementor.php' ) ) {
    $detected_builders[] = array( 'name' => 'Elementor', 'slug' => 'elementor', 'status' => 'inactive', 'icon' => '🏗️', 'desc' => 'Page Builder complet' );
}

if ( is_plugin_active( 'divi-builder/divi-builder.php' ) || is_plugin_active( 'divi-machine/divi-machine.php' ) ) {
    $detected_builders[] = array( 'name' => 'Divi Builder', 'slug' => 'divi', 'status' => 'active', 'icon' => '🎨', 'desc' => 'Builder Elegant Themes' );
}

if ( is_plugin_active( 'beaver-builder-lite-version/fl-builder.php' ) || is_plugin_active( 'bb-plugin/fl-builder.php' ) ) {
    $detected_builders[] = array( 'name' => 'Beaver Builder', 'slug' => 'beaver', 'status' => 'active', 'icon' => '🦫', 'desc' => 'Builder visuel' );
}

if ( is_plugin_active( 'brizy/brizy.php' ) ) {
    $detected_builders[] = array( 'name' => 'Brizy', 'slug' => 'brizy', 'status' => 'active', 'icon' => '✨', 'desc' => 'Builder cloud' );
}

if ( is_plugin_active( 'siteorigin-panels/siteorigin-panels.php' ) ) {
    $detected_builders[] = array( 'name' => 'SiteOrigin', 'slug' => 'siteorigin', 'status' => 'active', 'icon' => '🧩', 'desc' => 'Page Builder gratuit' );
}

if ( is_plugin_active( 'wpbakery-visual-composer/js_composer.php' ) ) {
    $detected_builders[] = array( 'name' => 'WPBakery', 'slug' => 'wpbakery', 'status' => 'active', 'icon' => '🔧', 'desc' => 'Builder classique' );
}

if ( is_plugin_active( 'gutenberg/gutenberg.php' ) ) {
    $detected_builders[] = array( 'name' => 'Gutenberg', 'slug' => 'gutenberg', 'status' => 'active', 'icon' => '📦', 'desc' => 'Éditeur natif WordPress' );
}

// Récupération des menus existants
$menus = wp_get_nav_menus();

// Langues RTL
$rtl_languages = array( 'ar', 'he', 'fa', 'ur', 'yi', 'arc', 'dv', 'ha', 'ks', 'ku', 'ps', 'sd' );

// Vérifier les langues RTL actives
$active_rtl_langs = array();
if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
    $service_file = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
    if ( file_exists( $service_file ) ) {
        require_once $service_file;
    }
}
if ( class_exists( 'LinguaCommerce_Language_Service' ) ) {
    $active_langs = LinguaCommerce_Language_Service::get_active_languages();
    foreach ( $active_langs as $lang ) {
        $short_code = substr( $lang->code, 0, 2 );
        if ( in_array( $short_code, $rtl_languages ) ) {
            $active_rtl_langs[] = $lang;
        }
    }
}

// Widgets traduisibles
$widget_areas = $GLOBALS['wp_registered_sidebars'];
$translatable_widgets = array(
    'text'          => 'Widget Texte',
    'custom_html'   => 'Widget HTML Personnalisé',
    'nav_menu'      => 'Widget Menu de Navigation',
    'categories'    => 'Widget Catégories',
    'recent_posts'  => 'Widget Articles Récents',
    'meta'          => 'Widget Meta',
    'search'        => 'Widget Recherche',
    'archives'      => 'Widget Archives',
);

?>

<style>
    .lingua-menus-page { max-width: 1200px; }
    .lingua-menus-page h1 { margin-bottom: 5px; }
    .lingua-menus-page .menus-subtitle { color: #666; margin-bottom: 25px; font-size: 14px; }

    .lingua-menus-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .lingua-menus-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
    }

    .lingua-menus-card h2 {
        margin: 0 0 15px 0;
        font-size: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .lingua-menus-card h3 {
        margin: 15px 0 10px 0;
        font-size: 14px;
    }

    /* BUILDER DETECTION CARDS */
    .lingua-builder-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px;
        margin-bottom: 10px;
        border: 1px solid #eee;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .lingua-builder-item:hover { border-color: #2271b1; background: #f9fafb; }

    .lingua-builder-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .lingua-builder-icon { font-size: 28px; }

    .lingua-builder-name { font-weight: 600; font-size: 14px; }
    .lingua-builder-desc { font-size: 12px; color: #666; margin-top: 2px; }

    .lingua-builder-badge {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .lingua-builder-badge.active { background: #d1f7d1; color: #00a32a; }
    .lingua-builder-badge.inactive { background: #f0f0f0; color: #666; }

    /* MENU SYNC TABLE */
    .lingua-menu-sync-table {
        width: 100%;
        border-collapse: collapse;
    }

    .lingua-menu-sync-table th {
        text-align: left;
        padding: 10px;
        background: #f6f7f7;
        font-size: 12px;
        text-transform: uppercase;
        border-bottom: 2px solid #ddd;
    }

    .lingua-menu-sync-table td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }

    .lingua-menu-sync-table tr:hover td { background: #f9f9f9; }

    /* RTL SECTION */
    .lingua-rtl-lang-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        margin-bottom: 8px;
        background: #fff8e5;
        border: 1px solid #ffe082;
        border-radius: 6px;
    }

    .lingua-rtl-lang-flag { font-size: 22px; }
    .lingua-rtl-lang-name { font-weight: 600; font-size: 13px; }
    .lingua-rtl-lang-code { font-size: 12px; color: #856404; }
    .lingua-rtl-lang-dir { font-size: 11px; padding: 2px 8px; border-radius: 10px; background: #fff3cd; color: #856404; }

    /* CSS EDITOR */
    .lingua-css-editor {
        width: 100%;
        min-height: 160px;
        font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
        font-size: 13px;
        line-height: 1.6;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #1e1e1e;
        color: #d4d4d4;
        tab-size: 4;
    }

    .lingua-css-editor:focus {
        border-color: #2271b1;
        box-shadow: 0 0 0 1px #2271b1;
        outline: none;
    }

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

    /* ELEMENTOR INTEGRATION CARD */
    .lingua-elementor-card {
        background: linear-gradient(135deg, #6c2eb9, #9b59b6);
        border: none;
        color: #fff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
    }

    .lingua-elementor-card h3 {
        margin: 0 0 8px 0;
        color: #fff;
        font-size: 16px;
    }

    .lingua-elementor-card p {
        font-size: 13px;
        opacity: 0.9;
        margin: 0 0 12px 0;
    }

    .lingua-elementor-card .elementor-features {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 12px;
    }

    .lingua-elementor-card .feature-item {
        background: rgba(255,255,255,0.15);
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
    }

    /* WIDGET GRID */
    .lingua-widget-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 10px;
    }

    .lingua-widget-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border: 1px solid #eee;
        border-radius: 6px;
        font-size: 13px;
    }

    .lingua-widget-item .widget-status {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #00a32a;
    }

    .lingua-widget-item .widget-status.inactive { background: #ddd; }

    @media (max-width: 782px) {
        .lingua-menus-grid { grid-template-columns: 1fr; }
        .lingua-elementor-card .elementor-features { grid-template-columns: 1fr; }
        .lingua-widget-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="wrap lingua-menus-page">
    <h1>📋 Menus, Widgets & Builders</h1>
    <p class="menus-subtitle">Gérez la traduction des menus de navigation, widgets et intégrez avec vos constructeurs de pages.</p>

    <!-- BUILDERS DETECTION + ELEMENTOR -->
    <div class="lingua-menus-grid">
        <div class="lingua-menus-card">
            <h2>🏗️ Constructeurs de pages détectés</h2>
            <?php if ( ! empty( $detected_builders ) ) : ?>
                <?php foreach ( $detected_builders as $builder ) : ?>
                    <div class="lingua-builder-item">
                        <div class="lingua-builder-info">
                            <span class="lingua-builder-icon"><?php echo esc_html( $builder['icon'] ); ?></span>
                            <div>
                                <div class="lingua-builder-name"><?php echo esc_html( $builder['name'] ); ?></div>
                                <div class="lingua-builder-desc"><?php echo esc_html( $builder['desc'] ); ?></div>
                            </div>
                        </div>
                        <span class="lingua-builder-badge <?php echo esc_attr( $builder['status'] ); ?>">
                            <?php echo $builder['status'] === 'active' ? '✅ Actif' : '⏸️ Inactif'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align:center; padding:30px; color:#999;">
                    <p style="font-size:32px; margin:0;">🔍</p>
                    <p>Aucun constructeur de pages tiers détecté.</p>
                    <p style="font-size:12px;">L'éditeur WordPress par défaut sera utilisé.</p>
                </div>
            <?php endif; ?>

            <div style="margin-top:15px; padding:12px; background:#f0f6fc; border-radius:5px; border-left:4px solid #2271b1; font-size:12px;">
                <strong>💡 Note :</strong> LinguaCommerce AI détecte automatiquement vos constructeurs de pages et adapte la traduction en conséquence. Les contenus construits avec ces outils seront traduits tout en préservant la structure visuelle.
            </div>
        </div>

        <!-- Elementor Integration -->
        <div class="lingua-menus-card">
            <h2>⚡ Intégration Elementor</h2>
            <?php if ( is_plugin_active( 'elementor/elementor.php' ) ) : ?>
                <div class="lingua-elementor-card">
                    <h3>🏗️ Elementor est actif !</h3>
                    <p>Profitez d'une intégration complète pour traduire vos pages construites avec Elementor.</p>
                    <div class="elementor-features">
                        <div class="feature-item">✅ Widgets texte traduits</div>
                        <div class="feature-item">✅ Titres & sous-titres</div>
                        <div class="feature-item">✅ Boutons & liens</div>
                        <div class="feature-item">✅ Images (alt & légende)</div>
                        <div class="feature-item">✅ Templates Elementor</div>
                        <div class="feature-item">✅ Sections & colonnes</div>
                    </div>
                </div>

                <div class="lingua-toggle-row">
                    <div>
                        <label>Traduire les widgets Elementor</label>
                        <div class="description">Traduit automatiquement le contenu des widgets Elementor</div>
                    </div>
                    <div class="lingua-toggle-switch">
                        <input type="checkbox" name="lingua_commerce_ai_menus_settings[elementor_translate]" value="1" <?php checked( $elementor_translate, 1 ); ?>>
                        <span class="lingua-toggle-slider"></span>
                    </div>
                </div>
                <div class="lingua-toggle-row">
                    <div>
                        <label>Traduire les templates</label>
                        <div class="description">Inclut les templates Elementor dans les traductions</div>
                    </div>
                    <div class="lingua-toggle-switch">
                        <input type="checkbox" name="lingua_commerce_ai_menus_settings[elementor_templates]" value="1" <?php checked( $elementor_templates, 1 ); ?>>
                        <span class="lingua-toggle-slider"></span>
                    </div>
                </div>
            <?php else : ?>
                <div style="text-align:center; padding:30px; background:#f9f9f9; border-radius:8px;">
                    <p style="font-size:32px; margin:0 0 10px 0;">🏗️</p>
                    <p style="font-weight:600;">Elementor n'est pas installé</p>
                    <p style="font-size:12px; color:#666;">Installez Elementor pour bénéficier de l'intégration avancée avec LinguaCommerce AI.</p>
                    <a href="<?php echo admin_url( 'plugin-install.php?s=elementor&tab=search&type=term' ); ?>" class="button" style="margin-top:10px;">
                        Installer Elementor
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MENUS SYNC + RTL -->
    <div class="lingua-menus-grid">
        <!-- Menus de navigation -->
        <div class="lingua-menus-card">
            <h2>🧭 Synchronisation des menus</h2>

            <div class="lingua-toggle-row">
                <div>
                    <label>Synchroniser les menus</label>
                    <div class="description">Crée et maintient des menus traduits pour chaque langue</div>
                </div>
                <div class="lingua-toggle-switch">
                    <input type="checkbox" name="lingua_commerce_ai_menus_settings[menu_sync_enabled]" value="1" <?php checked( $menu_sync_enabled, 1 ); ?>>
                    <span class="lingua-toggle-slider"></span>
                </div>
            </div>
            <div class="lingua-toggle-row">
                <div>
                    <label>Synchroniser les labels</label>
                    <div class="description">Traduit automatiquement les libellés des éléments de menu</div>
                </div>
                <div class="lingua-toggle-switch">
                    <input type="checkbox" name="lingua_commerce_ai_menus_settings[nav_menu_label_sync]" value="1" <?php checked( $nav_menu_label_sync, 1 ); ?>>
                    <span class="lingua-toggle-slider"></span>
                </div>
            </div>
            <div class="lingua-toggle-row">
                <div>
                    <label>Création automatique</label>
                    <div class="description">Crée automatiquement un nouveau menu pour chaque langue activée</div>
                </div>
                <div class="lingua-toggle-switch">
                    <input type="checkbox" name="lingua_commerce_ai_menus_settings[auto_menu_create]" value="1" <?php checked( $auto_menu_create, 1 ); ?>>
                    <span class="lingua-toggle-slider"></span>
                </div>
            </div>

            <?php if ( ! empty( $menus ) ) : ?>
                <h3>📋 Menus existants</h3>
                <table class="lingua-menu-sync-table">
                    <thead>
                        <tr>
                            <th>Menu</th>
                            <th>Emplacements</th>
                            <th>Éléments</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $menus as $menu ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $menu->name ); ?></strong></td>
                                <td>
                                    <?php
                                    $locations = get_nav_menu_locations();
                                    $menu_locations = array();
                                    foreach ( $locations as $loc => $menu_id ) {
                                        if ( $menu_id == $menu->term_id ) {
                                            $menu_locations[] = $loc;
                                        }
                                    }
                                    echo ! empty( $menu_locations ) ? esc_html( implode( ', ', $menu_locations ) ) : '<em style="color:#999;">Non assigné</em>';
                                    ?>
                                </td>
                                <td><?php echo esc_html( $menu->count ); ?></td>
                                <td>
                                    <button type="button" class="button button-small lingua-sync-menu-btn" data-menu-id="<?php echo esc_attr( $menu->term_id ); ?>">
                                        🔄 Synchroniser
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p style="color:#999; font-style:italic; margin-top:15px;">Aucun menu de navigation trouvé. Créez-en un depuis Apparence > Menus.</p>
            <?php endif; ?>
        </div>

        <!-- RTL Support -->
        <div class="lingua-menus-card">
            <h2>➡️⬅️ Support RTL (Right-to-Left)</h2>

            <div class="lingua-toggle-row">
                <div>
                    <label>Détection automatique RTL</label>
                    <div class="description">Active automatiquement le mode RTL pour les langues concernées</div>
                </div>
                <div class="lingua-toggle-switch">
                    <input type="checkbox" name="lingua_commerce_ai_menus_settings[rtl_auto_detect]" value="1" <?php checked( $rtl_auto_detect, 1 ); ?>>
                    <span class="lingua-toggle-slider"></span>
                </div>
            </div>

            <?php if ( ! empty( $active_rtl_langs ) ) : ?>
                <h3>🔤 Langues RTL actives</h3>
                <?php
                $rtl_flags = array( 'ar' => '🇸🇦', 'he' => '🇮🇱', 'fa' => '🇮🇷', 'ur' => '🇵🇰' );
                foreach ( $active_rtl_langs as $rtl_lang ) :
                    $short = substr( $rtl_lang->code, 0, 2 );
                    $flag = isset( $rtl_flags[ $short ] ) ? $rtl_flags[ $short ] : '🏳️';
                ?>
                    <div class="lingua-rtl-lang-item">
                        <span class="lingua-rtl-lang-flag"><?php echo esc_html( $flag ); ?></span>
                        <span class="lingua-rtl-lang-name"><?php echo esc_html( $rtl_lang->native_name ); ?></span>
                        <span class="lingua-rtl-lang-code"><?php echo esc_html( $rtl_lang->code ); ?></span>
                        <span class="lingua-rtl-lang-dir">RTL →</span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="padding:20px; background:#f9f9f9; border-radius:6px; text-align:center; color:#666; font-size:13px;">
                    ℹ️ Aucune langue RTL n'est actuellement active.
                </div>
            <?php endif; ?>

            <h3>🎨 CSS RTL personnalisé</h3>
            <p class="description">Ce CSS sera injecté uniquement lorsque la langue active est RTL.</p>
            <textarea
                class="lingua-css-editor"
                name="lingua_commerce_ai_menus_settings[rtl_custom_css]"
                rows="8"
                placeholder="/* Exemple de CSS RTL */
body.rtl .main-nav {
    flex-direction: row-reverse;
}
body.rtl .sidebar {
    float: right;
}"
            ><?php echo esc_textarea( $rtl_custom_css ); ?></textarea>
        </div>
    </div>

    <!-- WIDGETS + CUSTOM CSS -->
    <div class="lingua-menus-grid">
        <!-- Widgets -->
        <div class="lingua-menus-card">
            <h2>🧩 Widgets traduisibles</h2>

            <div class="lingua-toggle-row">
                <div>
                    <label>Traduire les widgets</label>
                    <div class="description">Active la traduction automatique du contenu des widgets</div>
                </div>
                <div class="lingua-toggle-switch">
                    <input type="checkbox" name="lingua_commerce_ai_menus_settings[widget_translate]" value="1" <?php checked( $widget_translate, 1 ); ?>>
                    <span class="lingua-toggle-slider"></span>
                </div>
            </div>

            <h3>📋 Widgets pris en charge</h3>
            <div class="lingua-widget-grid">
                <?php foreach ( $translatable_widgets as $slug => $name ) : ?>
                    <div class="lingua-widget-item">
                        <span class="widget-status <?php echo $widget_translate ? '' : 'inactive'; ?>"></span>
                        <span><?php echo esc_html( $name ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ( ! empty( $widget_areas ) ) : ?>
                <h3>📊 Zones de widgets</h3>
                <div class="lingua-widget-grid">
                    <?php foreach ( $widget_areas as $sidebar ) : ?>
                        <div class="lingua-widget-item">
                            <span class="widget-status"></span>
                            <span><?php echo esc_html( $sidebar['name'] ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Custom CSS -->
        <div class="lingua-menus-card">
            <h2>🎨 CSS personnalisé multilingue</h2>
            <p class="description">Ce CSS sera injecté sur toutes les pages traduites. Utilisez les classes spéciales pour cibler des langues spécifiques.</p>

            <div style="background:#f6f7f7; border-radius:6px; padding:12px; margin-bottom:12px; font-size:12px;">
                <strong>💡 Classes CSS disponibles :</strong>
                <ul style="margin:5px 0 0 15px;">
                    <li><code>body.lang-en</code> — Cible la langue anglaise</li>
                    <li><code>body.lang-fr</code> — Cible la langue française</li>
                    <li><code>body.rtl</code> — Cible les langues RTL</li>
                    <li><code>.lingua-selector</code> — Cible le sélecteur de langue</li>
                </ul>
            </div>

            <textarea
                class="lingua-css-editor"
                name="lingua_commerce_ai_menus_settings[custom_css]"
                rows="12"
                placeholder="/* Exemple : Style du sélecteur par langue */
body.lang-en .site-title {
    font-family: 'Georgia', serif;
}

body.lang-fr .site-title {
    font-family: 'Helvetica', sans-serif;
}

/* Sélecteur de langue */
.lingua-selector {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px 10px;
}"
            ><?php echo esc_textarea( $custom_css ); ?></textarea>

            <div style="margin-top:15px;">
                <button type="button" id="lingua-preview-css-btn" class="button">👁️ Prévisualiser</button>
                <button type="button" id="lingua-save-css-btn" class="button button-primary" style="margin-left:10px;">💾 Sauvegarder le CSS</button>
                <span id="lingua-css-status" style="margin-left:10px;"></span>
            </div>
        </div>
    </div>

    <!-- SAVE ALL -->
    <div class="lingua-menus-card">
        <form method="post" action="options.php">
            <?php settings_fields( 'lingua_commerce_ai_menus_settings_group' ); ?>
            <p class="submit">
                <?php submit_button( '💾 Sauvegarder tous les réglages Menus & Widgets', 'primary', 'submit', false ); ?>
            </p>
        </form>
    </div>

</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Sync menu button
    $(document).on('click', '.lingua-sync-menu-btn', function() {
        var btn = $(this);
        var menuId = btn.data('menu-id');
        btn.prop('disabled', true).text('⏳ En cours...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_sync_menu',
                menu_id: menuId,
                nonce: '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>'
            },
            success: function(res) {
                if (res.success) {
                    btn.text('✅ Synchronisé !');
                    setTimeout(function() { btn.text('🔄 Synchroniser').prop('disabled', false); }, 2000);
                } else {
                    btn.text('❌ Erreur');
                    setTimeout(function() { btn.text('🔄 Synchroniser').prop('disabled', false); }, 2000);
                }
            },
            error: function() {
                btn.text('❌ Erreur serveur');
                setTimeout(function() { btn.text('🔄 Synchroniser').prop('disabled', false); }, 2000);
            }
        });
    });

    // Save CSS
    $('#lingua-save-css-btn').on('click', function() {
        var btn = $(this);
        var css = $('textarea[name="lingua_commerce_ai_menus_settings[custom_css]"]').val();
        btn.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_save_custom_css',
                css: css,
                nonce: '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>'
            },
            success: function(res) {
                if (res.success) {
                    $('#lingua-css-status').text('✅ CSS sauvegardé !').css('color', 'green');
                } else {
                    $('#lingua-css-status').text('❌ Erreur').css('color', 'red');
                }
            },
            error: function() {
                $('#lingua-css-status').text('❌ Erreur serveur').css('color', 'red');
            },
            complete: function() {
                btn.prop('disabled', false);
            }
        });
    });

    // Preview CSS
    $('#lingua-preview-css-btn').on('click', function() {
        var css = $('textarea[name="lingua_commerce_ai_menus_settings[custom_css]"]').val();
        // Remove existing preview style
        $('#lingua-css-preview').remove();
        if (css.trim()) {
            $('head').append('<style id="lingua-css-preview">' + css + '</style>');
            alert('CSS appliqué en prévisualisation. Rechargez la page pour annuler.');
        }
    });
});
</script>
