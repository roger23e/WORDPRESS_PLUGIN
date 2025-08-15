<?php
/**
 * Plugin Name: Oracle Database Widget
 * Plugin URI: https://yourwebsite.com
 * Description: A WordPress widget that displays information from an Oracle database
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: oracle-widget
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ORACLE_WIDGET_VERSION', '1.0.0');
define('ORACLE_WIDGET_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ORACLE_WIDGET_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Load plugin text domain for internationalization
 */
function oracle_widget_load_textdomain() {
    load_plugin_textdomain('oracle-widget', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'oracle_widget_load_textdomain');

/**
 * Oracle Database Widget Class
 */
class Oracle_Database_Widget extends WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'oracle_database_widget', // Base ID
            __('Oracle Database Widget', 'oracle-widget'), // Widget name
            array(
                'description' => __('Display information from Oracle database', 'oracle-widget'),
                'classname' => 'oracle-database-widget'
            )
        );
    }
    
    /**
     * Widget frontend display
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        $title = !empty($instance['title']) ? $instance['title'] : '';
        if (!empty($title)) {
            echo $args['before_title'] . apply_filters('widget_title', $title) . $args['after_title'];
        }
        
        // Get widget content
        $content = $this->get_oracle_data($instance);
        
        if ($content) {
            echo '<div class="oracle-widget-content">';
            echo $content;
            echo '</div>';
        } else {
            echo '<p>' . __('No hay datos disponibles', 'oracle-widget') . '</p>';
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Widget backend form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $connection_id = !empty($instance['connection_id']) ? $instance['connection_id'] : '';
        $query_id = !empty($instance['query_id']) ? $instance['query_id'] : '';
        $limit = !empty($instance['limit']) ? $instance['limit'] : '10';
        $order_by = !empty($instance['order_by']) ? $instance['order_by'] : '';
        $display_type = !empty($instance['display_type']) ? $instance['display_type'] : 'table';
        $map_region = !empty($instance['map_region']) ? $instance['map_region'] : 'world';
        
        // Get saved connections and queries
        $connections = get_option('oracle_widget_connections', array());
        $queries = get_option('oracle_widget_queries', array());
        ?>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Título:', 'oracle-widget'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('connection_id'); ?>"><?php _e('Conexión de Base de Datos:', 'oracle-widget'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('connection_id'); ?>" name="<?php echo $this->get_field_name('connection_id'); ?>">
                <option value=""><?php _e('-- Seleccionar Conexión --', 'oracle-widget'); ?></option>
                <?php foreach ($connections as $id => $connection): ?>
                    <option value="<?php echo esc_attr($id); ?>" <?php selected($connection_id, $id); ?>>
                        <?php echo esc_html($connection['name']); ?> (<?php echo esc_html($connection['host']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <small><a href="<?php echo admin_url('admin.php?page=oracle-widget-settings'); ?>"><?php _e('Gestionar Conexiones', 'oracle-widget'); ?></a></small>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('query_id'); ?>"><?php _e('Consulta SQL:', 'oracle-widget'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('query_id'); ?>" name="<?php echo $this->get_field_name('query_id'); ?>">
                <option value=""><?php _e('-- Seleccionar Consulta --', 'oracle-widget'); ?></option>
                <?php foreach ($queries as $id => $query): ?>
                    <option value="<?php echo esc_attr($id); ?>" <?php selected($query_id, $id); ?>>
                        <?php echo esc_html($query['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small><a href="<?php echo admin_url('admin.php?page=oracle-widget-settings'); ?>"><?php _e('Gestionar Consultas', 'oracle-widget'); ?></a></small>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('display_type'); ?>"><?php _e('Tipo de Visualización:', 'oracle-widget'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('display_type'); ?>" name="<?php echo $this->get_field_name('display_type'); ?>">
                <option value="table" <?php selected($display_type, 'table'); ?>><?php _e('Tabla', 'oracle-widget'); ?></option>
                <option value="map" <?php selected($display_type, 'map'); ?>><?php _e('Mapa (requiere columnas LATITUDE, LONGITUDE, NAME)', 'oracle-widget'); ?></option>
                <option value="both" <?php selected($display_type, 'both'); ?>><?php _e('Tabla y Mapa (requiere columnas LATITUDE, LONGITUDE, NAME)', 'oracle-widget'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('map_region'); ?>"><?php _e('Región del Mapa:', 'oracle-widget'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('map_region'); ?>" name="<?php echo $this->get_field_name('map_region'); ?>">
                <option value="world" <?php selected($map_region, 'world'); ?>><?php _e('Mundo', 'oracle-widget'); ?></option>
                <option value="us_aea" <?php selected($map_region, 'us_aea'); ?>><?php _e('Estados Unidos', 'oracle-widget'); ?></option>
                <option value="europe_mill" <?php selected($map_region, 'europe_mill'); ?>><?php _e('Europa', 'oracle-widget'); ?></option>
                <option value="es_mill" <?php selected($map_region, 'es_mill'); ?>><?php _e('España', 'oracle-widget'); ?></option>
                <option value="mx_mill" <?php selected($map_region, 'mx_mill'); ?>><?php _e('México', 'oracle-widget'); ?></option>
                <option value="ar_mill" <?php selected($map_region, 'ar_mill'); ?>><?php _e('Argentina', 'oracle-widget'); ?></option>
                <option value="br_mill" <?php selected($map_region, 'br_mill'); ?>><?php _e('Brasil', 'oracle-widget'); ?></option>
                <option value="co_mill" <?php selected($map_region, 'co_mill'); ?>><?php _e('Colombia', 'oracle-widget'); ?></option>
                <option value="ve_mill" <?php selected($map_region, 've_mill'); ?>><?php _e('Venezuela', 'oracle-widget'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Límite de Filas:', 'oracle-widget'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" name="<?php echo $this->get_field_name('limit'); ?>" type="number" value="<?php echo esc_attr($limit); ?>" min="1" max="100">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('order_by'); ?>"><?php _e('Ordenar Por:', 'oracle-widget'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('order_by'); ?>" name="<?php echo $this->get_field_name('order_by'); ?>" type="text" value="<?php echo esc_attr($order_by); ?>" placeholder="<?php _e('columna1, columna2 DESC, columna3 ASC', 'oracle-widget'); ?>">
            <small><?php _e('Columnas separadas por coma. Use DESC o ASC para orden descendente o ascendente.', 'oracle-widget'); ?></small>
        </p>
        
        <?php
    }
    
    /**
     * Save widget settings
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['connection_id'] = (!empty($new_instance['connection_id'])) ? strip_tags($new_instance['connection_id']) : '';
        $instance['query_id'] = (!empty($new_instance['query_id'])) ? strip_tags($new_instance['query_id']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 10;
        $instance['order_by'] = (!empty($new_instance['order_by'])) ? strip_tags($new_instance['order_by']) : '';
        $instance['display_type'] = (!empty($new_instance['display_type'])) ? strip_tags($new_instance['display_type']) : 'table';
        $instance['map_region'] = (!empty($new_instance['map_region'])) ? strip_tags($new_instance['map_region']) : 'world';
        
        return $instance;
    }
    
    /**
     * Get data from Oracle database (now public for shortcode access)
     */
    public function get_oracle_data($instance) {
        // Check if OCI8 extension is available
        if (!extension_loaded('oci8')) {
            return '<p class="error">' . __('La extensión Oracle OCI8 no está instalada', 'oracle-widget') . '</p>';
        }
        
        // Get connection and query data
        $connection_id = $instance['connection_id'] ?? '';
        $query_id = $instance['query_id'] ?? '';
        $limit = $instance['limit'] ?? 10;
        $order_by = $instance['order_by'] ?? '';
        $display_type = $instance['display_type'] ?? 'table';
        $map_region = $instance['map_region'] ?? 'world';
        
        if (empty($connection_id) || empty($query_id)) {
            return '<p class="error">' . __('Por favor configure la conexión de base de datos y la consulta', 'oracle-widget') . '</p>';
        }
        
        $connections = get_option('oracle_widget_connections', array());
        $queries = get_option('oracle_widget_queries', array());
        
        // Debug information (only for administrators)
        if (current_user_can('manage_options')) {
            $debug_info = '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; font-size: 12px;">';
            $debug_info .= '<strong>Información de Depuración:</strong><br>';
            $debug_info .= 'Connection ID: ' . esc_html($connection_id) . '<br>';
            $debug_info .= 'Query ID: ' . esc_html($query_id) . '<br>';
            $debug_info .= 'Display Type: ' . esc_html($display_type) . '<br>';
            $debug_info .= 'Map Region: ' . esc_html($map_region) . '<br>';
            $debug_info .= 'Order By: ' . esc_html($order_by) . '<br>';
            $debug_info .= 'Available Connections: ' . implode(', ', array_keys($connections)) . '<br>';
            $debug_info .= 'Available Queries: ' . implode(', ', array_keys($queries)) . '<br>';
            $debug_info .= '</div>';
        } else {
            $debug_info = '';
        }
        
        if (!isset($connections[$connection_id])) {
            return '<p class="error">' . __('Conexión no encontrada: ', 'oracle-widget') . esc_html($connection_id) . '</p>' . $debug_info;
        }
        
        if (!isset($queries[$query_id])) {
            return '<p class="error">' . __('Consulta no encontrada: ', 'oracle-widget') . esc_html($query_id) . '</p>' . $debug_info;
        }
        
        $connection = $connections[$connection_id];
        $query_data = $queries[$query_id];
        
        $host = $connection['host'];
        $port = $connection['port'];
        $service_name = $connection['service_name'];
        $username = $connection['username'];
        $password = base64_decode($connection['password']);
        $query = $query_data['query'];
        
        try {
            // Validate connection parameters
            if (empty($host) || empty($port) || empty($service_name) || empty($username) || empty($password)) {
                return '<p class="error">' . __('Parámetros de conexión incompletos', 'oracle-widget') . '</p>';
            }
            
            // Create connection string with timeout
            $connection_string = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$service_name}))(CONNECT_TIMEOUT=10)(RETRY_COUNT=3))";
            
            // Connect to Oracle with error suppression to handle it properly
            $db_connection = @oci_connect($username, $password, $connection_string);
            
            if (!$db_connection) {
                $error = oci_error();
                $error_message = isset($error['message']) ? $error['message'] : __('Error de conexión desconocido', 'oracle-widget');
                
                // Log error for debugging (only for admins)
                if (current_user_can('manage_options')) {
                    error_log('Oracle Widget Connection Error: ' . $error_message);
                }
                
                return '<p class="error">' . __('Error de conexión a la base de datos: ', 'oracle-widget') . esc_html($error_message) . '</p>';
            }
            
            // Validate limit parameter
            $limit = intval($limit);
            if ($limit < 1 || $limit > 1000) {
                $limit = 10; // Default safe limit
            }
            
            // Replace :limit placeholder
            $query = str_replace(':limit', $limit, $query);
            
            // Add ORDER BY clause if specified
            if (!empty($order_by)) {
                // Sanitize ORDER BY clause to prevent SQL injection
                $order_by = $this->sanitize_order_by($order_by);
                if ($order_by) {
                    // Check if query already has ORDER BY
                    if (stripos($query, 'ORDER BY') === false) {
                        $query .= ' ORDER BY ' . $order_by;
                    } else {
                        // If ORDER BY already exists, replace it or append
                        $query = preg_replace('/\s+ORDER\s+BY\s+.*$/i', '', $query);
                        $query .= ' ORDER BY ' . $order_by;
                    }
                }
            }
            
            // Prepare query with error handling
            $statement = @oci_parse($db_connection, $query);
            
            if (!$statement) {
                $error = oci_error($db_connection);
                $error_message = isset($error['message']) ? $error['message'] : __('Error de preparación desconocido', 'oracle-widget');
                
                if (current_user_can('manage_options')) {
                    error_log('Oracle Widget Parse Error: ' . $error_message . ' Query: ' . $query);
                }
                
                oci_close($db_connection);
                return '<p class="error">' . __('Error en la preparación de la consulta: ', 'oracle-widget') . esc_html($error_message) . '</p>';
            }
            
            // Execute query with timeout
            $result = @oci_execute($statement, OCI_DEFAULT);
            
            if (!$result) {
                $error = oci_error($statement);
                $error_message = isset($error['message']) ? $error['message'] : __('Error de ejecución desconocido', 'oracle-widget');
                
                if (current_user_can('manage_options')) {
                    error_log('Oracle Widget Execute Error: ' . $error_message . ' Query: ' . $query);
                }
                
                oci_free_statement($statement);
                oci_close($db_connection);
                return '<p class="error">' . __('Error en la ejecución de la consulta: ', 'oracle-widget') . esc_html($error_message) . '</p>';
            }
            
            // Get column names with error handling
            $columns = array();
            $num_columns = oci_num_fields($statement);
            
            if ($num_columns === false) {
                oci_free_statement($statement);
                oci_close($db_connection);
                return '<p class="error">' . __('Error al obtener información de las columnas', 'oracle-widget') . '</p>';
            }
            
            for ($i = 1; $i <= $num_columns; $i++) {
                $column_name = oci_field_name($statement, $i);
                if ($column_name !== false) {
                    $columns[] = $column_name;
                }
            }
            
            // Fetch data with memory limit protection
            $data = array();
            $row_count = 0;
            $max_rows = min($limit, 1000); // Enforce maximum rows to prevent memory issues
            
            while (($row = oci_fetch_assoc($statement)) && $row_count < $max_rows) {
                // Sanitize data for output
                $sanitized_row = array();
                foreach ($row as $key => $value) {
                    $sanitized_row[$key] = is_null($value) ? '' : (string)$value;
                }
                $data[] = $sanitized_row;
                $row_count++;
                
                // Check memory usage periodically
                if ($row_count % 100 === 0 && memory_get_usage() > (128 * 1024 * 1024)) { // 128MB limit
                    break;
                }
            }
            
            // Clean up resources
            oci_free_statement($statement);
            oci_close($db_connection);
            
            // Check if we have any data
            if (empty($data)) {
                return '<p class="info">' . __('La consulta no devolvió resultados', 'oracle-widget') . '</p>';
            }
            
                         // Add additional debug info for column detection
             if (current_user_can('manage_options')) {
                 $debug_info .= '<div style="background: #e8f4fd; padding: 10px; margin: 10px 0; border: 1px solid #007cba; font-size: 12px;">';
                 $debug_info .= '<strong>Debug - Datos de la consulta:</strong><br>';
                 $debug_info .= 'Número de columnas: ' . count($columns) . '<br>';
                 $debug_info .= 'Número de filas: ' . count($data) . '<br>';
                 $debug_info .= 'Columnas disponibles: ' . implode(', ', $columns) . '<br>';
                 if (!empty($data)) {
                     $debug_info .= 'Primera fila de datos: ' . implode(', ', array_values($data[0])) . '<br>';
                 }
                 $debug_info .= '</div>';
             }
             
             // Generate output based on display type
             if ($display_type === 'map') {
                 return $this->generate_map_output($data, $columns, $map_region) . $debug_info;
             } elseif ($display_type === 'both') {
                 $table_output = $this->generate_table_output($data, $columns);
                 $map_output = $this->generate_map_output($data, $columns, $map_region);
                 
                 // Check if map generation failed by looking for the specific error message
                 if (strpos($map_output, 'Para mostrar en mapa, la consulta debe incluir las columnas: LATITUDE, LONGITUDE, NAME') !== false) {
                     return $table_output . '<p class="error">' . __('Nota: El mapa no se pudo mostrar debido a columnas faltantes (LATITUDE, LONGITUDE, NAME)', 'oracle-widget') . '</p>' . $debug_info;
                 }
                 
                 return $table_output . '<div class="table-map-container"><h3>' . __('Visualización en Mapa', 'oracle-widget') . '</h3><div class="map-section">' . $map_output . '</div></div>' . $debug_info;
             } else {
                 return $this->generate_table_output($data, $columns) . $debug_info;
             }
            
        } catch (Exception $e) {
            // Log the full exception for debugging
            if (current_user_can('manage_options')) {
                error_log('Oracle Widget Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
            
            // Return user-friendly error message
            return '<p class="error">' . __('Error inesperado: ', 'oracle-widget') . esc_html($e->getMessage()) . '</p>' . $debug_info;
        } catch (Error $e) {
            // Handle PHP 7+ Fatal Errors
            if (current_user_can('manage_options')) {
                error_log('Oracle Widget Fatal Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
            
            return '<p class="error">' . __('Error fatal: ', 'oracle-widget') . esc_html($e->getMessage()) . '</p>' . $debug_info;
        }
    }
    
    /**
     * Generate table output
     */
    private function generate_table_output($data, $columns) {
        $output = '<div class="table-section"><table class="oracle-widget-table">';
        
        // Table header
        $output .= '<thead><tr>';
        foreach ($columns as $column) {
            $output .= '<th>' . esc_html($column) . '</th>';
        }
        $output .= '</tr></thead>';
        
        // Table body
        $output .= '<tbody>';
        foreach ($data as $row) {
            $output .= '<tr>';
            foreach ($row as $value) {
                $output .= '<td>' . esc_html($value) . '</td>';
            }
            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table></div>';
        
        return $output;
    }
    
    /**
     * Generate map output
     */
    private function generate_map_output($data, $columns, $map_region) {
        // Find the actual column names (case-insensitive search)
        $lat_col = null;
        $lng_col = null;
        $name_col = null;
        
        foreach ($columns as $col) {
            $col_upper = strtoupper($col);
            if ($col_upper === 'LATITUDE') {
                $lat_col = $col;
            } elseif ($col_upper === 'LONGITUDE') {
                $lng_col = $col;
            } elseif ($col_upper === 'NAME') {
                $name_col = $col;
            }
        }
        
        // Check if required columns exist
        if (!$lat_col || !$lng_col || !$name_col) {
            $debug_info = '';
            if (current_user_can('manage_options')) {
                $debug_info = '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; font-size: 12px;">';
                $debug_info .= '<strong>Debug - Columnas encontradas:</strong><br>';
                $debug_info .= 'Columnas disponibles: ' . implode(', ', $columns) . '<br>';
                $debug_info .= 'LATITUDE encontrada: ' . ($lat_col ? 'SÍ (' . $lat_col . ')' : 'NO') . '<br>';
                $debug_info .= 'LONGITUDE encontrada: ' . ($lng_col ? 'SÍ (' . $lng_col . ')' : 'NO') . '<br>';
                $debug_info .= 'NAME encontrada: ' . ($name_col ? 'SÍ (' . $name_col . ')' : 'NO') . '<br>';
                $debug_info .= '<br><strong>Búsqueda detallada:</strong><br>';
                foreach ($columns as $col) {
                    $debug_info .= 'Columna: "' . $col . '" → Uppercase: "' . strtoupper($col) . '"<br>';
                }
                $debug_info .= '</div>';
            }
            return '<p class="error">' . __('Para mostrar en mapa, la consulta debe incluir las columnas: LATITUDE, LONGITUDE, NAME', 'oracle-widget') . '</p>' . $debug_info;
        }
        
        // Add debug info for successful column detection
        $debug_info = '';
        if (current_user_can('manage_options')) {
            $debug_info = '<div style="background: #d4edda; padding: 10px; margin: 10px 0; border: 1px solid #c3e6cb; font-size: 12px;">';
            $debug_info .= '<strong>Debug - Columnas detectadas correctamente:</strong><br>';
            $debug_info .= 'LATITUDE: ' . $lat_col . '<br>';
            $debug_info .= 'LONGITUDE: ' . $lng_col . '<br>';
            $debug_info .= 'NAME: ' . $name_col . '<br>';
            $debug_info .= 'Número de filas a procesar: ' . count($data) . '<br>';
            $debug_info .= '</div>';
        }
        
        // Generate unique ID for this map
        $map_id = 'oracle-map-' . uniqid();
        
        // Prepare markers data
        $markers = array();
        $valid_markers = 0;
        $invalid_markers = 0;
        
        foreach ($data as $row) {
            $lat = floatval($row[$lat_col]);
            $lng = floatval($row[$lng_col]);
            $name = esc_js($row[$name_col]);
            
            if ($lat != 0 && $lng != 0) {
                $markers[] = array(
                    'latLng' => array($lat, $lng),
                    'name' => $name
                );
                $valid_markers++;
            } else {
                $invalid_markers++;
            }
        }
        
        // Add debug info for markers processing
        if (current_user_can('manage_options')) {
            $debug_info .= '<div style="background: #fff3cd; padding: 10px; margin: 10px 0; border: 1px solid #ffeaa7; font-size: 12px;">';
            $debug_info .= '<strong>Debug - Procesamiento de marcadores:</strong><br>';
            $debug_info .= 'Marcadores válidos: ' . $valid_markers . '<br>';
            $debug_info .= 'Marcadores inválidos: ' . $invalid_markers . '<br>';
            $debug_info .= 'Total de marcadores: ' . count($markers) . '<br>';
            if (!empty($markers)) {
                $debug_info .= 'Primer marcador: Lat=' . $markers[0]['latLng'][0] . ', Lng=' . $markers[0]['latLng'][1] . ', Name=' . $markers[0]['name'] . '<br>';
            }
            $debug_info .= '</div>';
        }
        
        if (empty($markers)) {
            return '<p class="error">' . __('No se encontraron coordenadas válidas para mostrar en el mapa', 'oracle-widget') . '</p>' . $debug_info;
        }
        
        // Ensure the specific map region script is loaded
        $map_script_handle = 'jvectormap-' . $map_region;
        if (!wp_script_is($map_script_handle, 'enqueued')) {
            wp_enqueue_script($map_script_handle, 'https://unpkg.com/jvectormap@2.0.3/jquery-jvectormap-' . $map_region . '-mill.js', array('jvectormap'), '2.0.3', true);
        }
        
        $output = '<div id="' . $map_id . '" style="width: 100%; height: 400px; border: 2px solid #ccc; background-color: #f0f0f0;">
            <div style="padding: 20px; text-align: center; color: #666;">
                <strong>Mapa cargando...</strong><br>
                ID del contenedor: ' . $map_id . '<br>
                Región: ' . $map_region . '<br>
                Marcadores: ' . count($markers) . '
            </div>
        </div>';
        
        // Add JavaScript for map initialization with error handling
        $output .= '<script type="text/javascript">
        jQuery(document).ready(function($) {
            // Wait for jVectorMap to be available
            function initMap() {
                console.log("initMap called - checking jQuery and jVectorMap availability...");
                
                // Check if jQuery is available
                if (typeof $ === "undefined" || typeof $.fn === "undefined") {
                    console.log("jQuery not available yet, retrying in 100ms...");
                    setTimeout(initMap, 100);
                    return;
                }
                
                console.log("$.fn.vectorMap available:", typeof $.fn.vectorMap !== "undefined");
                
                if (typeof $.fn.vectorMap !== "undefined") {
                    console.log("jVectorMap is available, checking map data...");
                    console.log("Map container:", $("#' . $map_id . '"));
                    console.log("Map region:", "' . $map_region . '");
                    console.log("Markers count:", ' . count($markers) . ');
                    
                    // Map region names to actual map data keys
                    var mapRegionKey = "' . $map_region . '";
                    if (mapRegionKey === "world") {
                        mapRegionKey = "world-mill";
                    } else if (mapRegionKey === "ve_mill") {
                        mapRegionKey = "ve_mill";
                    }
                    
                    // Check if the specific map region is available
                    if (typeof $.fn.vectorMap.maps !== "undefined" && $.fn.vectorMap.maps[mapRegionKey]) {
                        console.log("Map data for region " + mapRegionKey + " is available, initializing...");
                        console.log("Available maps:", Object.keys($.fn.vectorMap.maps));
                        
                        try {
                            // Clear the loading message
                            $("#' . $map_id . '").empty();
                            
                            $("#' . $map_id . '").vectorMap({
                                map: mapRegionKey,
                                backgroundColor: "#f8f9fa",
                                zoomOnScroll: true,
                                markers: ' . json_encode($markers) . ',
                                markerStyle: {
                                    initial: {
                                        fill: "#ff6b6b",
                                        stroke: "#fff",
                                        "stroke-width": 2,
                                        "stroke-opacity": 0.8,
                                        r: 6
                                    },
                                    hover: {
                                        fill: "#ff5252",
                                        stroke: "#fff",
                                        "stroke-width": 2,
                                        "stroke-opacity": 1,
                                        r: 8
                                    }
                                },
                                onMarkerTipShow: function(e, tip, code) {
                                    tip.html(tip.html());
                                }
                            });
                            console.log("Map initialized successfully!");
                        } catch (error) {
                            console.error("jVectorMap error:", error);
                            $("#' . $map_id . '").html("<div style=\'padding: 20px; text-align: center; color: #666;\'><strong>Error:</strong> Could not initialize map. Please try refreshing the page.</div>");
                        }
                    } else {
                        console.log("Map data for region " + mapRegionKey + " not loaded yet, retrying in 200ms...");
                        console.log("Available maps:", typeof $.fn.vectorMap.maps !== "undefined" ? Object.keys($.fn.vectorMap.maps) : "undefined");
                        // Retry after a longer delay to allow map data to load
                        setTimeout(initMap, 200);
                    }
                } else {
                    console.log("$.fn.vectorMap not ready, retrying in 100ms...");
                    // Retry after a short delay
                    setTimeout(initMap, 100);
                }
            }
            
            // Start initialization
            console.log("Starting map initialization for container: #' . $map_id . '");
            console.log("Container exists:", $("#' . $map_id . '").length > 0);
            console.log("Container HTML:", $("#' . $map_id . '").html());
            
            // Listen for jvectormap:loaded event or start immediately
            $(document).on("jvectormap:loaded", function() {
                console.log("jvectormap:loaded event received, initializing map...");
                initMap();
            });
            
            // Also try to initialize immediately in case the event was already fired
            initMap();
        });
        </script>';
        
        return $output . $debug_info;
    }
    
    /**
     * Sanitize ORDER BY clause to prevent SQL injection
     */
    private function sanitize_order_by($order_by) {
        if (empty($order_by)) {
            return '';
        }
        
        // Remove any potentially dangerous characters
        $order_by = preg_replace('/[^\w\s,\(\)\.\_\-]/', '', $order_by);
        
        // Split by comma and validate each part
        $parts = explode(',', $order_by);
        $sanitized_parts = array();
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Check if it's a valid column name with optional ASC/DESC
            if (preg_match('/^[\w\.\_\-]+(\s+(ASC|DESC))?$/i', $part)) {
                $sanitized_parts[] = $part;
            }
        }
        
        return implode(', ', $sanitized_parts);
    }
}

/**
 * Enqueue jVectorMap scripts and styles
 */
function oracle_widget_enqueue_scripts() {
    global $post;
    
    // Check if we need to enqueue scripts
    $should_enqueue = false;
    
    // Always enqueue in admin
    if (is_admin()) {
        $should_enqueue = true;
    }
    
    // Check if widget is active
    if (is_active_widget(false, false, 'oracle_database_widget')) {
        $should_enqueue = true;
    }
    
    // Check for shortcode in current post
    if (is_object($post) && has_shortcode($post->post_content, 'oracle_data')) {
        $should_enqueue = true;
    }
    
    if ($should_enqueue) {
        
        // Try to enqueue jVectorMap from CDN with fallback
        $jvectormap_cdn = 'https://unpkg.com/jvectormap@2.0.3/jquery-jvectormap.min.js';
        $jvectormap_css_cdn = 'https://unpkg.com/jvectormap@2.0.3/jquery-jvectormap.css';
        
        wp_enqueue_script('jvectormap', $jvectormap_cdn, array('jquery'), '2.0.3', true);
        wp_enqueue_style('jvectormap', $jvectormap_css_cdn, array(), '2.0.3');
        
        // Only enqueue map regions that might be used
        $used_regions = oracle_widget_get_used_map_regions();
        foreach ($used_regions as $region) {
            $map_script_handle = 'jvectormap-' . $region;
            if (!wp_script_is($map_script_handle, 'enqueued')) {
                $map_url = 'https://unpkg.com/jvectormap@2.0.3/jquery-jvectormap-' . $region . '.js';
                wp_enqueue_script($map_script_handle, $map_url, array('jvectormap'), '2.0.3', true);
            }
        }
        
        // Add Venezuelan map data
        wp_add_inline_script('jvectormap', '
            // Venezuelan map definition
            if (typeof jQuery !== "undefined" && typeof jQuery.fn.vectorMap !== "undefined") {
                jQuery.fn.vectorMap("addMap", "ve_mill", ' . json_encode(json_decode('{"insets": [{"width": 900, "top": 0, "height": 1006.432148295754, "bbox": [{"y": -1763114.2057327146, "x": -8174009.062545}, {"y": -72319.12180521358, "x": -6662018.832392322}], "left": 0}], "paths": {"VE-": {"path": "M680.48,291.49l0.44,-0.15l-0.02,0.52l-0.41,-0.36ZM594.09,359.8l-0.11,-0.2l0.7,-0.47l0.07,0.39l-0.66,0.29ZM586.29,366.55l0.02,0.02l-0.05,0.05l0.0,-0.01l0.02,-0.05ZM572.57,364.87l0.59,-0.75l0.61,0.05l-0.38,1.25l-0.81,-0.55ZM500.82,366.76l0.55,0.06l1.77,0.73l-1.59,-0.2l-0.73,-0.59ZM503.8,367.82l1.09,0.52l0.11,0.1l-0.41,-0.06l-0.78,-0.56Z", "name": ""}, "VE-L": {"path": "M144.5,438.56l0.69,-2.6l1.22,-0.94l2.27,-2.7l2.27,-0.41l2.89,-1.24l1.13,-1.73l1.49,-0.86l3.64,0.08l3.53,3.07l1.78,7.43l1.88,3.56l0.66,0.77l4.24,2.44l0.95,1.72l1.01,0.44l1.04,-0.15l0.86,0.54l1.09,2.91l0.84,1.23l0.71,0.54l1.39,-0.09l2.29,-1.03l2.46,-2.04l1.48,-0.51l0.84,1.53l1.37,0.55l-0.07,2.18l0.45,0.97l-1.3,1.88l-0.81,3.71l-0.49,0.68l-2.45,0.65l-2.8,1.43l-2.01,0.15l-0.72,0.67l-1.18,2.66l-0.69,0.55l-0.49,-0.09l-0.77,-1.13l-1.59,0.18l-4.61,3.77l-4.7,6.64l-0.91,-0.37l-0.72,0.25l-2.46,2.43l-0.53,1.48l0.48,2.29l-0.71,1.97l-0.67,0.9l-2.42,1.82l-0.54,1.01l-0.24,1.8l-2.14,2.17l-0.47,1.08l0.15,2.68l0.68,2.14l2.25,1.98l1.26,3.81l-0.2,1.32l-2.15,3.32l-0.79,2.46l-1.96,2.75l-5.11,4.53l-2.63,1.57l-3.03,3.51l-2.37,1.37l-9.28,6.66l-2.2,2.23l-0.15,1.9l0.52,2.08l-0.37,1.06l-0.86,0.38l-0.82,-2.41l-2.31,-2.01l1.76,-4.71l-0.09,-3.78l-0.32,-1.84l-0.65,-1.14l0.13,-0.74l1.33,-1.84l0.11,-1.39l-0.68,-1.24l-5.7,-3.66l-3.24,-0.22l-1.44,0.29l-1.43,-0.32l-1.91,0.83l-2.06,-1.62l-3.37,-5.06l-1.77,-1.36l-2.15,-0.54l-2.77,0.3l0.12,-1.55l0.58,-1.77l2.82,-3.94l1.01,-0.53l0.73,-0.87l0.51,-2.16l-1.18,-3.54l3.32,-4.15l0.51,-1.72l-0.34,-1.46l-1.4,-1.92l-0.27,-2.29l-0.69,-1.27l-1.08,-0.68l-7.42,-1.3l2.95,-1.15l2.34,-1.99l1.41,-0.3l0.89,0.1l0.75,0.66l0.07,0.82l-0.52,1.4l0.42,0.59l3.29,-0.7l1.68,0.89l0.59,-0.03l0.61,-0.6l1.05,-3.58l2.02,-2.72l4.13,-3.54l17.61,-12.94l3.21,-3.16l3.54,-2.11l0.79,-0.12l1.4,1.69l0.43,1.52l0.68,0.44l1.25,-0.41l1.15,-0.88l0.73,-1.77l0.39,-5.47l-0.44,-1.16l-4.53,-3.85Z", "name": "Mérida"}}', true)) . ');
            }
        ');
        
        // Add debug info to console and ensure proper loading
        wp_add_inline_script('jvectormap', '
            jQuery(document).ready(function($) {
                console.log("jVectorMap 2.0.3 scripts enqueued successfully");
                console.log("jQuery version:", jQuery.fn.jquery);
                console.log("$.fn.vectorMap available:", typeof $.fn.vectorMap !== "undefined");
                console.log("jvm global object available:", typeof jvm !== "undefined");
                
                // Try to manually define jvm if it doesn\'t exist
                if (typeof jvm === "undefined" && typeof $.fn.vectorMap !== "undefined") {
                    console.log("Attempting to manually define jvm global object...");
                    window.jvm = {};
                    console.log("jvm manually defined:", typeof jvm !== "undefined");
                }
                
                // Check if map data is available
                if (typeof $.fn.vectorMap !== "undefined") {
                    console.log("Checking available map regions...");
                    if (typeof $.fn.vectorMap.maps !== "undefined") {
                        console.log("Available maps:", Object.keys($.fn.vectorMap.maps));
                    } else {
                        console.log("$.fn.vectorMap.maps is not defined");
                    }
                }
                
                // Function to check if all required maps are loaded
                function checkMapsLoaded() {
                    if (typeof $.fn.vectorMap !== "undefined" && typeof $.fn.vectorMap.maps !== "undefined") {
                        var requiredMaps = ["world-mill", "us-aea", "europe-mill", "south-america-mill", "ve_mill"];
                        var loadedMaps = Object.keys($.fn.vectorMap.maps);
                        var allLoaded = true;
                        
                        for (var i = 0; i < requiredMaps.length; i++) {
                            if (loadedMaps.indexOf(requiredMaps[i]) === -1) {
                                allLoaded = false;
                                break;
                            }
                        }
                        
                        if (allLoaded) {
                            console.log("All required maps loaded successfully:", loadedMaps);
                            $(document).trigger("jvectormap:loaded");
                            return true;
                        }
                    }
                    return false;
                }
                
                // Check immediately and then retry
                if (!checkMapsLoaded()) {
                    var checkInterval = setInterval(function() {
                        if (checkMapsLoaded()) {
                            clearInterval(checkInterval);
                        }
                    }, 100);
                    
                    // Timeout after 10 seconds
                    setTimeout(function() {
                        clearInterval(checkInterval);
                        console.log("Timeout reached, triggering jvectormap:loaded anyway");
                        $(document).trigger("jvectormap:loaded");
                    }, 10000);
                }
                
                // Add Venezuelan map data
                if (typeof $.fn.vectorMap !== "undefined") {
                    console.log("Loading Venezuelan map data...");
                    // Note: This is a simplified version. For production, include all Venezuelan states
                    $.fn.vectorMap("addMap", "ve_mill", {
                        "insets": [{"width": 900, "top": 0, "height": 1006.432148295754, "bbox": [{"y": -1763114.2057327146, "x": -8174009.062545}, {"y": -72319.12180521358, "x": -6662018.832392322}], "left": 0}],
                        "paths": {
                            "VE-L": {"path": "M144.5,438.56l0.69,-2.6l1.22,-0.94l2.27,-2.7l2.27,-0.41l2.89,-1.24l1.13,-1.73l1.49,-0.86l3.64,0.08l3.53,3.07l1.78,7.43l1.88,3.56l0.66,0.77l4.24,2.44l0.95,1.72l1.01,0.44l1.04,-0.15l0.86,0.54l1.09,2.91l0.84,1.23l0.71,0.54l1.39,-0.09l2.29,-1.03l2.46,-2.04l1.48,-0.51l0.84,1.53l1.37,0.55l-0.07,2.18l0.45,0.97l-1.3,1.88l-0.81,3.71l-0.49,0.68l-2.45,0.65l-2.8,1.43l-2.01,0.15l-0.72,0.67l-1.18,2.66l-0.69,0.55l-0.49,-0.09l-0.77,-1.13l-1.59,0.18l-4.61,3.77l-4.7,6.64l-0.91,-0.37l-0.72,0.25l-2.46,2.43l-0.53,1.48l0.48,2.29l-0.71,1.97l-0.67,0.9l-2.42,1.82l-0.54,1.01l-0.24,1.8l-2.14,2.17l-0.47,1.08l0.15,2.68l0.68,2.14l2.25,1.98l1.26,3.81l-0.2,1.32l-2.15,3.32l-0.79,2.46l-1.96,2.75l-5.11,4.53l-2.63,1.57l-3.03,3.51l-2.37,1.37l-9.28,6.66l-2.2,2.23l-0.15,1.9l0.52,2.08l-0.37,1.06l-0.86,0.38l-0.82,-2.41l-2.31,-2.01l1.76,-4.71l-0.09,-3.78l-0.32,-1.84l-0.65,-1.14l0.13,-0.74l1.33,-1.84l0.11,-1.39l-0.68,-1.24l-5.7,-3.66l-3.24,-0.22l-1.44,0.29l-1.43,-0.32l-1.91,0.83l-2.06,-1.62l-3.37,-5.06l-1.77,-1.36l-2.15,-0.54l-2.77,0.3l0.12,-1.55l0.58,-1.77l2.82,-3.94l1.01,-0.53l0.73,-0.87l0.51,-2.16l-1.18,-3.54l3.32,-4.15l0.51,-1.72l-0.34,-1.46l-1.4,-1.92l-0.27,-2.29l-0.69,-1.27l-1.08,-0.68l-7.42,-1.3l2.95,-1.15l2.34,-1.99l1.41,-0.3l0.89,0.1l0.75,0.66l0.07,0.82l-0.52,1.4l0.42,0.59l3.29,-0.7l1.68,0.89l0.59,-0.03l0.61,-0.6l1.05,-3.58l2.02,-2.72l4.13,-3.54l17.61,-12.94l3.21,-3.16l3.54,-2.11l0.79,-0.12l1.4,1.69l0.43,1.52l0.68,0.44l1.25,-0.41l1.15,-0.88l0.73,-1.77l0.39,-5.47l-0.44,-1.16l-4.53,-3.85Z", "name": "Mérida"},
                            "VE-M": {"path": "M451.69,347.24l4.17,0.26l2.83,1.11l1.33,-0.35l0.77,0.53l5.65,-0.54l2.23,0.91l0.84,-0.55l0.22,-0.55l0.26,-3.4l-0.33,-2.36l0.98,-0.89l1.11,-0.32l1.45,0.22l4.09,2.09l4.28,1.01l1.48,1.21l3.29,0.41l-1.54,1.21l-1.61,0.51l-1.19,-0.14l-0.44,0.43l-0.0,2.54l0.34,0.73l2.08,2.14l5.56,4.56l2.13,1.3l1.28,1.87l3.29,1.97l-1.81,-0.72l-0.99,0.96l-0.02,0.51l1.4,1.83l1.98,-0.21l1.53,0.91l2.88,2.55l1.82,0.67l5.72,0.39l0.39,-0.28l-0.16,-0.45l-1.97,-1.28l-6.59,-2.81l-0.44,-0.59l17.72,7.71l9.92,2.77l-0.72,2.74l-0.6,0.92l-2.45,1.54l-1.62,0.52l-2.91,2.56l-3.79,1.29l-1.39,1.23l-0.87,-0.5l-1.06,-0.02l-2.24,1.77l-3.12,0.92l-3.03,0.04l-2.21,-0.87l-2.43,-0.35l-3.4,1.27l-3.75,0.6l-6.0,-1.83l-3.3,0.58l-1.53,-1.26l-3.72,1.19l-3.51,-1.91l-3.63,-0.18l-3.43,-1.79l-2.86,-0.85l-2.1,0.61l-1.92,1.92l-5.04,0.89l-5.57,0.13l-3.44,-0.44l-2.44,-1.17l-3.04,-0.04l-1.48,-1.12l-1.44,-0.33l-2.12,-0.17l-3.62,1.03l-2.03,-0.74l-1.18,-0.06l-2.21,-3.04l-1.15,-0.93l-1.89,-0.23l-2.4,1.38l-4.32,-3.03l-1.35,-2.78l-0.09,-0.97l1.09,-2.2l0.58,-4.81l-1.42,-1.3l-2.68,-1.1l-1.09,-0.97l-0.04,-1.38l0.81,-0.98l0.02,-0.46l-1.12,-1.05l-1.66,-2.67l3.52,-1.28l0.52,0.62l4.91,1.88l3.1,0.56l1.59,-0.17l4.15,-1.73l2.94,0.34l0.47,-0.32l1.03,-4.21l1.83,-2.17l-0.01,-0.5l-1.12,-1.82l0.25,-0.14l1.55,0.59l2.81,-0.44l4.81,-0.09l1.13,0.43l6.75,0.07l1.76,-1.05Z", "name": "Miranda"},
                            "VE-A": {"path": "M414.01,355.24l2.07,-1.56l1.39,0.17l1.19,-0.9l1.69,-0.31l0.17,-3.28l2.39,-1.16l0.14,-0.5l-0.33,-0.51l0.62,-0.92l1.12,0.77l2.42,0.9l2.58,-0.5l2.37,0.65l1.14,1.86l-1.76,2.0l-1.04,4.06l-2.85,-0.27l-4.25,1.76l-1.35,0.13l-2.84,-0.52l-4.89,-1.88Z", "name": "Distrito Capital"}
                        },
                        "height": 1006.432148295754,
                        "projection": {"type": "mill", "centralMeridian": 0.0},
                        "width": 900.0
                    });
                    console.log("Venezuelan map (ve_mill) loaded successfully with sample states");
                }
            });
        ', 'after');
    }
}
add_action('wp_enqueue_scripts', 'oracle_widget_enqueue_scripts');

/**
 * Get map regions that are currently being used
 */
function oracle_widget_get_used_map_regions() {
    global $post;
    
    $used_regions = array();
    
    // Check active widgets for map regions
    $widget_instances = get_option('widget_oracle_database_widget', array());
    foreach ($widget_instances as $instance) {
        if (isset($instance['map_region']) && !empty($instance['map_region'])) {
            $region = $instance['map_region'];
                         // Convert region names to actual map file names
             if ($region === 'world') {
                 $used_regions[] = 'world-mill';
             } elseif ($region === 'us_aea') {
                 $used_regions[] = 'us-aea';
             } elseif ($region === 'europe_mill') {
                 $used_regions[] = 'europe-mill';
             } elseif ($region === 've_mill') {
                 $used_regions[] = 've-mill';
             } else {
                 $used_regions[] = $region;
             }
        }
    }
    
    // Check current post for shortcodes with map_region attribute
    if (is_object($post) && has_shortcode($post->post_content, 'oracle_data')) {
        $pattern = get_shortcode_regex(array('oracle_data'));
        if (preg_match_all('/' . $pattern . '/s', $post->post_content, $matches)) {
            foreach ($matches[3] as $attrs) {
                $atts = shortcode_parse_atts($attrs);
                                 if (isset($atts['map_region'])) {
                     $region = $atts['map_region'];
                     if ($region === 'world') {
                         $used_regions[] = 'world-mill';
                     } elseif ($region === 'us_aea') {
                         $used_regions[] = 'us-aea';
                     } elseif ($region === 'europe_mill') {
                         $used_regions[] = 'europe-mill';
                     } elseif ($region === 've_mill') {
                         $used_regions[] = 've-mill';
                     } else {
                         $used_regions[] = $region;
                     }
                 }
            }
        }
    }
    
    // Default to world map if no specific regions found
    if (empty($used_regions)) {
        $used_regions[] = 'world-mill';
    }
    
    return array_unique($used_regions);
}

/**
 * Register the widget
 */
function register_oracle_database_widget() {
    register_widget('Oracle_Database_Widget');
}
add_action('widgets_init', 'register_oracle_database_widget');

/**
 * Shortcode for Elementor and other page builders
 * Usage: [oracle_data connection="connection_id" query="query_id" limit="10" order_by="column1, column2 DESC" display_type="both" map_region="world"]
 */
function oracle_data_shortcode($atts) {
    $atts = shortcode_atts(array(
        'connection' => '',
        'query' => '',
        'limit' => '10',
        'order_by' => '',
        'display_type' => 'table',
        'map_region' => 'world'
    ), $atts, 'oracle_data');
    
    // Create instance array for the widget method
    $instance = array(
        'connection_id' => $atts['connection'],
        'query_id' => $atts['query'],
        'limit' => $atts['limit'],
        'order_by' => $atts['order_by'],
        'display_type' => $atts['display_type'],
        'map_region' => $atts['map_region']
    );
    
    // Create widget instance and get data
    $widget = new Oracle_Database_Widget();
    $content = $widget->get_oracle_data($instance);
    
    if ($content) {
        return '<div class="oracle-widget-content">' . $content . '</div>';
    } else {
        return '<p>' . __('No hay datos disponibles', 'oracle-widget') . '</p>';
    }
}
add_shortcode('oracle_data', 'oracle_data_shortcode');

/**
 * Admin Menu Setup
 */
function oracle_widget_admin_menu() {
    add_options_page(
        __('Configuración del Widget Oracle', 'oracle-widget'),
        __('Widget Oracle', 'oracle-widget'),
        'manage_options',
        'oracle-widget-settings',
        'oracle_widget_settings_page'
    );
}
add_action('admin_menu', 'oracle_widget_admin_menu');

/**
 * Settings Page
 */
function oracle_widget_settings_page() {
    if (isset($_POST['submit_connection'])) {
        oracle_widget_save_connection();
    } elseif (isset($_POST['submit_query'])) {
        oracle_widget_save_query();
    } elseif (isset($_POST['update_connection'])) {
        oracle_widget_update_connection();
    } elseif (isset($_POST['update_query'])) {
        oracle_widget_update_query();
    } elseif (isset($_GET['delete_connection'])) {
        oracle_widget_delete_connection($_GET['delete_connection']);
    } elseif (isset($_GET['delete_query'])) {
        oracle_widget_delete_query($_GET['delete_query']);
    }
    
    $connections = get_option('oracle_widget_connections', array());
    $queries = get_option('oracle_widget_queries', array());
    
    // Check if we're editing
    $editing_connection = isset($_GET['edit_connection']) ? $_GET['edit_connection'] : '';
    $editing_query = isset($_GET['edit_query']) ? $_GET['edit_query'] : '';
    ?>
    <div class="wrap">
        <h1><?php _e('Configuración del Widget Oracle', 'oracle-widget'); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="#connections" class="nav-tab nav-tab-active"><?php _e('Conexiones de Base de Datos', 'oracle-widget'); ?></a>
            <a href="#queries" class="nav-tab"><?php _e('Consultas SQL', 'oracle-widget'); ?></a>
        </h2>
        
        <div id="connections" class="tab-content">
            <?php if ($editing_connection && isset($connections[$editing_connection])): ?>
                <h3><?php _e('Editar Conexión', 'oracle-widget'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('oracle_widget_connection'); ?>
                    <input type="hidden" name="connection_id" value="<?php echo esc_attr($editing_connection); ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Nombre de Conexión', 'oracle-widget'); ?></th>
                            <td><input type="text" name="connection_name" class="regular-text" value="<?php echo esc_attr($connections[$editing_connection]['name']); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Servidor', 'oracle-widget'); ?></th>
                            <td><input type="text" name="host" value="<?php echo esc_attr($connections[$editing_connection]['host']); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Puerto', 'oracle-widget'); ?></th>
                            <td><input type="text" name="port" value="<?php echo esc_attr($connections[$editing_connection]['port']); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Nombre del Servicio', 'oracle-widget'); ?></th>
                            <td><input type="text" name="service_name" value="<?php echo esc_attr($connections[$editing_connection]['service_name']); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Usuario', 'oracle-widget'); ?></th>
                            <td><input type="text" name="username" value="<?php echo esc_attr($connections[$editing_connection]['username']); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Contraseña', 'oracle-widget'); ?></th>
                            <td>
                                <input type="password" name="password" class="regular-text" placeholder="<?php _e('Dejar en blanco para mantener la contraseña actual', 'oracle-widget'); ?>">
                                <p class="description"><?php _e('Dejar en blanco para mantener la contraseña actual', 'oracle-widget'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="update_connection" class="button-primary" value="<?php _e('Actualizar Conexión', 'oracle-widget'); ?>">
                        <input type="button" id="test-connection-edit-btn" class="button" value="<?php esc_attr_e('Probar Conexión', 'oracle-widget'); ?>">
                        <a href="?page=oracle-widget-settings" class="button"><?php _e('Cancelar', 'oracle-widget'); ?></a>
                        <span id="test-connection-edit-result" style="margin-left: 15px;"></span>
                    </p>
                    <p class="description">
                        <strong><?php esc_html_e('Consejo:', 'oracle-widget'); ?></strong> <?php esc_html_e('Ingrese la contraseña y use "Probar Conexión" para verificar los cambios antes de actualizar.', 'oracle-widget'); ?>
                    </p>
                </form>
            <?php else: ?>
                <h3><?php _e('Agregar Nueva Conexión', 'oracle-widget'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('oracle_widget_connection'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Nombre de Conexión', 'oracle-widget'); ?></th>
                            <td><input type="text" name="connection_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Servidor', 'oracle-widget'); ?></th>
                            <td><input type="text" name="host" value="localhost" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Puerto', 'oracle-widget'); ?></th>
                            <td><input type="text" name="port" value="1521" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Nombre del Servicio', 'oracle-widget'); ?></th>
                            <td><input type="text" name="service_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Usuario', 'oracle-widget'); ?></th>
                            <td><input type="text" name="username" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Contraseña', 'oracle-widget'); ?></th>
                            <td><input type="password" name="password" class="regular-text" required></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit_connection" class="button-primary" value="<?php _e('Guardar Conexión', 'oracle-widget'); ?>">
                        <input type="button" id="test-connection-btn" class="button" value="<?php esc_attr_e('Probar Conexión', 'oracle-widget'); ?>">
                        <span id="test-connection-result" style="margin-left: 15px;"></span>
                    </p>
                    <p class="description">
                        <strong><?php esc_html_e('Consejo:', 'oracle-widget'); ?></strong> <?php esc_html_e('Use el botón "Probar Conexión" para verificar que los datos son correctos antes de guardar.', 'oracle-widget'); ?>
                    </p>
                </form>
            <?php endif; ?>
            
            <h3><?php _e('Conexiones Existentes', 'oracle-widget'); ?></h3>
            <?php if (empty($connections)): ?>
                <p><?php _e('Aún no hay conexiones configuradas.', 'oracle-widget'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Nombre', 'oracle-widget'); ?></th>
                            <th><?php _e('Servidor', 'oracle-widget'); ?></th>
                            <th><?php _e('Servicio', 'oracle-widget'); ?></th>
                            <th><?php _e('Usuario', 'oracle-widget'); ?></th>
                            <th><?php _e('Acciones', 'oracle-widget'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connections as $id => $connection): ?>
                            <tr>
                                <td><?php echo esc_html($connection['name']); ?></td>
                                <td><?php echo esc_html($connection['host']); ?></td>
                                <td><?php echo esc_html($connection['service_name']); ?></td>
                                <td><?php echo esc_html($connection['username']); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'oracle-widget-settings', 'edit_connection' => $id))); ?>" 
                                       class="button button-small"><?php esc_html_e('Editar', 'oracle-widget'); ?></a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'oracle-widget-settings', 'delete_connection' => $id)), 'delete_connection_' . $id)); ?>" 
                                       onclick="return confirm('<?php esc_attr_e('¿Está seguro?', 'oracle-widget'); ?>')"
                                       class="button button-small"><?php esc_html_e('Eliminar', 'oracle-widget'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div id="queries" class="tab-content" style="display:none;">
            <?php if ($editing_query && isset($queries[$editing_query])): ?>
                <h3><?php _e('Editar Consulta', 'oracle-widget'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('oracle_widget_query'); ?>
                    <input type="hidden" name="query_id" value="<?php echo esc_attr($editing_query); ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Nombre de Consulta', 'oracle-widget'); ?></th>
                            <td><input type="text" name="query_name" class="regular-text" value="<?php echo esc_attr($queries[$editing_query]['name']); ?>" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Consulta SQL', 'oracle-widget'); ?></th>
                            <td>
                                <textarea name="sql_query" rows="6" cols="50" class="large-text" required><?php echo esc_textarea($queries[$editing_query]['query']); ?></textarea>
                                <p class="description"><?php _e('Use :limit como marcador de posición para el límite de filas', 'oracle-widget'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Descripción', 'oracle-widget'); ?></th>
                            <td><input type="text" name="query_description" class="regular-text" value="<?php echo esc_attr($queries[$editing_query]['description']); ?>"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Orden Predeterminado', 'oracle-widget'); ?></th>
                            <td>
                                <input type="text" name="default_order" class="regular-text" value="<?php echo esc_attr($queries[$editing_query]['default_order'] ?? ''); ?>" placeholder="<?php _e('columna1, columna2 DESC', 'oracle-widget'); ?>">
                                <p class="description"><?php _e('Orden predeterminado para esta consulta (opcional)', 'oracle-widget'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="update_query" class="button-primary" value="<?php _e('Actualizar Consulta', 'oracle-widget'); ?>">
                        <a href="?page=oracle-widget-settings" class="button"><?php _e('Cancelar', 'oracle-widget'); ?></a>
                    </p>
                </form>
            <?php else: ?>
                <h3><?php _e('Agregar Nueva Consulta', 'oracle-widget'); ?></h3>
                <form method="post" action="">
                    <?php wp_nonce_field('oracle_widget_query'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Nombre de Consulta', 'oracle-widget'); ?></th>
                            <td><input type="text" name="query_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Consulta SQL', 'oracle-widget'); ?></th>
                            <td>
                                <textarea name="sql_query" rows="6" cols="50" class="large-text" required></textarea>
                                <p class="description"><?php _e('Use :limit como marcador de posición para el límite de filas', 'oracle-widget'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Descripción', 'oracle-widget'); ?></th>
                            <td><input type="text" name="query_description" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Orden Predeterminado', 'oracle-widget'); ?></th>
                            <td>
                                <input type="text" name="default_order" class="regular-text" placeholder="<?php _e('columna1, columna2 DESC', 'oracle-widget'); ?>">
                                <p class="description"><?php _e('Orden predeterminado para esta consulta (opcional)', 'oracle-widget'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit_query" class="button-primary" value="<?php _e('Guardar Consulta', 'oracle-widget'); ?>">
                    </p>
                </form>
            <?php endif; ?>
            
            <h3><?php _e('Consultas Existentes', 'oracle-widget'); ?></h3>
            <?php if (empty($queries)): ?>
                <p><?php _e('Aún no hay consultas configuradas.', 'oracle-widget'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Nombre', 'oracle-widget'); ?></th>
                            <th><?php _e('Descripción', 'oracle-widget'); ?></th>
                            <th><?php _e('Consulta', 'oracle-widget'); ?></th>
                            <th><?php _e('Orden Predeterminado', 'oracle-widget'); ?></th>
                            <th><?php _e('Acciones', 'oracle-widget'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queries as $id => $query): ?>
                            <tr>
                                <td><?php echo esc_html($query['name']); ?></td>
                                <td><?php echo esc_html($query['description']); ?></td>
                                <td><code><?php echo esc_html(substr($query['query'], 0, 100)) . (strlen($query['query']) > 100 ? '...' : ''); ?></code></td>
                                <td><?php echo esc_html($query['default_order'] ?? ''); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'oracle-widget-settings', 'edit_query' => $id))); ?>" 
                                       class="button button-small"><?php esc_html_e('Editar', 'oracle-widget'); ?></a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('page' => 'oracle-widget-settings', 'delete_query' => $id)), 'delete_query_' . $id)); ?>" 
                                       onclick="return confirm('<?php esc_attr_e('¿Está seguro?', 'oracle-widget'); ?>')"
                                       class="button button-small"><?php esc_html_e('Eliminar', 'oracle-widget'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('.nav-tab').click(function(e) {
            e.preventDefault();
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.tab-content').hide();
            $($(this).attr('href')).show();
        });
        
        // Test connection functionality
        function testConnection(formSelector, resultSelector) {
            var $form = $(formSelector);
            var $result = $(resultSelector);
            
            var connectionData = {
                action: 'oracle_widget_test_connection',
                nonce: '<?php echo wp_create_nonce('oracle_widget_test_connection'); ?>',
                host: $form.find('input[name="host"]').val(),
                port: $form.find('input[name="port"]').val(),
                service_name: $form.find('input[name="service_name"]').val(),
                username: $form.find('input[name="username"]').val(),
                password: $form.find('input[name="password"]').val()
            };
            
            // Validate required fields
            if (!connectionData.host || !connectionData.port || !connectionData.service_name || !connectionData.username) {
                $result.html('<span style="color: #d63638; font-weight: bold;">⚠️ <?php esc_html_e('Por favor complete todos los campos requeridos', 'oracle-widget'); ?></span>');
                return;
            }
            
            // For edit form, password might be empty (keeping existing)
            if (!connectionData.password) {
                if (formSelector.indexOf('edit') !== -1) {
                    $result.html('<span style="color: #d63638; font-weight: bold;">⚠️ <?php esc_html_e('Ingrese la contraseña para probar la conexión', 'oracle-widget'); ?></span>');
                } else {
                    $result.html('<span style="color: #d63638; font-weight: bold;">⚠️ <?php esc_html_e('La contraseña es requerida', 'oracle-widget'); ?></span>');
                }
                return;
            }
            
            $result.html('<span style="color: #0073aa;"><span class="oracle-test-loading"></span><?php esc_html_e('Probando conexión...', 'oracle-widget'); ?></span>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: connectionData,
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        $result.html('<span style="color: #00a32a; font-weight: bold;">✅ ' + response.data.message + '</span>');
                    } else {
                        $result.html('<span style="color: #d63638; font-weight: bold;">❌ ' + response.data.message + '</span>');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = '<?php esc_html_e('Error de conexión: ', 'oracle-widget'); ?>';
                    if (status === 'timeout') {
                        errorMsg += '<?php esc_html_e('Tiempo de espera agotado', 'oracle-widget'); ?>';
                    } else {
                        errorMsg += error;
                    }
                    $result.html('<span style="color: #d63638; font-weight: bold;">❌ ' + errorMsg + '</span>');
                }
            });
        }
        
        // Test connection for new connection form
        $('#test-connection-btn').click(function() {
            testConnection('form', '#test-connection-result');
        });
        
        // Test connection for edit connection form
        $('#test-connection-edit-btn').click(function() {
            testConnection('form', '#test-connection-edit-result');
        });
    });
    </script>
    <?php
}

/**
 * Save Connection
 */
function oracle_widget_save_connection() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Validate nonce for security
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'oracle_widget_connection')) {
        wp_die(__('Security check failed. Please try again.', 'oracle-widget'));
    }
    
    // Validate required fields
    $required_fields = array('connection_name', 'host', 'port', 'service_name', 'username', 'password');
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('El campo %s es obligatorio.', 'oracle-widget'), $field) . '</p></div>';
            return;
        }
    }
    
    // Validate port number
    $port = intval($_POST['port']);
    if ($port < 1 || $port > 65535) {
        echo '<div class="notice notice-error"><p>' . __('El puerto debe ser un número entre 1 y 65535.', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Validate host (basic check for valid hostname/IP)
    $host = sanitize_text_field($_POST['host']);
    if (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        echo '<div class="notice notice-error"><p>' . __('El host no es válido.', 'oracle-widget') . '</p></div>';
        return;
    }
    
    $connections = get_option('oracle_widget_connections', array());
    $id = sanitize_title($_POST['connection_name']);
    
    $connections[$id] = array(
        'name' => sanitize_text_field($_POST['connection_name']),
        'host' => $host,
        'port' => $port,
        'service_name' => sanitize_text_field($_POST['service_name']),
        'username' => sanitize_text_field($_POST['username']),
        'password' => base64_encode($_POST['password']) // Basic encoding (not encryption, but better than plain text)
    );
    
    update_option('oracle_widget_connections', $connections);
    
    echo '<div class="notice notice-success"><p>' . __('Conexión guardada exitosamente!', 'oracle-widget') . '</p></div>';
}

/**
 * Update Connection
 */
function oracle_widget_update_connection() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Validate nonce for security
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'oracle_widget_connection')) {
        wp_die(__('Security check failed. Please try again.', 'oracle-widget'));
    }
    
    $connections = get_option('oracle_widget_connections', array());
    $id = sanitize_text_field($_POST['connection_id']);
    
    if (!isset($connections[$id])) {
        echo '<div class="notice notice-error"><p>' . __('¡Conexión no encontrada!', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Validate required fields (except password which can be empty to keep existing)
    $required_fields = array('connection_name', 'host', 'port', 'service_name', 'username');
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('El campo %s es obligatorio.', 'oracle-widget'), $field) . '</p></div>';
            return;
        }
    }
    
    // Validate port number
    $port = intval($_POST['port']);
    if ($port < 1 || $port > 65535) {
        echo '<div class="notice notice-error"><p>' . __('El puerto debe ser un número entre 1 y 65535.', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Validate host (basic check for valid hostname/IP)
    $host = sanitize_text_field($_POST['host']);
    if (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        echo '<div class="notice notice-error"><p>' . __('El host no es válido.', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Keep existing password if new one is not provided
    $password = !empty($_POST['password']) ? base64_encode($_POST['password']) : $connections[$id]['password'];
    
    $connections[$id] = array(
        'name' => sanitize_text_field($_POST['connection_name']),
        'host' => $host,
        'port' => $port,
        'service_name' => sanitize_text_field($_POST['service_name']),
        'username' => sanitize_text_field($_POST['username']),
        'password' => $password
    );
    
    update_option('oracle_widget_connections', $connections);
    
    echo '<div class="notice notice-success"><p>' . __('Conexión actualizada exitosamente!', 'oracle-widget') . '</p></div>';
}

/**
 * Save Query
 */
function oracle_widget_save_query() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Validate nonce for security
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'oracle_widget_query')) {
        wp_die(__('Security check failed. Please try again.', 'oracle-widget'));
    }
    
    // Validate required fields
    if (empty($_POST['query_name']) || empty($_POST['sql_query'])) {
        echo '<div class="notice notice-error"><p>' . __('El nombre y la consulta SQL son obligatorios.', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Basic SQL validation - check for dangerous operations
    $sql_query = trim($_POST['sql_query']);
    if (!preg_match('/^\s*SELECT\s+/i', $sql_query)) {
        echo '<div class="notice notice-error"><p>' . __('Solo se permiten consultas SELECT.', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Check for potentially dangerous SQL keywords
    $dangerous_keywords = array('DELETE', 'DROP', 'INSERT', 'UPDATE', 'ALTER', 'CREATE', 'TRUNCATE', 'EXEC', 'EXECUTE');
    foreach ($dangerous_keywords as $keyword) {
        if (preg_match('/\b' . $keyword . '\b/i', $sql_query)) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('La consulta contiene la palabra clave no permitida: %s', 'oracle-widget'), $keyword) . '</p></div>';
            return;
        }
    }
    
    $queries = get_option('oracle_widget_queries', array());
    $id = sanitize_title($_POST['query_name']);
    
    // Sanitize default_order using our sanitization function
    $default_order = '';
    if (!empty($_POST['default_order'])) {
        // Create a temporary widget instance to use the sanitize method
        $widget = new Oracle_Database_Widget();
        $reflection = new ReflectionClass($widget);
        $method = $reflection->getMethod('sanitize_order_by');
        $method->setAccessible(true);
        $default_order = $method->invoke($widget, $_POST['default_order']);
    }
    
    $queries[$id] = array(
        'name' => sanitize_text_field($_POST['query_name']),
        'description' => sanitize_text_field($_POST['query_description']),
        'query' => $sql_query,
        'default_order' => $default_order
    );
    
    update_option('oracle_widget_queries', $queries);
    
    echo '<div class="notice notice-success"><p>' . __('Consulta guardada exitosamente!', 'oracle-widget') . '</p></div>';
}

/**
 * Update Query
 */
function oracle_widget_update_query() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Validate nonce for security
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'oracle_widget_query')) {
        wp_die(__('Security check failed. Please try again.', 'oracle-widget'));
    }
    
    $queries = get_option('oracle_widget_queries', array());
    $id = sanitize_text_field($_POST['query_id']);
    
    if (!isset($queries[$id])) {
        echo '<div class="notice notice-error"><p>' . __('¡Consulta no encontrada!', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Validate required fields
    if (empty($_POST['query_name']) || empty($_POST['sql_query'])) {
        echo '<div class="notice notice-error"><p>' . __('El nombre y la consulta SQL son obligatorios.', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Basic SQL validation - check for dangerous operations
    $sql_query = trim($_POST['sql_query']);
    if (!preg_match('/^\s*SELECT\s+/i', $sql_query)) {
        echo '<div class="notice notice-error"><p>' . __('Solo se permiten consultas SELECT.', 'oracle-widget') . '</p></div>';
        return;
    }
    
    // Check for potentially dangerous SQL keywords
    $dangerous_keywords = array('DELETE', 'DROP', 'INSERT', 'UPDATE', 'ALTER', 'CREATE', 'TRUNCATE', 'EXEC', 'EXECUTE');
    foreach ($dangerous_keywords as $keyword) {
        if (preg_match('/\b' . $keyword . '\b/i', $sql_query)) {
            echo '<div class="notice notice-error"><p>' . sprintf(__('La consulta contiene la palabra clave no permitida: %s', 'oracle-widget'), $keyword) . '</p></div>';
            return;
        }
    }
    
    // Sanitize default_order using our sanitization function
    $default_order = '';
    if (!empty($_POST['default_order'])) {
        // Create a temporary widget instance to use the sanitize method
        $widget = new Oracle_Database_Widget();
        $reflection = new ReflectionClass($widget);
        $method = $reflection->getMethod('sanitize_order_by');
        $method->setAccessible(true);
        $default_order = $method->invoke($widget, $_POST['default_order']);
    }
    
    $queries[$id] = array(
        'name' => sanitize_text_field($_POST['query_name']),
        'description' => sanitize_text_field($_POST['query_description']),
        'query' => $sql_query,
        'default_order' => $default_order
    );
    
    update_option('oracle_widget_queries', $queries);
    
    echo '<div class="notice notice-success"><p>' . __('Consulta actualizada exitosamente!', 'oracle-widget') . '</p></div>';
}

/**
 * Delete Connection
 */
function oracle_widget_delete_connection($id) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verify nonce for security
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_connection_' . $id)) {
        wp_die(esc_html__('Security check failed. Please try again.', 'oracle-widget'));
    }
    
    $connections = get_option('oracle_widget_connections', array());
    if (isset($connections[$id])) {
        unset($connections[$id]);
        update_option('oracle_widget_connections', $connections);
        echo '<div class="notice notice-success"><p>' . esc_html__('Conexión eliminada exitosamente!', 'oracle-widget') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__('Conexión no encontrada.', 'oracle-widget') . '</p></div>';
    }
}

/**
 * Delete Query
 */
function oracle_widget_delete_query($id) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Verify nonce for security
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_query_' . $id)) {
        wp_die(esc_html__('Security check failed. Please try again.', 'oracle-widget'));
    }
    
    $queries = get_option('oracle_widget_queries', array());
    if (isset($queries[$id])) {
        unset($queries[$id]);
        update_option('oracle_widget_queries', $queries);
        echo '<div class="notice notice-success"><p>' . esc_html__('Consulta eliminada exitosamente!', 'oracle-widget') . '</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>' . esc_html__('Consulta no encontrada.', 'oracle-widget') . '</p></div>';
    }
}

