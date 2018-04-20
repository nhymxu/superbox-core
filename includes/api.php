<?php

/*
 * API insert normal post
 */
add_action( 'rest_api_init', function() {
    register_rest_route( 'mars', '/post', [
        'methods'             => WP_REST_Server::ALLMETHODS,
        'callback'            => 'nhymxu_post_restapi_callback'
    ] );
} );

function nhymxu_post_restapi_callback( WP_REST_Request $request ) {
    
    $auth_key =  $request->get_header('ApiKey');

    if( !$auth_key || $auth_key === null ) {
        return 'DENIED';
    }

    if( $auth_key !== '0b3b5a9713344fe284cd3ed4d9de1975' ) {
        return 'AUTH_FAIL';
    }

    $json = $request->get_body();
    
    $data = json_decode($json, true);
    
    if( null == $data ) {
        return 'BAD_REQUEST';
    }
    
    if( isset( $data[0] ) && is_array( $data[0] ) ) {
        foreach( $data as $single_post ) {
            $result = nhymxu_insert_post( $single_post );
        }
    } else {
        $result = nhymxu_insert_post( $data );
    }
    
    return $result;
}

function nhymxu_insert_post( $data ) {
    global $wpdb;

    // Create post object
    $post_data = [
            'post_title'    => wp_strip_all_tags( $data['title'] ),
            'post_content'  => $data['content'],
            //'post_status'   => 'publish', // default draf
            'post_author'   => 2,
            'post_type'		=> 'post',
        ];

    // Insert the post into the database
    $post_id = wp_insert_post( $post_data );
    
    // Sideload featured image
    if ( $post_id > 0 ) {

        if( $data['image'] && $data['image'] != null && $data['image'] != '' ) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
    
            $image_id = media_sideload_image( $data['image'], $post_id, '', 'id' );
            set_post_thumbnail( $post_id, $image_id );
        }

        return 'OK';
    } else {
        return 'INSERT_ERROR';
    }
}

//================================================================================
/*
 * API insert coupon
 */
add_action( 'rest_api_init', function () {
	$args = array(
		// 'methods' => WP_REST_Server::CREATABLE,
		'methods' => WP_REST_Server::ALLMETHODS,
		'callback' => 'coupon_restapi_callback',
	);
	// Add the route 'mars/coupon' to the WP REST API
	register_rest_route( 'mars', 'coupon', $args );
} );

function coupon_restapi_callback( WP_REST_Request $request ) {

	$auth_key =  $request->get_header('ApiKey');
	
	if( !$auth_key || $auth_key === null ) {
		return 'DENIED';
	}

	if( $auth_key !== '0b3b5a9713344fe284cd3ed4d9de1975' ) {
		return 'AUTH_FAIL';
	}

	$json = $request->get_body();
	
	$data = json_decode($json, true);
	
	if( null == $data ) {
		return 'BAD_REQUEST';
	}
	
	if( isset( $data[0] ) && is_array( $data[0] ) ) {
		foreach( $data as $coupon_data ) {
			$result = insert_coupon( $coupon_data );
		}
		//$result = insert_coupons( $data );
	} else {
		$result = insert_coupon( $data );
	}
	
	return $result;
}

function insert_coupons( $input_data ) {
	global $wpdb;

	$coupon_value = [];
	foreach( $input_data as $coupon ) {
		$coupon_value[] = $wpdb->prepare(
			'(%s, %s, %s, %s, %s, %s, %s)',
			$coupon['merchant'], $coupon['title'], $coupon['coupon_code'], $coupon['date_end'], $coupon['coupon_desc'], $coupon['link'], $coupon['coupon_save']);
	}
	$values = implode(', ', $coupon_value);
	
	$result = $wpdb->query("INSERT INTO {$wpdb->prefix}coupons (type, title, code, exp, note, url, save) VALUES {$values}");
	
	if( false === $result ) {
		return 'INSERT_ERROR';
	}
	
	if( 0 === $result ) {
		return 'NO_ROW';
	}
	
	return 'OK';
}

function insert_coupon( $data ) {
	global $wpdb;
	
	$result = $wpdb->insert( 
		$wpdb->prefix . 'coupons',
		[
			'type'	=> $data['merchant'],
			'title' => $data['title'],
			'code'	=> ($data['coupon_code']) ? $data['coupon_code'] : '',
			'exp'	=> $data['date_end'],
			'note'	=> $data['coupon_desc'],
			'url'	=> ($data['link']) ? $data['link'] : '',
			'save'	=> ($data['coupon_save']) ? $data['coupon_save'] : ''
		],
		['%s','%s','%s','%s','%s','%s','%s']
	);
	
	if ( $result ) {
		$coupon_id = $wpdb->insert_id;
		if( isset( $data['categories'] ) && !empty( $data['categories'] ) ) {
			$cat_ids = coupon_get_category_id( $data['categories'] );
			foreach( $cat_ids as $row ) {
				$wpdb->insert(
					$wpdb->prefix . 'coupon_category_rel',
					[
						'coupon_id' => $coupon_id,
						'category_id'	=> $row
					],
					['%d', '%d']
				);
			}
		}

		return 'OK';
	} else {
		return 'INSERT_ERROR';
	}
}

