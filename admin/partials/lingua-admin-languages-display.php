<?php
/**
 * Vue pour la page Gestion des Langues
 * Drapeaux, sélecteur personnalisé, toggle switches
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) { die; }

// Récupération des langues disponibles
$all_languages = array(
    array( 'code' => 'fr_FR', 'name' => 'Français', 'native' => 'Français', 'flag' => '🇫🇷', 'rtl' => false ),
    array( 'code' => 'en_US', 'name' => 'English', 'native' => 'English', 'flag' => '🇬🇧', 'rtl' => false ),
    array( 'code' => 'es_ES', 'name' => 'Spanish', 'native' => 'Español', 'flag' => '🇪🇸', 'rtl' => false ),
    array( 'code' => 'de_DE', 'name' => 'German', 'native' => 'Deutsch', 'flag' => '🇩🇪', 'rtl' => false ),
    array( 'code' => 'it_IT', 'name' => 'Italian', 'native' => 'Italiano', 'flag' => '🇮🇹', 'rtl' => false ),
    array( 'code' => 'pt_PT', 'name' => 'Portuguese', 'native' => 'Português', 'flag' => '🇵🇹', 'rtl' => false ),
    array( 'code' => 'pt_BR', 'name' => 'Portuguese (Brazil)', 'native' => 'Português (Brasil)', 'flag' => '🇧🇷', 'rtl' => false ),
    array( 'code' => 'nl_NL', 'name' => 'Dutch', 'native' => 'Nederlands', 'flag' => '🇳🇱', 'rtl' => false ),
    array( 'code' => 'ru_RU', 'name' => 'Russian', 'native' => 'Русский', 'flag' => '🇷🇺', 'rtl' => false ),
    array( 'code' => 'zh_CN', 'name' => 'Chinese (Simplified)', 'native' => '简体中文', 'flag' => '🇨🇳', 'rtl' => false ),
    array( 'code' => 'zh_TW', 'name' => 'Chinese (Traditional)', 'native' => '繁體中文', 'flag' => '🇹🇼', 'rtl' => false ),
    array( 'code' => 'ja_JP', 'name' => 'Japanese', 'native' => '日本語', 'flag' => '🇯🇵', 'rtl' => false ),
    array( 'code' => 'ko_KR', 'name' => 'Korean', 'native' => '한국어', 'flag' => '🇰🇷', 'rtl' => false ),
    array( 'code' => 'ar_SA', 'name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦', 'rtl' => true ),
    array( 'code' => 'he_IL', 'name' => 'Hebrew', 'native' => 'עברית', 'flag' => '🇮🇱', 'rtl' => true ),
    array( 'code' => 'fa_IR', 'name' => 'Persian', 'native' => 'فارسی', 'flag' => '🇮🇷', 'rtl' => true ),
    array( 'code' => 'ur_PK', 'name' => 'Urdu', 'native' => 'اردو', 'flag' => '🇵🇰', 'rtl' => true ),
    array( 'code' => 'tr_TR', 'name' => 'Turkish', 'native' => 'Türkçe', 'flag' => '🇹🇷', 'rtl' => false ),
    array( 'code' => 'pl_PL', 'name' => 'Polish', 'native' => 'Polski', 'flag' => '🇵🇱', 'rtl' => false ),
    array( 'code' => 'sv_SE', 'name' => 'Swedish', 'native' => 'Svenska', 'flag' => '🇸🇪', 'rtl' => false ),
    array( 'code' => 'da_DK', 'name' => 'Danish', 'native' => 'Dansk', 'flag' => '🇩🇰', 'rtl' => false ),
    array( 'code' => 'no_NO', 'name' => 'Norwegian', 'native' => 'Norsk', 'flag' => '🇳🇴', 'rtl' => false ),
    array( 'code' => 'fi_FI', 'name' => 'Finnish', 'native' => 'Suomi', 'flag' => '🇫🇮', 'rtl' => false ),
    array( 'code' => 'cs_CZ', 'name' => 'Czech', 'native' => 'Čeština', 'flag' => '🇨🇿', 'rtl' => false ),
    array( 'code' => 'el_GR', 'name' => 'Greek', 'native' => 'Ελληνικά', 'flag' => '🇬🇷', 'rtl' => false ),
    array( 'code' => 'hu_HU', 'name' => 'Hungarian', 'native' => 'Magyar', 'flag' => '🇭🇺', 'rtl' => false ),
    array( 'code' => 'ro_RO', 'name' => 'Romanian', 'native' => 'Română', 'flag' => '🇷🇴', 'rtl' => false ),
    array( 'code' => 'bg_BG', 'name' => 'Bulgarian', 'native' => 'Български', 'flag' => '🇧🇬', 'rtl' => false ),
    array( 'code' => 'hr_HR', 'name' => 'Croatian', 'native' => 'Hrvatski', 'flag' => '🇭🇷', 'rtl' => false ),
    array( 'code' => 'sk_SK', 'name' => 'Slovak', 'native' => 'Slovenčina', 'flag' => '🇸🇰', 'rtl' => false ),
    array( 'code' => 'uk_UA', 'name' => 'Ukrainian', 'native' => 'Українська', 'flag' => '🇺🇦', 'rtl' => false ),
    array( 'code' => 'th_TH', 'name' => 'Thai', 'native' => 'ไทย', 'flag' => '🇹🇭', 'rtl' => false ),
    array( 'code' => 'vi_VN', 'name' => 'Vietnamese', 'native' => 'Tiếng Việt', 'flag' => '🇻🇳', 'rtl' => false ),
    array( 'code' => 'id_ID', 'name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'flag' => '🇮🇩', 'rtl' => false ),
    array( 'code' => 'ms_MY', 'name' => 'Malay', 'native' => 'Bahasa Melayu', 'flag' => '🇲🇾', 'rtl' => false ),
    array( 'code' => 'hi_IN', 'name' => 'Hindi', 'native' => 'हिन्दी', 'flag' => '🇮🇳', 'rtl' => false ),
    array( 'code' => 'bn_BD', 'name' => 'Bengali', 'native' => 'বাংলা', 'flag' => '🇧🇩', 'rtl' => false ),
    array( 'code' => 'ca_ES', 'name' => 'Catalan', 'native' => 'Català', 'flag' => '🏴', 'rtl' => false ),
    array( 'code' => 'eu_ES', 'name' => 'Basque', 'native' => 'Euskara', 'flag' => '🏴', 'rtl' => false ),
    array( 'code' => 'gl_ES', 'name' => 'Galician', 'native' => 'Galego', 'flag' => '🏴', 'rtl' => false ),
);

// Récupération des langues actives depuis la base
global $wpdb;
$table_languages = $wpdb->prefix . 'lingua_languages';
$active_language_codes = array();

if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_languages}'" ) === $table_languages ) {
    $active_rows = $wpdb->get_results( "SELECT code FROM {$table_languages} WHERE is_active = 1" );
    foreach ( $active_rows as $row ) {
        $active_language_codes[] = $row->code;
    }
}

// Si aucune langue active, le français est par défaut
if ( empty( $active_language_codes ) ) {
    $active_language_codes = array( 'fr_FR' );
}

// Statistiques par langue
$lang_stats = array();
$table_translations = $wpdb->prefix . 'lingua_translations';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_translations}'" ) === $table_translations ) {
    $stats_rows = $wpdb->get_results( "SELECT target_lang, COUNT(*) as cnt FROM {$table_translations} GROUP BY target_lang" );
    foreach ( $stats_rows as $row ) {
        $short_code = substr( $row->target_lang, 0, 2 );
        $lang_stats[ $short_code ] = (int) $row->cnt;
    }
}

// Recherche
$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

?>

<style>
    .lingua-languages-page { max-width: 1200px; }
    .lingua-languages-page h1 { margin-bottom: 5px; }
    .lingua-languages-page .lang-subtitle { color: #666; margin-bottom: 25px; font-size: 14px; }

    /* SEARCH BAR */
    .lingua-search-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        background: #fff;
        padding: 15px 20px;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
    }

    .lingua-search-bar input {
        flex: 1;
        font-size: 14px;
        padding: 8px 12px;
    }

    .lingua-search-bar .search-count {
        font-size: 13px;
        color: #666;
        min-width: 120px;
    }

    /* LANGUAGE GRID */
    .lingua-lang-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }

    .lingua-lang-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 16px;
        transition: all 0.2s;
        position: relative;
    }

    .lingua-lang-card:hover {
        border-color: #2271b1;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .lingua-lang-card.is-active {
        border-color: #2271b1;
        background: #f0f6fc;
    }

    .lingua-lang-card.is-source {
        border-color: #00a32a;
        background: #f1f8f1;
    }

    .lingua-lang-card .lang-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
    }

    .lingua-lang-card .lang-flag {
        font-size: 32px;
        line-height: 1;
    }

    .lingua-lang-card .lang-info {
        flex: 1;
    }

    .lingua-lang-card .lang-native-name {
        font-size: 15px;
        font-weight: 700;
        color: #1d2327;
    }

    .lingua-lang-card .lang-english-name {
        font-size: 12px;
        color: #666;
    }

    .lingua-lang-card .lang-code-badge {
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 10px;
        background: #f0f0f0;
        color: #555;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .lingua-lang-card .lang-rtl-badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        background: #fff3cd;
        color: #856404;
        margin-left: 4px;
    }

    .lingua-lang-card .lang-card-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #eee;
    }

    .lingua-lang-card .lang-stat {
        font-size: 12px;
        color: #666;
    }

    .lingua-lang-card .lang-stat strong {
        color: #2271b1;
    }

    /* CUSTOM SELECT */
    .lingua-custom-select-wrapper {
        position: relative;
        min-width: 180px;
    }

    .lingua-custom-select-trigger {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 13px;
    }

    .lingua-custom-select-trigger:hover { border-color: #2271b1; }
    .lingua-custom-select-trigger.open { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }

    .lingua-custom-select-trigger .select-arrow {
        margin-left: auto;
        font-size: 10px;
        color: #999;
        transition: transform 0.2s;
    }

    .lingua-custom-select-trigger.open .select-arrow { transform: rotate(180deg); }

    .lingua-custom-select-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #2271b1;
        border-top: none;
        border-radius: 0 0 6px 6px;
        max-height: 250px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .lingua-custom-select-dropdown.open { display: block; }

    .lingua-custom-select-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        cursor: pointer;
        font-size: 13px;
        transition: background 0.15s;
    }

    .lingua-custom-select-option:hover { background: #f0f6fc; }
    .lingua-custom-select-option.selected { background: #e7f3ff; font-weight: 600; }

    /* TOGGLE SWITCH */
    .lingua-toggle-switch {
        position: relative;
        width: 44px;
        height: 22px;
        flex-shrink: 0;
        display: inline-block;
    }

    .lingua-toggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }

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

    /* SOURCE LANG BADGE */
    .lingua-source-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 10px;
        background: #00a32a;
        color: #fff;
        font-weight: 600;
    }

    /* ACTIVE LANGS SUMMARY */
    .lingua-active-summary {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .lingua-active-summary h2 {
        margin: 0 0 12px 0;
        font-size: 16px;
    }

    .lingua-active-flags {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }

    .lingua-active-flag-item {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f0f6fc;
        border: 1px solid #d1e4f7;
        border-radius: 20px;
        font-size: 13px;
    }

    .lingua-active-flag-item.is-source {
        background: #f1f8f1;
        border-color: #a3d9a3;
    }

    .lingua-active-flag-item .item-flag { font-size: 18px; }
    .lingua-active-flag-item .item-name { font-weight: 500; }
    .lingua-active-flag-item .item-remove {
        cursor: pointer;
        color: #999;
        font-size: 14px;
        margin-left: 4px;
    }
    .lingua-active-flag-item .item-remove:hover { color: #d63638; }

    /* ADD LANGUAGE BUTTON */
    .lingua-add-lang-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border: 1px dashed #2271b1;
        border-radius: 20px;
        color: #2271b1;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
        background: transparent;
    }

    .lingua-add-lang-btn:hover {
        background: #f0f6fc;
        border-style: solid;
    }

    /* RESPONSIVE */
    @media (max-width: 782px) {
        .lingua-lang-grid { grid-template-columns: 1fr; }
        .lingua-search-bar { flex-direction: column; align-items: stretch; }
    }
</style>

<div class="wrap lingua-languages-page">
    <h1>🌍 Gestion des Langues</h1>
    <p class="lang-subtitle">Activez ou désactivez les langues pour votre site multilingue.</p>

    <!-- ACTIVE LANGUAGES SUMMARY -->
    <div class="lingua-active-summary">
        <h2>✅ Langues actives (<?php echo count( $active_language_codes ); ?>)</h2>
        <div class="lingua-active-flags">
            <?php
            $settings = get_option( 'lingua_commerce_ai_settings', array() );
            $source_lang = isset( $settings['default_language'] ) ? $settings['default_language'] : 'fr_FR';

            foreach ( $all_languages as $lang ) :
                if ( ! in_array( $lang['code'], $active_language_codes ) ) continue;
                $is_source = ( $lang['code'] === $source_lang );
            ?>
                <div class="lingua-active-flag-item <?php echo $is_source ? 'is-source' : ''; ?>">
                    <span class="item-flag"><?php echo esc_html( $lang['flag'] ); ?></span>
                    <span class="item-name"><?php echo esc_html( $lang['native'] ); ?></span>
                    <?php if ( $is_source ) : ?>
                        <span style="font-size:10px; color:#00a32a; font-weight:600;">SOURCE</span>
                    <?php else : ?>
                        <span class="item-remove lingua-deactivate-lang" data-code="<?php echo esc_attr( $lang['code'] ); ?>" title="Désactiver">✕</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="button" class="lingua-add-lang-btn" id="lingua-show-add-lang">
                ➕ Ajouter une langue
            </button>
        </div>
    </div>

    <!-- SEARCH BAR -->
    <div class="lingua-search-bar">
        <span style="font-size:16px;">🔍</span>
        <input type="text" id="lingua-lang-search" placeholder="Rechercher une langue (nom, code, drapeau)..." value="<?php echo esc_attr( $search_query ); ?>">
        <span class="search-count" id="lingua-lang-count"><?php echo count( $all_languages ); ?> langues disponibles</span>
    </div>

    <!-- LANGUAGE CARDS -->
    <div class="lingua-lang-grid" id="lingua-lang-grid">
        <?php foreach ( $all_languages as $lang ) :
            $is_active = in_array( $lang['code'], $active_language_codes );
            $is_source = ( $lang['code'] === $source_lang );
            $short_code = substr( $lang['code'], 0, 2 );
            $stat_count = isset( $lang_stats[ $short_code ] ) ? $lang_stats[ $short_code ] : 0;
        ?>
            <div class="lingua-lang-card <?php echo $is_active ? 'is-active' : ''; ?> <?php echo $is_source ? 'is-source' : ''; ?>"
                 data-name="<?php echo esc_attr( strtolower( $lang['name'] . ' ' . $lang['native'] . ' ' . $lang['code'] ) ); ?>">
                <?php if ( $is_source ) : ?>
                    <span class="lingua-source-badge">🌟 Source</span>
                <?php endif; ?>

                <div class="lang-card-header">
                    <span class="lang-flag"><?php echo esc_html( $lang['flag'] ); ?></span>
                    <div class="lang-info">
                        <div class="lang-native-name"><?php echo esc_html( $lang['native'] ); ?></div>
                        <div class="lang-english-name"><?php echo esc_html( $lang['name'] ); ?></div>
                    </div>
                    <span class="lang-code-badge"><?php echo esc_html( $lang['code'] ); ?></span>
                    <?php if ( $lang['rtl'] ) : ?>
                        <span class="lang-rtl-badge">RTL</span>
                    <?php endif; ?>
                </div>

                <div class="lang-card-meta">
                    <div class="lang-stat">
                        📝 <strong><?php echo number_format( $stat_count ); ?></strong> traductions
                    </div>
                    <label class="lingua-toggle-switch" title="<?php echo $is_active ? 'Désactiver' : 'Activer'; ?>">
                        <input type="checkbox"
                               class="lingua-lang-toggle"
                               data-code="<?php echo esc_attr( $lang['code'] ); ?>"
                               <?php checked( $is_active ); ?>
                               <?php echo $is_source ? 'disabled title="Langue source"' : ''; ?>>
                        <span class="lingua-toggle-slider"></span>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- SAVE -->
    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:8px; padding:20px; margin-bottom:20px;">
        <p class="submit">
            <button type="button" id="lingua-save-languages-btn" class="button button-primary button-hero">💾 Sauvegarder les langues actives</button>
            <span id="lingua-languages-status" style="margin-left:15px; font-size:14px;"></span>
        </p>
    </div>

</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var nonce = '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>';

    // Search
    $('#lingua-lang-search').on('input', function() {
        var query = $(this).val().toLowerCase().trim();
        var count = 0;
        $('.lingua-lang-card').each(function() {
            var name = $(this).data('name');
            if (name.indexOf(query) !== -1) {
                $(this).show();
                count++;
            } else {
                $(this).hide();
            }
        });
        $('#lingua-lang-count').text(count + ' langue(s) trouvée(s)');
    });

    // Toggle language
    $(document).on('change', '.lingua-lang-toggle', function() {
        var checkbox = $(this);
        var code = checkbox.data('code');
        var isActive = checkbox.is(':checked');
        var card = checkbox.closest('.lingua-lang-card');

        if (isActive) {
            card.addClass('is-active');
        } else {
            card.removeClass('is-active');
        }
    });

    // Deactivate from summary
    $(document).on('click', '.lingua-deactivate-lang', function() {
        var code = $(this).data('code');
        var toggle = $('.lingua-lang-toggle[data-code="' + code + '"]');
        toggle.prop('checked', false).trigger('change');
        $(this).closest('.lingua-active-flag-item').fadeOut(300, function() { $(this).remove(); });
    });

    // Show add language section (scroll to search)
    $('#lingua-show-add-lang').on('click', function() {
        $('#lingua-lang-search').focus().val('');
        $('.lingua-lang-card').show();
        $('html, body').animate({
            scrollTop: $('#lingua-lang-search').offset().top - 50
        }, 400);
    });

    // Save languages
    $('#lingua-save-languages-btn').on('click', function() {
        var btn = $(this);
        var activeCodes = [];

        $('.lingua-lang-toggle:checked').each(function() {
            activeCodes.push($(this).data('code'));
        });

        if (activeCodes.length === 0) {
            alert('Vous devez avoir au moins une langue active.');
            return;
        }

        btn.prop('disabled', true).text('⏳ Sauvegarde...');
        $('#lingua-languages-status').text('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_save_languages',
                languages: activeCodes,
                nonce: nonce
            },
            success: function(res) {
                if (res.success) {
                    $('#lingua-languages-status').text('✅ Langues sauvegardées avec succès !').css('color', 'green');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    $('#lingua-languages-status').text('❌ Erreur : ' + res.data).css('color', 'red');
                }
            },
            error: function() {
                $('#lingua-languages-status').text('❌ Erreur serveur').css('color', 'red');
            },
            complete: function() {
                btn.prop('disabled', false).text('💾 Sauvegarder les langues actives');
            }
        });
    });
});
</script>