/**
 * Add CSS styles for the widget
 */
function oracle_widget_styles() {
    // Only add styles if we're likely to need them
    global $post;
    $should_add_styles = false;
    
    if (is_admin()) {
        $should_add_styles = true;
    } elseif (is_active_widget(false, false, 'oracle_database_widget')) {
        $should_add_styles = true;
    } elseif (is_object($post) && has_shortcode($post->post_content, 'oracle_data')) {
        $should_add_styles = true;
    }
    
    if (!$should_add_styles) {
        return;
    }
    ?>
    <style>
        .oracle-widget-content {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        .oracle-widget-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .oracle-widget-table th,
        .oracle-widget-table td {
            border: 1px solid #e0e0e0;
            padding: 12px 8px;
            text-align: left;
            word-wrap: break-word;
        }
        
        .oracle-widget-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        .oracle-widget-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .oracle-widget-table tr:hover {
            background-color: #e3f2fd;
            transition: background-color 0.2s ease;
        }
        
        .oracle-widget-content .error {
            color: #d32f2f;
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            border-radius: 4px;
            padding: 12px;
            margin: 10px 0;
            font-weight: 500;
        }
        
        .oracle-widget-content .info {
            color: #1976d2;
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 4px;
            padding: 12px;
            margin: 10px 0;
            font-weight: 500;
        }
        
        /* jVectorMap Styles */
        .oracle-widget-content .jvectormap-container {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            background: white;
        }
        
        .oracle-widget-content .jvectormap-zoomin,
        .oracle-widget-content .jvectormap-zoomout {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 14px;
            font-weight: bold;
            height: 32px;
            width: 32px;
            line-height: 32px;
            text-align: center;
            cursor: pointer;
            margin: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        }
        
        .oracle-widget-content .jvectormap-zoomin:hover,
        .oracle-widget-content .jvectormap-zoomout:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.25);
        }
        
        .oracle-widget-content .jvectormap-zoomout {
            top: 48px;
        }
        
        /* Map container responsive */
        .oracle-widget-content .map-container {
            position: relative;
            width: 100%;
            height: 400px;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f5f5;
        }
        
        /* Loading state */
        .oracle-widget-content .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 200px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #666;
            font-style: italic;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .oracle-widget-table {
                font-size: 12px;
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .oracle-widget-table th,
            .oracle-widget-table td {
                padding: 8px 6px;
                min-width: 80px;
            }
            
            .oracle-widget-content .map-container {
                height: 300px;
            }
            
            .oracle-widget-content .jvectormap-zoomin,
            .oracle-widget-content .jvectormap-zoomout {
                height: 28px;
                width: 28px;
                font-size: 12px;
                margin: 6px;
            }
        }
        
        @media (max-width: 480px) {
            .oracle-widget-table {
                font-size: 11px;
            }
            
            .oracle-widget-content .map-container {
                height: 250px;
            }
        }
        
        /* Both table and map layout */
        .oracle-widget-content .table-map-container {
            margin-bottom: 30px;
        }
        
        .oracle-widget-content .map-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 3px solid #e0e0e0;
        }
        
        .oracle-widget-content .map-section h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
        }
        
        .oracle-widget-content .table-section {
            margin-bottom: 20px;
        }
        
        /* Performance optimization - reduce repaints */
        .oracle-widget-content * {
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
        }
    </style>
    
    <?php if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'oracle-widget-settings'): ?>
    <style>
        /* Admin styles for test connection functionality */
        #test-connection-btn, #test-connection-edit-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-color: #28a745;
            color: white;
            font-weight: 600;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
            transition: all 0.2s ease;
        }
        
        #test-connection-btn:hover, #test-connection-edit-btn:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            border-color: #1e7e34;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        
        #test-connection-btn:active, #test-connection-edit-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }
        
        #test-connection-result, #test-connection-edit-result {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: 500;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Loading spinner */
        .oracle-test-loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0073aa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <?php endif; ?>
    <?php
}
add_action('wp_head', 'oracle_widget_styles');