function coupon_get_category_id( $input ) {
	global $wpdb;

	$cat_id = [];

	foreach( $input as $row ) {
		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}coupon_categories WHERE slug = '{$row['slug']}'");
		
		if( $result ) {
			$cat_id[] = (int) $result->id;
		} else {
			$result = $wpdb->insert(
				$wpdb->prefix . 'coupon_categories',
				[
					'name'	=> $row['title'],
					'slug'	=> $row['slug']
				],
				['%s', '%s']
			);
			$cat_id[] = (int) $wpdb->insert_id;				
		}
	}

	return $cat_id;
}

//================================================================================
/**
 * Clear vinaphone product feed cache
 */
add_action( 'rest_api_init', function () {
	// Add the route 'mars/flush_vinaphone'
	register_rest_route( 'mars', 'flush_vinaphone', ['method' => WP_REST_Server::ALLMETHODS, 'callback' => 'flush_vinaphone'] );

} );

function flush_vinaphone( WP_REST_Request $request ) {
	delete_transient('vinaphone_products');
	return 'Done';
}

//================================================================================
/**
 * Insert product api
 */
add_action( 'rest_api_init', function() {
    register_rest_route( 'mars', '/product', [
        'methods'             => WP_REST_Server::ALLMETHODS,
        'callback'            => 'nhymxu_product_restapi_callback'
    ] );
} );

function nhymxu_product_restapi_callback( WP_REST_Request $request ) {

	$auth_key =  $request->get_header('ApiKey');

	if( !$auth_key || $auth_key === null ) {
		return 'DENIED';
	}

	if( $auth_key !== '0b3b5a9713344fe284cd3ed4d9de1975' ) {
		return 'AUTH_FAIL';
	}

	$json = $request->get_body();
	
	$data = json_decode($json, true);
	
	if( null == $data ) {
		return 'BAD_REQUEST';
	}
	
	if( isset( $data[0] ) && is_array( $data[0] ) ) {
		foreach( $data as $single_product ) {
			$result = nhymxu_insert_product( $single_product );
		}
	} else {
		$result = nhymxu_insert_product( $data );
	}
	
	return $result;
}

function nhymxu_insert_product( $data ) {
	global $wpdb;

	$exist = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_at_id' AND meta_value = '{$data['id']}'");

	// Đã có sản phẩm, cập nhật giá
	if( $exist !== null ) {
		update_post_meta( $exist->post_id, 'product_price', $data['price'] );
		update_post_meta( $exist->post_id, 'product_sale_price', $data['sale_price'] );		
		return 'OK';
	}

	// Chưa có sản phẩm, insert

	$categories = nhymxu_get_product_category_id( $data['categories'] );

	// Create post object
	$product_data = [
			'post_title'    => wp_strip_all_tags( $data['title'] ),
			'post_content'  => $data['content'],
			'post_status'   => 'publish',
			'post_author'   => 1,
			'post_type'		=> 'san-pham',
			'tax_input'	=> [
				'loai-san-pham' => $categories
			],
		    'meta_input'   => [
		    		'product_price'	=> $data['price'],
		    		'product_sale_price' => $data['sale_price'],
					'product_aff_url'	=> $data['link'],
					'product_image'		=> $data['image'],
					'_at_id'			=> $data['id'],
					'_at_merchant'		=> $data['merchant']
		    	],
		];

	// Insert the post into the database
	$product_id = wp_insert_post( $product_data );
	
	// Sideload featured image
	if ( $product_id > 0 ) {
		wp_set_object_terms( $product_id, $categories, 'loai-san-pham' );

		/*
		 * 2017-10-12 tắt load ảnh về giảm tải server
		 */
		/*require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$image_id = media_sideload_image( $data['image'], $product_id, '', 'id' );
		set_post_thumbnail( $product_id, $image_id );*/

		return 'OK';
	} else {
		return 'INSERT_ERROR';
	}
}

function nhymxu_get_product_category_id( $input ) {
	$cat_id = [];

	if( empty( $input ) ) {
		return $cat_id;
	}

	foreach( $input as $cat ) {
		$cat_term = term_exists( $cat['slug'], 'loai-san-pham' );
		if( !$cat_term ) {
			$cat_term = wp_insert_term( $cat['title'], 'loai-san-pham', ['parent' => 0, 'slug' => $cat['slug']] );
		}
		$cat_id[] = (int) $cat_term['term_id'];
	}

	return $cat_id;
}