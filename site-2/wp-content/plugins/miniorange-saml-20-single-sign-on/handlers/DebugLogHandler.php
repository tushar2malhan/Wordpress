<?php


class DebugLogHandler {

	static function process_logging($post_array) {

		if(isset($post_array['download'])) {
			self::download_log_file();
		}
		else if(isset($post_array['clear'])){
			self::mo_saml_cleanup_logs();
		}
		else {
			self::enable_logging($post_array);
		}
	}

	static function download_log_file() {
		$file= MoSAMLLogger::get_log_file_path('mo_saml');
		$log_message = mo_saml_miniorange_import_export(false,true);
		MoSAMLLogger::add_log(mo_saml_error_log::showMessage('PLUGIN_CONFIGURATIONS',json_decode($log_message,TRUE)), MoSAMLLogger::INFO);

		if(!file_exists($file)) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::LOG_FILE_NOT_FOUND);
			$post_save->post_save_action();
			return;
		}

		header("Content-Disposition: attachment;");
		header('Content-type: application');
		header('Content-Disposition: attachment; filename="'.basename($file).'"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file));
		readfile($file);
		exit;
	}

	static function mo_saml_cleanup_logs() {
		$retention_period = absint(apply_filters('mo_saml_logs_retention_period',0));
		$timestamp = strtotime( "-{$retention_period} days" );
		if (is_callable(array('MoSAMLLogger', 'delete_logs_before_timestamp'))) {
			MoSAMLLogger::delete_logs_before_timestamp($timestamp);
		}
		$post_save = new PostSaveHandler('SUCCESS', mo_saml_messages::LOG_FILE_CLEARED);
		$post_save->post_save_action();
		return;
	}

	static function enable_logging($post_array) {

		$mo_saml_enable_logs = false;
		if(isset($post_array['mo_saml_enable_debug_logs']) and $post_array['mo_saml_enable_debug_logs'] === 'true')
			$mo_saml_enable_logs = true;

		$wp_config_path = ABSPATH . 'wp-config.php';
		if(!is_writeable($wp_config_path)) {
			$post_save = new PostSaveHandler('ERROR', mo_saml_messages::WPCONFIG_ERROR);
			$post_save->post_save_action();
			return;
		}

		try {
			$wp_config_editor = new WPConfigEditor($wp_config_path);    //that will be null in case wp-config.php is not writable
			if($mo_saml_enable_logs) {
				MoSAMLLogger::init();
				$wp_config_editor->update('MO_SAML_LOGGING', 'true'); //fatal error is call on null
				MoSAMLLogger::add_log("MO SAML Debug Logs Enabled",MoSAMLLogger::INFO);
			}
			else {
				MoSAMLLogger::add_log("MO SAML Debug Logs Disabled",MoSAMLLogger::INFO);
				$wp_config_editor->update('MO_SAML_LOGGING', 'false');  //fatal error
			}
			$delay_for_file_write = (int) 2;
			sleep($delay_for_file_write);
			wp_redirect(saml_get_current_page_url());
			exit();

		} catch (Exception $e){
			return;
		}
	}

}