<?php
/**
 * انواع محتوا: محصول گیمینگ.
 *
 * @package GMX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * کلاس انواع محتوا.
 */
class GMX_Post_Types {

	/**
	 * سازنده.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_gmx_product', array( $this, 'save_meta' ), 10, 2 );
		add_filter( 'manage_gmx_product_posts_columns', array( $this, 'admin_columns' ) );
		add_action( 'manage_gmx_product_posts_custom_column', array( $this, 'admin_column_content' ), 10, 2 );
	}

	/**
	 * ثبت CPT محصول.
	 */
	public function register_post_types() {
		register_post_type(
			'gmx_product',
			array(
				'labels'        => array(
					'name'          => __( 'محصولات', 'gmx-market' ),
					'singular_name' => __( 'محصول', 'gmx-market' ),
					'add_new'       => __( 'افزودن محصول', 'gmx-market' ),
					'add_new_item'  => __( 'افزودن محصول جدید', 'gmx-market' ),
					'edit_item'     => __( 'ویرایش محصول', 'gmx-market' ),
					'search_items'  => __( 'جستجوی محصولات', 'gmx-market' ),
				),
				'public'        => true,
				'has_archive'   => true,
				'menu_icon'     => 'dashicons-games',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
				'rewrite'       => array( 'slug' => 'product' ),
				'show_in_rest'  => true,
			)
		);
	}

	/**
	 * ثبت تکسونومی نوع محصول و دسته‌بندی بازی.
	 */
	public function register_taxonomies() {
		register_taxonomy(
			'gmx_product_type',
			'gmx_product',
			array(
				'labels'       => array(
					'name'          => __( 'نوع محصول', 'gmx-market' ),
					'singular_name' => __( 'نوع', 'gmx-market' ),
				),
				'hierarchical' => false,
				'show_admin_column' => true,
				'show_in_rest' => true,
				'rewrite'      => array(
					'slug'         => 'shop/type',
					'with_front'   => false,
				),
			)
		);
		register_taxonomy(
			'gmx_game',
			'gmx_product',
			array(
				'labels'       => array(
					'name'          => __( 'بازی', 'gmx-market' ),
					'singular_name' => __( 'بازی', 'gmx-market' ),
				),
				'hierarchical' => true,
				'show_admin_column' => true,
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * افزودن متاباکس‌ها.
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'gmx_product_meta',
			__( 'تنظیمات محصول گیمینگ', 'gmx-market' ),
			array( $this, 'render_meta_box' ),
			'gmx_product',
			'normal',
			'high'
		);
	}

	/**
	 * نمایش متاباکس.
	 *
	 * @param WP_Post $post پست.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'gmx_product_meta', 'gmx_product_meta_nonce' );
		$price       = get_post_meta( $post->ID, '_gmx_price', true );
		$stock       = get_post_meta( $post->ID, '_gmx_stock', true );
		$delivery    = get_post_meta( $post->ID, '_gmx_delivery_time', true );
		$auto        = get_post_meta( $post->ID, '_gmx_auto_delivery', true );
		$credentials = get_post_meta( $post->ID, '_gmx_credentials', true );
		?>
		<style>
			.gmx-meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; max-width: 700px; }
			.gmx-meta-grid label { font-weight: 600; display: block; margin-bottom: 4px; }
			.gmx-meta-grid input[type=text], .gmx-meta-grid input[type=number], .gmx-meta-grid textarea { width: 100%; }
			.gmx-meta-full { grid-column: 1 / -1; }
		</style>
		<div class="gmx-meta-grid">
			<div>
				<label for="gmx_price"><?php esc_html_e( 'قیمت (تومان)', 'gmx-market' ); ?></label>
				<input type="number" id="gmx_price" name="gmx_price" value="<?php echo esc_attr( $price ); ?>" min="0" step="1000" />
			</div>
			<div>
				<label for="gmx_stock"><?php esc_html_e( 'موجودی (۰ = نامحدود)', 'gmx-market' ); ?></label>
				<input type="number" id="gmx_stock" name="gmx_stock" value="<?php echo esc_attr( $stock ); ?>" min="0" />
			</div>
			<div>
				<label for="gmx_delivery_time"><?php esc_html_e( 'زمان تحویل (مثلاً ۳۰ دقیقه)', 'gmx-market' ); ?></label>
				<input type="text" id="gmx_delivery_time" name="gmx_delivery_time" value="<?php echo esc_attr( $delivery ); ?>" />
			</div>
			<div>
				<label><input type="checkbox" name="gmx_auto_delivery" value="1" <?php checked( $auto, '1' ); ?> /> <?php esc_html_e( 'تحویل خودکار (اطلاعات اکانت پس از پرداخت نمایش داده شود)', 'gmx-market' ); ?></label>
			</div>
			<div class="gmx-meta-full">
				<label for="gmx_credentials"><?php esc_html_e( 'اطلاعات تحویل (برای تحویل خودکار — پس از پرداخت به خریدار نمایش داده می‌شود)', 'gmx-market' ); ?></label>
				<textarea id="gmx_credentials" name="gmx_credentials" rows="4"><?php echo esc_textarea( $credentials ); ?></textarea>
			</div>
		</div>
		<?php
	}

	/**
	 * ذخیره متا.
	 *
	 * @param int     $post_id شناسه پست.
	 * @param WP_Post $post    پست.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['gmx_product_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gmx_product_meta_nonce'] ), 'gmx_product_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$fields = array( 'gmx_price', 'gmx_stock', 'gmx_delivery_time', 'gmx_credentials' );
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
		update_post_meta( $post_id, '_gmx_auto_delivery', isset( $_POST['gmx_auto_delivery'] ) ? '1' : '' );
	}

	/**
	 * ستون‌های لیست ادمین.
	 *
	 * @param array $columns ستون‌ها.
	 * @return array
	 */
	public function admin_columns( $columns ) {
		$columns['gmx_price'] = __( 'قیمت', 'gmx-market' );
		$columns['gmx_stock'] = __( 'موجودی', 'gmx-market' );
		return $columns;
	}

	/**
	 * محتوای ستون‌های ادمین.
	 *
	 * @param string $column  ستون.
	 * @param int    $post_id شناسه پست.
	 */
	public function admin_column_content( $column, $post_id ) {
		if ( 'gmx_price' === $column ) {
			echo esc_html( gmx_price( get_post_meta( $post_id, '_gmx_price', true ) ) );
		}
		if ( 'gmx_stock' === $column ) {
			$stock = (int) get_post_meta( $post_id, '_gmx_stock', true );
			echo $stock ? esc_html( gmx_fa_num( $stock ) ) : esc_html__( 'نامحدود', 'gmx-market' );
		}
	}
}
