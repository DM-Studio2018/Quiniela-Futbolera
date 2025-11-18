<?php
/**
 * Sistema de puntuación
 * Archivo: includes/class-quiniela-scoring.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quiniela_Scoring {
    
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = new Quiniela_DB();
        
        // Hook para calcular puntos cuando se actualiza un resultado
        add_action('quiniela_match_result_updated', array($this, 'calculate_match_points'), 10, 1);
    }
    
    /**
     * Calcular puntos de un partido para todos los usuarios
     */
    public function calculate_match_points($match_id) {
        global $wpdb;
        
        // Obtener el partido
        $match = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->db->matches} WHERE id = %d",
            $match_id
        ));
        
        if (!$match || $match->status !== 'finished') {
            return;
        }
        
        // Obtener todas las predicciones para este partido
        $predictions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->db->predictions} WHERE match_id = %d",
            $match_id
        ));
        
        foreach ($predictions as $prediction) {
            $points = $this->calculate_prediction_points(
                $prediction->team1_predicted,
                $prediction->team2_predicted,
                $match->team1_score,
                $match->team2_score
            );
            
            // Actualizar puntos
            $wpdb->update(
                $this->db->predictions,
                array('points_earned' => $points),
                array('id' => $prediction->id),
                array('%d'),
                array('%d')
            );
        }
    }
    
    /**
     * Calcular puntos de una predicción individual
     */
    public function calculate_prediction_points($pred_team1, $pred_team2, $real_team1, $real_team2) {
        $points = 0;
        
        $points_winner = get_option('quiniela_points_winner', 5);
        $points_one_score = get_option('quiniela_points_one_score', 5);
        $points_both_scores = get_option('quiniela_points_both_scores', 10);
        
        // Determinar ganador predicho y real
        $pred_winner = $pred_team1 > $pred_team2 ? 1 : ($pred_team1 < $pred_team2 ? 2 : 0);
        $real_winner = $real_team1 > $real_team2 ? 1 : ($real_team1 < $real_team2 ? 2 : 0);
        
        // Puntos por acertar ganador o empate
        if ($pred_winner === $real_winner) {
            $points += $points_winner;
        }
        
        // Puntos por acertar goles
        $team1_match = ($pred_team1 == $real_team1);
        $team2_match = ($pred_team2 == $real_team2);
        
        if ($team1_match && $team2_match) {
            // Acertó ambos marcadores
            $points += $points_both_scores;
        } elseif ($team1_match || $team2_match) {
            // Acertó un marcador
            $points += $points_one_score;
        }
        
        return $points;
    }
    
    /**
     * Calcular puntos de predicciones finales
     */
    public function calculate_final_predictions_points($tournament_id, $results) {
        global $wpdb;
        
        // Obtener todas las predicciones finales
        $predictions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->db->final_predictions} WHERE tournament_id = %d",
            $tournament_id
        ));
        
        $points_config = array(
            'champion' => get_option('quiniela_points_champion', 30),
            'runner_up' => get_option('quiniela_points_runner_up', 25),
            'third' => get_option('quiniela_points_third', 20),
            'fourth' => get_option('quiniela_points_fourth', 15),
            'top_scorer' => get_option('quiniela_points_top_scorer', 20)
        );
        
        foreach ($predictions as $prediction) {
            $points = 0;
            
            if ($prediction->champion_id == $results['champion_id']) {
                $points += $points_config['champion'];
            }
            
            if ($prediction->runner_up_id == $results['runner_up_id']) {
                $points += $points_config['runner_up'];
            }
            
            if ($prediction->third_place_id == $results['third_place_id']) {
                $points += $points_config['third'];
            }
            
            if ($prediction->fourth_place_id == $results['fourth_place_id']) {
                $points += $points_config['fourth'];
            }
            
            if ($prediction->top_scorer == $results['top_scorer']) {
                $points += $points_config['top_scorer'];
            }
            
            // Actualizar puntos
            $wpdb->update(
                $this->db->final_predictions,
                array('points_earned' => $points),
                array('id' => $prediction->id),
                array('%d'),
                array('%d')
            );
        }
    }
    
    /**
     * Calcular puntos de trivia
     */
    public function calculate_trivia_points($question_id) {
        global $wpdb;
        
        // Obtener la pregunta
        $question = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->db->trivia_questions} WHERE id = %d",
            $question_id
        ));
        
        if (!$question || empty($question->correct_answer)) {
            return;
        }
        
        // Obtener todas las respuestas
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->db->trivia_answers} WHERE question_id = %d",
            $question_id
        ));
        
        foreach ($answers as $answer) {
            $points = 0;
            
            if (trim($answer->answer) === trim($question->correct_answer)) {
                $points = $question->points;
            }
            
            $wpdb->update(
                $this->db->trivia_answers,
                array('points_earned' => $points),
                array('id' => $answer->id),
                array('%d'),
                array('%d')
            );
        }
    }
    
    /**
     * Obtener puntos totales de un usuario
     */
    public function get_user_total_points($user_id, $tournament_id) {
        global $wpdb;
        
        // Puntos de partidos
        $match_points = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(p.points_earned), 0)
            FROM {$this->db->predictions} p
            INNER JOIN {$this->db->matches} m ON p.match_id = m.id
            WHERE p.user_id = %d AND m.tournament_id = %d",
            $user_id,
            $tournament_id
        ));
        
        // Puntos de predicciones finales
        $final_points = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(points_earned, 0)
            FROM {$this->db->final_predictions}
            WHERE user_id = %d AND tournament_id = %d",
            $user_id,
            $tournament_id
        ));
        
        // Puntos de trivia
        $trivia_points = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(ta.points_earned), 0)
            FROM {$this->db->trivia_answers} ta
            INNER JOIN {$this->db->trivia_questions} tq ON ta.question_id = tq.id
            WHERE ta.user_id = %d AND tq.tournament_id = %d",
            $user_id,
            $tournament_id
        ));
        
        return intval($match_points) + intval($final_points) + intval($trivia_points);
    }
    
    /**
     * Obtener tabla de posiciones
     */
    public function get_standings($tournament_id) {
        global $wpdb;
        
        // Obtener usuarios que han pagado
        $paid_users = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$this->db->user_payments}
            WHERE tournament_id = %d AND status = 'completed'",
            $tournament_id
        ));
        
        if (empty($paid_users)) {
            return array();
        }
        
        $standings = array();
        
        foreach ($paid_users as $user_id) {
            $user = get_userdata($user_id);
            
            if ($user) {
                $standings[] = array(
                    'user_id' => $user_id,
                    'username' => $user->user_login,
                    'display_name' => $user->display_name,
                    'points' => $this->get_user_total_points($user_id, $tournament_id)
                );
            }
        }
        
        // Ordenar por puntos descendente
        usort($standings, function($a, $b) {
            return $b['points'] - $a['points'];
        });
        
        // Asignar posiciones
        $position = 1;
        foreach ($standings as $key => $standing) {
            $standings[$key]['position'] = $position;
            $position++;
        }
        
        return $standings;
    }
}
