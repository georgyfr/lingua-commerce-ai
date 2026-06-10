<?php
/**
 * Vue pour la page d'administration des Traductions
 * Design : Liste (Tableau) OU Mosaique (Grille de cartes pour les médias) OU Cascade
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// Récupération des données
 $active_languages = isset( $active_languages ) ? $active_languages : array();
 $default_lang = $default_lang ?? (object) array('code' => 'fr_FR', 'native_name' => 'Français');

// Fallback
if ( ! $default_lang ) {
    $default_lang = (object) array('code' => 'unknown', 'native_name' => 'Non défini');
}

?>

<div class="wrap lingua-translations-page" id="lingua-translations-app">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
        <!-- BARRE D'OUTILS SUPÉRIEURE -->
    <div class="lingua-toolbar" style="background: #fff; padding: 15px; margin-top: 20px; border: 1px solid #c3c4c7; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
        
        <!-- Conteneur Flex forcé pour l'alignement horizontal -->
        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            
            <!-- 1. Filtre Type de contenu -->
            <div>
                <label for="lingua-post-type-select" style="font-weight: 600; margin-right: 5px; display:none;">Type :</label>
                <select id="lingua-post-type-select" style="width: auto; min-width: 180px;">
                    
                    <?php 
                    // --- LOGIQUE DYNAMIQUE CORRIGÉE ---
                    $wp_types_keys = array('post', 'page', 'attachment');
                    $woo_types_keys = array('product');
                    
                    // GROUPE WP
                    $show_wp_group = false;
                    foreach($wp_types_keys as $key) { if(in_array($key, $active_post_types)) $show_wp_group = true; }
                    if ( $show_wp_group ) : 
                    ?>
                    <optgroup label="Contenu WordPress">
                        <?php if ( in_array('post', $active_post_types) ) : ?><option value="post">Articles</option><?php endif; ?>
                        <?php if ( in_array('page', $active_post_types) ) : ?><option value="page">Pages</option><?php endif; ?>
                        <?php if ( in_array('attachment', $active_post_types) ) : ?><option value="attachment">Médias</option><?php endif; ?>
                    </optgroup>
                    <?php endif; ?>

                    <?php 
                    // GROUPE WOO
                    $show_woo_pt_group = false;
                    foreach($woo_types_keys as $key) { if(in_array($key, $active_post_types)) $show_woo_pt_group = true; }
                    if ( $show_woo_pt_group ) : 
                    ?>
                    <optgroup label="WooCommerce">
                        <?php if ( in_array('product', $active_post_types) ) : ?><option value="product">Produits</option><?php endif; ?>
                    </optgroup>
                    <?php endif; ?>

                    <?php
                    // --- TAXONOMIES ---
                    $taxonomies_map = array(
                        'category'          => 'Catégories',
                        'post_tag'          => 'Étiquettes',
                        'product_cat'       => 'Catégories Produit',
                        'product_tag'       => 'Étiquettes Produit',
                        'product_brand'     => 'Marques',
                        'product_shipping_class' => 'Classe d’expédition'
                    );
                    $wp_tax_keys = array('category', 'post_tag');
                    $woo_tax_keys = array('product_cat', 'product_tag', 'product_brand', 'product_shipping_class');

                    // GROUPE TAX WP
                    $has_wp_tax = false;
                    foreach($wp_tax_keys as $key) { if(in_array($key, $active_taxonomies)) $has_wp_tax = true; }
                    if ( $has_wp_tax ) :
                    ?>
                    <optgroup label="Taxonomies WordPress">
                         <?php foreach($taxonomies_map as $slug => $label) : if ( in_array($slug, $wp_tax_keys) && in_array($slug, $active_taxonomies) ) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                        <?php endif; endforeach; ?>
                    </optgroup>
                    <?php endif; ?>

                    <?php
                    // GROUPE TAX WOO
                    $has_woo_tax = false;
                    foreach($woo_tax_keys as $key) { if(in_array($key, $active_taxonomies)) $has_woo_tax = true; }
                    if ( $has_woo_tax ) :
                    ?>
                    <optgroup label="Taxonomies WooCommerce">
                         <?php foreach($taxonomies_map as $slug => $label) : if ( in_array($slug, $woo_tax_keys) && in_array($slug, $active_taxonomies) ) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                        <?php endif; endforeach; ?>
                    </optgroup>
                    <?php endif; ?>

                    <?php if ( in_array('product_attributes', $active_post_types) ) : ?>
                    <optgroup label="Attributs">
                         <option value="product_attributes">Attributs Globaux</option>
                    </optgroup>
                    <?php endif; ?>

                </select>
            </div>

            <!-- 2. Filtre Vue -->
            <div>
                <label for="lingua-view-mode" style="display:none;">Affichage :</label>
                <select id="lingua-view-mode" style="width: auto; min-width: 150px;">
                    <option value="list">Liste (Tableau)</option>
                    <option value="masonry">Mosaique</option>
                    <option value="cascade">Cascade</option>
                </select>
            </div>

            <!-- 3. Filtre Langue Cible -->
            <div>
                <select id="lingua-target-lang-select" style="width: auto; min-width: 150px;">
                    <?php 
                    if ( ! empty( $active_languages ) ) : 
                        foreach ( $active_languages as $lang ) : 
                            if ( $lang->code === $default_lang->code ) continue; 
                    ?>
                        <option value="<?php echo esc_attr( $lang->code ); ?>">
                            <?php echo esc_html( $lang->native_name ); ?> (<?php echo esc_html( substr($lang->code, -2) ); ?>)
                        </option>
                    <?php 
                        endforeach; 
                    else : 
                    ?>
                        <option value="">Aucune langue active</option>
                    <?php endif; ?>
                </select>
            </div>

            <div style="width: 1px; height: 30px; background: #ddd;"></div>

            <!-- 4. Filtres Statut (Chips) -->
            <div class="filter-chips">
                <span style="font-weight: 600; margin-right: 5px; color: #555;">Statut :</span>
                <button class="button lingua-chip active" data-status="all">Tous</button>
                <button class="button lingua-chip" data-status="untranslated">À traduire</button>
                <button class="button lingua-chip" data-status="translated">Validés</button>
            </div>
            
            <div style="flex-grow: 1;"></div>
            
            <!-- Bouton IA Global -->
            <button class="button button-primary lingua-bulk-ai" disabled title="Fonctionnalité IA à venir">
                <span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span> Traduire tout avec l'IA
            </button>
        </div>
    </div>
    <!-- CONTENEUR PRINCIPAL -->
    <div id="lingua-translations-container" style="margin-top: 20px;">
        
        <!-- Vue LISTE -->
        <table class="wp-list-table widefat fixed striped table-view-list lingua-view-list" style="margin: 0;">
            <thead>
                <tr>
                    <th class="manage-column column-title lingua-sticky-head" style="position: sticky; left: 0; z-index: 20; background: #f6f7f7; border-right: 1px solid #ddd; min-width: 300px;">Contenu</th>
                    <th style="width: 15%;">Statut</th>
                    <th style="width: 15%; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="lingua-translations-list">
                <tr><td colspan="3" style="text-align: center; padding: 40px;">Chargement...</td></tr>
            </tbody>
        </table>

        <!-- Vue MOSAIQUE -->
        <div id="lingua-masonry-grid" class="lingua-masonry-grid" style="display: none;"></div>

        <!-- Vue CASCADE -->
        <div id="lingua-cascade-grid" class="lingua-cascade-grid" style="display: none;"></div>

    </div>

<!-- MODALE D'ÉDITION -->
<div id="lingua-editor-modal" class="lingua-modal" style="display: none;">
    <div class="lingua-modal-content" style="max-width: 900px; width: 95%; height: 80vh; display: flex; flex-direction: column; border: 1px solid #ccc; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        
        <!-- En-tête Modale (CORRIGÉ AVEC BOUTONS) -->
        <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9; border-top-left-radius: 4px; border-top-right-radius: 4px;">
            <h2 style="margin: 0; font-size: 1.2em;">Éditeur de Traduction</h2>
            <div>
                <!-- Bouton Tout Traduire -->
                <button class="button" id="lingua-translate-all" style="margin-right: 10px;">
                    <span class="dashicons dashicons-translation" style="vertical-align: middle; margin-top: -2px;"></span> 
                    Tout Traduire (IA)
                </button>
                <!-- Bouton Tout Valider -->
                <button class="button button-primary" id="lingua-save-all">Tout Valider</button>
                <button class="button button-secondary lingua-close-modal" style="margin-left: 10px;">Fermer</button>
            </div>
        </div>

        <!-- Corps Modale (Structure Alignée) -->
        <div style="flex-grow: 1; overflow-y: auto; padding: 0; background: #f1f1f1;">
            
            <!-- En-tête fixe des colonnes -->
            <div class="lingua-header-row" style="display: flex; background: #fff; border-bottom: 2px solid #ddd; position: sticky; top: 0; z-index: 10; padding: 15px 20px;">
                <div style="width: 50%; padding-right: 10px;">
                    <h3 style="margin: 0; color: #666; font-size: 1em;">Original (<?php echo esc_html( $default_lang->native_name ); ?>)</h3>
                </div>
                <div style="width: 50%; padding-left: 10px; border-left: 1px solid #eee;">
                     <h3 style="margin: 0; font-size: 1em;">Traduction</h3>
                </div>
            </div>

            <!-- Conteneur des lignes de champs -->
            <div id="lingua-editor-container" style="padding: 20px;">
                <!-- Les lignes seront injectées ici par JS -->
            </div>

        </div>

        <!-- Pied de page Modale -->
        <div style="padding: 15px 20px; border-top: 1px solid #eee; text-align: right; background: #f9f9f9; border-bottom-left-radius: 4px; border-bottom-right-radius: 4px;">
            <span id="lingua-save-status" style="float: left; color: #666; font-size: 13px; line-height: 28px; font-style: italic;"></span>
        </div>

    </div>
</div>

<!-- CSS DE L'INTERFACE -->
<style>
    /* Badges Statut */
    .lingua-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: #fff; text-transform: uppercase; }
    .lingua-badge-green { background-color: #00a32a; }
    .lingua-badge-orange { background-color: #dba617; }
    .lingua-badge-red { background-color: #d63638; }

    /* Chips Filtres */
    .filter-chips { display: flex; align-items: center; gap: 5px; }
    .filter-chips .button { margin-right: 0; border-radius: 15px; font-size: 13px; padding: 0 15px; line-height: 2; height: 30px; }
    .filter-chips .button.active { background: #2271b1; color: #fff; border-color: #2271b1; }

    /* Table & Sticky Column */
    .lingua-view-list { display: table; }
    .lingua-masonry-grid, .lingua-cascade-grid { display: none; }
    .lingua-sticky-col { background: #fff; border-right: 1px solid #ddd; padding: 12px 10px !important; }
    
    /* MOSAIQUE & CASCADE */
    .lingua-masonry-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding-bottom: 40px; }
    .lingua-cascade-grid { columns: 280px 4; column-gap: 20px; padding-bottom: 40px; }
    .lingua-cascade-grid .lingua-card-item { break-inside: avoid; margin-bottom: 20px; display: inline-block; width: 100%; }
    .lingua-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: visible; display: flex; flex-direction: column; transition: transform 0.2s ease; min-height: 300px; }
    .lingua-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .lingua-card-thumb { height: 160px; min-height: 160px; width: 100%; background-color: #f0f0f0; overflow: hidden; position: relative; flex-shrink: 0; }
    .lingua-card-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .lingua-card-content { padding: 15px; flex: 1 1 auto; display: flex; flex-direction: column; min-height: 140px; }
    .lingua-card h3 { margin: 0 0 10px 0; font-size: 1.1em; line-height: 1.3; color: #333; }
    .lingua-card-meta { font-size: 11px; color: #777; margin-bottom: 10px; }
    .lingua-progress-bar { width: 100%; background: #eee; height: 4px; border-radius: 2px; margin: 10px 0; }
    .lingua-progress-bar div { height: 100%; border-radius: 2px; }
    .lingua-card-actions { margin-top: auto; padding-top: 10px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; }

    /* Modale */
    .lingua-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 100001; display: none; align-items: center; justify-content: center; }
    
    /* --- SYSTÈME D'ALIGNEMENT PAR LIGNES --- */
    .lingua-row { display: flex; background: #fff; margin-bottom: 20px; border: 1px solid #dcdcdc; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.03); transition: box-shadow 0.2s; }
    .lingua-row:hover { box-shadow: 0 4px 10px rgba(0,0,0,0.06); }

    .lingua-col { width: 50%; padding: 20px; display: flex; flex-direction: column; }
    .lingua-col-left { background: #fafafa; border-right: 1px solid #e1e1e1; }
    .lingua-col-right { background: #fff; }

    .lingua-field-group { width: 100%; display: flex; flex-direction: column; flex-grow: 1; }
    .lingua-field-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 13px; color: #333; }
    .lingua-field-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; line-height: 1.5; }
    .lingua-field-input:focus { border-color: #0073aa; box-shadow: 0 0 0 1px #0073aa; outline: none; }

    .lingua-input-text { font-size: 15px !important; padding: 10px !important; }
    .lingua-textarea { font-size: 14px !important; padding: 12px !important; resize: vertical; min-height: 100px; flex-grow: 1; }

    .lingua-original-text { background: transparent; padding: 0; color: #444; line-height: 1.6; white-space: pre-wrap; font-size: 14px; font-style: italic; }

    /* Barre d'outils IA */
    .lingua-toolbar-bottom { margin-top: auto; padding-top: 15px; border-top: 1px solid #eee; }
    .lingua-actions-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    .lingua-ai-tools { display: flex; align-items: center; gap: 8px; background: #f6f7f7; padding: 5px; border-radius: 6px; border: 1px solid #dcdcdc; }
    .lingua-engine-selector { margin: 0 !important; padding: 4px 8px !important; border-radius: 4px !important; border: 1px solid #8c8f94 !important; background-color: #fff !important; font-size: 13px !important; height: auto !important; cursor: pointer; min-width: 120px; }
    .lingua-btn-ai { margin: 0 !important; display: inline-flex; align-items: center; }
    .lingua-btn-save-field { margin: 0 !important; }
</style>

<!-- JAVASCRIPT -->
<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>';
    var activeEngines = <?php echo json_encode( $active_engines ); ?>;

    // --- 1. CHARGEMENT INITIAL ---
    function loadList() {
        var postType = $('#lingua-post-type-select').val();
        var viewMode = $('#lingua-view-mode').val();
        var lang = $('#lingua-target-lang-select').val();
        var status = $('.lingua-chip.active').data('status');

        if(!lang) {
            $('#lingua-translations-container').html('<div style="text-align:center; padding: 40px; color: #666;">Veuillez activer une langue cible.</div>');
            return;
        }

        $('.lingua-view-list').hide();
        $('#lingua-masonry-grid').hide();
        $('#lingua-cascade-grid').hide();
        
        if (viewMode === 'list') {
            $('.lingua-view-list').show();
            $('#lingua-translations-list').html('<tr><td colspan="3" style="text-align:center; padding: 40px;">Chargement...</td></tr>');
        } else if (viewMode === 'masonry') {
            $('#lingua-masonry-grid').show().html('<div style="text-align:center; padding: 40px;">Chargement...</div>');
        } else if (viewMode === 'cascade') {
            $('#lingua-cascade-grid').show().html('<div style="text-align:center; padding: 40px;">Chargement...</div>');
        }

        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'lingua_get_translations_list', nonce: nonce, post_type: postType, lang: lang, status: status, view_mode: viewMode },
            success: function(res) {
                if(res.success) {
                    if(viewMode === 'list') $('#lingua-translations-list').html(res.data.html);
                    else if (viewMode === 'masonry') $('#lingua-masonry-grid').html(res.data.html);
                    else if (viewMode === 'cascade') $('#lingua-cascade-grid').html(res.data.html);
                } else { $('#lingua-translations-container').html('<div style="text-align:center; color: red;">Erreur : ' + res.data + '</div>'); }
            }
        });
    }

    $('#lingua-post-type-select, #lingua-target-lang-select, #lingua-view-mode').on('change', loadList);
    $('.lingua-chip').on('click', function() { $('.lingua-chip').removeClass('active'); $(this).addClass('active'); loadList(); });
    loadList();

    // --- 2. MODALE D'ÉDITION ---
    $(document).on('click', '.lingua-btn-edit', function() {
        var id = $(this).data('id');
        var type = $(this).data('type');
        var lang = $('#lingua-target-lang-select').val();
        
        $('#lingua-editor-modal').css('display', 'flex');
        $('#lingua-editor-container').html('<p style="text-align:center; padding: 40px;">Chargement...</p>');
        $('#lingua-save-status').text(''); // Reset statut
        
        $('#lingua-editor-modal').data('current-id', id);
        $('#lingua-editor-modal').data('current-type', type);

        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'lingua_get_editor_data', nonce: nonce, id: id, type: type, lang: lang },
            success: function(res) {
                if(res.success) renderEditor(res.data.fields);
                else { alert('Erreur : ' + res.data); $('#lingua-editor-modal').hide(); }
            }
        });
    });

    $('.lingua-close-modal').on('click', function() { $('#lingua-editor-modal').hide(); });
    $(document).on('click', function(e) { if ($(e.target).is('.lingua-modal')) $('#lingua-editor-modal').hide(); });

      // --- 3. RENDU DE L'ÉDITEUR (AVEC CONSERVATION HTML) ---
    function renderEditor(fields) {
        var html = '';
        if(fields.length === 0) html = '<p style="text-align:center; padding: 40px;">Aucun champ.</p>';

        fields.forEach(function(field) {
            var rowClass = 'lingua-row lingua-type-' + field.type;
            var requiredMark = (field.key === 'post_title' || field.key === 'name') ? ' <span style="color:red">*</span>' : '';
            var savedClass = (field.status === 'validated') ? 'border-color: #00a32a;' : '';
            var fieldValue = field.translated || '';
            
            // On prépare l'affichage visuel (displayOrig)
            var displayOrig = (field.original && field.original.trim() !== '') ? field.original : '<em style="color:#999;">(vide)</em>';

            html += '<div class="' + rowClass + '" data-field="' + field.key + '">';
            
                // 1. COLONNE GAUCHE (Original)
                html += '<div class="lingua-col lingua-col-left"><div class="lingua-field-group">';
                    html += '<label class="lingua-field-label">' + field.label + '</label>';
                    
                    // Affichage visuel pour l'utilisateur
                    html += '<div class="lingua-original-text">' + displayOrig + '</div>';
                    
                    // IMPORTANT : On stocke le code HTML BRUT dans un champ caché
                    // Cela permet à l'IA de recevoir les balises <p>, <strong>, etc.
                    html += '<input type="hidden" class="lingua-raw-original-value" value="' + encodeURIComponent(field.original) + '">';
                    
                html += '</div></div>';

                // 2. COLONNE DROITE (Traduction)
                html += '<div class="lingua-col lingua-col-right"><div class="lingua-field-group">';
                    
                    // Input vs Textarea
                    if (field.type === 'textarea') {
                        html += '<textarea class="lingua-field-input lingua-textarea" style="' + savedClass + '" rows="5">' + fieldValue + '</textarea>';
                    } else {
                        html += '<input type="text" class="lingua-field-input lingua-input-text" value="' + fieldValue + '" style="' + savedClass + '">';
                    }

                    // Barre d'outils IA
                    html += '<div class="lingua-toolbar-bottom">';
                    var selectorHtml = '<select class="lingua-engine-selector">';
                    activeEngines.forEach(function(eng) {
                        var selected = (eng.is_default) ? 'selected' : '';
                        selectorHtml += '<option value="' + eng.slug + '" ' + selected + '>' + eng.label + '</option>';
                    });
                    selectorHtml += '</select>';

                    html += '<div class="lingua-actions-row">';
                    html += '<button class="lingua-btn-save-field button button-secondary">💾 Sauvegarder</button>';
                    html += '<div class="lingua-ai-tools">' + selectorHtml + '<button class="lingua-btn-ai button button-primary">✨ Traduire</button></div>';
                    html += '</div>';
                    html += '</div>'; // Fin toolbar

                html += '</div></div>'; // Fin Col Droite

            html += '</div>'; // Fin Ligne
        });
        
        $('#lingua-editor-container').html(html);
    }
    
    // --- 4. SAUVEGARDE INDIVIDUELLE ---
    $(document).on('click', '.lingua-btn-save-field', function() {
        var row = $(this).closest('.lingua-row');
        var input = row.find('.lingua-field-input');
        var fieldKey = row.data('field');
        var content = input.val();
        
        var saveBtn = $(this);
        var originalText = saveBtn.text();
        saveBtn.text('...').prop('disabled', true);

        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { 
                action: 'lingua_save_translation', nonce: nonce, 
                id: $('#lingua-editor-modal').data('current-id'), 
                type: $('#lingua-editor-modal').data('current-type'), 
                field: fieldKey, 
                lang: $('#lingua-target-lang-select').val(), 
                content: content, status: 'validated'
            },
            success: function(res) {
                if(res.success) {
                    saveBtn.text('✅ OK').css('color', 'green');
                    input.css('border-color', '#00a32a');
                    setTimeout(function(){ saveBtn.text(originalText).css('color', '').prop('disabled', false); }, 2000);
                } else { saveBtn.text('❌ Err'); setTimeout(function(){ saveBtn.text(originalText).prop('disabled', false); }, 2000); }
            }
        });
    });
    
           // --- 5. TRADUCTION INDIVIDUELLE (CORRECTION HTML) ---
    $(document).on('click', '.lingua-btn-ai', function() {
        var row = $(this).closest('.lingua-row');
        var input = row.find('.lingua-field-input');
        
        // CORRECTION : On récupère le HTML caché au lieu du texte affiché
        var hiddenField = row.find('.lingua-raw-original-value');
        var originalText = decodeURIComponent(hiddenField.val());
        
        var engine = row.find('.lingua-engine-selector').val();

        if(!originalText || originalText === '(vide)') { alert("Texte original vide."); return; }
        if (input.val().trim() !== "" && !confirm("Remplacer le contenu existant ?")) return;
        
        var btn = $(this);
        btn.prop('disabled', true).text('⏳...');
        input.prop('disabled', true);

        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { 
                action: 'lingua_translate_field', 
                nonce: nonce, 
                text: originalText, // Contient maintenant les balises <p>, <strong>, etc.
                target_lang: $('#lingua-target-lang-select').val(), 
                engine: engine 
            },
            success: function(res) {
                if(res.success) {
                    input.val(res.data.translated_text);
                    input.css('background-color', '#f0f8ff').animate({'backgroundColor': '#fff'}, 1000);
                } else {
                    alert('Erreur : ' + (res.data || 'Inconnue'));
                }
            },
            error: function() { alert('Erreur réseau'); },
            complete: function() { 
                btn.prop('disabled', false).text('✨ Traduire'); 
                input.prop('disabled', false); 
            }
        });
    });

       // --- 6. TOUT TRADUIRE (CORRECTION HTML) ---
    $('#lingua-translate-all').on('click', function() {
        var btn = $(this);
        var rows = $('#lingua-editor-container .lingua-row');
        var total = rows.length;
        var current = 0;
        var targetLang = $('#lingua-target-lang-select').val();

        if(!targetLang) { alert("Langue cible ?"); return; }

        btn.prop('disabled', true).text('En cours...');
        $('#lingua-save-status').text('⏳ Traduction globale...').css('color', '#dba617');

        function processNext() {
            if (current >= total) {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-translation"></span> Tout Traduire');
                $('#lingua-save-status').text('✅ Terminé !').css('color', 'green');
                return;
            }
            
            var row = rows.eq(current);
            
            // CORRECTION : On récupère le HTML caché
            var hiddenField = row.find('.lingua-raw-original-value');
            var txt = decodeURIComponent(hiddenField.val());
            
            var inp = row.find('.lingua-field-input');
            var eng = row.find('.lingua-engine-selector').val();
            
            current++;

            if (!txt || txt === '(vide)') { processNext(); return; }
            
            $('#lingua-save-status').text('⏳ Champ ' + current + '/' + total);
            
            $.ajax({
                url: ajaxurl, type: 'POST',
                data: { 
                    action: 'lingua_translate_field', 
                    nonce: nonce, 
                    text: txt, // HTML brut
                    target_lang: targetLang, 
                    engine: eng 
                },
                success: function(res) { 
                    if(res.success) inp.val(res.data.translated_text); 
                },
                complete: function() { processNext(); }
            });
        }
        processNext();
    });

    // --- 7. TOUT VALIDER (GLOBAL) ---
    $('#lingua-save-all').on('click', function() {
        var btn = $(this);
        var rows = $('#lingua-editor-container .lingua-row');
        var count = 0;
        var total = rows.length;
        
        btn.prop('disabled', true).text('Sauvegarde...');
        $('#lingua-save-status').text('⏳ Sauvegarde...').css('color', '#dba617');

        rows.each(function(index) {
            var saveBtn = $(this).find('.lingua-btn-save-field');
            if (saveBtn.length) {
                setTimeout(function() {
                    saveBtn.click();
                    count++;
                    if(count === total) {
                        setTimeout(function() {
                            btn.prop('disabled', false).text('Tout Valider');
                            $('#lingua-save-status').text('✅ Tout sauvegardé.').css('color', 'green');
                        }, 1000);
                    }
                }, index * 300);
            }
        });
    });
});
</script>