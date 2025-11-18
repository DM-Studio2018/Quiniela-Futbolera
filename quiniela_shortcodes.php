<?php
/**
 * Shortcodes del plugin
 * Archivo: public/class-quiniela-shortcodes.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quiniela_Shortcodes {
    
    private $db;
    private $scoring;
    
    public function __construct() {
        $this->db = new Quiniela_DB();
        $this->scoring = new Quiniela_Scoring();
        
        add_shortcode('quiniela_predictions', array($this, 'predictions_page'));
        add_shortcode('quiniela_standings', array($this, 'standings_page'));
        add_shortcode('quiniela_prizes', array($this, 'prizes_page'));
        add_shortcode('quiniela_rules', array($this, 'rules_page'));
        
        // AJAX handlers
        add_action('wp_ajax_save_predictions', array($this, 'ajax_save_predictions'));
        add_action('wp_ajax_save_final_predictions', array($this, 'ajax_save_final_predictions'));
        add_action('wp_ajax_save_trivia_answers', array($this, 'ajax_save_trivia_answers'));
    }
    
    /**
     * Shortcode: Mis Pronósticos
     */
    public function predictions_page($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Debes iniciar sesión para ver tus pronósticos.', 'quiniela-fifa') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $tournament = $this->db->get_active_tournament();
        
        if (!$tournament) {
            return '<p>' . __('No hay torneos activos en este momento.', 'quiniela-fifa') . '</p>';
        }
        
        // Verificar pago
        if (!$this->db->user_has_paid($user_id, $tournament->id)) {
            return '<div class="quiniela-notice warning">' . 
                   __('Debes completar tu inscripción para participar. ', 'quiniela-fifa') . 
                   '<a href="#">' . __('Inscribirse ahora', 'quiniela-fifa') . '</a>' .
                   '</div>';
        }
        
        ob_start();
        ?>
        <div class="quiniela-predictions-container">
            <h2><?php echo esc_html($tournament->name); ?></h2>
            
            <?php $this->render_match_predictions($user_id, $tournament->id); ?>
            
            <?php $this->render_final_predictions($user_id, $tournament->id); ?>
            
            <?php $this->render_trivia_questions($user_id, $tournament->id); ?>
            
            <div class="quiniela-actions">
                <button type="button" id="save-predictions" class="button button-primary button-large">
                    <?php _e('Guardar Pronósticos', 'quiniela-fifa'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Renderizar pronósticos de partidos
     */
    private function render_match_predictions($user_id, $tournament_id) {
        global $wpdb;
        
        $db = $this->db;
        $deadline_minutes = get_option('quiniela_edit_deadline_minutes', 10);
        
        // Obtener partidos
        $matches = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, 
             t1.name as team1_name, t1.short_name as team1_short, t1.flag_url as team1_flag,
             t2.name as team2_name, t2.short_name as team2_short, t2.flag_url as team2_flag,
             p.team1_predicted, p.team2_predicted, p.points_earned
            FROM {$db->matches} m
            LEFT JOIN {$db->teams} t1 ON m.team1_id = t1.id
            LEFT JOIN {$db->teams} t2 ON m.team2_id = t2.id
            LEFT JOIN {$db->predictions} p ON m.id = p.match_id AND p.user_id = %d
            WHERE m.tournament_id = %d
            ORDER BY m.match_date ASC, m.match_number ASC",
            $user_id,
            $tournament_id
        ));
        
        if (empty($matches)) {
            echo '<p>' . __('No hay partidos programados aún.', 'quiniela-fifa') . '</p>';
            return;
        }
        
        // Agrupar por fase
        $phases = array();
        foreach ($matches as $match) {
            $phases[$match->phase][] = $match;
        }
        
        echo '<div class="quiniela-matches">';
        
        foreach ($phases as $phase => $phase_matches) {
            echo '<h3 class="phase-title">' . esc_html($phase) . '</h3>';
            echo '<table class="quiniela-table">';
            echo '<thead>
                    <tr>
                        <th>' . __('#', 'quiniela-fifa') . '</th>
                        <th>' . __('Fecha', 'quiniela-fifa') . '</th>
                        <th>' . __('Grupo', 'quiniela-fifa') . '</th>
                        <th colspan="3">' . __('Partido', 'quiniela-fifa') . '</th>
                        <th>' . __('Pronóstico', 'quiniela-fifa') . '</th>
                        <th>' . __('Resultado', 'quiniela-fifa') . '</th>
                        <th>' . __('Puntos', 'quiniela-fifa') . '</th>
                    </tr>
                  </thead>';
            echo '<tbody>';
            
            foreach ($phase_matches as $match) {
                $match_time = strtotime($match->match_date);
                $deadline = $match_time - ($deadline_minutes * 60);
                $is_editable = time() < $deadline;
                $row_class = $is_editable ? 'editable' : 'locked';
                
                if ($match->status === 'finished') {
                    $row_class .= ' finished';
                }
                
                echo '<tr class="' . $row_class . '" data-match-id="' . $match->id . '">';
                echo '<td>' . $match->match_number . '</td>';
                echo '<td>' . date_i18n('d/m/Y H:i', $match_time) . '</td>';
                echo '<td>' . esc_html($match->group_letter ?? '-') . '</td>';
                
                // Equipo 1
                echo '<td class="team">';
                if ($match->team1_flag) {
                    echo '<img src="' . esc_url($match->team1_flag) . '" alt="" class="flag">';
                }
                echo esc_html($match->team1_short);
                echo '</td>';
                
                // VS
                echo '<td class="vs">vs</td>';
                
                // Equipo 2
                echo '<td class="team">';
                if ($match->team2_flag) {
                    echo '<img src="' . esc_url($match->team2_flag) . '" alt="" class="flag">';
                }
                echo esc_html($match->team2_short);
                echo '</td>';
                
                // Pronóstico
                echo '<td class="prediction">';
                if ($is_editable) {
                    echo '<input type="number" name="pred_team1_' . $match->id . '" 
                          value="' . esc_attr($match->team1_predicted ?? '') . '" 
                          min="0" max="20" class="pred-score">';
                    echo ' - ';
                    echo '<input type="number" name="pred_team2_' . $match->id . '" 
                          value="' . esc_attr($match->team2_predicted ?? '') . '" 
                          min="0" max="20" class="pred-score">';
                } else {
                    echo ($match->team1_predicted ?? '-') . ' - ' . ($match->team2_predicted ?? '-');
                }
                echo '</td>';
                
                // Resultado
                echo '<td class="result">';
                if ($match->status === 'finished') {
                    echo $match->team1_score . ' - ' . $match->team2_score;
                } else {
                    echo '-';
                }
                echo '</td>';
                
                // Puntos
                echo '<td class="points">';
                echo ($match->status === 'finished' ? $match->points_earned ?? 0 : '-');
                echo '</td>';
                
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        
        echo '</div>';
    }
    
    /**
     * Renderizar predicciones finales
     */
    private function render_final_predictions($user_id, $tournament_id) {
        global $wpdb;
        
        $db = $this->db;
        
        // Obtener equipos del torneo
        $teams = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, short_name FROM {$db->teams} WHERE tournament_id = %d ORDER BY name",
            $tournament_id
        ));
        
        // Obtener predicciones existentes
        $predictions = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$db->final_predictions} WHERE user_id = %d AND tournament_id = %d",
            $user_id,
            $tournament_id
        ));
        
        ?>
        <div class="quiniela-final-predictions">
            <h3><?php _e('Predicciones Finales', 'quiniela-fifa'); ?></h3>
            <p class="description"><?php _e('Selecciona tus predicciones para las posiciones finales del torneo.', 'quiniela-fifa'); ?></p>
            
            <table class="quiniela-table final-table">
                <tr>
                    <th><?php _e('Campeón', 'quiniela-fifa'); ?></th>
                    <td>
                        <select name="champion_id" class="final-select">
                            <option value="">-- <?php _e('Seleccionar', 'quiniela-fifa'); ?> --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team->id; ?>" 
                                    <?php selected($predictions->champion_id ?? '', $team->id); ?>>
                                    <?php echo esc_html($team->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Subcampeón', 'quiniela-fifa'); ?></th>
                    <td>
                        <select name="runner_up_id" class="final-select">
                            <option value="">-- <?php _e('Seleccionar', 'quiniela-fifa'); ?> --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team->id; ?>" 
                                    <?php selected($predictions->runner_up_id ?? '', $team->id); ?>>
                                    <?php echo esc_html($team->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Tercer Lugar', 'quiniela-fifa'); ?></th>
                    <td>
                        <select name="third_place_id" class="final-select">
                            <option value="">-- <?php _e('Seleccionar', 'quiniela-fifa'); ?> --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team->id; ?>" 
                                    <?php selected($predictions->third_place_id ?? '', $team->id); ?>>
                                    <?php echo esc_html($team->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Cuarto Lugar', 'quiniela-fifa'); ?></th>
                    <td>
                        <select name="fourth_place_id" class="final-select">
                            <option value="">-- <?php _e('Seleccionar', 'quiniela-fifa'); ?> --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team->id; ?>" 
                                    <?php selected($predictions->fourth_place_id ?? '', $team->id); ?>>
                                    <?php echo esc_html($team->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Goleador del Torneo', 'quiniela-fifa'); ?></th>
                    <td>
                        <input type="text" name="top_scorer" 
                               value="<?php echo esc_attr($predictions->top_scorer ?? ''); ?>" 
                               placeholder="<?php _e('Nombre del jugador', 'quiniela-fifa'); ?>">
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    
    /**
     * Renderizar preguntas de trivia
     */
    private function render_trivia_questions($user_id, $tournament_id) {
        global $wpdb;
        
        $db = $this->db;
        
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$db->trivia_questions} WHERE tournament_id = %d ORDER BY id",
            $tournament_id
        ));
        
        if (empty($questions)) {
            return;
        }
        
        echo '<div class="quiniela-trivia">';
        echo '<h3>' . __('Preguntas Extra', 'quiniela-fifa') . '</h3>';
        
        foreach ($questions as $question) {
            $answer = $wpdb->get_var($wpdb->prepare(
                "SELECT answer FROM {$db->trivia_answers} WHERE user_id = %d AND question_id = %d",
                $user_id,
                $question->id
            ));
            
            $options = json_decode($question->options, true);
            
            echo '<div class="trivia-question">';
            echo '<p><strong>' . esc_html($question->question) . '</strong></p>';
            echo '<select name="trivia_' . $question->id . '" class="trivia-select">';
            echo '<option value="">-- ' . __('Seleccionar', 'quiniela-fifa') . ' --</option>';
            
            foreach ($options as $option) {
                echo '<option value="' . esc_attr($option) . '" ' . selected($answer, $option, false) . '>';
                echo esc_html($option);
                echo '</option>';
            }
            
            echo '</select>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Guardar pronósticos vía AJAX
     */
    public function ajax_save_predictions() {
        check_ajax_referer('quiniela_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'quiniela-fifa'));
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $predictions = $_POST['predictions'] ?? array();
        
        foreach ($predictions as $match_id => $scores) {
            // Verificar deadline
            $match = $wpdb->get_row($wpdb->prepare(
                "SELECT match_date FROM {$this->db->matches} WHERE id = %d",
                $match_id
            ));
            
            if (!$match) continue;
            
            $deadline = strtotime($match->match_date) - (get_option('quiniela_edit_deadline_minutes', 10) * 60);
            if (time() >= $deadline) continue;
            
            // Insertar o actualizar
            $wpdb->replace(
                $this->db->predictions,
                array(
                    'user_id' => $user_id,
                    'match_id' => $match_id,
                    'team1_predicted' => intval($scores['team1']),
                    'team2_predicted' => intval($scores['team2'])
                ),
                array('%d', '%d', '%d', '%d')
            );
        }
        
        wp_send_json_success(__('Pronósticos guardados correctamente', 'quiniela-fifa'));
    }
    
    /**
     * Shortcode: Tabla de Posiciones
     */
    public function standings_page($atts) {
        $tournament = $this->db->get_active_tournament();
        
        if (!$tournament) {
            return '<p>' . __('No hay torneos activos.', 'quiniela-fifa') . '</p>';
        }
        
        $standings = $this->scoring->get_standings($tournament->id);
        $current_user_id = get_current_user_id();
        
        ob_start();
        ?>
        <div class="quiniela-standings">
            <h2><?php _e('Tabla de Posiciones', 'quiniela-fifa'); ?></h2>
            <p class="current-time"><?php echo current_time('d/m/Y H:i'); ?></p>
            
            <?php if ($current_user_id): ?>
                <div class="user-stats">
                    <?php 
                    $user_standing = array_filter($standings, function($s) use ($current_user_id) {
                        return $s['user_id'] == $current_user_id;
                    });
                    $user_standing = reset($user_standing);
                    if ($user_standing): ?>
                        <p><?php printf(__('Tu posición: %d - Puntos: %d', 'quiniela-fifa'), 
                                      $user_standing['position'], $user_standing['points']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <table class="quiniela-table standings-table">
                <thead>
                    <tr>
                        <th><?php _e('Posición', 'quiniela-fifa'); ?></th>
                        <?php if (get_option('quiniela_show_user_column', true)): ?>
                            <th><?php _e('Usuario', 'quiniela-fifa'); ?></th>
                        <?php endif; ?>
                        <?php if (get_option('quiniela_show_name_column', true)): ?>
                            <th><?php _e('Nombre', 'quiniela-fifa'); ?></th>
                        <?php endif; ?>
                        <th><?php _e('Puntos', 'quiniela-fifa'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($standings as $standing): 
                        $row_class = ($standing['user_id'] == $current_user_id) ? 'current-user' : '';
                    ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td><?php echo $standing['position']; ?></td>
                            <?php if (get_option('quiniela_show_user_column', true)): ?>
                                <td><?php echo esc_html($standing['username']); ?></td>
                            <?php endif; ?>
                            <?php if (get_option('quiniela_show_name_column', true)): ?>
                                <td><?php echo esc_html($standing['display_name']); ?></td>
                            <?php endif; ?>
                            <td><strong><?php echo $standing['points']; ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Premios
     */
    public function prizes_page($atts) {
        $content = get_option('quiniela_prizes_content', '');
        
        ob_start();
        ?>
        <div class="quiniela-prizes">
            <h2><?php _e('Premios', 'quiniela-fifa'); ?></h2>
            <?php echo wp_kses_post($content); ?>
            
            <?php $this->display_prize_pool(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Mostrar bote de premios
     */
    private function display_prize_pool() {
        global $wpdb;
        $tournament = $this->db->get_active_tournament();
        
        if (!$tournament) return;
        
        $total_paid = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM {$this->db->user_payments}
            WHERE tournament_id = %d AND status = 'completed'",
            $tournament->id
        ));
        
        $pot_percentage = get_option('quiniela_pot_percentage', 30);
        $pot_max = get_option('quiniela_pot_max', 10000000);
        
        $pot_amount = ($total_paid * $pot_percentage / 100);
        if ($pot_amount > $pot_max) {
            $pot_amount = $pot_max;
        }
        
        $champion_pct = get_option('quiniela_prize_champion_pct', 70);
        $runner_up_pct = get_option('quiniela_prize_runner_up_pct', 30);
        
        ?>
        <div class="prize-pool-info">
            <h3><?php _e('Bote Actual', 'quiniela-fifa'); ?></h3>
            <p class="pot-amount"><?php echo number_format($pot_amount, 0, ',', '.'); ?> COP</p>
            <ul>
                <li><?php printf(__('Campeón (%d%%): %s COP', 'quiniela-fifa'), 
                    $champion_pct, number_format($pot_amount * $champion_pct / 100, 0, ',', '.')); ?></li>
                <li><?php printf(__('Subcampeón (%d%%): %s COP', 'quiniela-fifa'), 
                    $runner_up_pct, number_format($pot_amount * $runner_up_pct / 100, 0, ',', '.')); ?></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Shortcode: Reglas
     */
    public function rules_page($atts) {
        $content = get_option('quiniela_rules_content', $this->get_default_rules());
        
        ob_start();
        ?>
        <div class="quiniela-rules">
            <h2><?php _e('Reglas del Torneo', 'quiniela-fifa'); ?></h2>
            <?php echo wp_kses_post($content); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Reglas por defecto
     */
    private function get_default_rules() {
        $points_winner = get_option('quiniela_points_winner', 5);
        $points_one = get_option('quiniela_points_one_score', 5);
        $points_both = get_option('quiniela_points_both_scores', 10);
        
        return sprintf(
            '<h3>Sistema de Puntos</h3>
            <ul>
                <li>%d puntos por acertar el ganador o empate</li>
                <li>%d puntos por acertar el marcador de un equipo</li>
                <li>%d puntos por acertar ambos marcadores</li>
            </ul>
            <h3>Predicciones Finales</h3>
            <ul>
                <li>30 puntos por acertar el campeón</li>
                <li>25 puntos por acertar el subcampeón</li>
                <li>20 puntos por acertar el tercer lugar</li>
                <li>15 puntos por acertar el cuarto lugar</li>
                <li>20 puntos por acertar el goleador</li>
            </ul>',
            $points_winner,
            $points_one,
            $points_both
        );
    }
}
