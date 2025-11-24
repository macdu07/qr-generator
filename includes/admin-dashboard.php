<?php
/**
 * QR Generator Admin Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get database instance
$db = new QR_Generator_Database();

// Handle delete action
if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['qr_id'] ) && isset( $_GET['_wpnonce'] ) ) {
	if ( wp_verify_nonce( $_GET['_wpnonce'], 'delete_qr_' . $_GET['qr_id'] ) ) {
		$db->delete_qr( intval( $_GET['qr_id'] ), get_current_user_id() );
		echo '<div class="notice notice-success is-dismissible"><p>QR Code deleted successfully.</p></div>';
	}
}

// Pagination
$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page = 20;
$total_items = $db->get_total_count();
$total_pages = ceil( $total_items / $per_page );

// Get history
$history = $db->get_history( $current_page, $per_page );

// Find page with QR Generator shortcode
function qr_get_shortcode_page_url() {
	global $wpdb;
	
	// Search for posts/pages containing the shortcode
	$posts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_type FROM {$wpdb->posts} 
			WHERE post_status = 'publish' 
			AND (post_type = 'page' OR post_type = 'post')
			AND post_content LIKE %s
			LIMIT 1",
			'%[qr_generator]%'
		)
	);
	
	if ( ! empty( $posts ) ) {
		return get_permalink( $posts[0]->ID );
	}
	
	return home_url(); // Fallback to homepage if not found
}

$qr_page_url = qr_get_shortcode_page_url();

?>
<div class="wrap qr-generator-dashboard">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-generic"></span>
		QR Generator Dashboard
	</h1>
	
	<p class="description">
		View and manage all generated QR codes. This history includes all QR codes that have been downloaded.
	</p>
	<a href="<?php echo esc_url( $qr_page_url ); ?>" class="button qr-generator-btn" target="_blank">Generate QR Code</a>

	<hr class="wp-header-end">

	<?php if ( empty( $history ) ) : ?>
		<div class="qr-empty-state">
			<div class="qr-empty-icon">
				<span class="dashicons dashicons-editor-code"></span>
			</div>
			<h2>No QR Codes Generated Yet</h2>
			<p>Start generating QR codes and they will appear here once downloaded.</p>
		</div>
	<?php else : ?>
		<div class="qr-stats">
			<div class="qr-stat-box">
				<span class="qr-stat-number"><?php echo esc_html( $total_items ); ?></span>
				<span class="qr-stat-label">Total QR Codes</span>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped qr-history-table">
			<thead>
				<tr>
					<th scope="col" class="column-date">Date</th>
					<th scope="col" class="column-type">Type</th>
					<th scope="col" class="column-content">Content</th>
					<th scope="col" class="column-format">Format</th>
					<th scope="col" class="column-actions">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $history as $item ) : 
					$customization = json_decode( $item['customization'], true );
				?>
					<tr>
						<td class="column-date">
							<strong><?php echo esc_html( date( 'M j, Y', strtotime( $item['created_at'] ) ) ); ?></strong><br>
							<span class="qr-time"><?php echo esc_html( date( 'g:i a', strtotime( $item['created_at'] ) ) ); ?></span>
						</td>
						<td class="column-type">
							<span class="qr-type-badge qr-type-<?php echo esc_attr( $item['content_type'] ); ?>">
								<?php echo esc_html( ucfirst( $item['content_type'] ) ); ?>
							</span>
						</td>
						<td class="column-content">
							<div class="qr-content-preview">
								<?php
									$content = $item['qr_data'];
									if ( strlen( $content ) > 80 ) {
										$content = substr( $content, 0, 80 ) . '...';
									}
									echo esc_html( $content );
								?>
							</div>
						</td>
						<td class="column-format">
							<span class="qr-format-badge">
								<?php echo esc_html( strtoupper( $item['file_format'] ) ); ?>
							</span>
						</td>
						<td class="column-actions">
						<button type="button" class="button button-small button-primary qr-download-btn" 
							data-qr-data="<?php echo esc_attr( $item['qr_data'] ); ?>"
							data-customization="<?php echo esc_attr( $item['customization'] ); ?>"
							data-format="<?php echo esc_attr( $item['file_format'] ); ?>">
							<span class="dashicons dashicons-download"></span>
							Download <?php echo esc_html( strtoupper( $item['file_format'] ) ); ?>
						</button>
						<button type="button" class="button button-small qr-view-details" 
							data-id="<?php echo esc_attr( $item['id'] ); ?>"
							data-type="<?php echo esc_attr( $item['content_type'] ); ?>"
							data-content="<?php echo esc_attr( $item['qr_data'] ); ?>"
							data-customization="<?php echo esc_attr( $item['customization'] ); ?>">
							View Details
						</button>
						<a href="<?php echo esc_url( wp_nonce_url( 
							add_query_arg( array( 'action' => 'delete', 'qr_id' => $item['id'] ) ),
							'delete_qr_' . $item['id']
						) ); ?>" 
						class="button button-small button-link-delete"
						onclick="return confirm('Are you sure you want to delete this QR code from history?');">
							Delete
						</a>
					</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					echo paginate_links( array(
						'base' => add_query_arg( 'paged', '%#%' ),
						'format' => '',
						'prev_text' => '&laquo; Previous',
						'next_text' => 'Next &raquo;',
						'total' => $total_pages,
						'current' => $current_page
					) );
					?>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>

<!-- Details Modal -->
<div id="qr-details-modal" class="qr-modal" style="display: none;">
	<div class="qr-modal-content">
		<div class="qr-modal-header">
			<h2>QR Code Details</h2>
			<button type="button" class="qr-modal-close">&times;</button>
		</div>
		<div class="qr-modal-body">
			<div class="qr-detail-row">
				<strong>Type:</strong>
				<span id="detail-type"></span>
			</div>
			<div class="qr-detail-row">
				<strong>Content:</strong>
				<div id="detail-content"></div>
			</div>
			<div class="qr-detail-row">
				<strong>Customization:</strong>
				<div id="detail-customization"></div>
			</div>
		</div>
	</div>
</div>

