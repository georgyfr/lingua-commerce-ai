<?php
/**
 * Vue pour la page Menus, Widgets & Builders
 * Version : Complète avec Traductions, CSS, RTL, Dates
 */

if ( ! defined( 'WPINC' ) ) { die; }

 $settings = get_option( 'lingua_commerce_ai_settings', array() );

if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
}
 $active_languages = LinguaCommerce_Language_Service::get_active_languages();
 $default_lang = LinguaCommerce_Language_Service::get_default_language();

// Détection Elementor Pro
 $has_elementor_pro = defined( 'ELEMENTOR_PRO_VERSION' );
 $elementor_headers = array();
 $elementor_footers = array();

if ( $has_elementor_pro ) {
    $args = array(
        'post_type' => 'elementor_library',
        'posts_per_page' => -1,
        'meta_query' => array( 'relation' => 'OR', array( 'key' => '_elementor_template_type', 'value' => 'header' ), array( 'key' => '_elementor_template_type', 'value' => 'footer' ) )
    );
    $templates = get_posts( $args );
    foreach ( $templates as $t ) {
        $type = get_post_meta( $t->ID, '_elementor_template_type', true );
        if ( $type === 'header' ) $elementor_headers[] = $t;
        if ( $type === 'footer' ) $elementor_footers[] = $t;
    }
}

// Menus WP
 $menus = wp_get_nav_menus();

// Widgets
global $wp_registered_sidebars;
 $sidebars = $wp_registered_sidebars;
// Récupération des widgets texte actifs
 $text_widgets = array();
 $sidebars_widgets = wp_get_sidebars_widgets();
if ( is_array( $sidebars_widgets ) ) {
    foreach ( $sidebars_widgets as $sidebar_id => $widget_ids ) {
        if ( $sidebar_id == 'wp_inactive_widgets' || empty( $widget_ids ) ) continue;
        foreach ( $widget_ids as $widget_id ) {
            if ( strpos( $widget_id, 'text-' ) === 0 ) {
                $id_base = 'text';
                $widget_number = str_replace( 'text-', '', $widget_id );
                $option = get_option( 'widget_' . $id_base );
                if ( isset( $option[$widget_number] ) ) {
                    $text_widgets[] = array(
                        'id' => $widget_id,
                        'title' => $option[$widget_number]['title'] ?? 'Sans Titre',
                        'sidebar' => $sidebars[$sidebar_id]['name'] ?? 'Inconnu'
                    );
                }
            }
        }
    }
}

?>

