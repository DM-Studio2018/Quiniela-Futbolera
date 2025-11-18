<?php
/**
 * Panel de administración
 * Archivo: admin/class-quiniela-admin.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quiniela_Admin {
    
    private $db;
    
    public function __construct() {
        $this->db = new Quiniela_DB();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_quiniela_save_tournament', array($this, 'save_tournament'));
        add_action('admin_post_quiniela_save_team', array($this, 'save_team'));
        add_action('admin_post_quiniela_save_match', array($this, 'save_match'));
        add_action('admin_post_quiniela_import_csv', array($this, 'import_csv'));
    }
    
    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Quiniela FIFA', 'quiniela-fifa'),
            __('Quiniela', 'quiniela-fifa'),
            'manage_options',
            'quiniela-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-awards',
            30
        );
        
        add_submenu_page(
            'quiniela-dashboard',
            __('Torneos', 'quiniela-fifa'),
            __('Torneos', 'quiniela-fifa'),
            'manage_options',
            'quiniela-tournaments',
            array($this, 'tournaments_page')
        );
        
        add_submenu_page(
            'quiniela-dashboard',
            __('Equipos', 'quiniela-fifa'),
            __('Equipos', 'quiniela-fifa'),
            'manage_options',
            'quiniela-teams',
            array($this, 'teams_page')
        );
        
        add_submenu_page(
            'quiniela-dashboard',
            __('Partidos', 'quiniela-fifa'),
            __('Partidos', 'quiniela-fifa'),
            'manage_options',
            'quiniela-matches',
            array($this, 'matches_page')
        );
        
        add_submenu_page(
            'quiniela-dashboard',
            __('Participantes', 'quiniela-fifa'),
            __('Participantes', 'quiniela-fifa'),
            'manage_options',
            'quiniela-participants',
            array($this, 'participants_page')
        );
        
        add_submenu_page(
            'quiniela-dashboard',
            __('Trivia', 'quiniela-fifa'),
            __('Trivia', 'quiniela-fifa'),
            'manage_options',
            'quiniela-trivia',
            array($this, 'trivia_page')
        );
        
        add_submenu_page(
            'quiniela-dashboard',
            __('Configuración', 'quiniela-fifa'),
            __('Configuración', 'quiniela-fifa'),
            'manage_options',
            'quiniela-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'quiniela-dashboard',
            __('Importar CSV', 'quiniela-fifa'),
            __('Importar CSV', 'quiniela-fifa'),
            'manage_options',
            'quiniela-import',
            array($this, 'import_page')
        );
    }
    
    /**
     * Página principal del dashboard
     */
    public function dashboard_page() {
        global $wpdb;
        $tournament = $this->db->get_active_tournament();
        
        if (!$tournament) {
            echo '<div class="wrap"><h1>No hay torneos activos</h1></div>';
            return;
        }
        
        // Estadísticas
        $total_participants = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->db->user_payments} WHERE tournament_id = %d AND status = 'completed'",
            $tournament->id
        ));
        
        $total_matches = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->db->matches} WHERE tournament_id = %d",
            $tournament->id
        ));
        
        $finished_matches = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->db->matches} WHERE tournament_id = %d AND status = 'finished'",
            $tournament->id
        ));
        
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$this->db->user_payments} WHERE tournament_id = %d AND status = 'completed'",
            $tournament->id
        ));
        
        ?>
        <div class="wrap">
            <h1><?php _e('Dashboard - Quiniela FIFA', 'quiniela-fifa'); ?></h1>
            
            <div class="quiniela-dashboard-stats">
                <div class="stat-box">
                    <h3><?php echo esc_html($tournament->name); ?></h3>
                    <p><?php echo date_i18n('d/m/Y', strtotime($tournament->start_date)); ?> - 
                       <?php echo date_i18n('d/m/Y', strtotime($tournament->end_date)); ?></p>
                    <span class="status-badge <?php echo esc_attr($tournament->status); ?>">
                        <?php echo esc_html(ucfirst($tournament->status)); ?>
                    </span>
                </div>
                
                <div class="stat-box">
                    <div class="stat-number"><?php echo number_format($total_participants); ?></div>
                    <div class="stat-label"><?php _e('Participantes', 'quiniela-fifa'); ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-number"><?php echo $finished_matches; ?> / <?php echo $total_matches; ?></div>
                    <div class="stat-label"><?php _e('Partidos Finalizados', 'quiniela-fifa'); ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-number">$<?php echo number_format($total_revenue, 0, ',', '.'); ?></div>
                    <div class="stat-label"><?php _e('Recaudación Total', 'quiniela-fifa'); ?></div>
                </div>
            </div>
            
            <div class="quiniela-quick-actions">
                <h2><?php _e('Acciones Rápidas', 'quiniela-fifa'); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=quiniela-matches&action=add'); ?>" class="button button-primary">
                    <?php _e('Agregar Partido', 'quiniela-fifa'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=quiniela-teams&action=add'); ?>" class="button">
                    <?php _e('Agregar Equipo', 'quiniela-fifa'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=quiniela-import'); ?>" class="button">
                    <?php _e('Importar CSV', 'quiniela-fifa'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=quiniela-participants'); ?>" class="button">
                    <?php _e('Ver Participantes', 'quiniela-fifa'); ?>
                </a>
            </div>
            
            <style>
                .quiniela-dashboard-stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin: 30px 0;
                }
                .stat-box {
                    background: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    text-align: center;
                }
                .stat-number {
                    font-size: 48px;
                    font-weight: 700;
                    color: #2c3e50;
                    margin-bottom: 10px;
                }
                .stat-label {
                    font-size: 16px;
                    color: #7f8c8d;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }
                .status-badge {
                    display: inline-block;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                .status-badge.upcoming {
                    background: #3498db;
                    color: #fff;
                }
                .status-badge.active {
                    background: #27ae60;
                    color: #fff;
                }
                .status-badge.finished {
                    background: #95a5a6;
                    color: #fff;
                }
                .quiniela-quick-actions {
                    background: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .quiniela-quick-actions h2 {
                    margin-top: 0;
                }
                .quiniela-quick-actions .button {
                    margin-right: 10px;
                    margin-bottom: 10px;
                }
            </style>
        </div>
        <?php
    }
    
    /**
     * Página de torneos
     */
    public function tournaments_page() {
        global $wpdb;
        
        $action = $_GET['action'] ?? 'list';
        $tournament_id = $_GET['id'] ?? 0;
        
        if ($action === 'edit' && $tournament_id) {
            $tournament = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->db->tournaments} WHERE id = %d",
                $tournament_id
            ));
            
            $this->render_tournament_form($tournament);
        } elseif ($action === 'add') {
            $this->render_tournament_form();
        } else {
            $this->render_tournaments_list();
        }
    }
    
    /**
     * Renderizar lista de torneos
     */
    private function render_tournaments_list() {
        global $wpdb;
        
        $tournaments = $wpdb->get_results("SELECT * FROM {$this->db->tournaments} ORDER BY start_date DESC");
        
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Torneos', 'quiniela-fifa'); ?>
                <a href="<?php echo admin_url('admin.php?page=quiniela-tournaments&action=add'); ?>" class="page-title-action">
                    <?php _e('Agregar Nuevo', 'quiniela-fifa'); ?>
                </a>
            </h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'quiniela-fifa'); ?></th>
                        <th><?php _e('Nombre', 'quiniela-fifa'); ?></th>
                        <th><?php _e('Fecha Inicio', 'quiniela-fifa'); ?></th>
                        <th><?php _e('Fecha Fin', 'quiniela-fifa'); ?></th>
                        <th><?php _e('Equipos', 'quiniela-fifa'); ?></th>
                        <th><?php _e('Estado', 'quiniela-fifa'); ?></th>
                        <th><?php _e('Acciones', 'quiniela-fifa'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tournaments as $tournament): ?>
                        <tr>
                            <td><?php echo $tournament->id; ?></td>
                            <td><strong><?php echo esc_html($tournament->name); ?></strong></td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($tournament->start_date)); ?></td>
                            <td><?php echo date_i18n('d/m/Y', strtotime($tournament->end_date)); ?></td>
                            <td><?php echo $tournament->num_teams; ?></td>
                            <td>
                                <span class="status-badge <?php echo esc_attr($tournament->status); ?>">
                                    <?php echo esc_html(ucfirst($tournament->status)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=quiniela-tournaments&action=edit&id=' . $tournament->id); ?>" class="button button-small">
                                    <?php _e('Editar', 'quiniela-fifa'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Renderizar formulario de torneo
     */
    private function render_tournament_form($tournament = null) {
        $is_edit = !empty($tournament);
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? __('Editar Torneo', 'quiniela-fifa') : __('Agregar Torneo', 'quiniela-fifa'); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="quiniela_save_tournament">
                <?php wp_nonce_field('quiniela_save_tournament'); ?>
                
                <?php if ($is_edit): ?>
                    <input type="hidden" name="tournament_id" value="<?php echo $tournament->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="name"><?php _e('Nombre del Torneo', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <input type="text" id="name" name="name" class="regular-text" 
                                   value="<?php echo esc_attr($tournament->name ?? ''); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="slug"><?php _e('Slug', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <input type="text" id="slug" name="slug" class="regular-text" 
                                   value="<?php echo esc_attr($tournament->slug ?? ''); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php _e('Descripción', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <textarea id="description" name="description" rows="4" class="large-text"><?php echo esc_textarea($tournament->description ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="start_date"><?php _e('Fecha de Inicio', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <input type="datetime-local" id="start_date" name="start_date" 
                                   value="<?php echo esc_attr($tournament->start_date ?? ''); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="end_date"><?php _e('Fecha de Fin', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <input type="datetime-local" id="end_date" name="end_date" 
                                   value="<?php echo esc_attr($tournament->end_date ?? ''); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="num_teams"><?php _e('Número de Equipos', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <input type="number" id="num_teams" name="num_teams" min="2" 
                                   value="<?php echo esc_attr($tournament->num_teams ?? 48); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="format"><?php _e('Formato', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <input type="text" id="format" name="format" class="regular-text" 
                                   value="<?php echo esc_attr($tournament->format ?? ''); ?>">
                            <p class="description"><?php _e('Ej: 12 grupos de 4 equipos + Ronda de 32', 'quiniela-fifa'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="status"><?php _e('Estado', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="upcoming" <?php selected($tournament->status ?? '', 'upcoming'); ?>><?php _e('Próximo', 'quiniela-fifa'); ?></option>
                                <option value="active" <?php selected($tournament->status ?? '', 'active'); ?>><?php _e('Activo', 'quiniela-fifa'); ?></option>
                                <option value="finished" <?php selected($tournament->status ?? '', 'finished'); ?>><?php _e('Finalizado', 'quiniela-fifa'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php echo $is_edit ? __('Actualizar Torneo', 'quiniela-fifa') : __('Crear Torneo', 'quiniela-fifa'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=quiniela-tournaments'); ?>" class="button">
                        <?php _e('Cancelar', 'quiniela-fifa'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Guardar torneo
     */
    public function save_tournament() {
        check_admin_referer('quiniela_save_tournament');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos', 'quiniela-fifa'));
        }
        
        global $wpdb;
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'slug' => sanitize_title($_POST['slug']),
            'description' => sanitize_textarea_field($_POST['description']),
            'start_date' => sanitize_text_field($_POST['start_date']),
            'end_date' => sanitize_text_field($_POST['end_date']),
            'num_teams' => intval($_POST['num_teams']),
            'format' => sanitize_text_field($_POST['format']),
            'status' => sanitize_text_field($_POST['status'])
        );
        
        if (!empty($_POST['tournament_id'])) {
            // Actualizar
            $wpdb->update(
                $this->db->tournaments,
                $data,
                array('id' => intval($_POST['tournament_id'])),
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Crear
            $wpdb->insert(
                $this->db->tournaments,
                $data,
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
            );
        }
        
        wp_redirect(admin_url('admin.php?page=quiniela-tournaments&message=saved'));
        exit;
    }
    
    /**
     * Páginas adicionales (teams, matches, etc.)
     * Implementación similar a tournaments
     */
    public function teams_page() {
        echo '<div class="wrap"><h1>Gestión de Equipos</h1><p>Funcionalidad similar a torneos...</p></div>';
    }
    
    public function matches_page() {
        echo '<div class="wrap"><h1>Gestión de Partidos</h1><p>Funcionalidad similar a torneos...</p></div>';
    }
    
    public function participants_page() {
        echo '<div class="wrap"><h1>Participantes</h1><p>Lista de usuarios inscritos y pagos...</p></div>';
    }
    
    public function trivia_page() {
        echo '<div class="wrap"><h1>Preguntas de Trivia</h1><p>Crear y gestionar preguntas extra...</p></div>';
    }
    
    /**
     * Página de configuración
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de Quiniela', 'quiniela-fifa'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('quiniela_settings');
                do_settings_sections('quiniela-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('quiniela_settings', 'quiniela_points_winner');
        register_setting('quiniela_settings', 'quiniela_points_one_score');
        register_setting('quiniela_settings', 'quiniela_points_both_scores');
        register_setting('quiniela_settings', 'quiniela_entry_fee');
        register_setting('quiniela_settings', 'quiniela_pot_percentage');
        register_setting('quiniela_settings', 'quiniela_pot_max');
        
        add_settings_section(
            'quiniela_points_section',
            __('Sistema de Puntos', 'quiniela-fifa'),
            null,
            'quiniela-settings'
        );
        
        add_settings_field(
            'quiniela_points_winner',
            __('Puntos por acertar ganador', 'quiniela-fifa'),
            array($this, 'render_number_field'),
            'quiniela-settings',
            'quiniela_points_section',
            array('name' => 'quiniela_points_winner')
        );
    }
    
    public function render_number_field($args) {
        $value = get_option($args['name']);
        echo '<input type="number" name="' . esc_attr($args['name']) . '" value="' . esc_attr($value) . '" min="0">';
    }
    
    /**
     * Página de importación CSV
     */
    public function import_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Importar Partidos desde CSV', 'quiniela-fifa'); ?></h1>
            <p><?php _e('Sube un archivo CSV con los siguientes campos: match_number, date, time, phase, group, team1_id, team2_id', 'quiniela-fifa'); ?></p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="quiniela_import_csv">
                <?php wp_nonce_field('quiniela_import_csv'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="csv_file"><?php _e('Archivo CSV', 'quiniela-fifa'); ?></label></th>
                        <td>
                            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Importar', 'quiniela-fifa'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function import_csv() {
        check_admin_referer('quiniela_import_csv');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos', 'quiniela-fifa'));
        }
        
        // Implementar lógica de importación CSV
        $importer = new Quiniela_CSV_Importer();
        $result = $importer->import($_FILES['csv_file']);
        
        wp_redirect(admin_url('admin.php?page=quiniela-matches&message=imported'));
        exit;
    }
}