/**
 * AJAX handler for testing database connection
 */
function oracle_widget_test_connection_ajax() {
    // Check if user has permission
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'oracle-widget'));
    }
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'oracle_widget_test_connection')) {
        wp_send_json_error(array('message' => __('Verificación de seguridad fallida.', 'oracle-widget')));
        return;
    }
    
    // Check if OCI8 extension is available
    if (!extension_loaded('oci8')) {
        wp_send_json_error(array('message' => __('La extensión Oracle OCI8 no está instalada en el servidor.', 'oracle-widget')));
        return;
    }
    
    // Get and validate connection parameters
    $host = sanitize_text_field($_POST['host'] ?? '');
    $port = intval($_POST['port'] ?? 0);
    $service_name = sanitize_text_field($_POST['service_name'] ?? '');
    $username = sanitize_text_field($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    if (empty($host) || empty($port) || empty($service_name) || empty($username)) {
        wp_send_json_error(array('message' => __('Todos los campos son requeridos.', 'oracle-widget')));
        return;
    }
    
    // Validate port number
    if ($port < 1 || $port > 65535) {
        wp_send_json_error(array('message' => __('El puerto debe ser un número entre 1 y 65535.', 'oracle-widget')));
        return;
    }
    
    // Validate host (basic check for valid hostname/IP)
    if (!filter_var($host, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9\-\.]+$/', $host)) {
        wp_send_json_error(array('message' => __('El host no es válido.', 'oracle-widget')));
        return;
    }
    
    // If password is empty, it might be an edit form - we can't test without password
    if (empty($password)) {
        wp_send_json_error(array('message' => __('La contraseña es requerida para probar la conexión.', 'oracle-widget')));
        return;
    }
    
    try {
        // Create connection string with timeout
        $connection_string = "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST={$host})(PORT={$port}))(CONNECT_DATA=(SERVICE_NAME={$service_name}))(CONNECT_TIMEOUT=10)(RETRY_COUNT=1))";
        
        // Attempt to connect to Oracle
        $db_connection = @oci_connect($username, $password, $connection_string);
        
        if (!$db_connection) {
            $error = oci_error();
            $error_message = isset($error['message']) ? $error['message'] : __('Error de conexión desconocido', 'oracle-widget');
            
            // Log error for debugging
            error_log('Oracle Widget Test Connection Error: ' . $error_message);
            
            wp_send_json_error(array('message' => sprintf(__('Error de conexión: %s', 'oracle-widget'), esc_html($error_message))));
            return;
        }
        
        // Test a simple query to verify the connection works
        $test_query = "SELECT 1 FROM DUAL";
        $statement = @oci_parse($db_connection, $test_query);
        
        if (!$statement) {
            $error = oci_error($db_connection);
            oci_close($db_connection);
            wp_send_json_error(array('message' => sprintf(__('Error al preparar consulta de prueba: %s', 'oracle-widget'), esc_html($error['message'] ?? 'Error desconocido'))));
            return;
        }
        
        $result = @oci_execute($statement);
        
        if (!$result) {
            $error = oci_error($statement);
            oci_free_statement($statement);
            oci_close($db_connection);
            wp_send_json_error(array('message' => sprintf(__('Error al ejecutar consulta de prueba: %s', 'oracle-widget'), esc_html($error['message'] ?? 'Error desconocido'))));
            return;
        }
        
        // Get Oracle version info for additional verification
        $version_query = "SELECT BANNER FROM V\$VERSION WHERE ROWNUM = 1";
        $version_statement = @oci_parse($db_connection, $version_query);
        $oracle_version = '';
        
        if ($version_statement && @oci_execute($version_statement)) {
            $version_row = oci_fetch_assoc($version_statement);
            if ($version_row && isset($version_row['BANNER'])) {
                $oracle_version = ' (' . substr($version_row['BANNER'], 0, 50) . '...)';
            }
            oci_free_statement($version_statement);
        }
        
        // Clean up
        oci_free_statement($statement);
        oci_close($db_connection);
        
        // Success response
        wp_send_json_success(array(
            'message' => sprintf(__('¡Conexión exitosa!%s', 'oracle-widget'), $oracle_version)
        ));
        
    } catch (Exception $e) {
        error_log('Oracle Widget Test Connection Exception: ' . $e->getMessage());
        wp_send_json_error(array('message' => sprintf(__('Error inesperado: %s', 'oracle-widget'), esc_html($e->getMessage()))));
    } catch (Error $e) {
        error_log('Oracle Widget Test Connection Fatal Error: ' . $e->getMessage());
        wp_send_json_error(array('message' => sprintf(__('Error fatal: %s', 'oracle-widget'), esc_html($e->getMessage()))));
    }
}
add_action('wp_ajax_oracle_widget_test_connection', 'oracle_widget_test_connection_ajax');