<div class="wrap lingua-menus-page">
    <h1>🧩 Menus, Widgets & Builders</h1>
    
    <div class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="#tab-menus" class="nav-tab nav-tab-active">🧭 Menus WP</a>
        <a href="#tab-elementor" class="nav-tab">⚡ Elementor</a>
        <a href="#tab-widgets" class="nav-tab">🧱 Widgets & Blocks</a>
        <a href="#tab-options" class="nav-tab">⚙️ Options Avancées</a>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'lingua_commerce_ai_settings_group' ); ?>
        
        <!-- ONGLET 1 : MENUS -->
        <div id="tab-menus" class="lingua-tab-content active" style="background:#fff; padding:20px; border:1px solid #ccc;">
            
            <h2>1. Position du Sélecteur</h2>
            <table class="form-table">
                <tr>
                    <th>Menu Cible</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[menu_switcher_id]" id="menu_switcher_select">
                            <option value="0">-- Ne pas insérer --</option>
                            <?php foreach ( $menus as $menu ) : ?>
                                <option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( isset($settings['menu_switcher_id']) ? $settings['menu_switcher_id'] : 0, $menu->term_id ); ?>>
                                    <?php echo esc_html( $menu->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr><th>Style</th><td>
                    <select name="lingua_commerce_ai_settings[menu_switcher_style]">
                        <option value="flags_only" <?php selected( isset($settings['menu_switcher_style']) ? $settings['menu_switcher_style'] : '', 'flags_only' ); ?>>Drapeaux</option>
                        <option value="names_only" <?php selected( isset($settings['menu_switcher_style']) ? $settings['menu_switcher_style'] : '', 'names_only' ); ?>>Noms</option>
                        <option value="flags_names" <?php selected( isset($settings['menu_switcher_style']) ? $settings['menu_switcher_style'] : '', 'flags_names' ); ?>>Drapeaux + Noms</option>
                    </select>
                </td></tr>
                <tr><th>Liens Dynamiques</th><td>
                    <label><input type="checkbox" name="lingua_commerce_ai_settings[auto_adjust_menu_links]" value="1" <?php checked( isset($settings['auto_adjust_menu_links']) ? $settings['auto_adjust_menu_links'] : 0, 1 ); ?>> Ajouter automatiquement ?lang=xx aux liens personnalisés</label>
                </td></tr>
            </table>

            <hr style="margin:20px 0;">
            
            <h2>2. Traduction des Intitulés du Menu</h2>
            <p class="description">Traduisez les titres des éléments de votre menu principal.</p>
            <div id="menu-items-translator" style="max-height:400px; overflow-y:auto; border:1px solid #eee; padding:10px; background:#fafafa;">
                <!-- Chargement via JS pour ne pas surcharger le PHP au chargement initial -->
                <p><em>Sélectionnez un menu ci-dessus et sauvegardez pour voir les éléments à traduire.</em></p>
            </div>
        </div>

        <!-- ONGLET 2 : ELEMENTOR -->
        <div id="tab-elementor" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
            
            <h2>1. Classes CSS Dynamiques</h2>
            <p class="description">Ajoute une classe spécifique à la balise &lt;body&gt; selon la langue (ex: &lt;body class="lang-en"&gt;).</p>
            <table class="form-table">
                <tr>
                    <th>Activer</th>
                    <td>
                        <label><input type="checkbox" name="lingua_commerce_ai_settings[enable_body_class]" value="1" <?php checked( isset($settings['enable_body_class']) ? $settings['enable_body_class'] : 0, 1 ); ?>> Injecter les classes CSS dynamiques</label>
                        <p class="description">Utile pour masquer/afficher des éléments Elementor via CSS sans créer plusieurs headers.</p>
                    </td>
                </tr>
            </table>

            <hr style="margin:20px 0;">

            <h2>2. Assignation des Templates (Theme Builder)</h2>
            <?php if ( ! $has_elementor_pro ) : ?>
                <div class="notice notice-info inline"><p>Nécessite Elementor Pro pour les Templates Conditionnels.</p></div>
            <?php else : ?>
                <table class="form-table widefat striped" style="max-width:800px;">
                    <thead><tr><th>Langue</th><th>Header Assigné</th><th>Footer Assigné</th></tr></thead>
                    <tbody>
                        <?php foreach ( $active_languages as $lang ) : ?>
                        <tr>
                            <td style="font-weight:bold;"><?php echo esc_html( $lang->native_name ); ?></td>
                            <td>
                                <select name="lingua_commerce_ai_settings[elementor_header_<?php echo esc_attr($lang->code); ?>]">
                                    <option value="">-- Par défaut --</option>
                                    <?php foreach ( $elementor_headers as $h ) : ?>
                                        <option value="<?php echo esc_attr( $h->ID ); ?>" <?php selected( isset($settings['elementor_header_'.$lang->code]) ? $settings['elementor_header_'.$lang->code] : '', $h->ID ); ?>><?php echo esc_html( $h->post_title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="lingua_commerce_ai_settings[elementor_footer_<?php echo esc_attr($lang->code); ?>]">
                                    <option value="">-- Par défaut --</option>
                                    <?php foreach ( $elementor_footers as $f ) : ?>
                                        <option value="<?php echo esc_attr( $f->ID ); ?>" <?php selected( isset($settings['elementor_footer_'.$lang->code]) ? $settings['elementor_footer_'.$lang->code] : '', $f->ID ); ?>><?php echo esc_html( $f->post_title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- ONGLET 3 : WIDGETS -->
        <div id="tab-widgets" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
            
            <h2>1. Widgets WooCommerce</h2>
            <p class="description">Traduisez les titres des widgets standards WooCommerce.</p>
            <table class="form-table">
                <tr>
                    <th>Titres à traduire</th>
                    <td>
                        <textarea name="lingua_commerce_ai_settings[wc_widget_titles]" rows="5" class="large-text" placeholder="Cart, Filter, Products..."><?php echo esc_textarea( isset($settings['wc_widget_titles']) ? $settings['wc_widget_titles'] : '' ); ?></textarea>
                        <p class="description">Entrez les titres originaux séparés par des virgules. Le plugin tentera de les traduire automatiquement.</p>
                    </td>
                </tr>
            </table>

            <hr style="margin:20px 0;">

            <h2>2. Widgets Texte Détectés</h2>
            <p class="description">Liste des widgets texte actifs. Pour traduire leur contenu, créez une copie du widget pour chaque langue ou utilisez les shortcodes.</p>
            
            <table class="widefat striped">
                <thead><tr><th>Titre</th><th>Zone (Sidebar)</th><th>ID</th></tr></thead>
                <tbody>
                    <?php if(!empty($text_widgets)) : foreach($text_widgets as $w) : ?>
                    <tr>
                        <td><?php echo esc_html($w['title']); ?></td>
                        <td><?php echo esc_html($w['sidebar']); ?></td>
                        <td><code><?php echo esc_html($w['id']); ?></code></td>
                    </tr>
                    <?php endforeach; else : ?>
                    <tr><td colspan="3">Aucun widget texte détecté.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ONGLET 4 : OPTIONS AVANCEES -->
        <div id="tab-options" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
            
            <h2>1. Formats de Date</h2>
            <table class="form-table widefat striped" style="max-width:600px;">
                <thead><tr><th>Langue</th><th>Format Date</th></tr></thead>
                <tbody>
                    <?php foreach ( $active_languages as $lang ) : ?>
                    <tr>
                        <td><?php echo esc_html( $lang->native_name ); ?></td>
                        <td>
                            <input type="text" name="lingua_commerce_ai_settings[date_format_<?php echo esc_attr($lang->code); ?>]" value="<?php echo esc_attr( isset($settings['date_format_'.$lang->code]) ? $settings['date_format_'.$lang->code] : '' ); ?>" placeholder="ex: d/m/Y">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr style="margin:20px 0;">

            <h2>2. Support RTL (Droite à Gauche)</h2>
            <table class="form-table widefat striped" style="max-width:600px;">
                <thead><tr><th>Langue</th><th>Activer RTL</th></tr></thead>
                <tbody>
                    <?php foreach ( $active_languages as $lang ) : ?>
                    <tr>
                        <td><?php echo esc_html( $lang->native_name ); ?></td>
                        <td>
                            <label><input type="checkbox" name="lingua_commerce_ai_settings[is_rtl_<?php echo esc_attr($lang->code); ?>]" value="1" <?php checked( isset($settings['is_rtl_'.$lang->code]) ? $settings['is_rtl_'.$lang->code] : 0, 1 ); ?>> Mode RTL</label>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr style="margin:20px 0;">

            <h2>3. CSS Personnalisé par Langue</h2>
            <p class="description">Injectez du code CSS spécifique qui ne s'activera que pour cette langue.</p>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <?php foreach ( $active_languages as $lang ) : ?>
                <div style="background:#f9f9f9; padding:10px; border:1px solid #ddd;">
                    <strong><?php echo esc_html( $lang->native_name ); ?> CSS</strong>
                    <textarea name="lingua_commerce_ai_settings[custom_css_<?php echo esc_attr($lang->code); ?>]" rows="5" class="widefat code" style="font-family:monospace; margin-top:5px;"><?php echo esc_textarea( isset($settings['custom_css_'.$lang->code]) ? $settings['custom_css_'.$lang->code] : '' ); ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

        <p class="submit" style="margin-top: 20px;">
            <?php submit_button( 'Sauvegarder toutes les configurations', 'primary', 'submit', false ); ?>
        </p>
    </form>
</div>

<style>.lingua-tab-content { display:none; }</style>
<script>
jQuery(document).ready(function($) {
    // Gestion des onglets
    $('.nav-tab').on('click', function(e) { e.preventDefault(); $('.nav-tab').removeClass('nav-tab-active'); $(this).addClass('nav-tab-active'); $('.lingua-tab-content').hide(); $($(this).attr('href')).show(); });
});
</script>