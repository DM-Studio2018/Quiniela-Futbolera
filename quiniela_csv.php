<?php
/**
 * Importador de CSV
 * Archivo: admin/class-quiniela-csv-importer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Quiniela_CSV_Importer {
    
    private $db;
    
    public function __construct() {
        $this->db = new Quiniela_DB();
    }
    
    /**
     * Importar partidos desde CSV
     */
    public function import($file) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return array('error' => 'No se proporcionó archivo');
        }
        
        $csv_file = $file['tmp_name'];
        
        if (!file_exists($csv_file)) {
            return array('error' => 'Archivo no encontrado');
        }
        
        $tournament = $this->db->get_active_tournament();
        
        if (!$tournament) {
            return array('error' => 'No hay torneo activo');
        }
        
        $handle = fopen($csv_file, 'r');
        
        if ($handle === false) {
            return array('error' => 'No se pudo abrir el archivo');
        }
        
        global $wpdb;
        $imported = 0;
        $errors = array();
        
        // Leer encabezados
        $headers = fgetcsv($handle, 1000, ',');
        $headers = array_map('trim', $headers);
        
        // Validar encabezados requeridos
        $required_headers = array('match_number', 'date', 'time', 'phase', 'team1', 'team2');
        $missing_headers = array_diff($required_headers, $headers);
        
        if (!empty($missing_headers)) {
            fclose($handle);
            return array('error' => 'Faltan columnas: ' . implode(', ', $missing_headers));
        }
        
        $row_number = 1;
        
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $row_number++;
            
            // Crear array asociativo
            $row = array_combine($headers, $data);
            
            // Validar datos
            if (empty($row['match_number']) || empty($row['date']) || empty($row['team1']) || empty($row['team2'])) {
                $errors[] = "Fila {$row_number}: Datos incompletos";
                continue;
            }
            
            // Buscar o crear equipos
            $team1_id = $this->get_or_create_team($row['team1'], $tournament->id, $row['group'] ?? null);
            $team2_id = $this->get_or_create_team($row['team2'], $tournament->id, $row['group'] ?? null);
            
            if (!$team1_id || !$team2_id) {
                $errors[] = "Fila {$row_number}: Error al crear equipos";
                continue;
            }
            
            // Combinar fecha y hora
            $date = trim($row['date']);
            $time = isset($row['time']) ? trim($row['time']) : '00:00';
            
            // Intentar diferentes formatos de fecha
            $match_datetime = $this->parse_datetime($date, $time);
            
            if (!$match_datetime) {
                $errors[] = "Fila {$row_number}: Formato de fecha inválido";
                continue;
            }
            
            // Insertar partido
            $result = $wpdb->insert(
                $this->db->matches,
                array(
                    'tournament_id' => $tournament->id,
                    'match_number' => intval($row['match_number']),
                    'phase' => sanitize_text_field($row['phase']),
                    'group_letter' => isset($row['group']) ? sanitize_text_field($row['group']) : null,
                    'team1_id' => $team1_id,
                    'team2_id' => $team2_id,
                    'match_date' => $match_datetime,
                    'venue' => isset($row['venue']) ? sanitize_text_field($row['venue']) : null,
                    'status' => 'scheduled'
                ),
                array('%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s')
            );
            
            if ($result) {
                $imported++;
            } else {
                $errors[] = "Fila {$row_number}: Error al insertar partido";
            }
        }
        
        fclose($handle);
        
        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        );
    }
    
    /**
     * Obtener o crear equipo
     */
    private function get_or_create_team($team_name, $tournament_id, $group = null) {
        global $wpdb;
        
        $team_name = trim($team_name);
        
        // Buscar equipo existente
        $team_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->db->teams} 
            WHERE name = %s AND tournament_id = %d",
            $team_name,
            $tournament_id
        ));
        
        if ($team_id) {
            return $team_id;
        }
        
        // Crear nuevo equipo
        $short_name = $this->generate_short_name($team_name);
        $country_code = $this->get_country_code($team_name);
        $flag_url = $this->get_flag_url($country_code);
        
        $wpdb->insert(
            $this->db->teams,
            array(
                'name' => $team_name,
                'short_name' => $short_name,
                'country_code' => $country_code,
                'flag_url' => $flag_url,
                'group_letter' => $group,
                'tournament_id' => $tournament_id
            ),
            array('%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Generar nombre corto
     */
    private function generate_short_name($full_name) {
        $words = explode(' ', $full_name);
        
        if (count($words) <= 1) {
            return strtoupper(substr($full_name, 0, 3));
        }
        
        // Tomar primeras letras
        $short = '';
        foreach ($words as $word) {
            $short .= strtoupper(substr($word, 0, 1));
            if (strlen($short) >= 3) break;
        }
        
        return $short;
    }
    
    /**
     * Obtener código de país
     */
    private function get_country_code($country_name) {
        // Mapeo de países a códigos ISO
        $country_codes = array(
            'Argentina' => 'AR',
            'Brasil' => 'BR',
            'Colombia' => 'CO',
            'México' => 'MX',
            'España' => 'ES',
            'Francia' => 'FR',
            'Alemania' => 'DE',
            'Italia' => 'IT',
            'Inglaterra' => 'GB',
            'Portugal' => 'PT',
            'Bélgica' => 'BE',
            'Países Bajos' => 'NL',
            'Holanda' => 'NL',
            'Uruguay' => 'UY',
            'Chile' => 'CL',
            'Perú' => 'PE',
            'Ecuador' => 'EC',
            'Estados Unidos' => 'US',
            'Canadá' => 'CA',
            'Japón' => 'JP',
            'Corea del Sur' => 'KR',
            'Australia' => 'AU',
            'Marruecos' => 'MA',
            'Senegal' => 'SN',
            'Nigeria' => 'NG',
            'Ghana' => 'GH',
            'Camerún' => 'CM',
            'Túnez' => 'TN',
            'Egipto' => 'EG',
            'Suiza' => 'CH',
            'Dinamarca' => 'DK',
            'Suecia' => 'SE',
            'Polonia' => 'PL',
            'Croacia' => 'HR',
            'Serbia' => 'RS',
            'Qatar' => 'QA',
            'Arabia Saudita' => 'SA',
            'Irán' => 'IR',
            'Costa Rica' => 'CR',
            'Panamá' => 'PA'
        );
        
        return $country_codes[$country_name] ?? 'XX';
    }
    
    /**
     * Obtener URL de bandera
     */
    private function get_flag_url($country_code) {
        // Usar servicio de banderas (Flagpedia, CountryFlags API, etc.)
        // Ejemplo con Flagpedia
        return "https://flagcdn.com/w80/{$country_code}.png";
    }
    
    /**
     * Parsear fecha y hora
     */
    private function parse_datetime($date, $time) {
        // Intentar formato DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            $date_formatted = "{$year}-{$month}-{$day}";
        }
        // Intentar formato YYYY-MM-DD
        elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date, $matches)) {
            $date_formatted = $date;
        }
        else {
            return false;
        }
        
        // Parsear hora
        if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minute = $matches[2];
            $time_formatted = "{$hour}:{$minute}:00";
        } else {
            $time_formatted = "00:00:00";
        }
        
        return "{$date_formatted} {$time_formatted}";
    }
    
    /**
     * Importar equipos desde CSV
     */
    public function import_teams($file, $tournament_id) {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return array('error' => 'No se proporcionó archivo');
        }
        
        $csv_file = $file['tmp_name'];
        $handle = fopen($csv_file, 'r');
        
        if ($handle === false) {
            return array('error' => 'No se pudo abrir el archivo');
        }
        
        global $wpdb;
        $imported = 0;
        $errors = array();
        
        // Leer encabezados
        $headers = fgetcsv($handle, 1000, ',');
        $headers = array_map('trim', $headers);
        
        $row_number = 1;
        
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $row_number++;
            $row = array_combine($headers, $data);
            
            if (empty($row['name'])) {
                $errors[] = "Fila {$row_number}: Nombre vacío";
                continue;
            }
            
            $country_code = isset($row['country_code']) ? $row['country_code'] : $this->get_country_code($row['name']);
            
            $result = $wpdb->insert(
                $this->db->teams,
                array(
                    'name' => sanitize_text_field($row['name']),
                    'short_name' => isset($row['short_name']) ? sanitize_text_field($row['short_name']) : $this->generate_short_name($row['name']),
                    'country_code' => $country_code,
                    'flag_url' => $this->get_flag_url($country_code),
                    'group_letter' => isset($row['group']) ? sanitize_text_field($row['group']) : null,
                    'tournament_id' => $tournament_id
                ),
                array('%s', '%s', '%s', '%s', '%s', '%d')
            );
            
            if ($result) {
                $imported++;
            } else {
                $errors[] = "Fila {$row_number}: Error al insertar";
            }
        }
        
        fclose($handle);
        
        return array(
            'success' => true,
            'imported' => $imported,
            'errors' => $errors
        );
    }
}
