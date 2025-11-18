<?php
/**
 * Gestión de base de datos
 * Archivo: includes/class-quiniela-db.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quiniela_DB {
    
    private $wpdb;
    private $charset_collate;
    
    // Nombres de tablas
    public $tournaments;
    public $teams;
    public $matches;
    public $predictions;
    public $final_predictions;
    public $trivia_questions;
    public $trivia_answers;
    public $user_payments;
    
    public function __construct() {
        global $wpdb;
        
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
        
        // Definir nombres de tablas
        $this->tournaments = $wpdb->prefix . 'quiniela_tournaments';
        $this->teams = $wpdb->prefix . 'quiniela_teams';
        $this->matches = $wpdb->prefix . 'quiniela_matches';
        $this->predictions = $wpdb->prefix . 'quiniela_predictions';
        $this->final_predictions = $wpdb->prefix . 'quiniela_final_predictions';
        $this->trivia_questions = $wpdb->prefix . 'quiniela_trivia_questions';
        $this->trivia_answers = $wpdb->prefix . 'quiniela_trivia_answers';
        $this->user_payments = $wpdb->prefix . 'quiniela_user_payments';
    }
    
    /**
     * Crear todas las tablas necesarias
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Tabla de torneos
        $sql_tournaments = "CREATE TABLE {$this->tournaments} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            description text,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            num_teams int(11) NOT NULL,
            format varchar(100),
            status varchar(20) DEFAULT 'upcoming',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$this->charset_collate};";
        
        // Tabla de equipos
        $sql_teams = "CREATE TABLE {$this->teams} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            short_name varchar(50) NOT NULL,
            country_code varchar(10) NOT NULL,
            flag_url varchar(500),
            group_letter varchar(5),
            tournament_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id)
        ) {$this->charset_collate};";
        
        // Tabla de partidos
        $sql_matches = "CREATE TABLE {$this->matches} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            match_number int(11) NOT NULL,
            phase varchar(50) NOT NULL,
            group_letter varchar(5),
            team1_id bigint(20) NOT NULL,
            team2_id bigint(20) NOT NULL,
            match_date datetime NOT NULL,
            venue varchar(200),
            team1_score int(11) DEFAULT NULL,
            team2_score int(11) DEFAULT NULL,
            status varchar(20) DEFAULT 'scheduled',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id),
            KEY match_date (match_date),
            KEY phase (phase)
        ) {$this->charset_collate};";
        
        // Tabla de pronósticos de partidos
        $sql_predictions = "CREATE TABLE {$this->predictions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            match_id bigint(20) NOT NULL,
            team1_predicted int(11) NOT NULL,
            team2_predicted int(11) NOT NULL,
            points_earned int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_match (user_id, match_id),
            KEY match_id (match_id)
        ) {$this->charset_collate};";
        
        // Tabla de pronósticos finales (campeón, subcampeón, etc.)
        $sql_final_predictions = "CREATE TABLE {$this->final_predictions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tournament_id bigint(20) NOT NULL,
            champion_id bigint(20),
            runner_up_id bigint(20),
            third_place_id bigint(20),
            fourth_place_id bigint(20),
            top_scorer varchar(200),
            points_earned int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_tournament (user_id, tournament_id)
        ) {$this->charset_collate};";
        
        // Tabla de preguntas de trivia
        $sql_trivia_questions = "CREATE TABLE {$this->trivia_questions} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tournament_id bigint(20) NOT NULL,
            question text NOT NULL,
            options text NOT NULL,
            correct_answer varchar(500),
            points int(11) DEFAULT 10,
            deadline datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tournament_id (tournament_id)
        ) {$this->charset_collate};";
        
        // Tabla de respuestas de trivia
        $sql_trivia_answers = "CREATE TABLE {$this->trivia_answers} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            question_id bigint(20) NOT NULL,
            answer varchar(500),
            points_earned int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_question (user_id, question_id)
        ) {$this->charset_collate};";
        
        // Tabla de pagos de usuarios
        $sql_user_payments = "CREATE TABLE {$this->user_payments} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tournament_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(50),
            transaction_id varchar(200),
            status varchar(20) DEFAULT 'pending',
            paid_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_tournament_payment (user_id, tournament_id),
            KEY status (status)
        ) {$this->charset_collate};";
        
        // Ejecutar creación de tablas
        dbDelta($sql_tournaments);
        dbDelta($sql_teams);
        dbDelta($sql_matches);
        dbDelta($sql_predictions);
        dbDelta($sql_final_predictions);
        dbDelta($sql_trivia_questions);
        dbDelta($sql_trivia_answers);
        dbDelta($sql_user_payments);
        
        // Insertar torneo por defecto (Mundial 2026)
        $this->insert_default_tournament();
    }
    
    /**
     * Insertar torneo Mundial 2026 por defecto
     */
    private function insert_default_tournament() {
        $exists = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->tournaments} WHERE slug = %s",
                'mundial-2026'
            )
        );
        
        if (!$exists) {
            $this->wpdb->insert(
                $this->tournaments,
                array(
                    'name' => 'Mundial de la FIFA 2026',
                    'slug' => 'mundial-2026',
                    'description' => 'Copa Mundial de la FIFA 2026 - Estados Unidos, Canadá y México',
                    'start_date' => '2026-06-11 00:00:00',
                    'end_date' => '2026-07-19 00:00:00',
                    'num_teams' => 48,
                    'format' => '12 grupos de 4 equipos + Ronda de 32',
                    'status' => 'upcoming'
                ),
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
            );
        }
    }
    
    /**
     * Obtener torneo activo
     */
    public function get_active_tournament() {
        return $this->wpdb->get_row(
            "SELECT * FROM {$this->tournaments} 
            WHERE status IN ('upcoming', 'active') 
            ORDER BY start_date ASC 
            LIMIT 1"
        );
    }
    
    /**
     * Verificar si el usuario ha pagado
     */
    public function user_has_paid($user_id, $tournament_id) {
        $payment = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->user_payments} 
                WHERE user_id = %d AND tournament_id = %d AND status = 'completed'",
                $user_id,
                $tournament_id
            )
        );
        
        return !empty($payment);
    }
}
