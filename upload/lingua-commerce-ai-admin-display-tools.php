<?php
/**
 * Vue pour la page Outils & Maintenance
 */

if ( ! defined( 'WPINC' ) ) { die; }

 // Récupération des statuts pour les notifications
 $status = isset( $_GET['import'] ) ? sanitize_text_field( $_GET['import'] ) : '';
 $reset_status = isset( $_GET['reset'] ) ? sanitize_text_field( $_GET['reset'] ) : '';
?>

<div class="wrap lingua-tools-page">
    <h1>🛠️ Outils & Maintenance</h1>
    
    <?php if ( $status === 'success' ) : ?>
        <div class="notice notice-success is-dismissible"><p>✅ Sauvegarde restaurée avec succès !</p></div>
    <?php elseif ( $status === 'error' ) : ?>
        <div class="notice notice-error is-dismissible"><p>❌ Erreur lors de l'importation.</p></div>
    <?php elseif ( $status === 'invalid_format' ) : ?>
        <div class="notice notice-warning is-dismissible"><p>⚠️ Le fichier fourni n'est pas une sauvegarde valide de LinguaCommerce.</p></div>
    <?php elseif ( $reset_status === 'success' ) : ?>
        <div class="notice notice-success is-dismissible"><p>✅ Réglages réinitialisés.</p></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        
        <!-- COLONNE 1 -->
        <div>
                        <!-- SAUVEGARDE & RESTAURATION -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 5px; margin-bottom:20px;">
                <h2 style="margin-top:0;">💾 Sauvegarde & Restauration</h2>
                <p class="description">Exportez tout (Réglages + Langues + Traductions) pour une sauvegarde complète ou une migration.</p>
                <table class="form-table">
                    <tr><td>
                        <form method="post" action="">
                            <input type="hidden" name="lingua_action" value="export_full_backup">
                            <?php wp_nonce_field( 'lingua_tools_export_full_backup', 'lingua_tools_nonce' ); ?>
                            <button type="submit" class="button button-primary">📥 Télécharger la Sauvegarde Complète (JSON)</button>
                        </form>
                    </td></tr>
                    <tr><td>
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="lingua_action" value="import_full_backup">
                            <?php wp_nonce_field( 'lingua_tools_import_full_backup', 'lingua_tools_nonce' ); ?>
                            <input type="file" name="import_file" accept=".json" required style="margin-bottom:5px;">
                            <button type="submit" class="button">📤 Restaurer une sauvegarde</button>
                        </form>
                        <p class="description" style="color:#d63638; margin-top:5px;">⚠️ Attention : La restauration écrasera les données existantes.</p>
                    </td></tr>
                </table>
            </div>
            <!-- MAINTENANCE DB -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 5px;">
                <h2 style="margin-top:0;">🩺 Base de Données</h2>
                <table class="form-table">
                    <tr>
                        <th>Vérification</th>
                        <td><button id="btn-check-tables" class="button">🔍 Vérifier tables</button></td>
                    </tr>
                    <tr>
                        <th>Nettoyage</th>
                        <td><button id="btn-clean-orphans" class="button">🧹 Orphelins</button></td>
                    </tr>
                    <tr>
                        <th>Cache</th>
                        <td><button id="btn-purge-cache" class="button">🗑️ Vider Cache</button></td>
                    </tr>
                </table>
                <div id="result-db" style="margin-top:10px; padding:10px; background:#f9f9f9; border-left:4px solid #0073aa; display:none;"></div>
            </div>
        </div>

        <!-- COLONNE 2 -->
        <div>
            <!-- DIAGNOSTIC SYSTEME -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 5px; margin-bottom:20px;">
                <h2 style="margin-top:0;">📊 Diagnostic Système</h2>
                <p class="description">Informations techniques utiles pour le support.</p>
                <div id="system-status-container" style="margin-top:10px;">
                    <button id="btn-get-status" class="button button-large">📡 Analyser mon système</button>
                    <div id="system-status-output" style="margin-top:10px; font-family:monospace; font-size:12px; background:#23282d; color:#00ff00; padding:15px; border-radius:4px; display:none; overflow-x:auto;"></div>
                </div>
            </div>

                       <!-- FILE D'ATTENTE IA AVANCÉE -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 5px; margin-bottom:20px;">
                <h2 style="margin-top:0;">🤖 Centre de Contrôle IA</h2>
                <p class="description">Pilotez et surveillez les traductions automatiques en temps réel.</p>

                              <!-- Statistiques Dynamiques -->
                <div id="queue-stats-grid" style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px; margin: 20px 0;">
                    
                    <!-- En Attente -->
                    <div class="lingua-stat-box" style="background:#f0f6fc; padding:15px; text-align:center; border-radius:5px; border:1px solid #d1e4f7;">
                        <div style="font-size:24px; font-weight:bold; color:#0073aa;" id="stat-pending">-</div>
                        <div style="color:#555; font-size:12px; text-transform:uppercase;">En Attente</div>
                    </div>

                    <!-- En Cours -->
                    <div class="lingua-stat-box" style="background:#fff8e5; padding:15px; text-align:center; border-radius:5px; border:1px solid #ffe082;">
                        <div style="font-size:24px; font-weight:bold; color:#ffb900;" id="stat-processing">-</div>
                        <div style="color:#555; font-size:12px; text-transform:uppercase;">En Cours</div>
                    </div>

                    <!-- Échouées -->
                    <div class="lingua-stat-box" style="background:#fcf0f1; padding:15px; text-align:center; border-radius:5px; border:1px solid #f7d1d1;">
                        <div style="font-size:24px; font-weight:bold; color:#d63638;" id="stat-failed">-</div>
                        <div style="color:#555; font-size:12px; text-transform:uppercase;">Échouées</div>
                    </div>

                    <!-- NOUVEAU : Complet -->
                    <div class="lingua-stat-box" style="background:#f1f8f1; padding:15px; text-align:center; border-radius:5px; border:1px solid #d1f7d1;">
                        <div style="font-size:24px; font-weight:bold; color:#00a32a;" id="stat-completed">-</div>
                        <div style="color:#555; font-size:12px; text-transform:uppercase;">Complet</div>
                    </div>
                    
                </div>

                <!-- Actions -->
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button id="btn-refresh-stats" class="button" style="width:100%;">🔄 Rafraîchir les stats</button>
                    <button id="btn-trigger-cron" class="button button-primary" style="width:100%;">⚡ Lancer la traduction maintenant</button>
                    <button id="btn-retry-failed" class="button" style="width:100%;">🔁 Relancer les échecs</button>
                    <button id="btn-clear-queue" class="button button-link-delete" style="width:100%;">🗑️ Vider toute la file</button>
                                        <!-- LIEN VERS PAGE TRADUCTIONS -->
                    <a href="<?php echo admin_url('admin.php?page=lingua-commerce-ai-translations'); ?>" class="button" style="width:100%; text-align:center;">
                        📋 Voir les détails des traductions
                    </a>
                </div>
                
                <div id="ai-log-output" style="margin-top:15px; font-size:12px; color:#666; font-style:italic;"></div>
            </div>

            <!-- ZONE DE DANGER -->
            <div style="background: #fff; border: 1px solid #d63638; padding: 20px; border-radius: 5px;">
                <h2 style="margin-top:0; color:#d63638;">⚠️ Zone de Danger</h2>
                <form method="post" action="" onsubmit="return confirm('Tous les réglages seront perdus ! Continuer ?');">
                    <input type="hidden" name="lingua_action" value="reset_settings">
                    <?php wp_nonce_field( 'lingua_tools_reset_settings', 'lingua_tools_nonce' ); ?>
                    <button type="submit" class="button button-link-delete">Réinitialiser tout le plugin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // --- 1. MAINTENANCE BASE DE DONNÉES ---

    // Fonction helper pour les boutons DB
    function handleDBAction(action, btnId) {
        var btn = $(btnId);
        var resultDiv = $('#result-db'); // La zone de résultat commune
        
        // État chargement
        btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0 5px;"></span> En cours...');
        resultDiv.show().html('<em>Traitement en cours...</em>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: action, // ex: lingua_check_tables
                nonce: '<?php echo wp_create_nonce( "lingua_admin_nonce" ); ?>'
            },
            success: function(res) {
                if(res.success) {
                    resultDiv.html('✅ ' + res.data).css('color', 'green');
                } else {
                    resultDiv.html('❌ Erreur : ' + res.data).css('color', 'red');
                }
            },
            error: function() {
                resultDiv.html('❌ Erreur serveur.').css('color', 'red');
            },
            complete: function() {
                // Restaurer le texte du bouton
                var text = "";
                if(action === 'lingua_check_tables') text = "🔍 Vérifier tables";
                if(action === 'lingua_clean_orphans') text = "🧹 Orphelins";
                if(action === 'lingua_purge_cache') text = "🗑️ Vider Cache";
                
                btn.prop('disabled', false).text(text);
            }
        });
    }

    // Écouteurs pour les 3 boutons
    $('#btn-check-tables').on('click', function(e) {
        e.preventDefault();
        handleDBAction('lingua_check_tables', '#btn-check-tables');
    });

    $('#btn-clean-orphans').on('click', function(e) {
        e.preventDefault();
        handleDBAction('lingua_clean_orphans', '#btn-clean-orphans');
    });

    $('#btn-purge-cache').on('click', function(e) {
        e.preventDefault();
        handleDBAction('lingua_purge_cache', '#btn-purge-cache');
    });

    // --- 2. GESTION IA (Le reste de ton script reste ici) ---
    // ... (conserve le code JavaScript pour la partie IA que je t'ai donné avant)

});
</script>