/**
 * Plugin activation hook
 */
function oracle_widget_activate() {
    // Check if OCI8 extension is available
    if (!extension_loaded('oci8')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Este plugin requiere que la extensión Oracle OCI8 de PHP esté instalada.', 'oracle-widget'),
            esc_html__('Error de Activación del Plugin', 'oracle-widget'),
            array('back_link' => true)
        );
    }
    
    // Create default options if they don't exist
    add_option('oracle_widget_connections', array());
    add_option('oracle_widget_queries', array());
    
    // Set plugin version
    add_option('oracle_widget_version', ORACLE_WIDGET_VERSION);
}
register_activation_hook(__FILE__, 'oracle_widget_activate');

/**
 * Plugin deactivation hook
 */
function oracle_widget_deactivate() {
    // Clear any cached data
    wp_cache_flush();
    
    // Remove scheduled events if any were added
    wp_clear_scheduled_hook('oracle_widget_cleanup');
}
register_deactivation_hook(__FILE__, 'oracle_widget_deactivate');

/**
 * Plugin uninstall hook - only runs when plugin is deleted
 */
function oracle_widget_uninstall() {
    // Remove plugin options
    delete_option('oracle_widget_connections');
    delete_option('oracle_widget_queries');
    delete_option('oracle_widget_version');
    
    // Clean up any transients
    delete_transient('oracle_widget_cache');
    
    // Remove widget instances
    delete_option('widget_oracle_database_widget');
}
register_uninstall_hook(__FILE__, 'oracle_widget_uninstall'); 