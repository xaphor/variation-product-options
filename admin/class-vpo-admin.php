<?php
/**
 * Admin functionality class.
 *
 * @package VariationProductOptions
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class VPO_Admin {

	/**
	 * Instance of this class.
	 *
	 * @var VPO_Admin
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return VPO_Admin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize.
	 */
	private function init() {
		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// Add meta boxes to product edit page.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Handle form submissions.
		add_action( 'admin_post_vpo_save_group', array( $this, 'handle_save_group' ) );
		add_action( 'admin_post_vpo_delete_group', array( $this, 'handle_delete_group' ) );
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Product Options', 'variation-product-options' ),
			__( 'Product Options', 'variation-product-options' ),
			'manage_woocommerce',
			'vpo-product-options',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		// Handle actions.
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		$group_id = isset( $_GET['group_id'] ) ? sanitize_key( $_GET['group_id'] ) : '';

		if ( 'edit' === $action ) {
			// Edit existing group or create new one (if group_id is empty).
			$this->render_edit_page( $group_id );
		} else {
			$this->render_list_page();
		}
	}

	/**
	 * Render list page.
	 */
	private function render_list_page() {
		$all_groups = VPO_Data_Handler::get_all_field_groups();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Product Options', 'variation-product-options' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=vpo-product-options&action=edit' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'variation-product-options' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( empty( $all_groups ) ) : ?>
				<p><?php esc_html_e( 'No field groups found. Create your first field group to get started.', 'variation-product-options' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Group Name', 'variation-product-options' ); ?></th>
							<th><?php esc_html_e( 'Fields', 'variation-product-options' ); ?></th>
							<th><?php esc_html_e( 'Assignment', 'variation-product-options' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'variation-product-options' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $all_groups as $group_id => $group ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( isset( $group['name'] ) ? $group['name'] : $group_id ); ?></strong>
								</td>
								<td>
									<?php
									$field_count = isset( $group['fields'] ) ? count( $group['fields'] ) : 0;
									echo esc_html( sprintf( _n( '%d field', '%d fields', $field_count, 'variation-product-options' ), $field_count ) );
									?>
								</td>
								<td>
									<?php
									$rules = isset( $group['rules'] ) ? $group['rules'] : array();
									if ( isset( $rules['all_products'] ) && $rules['all_products'] ) {
										echo '<span class="dashicons dashicons-admin-site"></span> ' . esc_html__( 'All Products', 'variation-product-options' );
									} elseif ( ! empty( $rules['product_ids'] ) || ! empty( $rules['variation_ids'] ) ) {
										$assignments = array();
										if ( ! empty( $rules['product_ids'] ) ) {
											$assignments[] = sprintf( _n( '%d product', '%d products', count( $rules['product_ids'] ), 'variation-product-options' ), count( $rules['product_ids'] ) );
										}
										if ( ! empty( $rules['variation_ids'] ) ) {
											$assignments[] = sprintf( _n( '%d variation', '%d variations', count( $rules['variation_ids'] ), 'variation-product-options' ), count( $rules['variation_ids'] ) );
										}
										echo esc_html( implode( ', ', $assignments ) );
									} else {
										echo '<span class="description">' . esc_html__( 'Not assigned', 'variation-product-options' ) . '</span>';
									}
									?>
								</td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=vpo-product-options&action=edit&group_id=' . $group_id ) ); ?>" class="button button-small">
										<?php esc_html_e( 'Edit', 'variation-product-options' ); ?>
									</a>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vpo_delete_group&group_id=' . $group_id ), 'vpo_delete_group_' . $group_id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this field group?', 'variation-product-options' ); ?>');">
										<?php esc_html_e( 'Delete', 'variation-product-options' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render edit page.
	 *
	 * @param string $group_id Group ID.
	 */
	private function render_edit_page( $group_id = '' ) {
		$group = $group_id ? VPO_Data_Handler::get_field_group( $group_id ) : false;
		$is_new = ! $group;

		if ( $is_new ) {
			$group = array(
				'group_id' => '',
				'name'     => '',
				'rules'    => array(
					'all_products'  => false,
					'product_ids'   => array(),
					'variation_ids' => array(),
				),
				'fields'   => array(),
			);
		}

		require_once VPO_PLUGIN_DIR . 'admin/class-vpo-field-builder.php';
		VPO_Field_Builder::render( $group );
	}

	/**
	 * Add meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'vpo-product-options',
			__( 'Product Options', 'variation-product-options' ),
			array( $this, 'render_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_meta_box( $post ) {
		$product_id = $post->ID;
		$all_groups = VPO_Data_Handler::get_all_field_groups();
		$applicable_groups = VPO_Data_Handler::get_field_groups( $product_id );

		wp_nonce_field( 'vpo_meta_box', 'vpo_meta_box_nonce' );
		?>
		<div class="vpo-meta-box">
			<p>
				<?php esc_html_e( 'Manage product options from the main Product Options page or create field groups that apply to this product.', 'variation-product-options' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=vpo-product-options' ) ); ?>" class="button">
					<?php esc_html_e( 'Manage Product Options', 'variation-product-options' ); ?>
				</a>
			</p>
			<?php if ( ! empty( $applicable_groups ) ) : ?>
				<h4><?php esc_html_e( 'Field Groups Applied to This Product:', 'variation-product-options' ); ?></h4>
				<ul>
					<?php foreach ( $applicable_groups as $group_id => $group ) : ?>
						<li>
							<strong><?php echo esc_html( isset( $group['name'] ) ? $group['name'] : $group_id ); ?></strong>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=vpo-product-options&action=edit&group_id=' . $group_id ) ); ?>" class="button button-small">
								<?php esc_html_e( 'Edit', 'variation-product-options' ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle save group form submission.
	 */
	public function handle_save_group() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'variation-product-options' ) );
		}

		check_admin_referer( 'vpo_save_group' );

		error_log( 'VPO: handle_save_group() called. POST data: ' . wp_json_encode( $_POST ) );

		$group_data = array(
			'group_id' => isset( $_POST['group_id'] ) ? sanitize_key( $_POST['group_id'] ) : '',
			'name'     => isset( $_POST['group_name'] ) ? sanitize_text_field( $_POST['group_name'] ) : '',
			'rules'    => array(
				'all_products'  => isset( $_POST['all_products'] ) ? true : false,
				'product_ids'   => isset( $_POST['product_ids'] ) ? array_map( 'absint', (array) $_POST['product_ids'] ) : array(),
				'variation_ids' => isset( $_POST['variation_ids'] ) ? array_map( 'absint', (array) $_POST['variation_ids'] ) : array(),
			),
			'fields'   => isset( $_POST['fields'] ) ? (array) $_POST['fields'] : array(),
		);

		error_log( 'VPO: Calling save_field_group with: ' . wp_json_encode( $group_data ) );

		$result = VPO_Data_Handler::save_field_group( $group_data );

		error_log( 'VPO: save_field_group returned: ' . ( $result ? 'success (' . $result . ')' : 'false' ) );

		if ( $result ) {
			wp_safe_redirect( admin_url( 'admin.php?page=vpo-product-options&action=edit&group_id=' . $result . '&saved=1' ) );
			exit;
		} else {
			wp_die( esc_html__( 'Error saving field group.', 'variation-product-options' ) );
		}
	}

	/**
	 * Handle delete group action.
	 */
	public function handle_delete_group() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'variation-product-options' ) );
		}

		$group_id = isset( $_GET['group_id'] ) ? sanitize_key( $_GET['group_id'] ) : '';
		check_admin_referer( 'vpo_delete_group_' . $group_id );

		$result = VPO_Data_Handler::delete_field_group( $group_id );

		if ( $result ) {
			wp_safe_redirect( admin_url( 'admin.php?page=vpo-product-options&deleted=1' ) );
			exit;
		} else {
			wp_die( esc_html__( 'Error deleting field group.', 'variation-product-options' ) );
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on relevant pages.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'woocommerce_page_vpo-product-options' ), true ) ) {
			return;
		}

		// Enqueue admin CSS.
		wp_enqueue_style(
			'vpo-admin',
			VPO_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			VPO_VERSION
		);

		// Enqueue admin JS.
		wp_enqueue_script(
			'vpo-admin',
			VPO_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			VPO_VERSION,
			true
		);
	}
}
