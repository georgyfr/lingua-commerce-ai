<?php
/**
 * Vue pour la page de gestion des Langues (AVEC DRAPEAUX)
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! isset( $installed_languages ) ) $installed_languages = array();
if ( ! isset( $available_languages ) ) $available_languages = array();
?>

<div class="wrap lingua-languages-page">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <hr class="wp-header-end">

    <div class="lingua-toolbar" style="margin: 20px 0; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; background: #fff; padding: 15px; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        
        <div class="lingua-filters" style="display: flex; gap: 5px;">
            <button class="button lingua-filter-btn active" data-filter="all">Toutes (<span id="count-all"><?php echo count( $installed_languages ); ?></span>)</button>
            <button class="button lingua-filter-btn" data-filter="active">Actives (<span id="count-active"><?php echo count( array_filter( $installed_languages, function( $l ) { return $l->is_active; } ) ); ?></span>)</button>
            <button class="button lingua-filter-btn" data-filter="inactive">Inactives (<span id="count-inactive"><?php echo count( array_filter( $installed_languages, function( $l ) { return ! $l->is_active; } ) ); ?></span>)</button>
        </div>

        <div style="width: 1px; height: 30px; background: #ddd; margin: 0 10px;"></div>

        <div style="flex-grow: 1; max-width: 300px; position: relative;">
            <input type="text" id="lingua-search-input" class="regular-text" placeholder="Filtrer la liste installée..." style="width: 100%; padding-left: 30px;">
            <span class="dashicons dashicons-search" style="position: absolute; left: 8px; top: 8px; color: #666;"></span>
        </div>

        <!-- ZONE AJOUT LANGUE (AVEC DRAPEAUX) -->
        <div style="display: flex; gap: 5px; align-items: center; border-left: 1px solid #ddd; padding-left: 15px;">
            <div class="lingua-custom-select-wrapper" style="position: relative; width: 300px;">
                <input type="text" id="lingua-combo-input" class="regular-text" placeholder="Rechercher langue à ajouter..." style="width: 100%; cursor: text;" autocomplete="off">
                <span class="dashicons dashicons-arrow-down-alt2" style="position: absolute; right: 8px; top: 8px; color: #666; pointer-events: none;"></span>

                <ul id="lingua-combo-list" class="lingua-dropdown-list" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; max-height: 250px; overflow-y: auto; background: #fff; border: 1px solid #c3c4c7; z-index: 100; margin-top: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <?php if ( empty( $available_languages ) ) : ?>
                        <li class="lingua-no-result" style="padding: 10px; color: #666; text-align: center;">Aucune langue disponible</li>
                    <?php else : ?>
                        <?php foreach ( $available_languages as $code => $data ) : 
                            // Générer URL du drapeau pour la liste déroulante
                            $country_code = strtolower( substr( $code, -2 ) );
                            $flag_url = "https://flagcdn.com/w40/{$country_code}.png";
                        ?>
                            <li class="lingua-option-item" 
                                data-code="<?php echo esc_attr( $code ); ?>" 
                                data-name="<?php echo esc_attr( $data['name'] ); ?>" 
                                data-native="<?php echo esc_attr( $data['native_name'] ); ?>">
                                <img src="<?php echo esc_url( $flag_url ); ?>" class="lingua-flag" alt="">
                                <strong><?php echo esc_html( $data['name'] ); ?></strong> <small style="color:#666">(<?php echo esc_html( $code ); ?>)</small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <button id="lingua-btn-add-confirm" class="button button-primary">Ajouter</button>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title" style="width: 35%;">Langue</th>
                <th scope="col">Code ISO</th>
                <th scope="col">Locale</th>
                <th scope="col">Statut</th>
                <th scope="col">Source</th>
                <th scope="col" style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody id="lingua-languages-list">
            <?php if ( ! empty( $installed_languages ) ) : ?>
                <?php foreach ( $installed_languages as $lang ) : 
                    $is_active = (bool) $lang->is_active;
                    $is_default = (bool) $lang->is_default;
                    $status_class = $is_active ? 'active' : 'inactive';
                    $status_label = $is_active ? 'Actif' : 'Inactif';
                    $status_color = $is_active ? 'green' : 'red';
                    
                    // Drapeau pour le tableau initial
                    $country_code = strtolower( substr( $lang->code, -2 ) );
                    $flag_url = "https://flagcdn.com/w40/{$country_code}.png";
                ?>
                    <tr data-id="<?php echo esc_attr( $lang->id ); ?>" data-status="<?php echo esc_attr( $status_class ); ?>" data-code="<?php echo esc_attr($lang->code); ?>" data-name="<?php echo esc_attr($lang->name); ?>" data-native="<?php echo esc_attr($lang->native_name); ?>">
                        <td>
                            <div style="display:flex; align-items:center;">
                                <img src="<?php echo esc_url( $flag_url ); ?>" class="lingua-flag" alt="">
                                <div style="margin-left: 10px;">
                                    <strong><?php echo esc_html( $lang->name ); ?></strong>
                                    <div style="font-size: 12px; color: #666;"><?php echo esc_html( $lang->native_name ); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><code><?php echo esc_html( $lang->code ); ?></code></td>
                        <td><?php echo esc_html( $lang->locale ); ?></td>
                        <td>
                            <span class="lingua-badge lingua-badge-<?php echo esc_attr( $status_color ); ?>">
                                <?php echo esc_html( $status_label ); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ( $is_default ) : ?>
                                <span class="lingua-badge lingua-badge-blue">Source</span>
                            <?php else : ?>
                                <button class="button button-small lingua-set-default" data-id="<?php echo esc_attr( $lang->id ); ?>">Définir Source</button>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <label class="switch">
                                <input type="checkbox" class="lingua-toggle-status" data-id="<?php echo esc_attr( $lang->id ); ?>" <?php checked( $is_active, true ); ?> <?php disabled( $is_default, true ); ?>>
                                <span class="slider round"></span>
                            </label>
                            <?php if ( ! $is_default ) : ?>
                                <button class="button button-small lingua-delete-lang" data-id="<?php echo esc_attr( $lang->id ); ?>" title="Supprimer" style="color: #a00; margin-left: 10px; border-color: transparent; background: transparent; box-shadow: none;">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px;">Aucune langue installée.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    /* DRAPEAUX */
    .lingua-flag { 
        width: 24px; 
        height: auto; 
        vertical-align: middle; 
        margin-right: 8px; 
        box-shadow: 0 0 2px rgba(0,0,0,0.2); 
        border-radius: 2px; 
        display: inline-block;
    }

    .lingua-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; color: #fff; text-transform: uppercase; }
    .lingua-badge-green { background-color: #00a32a; }
    .lingua-badge-red { background-color: #d63638; }
    .lingua-badge-blue { background-color: #2271b1; }
    
    .switch { position: relative; display: inline-block; width: 36px; height: 20px; vertical-align: middle; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider { background-color: #2271b1; }
    input:checked + .slider:before { transform: translateX(16px); }
    input:disabled + .slider { opacity: 0.5; cursor: not-allowed; background-color: #e0e0e0; }

    .lingua-dropdown-list li { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; transition: background 0.2s; display: flex; align-items: center; }
    .lingua-dropdown-list li:hover { background-color: #f0f0f1; }
    .lingua-dropdown-list li:last-child { border-bottom: none; }
    .lingua-dropdown-list .lingua-no-result { cursor: default; background: none !important; justify-content: center; }
</style>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>';

    // --- 1. CUSTOM SELECT ---
    $('#lingua-combo-input').on('focus click', function() {
        $('#lingua-combo-list').show();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.lingua-custom-select-wrapper').length) {
            $('#lingua-combo-list').hide();
        }
    });

    $('#lingua-combo-input').on('keyup', function() {
        var val = $(this).val().toLowerCase();
        var hasResults = false;

        $('#lingua-combo-list li').each(function() {
            if ($(this).hasClass('lingua-no-result')) { $(this).hide(); return; }
            var text = $(this).text().toLowerCase();
            if (text.indexOf(val) > -1) {
                $(this).show();
                hasResults = true;
            } else {
                $(this).hide();
            }
        });
        if (!hasResults) { $('#lingua-combo-list').find('.lingua-no-result').show(); } 
        else { $('#lingua-combo-list').find('.lingua-no-result').hide(); }
    });

    $('#lingua-combo-list').on('click', 'li', function() {
        if ($(this).hasClass('lingua-no-result')) return;

        // Récupérer le texte sans le tag HTML du drapeau pour l'affichage propre dans l'input
        var text = $(this).find('strong').text() + ' (' + $(this).find('small').text() + ')';
        var code = $(this).data('code');
        var name = $(this).data('name');
        var native = $(this).data('native');

        $('#lingua-combo-input').val(text);
        $('#lingua-combo-input').attr('data-code', code);
        $('#lingua-combo-input').attr('data-name', name);
        $('#lingua-combo-input').attr('data-native', native);
        $('#lingua-combo-list').hide();
    });

    // --- 2. RECHERCHE INSTALLÉES ---
    var searchTimer;
    $('#lingua-search-input').on('keyup', function() {
        var query = $(this).val();
        var currentFilter = $('.lingua-filter-btn.active').data('filter');
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            $.ajax({
                url: ajaxurl, type: 'POST',
                data: { action: 'lingua_search_installed_languages', nonce: nonce, search: query },
                success: function(response) {
                    if(response.success) {
                        $('#lingua-languages-list').html(response.data.html);
                        applyClientSideFilter(currentFilter);
                        updateCounts();
                    }
                }
            });
        }, 400);
    });

    // --- 3. AJOUT LANGUE ---
    $('#lingua-btn-add-confirm').on('click', function() {
        var input = $('#lingua-combo-input');
        var code = input.attr('data-code');
        var name = input.attr('data-name');
        var native = input.attr('data-native');

        if(!code || input.val() === "") { alert('Veuillez sélectionner une langue.'); return; }
        if(!confirm('Ajouter : ' + name + ' (' + native + ') ?')) return;

        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'lingua_add_language', nonce: nonce, code: code, name: name, native_name: native, locale: code },
            success: function(response) {
                if(response.success) {
                    $('li[data-code="'+code+'"]').remove();
                    input.val('');
                    input.removeAttr('data-code data-name data-native');
                    $('#lingua-search-input').keyup();
                } else {
                    alert('Erreur : ' + response.data);
                }
            }
        });
    });

    // --- 4. TOGGLE STATUT ---
    $(document).on('change', '.lingua-toggle-status', function() {
        var checkbox = $(this); var id = checkbox.data('id'); var newState = checkbox.prop('checked') ? 1 : 0;
        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'lingua_toggle_language_status', nonce: nonce, id: id, status: newState },
            success: function(response) {
                if(response.success) {
                    var row = checkbox.closest('tr'); var badge = row.find('.lingua-badge');
                    if(newState == 1) { badge.removeClass('lingua-badge-red').addClass('lingua-badge-green').text('Actif'); row.attr('data-status', 'active'); }
                    else { badge.removeClass('lingua-badge-green').addClass('lingua-badge-red').text('Inactif'); row.attr('data-status', 'inactive'); }
                    var activeFilter = $('.lingua-filter-btn.active').data('filter');
                    if(activeFilter === 'active' && newState == 0) row.hide();
                    if(activeFilter === 'inactive' && newState == 1) row.hide();
                    updateCounts();
                } else { alert('Erreur : ' + response.data); checkbox.prop('checked', !newState); }
            }
        });
    });

    // --- 5. SET DEFAULT ---
    $(document).on('click', '.lingua-set-default', function() {
        if(!confirm('Définir cette langue comme source ?')) return;
        var id = $(this).data('id');
        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'lingua_set_default_language', nonce: nonce, id: id },
            success: function(response) { if(response.success) $('#lingua-search-input').keyup(); else alert('Erreur : ' + response.data); }
        });
    });

    // --- 6. SUPPRESSION (AVEC RESTAURATION + DRAPEAU) ---
    $(document).on('click', '.lingua-delete-lang', function() {
        if(!confirm('Supprimer cette langue ? Elle réapparaîtra dans la liste.')) return;
        
        var btn = $(this);
        var row = btn.closest('tr');
        var id = btn.data('id');

        var code = row.data('code');
        var name = row.data('name');
        var native = row.data('native');

        // GÉNÉRATION JS DU DRAPEAU (Pour la remise dans la liste)
        var countryCode = code.slice(-2).toLowerCase();
        var flagImg = '<img src="https://flagcdn.com/w40/' + countryCode + '.png" class="lingua-flag" alt="">';

        $.ajax({
            url: ajaxurl, type: 'POST',
            data: { action: 'lingua_delete_language', nonce: nonce, id: id },
            success: function(response) {
                if(response.success) { 
                    row.fadeOut(300, function(){ 
                        $(this).remove(); 
                        updateCounts();
                        
                        var newLi = '<li class="lingua-option-item" data-code="' + code + '" data-name="' + name + '" data-native="' + native + '">';
                        newLi += flagImg; // Ajout du drapeau généré en JS
                        newLi += '<strong>' + name + '</strong> <small style="color:#666">(' + code + ')</small>';
                        newLi += '</li>';
                        
                        $('#lingua-combo-list').append(newLi);
                    }); 
                }
                else { alert('Erreur : ' + response.data); }
            }
        });
    });

    // --- UTILITAIRES ---
    $('.lingua-filter-btn').on('click', function() {
        $('.lingua-filter-btn').removeClass('active'); $(this).addClass('active');
        applyClientSideFilter($(this).data('filter'));
    });
    function applyClientSideFilter(type) {
        $('#lingua-languages-list tr').show();
        if(type === 'all') return;
        $('#lingua-languages-list tr').each(function() {
            if(type === 'active' && $(this).data('status') !== 'active') $(this).hide();
            if(type === 'inactive' && $(this).data('status') !== 'inactive') $(this).hide();
        });
    }
    function updateCounts() {
        $('#count-all').text($('#lingua-languages-list tr').length);
        $('#count-active').text($('#lingua-languages-list tr[data-status="active"]').length);
        $('#count-inactive').text($('#lingua-languages-list tr[data-status="inactive"]').length);
    }
});
</script>