<?php
/**
 * Fichier d'affichage pour la page du Tableau de bord - Premium UI
 *
 * @package    LinguaCommerce_AI
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

global $wpdb;

// 1. Récupération des données réelles
$table_queue = $wpdb->prefix . 'lingua_translation_queue';
$table_logs = $wpdb->prefix . 'lingua_logs';
$table_translations = $wpdb->prefix . 'lingua_translations';
$table_engines = $wpdb->prefix . 'lingua_ai_engines';

// Tâches en attente et en erreur
$queue_pending = 0;
$queue_error = 0;
if ( $wpdb->get_var("SHOW TABLES LIKE '$table_queue'") === $table_queue ) {
    $queue_pending = $wpdb->get_var( "SELECT COUNT(id) FROM $table_queue WHERE status = 'pending'" ) ?: 0;
    $queue_error = $wpdb->get_var( "SELECT COUNT(id) FROM $table_queue WHERE status = 'error'" ) ?: 0;
}

// Statistiques des requêtes IA (Logs)
$total_tokens = 0;
$total_cost = 0;
if ( $wpdb->get_var("SHOW TABLES LIKE '$table_logs'") === $table_logs ) {
    $total_tokens = $wpdb->get_var( "SELECT SUM(tokens_input + tokens_output) FROM $table_logs" ) ?: 0;
    $total_cost = $wpdb->get_var( "SELECT SUM(cost_estimate) FROM $table_logs" ) ?: 0.00;
}

// Nombre total de traductions validées dans la BDD
$total_translations = 0;
if ( $wpdb->get_var("SHOW TABLES LIKE '$table_translations'") === $table_translations ) {
    $total_translations = $wpdb->get_var( "SELECT COUNT(id) FROM $table_translations WHERE status = 'validated'" ) ?: 0;
}

// Nombre de moteurs IA configurés
$active_engines = 0;
if ( $wpdb->get_var("SHOW TABLES LIKE '$table_engines'") === $table_engines ) {
    $active_engines = $wpdb->get_var( "SELECT COUNT(id) FROM $table_engines WHERE status = 'active'" ) ?: 0;
}

// Langues
$active_languages = 0;
if ( class_exists( 'LinguaCommerce_Language_Service' ) ) {
    $langs = LinguaCommerce_Language_Service::get_active_languages();
    if($langs) $active_languages = count($langs);
}

// Pourcentage simulé de complétion
$total_posts = (wp_count_posts('product')->publish ?? 0) + (wp_count_posts('post')->publish ?? 0);
$total_posts = $total_posts ?: 10; // Valeur fallback si wp_count_posts échoue
$estimated_needed = $total_posts * max(1, $active_languages - 1); // Titre+desc (simplifié)
$progress_percent = $estimated_needed > 0 ? min(100, round(($total_translations / ($estimated_needed * 3)) * 100)) : 0;
?>

<div class="wrap lingua-dashboard-wrapper">
    <style>
        .lingua-dashboard-wrapper {
            margin: 20px 20px 0 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: #e2e8f0;
            background: #0f172a;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }
        
        .lingua-dashboard-wrapper * {
            box-sizing: border-box;
        }

        /* Décorations d'arrière-plan (Glowing Orbs) */
        .lingua-dashboard-wrapper::before {
            content: '';
            position: absolute;
            top: -150px;
            left: -150px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .lingua-dashboard-wrapper::after {
            content: '';
            position: absolute;
            bottom: -200px;
            right: -100px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(168,85,247,0.15) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .lingua-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        .lingua-title-area h1 {
            font-size: 32px;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #a855f7 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .lingua-title-area p {
            font-size: 15px;
            color: #94a3b8;
            margin: 5px 0 0 0;
        }

        .lingua-status-pill {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 0 15px rgba(16,185,129,0.2);
            animation: pulse-glow 2s infinite;
        }

        @keyframes pulse-glow {
            0% { box-shadow: 0 0 15px rgba(16,185,129,0.2); }
            50% { box-shadow: 0 0 25px rgba(16,185,129,0.5); }
            100% { box-shadow: 0 0 15px rgba(16,185,129,0.2); }
        }

        /* Grille principale */
        .lingua-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        /* Cartes Haut de Gamme (Glassmorphism) */
        .lingua-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .lingua-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255,255,255,0.1);
        }

        .lingua-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .lingua-card-title {
            font-size: 14px;
            font-weight: 600;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .lingua-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .icon-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .icon-purple { background: rgba(168, 85, 247, 0.1); color: #a855f7; }
        .icon-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .icon-orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        .lingua-card-value {
            font-size: 36px;
            font-weight: 800;
            color: #ffffff;
            margin: 0 0 5px 0;
            line-height: 1;
        }

        .lingua-card-subtitle {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }

        /* Section Layout (2 colonnes) */
        .lingua-sections-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 900px) {
            .lingua-sections-grid { grid-template-columns: 1fr; }
        }

        .lingua-panel {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 24px;
        }

        .lingua-panel h2 {
            color: #ffffff;
            font-size: 18px;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Barre de progression stylisée */
        .lingua-progress-container {
            margin-top: 10px;
        }
        
        .lingua-progress-bar-bg {
            background: #334155;
            height: 10px;
            border-radius: 50px;
            overflow: hidden;
            position: relative;
        }

        .lingua-progress-bar-fill {
            background: linear-gradient(90deg, #3b82f6, #a855f7);
            height: 100%;
            border-radius: 50px;
            width: <?php echo esc_attr( $progress_percent ); ?>%;
            position: relative;
            box-shadow: 0 0 10px rgba(168,85,247,0.5);
        }

        .lingua-progress-bar-fill::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.3) 50%, rgba(255,255,255,0) 100%);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .lingua-progress-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 13px;
            color: #94a3b8;
        }

        /* Actions rapides */
        .lingua-action-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .lingua-action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: #cbd5e1 !important;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .lingua-action-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            color: #ffffff !important;
            transform: translateX(5px);
        }

        .lingua-action-btn span.dashicons {
            color: #a855f7;
            margin-top: 2px;
        }
        
        .lingua-log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .lingua-log-table th {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8;
            font-weight: 500;
        }
        
        .lingua-log-table td {
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #e2e8f0;
        }
    </style>

    <div class="lingua-header">
        <div class="lingua-title-area">
            <h1>LinguaCommerce AI</h1>
            <p>Intelligence Artificielle en temps réel pour votre boutique WordPress.</p>
        </div>
        <div class="lingua-status-pill">
            <span style="font-size: 12px;">🟢</span> Système Principal Actif
        </div>
    </div>

    <div class="lingua-kpi-grid">
        <!-- KPI 1 -->
        <div class="lingua-card">
            <div class="lingua-card-header">
                <h3 class="lingua-card-title">Traductions en Base</h3>
                <div class="lingua-icon icon-blue"><span class="dashicons dashicons-translation"></span></div>
            </div>
            <div>
                <p class="lingua-card-value"><?php echo number_format($total_translations, 0, ',', ' '); ?></p>
                <p class="lingua-card-subtitle">Entrées validées</p>
            </div>
        </div>

        <!-- KPI 2 -->
        <div class="lingua-card">
            <div class="lingua-card-header">
                <h3 class="lingua-card-title">File d'Attente (IA)</h3>
                <div class="lingua-icon icon-orange"><span class="dashicons dashicons-update-alt"></span></div>
            </div>
            <div>
                <p class="lingua-card-value"><?php echo number_format($queue_pending, 0, ',', ' '); ?></p>
                <p class="lingua-card-subtitle"><?php echo $queue_error; ?> tâche(s) en échec</p>
            </div>
        </div>

        <!-- KPI 3 -->
        <div class="lingua-card">
            <div class="lingua-card-header">
                <h3 class="lingua-card-title">Jetons (Tokens) Utilisés</h3>
                <div class="lingua-icon icon-purple"><span class="dashicons dashicons-networking"></span></div>
            </div>
            <div>
                <p class="lingua-card-value"><?php 
                    $formattedTokens = number_format($total_tokens, 0, ',', ' ');
                    echo (strlen($formattedTokens) > 10) ? substr($total_tokens/1000000, 0, 4).'M' : $formattedTokens; 
                ?></p>
                <p class="lingua-card-subtitle">Coût estimé : <?php echo number_format($total_cost, 4); ?> $</p>
            </div>
        </div>

        <!-- KPI 4 -->
        <div class="lingua-card">
            <div class="lingua-card-header">
                <h3 class="lingua-card-title">Moteurs / Langues</h3>
                <div class="lingua-icon icon-green"><span class="dashicons dashicons-admin-site-alt3"></span></div>
            </div>
            <div>
                <p class="lingua-card-value"><?php echo $active_engines; ?> / <?php echo $active_languages; ?></p>
                <p class="lingua-card-subtitle">En service</p>
            </div>
        </div>
    </div>

    <div class="lingua-sections-grid">
        <!-- Panel Gauche : Progression Globale -->
        <div class="lingua-panel">
            <h2><span class="dashicons dashicons-chart-area"></span> Ratio de Localisation du Site</h2>
            <p style="color: #94a3b8; font-size: 14px; margin-bottom: 20px;">Surveillance de l'avancement global en fonction de vos types de contenus et langues.</p>
            
            <div class="lingua-progress-container">
                <div class="lingua-progress-labels">
                    <span>Avancement Approximatif</span>
                    <span style="color:#a855f7; font-weight:bold;"><?php echo $progress_percent; ?>%</span>
                </div>
                <div class="lingua-progress-bar-bg">
                    <div class="lingua-progress-bar-fill"></div>
                </div>
            </div>
            
            <h3 style="color:#fff; margin-top:30px; font-size:16px;">Activité Récente (Logs IA)</h3>
            <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 15px; margin-top: 15px; max-height: 200px; overflow-y: auto;">
                <?php
                if ( $wpdb->get_var("SHOW TABLES LIKE '$table_logs'") === $table_logs ) {
                    $recent_logs = $wpdb->get_results("SELECT * FROM $table_logs ORDER BY timestamp DESC LIMIT 4");
                    if($recent_logs) {
                        echo '<table class="lingua-log-table">';
                        foreach($recent_logs as $log) {
                            $color = $log->status === 'error' ? '#ef4444' : '#10b981';
                            echo '<tr>';
                            echo '<td>' . esc_html(date('H:i', strtotime($log->timestamp))) . '</td>';
                            echo '<td>' . esc_html($log->action_type) . ' (' . esc_html($log->engine) . ')</td>';
                            echo '<td style="color:'.$color.'; font-weight:bold;">' . esc_html(strtoupper($log->status)) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<p style="color:#64748b; font-size:13px; text-align:center;">Opérations IA non détectées.</p>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Panel Droit : Raccourcis -->
        <div class="lingua-panel">
            <h2><span class="dashicons dashicons-admin-links"></span> Raccourcis Rapides</h2>
            <div class="lingua-action-list">
                <a href="admin.php?page=lingua-commerce-ai-ai" class="lingua-action-btn">
                    <span class="dashicons dashicons-admin-generic"></span>
                    Gestion des Moteurs IA
                </a>
                <a href="admin.php?page=lingua-commerce-ai-translations" class="lingua-action-btn">
                    <span class="dashicons dashicons-edit"></span>
                    Gérer la File d'Attente
                </a>
                <a href="admin.php?page=lingua-commerce-ai-languages" class="lingua-action-btn">
                    <span class="dashicons dashicons-translation"></span>
                    Ajouter une Langue
                </a>
                <a href="admin.php?page=lingua-commerce-ai-settings" class="lingua-action-btn">
                    <span class="dashicons dashicons-admin-settings"></span>
                    Paramètres Globaux
                </a>
                <a href="<?php echo esc_url(site_url()); ?>" target="_blank" class="lingua-action-btn" style="border-color: rgba(59, 130, 246, 0.4); background: rgba(59, 130, 246, 0.05); margin-top: 10px;">
                    <span class="dashicons dashicons-visibility" style="color: #3b82f6;"></span>
                    <span style="color: #3b82f6;">Aller sur le site (Live Editor)</span>
                </a>
            </div>
        </div>
    </div>
</div>