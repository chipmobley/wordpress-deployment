<?php
//improve error reporting.  s3 and dir backup have decent reporting now, but not sure i know what to do from here
//better implementation of retain.  one that isn't dependent on being inside the cloud_backup method
//list backups that aren't tracked (helps with double backup problem)
//refactor db backup methods a bit.  give full credit to wp-db-backup
//investigate $php_errormsg further
//pretty up return messages in admin area
//check s3/ftp download
//allow upload of backup files too. (specify 1-4 files to restore)
//user permissions for WP users if ( function_exists('is_site_admin') && ! is_site_admin() ) around backups?


/*
Plugin Name: Updraft - Backup/Restore
Plugin URI: http://langui.sh/updraft-wp-backup-restore
Description: Updraft - Backup/Restore is a plugin designed to back up your WordPress blog.  Uploads, themes, plugins, and your DB can be backed up to Rackspace Cloud Files, Amazon S3, sent to an FTP server, or even emailed to you on a scheduled basis.
Author: Paul Kehrer
Version: 0.6
Author URI: http://langui.sh/
*/ 

/*  Copyright 2010  Paul Kehrer

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
ini_set('memory_limit', '192M'); //up the memory limit for large backup files...probably need to make this a configurable pref
set_time_limit(900); //15 minutes max. i'm not sure how long a really big blog could take to back up?

$updraft = new updraft();

class updraft {
	var $dbhandle;
	var $errors = array();
	var $nonce;
	var $backup_time;
	
	function __construct() {
		$this->bind_actions();
		$this->bind_filters();
	}
	
	function bind_actions() {
		if(!defined('UPDRAFT_CONSTANTS')) {
			add_action('admin_menu', array($this,'add_admin_pages'));  //create admin page
		}
		add_action( 'admin_init', array($this,'admin_init'));
		add_action('updraft_backup', array($this,'backup'));
		add_action('wp_ajax_updraft_download_backup', array($this, 'updraft_download_backup'));
	}

	function bind_filters() {
		add_filter('cron_schedules', array($this,'modify_cron_schedules'));
	}
	
	function backup_time_nonce() {
		$this->backup_time = time();
		$this->nonce = substr(md5(time().rand()),20);
	}
	
	//scheduled wp-cron events can have a race condition here if page loads are coming fast enough, but there's nothing we can do about it.
	function backup() {
		//generate backup information
		$this->backup_time_nonce();
		
		//backup directories and return a numerically indexed array of file paths to the backup files
		$backup_array = $this->backup_dirs();
		//backup DB and return string of file path
		$db_backup = $this->backup_db();
		//add db path to rest of files
		if(is_array($backup_array)) {
			$backup_array['db'] = $db_backup;
		}
		//save this to our history so we can track backups for the retain feature
		$this->save_backup_history($backup_array);

		//cloud operations (CF,S3,FTP,email,nothing)
		//this also calls the retain feature at the end (done in this method to reuse existing cloud connections)
		if(is_array($backup_array)) {
			$this->cloud_backup($backup_array);
		}
		//delete local files if the pref is set
		foreach($backup_array as $file) {
			$this->delete_local($file);
		}
		
		//save the last backup info, including errors, if any
		$this->save_last_backup($backup_array);
		
		if(get_option('updraft_email_complete') && get_option('updraft_service') != 'email') {
			wp_mail(get_option('updraft_email'),'WordPress Backup '.date('Y-m-d H:i',time()),'Backup is complete.');
		}
	}
	
	function save_last_backup($backup_array) {
		$success = (empty($this->errors))?1:0;
		$last_backup = array('backup_time'=>$this->backup_time,'backup_array'=>$backup_array,'success'=>$success,'errors'=>$this->errors);
		update_option('updraft_last_backup',$last_backup);
	}

	function cloud_backup($backup_array) {
		switch(get_option('updraft_service')) {
			case 'cloudfiles':
				$this->cf_backup($backup_array);
			break;
			case 's3':
				$this->s3_backup($backup_array);
			break;
			case 'ftp':
				$this->ftp_backup($backup_array);
			break;
			case 'email':
				//files can easily get way too big for this...
				foreach($backup_array as $type=>$file) {
					$fullpath = trailingslashit(get_option('updraft_dir')).$file;
					wp_mail(get_option('updraft_email'),"WordPress Backup ".date('Y-m-d H:i',$this->backup_time),"Backup is of the $type.  Be wary; email backups may fail because of file size limitations on mail servers.",null,array($fullpath));
				}
				//we don't break here so it goes and executes all the default behavior below as well.  this gives us retain behavior for email
			default:
				/*retain behavior*/
				$updraft_retain = get_option('updraft_retain');
				$retain = (isset($updraft_retain))?get_option('updraft_retain'):1;
				$backup_history = $this->get_backup_history();
				while (count($backup_history) > $retain) {
					$backup_to_delete = array_pop($backup_history);
					foreach($backup_to_delete as $file) {
						$fullpath = trailingslashit(get_option('updraft_dir')).$file;
						@unlink($file); //delete it if it's locally available
					}
				}
				update_option('updraft_backup_history',$backup_history);
				/*retain behavior*/
			break;
		}
	}
	
	function cf_backup($backup_array) {
		if(!class_exists('CF_Authentication')) {
			require_once(dirname(__FILE__).'/includes/cloudfiles/cloudfiles.php');
		}
		$auth = new CF_Authentication(get_option('updraft_cf_login'),get_option('updraft_cf_pass'));
		try {
			$auth->authenticate();
		}
		catch(AuthenticationException $e) {
			$this->error('Authentication to CF failed. '.$e->getMessage());
		}
		$conn = new CF_Connection($auth);
		$cf_remote_path = untrailingslashit(get_option('updraft_cf_remote_path'));
		try {
			$container = $conn->create_container($cf_remote_path);
		}
		catch(Exception $e) {
			//we need some error handling
		}
		foreach($backup_array as $file) {
			$object = $container->create_object($file);
			$fullpath = trailingslashit(get_option('updraft_dir')).$file;
			$object->load_from_filename($fullpath);
		}
		
		/*retain behavior*/
		$updraft_retain = get_option('updraft_retain');
		$retain = (isset($updraft_retain))?get_option('updraft_retain'):1;
		$backup_history = $this->get_backup_history();
		while (count($backup_history) > $retain) {
			$backup_to_delete = array_pop($backup_history);
			foreach($backup_to_delete as $file) {
				//if for some reason one of the backup files is an empty string let's skip it.
				if($file == '') {
					continue;
				}
				$fullpath = trailingslashit(get_option('updraft_dir')).$file;
				@unlink($fullpath); //delete it if it's locally available
				try {
					$object = $container->delete_object($file);
				}
				catch(NoSuchObjectException $e) {
					//we don't care about something not existing when we try to delete it, so let's just 
					//eat the exception. yum yum yum
				}
			}
		}
		update_option('updraft_backup_history',$backup_history);
		/*retain behavior*/
	}
	
	function s3_backup($backup_array) {
		if(!class_exists('S3')) {
			require_once(dirname(__FILE__).'/includes/S3.php');
		}
		$s3 = new S3(get_option('updraft_s3_login'), get_option('updraft_s3_pass'));
		$bucket_name = untrailingslashit(get_option('updraft_s3_remote_path'));
		if (@$s3->putBucket($bucket_name, S3::ACL_PRIVATE)) {
			foreach($backup_array as $file) {
				$fullpath = trailingslashit(get_option('updraft_dir')).$file;
				if (!$s3->putObjectFile($fullpath, $bucket_name, $file)) {
					$this->error("S3 Error: Failed to upload $fullpath. Error was ".$php_errormsg);
				}
			}
		} else {
			$this->error("S3 Error: Failed to create bucket $bucket_name. Error was ".$php_errormsg);
		}
		/*retain behavior*/
		$updraft_retain = get_option('updraft_retain');
		$retain = (isset($updraft_retain))?get_option('updraft_retain'):1;
		$backup_history = $this->get_backup_history();
		while (count($backup_history) > $retain) {
			$backup_to_delete = array_pop($backup_history);
			foreach($backup_to_delete as $file) {
				//if for some reason one of the backup files is an empty string let's skip it.
				if($file == '') {
					continue;
				}
				$fullpath = trailingslashit(get_option('updraft_dir')).$file;
				@unlink($fullpath); //delete it if it's locally available
				if (!$s3->deleteObject($bucket_name, $file)) {
					$this->error("S3 Error: Failed to delete object $file. Error was ".$php_errormsg);
				}
			}
		}
		update_option('updraft_backup_history',$backup_history);
		/*retain behavior*/
	}
	
	function ftp_backup($backup_array) {
		if( !class_exists('ftp_wrapper')) {
			require_once(dirname(__FILE__).'/includes/ftp.class.php');
		}
		//handle SSL and errors at some point TODO
		$ftp = new ftp_wrapper(get_option('updraft_server_address'),get_option('updraft_ftp_login'),get_option('updraft_ftp_pass'));
		$ftp->passive = true;
		$ftp->connect();
		//$ftp->make_dir(); we may need to recursively create dirs? TODO
		
		$ftp_remote_path = trailingslashit(get_option('updraft_ftp_remote_path'));
		foreach($backup_array as $file) {
			$fullpath = trailingslashit(get_option('updraft_dir')).$file;
			$ftp->put($fullpath,$ftp_remote_path.$file,FTP_BINARY);
		}
		
		/*retain behavior*/
		$updraft_retain = get_option('updraft_retain');
		$retain = (isset($updraft_retain))?get_option('updraft_retain'):1;
		$backup_history = $this->get_backup_history();
		while (count($backup_history) > $retain) {
			$backup_to_delete = array_pop($backup_history);
			foreach($backup_to_delete as $file) {
				//if for some reason one of the backup files is an empty string let's skip it.
				if($file == '') {
					continue;
				}
				$fullpath = trailingslashit(get_option('updraft_dir')).$file;
				@unlink($fullpath); //delete it if it's locally available
				@$ftp->delete($ftp_remote_path.$file);
			}
		}
		update_option('updraft_backup_history',$backup_history);
		/*retain behavior*/
	}
	
	function delete_local($file) {
		if(get_option('updraft_delete_local')) {
			//need error checking so we don't delete what isn't successfully uploaded?
			$fullpath = trailingslashit(get_option('updraft_dir')).$file;
			return unlink($fullpath);
		}
		return true;
	}
	
	function backup_dirs() {
		if(!$this->backup_time) {
			$this->backup_time_nonce();
		}
		$wp_themes_dir = WP_CONTENT_DIR.'/themes';
		$wp_upload_dir = wp_upload_dir();
		$wp_upload_dir = $wp_upload_dir['basedir'];
		$wp_plugins_dir = WP_PLUGIN_DIR;
		if(!class_exists('PclZip')) {
			if (file_exists(ABSPATH.'/wp-admin/includes/class-pclzip.php')) {
				require_once(ABSPATH.'/wp-admin/includes/class-pclzip.php');
			}
		}
		$updraft_dir = $this->check_backups_dir();
		if(!is_writable($updraft_dir)) {
			$this->error('Backup directory is not writable.','fatal');
		}
		//get the blog name and rip out all non-alphanumeric chars other than _
		$blog_name = str_replace(' ','_',get_bloginfo());
		$blog_name = preg_replace('/[^A-Za-z0-9_]/','', $blog_name);
		if(!$blog_name) {
			$blog_name = 'non_alpha_name';
		}

		$backup_file_base = $updraft_dir.'/backup_'.date('Y-m-d-Hi',$this->backup_time).'_'.$blog_name.'_'.$this->nonce;
		$themes = new PclZip($backup_file_base.'-themes.zip');
		if (!$themes->create($wp_themes_dir,PCLZIP_OPT_REMOVE_PATH,WP_CONTENT_DIR)) {
			$this->error('Could not create themes zip. Error was '.$php_errmsg,'fatal');
		}
		$plugins = new PclZip($backup_file_base.'-plugins.zip');
		if (!$plugins->create($wp_plugins_dir,PCLZIP_OPT_REMOVE_PATH,WP_CONTENT_DIR)) {
			$this->error('Could not create plugins zip. Error was '.$php_errmsg,'fatal');
		}
		$uploads = new PclZip($backup_file_base.'-uploads.zip');
		if (!$uploads->create($wp_upload_dir,PCLZIP_OPT_REMOVE_PATH,WP_CONTENT_DIR)) {
			$this->error('Could not create uploads zip. Error was '.$php_errmsg,'fatal');
		}
		$backup_array = array('themes'=>basename($backup_file_base.'-themes.zip'),'plugins'=>basename($backup_file_base.'-plugins.zip'),'uploads'=>basename($backup_file_base.'-uploads.zip'));
		return $backup_array;
	}

	function save_backup_history($backup_array) {
		//this stores full paths right now.  should probably concatenate with ABSPATH to make it easier to move blogs
		$backup_history = get_option('updraft_backup_history');
		$backup_history = (!is_array($backup_history))?array():$backup_history;
		if(is_array($backup_array)) {
			$backup_history[$this->backup_time] = $backup_array;
			update_option('updraft_backup_history',$backup_history);
		} else {
			$this->error('Could not save backup history because we have no backup array.  Backup probably failed.');
		}
	}
	
	function get_backup_history() {
		//$backup_history = get_option('updraft_backup_history');
		//by doing a raw DB query to get the most up-to-date data from this option we slightly narrow the window for the multiple-cron race condition
		global $wpdb;
		$backup_history = @unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value from $wpdb->options WHERE option_name='updraft_backup_history'")));
		if(is_array($backup_history)) {
			krsort($backup_history); //reverse sort so earliest backup is last on the array.  this way we can array_pop
		} else {
			$backup_history = array();
		}
		return $backup_history;
	}
	
	
	/*START OF WB-DB-BACKUP BLOCK*/
	/*START OF WB-DB-BACKUP BLOCK*/
	/*START OF WB-DB-BACKUP BLOCK*/
	/*START OF WB-DB-BACKUP BLOCK*/
	/*START OF WB-DB-BACKUP BLOCK*/
	/*START OF WB-DB-BACKUP BLOCK*/

	function backup_db() {
		global $table_prefix, $wpdb;
		if(!$this->backup_time) {
			$this->backup_time_nonce();
		}
		$possible_names = array(
			'categories',
			'comments',
			'link2cat',
			'linkcategories',
			'links',
			'options',
			'post2cat',
			'postmeta',
			'posts',
			'terms',
			'term_taxonomy',
			'term_relationships',
			'users',
			'usermeta',
		);

		foreach( $possible_names as $name ) {
			if ( isset( $wpdb->{$name} ) ) {
				$core_table_names[] = $wpdb->{$name};
			}
		}
		
		$all_tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
		$all_tables = array_map(create_function('$a', 'return $a[0];'), $all_tables);
		$core_tables = array_intersect($all_tables, $core_table_names);
		//$other_tables = get_option('wp_cron_backup_tables');
		
		$updraft_dir = $this->check_backups_dir();
		//get the blog name and rip out all non-alphanumeric chars other than _
		$blog_name = str_replace(' ','_',get_bloginfo());
		$blog_name = preg_replace('/[^A-Za-z0-9_]/','', $blog_name);
		if(!$blog_name) {
			$blog_name = 'non_alpha_name';
		}

		$backup_file_base = $updraft_dir.'/backup_'.date('Y-m-d-Hi',$this->backup_time).'_'.$blog_name.'_'.$this->nonce;
		if (is_writable($updraft_dir)) {
			if (function_exists('gzopen')) {
				$this->dbhandle = @gzopen($backup_file_base.'-db.gz','w');
			} else {
				$this->dbhandle = @fopen($backup_file_base.'-db.gz', 'w');
			}
			if(!$this->dbhandle) {
				//$this->error(__('Could not open the backup file for writing!','wp-db-backup'));
			}
		} else {
			//$this->error(__('The backup directory is not writable!','wp-db-backup'));
		}
		
		//Begin new backup of MySql
		$this->stow("# " . __('WordPress MySQL database backup','wp-db-backup') . "\n");
		$this->stow("#\n");
		$this->stow("# " . sprintf(__('Generated: %s','wp-db-backup'),date("l j. F Y H:i T")) . "\n");
		$this->stow("# " . sprintf(__('Hostname: %s','wp-db-backup'),DB_HOST) . "\n");
		$this->stow("# " . sprintf(__('Database: %s','wp-db-backup'),$this->backquote(DB_NAME)) . "\n");
		$this->stow("# --------------------------------------------------------\n");
		
			if ( (is_array($other_tables)) && (count($other_tables) > 0) )
			$tables = array_merge($core_tables, $other_tables);
		else
			$tables = $core_tables;
		
		foreach ($tables as $table) {
			// Increase script execution time-limit to 15 min for every table.
			if ( !ini_get('safe_mode')) @set_time_limit(15*60);
			// Create the SQL statements
			$this->stow("# --------------------------------------------------------\n");
			$this->stow("# " . sprintf(__('Table: %s','wp-db-backup'),$this->backquote($table)) . "\n");
			$this->stow("# --------------------------------------------------------\n");
			$this->backup_table($table);
		}
				
		$this->close($this->dbhandle);
		
		if (count($this->errors)) {
			return false;
		} else {
			return basename($backup_file_base.'-db.gz');
		}
		
	} //wp_db_backup

	/**
	 * Taken partially from phpMyAdmin and partially from
	 * Alain Wolf, Zurich - Switzerland
	 * Website: http://restkultur.ch/personal/wolf/scripts/db_backup/
	 * Modified by Scott Merrill (http://www.skippy.net/) 
	 * to use the WordPress $wpdb object
	 * @param string $table
	 * @param string $segment
	 * @return void
	 */
	function backup_table($table, $segment = 'none') {
		global $wpdb;

		$table_structure = $wpdb->get_results("DESCRIBE $table");
		if (! $table_structure) {
			//$this->error(__('Error getting table details','wp-db-backup') . ": $table");
			return false;
		}
	
		if(($segment == 'none') || ($segment == 0)) {
			// Add SQL statement to drop existing table
			$this->stow("\n\n");
			$this->stow("#\n");
			$this->stow("# " . sprintf(__('Delete any existing table %s','wp-db-backup'),$this->backquote($table)) . "\n");
			$this->stow("#\n");
			$this->stow("\n");
			$this->stow("DROP TABLE IF EXISTS " . $this->backquote($table) . ";\n");
			
			// Table structure
			// Comment in SQL-file
			$this->stow("\n\n");
			$this->stow("#\n");
			$this->stow("# " . sprintf(__('Table structure of table %s','wp-db-backup'),$this->backquote($table)) . "\n");
			$this->stow("#\n");
			$this->stow("\n");
			
			$create_table = $wpdb->get_results("SHOW CREATE TABLE $table", ARRAY_N);
			if (false === $create_table) {
				$err_msg = sprintf(__('Error with SHOW CREATE TABLE for %s.','wp-db-backup'), $table);
				//$this->error($err_msg);
				$this->stow("#\n# $err_msg\n#\n");
			}
			$this->stow($create_table[0][1] . ' ;');
			
			if (false === $table_structure) {
				$err_msg = sprintf(__('Error getting table structure of %s','wp-db-backup'), $table);
				//$this->error($err_msg);
				$this->stow("#\n# $err_msg\n#\n");
			}
		
			// Comment in SQL-file
			$this->stow("\n\n");
			$this->stow("#\n");
			$this->stow('# ' . sprintf(__('Data contents of table %s','wp-db-backup'),$this->backquote($table)) . "\n");
			$this->stow("#\n");
		}
		
		if(($segment == 'none') || ($segment >= 0)) {
			$defs = array();
			$ints = array();
			foreach ($table_structure as $struct) {
				if ( (0 === strpos($struct->Type, 'tinyint')) ||
					(0 === strpos(strtolower($struct->Type), 'smallint')) ||
					(0 === strpos(strtolower($struct->Type), 'mediumint')) ||
					(0 === strpos(strtolower($struct->Type), 'int')) ||
					(0 === strpos(strtolower($struct->Type), 'bigint')) ) {
						$defs[strtolower($struct->Field)] = ( null === $struct->Default ) ? 'NULL' : $struct->Default;
						$ints[strtolower($struct->Field)] = "1";
				}
			}
			
			
			// Batch by $row_inc
			if ( ! defined('ROWS_PER_SEGMENT') ) {
				define('ROWS_PER_SEGMENT', 100);
			}
			
			if($segment == 'none') {
				$row_start = 0;
				$row_inc = ROWS_PER_SEGMENT;
			} else {
				$row_start = $segment * ROWS_PER_SEGMENT;
				$row_inc = ROWS_PER_SEGMENT;
			}
			do {	
				// don't include extra stuff, if so requested
				$excs = array('revisions' => 0, 'spam' => 1); //TODO, FIX THIS
				$where = '';
				if ( is_array($excs['spam'] ) && in_array($table, $excs['spam']) ) {
					$where = ' WHERE comment_approved != "spam"';
				} elseif ( is_array($excs['revisions'] ) && in_array($table, $excs['revisions']) ) {
					$where = ' WHERE post_type != "revision"';
				}
				
				if ( !ini_get('safe_mode')) @set_time_limit(15*60);
				$table_data = $wpdb->get_results("SELECT * FROM $table $where LIMIT {$row_start}, {$row_inc}", ARRAY_A);
				$entries = 'INSERT INTO ' . $this->backquote($table) . ' VALUES (';	
				//    \x08\\x09, not required
				$search = array("\x00", "\x0a", "\x0d", "\x1a");
				$replace = array('\0', '\n', '\r', '\Z');
				if($table_data) {
					foreach ($table_data as $row) {
						$values = array();
						foreach ($row as $key => $value) {
							if ($ints[strtolower($key)]) {
								// make sure there are no blank spots in the insert syntax,
								// yet try to avoid quotation marks around integers
								$value = ( null === $value || '' === $value) ? $defs[strtolower($key)] : $value;
								$values[] = ( '' === $value ) ? "''" : $value;
							} else {
								$values[] = "'" . str_replace($search, $replace, $this->sql_addslashes($value)) . "'";
							}
						}
						$this->stow(" \n" . $entries . implode(', ', $values) . ');');
					}
					$row_start += $row_inc;
				}
			} while((count($table_data) > 0) and ($segment=='none'));
		}
		
		if(($segment == 'none') || ($segment < 0)) {
			// Create footer/closing comment in SQL-file
			$this->stow("\n");
			$this->stow("#\n");
			$this->stow("# " . sprintf(__('End of data contents of table %s','wp-db-backup'),$this->backquote($table)) . "\n");
			$this->stow("# --------------------------------------------------------\n");
			$this->stow("\n");
		}
	} // end backup_table()


	function stow($query_line) {
		if (function_exists('gzopen')) {
			if(! @gzwrite($this->dbhandle, $query_line)) {
				//$this->error(__('There was an error writing a line to the backup script:','wp-db-backup') . '  ' . $query_line . '  ' . $php_errormsg);
			}
		} else {
			if(false === @fwrite($this->dbhandle, $query_line)) {
				//$this->error(__('There was an error writing a line to the backup script:','wp-db-backup') . '  ' . $query_line . '  ' . $php_errormsg);
			}
		}
	}


	function close($handle) {
		if (function_exists('gzopen')) {
			gzclose($handle);
		} else {
			fclose($handle);
		}
	}

	/**
	 * Logs any error messages
	 * @param array $args
	 * @return bool
	 */
	function error($error,$severity='') {
		$this->errors[] = array('error'=>$error,'severity'=>$severity);
		if ($severity == 'fatal') {
			//do something...
		}
		return true;
	}



	/**
	 * Add backquotes to tables and db-names in
	 * SQL queries. Taken from phpMyAdmin.
	 */
	function backquote($a_name) {
		if (!empty($a_name) && $a_name != '*') {
			if (is_array($a_name)) {
				$result = array();
				reset($a_name);
				while(list($key, $val) = each($a_name)) 
					$result[$key] = '`' . $val . '`';
				return $result;
			} else {
				return '`' . $a_name . '`';
			}
		} else {
			return $a_name;
		}
	}

	/**
	 * Better addslashes for SQL queries.
	 * Taken from phpMyAdmin.
	 */
	function sql_addslashes($a_string = '', $is_like = false) {
		if ($is_like) $a_string = str_replace('\\', '\\\\\\\\', $a_string);
		else $a_string = str_replace('\\', '\\\\', $a_string);
		return str_replace('\'', '\\\'', $a_string);
	} 

	/*END OF WP-DB-BACKUP BLOCK */
	/*END OF WP-DB-BACKUP BLOCK */
	/*END OF WP-DB-BACKUP BLOCK */
	/*END OF WP-DB-BACKUP BLOCK */
	/*END OF WP-DB-BACKUP BLOCK */

	/*
	this function is both the backup scheduler and ostensibly a filter callback for saving the option.
	it is called in the register_setting for the updraft_interval, which means when the admin settings 
	are saved it is called.  it returns the actual result from wp_filter_nohtml_kses (a sanitization filter) 
	so the option can be properly saved.  this is an UGLY HACK and there must be a better way.
	*/
	function schedule_backup($interval) {
		//clear schedule and add new so we don't stack up scheduled backups
		wp_clear_scheduled_hook('updraft_backup');
		switch($interval) {
			case 'daily':
			case 'weekly':
			case 'monthly':
				wp_schedule_event(time()+300, $interval, 'updraft_backup');
			break;
		}
		return wp_filter_nohtml_kses($interval);
	}

	//wp-cron only has hourly, daily and twicedaily, so we need to add weekly and monthly. 
	function modify_cron_schedules($schedules) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => 'Once Weekly'
		);
		$schedules['monthly'] = array(
			'interval' => 2592000,
			'display' => 'Once Monthly'
		);
		return $schedules;
	}
	
	function check_backups_dir() {
		$updraft_dir = untrailingslashit(get_option('updraft_dir'));
		$default_backup_dir = WP_CONTENT_DIR.'/updraft';
		//if the option isn't set, default it to /backups inside the upload dir
		$updraft_dir = ($updraft_dir)?$updraft_dir:$default_backup_dir;
		//check for the existence of the dir and an enumeration preventer.
		if(!is_dir($updraft_dir) || !is_file($updraft_dir.'/index.html') || !is_file($updraft_dir.'/.htaccess')) {
			@mkdir($updraft_dir,0777,true); //recursively create the dir with 0777 permissions. 0777 is default for php creation.  not ideal, but I'll get back to this
			@file_put_contents($updraft_dir.'/index.html','Nothing to see here.');
			@file_put_contents($updraft_dir.'/.htaccess','deny from all');
		}
		return $updraft_dir;
	}
	
	function updraft_download_backup() {
		$type = $_POST['type'];
		$timestamp = (int)$_POST['timestamp'];
		$backup_history = $this->get_backup_history();
		$file = $backup_history[$timestamp][$type];
		$fullpath = trailingslashit(get_option('updraft_dir')).$file;
		if(!is_readable($fullpath)) {
			//if the file doesn't exist and they're using one of the cloud options, fetch it down from the cloud.
			$this->download_backup($file);
		}
		if(@is_readable($fullpath) && is_file($fullpath)) {
			$len = filesize($fullpath);

			$filearr = explode('.',$file);
			//we've only got zip and gz...for now
			if(array_pop($filearr) == 'zip') {
				header('Content-type: application/zip');
			} else {
				header('Content-type: application/x-gzip');
			}
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			header("Content-Length: $len;");
			header("Content-Disposition: attachment; filename=\"$file\";");
			ob_end_flush();
			readfile($fullpath);
			$this->delete_local($file);
			exit; //we exit immediately because otherwise admin-ajax appends an additional zero to the end for some reason I don't understand. seriously, why die('0')?
		} else {
			echo 'Download failed.  File '.$fullpath.' did not exist or was unreadable.  If you delete local backups then S3, CF, or FTP retrieval may have failed.';
		}
	}
	
	function download_backup($file) {
		switch(get_option('updraft_service')) {
			case 'cloudfiles':
				$this->download_cf_backup($file);
			break;
			case 's3':
				$this->download_s3_backup($file);
			break;
			case 'ftp':
				$this->download_ftp_backup($file);
			break;
			default:
				$this->error('Automatic backup restoration is only available via Cloud Files, S3, FTP, and local. Email and downloaded backup restoration must be performed manually.');
		}
	}
	
	function download_cf_backup($file) {
		if(!class_exists('CF_Authentication')) {
			require_once(dirname(__FILE__).'/includes/cloudfiles/cloudfiles.php');
		}
		$auth = new CF_Authentication(get_option('updraft_cf_login'),get_option('updraft_cf_pass'));
		try {
			$auth->authenticate();
		}
		catch(AuthenticationException $e) {
			$this->error('Authentication to CF failed. '.$e->getMessage());
		}
		$conn = new CF_Connection($auth);
		$cf_remote_path = untrailingslashit(get_option('updraft_cf_remote_path'));
		try {
			$container = $conn->create_container($cf_remote_path);
		}
		catch(Exception $e) {
			//we need some error handling
		}
		//catch more exceptions here
		$object = $container->create_object($file);
		$filepath = trailingslashit(get_option('updraft_dir')).$file;
		$object->save_to_filename($filepath);
	}
	
	function download_s3_backup($file) {
		if(!class_exists('S3')) {
			require_once(dirname(__FILE__).'/includes/S3.php');
		}
		$s3 = new S3(get_option('updraft_s3_login'), get_option('updraft_s3_pass'));
		$bucket_name = untrailingslashit(get_option('updraft_s3_remote_path'));
		if (@$s3->putBucket($bucket_name, S3::ACL_PRIVATE)) {
			$fullpath = trailingslashit(get_option('updraft_dir')).$file;
			if (!$s3->getObject($bucket_name, $file, $fullpath)) {
				$this->error("S3 Error: Failed to download $fullpath. Error was ".$php_errormsg);
			}
		} else {
			$this->error("S3 Error: Failed to create bucket $bucket_name. Error was ".$php_errormsg);
		}
	}
	
	function download_ftp_backup($file) {
		if( !class_exists('ftp_wrapper')) {
			require_once(dirname(__FILE__).'/includes/ftp.class.php');
		}
		//handle SSL and errors at some point TODO
		$ftp = new ftp_wrapper(get_option('updraft_server_address'),get_option('updraft_ftp_login'),get_option('updraft_ftp_pass'));
		$ftp->passive = true;
		$ftp->connect();
		//$ftp->make_dir(); we may need to recursively create dirs? TODO
		
		$ftp_remote_path = trailingslashit(get_option('updraft_ftp_remote_path'));
		$fullpath = trailingslashit(get_option('updraft_dir')).$file;
		$ftp->get($fullpath,$ftp_remote_path.$file,FTP_BINARY);
	}
	
	function restore_backup($timestamp) {
		global $wp_filesystem;
		$backup_history = get_option('updraft_backup_history');
		if(!is_array($backup_history[$timestamp])) {
			echo '<p>This backup does not exist in the backup history -- restoration aborted!  timestamp: '.$timestamp.'</p><br/>';
			return false;
		}

		$credentials = request_filesystem_credentials("options-general.php?page=updraft-backuprestore.php&action=updraft_restore&backup_timestamp=$timestamp"); 
		WP_Filesystem($credentials);
		if ( $wp_filesystem->errors->get_error_code() ) { 
			foreach ( $wp_filesystem->errors->get_error_messages() as $message )
				show_message($message); 
			exit; 
		}
		
		//if we make it this far then WP_Filesystem has been instantiated and is functional (tested with ftpext, what about suPHP and other situations where direct may work?)
		echo '<span style="font-weight:bold">Restoration Progress </span><div id="updraft-restore-progress">';

		$updraft_dir = trailingslashit(get_option('updraft_dir'));
		foreach($backup_history[$timestamp] as $type=>$file) {
			$fullpath = $updraft_dir.$file;
			if(!is_readable($fullpath) && $type != 'db') {
				$this->download_backup($file);
			}
			if(is_readable($fullpath) && $type != 'db') {
				if(!class_exists('WP_Upgrader')) {
					require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
				}
				require_once('includes/updraft-restorer.php');
				$restorer = new Updraft_Restorer();
				$val = $restorer->restore_backup($fullpath,$type);
				if(is_wp_error($val)) {
					print_r($val);
					echo '</div>'; //close the updraft_restore_progress div even if we error
					return false;
				}
			}
		}
		echo '</div>'; //close the updraft_restore_progress div
		if(ini_get('safe_mode')) {
			echo "<p>DB could not be restored because safe_mode is active on your server.  You will need to manually restore the file via phpMyAdmin or another method.</p><br/>";
			return false;
		}
		return true;
	}


	//deletes the -old directories that are created when a backup is restored.
	function delete_old_dirs() {
		global $wp_filesystem;
		$credentials = request_filesystem_credentials("options-general.php?page=updraft-backuprestore.php&action=updraft_delete_old_dirs"); 
		WP_Filesystem($credentials);
		if ( $wp_filesystem->errors->get_error_code() ) { 
			foreach ( $wp_filesystem->errors->get_error_messages() as $message )
				show_message($message); 
			exit; 
		}
		
		$to_delete = array('themes-old','plugins-old','uploads-old');

		foreach($to_delete as $name) {
			//recursively delete
			if(!$wp_filesystem->delete(WP_CONTENT_DIR.'/'.$name, true)) {
				return false;
			}
		}
		return true;
	}
	
	//scans the content dir to see if any -old dirs are present
	function scan_old_dirs() {
		$dirArr = scandir(WP_CONTENT_DIR);
		foreach($dirArr as $dir) {
			if(strpos($dir,'-old') !== false) {
				return true;
			}
		}
		return false;
	}
	
	
	function retain_range($input) {
		$input = (int)$input;
		if($input > 0 && $input < 3650) {
			return $input;
		} else {
			return 1;
		}
	}
	
	function create_backup_dir() {
		global $wp_filesystem;
		$credentials = request_filesystem_credentials("options-general.php?page=updraft-backuprestore.php&action=updraft_create_backup_dir"); 
		WP_Filesystem($credentials);
		if ( $wp_filesystem->errors->get_error_code() ) { 
			foreach ( $wp_filesystem->errors->get_error_messages() as $message )
				show_message($message); 
			exit; 
		}

		$updraft_dir = untrailingslashit(get_option('updraft_dir'));
		$default_backup_dir = WP_CONTENT_DIR.'/updraft';
		$updraft_dir = ($updraft_dir)?$updraft_dir:$default_backup_dir;

		//chmod the backup dir to 0777. ideally we'd rather chgrp it but i'm not sure if it's possible to detect the group apache is running under (or what if it's not apache...)
		if(!$wp_filesystem->mkdir($updraft_dir, 0777)) {
			return false;
		}
		return true;
	}
	

	function memory_check($memory) {
		$memory_limit = ini_get('memory_limit');
		$memory_unit = $memory_limit[strlen($memory_limit)-1];
		$memory_limit = substr($memory_limit,0,strlen($memory_limit)-1);
		switch($memory_unit) {
			case 'K':
				$memory_limit = $memory_limit/1024;
			break;
			case 'G':
				$memory_limit = $memory_limit*1024;
			break;
			case 'M':
				//assumed size, no chane needed
			break;
		}
		return ($memory_limit >= $memory)?true:false;
	}

	function execution_time_check($time) {
		return (ini_get('max_execution_time') >= $time)?true:false;
	}

	function admin_init() {
		if(get_option('updraft_debug_mode')) {
			ini_set('display_errors',1);
			error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
			ini_set('track_errors',1);
		}
		wp_enqueue_script('jquery');
		register_setting( 'updraft-options-group', 'updraft_interval', array($this,'schedule_backup') );
		register_setting( 'updraft-options-group', 'updraft_retain', array($this,'retain_range') );
		register_setting( 'updraft-options-group', 'updraft_service', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_cf_login', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_cf_pass', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_s3_login', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_s3_pass', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_ftp_login', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_ftp_pass', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_dir', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_email', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_cf_remote_path', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_s3_remote_path', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_ftp_remote_path', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_server_address', 'wp_filter_nohtml_kses' );
		register_setting( 'updraft-options-group', 'updraft_delete_local', 'absint' );
		register_setting( 'updraft-options-group', 'updraft_email_complete', 'absint' );
		register_setting( 'updraft-options-group', 'updraft_debug_mode', 'absint' );
		
	}
	

	function add_admin_pages() {
		add_submenu_page('options-general.php', "Updraft", "Updraft", 10, "updraft-backuprestore.php",
		array($this,"settings_output"));
	}

	function settings_output() {
		/*
		we use request here because the initial restore is triggered by a POSTed form. we then may need to obtain credentials 
		for the WP_Filesystem. to do this WP outputs a form that we can't insert variables into (apparently). So the values are 
		passed back in as GET parameters. REQUEST covers both GET and POST so this weird logic works.
		*/
		if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'updraft_restore' && isset($_REQUEST['backup_timestamp'])) {
			$backup_success = $this->restore_backup($_REQUEST['backup_timestamp']);
			if(empty($this->errors) && $backup_success == true) {
				echo '<p>Restore successful!</p><br/>';
				echo '<b>Actions:</b> <a href="options-general.php?page=updraft-backuprestore.php&updraft_restore_success=true">Return to Updraft Configuration</a>.';
				return;
			} else {
				echo '<p>Restore failed...</p><br/>';
				echo '<b>Actions:</b> <a href="options-general.php?page=updraft-backuprestore.php">Return to Updraft Configuration</a>.';
				return;
			}
			//uncomment the below once i figure out how i want the flow of a restoration to work.
			//echo '<b>Actions:</b> <a href="options-general.php?page=updraft-backuprestore.php">Return to Updraft Configuration</a>.';
		}
		if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'updraft_delete_old_dirs') {
			if($this->delete_old_dirs()) {
				$deleted_old_dirs = true;
			} else {
				echo '<p>Old directory removal failed for some reason. You may want to do this manually.</p><br/>';
			}
			echo '<p>Old directories successfully removed.</p><br/>';
			echo '<b>Actions:</b> <a href="options-general.php?page=updraft-backuprestore.php">Return to Updraft Configuration</a>.';
			return;
		}
		
		if(isset($_GET['action']) && $_GET['action'] == 'updraft_create_backup_dir') {
			if(!$this->create_backup_dir()) {
				echo '<p>Backup directory could not be created...</p><br/>';
			}
			echo '<p>Backup directory successfully created.</p><br/>';
			echo '<b>Actions:</b> <a href="options-general.php?page=updraft-backuprestore.php">Return to Updraft Configuration</a>.';
			return;
		}
		
		if(isset($_POST['action']) && $_POST['action'] == 'updraft_backup') {
			wp_schedule_single_event(time()+3, 'updraft_backup');
		}
		if(isset($_POST['action']) && $_POST['action'] == 'updraft_backup_debug_all') {
			$this->backup();
		}
		if(isset($_POST['action']) && $_POST['action'] == 'updraft_backup_debug_db') {
			$this->backup_db();
		}
		?>
		<div class="wrap">
			<h2>Updraft - Backup/Restore</h2>
			By <b>Paul Kehrer</b> ( <a href="http://langui.sh" target="_blank">Blog</a> | <a href="http://twitter.com/reaperhulk" target="_blank">Twitter</a> )
			<br/>
			<?php
			if($_GET['updraft_restore_success']) {
				echo "<div style=\"color:blue\">Your backup has been restored.  Your old themes, uploads, and plugins directories have been retained with \"-old\" appended to their name.  Remove them when you are satisfied that the backup worked properly.  At this time Updraft does not automatically restore your DB.  You will need to use an external tool like phpMyAdmin to perform that task.</div>";
			}
			if($deleted_old_dirs) {
				echo "<div style=\"color:blue\">Old directories successfully deleted.</div>";
			}
			if(!$this->memory_check(96)) {?>
				<div style="color:orange">Your PHP memory limit is too low.  Updraft attempted to raise it but was unsuccessful.  This plugin may not work properly with a memory limit of less than 96MB.</div>
			<?php
			}
			if(!$this->execution_time_check(300)) {?>
				<div style="color:orange">Your PHP max_execution_time is less than 300 seconds. This probably means you're running in safe_mode. Either disable safe_mode or modify your php.ini to set max_execution_time to a higher number. If you do not, there is a chance Updraft will be unable to complete a backup.</div>
			<?php
			}

			if($this->scan_old_dirs()) {?>
				<div style="color:orange">You have old directories from a previous backup.  Click to delete them after you have verified that the restoration worked.</div>
				<form method="post" action="<?php echo remove_query_arg(array('updraft_restore_success','action')) ?>">
					<input type="hidden" name="action" value="updraft_delete_old_dirs" />
					<input type="submit" class="button-primary" value="Delete Old Dirs" onclick="return(confirm('Are you sure you want to delete the old directories?  This cannot be undone.'))" />
				</form>
			<?php
			}
			if(!empty($this->errors)) {
				foreach($this->errors as $error) {
					//ignoring severity here right now
					echo '<div style="color:red">'.$error['error'].'</div>';
				}
			}
			?>
			<p><i>Click any label for help!</i></p>
			<table class="form-table" style="float:left;width:475px">
				<tr>
					<?php
					$next_scheduled_backup = wp_next_scheduled('updraft_backup');
					if($next_scheduled_backup) {
						$next_scheduled_backup = date('D, F j, Y H:i T',$next_scheduled_backup);
					} else {
						$next_scheduled_backup = 'No backups are scheduled at this time.';
					}
					$current_time = date('D, F j, Y H:i T',time());
					$updraft_last_backup = get_option('updraft_last_backup');
					if($updraft_last_backup) {
						if($updraft_last_backup['success']) {
							$last_backup = date('D, F j, Y H:i T',$updraft_last_backup['backup_time']);
							$last_backup_color = 'green';
						} else {
							$last_backup = print_r($updraft_last_backup['errors'],true);
							$last_backup_color = 'red';
						}
					} else {
						$last_backup = 'No backup has been completed.';
						$last_backup_color = 'blue';
					}

					$updraft_dir = $this->check_backups_dir();
					if(is_writable($updraft_dir)) {
						$dir_info = '<span style="color:green">Backup directory specified is writable.</span>';
					} else {
						$backup_disabled = 'disabled="disabled"';
						$dir_info = '<span style="color:red">Backup directory specified is <b>not</b> writable. <span style="font-size:110%;font-weight:bold"><a href="options-general.php?page=updraft-backuprestore.php&action=updraft_create_backup_dir">Click here</a></span> to attempt to create the directory and set the permissions.  If that is unsuccessful check the permissions on your server or change it to another directory that is writable by your web server process.</span>';
					}
					if(strpos($updraft_dir,WP_CONTENT_DIR) !== false) {
						$relative_dir = str_replace(WP_CONTENT_DIR,'',$updraft_dir);
						$possible_updraft_url = WP_CONTENT_URL.$relative_dir;
						require_once( ABSPATH . 'wp-includes/class-snoopy.php');
						$snoopy = new Snoopy();
						$snoopy->fetch($possible_updraft_url);
						if(strpos($snoopy->response_code,'403') === false) {
							$dir_protection_info = '<span style="color:orange">Backup directory specified is accessible via the web.  This is a potential security problem.  Enable .htaccess support to allow Updraft to automatically deny web access.</span>';
						}
					}
					?>
					<th>Current Time:</th>
					<td style="color:blue"><?php echo $current_time?></td>
				</tr>
				<tr>
					<th>Next Scheduled Backup:</th>
					<td style="color:blue"><?php echo $next_scheduled_backup?></td>
				</tr>
				<tr>
					<th>Last Backup:</th>
					<td style="color:<?php echo $last_backup_color ?>"><?php echo $last_backup?></td>
				</tr>
			</table>
			<div style="float:left;width:200px">
				<form method="post" action="">
					<input type="hidden" name="action" value="updraft_backup" />
					<p><input type="submit" <?php echo $backup_disabled ?> class="button-primary" value="Backup Now!" style="padding-top:7px;padding-bottom:7px;font-size:24px !important" onclick="return(confirm('This will schedule a one time backup.  To trigger the backup immediately you may need to load a page on your blog.'))" /></p>
				</form>
				<div style="position:relative">
					<div style="position:absolute;top:0;left:0">
						<?php
						$backup_history = get_option('updraft_backup_history');
						$backup_history = (is_array($backup_history))?$backup_history:array();
						if (count($backup_history) == 0) {
							$restore_disabled = 'disabled="disabled"';
						}
						?>
						<input type="button" class="button-primary" <?php echo $restore_disabled ?> value="Restore" style="padding-top:7px;padding-bottom:7px;font-size:24px !important" onclick="jQuery('#backup-restore').fadeIn('slow');jQuery(this).parent().fadeOut('slow')" />
					</div>
					<div style="display:none;position:absolute;top:0;left:0" id="backup-restore">
						<form method="post" action="">
							<b>Choose: </b>
							<select name="backup_timestamp" style="display:inline">
								<?php
								foreach($backup_history as $key=>$value) {
									echo "<option value='$key'>".date('Y-m-d G:i',$key)."</option>\n";
								}
								?>
							</select>

							<input type="hidden" name="action" value="updraft_restore" />
							<input type="submit" <?php echo $restore_disabled ?> class="button-primary" value="Restore Now!" style="padding-top:7px;margin-top:5px;padding-bottom:7px;font-size:24px !important" onclick="return(confirm('Restoring from backup will replace this blog\'s themes, plugins, and uploads directories. DB restoration must be done separately at this time. Continue with the restoration process?'))" />
						</form>
					</div>
				</div>
			</div>
			<br style="clear:both" />
			<table class="form-table">
				<tr>
					<th><a href="#" title="Click for help!" onclick="jQuery('.download-backups').toggle();return false;">Download Backups</a></th>
					<td><a href="#" title="Click for help!" onclick="jQuery('.download-backups').toggle();return false;"><?php echo count($backup_history)?> available</a></td>
				</tr>
				<tr>
					<td colspan="2" class="download-backups" style="display:none">
						<table>
							<?php
							foreach($backup_history as $key=>$value) {
							?>
							<tr>
								<td><b><?php echo date('Y-m-d G:i',$key)?></b></td>
								<td>
									<form action="admin-ajax.php" method="post">
										<input type="hidden" name="action" value="updraft_download_backup" />
										<input type="hidden" name="type" value="db" />
										<input type="hidden" name="timestamp" value="<?php echo $key?>" />
										<input type="submit" value="Database" />
									</form>
								</td>
								<td>
									<form action="admin-ajax.php" method="post">
										<input type="hidden" name="action" value="updraft_download_backup" />
										<input type="hidden" name="type" value="plugins" />
										<input type="hidden" name="timestamp" value="<?php echo $key?>" />
										<input type="submit" value="Plugins" />
									</form>
								</td>
								<td>
									<form action="admin-ajax.php" method="post">
										<input type="hidden" name="action" value="updraft_download_backup" />
										<input type="hidden" name="type" value="themes" />
										<input type="hidden" name="timestamp" value="<?php echo $key?>" />
										<input type="submit" value="Themes" />
									</form>
								</td>
								<td>
									<form action="admin-ajax.php" method="post">
										<input type="hidden" name="action" value="updraft_download_backup" />
										<input type="hidden" name="type" value="uploads" />
										<input type="hidden" name="timestamp" value="<?php echo $key?>" />
										<input type="submit" value="Uploads" />
									</form>
								</td>
							</tr>
							<?php }?>
						</table>
					</td>
				</tr>
			</table>
			<form method="post" action="options.php">
			<?php settings_fields('updraft-options-group'); ?>
			<table class="form-table">
				<tr>
					<th><a href="#" title="Click for help!" onclick="jQuery('.backup-directory-description').toggle();return false;">Backup Directory:</a></th>
					<td><input type="text" name="updraft_dir" style="width:475px" value="<?php echo $updraft_dir ?>" /></td>
				</tr>
				<tr>
					<td colspan="2"><?php echo $dir_info ?></td>
				</tr>
				<tr>
					<td colspan="2"><?php echo $dir_protection_info ?></td>
				</tr>
				<tr style="display:none" class="backup-directory-description">
					<td colspan="2">This is where Updraft Backup/Restore will write the zip files it creates initially.  This directory must be writable by your web server.  Typically you'll want to have it inside your wp-content folder (this is the default).  <b>Do not</b> place it inside your uploads dir, as that will cause recursion issues.</td>
				</tr>
				<tr>
					<th><a href="#" title="Click for help!" onclick="jQuery('.backup-interval-description').toggle();return false;">Backup Intervals:</a></th>
					<td><select name="updraft_interval">
						<?php
						$set = 'selected="selected"';
						switch(get_option('updraft_interval')) {
							case 'manual':
								$manual = $set;
							break;
							case 'daily':
								$daily = $set;
							break;
							case 'weekly':
								$weekly = $set;
							break;
							case 'monthly':
								$monthly = $set;
							break;
						}
						?>
						<option value="manual" <?php echo $manual?>>Manual</option>
						<option value="daily" <?php echo $daily?>>Daily</option>
						<option value="weekly" <?php echo $weekly?>>Weekly</option>
						<option value="monthly" <?php echo $monthly?>>Monthly</option>
						</select></td>
				</tr>
				<tr class="backup-interval-description" style="display:none">
					<td colspan="2">If you would like to automatically schedule backups, choose a schedule from the dropdown above. Backups will occur at the interval specified starting five minutes after the current time.  If you choose manual you must click the backup now button to cause a backup to occur.</td>
				</tr>
				<tr>
					<th><a href="#" title="Click for help!" onclick="jQuery('.backup-retain-description').toggle();return false;">Retain Backups:</a></th>
					<?php
					$updraft_retain = get_option('updraft_retain');
					$retain = ((int)$updraft_retain > 0)?get_option('updraft_retain'):1;
					?>
					<td><input type="text" name="updraft_retain" value="<?php echo $retain ?>" style="width:50px" /></td>
				</tr>
				<tr class="backup-retain-description" style="display:none">
					<td colspan="2">By default Updraft Backup/Restore retains only the most recent backup.  If you'd like to preserve more, specify the number here.</td>
				</tr>
				<tr>
					<th><a href="#" title="Click for help!" onclick="jQuery('.backup-service-description').toggle();return false;">Cloud Backup Service:</a></th>
					<td><select name="updraft_service" id="updraft-service">
						<?php
						if(get_option('updraft_delete_local')) {
							$delete_local = 'checked="checked"';
						}
						if(get_option('updraft_email_complete')) {
							$email_complete = 'checked="checked"';
						}
						if(get_option('updraft_debug_mode')) {
							$debug_mode = 'checked="checked"';
						}
						$display_none = 'style="display:none"';
						switch(get_option('updraft_service')) {
							case 'cloudfiles':
								$cloudfiles = $set;
								$ftp_display = $display_none;
								$s3_display = $display_none;
							break;
							case 's3':
								$s3 = $set;
								$ftp_display = $display_none;
								$cf_display = $display_none;
							break;
							case 'ftp':
								$ftp = $set;
								$s3_display = $display_none;
								$cf_display = $display_none;
							break;
							case 'email':
								$email = $set;
								$ftp_display = $display_none;
								$cf_display = $display_none;
								$s3_display = $display_none;
								$display_email_complete = $display_none;
							break;
							default:
								$none = $set;
								$ftp_display = $display_none;
								$cf_display = $display_none;
								$s3_display = $display_none;
								$display_delete_local = $display_none;
							break;
						}
						?>
						<option value="none" <?php echo $none?>>None</option>
						<option value="cloudfiles" <?php echo $cloudfiles?>>Rackspace Cloud Files</option>
						<option value="s3" <?php echo $s3?>>Amazon S3</option>
						<option value="ftp" <?php echo $ftp?>>FTP</option>
						<option value="email" <?php echo $email?>>E-mail</option>
						</select></td>
				</tr>
				<tr class="backup-service-description" style="display:none">
					<td colspan="2">Choose which backup method you would like to employ.  Be aware that email servers tend to have strict file size limitations and it is possible you will not receive your backup emails (>10MB is a typical threshold).  Select none if you do not wish to send your backups anywhere.  <b>Not recommended.</b></td>
				
				</tr>
				<tr class="cf" <?php echo $cf_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.cf-description').toggle();return false;">CF Username:</a></th>
					<td><input type="text" autocomplete="off" name="updraft_cf_login" value="<?php echo get_option('updraft_cf_login') ?>" /></td>
				</tr>
				<tr class="cf" <?php echo $cf_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.cf-description').toggle();return false;">CF API Key:</a></th>
					<td><input type="password" autocomplete="off" style="width:260px" name="updraft_cf_pass" value="<?php echo get_option('updraft_cf_pass'); ?>" /></td>
				</tr>
				<tr class="cf" <?php echo $cf_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.cf-description').toggle();return false;">CF Container:</a></th>
					<td><input type="text" style="width:260px" name="updraft_cf_remote_path" value="<?php echo get_option('updraft_cf_remote_path'); ?>" /></td>
				</tr>
				<tr class="cf-description" style="display:none">
					<td colspan="2">A CF container will be something like "updraft".  This will create a container at your CF root with the name "updraft".</td>
				</tr>
				<tr class="s3" <?php echo $s3_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.s3-description').toggle();return false;">S3 Access Key:</a></th>
					<td><input type="text" autocomplete="off" style="width:260px" name="updraft_s3_login" value="<?php echo get_option('updraft_s3_login') ?>" /></td>
				</tr>
				<tr class="s3" <?php echo $s3_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.s3-description').toggle();return false;">S3 Secret Key:</a></th>
					<td><input type="password" autocomplete="off" style="width:260px" name="updraft_s3_pass" value="<?php echo get_option('updraft_s3_pass'); ?>" /></td>
				</tr>
				<tr class="s3" <?php echo $s3_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.s3-description').toggle();return false;">S3 Bucket:</a></th>
					<td><input type="text" style="width:260px" name="updraft_s3_remote_path" value="<?php echo get_option('updraft_s3_remote_path'); ?>" /></td>
				</tr>
				<tr class="s3-description" style="display:none">
					<td colspan="2">Get your access key and secret key from your AWS page, then pick a bucket name (like "updraft") for Updraft Backup/Restore to use for storage.</td>
				</tr>
				<tr class="ftp" <?php echo $ftp_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.ftp-description').toggle();return false;">FTP Server:</a></th>
					<td><input type="text" style="width:260px" name="updraft_server_address" value="<?php echo get_option('updraft_server_address'); ?>" /></td>
				</tr>
				<tr class="ftp" <?php echo $ftp_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.ftp-description').toggle();return false;">FTP Login:</a></th>
					<td><input type="text" autocomplete="off" name="updraft_ftp_login" value="<?php echo get_option('updraft_ftp_login') ?>" /></td>
				</tr>
				<tr class="ftp" <?php echo $ftp_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.ftp-description').toggle();return false;">FTP Password:</a></th>
					<td><input type="password" autocomplete="off" style="width:260px" name="updraft_ftp_pass" value="<?php echo get_option('updraft_ftp_pass'); ?>" /></td>
				</tr>
				<tr class="ftp" <?php echo $ftp_display?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.ftp-description').toggle();return false;">Remote Path:</a></th>
					<td><input type="text" style="width:260px" name="updraft_ftp_remote_path" value="<?php echo get_option('updraft_ftp_remote_path'); ?>" /></td>
				</tr>
				<tr class="ftp-description" style="display:none">
					<td colspan="2">An FTP remote path will look like '/home/backup/some/folder'</td>
				</tr>
				<tr class="email-complete" <?php echo $display_email_complete?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.email-complete-description').toggle();return false;">Email When Complete:</a></th>
					<td><input type="checkbox" name="updraft_email_complete" value="1" <?php echo $email_complete; ?> /></td>
				</tr>
				<tr class="email-complete-description" style="display:none">
					<td colspan="2">Check this to receive an email at the address below when a backup completes.</td>
				</tr>
				<tr class="email" <?php echo $email_display?>>
					<th>Email:</th>
					<td><input type="text" style="width:260px" name="updraft_email" value="<?php echo get_option('updraft_email'); ?>" /></td>
				</tr>
				<tr class="s3 ftp cloudfiles email delete-local" <?php echo $display_delete_local?>>
					<th><a href="#" title="Click for help!" onclick="jQuery('.local-backup-description').toggle();return false;">Delete Local Backup:</a></th>
					<td><input type="checkbox" name="updraft_delete_local" value="1" <?php echo $delete_local; ?> /></td>
				</tr>
				<tr class="local-backup-description" style="display:none">
					<td colspan="2">Check this to delete the local backup file after it has been sent off the server.</td>
				</tr>
				<tr>
					<th><a href="#" title="Click for help!" onclick="jQuery('.debug-mode-description').toggle();return false;">Debug Mode:</a></th>
					<td><input type="checkbox" name="updraft_debug_mode" value="1" <?php echo $debug_mode; ?> /></td>
				</tr>
				<tr class="debug-mode-description" style="display:none">
					<td colspan="2">Turn this on to see additional debug information if you are having issues.</td>
				</tr>
				<tr>
					<td>
						<input type="hidden" name="action" value="update" />
						<input type="submit" class="button-primary" value="Save Changes" />
					</td>
				</tr>
			</table>
			</form>
			<?php
			if(get_option('updraft_debug_mode')) {
			?>
			<div>
				<h3>Debug Information</h3>
				<?php
				$peak_memory_usage = memory_get_peak_usage(true)/1024/1024;
				$memory_usage = memory_get_usage(true)/1024/1024;
				echo 'Peak memory usage: '.$peak_memory_usage.' MB<br/>';
				echo 'Current memory usage: '.$memory_usage.' MB<br/>';
				echo 'PHP memory limit: '.ini_get('memory_limit').' <br/>';
				?>
				<form method="post" action="">
					<input type="hidden" name="action" value="updraft_backup_debug_all" />
					<p><input type="submit" class="button-primary" <?php echo $backup_disabled ?> value="Debug Backup" onclick="return(confirm('This will cause an immediate backup.  The page will stall loading until it finishes (ie, unscheduled).  Use this if you\'re trying to see peak memory usage.'))" /></p>
				</form>
				<form method="post" action="">
					<input type="hidden" name="action" value="updraft_backup_debug_db" />
					<p><input type="submit" class="button-primary" <?php echo $backup_disabled ?> value="Debug DB Backup" onclick="return(confirm('This will cause an immediate DB backup.  The page will stall loading until it finishes (ie, unscheduled). The backup will remain locally despite your prefs and will not go into the backup history or up into the cloud.'))" /></p>
				</form>
			</div>
			<?php } ?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery('#updraft-service').change(function() {
						switch(jQuery(this).val()) {
							case 'none':
								jQuery('.cf,.s3,.ftp,.delete-local,.s3-description,.cf-description,.ftp-description').hide()
								jQuery('.email,.email-complete').show()
							break;
							case 'cloudfiles':
								jQuery('.s3,.ftp,.s3-description,.ftp-description').hide()
								jQuery('.cf,.delete-local,.email,.email-complete').show()
							break;
							case 's3':
								jQuery('.ftp,.cf,.ftp-description,.cf-description').hide()
								jQuery('.s3,.delete-local,.email,.email-complete').show()
							break;
							case 'ftp':
								jQuery('.s3,.cf,.s3-description,.cf-description').hide()
								jQuery('.ftp,.delete-local,.email,.email-complete').show()
							break;
							case 'email':
								jQuery('.s3,.ftp,.cf,.s3-description,.cf-description,.ftp-description,.email-complete').hide()
								jQuery('.email,.delete-local').show()
							break;
						}
					})
				})
				jQuery(window).load(function() {
					//this is for hiding the restore progress at the top after it is done
					setTimeout('jQuery("#updraft-restore-progress").toggle(1000)',3000)
					jQuery('#updraft-restore-progress-toggle').click(function() {
						jQuery('#updraft-restore-progress').toggle(500)
					})
				})
			</script>
			<?php
	}
	
	/*array2json provided by bin-co.com under BSD license*/
	function array2json($arr) { 
		if(function_exists('json_encode')) return stripslashes(json_encode($arr)); //Latest versions of PHP already have this functionality. 
		$parts = array(); 
		$is_list = false; 

		//Find out if the given array is a numerical array 
		$keys = array_keys($arr); 
		$max_length = count($arr)-1; 
		if(($keys[0] == 0) and ($keys[$max_length] == $max_length)) {//See if the first key is 0 and last key is length - 1 
			$is_list = true; 
			for($i=0; $i<count($keys); $i++) { //See if each key correspondes to its position 
				if($i != $keys[$i]) { //A key fails at position check. 
					$is_list = false; //It is an associative array. 
					break; 
				} 
			} 
		} 

		foreach($arr as $key=>$value) { 
			if(is_array($value)) { //Custom handling for arrays 
				if($is_list) $parts[] = $this->array2json($value); /* :RECURSION: */ 
				else $parts[] = '"' . $key . '":' . $this->array2json($value); /* :RECURSION: */ 
			} else { 
				$str = ''; 
				if(!$is_list) $str = '"' . $key . '":'; 

				//Custom handling for multiple data types 
				if(is_numeric($value)) $str .= $value; //Numbers 
				elseif($value === false) $str .= 'false'; //The booleans 
				elseif($value === true) $str .= 'true'; 
				else $str .= '"' . addslashes($value) . '"'; //All other things 
				// :TODO: Is there any more datatype we should be in the lookout for? (Object?) 

				$parts[] = $str; 
			} 
		} 
		$json = implode(',',$parts); 

		if($is_list) return '[' . $json . ']';//Return numerical JSON 
		return '{' . $json . '}';//Return associative JSON 
	} 
}

?>
