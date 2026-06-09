<?php
/**
 * Vue pour la page Paramètres
 * Version : Onglet Shortcode Intégré et Fonctionnel
 */

if ( ! defined( 'WPINC' ) ) die;

// Récupération des valeurs actuelles
 $settings = get_option( 'lingua_commerce_ai_settings', array() );

 $default_lang     = isset( $settings['default_language'] ) ? $settings['default_language'] : 'fr_FR';
 $url_mode         = isset( $settings['url_mode'] ) ? $settings['url_mode'] : 'param';
 $cache_duration   = isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 3600;
 $fallback_mode    = isset( $settings['fallback_mode'] ) ? $settings['fallback_mode'] : 'original';
 $batch_size       = isset( $settings['batch_size'] ) ? $settings['batch_size'] : 5;
 $active_post_types = isset( $settings['active_post_types'] ) ? $settings['active_post_types'] : array('post', 'page', 'product');
 $active_taxonomies = isset( $settings['active_taxonomies'] ) ? $settings['active_taxonomies'] : array('category', 'product_cat');
 $custom_fields    = isset( $settings['custom_fields'] ) ? $settings['custom_fields'] : '';
 $excluded_ids     = isset( $settings['excluded_ids'] ) ? $settings['excluded_ids'] : '';
 $auto_publish     = isset( $settings['auto_publish_queue'] ) ? $settings['auto_publish_queue'] : 0;
 $cron_freq        = isset( $settings['cron_frequency'] ) ? $settings['cron_frequency'] : 'hourly';
 $browser_redirect = isset( $settings['browser_redirect'] ) ? $settings['browser_redirect'] : 0;
 $admin_translation= isset( $settings['admin_translation'] ) ? $settings['admin_translation'] : 0;
 $media_translation= isset( $settings['media_translation'] ) ? $settings['media_translation'] : 1;

 $post_types = get_post_types( array( 'public' => true ), 'objects' );
 $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
}
 $installed_languages = LinguaCommerce_Language_Service::get_active_languages();

// SVG Previews
 $svg_previews = array(
    'flags_only' => '<svg width="100%" height="50" viewBox="0 0 120 50"><rect x="10" y="15" width="25" height="18" fill="#002395" rx="2"/><rect x="45" y="15" width="25" height="18" fill="#ED2939" rx="2"/><rect x="80" y="15" width="25" height="18" fill="#009246" rx="2"/><text x="60" y="48" font-size="6" fill="#666" text-anchor="middle">Flags Only</text></svg>',
    'dropdown' => '<svg width="100%" height="50" viewBox="0 0 120 50"><rect x="10" y="12" width="100" height="26" fill="#fff" stroke="#ccc" rx="4"/><rect x="15" y="18" width="20" height="14" fill="#002395" rx="2"/><line x1="40" y1="25" x2="90" y2="25" stroke="#eee" stroke-width="8" stroke-linecap="round"/><path d="M95 25 l5 5 l5 -5" stroke="#666" fill="none" stroke-width="2"/><text x="60" y="48" font-size="6" fill="#666" text-anchor="middle">Dropdown</text></svg>',
    'floating_bubble' => '<svg width="100%" height="50" viewBox="0 0 120 50"><rect x="105" y="15" width="12" height="25" fill="#2271b1" rx="4"/><circle cx="111" cy="28" r="3" fill="#fff"/><rect x="55" y="10" width="45" height="12" fill="#fff" stroke="#ccc" rx="2"/><rect x="55" y="24" width="45" height="12" fill="#fff" stroke="#ccc" rx="2"/><rect x="55" y="38" width="45" height="12" fill="#fff" stroke="#ccc" rx="2"/><text x="60" y="48" font-size="6" fill="#666" text-anchor="middle">Floating Bubble</text></svg>',
    'text_pills' => '<svg width="100%" height="50" viewBox="0 0 120 50"><rect x="15" y="18" width="25" height="15" fill="#eee" rx="8"/><rect x="47" y="18" width="25" height="15" fill="#2271b1" rx="8"/><rect x="79" y="18" width="25" height="15" fill="#eee" rx="8"/><text x="27" y="28" font-size="6" fill="#555">FR</text><text x="59" y="28" font-size="6" fill="#fff">EN</text><text x="91" y="28" font-size="6" fill="#555">ES</text><text x="60" y="48" font-size="6" fill="#666" text-anchor="middle">Text Pills</text></svg>',
    'nav_menu' => '<svg width="100%" height="50" viewBox="0 0 120 50"><rect x="5" y="18" width="30" height="12" fill="#f3f3f3"/><rect x="40" y="18" width="30" height="12" fill="#f3f3f3"/><rect x="75" y="18" width="30" height="12" fill="#f3f3f3"/><rect x="10" y="20" width="15" height="8" fill="#ddd"/><rect x="45" y="20" width="15" height="8" fill="#ddd"/><rect x="80" y="20" width="15" height="8" fill="#ddd"/><text x="60" y="48" font-size="6" fill="#666" text-anchor="middle">Nav Menu</text></svg>',
    'modal' => '<svg width="100%" height="50" viewBox="0 0 120 50"><rect x="35" y="5" width="50" height="20" fill="#fff" stroke="#ccc" rx="3"/><rect x="5" y="28" width="110" height="20" fill="#fff" stroke="#ddd" rx="2" stroke-dasharray="2,2"/><rect x="45" y="15" width="20" height="12" fill="#002395" rx="2"/><text x="60" y="48" font-size="6" fill="#666" text-anchor="middle">Modal Popin</text></svg>'
);

