<?php
/**
 * QR Generator Database Handler
 * 
 * Handles database operations for QR code history
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QR_Generator_Database {

	/**
	 * Table name
	 */
	private $table_name;

	/**
	 * WordPress database object
	 */
	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = $wpdb->prefix . 'qr_generator_history';
	}

	/**
	 * Create database table
	 */
	public function create_table() {
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			content_type varchar(50) NOT NULL,
			qr_data text NOT NULL,
			customization longtext,
			file_format varchar(10) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Save QR code to history
	 */
	public function save_qr( $user_id, $content_type, $qr_data, $customization, $file_format ) {
		$result = $this->wpdb->insert(
			$this->table_name,
			array(
				'user_id' => $user_id,
				'content_type' => sanitize_text_field( $content_type ),
				'qr_data' => sanitize_textarea_field( $qr_data ),
				'customization' => wp_json_encode( $customization ),
				'file_format' => sanitize_text_field( $file_format ),
				'created_at' => current_time( 'mysql' )
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result !== false ? $this->wpdb->insert_id : false;
	}

	/**
	 * Get QR history with pagination
	 */
	public function get_history( $page = 1, $per_page = 20, $user_id = null ) {
		$offset = ( $page - 1 ) * $per_page;

		$where = '';
		if ( $user_id ) {
			$where = $this->wpdb->prepare( "WHERE user_id = %d", $user_id );
		}

		$query = "SELECT * FROM {$this->table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$results = $this->wpdb->get_results( 
			$this->wpdb->prepare( $query, $per_page, $offset ),
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Get total count
	 */
	public function get_total_count( $user_id = null ) {
		$where = '';
		if ( $user_id ) {
			$where = $this->wpdb->prepare( "WHERE user_id = %d", $user_id );
		}

		$query = "SELECT COUNT(*) FROM {$this->table_name} {$where}";
		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Delete QR from history
	 */
	public function delete_qr( $id, $user_id = null ) {
		$where = array( 'id' => $id );
		$where_format = array( '%d' );

		// If user_id is provided, ensure user can only delete their own QRs
		if ( $user_id ) {
			$where['user_id'] = $user_id;
			$where_format[] = '%d';
		}

		return $this->wpdb->delete( $this->table_name, $where, $where_format );
	}

	/**
	 * Get single QR by ID
	 */
	public function get_qr( $id ) {
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name} WHERE id = %d",
			$id
		);
		return $this->wpdb->get_row( $query, ARRAY_A );
	}
}
