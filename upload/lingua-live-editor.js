(function($) {
    'use strict';

    var currentData = {};
    var $currentElement = null;

    $(document).ready(function() {
        if ( typeof lingua_live_vars === 'undefined' ) return;
        if ( lingua_live_vars.is_admin !== '1' ) return;

        // Toggle Mode
        $('#wp-admin-bar-lingua-toggle-mode a').on('click', function(e) {
            e.preventDefault();
            $('body').toggleClass('lingua-edit-mode-active');
            var isActive = $('body').hasClass('lingua-edit-mode-active');
            $(this).text( isActive ? '🛑 Arrêter' : '✏️ Mode Traduction' );
            if (isActive) wrapContent(); else unwrapContent();
        });

        // Tout Traduire (Batch)
        $('#wp-admin-bar-lingua-translate-all a').on('click', function(e) {
            e.preventDefault();
            if ( !$('body').hasClass('lingua-edit-mode-active') ) {
                alert('Activez d\'abord le "Mode Traduction".');
                return;
            }
            if ( confirm('Traduire toute la page avec l\'IA ? (Cela peut prendre quelques secondes)') ) {
                translateWholePage();
            }
        });

        // Event Delegation for clicks
        $(document).on('click', '.lingua-live-wrapped', function(e) {
            if (!$('body').hasClass('lingua-edit-mode-active')) return;
            e.preventDefault();
            e.stopPropagation();
            openSidebar( $(this) );
        });

        // Fermeture
        $(document).on('click', '.lingua-sidebar-close, .lingua-sidebar-overlay', closeSidebar );
        
        // Actions
        $(document).on('click', '#lingua-btn-save', saveTranslation );
        $(document).on('click', '#lingua-btn-ai', getAISuggestion );
        
        // Support AJAX WooCommerce
        $(document).ajaxComplete(function(event, xhr, settings) {
            if ( $('body').hasClass('lingua-edit-mode-active') ) {
                setTimeout(function() { wrapContent(); }, 600);
            }
        });
    });

    // --- UTILS ---
    function safeHash(str) {
        var hash = 0;
        if (str.length === 0) return '000000';
        for (var i = 0; i < str.length; i++) {
            var char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return Math.abs(hash).toString(16);
    }

    // ------------------------------------------------------------------
    // FONCTIONS PRINCIPALES
    // ------------------------------------------------------------------

    function translateWholePage() {
        var $elements = $('.lingua-live-wrapped');
        if ( $elements.length === 0 ) return;

        var textsToTranslate = [];
        var elementsData = [];
        
        showGlobalLoader('🚀 Analyse de la page...');

        $elements.each(function() {
            var $el = $(this);
            var original = '';
            
            if ($el.is('img')) original = $el.attr('alt');
            else if ($el.is('input')) original = $el.attr('placeholder') || $el.val();
            else {
                var $clone = $el.clone();
                $clone.find('.lingua-edit-icon').remove();
                original = $clone.text().trim();
            }

            if ( original && original.length > 1 ) {
                textsToTranslate.push(original);
                elementsData.push({
                    $el: $el,
                    id: $el.attr('data-lingua-id'),
                    type: $el.attr('data-lingua-type'),
                    field: $el.attr('data-lingua-field')
                });
            }
        });

        if ( textsToTranslate.length === 0 ) {
            hideGlobalLoader();
            return;
        }

        updateLoaderText('🤖 Traduction par IA (' + textsToTranslate.length + ' éléments)...');

        // 1. Appel Batch AI
        $.post( lingua_live_vars.ajax_url, {
            action: 'lingua_batch_translate_frontend',
            nonce: lingua_live_vars.nonce,
            texts: textsToTranslate,
            target_lang: lingua_live_vars.current_lang,
            engine: 'openrouter' // On pourrait dynamiser le choix
        }, function(res) {
            if ( res.success && res.data.translations ) {
                var translations = res.data.translations;
                var saveBatch = [];

                updateLoaderText('💾 Sauvegarde en base de données...');

                translations.forEach(function(trans, i) {
                    if ( elementsData[i] ) {
                        var data = elementsData[i];
                        saveBatch.push({
                            id: data.id,
                            type: data.type,
                            field: data.field,
                            content: trans
                        });

                        // Mise à jour visuelle immédiate
                        if (data.$el.is('img')) data.$el.attr('alt', trans);
                        else if (data.$el.is('input')) {
                            if (data.$el.attr('placeholder')) data.$el.attr('placeholder', trans);
                            else data.$el.val(trans);
                        } else {
                            var icon = data.$el.find('.lingua-edit-icon').detach();
                            data.$el.text(trans).append(icon);
                        }
                    }
                });

                // 2. Appel Bulk Save
                $.post( lingua_live_vars.ajax_url, {
                    action: 'lingua_bulk_save_translations',
                    nonce: lingua_live_vars.nonce,
                    batch: saveBatch,
                    lang: lingua_live_vars.current_lang
                }, function(saveRes) {
                    hideGlobalLoader();
                    alert('✨ Page traduite et sauvegardée avec succès !');
                });

            } else {
                hideGlobalLoader();
                alert('Erreur lors de la traduction : ' + (res.data || 'Inconnu'));
            }
        });
    }

    function showGlobalLoader(text) {
        $('.lingua-global-loader').remove();
        var html = '<div class="lingua-global-loader"><div class="lingua-loader-content"><div class="lingua-spinner"></div><p>' + text + '</p></div></div>';
        $('body').append(html).addClass('lingua-loading');
    }

    function updateLoaderText(text) {
        $('.lingua-global-loader p').text(text);
    }

    function hideGlobalLoader() {
        $('.lingua-global-loader').fadeOut(300, function() { $(this).remove(); });
        $('body').removeClass('lingua-loading');
    }

    function wrapContent() {
        var wooSelectors = '.woocommerce-product-details__short-description, .description, .woocommerce-loop-product__title, .price, .product_title';
        var selectors = lingua_live_vars.selectors || ('.lingua-injected, h1, h2, h3, button, input[type="submit"], input[placeholder], img[alt], ' + wooSelectors);
        
        $('.lingua-edit-icon').remove();
        $('.lingua-live-wrapped').removeClass('lingua-live-wrapped');

        $(selectors).not('#lingua-live-sidebar, #lingua-live-sidebar *, #wpadminbar, #wpadminbar *, .lingua-global-loader, .lingua-global-loader *').each(function() {
            var $el = $(this);
            if ( $el.hasClass('lingua-live-wrapped') ) return;

            var id = $el.data('id') || $el.attr('data-lingua-id');
            var type = $el.data('type') || $el.attr('data-lingua-type');
            var field = $el.data('field') || $el.attr('data-lingua-field');

            if ( !id || !type || !field ) {
                var $parent = $el.closest('article, .product, .post, [id^="post-"]');
                if ( $parent.length ) {
                    var match = ($parent.attr('class') || '').match(/(?:post|product|page)-(\d+)/);
                    if (match) { 
                        id = match[1]; 
                        type = $parent.hasClass('product') ? 'product' : 'post'; 
                        
                        if ($el.is('img')) field = 'post_thumbnail_alt';
                        else if ($el.is('input') && $el.attr('placeholder')) field = 'placeholder';
                        else if ($el.hasClass('product_title') || $el.is('h1')) field = 'post_title';
                        else if ($el.hasClass('woocommerce-product-details__short-description')) field = 'post_excerpt';
                        else field = 'content_' + safeHash($el.text().substring(0, 20));
                    }
                }
            }

            if ( !id ) {
                var contentRef = $el.is('input') ? ($el.attr('placeholder') || $el.val()) : $el.text().trim();
                if (contentRef) {
                    id = 'ui_' + safeHash(contentRef);
                    type = 'ui';
                    field = 'text';
                }
            }

            if ( !id ) return;

            $el.addClass('lingua-live-wrapped')
               .attr('data-lingua-id', id)
               .attr('data-lingua-type', type)
               .attr('data-lingua-field', field);

            if ($el.css('position') === 'static') $el.css('position', 'relative');
            $el.append('<span class="lingua-edit-icon" title="Modifier">✏️</span>');
        });
    }

    function unwrapContent() {
        $('.lingua-edit-icon').remove();
        $('.lingua-live-wrapped').removeClass('lingua-live-wrapped');
    }

    function openSidebar( $element ) {
        $currentElement = $element;

        currentData = {
            id: $element.attr('data-lingua-id'),
            type: $element.attr('data-lingua-type'),
            field: $element.attr('data-lingua-field'),
            lang: lingua_live_vars.current_lang,
            isAttribute: $element.is('img') || ($element.is('input') && $element.attr('placeholder'))
        };

        $('#lingua-live-sidebar, .lingua-sidebar-overlay').remove();

        var engineOptions = '';
        var enginesList = lingua_live_vars.engines || [];
        enginesList.forEach(function(eng) {
            engineOptions += '<option value="' + eng + '">' + eng.toUpperCase() + '</option>';
        });

        var originalValue = '';
        if ($element.is('img')) {
            originalValue = $element.attr('alt');
        } else if ($element.is('input')) {
            originalValue = $element.attr('placeholder') || $element.val();
        } else {
            var $clone = $element.clone();
            $clone.find('.lingua-edit-icon').remove();
            originalValue = ($element.children().length > 0) ? $clone.html().trim() : $clone.text().trim();
        }

        var html = `
            <div class="lingua-sidebar-overlay"></div>
            <div id="lingua-live-sidebar">
                <div class="lingua-sidebar-header">
                    <h3>✏️ Traduction Rapide</h3>
                    <span class="lingua-sidebar-close">&times;</span>
                </div>
                <div class="lingua-sidebar-body">
                    <div class="lingua-field-group">
                        <label>Texte Original (${lingua_live_vars.default_lang})</label>
                        <div class="lingua-original-text"></div>
                    </div>
                    
                    <div class="lingua-field-group ai-field-group">
                        <label>🤖 Assistance IA</label>
                        <div style="display:flex; gap:10px;">
                            <select id="lingua-engine-selector" style="flex:1;">${engineOptions}</select>
                            <button id="lingua-btn-ai" style="padding:0 15px;">🔍 Suggérer</button>
                        </div>
                    </div>

                    <div class="lingua-field-group">
                        <label>Votre Traduction (${lingua_live_vars.current_lang})</label>
                        <textarea id="lingua-translation-input" placeholder="Écrivez ici..."></textarea>
                    </div>
                </div>
                <div class="lingua-sidebar-footer">
                    <button id="lingua-btn-save">💾 SAUVEGARDER</button>
                </div>
            </div>
        `;

        $('body').append(html);
        $('.lingua-original-text').text(originalValue);
        loadExistingTranslation();
        $('body').addClass('lingua-sidebar-open');
    }

    function closeSidebar() {
        $('body').removeClass('lingua-sidebar-open');
        setTimeout(function() { $('#lingua-live-sidebar, .lingua-sidebar-overlay').remove(); }, 300);
        $currentElement = null;
    }

    function loadExistingTranslation() {
        $.post( lingua_live_vars.ajax_url, {
            action: 'lingua_get_translation_frontend', nonce: lingua_live_vars.nonce,
            id: currentData.id, type: currentData.type, field: currentData.field, lang: currentData.lang
        }, function(res) { 
            if ( res.success && res.data.text ) $('#lingua-translation-input').val( res.data.text ); 
        });
    }

    function saveTranslation() {
        var content = $('#lingua-translation-input').val();
        var $btn = $('#lingua-btn-save');
        $btn.text('⏳...').prop('disabled', true);

        $.post( lingua_live_vars.ajax_url, {
            action: 'lingua_save_translation_frontend', nonce: lingua_live_vars.nonce,
            id: currentData.id, type: currentData.type, field: currentData.field, lang: currentData.lang, content: content
        }, function(res) {
            if ( res.success ) {
                $btn.text('✅ Terminé');
                
                if ( $currentElement ) {
                    if ($currentElement.is('img')) $currentElement.attr('alt', content);
                    else if ($currentElement.is('input')) {
                        if ($currentElement.attr('placeholder')) $currentElement.attr('placeholder', content);
                        else $currentElement.val(content);
                    } else {
                        var icon = $currentElement.find('.lingua-edit-icon').detach();
                        if (content.indexOf('<') !== -1) $currentElement.html(content).append(icon);
                        else $currentElement.text(content).append(icon);
                    }
                }
                setTimeout(closeSidebar, 800);
            } else {
                alert('Erreur: ' + (res.data || 'Impossible de sauvegarder'));
                $btn.text('💾 SAUVEGARDER').prop('disabled', false);
            }
        });
    }

    function getAISuggestion() {
        var original = $('.lingua-original-text').text();
        var engine = $('#lingua-engine-selector').val();
        var $btn = $('#lingua-btn-ai');
        
        if ( !original || !engine ) return;
        $btn.text('⏳').prop('disabled', true);

        $.post( lingua_live_vars.ajax_url, {
            action: 'lingua_ai_suggest_frontend', nonce: lingua_live_vars.nonce,
            text: original, target_lang: currentData.lang, engine: engine
        }, function(res) {
            if ( res.success ) {
                $('#lingua-translation-input').val( res.data.translation );
            }
            $btn.text('🔍 Suggérer').prop('disabled', false);
        });
    }

})(jQuery);