?>

<div class="wrap lingua-settings-page">
    <h1>⚙️ Paramètres Avancés</h1>
    
    <!-- MENU DES ONGLETS -->
    <div class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="#tab-general" class="nav-tab nav-tab-active">🌐 Général</a>
        <a href="#tab-frontend" class="nav-tab">🎨 Affichage</a>
        <a href="#tab-shortcode" class="nav-tab">📝 Shortcode</a>
        <a href="#tab-performance" class="nav-tab">🚀 Performance</a>
        <a href="#tab-content" class="nav-tab">📂 Contenus</a>
        <a href="#tab-automation" class="nav-tab">🔄 Automatisation</a>
        <a href="#tab-maintenance" class="nav-tab">🛠️ Maintenance</a>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'lingua_commerce_ai_settings_group' ); ?>
        
        <!-- ONGLET 1 : GÉNÉRAL -->
        <div id="tab-general" class="lingua-tab-content active" style="background:#fff; padding:20px; border:1px solid #ccc; border-top:none;">
            <table class="form-table">
                <tr>
                    <th scope="row">Langue Source</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[default_language]">
                            <?php foreach ( $installed_languages as $lang ) : ?>
                                <option value="<?php echo esc_attr( $lang->code ); ?>" <?php selected( $default_lang, $lang->code ); ?>>
                                    <?php echo esc_html( $lang->native_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Détection Navigateur</th>
                    <td>
                        <label>
                            <input type="checkbox" name="lingua_commerce_ai_settings[browser_redirect]" value="1" <?php checked( $browser_redirect, 1 ); ?>>
                            Rediriger automatiquement vers la langue du navigateur
                        </label>
                    </td>
                </tr>
                 <tr>
                    <th scope="row">Traduction Admin</th>
                    <td>
                        <label>
                            <input type="checkbox" name="lingua_commerce_ai_settings[admin_translation]" value="1" <?php checked( $admin_translation, 1 ); ?>>
                            Activer la traduction de l'interface d'administration
                        </label>
                    </td>
                </tr>
                 <tr>
                    <th scope="row">Médias</th>
                    <td>
                        <label>
                            <input type="checkbox" name="lingua_commerce_ai_settings[media_translation]" value="1" <?php checked( $media_translation, 1 ); ?>>
                            Activer la traduction des images (Alt, Légende, Titre)
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Structure URLs</th>
                    <td>
                        <fieldset>
                            <label><input type="radio" name="lingua_commerce_ai_settings[url_mode]" value="param" <?php checked( $url_mode, 'param' ); ?>> <code>?lang=en</code></label><br>
                            <label><input type="radio" name="lingua_commerce_ai_settings[url_mode]" value="directory" <?php checked( $url_mode, 'directory' ); ?>> <code>/en/</code></label>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ONGLET 2 : AFFICHAGE -->
        <div id="tab-frontend" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
            <h2 style="margin-top:0;">Apparence Globale du Sélecteur</h2>
            <p class="description">Survolez ou cliquez sur un modèle pour le sélectionner.</p>

            <div class="lingua-template-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-top: 20px;">
                <?php
                $templates = array(
                    'flags_only'     => 'Drapeaux Compacts',
                    'dropdown'       => 'Liste Déroulante',
                    'floating_bubble'=> 'Menu Flottant Latéral',
                    'text_pills'     => 'Puces Textuelles',
                    'nav_menu'       => 'Menu Principal',
                    'modal'          => 'Sélecteur Popin'
                );
                
                $selected_template = isset( $settings['selector_template'] ) ? $settings['selector_template'] : 'dropdown';
                
                foreach ( $templates as $key => $label ) {
                    $is_selected = ($selected_template === $key);
                    $card_style = $is_selected ? 'border: 2px solid #2271b1; box-shadow: 0 0 10px rgba(34, 113, 177, 0.2);' : 'border: 1px solid #ddd;';
                    
                    echo '<div class="lingua-template-card" style="background:#fff; padding:10px; border-radius:8px; ' . $card_style . ' cursor:pointer; transition:all 0.2s;" onclick="document.getElementById(\'tpl-radio-' . $key . '\').checked=true; document.getElementById(\'tpl-radio-' . $key . '\').dispatchEvent(new Event(\'change\'));">';
                    echo '<div style="background:#f9f9f9; border-radius:4px; margin-bottom:10px; overflow:hidden;">';
                    echo $svg_previews[$key];
                    echo '</div>';
                    echo '<label style="cursor:pointer; font-weight:600; display:block;">';
                    echo '<input type="radio" id="tpl-radio-'.$key.'" name="lingua_commerce_ai_settings[selector_template]" value="' . esc_attr($key) . '" ' . checked( $selected_template, $key, false ) . ' style="margin-right:5px;">';
                    echo esc_html($label);
                    echo '</label>';
                    echo '</div>';
                }
                ?>
            </div>

            <table class="form-table" style="margin-top:30px; border-top:1px solid #eee; padding-top:20px;">
                <tr>
                    <th scope="row">Position</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[selector_position]">
                            <option value="header" <?php selected( isset($settings['selector_position']) ? $settings['selector_position'] : 'header', 'header' ); ?>>En-tête (Header)</option>
                            <option value="footer" <?php selected( isset($settings['selector_position']) ? $settings['selector_position'] : 'header', 'footer' ); ?>>Pied de page (Footer)</option>
                            <option value="manual" <?php selected( isset($settings['selector_position']) ? $settings['selector_position'] : 'header', 'manual' ); ?>>Manuel (Shortcode)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Alignement</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[selector_align]">
                            <option value="left" <?php selected( isset($settings['selector_align']) ? $settings['selector_align'] : 'left', 'left' ); ?>>Gauche</option>
                            <option value="center" <?php selected( isset($settings['selector_align']) ? $settings['selector_align'] : 'left', 'center' ); ?>>Centre</option>
                            <option value="right" <?php selected( isset($settings['selector_align']) ? $settings['selector_align'] : 'left', 'right' ); ?>>Droite</option>
                        </select>
                        <p class="description">Fonctionne pour l'en-tête et le pied de page.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Espacement (Marge)</th>
                    <td>
                        <input type="text" name="lingua_commerce_ai_settings[selector_margin]" value="<?php echo esc_attr( isset($settings['selector_margin']) ? $settings['selector_margin'] : '0px' ); ?>" placeholder="Ex: 20px">
                        <p class="description">Espace autour du sélecteur (ex: <code>10px</code> ou <code>10px 20px</code>).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Style des Drapeaux</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[flag_style]">
                            <option value="rectangular" <?php selected( isset($settings['flag_style']) ? $settings['flag_style'] : 'rectangular', 'rectangular' ); ?>>Rectangulaire (4/3)</option>
                            <option value="round" <?php selected( isset($settings['flag_style']) ? $settings['flag_style'] : 'rectangular', 'round' ); ?>>Rond</option>
                            <option value="none" <?php selected( isset($settings['flag_style']) ? $settings['flag_style'] : 'rectangular', 'none' ); ?>>Sans drapeaux</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ONGLET 3 : SHORTCODE -->
        <div id="tab-shortcode" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
            <h2 style="margin-top:0;">Configuration du Shortcode</h2>
            <p class="description">Ces réglages s'appliquent uniquement lorsque vous utilisez <code>[lingua_selector]</code> dans une page.</p>
            <table class="form-table">
                <tr>
                    <th scope="row">Modèle du Shortcode</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[sc_template]">
                            <option value="default" <?php selected( isset($settings['sc_template']) ? $settings['sc_template'] : 'default', 'default' ); ?>>Utiliser le réglage global</option>
                            <option value="dropdown" <?php selected( isset($settings['sc_template']) ? $settings['sc_template'] : '', 'dropdown' ); ?>>Liste Déroulante</option>
                            <option value="flags_only" <?php selected( isset($settings['sc_template']) ? $settings['sc_template'] : '', 'flags_only' ); ?>>Drapeaux Compacts</option>
                            <option value="text_pills" <?php selected( isset($settings['sc_template']) ? $settings['sc_template'] : '', 'text_pills' ); ?>>Puces Textuelles</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Afficher le nom</th>
                    <td>
                        <label><input type="checkbox" name="lingua_commerce_ai_settings[sc_show_name]" value="1" <?php checked( isset($settings['sc_show_name']) ? $settings['sc_show_name'] : 0, 1 ); ?>> Afficher le nom de la langue</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Cacher la langue active</th>
                    <td>
                        <label><input type="checkbox" name="lingua_commerce_ai_settings[sc_hide_current]" value="1" <?php checked( isset($settings['sc_hide_current']) ? $settings['sc_hide_current'] : 0, 1 ); ?>> Ne pas afficher la langue actuelle dans la liste</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Alignement</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[sc_align]">
                            <option value="left" <?php selected( isset($settings['sc_align']) ? $settings['sc_align'] : 'left', 'left' ); ?>>Gauche</option>
                            <option value="center" <?php selected( isset($settings['sc_align']) ? $settings['sc_align'] : 'left', 'center' ); ?>>Centre</option>
                            <option value="right" <?php selected( isset($settings['sc_align']) ? $settings['sc_align'] : 'left', 'right' ); ?>>Droite</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ONGLET 4 : PERFORMANCE -->
        <div id="tab-performance" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
            <table class="form-table">
                <tr>
                    <th scope="row">Durée du Cache</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[cache_duration]">
                            <option value="3600" <?php selected( $cache_duration, 3600 ); ?>>1 Heure</option>
                            <option value="86400" <?php selected( $cache_duration, 86400 ); ?>>1 Jour</option>
                            <option value="604800" <?php selected( $cache_duration, 604800 ); ?>>1 Semaine</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Exclusions</th>
                    <td>
                        <textarea name="lingua_commerce_ai_settings[excluded_ids]" rows="3" class="large-text" placeholder="Ex: 12, 45, 99"><?php echo esc_textarea( $excluded_ids ); ?></textarea>
                        <p class="description">IDs des pages/articles à ne JAMAIS traduire.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Actions</th>
                    <td>
                        <button type="button" id="lingua-purge-cache-btn" class="button">🗑️ Vider le cache</button>
                        <span id="lingua-cache-status" style="margin-left:10px; color:green;"></span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ONGLET 5 : CONTENUS -->
        <div id="tab-content" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
            <h3>Types de contenus</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Post Types</th>
                    <td>
                        <?php foreach ( $post_types as $slug => $pt ) : ?>
                            <label style="display:block; margin-bottom:5px;">
                                <input type="checkbox" name="lingua_commerce_ai_settings[active_post_types][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $active_post_types ), true ); ?>>
                                <?php echo esc_html( $pt->labels->name ); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Taxonomies</th>
                    <td>
                        <?php foreach ( $taxonomies as $slug => $tax ) : ?>
                            <label style="display:block; margin-bottom:5px;">
                                <input type="checkbox" name="lingua_commerce_ai_settings[active_taxonomies][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $active_taxonomies ), true ); ?>>
                                <?php echo esc_html( $tax->labels->name ); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Champs Personnalisés</th>
                    <td>
                        <textarea name="lingua_commerce_ai_settings[custom_fields]" rows="4" class="large-text" placeholder="_mon_champ_1, _mon_champ_2"><?php echo esc_textarea( $custom_fields ); ?></textarea>
                        <p class="description">Clés Meta à rendre traduisibles.</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ONGLET 6 : AUTOMATISATION -->
        <div id="tab-automation" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
             <table class="form-table">
                <tr>
                    <th scope="row">Taille des lots (Batch)</th>
                    <td><input type="number" name="lingua_commerce_ai_settings[batch_size]" value="<?php echo esc_attr( $batch_size ); ?>" min="1" max="50"></td>
                </tr>
                <tr>
                    <th scope="row">Auto-Queue</th>
                    <td>
                        <label><input type="checkbox" name="lingua_commerce_ai_settings[auto_publish_queue]" value="1" <?php checked( $auto_publish, 1 ); ?>> Envoyer en traduction automatique dès la publication</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Fréquence CRON</th>
                    <td>
                        <select name="lingua_commerce_ai_settings[cron_frequency]">
                            <option value="lingua_5min" <?php selected( $cron_freq, 'lingua_5min' ); ?>>Toutes les 5 minutes</option>
                            <option value="hourly" <?php selected( $cron_freq, 'hourly' ); ?>>Toutes les heures</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ONGLET 7 : MAINTENANCE -->
        <div id="tab-maintenance" class="lingua-tab-content" style="display:none; background:#fff; padding:20px; border:1px solid #ccc;">
            <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <strong>📊 Statistiques :</strong> Utilisez la page "Outils" pour les exports avancés.
            </div>
            <table class="form-table">
                <tr>
                    <th scope="row">Sauvegarde</th>
                    <td><a href="<?php echo admin_url( 'admin.php?page=lingua-commerce-ai-tools' ); ?>" class="button">📁 Exporter les Réglages</a></td>
                </tr>
            </table>
        </div>

        <!-- BOUTON DE SAUVEGARDE GLOBAL -->
        <p class="submit" style="margin-top: 20px;">
            <?php submit_button( 'Sauvegarder tous les réglages', 'primary', 'submit', false ); ?>
        </p>
    </form>
</div>

<style>
    .lingua-tab-content { border-radius: 0 0 4px 4px; }
    .lingua-template-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: #999 !important; }
    .lingua-template-card input:checked + span { font-weight: bold; color: #2271b1; }
</style>
<script>
jQuery(document).ready(function($) {
    // Gestion des onglets
    $('.nav-tab').on('click', function(e) { e.preventDefault(); $('.nav-tab').removeClass('nav-tab-active'); $(this).addClass('nav-tab-active'); $('.lingua-tab-content').hide(); $($(this).attr('href')).show(); });
    
    // Gestion visuelle de la sélection du template
    $('.lingua-template-card input[type="radio"]').on('change', function() {
        $('.lingua-template-card').css('border', '1px solid #ddd').css('box-shadow', 'none');
        $(this).closest('.lingua-template-card').css('border', '2px solid #2271b1').css('box-shadow', '0 0 10px rgba(34, 113, 177, 0.2)');
    });

    // Purge Cache
    $('#lingua-purge-cache-btn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).text('Nettoyage...');
        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'lingua_purge_cache', nonce: '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>' },
            success: function(res) { if(res.success) { $('#lingua-cache-status').text('Cache vidé !'); } },
            complete: function() { btn.prop('disabled', false).text('🗑️ Vider le cache'); }
        });
    });
});
</script>
