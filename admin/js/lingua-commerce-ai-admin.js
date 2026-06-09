/**
 * LinguaCommerce AI — Admin Scripts
 * Gestion des onglets, modals, AJAX et interactions UI
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/js
 */

(function($) {
    'use strict';

    /* ============================================================
       1. INITIALISATION AU CHARGEMENT
       ============================================================ */
    $(document).ready(function() {
        LinguaAdmin.init();
    });

    /* ============================================================
       2. OBJET PRINCIPAL
       ============================================================ */
    var LinguaAdmin = {

        init: function() {
            this.initTabs();
            this.initModals();
            this.initDropdowns();
            this.initAjaxForms();
            this.initTooltips();
            this.bindGlobalEvents();
        },

        /* ----------------------------------------------------------
           ONGLETS (Tabs)
           ---------------------------------------------------------- */
        initTabs: function() {
            // Clic sur un onglet
            $(document).on('click', '.lingua-tab', function(e) {
                e.preventDefault();
                var $tab = $(this);
                var target = $tab.data('tab') || $tab.attr('href');

                // Désactiver tous les onglets du même groupe
                $tab.closest('.lingua-tabs').find('.lingua-tab').removeClass('active');
                $tab.addClass('active');

                // Afficher le contenu correspondant
                var $wrapper = $tab.closest('.lingua-admin-wrap') || $tab.closest('form');
                $wrapper.find('.lingua-tab-content').removeClass('active');
                $(target).addClass('active');
            });
        },

        /* ----------------------------------------------------------
           MODALS
           ---------------------------------------------------------- */
        initModals: function() {
            // Ouvrir
            $(document).on('click', '[data-lingua-modal]', function(e) {
                e.preventDefault();
                var modalId = $(this).data('lingua-modal');
                LinguaAdmin.openModal(modalId);
            });

            // Fermer (bouton X)
            $(document).on('click', '.lingua-modal-close', function(e) {
                e.preventDefault();
                LinguaAdmin.closeModal($(this).closest('.lingua-modal-overlay'));
            });

            // Fermer (clic overlay)
            $(document).on('click', '.lingua-modal-overlay', function(e) {
                if ($(e.target).hasClass('lingua-modal-overlay')) {
                    LinguaAdmin.closeModal($(this));
                }
            });

            // Fermer (Echap)
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.lingua-modal-overlay:visible').last().each(function() {
                        LinguaAdmin.closeModal($(this));
                    });
                }
            });
        },

        openModal: function(modalId) {
            var $overlay = $('#' + modalId);
            if ($overlay.length) {
                $overlay.fadeIn(200).css('display', 'flex');
                $('body').css('overflow', 'hidden');
            }
        },

        closeModal: function($overlay) {
            $overlay.fadeOut(150, function() {
                $('body').css('overflow', '');
            });
        },

        /* ----------------------------------------------------------
           DROPDOWNS
           ---------------------------------------------------------- */
        initDropdowns: function() {
            // Toggle dropdown
            $(document).on('click', '.lingua-dropdown-toggle', function(e) {
                e.stopPropagation();
                var $dropdown = $(this).next('.lingua-dropdown-menu');
                $('.lingua-dropdown-menu').not($dropdown).slideUp(150);
                $dropdown.slideToggle(150);
            });

            // Fermer au clic extérieur
            $(document).on('click', function() {
                $('.lingua-dropdown-menu:visible').slideUp(150);
            });
        },

        /* ----------------------------------------------------------
           FORMULAIRES AJAX GÉNÉRIQUES
           ---------------------------------------------------------- */
        initAjaxForms: function() {
            // Soumission AJAX sécurisée
            $(document).on('submit', '.lingua-ajax-form', function(e) {
                e.preventDefault();
                var $form = $(this);
                LinguaAdmin.submitAjaxForm($form);
            });

            // Boutons AJAX isolés (sans formulaire)
            $(document).on('click', '.lingua-ajax-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                LinguaAdmin.ajaxAction($btn);
            });
        },

        submitAjaxForm: function($form) {
            var action = $form.data('action') || $form.find('input[name="action"]').val();
            var nonce = $form.find('input[name="_wpnonce"]').val() || lingua_admin_ajax.nonce;
            var $statusEl = $form.find('.lingua-form-status');
            var $submitBtn = $form.find('button[type="submit"], .lingua-btn-submit');

            // Loading state
            $submitBtn.prop('disabled', true);
            $statusEl.html('<span class="lingua-spinner"></span> Enregistrement...').css('color', 'var(--lingua-text-muted)');

            var data = $form.serialize();
            data += '&action=' + action + '&_wpnonce=' + nonce;

            $.post(lingua_admin_ajax.ajax_url, data, function(res) {
                if (res.success) {
                    $statusEl.html('✅ ' + (res.data || 'Sauvegardé !')).css('color', 'var(--lingua-success)');
                } else {
                    $statusEl.html('❌ ' + (res.data || 'Erreur')).css('color', 'var(--lingua-danger)');
                }
            }).fail(function() {
                $statusEl.html('❌ Erreur serveur').css('color', 'var(--lingua-danger)');
            }).always(function() {
                $submitBtn.prop('disabled', false);
                setTimeout(function() { $statusEl.fadeOut(300); }, 4000);
            });
        },

        ajaxAction: function($btn) {
            var action = $btn.data('action');
            var nonce = $btn.data('nonce') || lingua_admin_ajax.nonce;
            var $statusEl = $('#' + $btn.data('status-target'));
            var extraData = $btn.data('extra') || {};
            var originalText = $btn.text();

            if (!action) return;

            $btn.prop('disabled', true).html('<span class="lingua-spinner"></span>');

            var data = $.extend({
                action: action,
                _wpnonce: nonce
            }, extraData);

            $.post(lingua_admin_ajax.ajax_url, data, function(res) {
                if (res.success) {
                    $btn.text('✅ OK');
                    if ($statusEl.length) $statusEl.html('✅ ' + (res.data || 'Succès')).css('color', 'var(--lingua-success)');
                } else {
                    $btn.text('❌ Échec');
                    if ($statusEl.length) $statusEl.html('❌ ' + (res.data || 'Erreur')).css('color', 'var(--lingua-danger)');
                }
            }).fail(function() {
                $btn.text('❌ Erreur');
                if ($statusEl.length) $statusEl.html('❌ Erreur serveur').css('color', 'var(--lingua-danger)');
            }).always(function() {
                setTimeout(function() {
                    $btn.prop('disabled', false).text(originalText);
                }, 2000);
            });
        },

        /* ----------------------------------------------------------
           TOOLTIPS
           ---------------------------------------------------------- */
        initTooltips: function() {
            $(document).on('mouseenter', '[data-lingua-tip]', function() {
                var tip = $(this).data('lingua-tip');
                var $el = $(this);
                var $tooltip = $('<div class="lingua-tooltip">' + tip + '</div>');
                $el.append($tooltip);
                $tooltip.css({
                    position: 'absolute',
                    bottom: '100%',
                    left: '50%',
                    transform: 'translateX(-50%)',
                    background: 'var(--lingua-bg-card)',
                    color: 'var(--lingua-text)',
                    padding: '6px 10px',
                    borderRadius: '6px',
                    fontSize: '12px',
                    whiteSpace: 'nowrap',
                    zIndex: 99999,
                    boxShadow: '0 4px 12px rgba(0,0,0,0.3)'
                });
            }).on('mouseleave', '[data-lingua-tip]', function() {
                $(this).find('.lingua-tooltip').remove();
            });
        },

        /* ----------------------------------------------------------
           ÉVÉNEMENTS GLOBAUX
           ---------------------------------------------------------- */
        bindGlobalEvents: function() {
            // Confirmation de suppression
            $(document).on('click', '.lingua-confirm-delete', function(e) {
                if (!confirm(lingua_admin_ajax.confirm_delete || 'Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
            });

            // Copier dans le presse-papier
            $(document).on('click', '.lingua-copy-btn', function(e) {
                e.preventDefault();
                var text = $(this).data('copy') || $(this).prev('input, textarea').val();
                LinguaAdmin.copyToClipboard(text, $(this));
            });

            // Toggle visibilité (show/hide)
            $(document).on('click', '.lingua-toggle-visibility', function(e) {
                e.preventDefault();
                var target = $(this).data('toggle-target');
                $(target).slideToggle(200);
                $(this).toggleClass('active');
            });
        },

        /* ----------------------------------------------------------
           UTILITAIRES
           ---------------------------------------------------------- */
        copyToClipboard: function(text, $btn) {
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();

            var original = $btn.text();
            $btn.text('✅ Copié !');
            setTimeout(function() { $btn.text(original); }, 1500);
        },

        /**
         * Affiche une notification temporaire
         */
        showNotice: function(message, type, targetSelector) {
            type = type || 'info'; // success, warning, error, info
            var $notice = $('<div class="lingua-notice lingua-notice-' + type + '">' + message + '</div>');
            var $target = targetSelector ? $(targetSelector) : $('.lingua-admin-wrap').first();
            $target.prepend($notice);
            setTimeout(function() { $notice.fadeOut(300, function() { $(this).remove(); }); }, 4000);
        },

        /**
         * Sélecteur de langue combo (utilisé dans partials)
         */
        initLanguageCombo: function(selector) {
            $(selector).select2({
                placeholder: 'Rechercher une langue...',
                allowClear: true,
                templateResult: function(item) {
                    if (!item.id) return item.text;
                    var flag = $(item.element).data('flag');
                    if (flag) {
                        return $('<span><img src="https://flagcdn.com/w20/' + flag + '.png" class="lingua-flag-img" style="margin-right:8px;">' + item.text + '</span>');
                    }
                    return item.text;
                },
                templateSelection: function(item) {
                    var flag = $(item.element).data('flag');
                    if (flag) {
                        return $('<span><img src="https://flagcdn.com/w20/' + flag + '.png" class="lingua-flag-img" style="margin-right:8px;">' + item.text + '</span>');
                    }
                    return item.text;
                }
            });
        }
    };

    /* ============================================================
       EXPOSITION GLOBALE (pour usage dans les partials)
       ============================================================ */
    window.LinguaAdmin = LinguaAdmin;

})(jQuery);
