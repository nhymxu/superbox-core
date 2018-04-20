<?php
/*
Custom post type for affiliate system
Version: 2.1.0
*/

/**
 * Register a product post type.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_post_type
 */
function nhymxu_product_init() {
 	$product_labels = [
		'name'               => 'Sản phẩm',
		'singular_name'      => 'Sản phẩm',
		'menu_name'          => 'Sản phẩm',
		'name_admin_bar'     => 'Sản phẩm',
		'add_new'            => 'Thêm mới',
		'add_new_item'       => 'Thêm mới Sản phẩm',
		'new_item'           => 'Thêm sản phẩm',
		'edit_item'          => 'Sửa sản phẩm',
		'view_item'          => 'Xem sản phẩm',
		'all_items'          => 'Tất cả sản phẩm',
		'search_items'       => 'Tìm sản phẩm',
		'parent_item_colon'  => 'Sản phẩm cha:',
		'not_found'          => 'Không có sản phẩm nào.',
		'not_found_in_trash' => 'Không có sản phẩm nào trong Thùng rác.',
	]; 

	$product_args = [
		'labels'             => $product_labels,
		// 'description'        => 'Description.', 'your-plugin-textdomain' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => ['slug' => 'san-pham'],
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => ['title', 'editor', 'thumbnail'],
		// 'taxonomies'            => ['post_tag'],
	];

	register_post_type( 'san-pham', $product_args );
	
	$category_labels = array(
		'name'                       => 'Loại sản phẩm',
		'singular_name'              => 'Loại sản phẩm',
		'menu_name'                  => 'Loại sản phẩm',
		'all_items'                  => 'Tất cả loại',
		'parent_item'                => 'Parent Item',
		'parent_item_colon'          => 'Parent Item:',
		'new_item_name'              => 'New Item Name',
		'add_new_item'               => 'Add New Item',
		'edit_item'                  => 'Edit Item',
		'update_item'                => 'Update Item',
		'view_item'                  => 'View Item',
		'separate_items_with_commas' => 'Separate items with commas',
		'add_or_remove_items'        => 'Add or remove items',
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular Items',
		'search_items'               => 'Search Items',
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No items',
		'items_list'                 => 'Items list',
		'items_list_navigation'      => 'Items list navigation',
	);
	$category_args = array(
		'labels'                     => $category_labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => false,
		'show_in_quick_edit'		 => true,
	);
	register_taxonomy( 'loai-san-pham', ['san-pham'], $category_args );
	
	$tag_labels = array(
		'name'                       => 'Tag sản phẩm',
		'singular_name'              => 'Tag sản phẩm',
		'menu_name'                  => 'Tag sản phẩm',
		'all_items'                  => 'Tất cả tag sản phẩm',
		'parent_item'                => 'Parent Item',
		'parent_item_colon'          => 'Parent Item:',
		'new_item_name'              => 'New Item Name',
		'add_new_item'               => 'Add New Item',
		'edit_item'                  => 'Edit Item',
		'update_item'                => 'Update Item',
		'view_item'                  => 'View Item',
		'separate_items_with_commas' => 'Separate items with commas',
		'add_or_remove_items'        => 'Add or remove items',
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular Items',
		'search_items'               => 'Search Items',
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No items',
		'items_list'                 => 'Items list',
		'items_list_navigation'      => 'Items list navigation',
	);
	$tag_args = array(
		'labels'                     => $tag_labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => false,
		'show_in_quick_edit'		 => true,
	);
	register_taxonomy( 'tag-san-pham', ['san-pham'], $tag_args );
}
add_action( 'init', 'nhymxu_product_init' );

/*
 * Register custom metabox for product post type
 */
/**
 * Register meta box(es).
 */
function nhymxu_register_product_meta_boxes() {
    add_meta_box( 'nhymxu-product-metabox', 'Chi tiết sản phẩm', 'nhymxu_product_metabox_callback', 'san-pham', "normal", "low" );
}
add_action( 'add_meta_boxes', 'nhymxu_register_product_meta_boxes' );

/**
 * Meta box display callback.
 *
 * @param WP_Post $post Current post object.
 */
function nhymxu_product_metabox_callback( $post ) {
	// global $post;
	$custom = get_post_custom( $post->ID );
	$product_price = (isset($custom["product_price"][0]) && '' != $custom["product_price"][0]) ? $custom["product_price"][0] : 0;
	$product_sale_price = (isset($custom["product_sale_price"][0]) && '' != $custom["product_sale_price"][0]) ? $custom["product_sale_price"][0] : 0;
	
	$product_aff_url = isset( $custom["product_aff_url"][0] ) ? $custom["product_aff_url"][0] : '';
	$product_image = isset( $custom["product_image"][0] ) ? $custom["product_image"][0] : '';
	?>
	<style>.width99 {width:99%;}</style>
	<p>
		<table width="100%">
			<tr>
				<td><label><strong>Giá</strong></label></td>
				<td><label><strong>Giá khuyến mãi</strong></label></td>
			</tr>
			<tr>
				<td><input type="number" name="product_price" class="width99" min="0" value="<?=$product_price;?>"></td>
				<td><input type="number" name="product_sale_price" class="width99" min="0" value="<?=$product_sale_price;?>"></td>
			</tr>
			<tr><td colspan="2"><small>Nếu giá khuyến mãi lớn hơn 0 thì sản phẩm sẽ lấy theo giá khuyến mãi.</small></td></tr>
		</table>
	</p>
	<p>Link sản phẩm: <input type="text" name="product_aff_url" class="width99" value="<?=$product_aff_url;?>"><p>
	<p>Link image: <input type="text" name="product_image" class="width99" value="<?=$product_image;?>"><p>
	<?php
}
 
/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function nhymxu_save_product_meta_box( $post_id ) {
	global $post;

	if( !isset( $_POST['post_type'] ) ) {
		return $post_id;
	}

	if ( 'san-pham' != $_POST['post_type'] ) {
		return $post_id;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) )
		return $post_id;
	
	if ( $post ) {
		update_post_meta($post->ID, "product_price", sanitize_text_field(@$_POST["product_price"]));
		update_post_meta($post->ID, "product_sale_price", sanitize_text_field(@$_POST["product_sale_price"]));
		update_post_meta($post->ID, "product_aff_url", sanitize_text_field(@$_POST["product_aff_url"]));
		update_post_meta($post->ID, "product_image", sanitize_text_field(@$_POST["product_image"]));
	}
}
add_action( 'save_post', 'nhymxu_save_product_meta_box' );
