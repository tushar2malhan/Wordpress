<?php

include_once 'Utilities.php';
require_once dirname(__FILE__) . '/includes/lib/mo-saml-options-enum.php';
include_once 'WPConfigEditor.php';

class MoSAMLLogger
{
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    const ERROR = 'ERROR';
    const CRITICAL = "CRITICAL";
    private static $log_file_writable = false;
	protected $cached_logs = array();

    /**
     * @return bool
     */
	public static function is_log_file_writable() {
		return is_writeable(self::get_saml_log_directory());
    }

	/***
	 *
	 * initializes directory to write debug logs.
	 */
	public static function init() {

		//For setting up debug directory for log files
		$upload_dir = wp_upload_dir( null, false );
		if ( is_writable( $upload_dir['basedir'] ) ) {
			self::$log_file_writable = true;
			if ( ! is_dir( self::get_saml_log_directory() ) ) {
				self::create_files();
			}
		} else {
			add_action( 'admin_notices', 'directory_notice', 11 );
		}
	}

	public static function get_saml_log_directory() {
		$upload_dir = wp_upload_dir( null, false );

		return $upload_dir['basedir'] . '/mo-saml-logs/';
	}

    /**
     * Add a log entry along with the log level
     *
     * @param string $log_message
     * @param string $log_level
     */
    public static function add_log($log_message = "", $log_level = self::INFO) {
        if ( !self::is_debugging_enabled() )
            return;

        error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
        ini_set( 'display_errors', 0 );
        $log_path = self::get_log_file_path( 'mo_saml' );
        if ( $log_path ) {
            ini_set( 'log_errors', 1 );
            ini_set( 'error_log', $log_path );
            $exception = new Exception();
            $trace     = $exception->getTrace();
            $last_call = $trace[1];
            $message   = $log_level;
            $message   .= ' ' . $last_call['function'] . ' : ' . $last_call['line'];
            $message   = $message . ' ' . str_replace( array( "\r", "\n", "\t" ), '', rtrim( $log_message ) ) . PHP_EOL;
            $message   = preg_replace( "/[,]/", "\n", $message );
            error_log( $message );
        }
    }

    /**
     * Cache log to write later.
     *
     * @param string $entry Log entry text.
     * @param string $handle Log entry handle.
     */
    protected function cache_log( $entry ) {
        $this->cached_logs[] = array(
            'entry'  => $entry,
            'handle' => 'mo_saml',
        );
    }

    /**
     * Write cached logs.
     */
    public function write_cached_logs() {
        foreach ( $this->cached_logs as $log ) {
            self::add_log($log['entry'], $log['handle']);
        }
    }

    /**
     *  Logs critical errors
     */
    public static function log_critical_errors() {
        $error = error_get_last();
        if ( $error && in_array( $error['type'], array(
                E_ERROR,
                E_PARSE,
                E_COMPILE_ERROR,
                E_USER_ERROR,
                E_RECOVERABLE_ERROR
            ), true ) ) {
            self::add_log(
                sprintf(__('%1$s in %2$s on line %3$s', 'mo'), $error['message'], $error['file'], $error['line']) . PHP_EOL, self::CRITICAL);
        }
    }

    /**
     * Get all log files in the log directory.
     *
     * @return array
	 * @since 3.4.0
     */
    public static function get_log_files()
    {
        $files  = @scandir(self::get_saml_log_directory());
        $result = array();
        if (!empty($files)) {
            foreach ($files as $key => $value) {
                if (!in_array($value, array('.', '..'), true)) {
                    if (!is_dir($value) && strstr($value, '.log')) {
                        $result[sanitize_title($value)] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Deletes all the files in the Log directory older than 7 Days
     */
    public static function delete_logs_before_timestamp($timestamp = 0) {
        if (!$timestamp) {
            return;
        }
        $log_files = self::get_log_files();
        foreach ($log_files as $log_file) {
            $last_modified = filemtime(trailingslashit(self::get_saml_log_directory()) . $log_file);
            if ($last_modified < $timestamp) {
                @unlink(trailingslashit(self::get_saml_log_directory()) . $log_file); // @codingStandardsIgnoreLine.
            }
        }
    }

    /**
     * Get the file path of current log file used by plugins
     */
    public static function get_log_file_path($handle)
    {
        if (function_exists('wp_hash')) {
            return trailingslashit(self::get_saml_log_directory()) . self::get_log_file_name($handle);
        } else {
            return false;
        }
    }

    /**
     * To get the log for based on the time
     */

    public static function get_log_file_name($handle)
    {
        if (function_exists('wp_hash')) {
            $date_suffix = date('Y-m-d', time());
            $hash_suffix = wp_hash($handle);
            return sanitize_file_name(implode('-', array($handle, $date_suffix, $hash_suffix)) . '.log');
        } else {
            _doing_it_wrong(__METHOD__, __('This method should not be called before plugins_loaded.', 'miniorange'), mo_saml_options_plugin_constants::Version);
            return false;
        }
    }

    /**
     * Used to show the UI part of the log feature to user screen.
     */
	public static function mo_saml_log_page() {
        mo_saml_display_log_page();
    }

    /**
     * Creates files Index.html for directory listing
     * and local .htaccess rule to avoid hotlinking
     */
    private static function create_files() {

        $upload_dir      = wp_get_upload_dir();

        $files = array(

            array(
                'base'    => self::get_saml_log_directory(),
                'file'    => '.htaccess',
                'content' => 'deny from all',
            ),
            array(
                'base'    => self::get_saml_log_directory(),
                'file'    => 'index.html',
                'content' => '',
            ),
        );

        foreach ($files as $file) {
            if (wp_mkdir_p($file['base']) && !file_exists(trailingslashit($file['base']) . $file['file'])) {
                $file_handle = @fopen(trailingslashit($file['base']) . $file['file'], 'wb'); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
                if ($file_handle) {
                    fwrite($file_handle, $file['content']);
                    fclose($file_handle);
                }
            }
        }
    }

    /**
     * Check if a constant is defined if not define a cosnt
     */
    private static function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * To check if Debug constant is defined and logs are enabled
     * @return bool
     */
    public static function is_debugging_enabled() {
        if (!defined('MO_SAML_LOGGING')) {
            return false;
        } else {
            return MO_SAML_LOGGING;
        }
    }

    public static function mo_saml_admin_notices(){
        
		if(!MoSAMLLogger::is_log_file_writable() && MoSAMLLogger::is_debugging_enabled())
		{
			add_action('admin_notices', function (){echo wp_kses_post( sprintf(
				__( '<div class="error" style=""><p/>To allow logging, make  <code>"%1s"</code> directory writable.miniOrange will not be able to log the errors.</div>', 'miniorange-saml-20-single-sign-on' ),
				self::get_saml_log_directory()
			) ); } );
		}
		if(MoSAMLLogger::is_log_file_writable() && MoSAMLLogger::is_debugging_enabled()){
			add_action('admin_notices', function (){echo wp_kses_post( sprintf(
				__( '<div class="updated" style="margin-left: auto"><p/> miniOrange SAML 2.0 logs are active. Want to turn it off? <a href="%s">Learn more here.</a></div>', 'miniorange-saml-20-single-sign-on' ),
				admin_url().'admin.php?page=mo_saml_enable_debug_logs'
			) ); } );
		}
	}

    function directory_notice() {
		$msg = esc_html( sprintf( 'Directory %1$s is not writeable, plugin will not able to write the file please update file permission', self::get_saml_log_directory() ) );
		echo "<div class=\"error\"> <p>" . esc_html($msg) . "</p></div>";
	}
}
