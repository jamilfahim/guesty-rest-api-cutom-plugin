<?php
/**
 * Plugin Name:       Guesty API
 * Plugin URI:        https://softtech-it.com/
 * Description:       This is a custom plugin for Guesty API.
 * Version:           1.0.0
 * Author:            jhfahim
 * Author URI:        https://jhfahim.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       softtechit
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Enqueue script
 */
function csv_enqueue_script()
{   
		
    wp_enqueue_style( 'style-js', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), '1.0.0', 'all' );
  
}
add_action('admin_enqueue_scripts', 'csv_enqueue_script');
require_once(ABSPATH.'/wp-load.php' );
require_once(ABSPATH .'/wp-content/plugins/woocommerce/woocommerce.php' );

/**
 * This template for shortcode
 */
require plugin_dir_path( __FILE__ ) . 'inc/shortcode.php';



function id_by_sku($sku){

    global $wpdb;

    $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

    return $product_id;
}


/*********************************************
 *        admin menu page
 *********************************************/

function wc_admin_menu_page() {
   add_menu_page(
       __( 'Menu Name', 'softtech' ),
       'Guesty API',
       'manage_options',
       'softtechit-developer',
       'create_product',
       'dashicons-cart',
       //plugins_url( 'myplugin/images/icon.png' ),
       58
   );
 
}

add_action( 'admin_menu','wc_admin_menu_page') ;



function create_product(){

     
        //=================================================
        // Get access token from api
        //==================================================
       $get_access_token = get_option('guesty_access_token');

        if(empty($get_access_token )){

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://booking.guesty.com/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials&scope=booking_engine%3Aapi&client_secret=rhm0VGDBMRhKTvYWqoV6umaSQWbVS9ZAa8tKRVuB&client_id=0oa6np5ev5eCyaCkX5d7',
            CURLOPT_HTTPHEADER => array(
                'accept: application/json',
                'cache-control: no-cache,no-cache',
                'content-type: application/x-www-form-urlencoded',
                'Cookie: JSESSIONID=5A9235A1A3EB6C2CBAD92FB5EE88B701'
            ),
            ));

            $response = curl_exec($curl);
            
            $response_decode = json_decode($response);
            $access_token = $response_decode->access_token;
            update_option( 'guesty_access_token', $access_token );

        }

      
        //======================================
        //get json product data from api
        //=======================================

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://booking.guesty.com/api/listings',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'accept: application/json; charset=utf-8',
            "Authorization: Bearer $get_access_token",
            'Cookie: JSESSIONID=E7EE5D0DBE7ACF9F7682181E11B1A63F; JSESSIONID=5A9235A1A3EB6C2CBAD92FB5EE88B701'
        ),
        ));
        $data = curl_exec($curl);
        curl_close($curl);
     
        $final_data = json_decode($data);
      
         //======================================
        //if error access token
        //=======================================
        //if error access token
        $products_data = $final_data->results;
       // var_dump($products_data);
        if($products_data == null){

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://booking.guesty.com/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials&scope=booking_engine%3Aapi&client_secret=rhm0VGDBMRhKTvYWqoV6umaSQWbVS9ZAa8tKRVuB&client_id=0oa6np5ev5eCyaCkX5d7',
            CURLOPT_HTTPHEADER => array(
                'accept: application/json',
                'cache-control: no-cache,no-cache',
                'content-type: application/x-www-form-urlencoded',
                'Cookie: JSESSIONID=5A9235A1A3EB6C2CBAD92FB5EE88B701'
            ),
            ));

            $response = curl_exec($curl);
            
            $response_decode = json_decode($response);
            $access_token_update = $response_decode->access_token;
            //var_dump($access_token);
            update_option( 'guesty_access_token', $access_token_update );
            var_dump($access_token_update);

        }

       
     

       
        

	if( isset( $_POST['createproduct'] ) && !empty($products_data) ){
        //=================================================
        // product data
        //==================================================
     
        foreach($products_data as $product_data){
  
            $sku = $product_data->_id;
            $product_id = id_by_sku( $sku );
            $bath_rooms = $product_data->bathrooms;
            $accommodates = $product_data->accommodates;
            $bedrooms = $product_data->bedrooms;
            $beds = $product_data->beds;
            $total_rooms = $bath_rooms + $accommodates + $bedrooms + $beds;
            $product = array(
                'post_title'    => $product_data->title,
                'post_content'  => ($product_data->publicDescription)->summary,
                'post_status'   => 'publish',
                'post_type'     => 'product',
            );
            // echo "<h1>$product_id</h1>";

            $amenities_array = array();

            foreach($product_data->amenities as $single_amenity){
                $amenities_array[] = "yes";
            }

            if($product_id == 0 ){

                // $post_id = wp_insert_post( $product );

                // update_post_meta( $post_id, '_sku',$product_data->_id );
                // wp_set_object_terms( $post_id, 'ovacrs_car_rental', 'product_type' );
                // update_post_meta( $post_id, '_regular_price', ($product_data->prices)->basePrice );
                // update_post_meta( $post_id, '_price', ($product_data->prices)->basePrice );

                //====================================================
                // For Adding rooms count, amenities and categories
                //=====================================================
                // update_post_meta( $post_id, 'ovacrs_car_count', $total_rooms );
                // update_post_meta( $post_id, 'ovacrs_features_label', $product_data->amenities );
                // update_post_meta( $post_id, 'ovacrs_features_special', $amenities_array );
                //  wp_set_object_terms($post_id, array($product_data->type,$product_data->roomType,$product_data->propertyType), 'product_cat', true);
                //  $image_url = ($product_data->picture)->thumbnail;


                //  //===========================================
                //  // For Adding Featured Image to Product
                //  //============================================
                //  // $image_url        = $product_data["images"]; // Define the image URL here
                //  $image_name       = basename($image_url);
                //  $upload_dir       = wp_upload_dir(); // Set upload folder
                //  $image_data       = file_get_contents($image_url); // Get image data
                //  $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
                //  $filename         = basename( $unique_file_name ); // Create image file name
 
                //  // Check folder permission and define file location
                //  if( wp_mkdir_p( $upload_dir['path'] ) ) {
                //  $file = $upload_dir['path'] . '/' . $filename;
                //  } else {
                //  $file = $upload_dir['basedir'] . '/' . $filename;
                //  }
 
                //  // Create the image  file on the server
                //  file_put_contents( $file, $image_data );
 
                //  // Check image file type
                //  $wp_filetype = wp_check_filetype( $filename, null );
 
                //  // Set attachment data
                //  $attachment = array(
                //      'post_mime_type' => $wp_filetype['type'],
                //      'post_title'     => sanitize_file_name( $filename ),
                //      'post_content'   => '',
                //      'post_status'    => 'inherit'
                //  );
 
                //  // Create the attachment
                //  $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
 
                //  // Include image.php
                //  require_once(ABSPATH . 'wp-admin/includes/image.php');
 
                //  // Define attachment metadata
                //  $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
 
                //  // Assign metadata to attachment
                //  wp_update_attachment_metadata( $attach_id, $attach_data );
 
                //  // And finally assign featured image to post
                //  set_post_thumbnail( $post_id, $attach_id );


                //=============== For Adding Gallery ==========================

                //  //$image_urls = ($product_data->pictures)->regular;
                //  $gallery_imgs = $product_data->pictures;
                // $image_urls = [];
                //  foreach($gallery_imgs as $gallery_img ){
                // $image_urls[] = $gallery_img->thumbnail;
                // }
                //  //$image_urls = array( 'https://guesty-listing-images.s3.amazonaws.com/production/regular_43074488_1011414873.jpg', 'https://guesty-listing-images.s3.amazonaws.com/production/regular_43074488_1011414873.jpg', 'https://guesty-listing-images.s3.amazonaws.com/production/regular_43074488_1011414873.jpg' );

                //  $product_image_gallery = get_post_meta( $post_id, '_product_image_gallery', true );
                 
                //  foreach ( $image_urls as $image_url ) {
                //      $file_array = array();
                //      $file_array['name'] = basename($image_url);
                //      $file_array['tmp_name'] = download_url( $image_url );
                //      $attachment_id = media_handle_sideload( $file_array, $post_id );
                //      $product_image_gallery .= ',' . $attachment_id;
                //  }    
                //  update_post_meta( $post_id, '_product_image_gallery', $product_image_gallery );
                 
            }else{  

                // $product_data_final = array(
                //     'ID' => $product_id,
                //     'post_title'    => $product_data->title,
                //     'post_content'  => ($product_data->publicDescription)->summary,
                //     'post_excerpt'  => 'Simple product',
                //     'post_status'   => 'publish',
                //     'post_type'     => 'product',
                //     'post_category' => array(),
                // );

                //  wp_update_post( $product_data_final );

                //  wp_set_object_terms( $product_id, 'ovacrs_car_rental', 'product_type' );
                 update_post_meta( $product_id, '_regular_price', ($product_data->prices)->basePrice );
                 update_post_meta( $product_id, '_price', ($product_data->prices)->basePrice );

                //====================================================
                // For Updating Total rooms count, amenities and categories
                //=====================================================
                //  update_post_meta( $product_id, 'ovacrs_car_count', 30 );
                //  update_post_meta( $product_id, 'ovacrs_features_label', $product_data->amenities );
                //  wp_set_object_terms($product_id, array($product_data->type,$product_data->roomType,$product_data->propertyType), 'product_cat', true);

                //==================== For Updating Featured image ===========================

                // $image_url = isset(($product_data->picture)->thumbnail) ? ($product_data->picture)->thumbnail : "";
                // $image_name       = basename($image_url);
                // $upload_dir       = wp_upload_dir(); // Set upload folder
                // $image_data       = file_get_contents($image_url); // Get image data
                // $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
                // $filename         = basename( $unique_file_name ); // Create image file name

                // if( wp_mkdir_p( $upload_dir['path'] ) ) {
                // $file = $upload_dir['path'] . '/' . $filename;
                // } else {
                // $file = $upload_dir['basedir'] . '/' . $filename;
                // }

                // file_put_contents( $file, $image_data );

                // $wp_filetype = wp_check_filetype( $filename, null );

                // $attachment = array(
                //     'post_mime_type' => $wp_filetype['type'],
                //     'post_title'     => sanitize_file_name( $filename ),
                //     'post_content'   => '',
                //     'post_status'    => 'inherit'
                // );

                // $attach_id = wp_insert_attachment( $attachment, $file, $product_id );

                // require_once(ABSPATH . 'wp-admin/includes/image.php');

                // $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                // wp_update_attachment_metadata( $attach_id, $attach_data );

                // set_post_thumbnail( $product_id, $attach_id );
                ?>
                    <div class="productupdated">
                        <h3> <?php echo $product_data->title ?></h3>
                    </div>
                <?php
                
                //==================== For Updating Gallery ===========================

                // $gallery_imgs = isset($product_data->pictures)? $product_data->pictures : "";
                // $image_urls = [];
                //  foreach($gallery_imgs as $gallery_img ){
                //     $image_urls[] = isset($gallery_img->thumbnail) ? $gallery_img->thumbnail : "";
                // }
          
                // $product_image_gallery = get_post_meta( $product_id, '_product_image_gallery', true );
                // foreach ( $image_urls as $image_url ) {
                //     $file_array = array();
                //     $file_array['name'] = basename($image_url);
                //     $file_array['tmp_name'] = download_url( $image_url );
                //     $attachment_id = media_handle_sideload( $file_array, $product_id );
                //     $product_image_gallery .= ',' . $attachment_id;
                // }

                // update_post_meta( $product_id, '_product_image_gallery', $product_image_gallery );
                
            }
           
          
    }
    //==================================================
    // Delete product
    //==================================================
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1
    );
    $query = new WP_Query( $args );
    $skus = [];
    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $product_id = get_the_ID();
            $skus[] = get_post_meta( $product_id, '_sku', true );
        }    
    }
    //get all guesty skus
    $guesty_skus = [];
    foreach($products_data as $product_data){
        $guesty_skus[] = $product_data->_id;
    }


    foreach ($skus as $sku) {

        $key = $sku;

        foreach($guesty_skus as $guesty_sku ){
             if($sku == $guesty_sku){
                $key = 0;
             }
        }

        if($key != 0){

            $product_id = id_by_sku( $key );
            wp_delete_post( $product_id, true );
        }

        
     }
}
    
   
	?>
    <div class="wc_create_product ">
      
    <form class="form" action="" method="post">
         <h2>Sync Products using Guesty API</h2>
         <button name="createproduct" type="submit" id="createOrder">Sync Products</button>
    </form>

    </div>
	

	<?php


}

//====================================================
//              schedule room update
//====================================================

function schedule_room_post() {
    if (! wp_next_scheduled ( 'update_room_hook' )) {
        wp_schedule_single_event(time() + 43200, 'update_room_hook');
    }
}
add_action('wp', 'schedule_room_post');

function update_room_function() {
        //Get Guesty data 
        $get_access_token = get_option('guesty_access_token');
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://booking.guesty.com/api/listings',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'accept: application/json; charset=utf-8',
            "Authorization: Bearer $get_access_token",
            'Cookie: JSESSIONID=E7EE5D0DBE7ACF9F7682181E11B1A63F; JSESSIONID=5A9235A1A3EB6C2CBAD92FB5EE88B701'
        ),
        ));
        $data = curl_exec($curl);
        curl_close($curl);
        $final_data = json_decode($data);
        $products_data = $final_data->results;

        foreach($products_data as $product_data){

            $sku = $product_data->_id;
            $product_id = id_by_sku( $sku );
            //wp_set_object_terms( $product_id, 'ovacrs_car_rental', 'product_type' );
             update_post_meta( $product_id, '_regular_price', ($product_data->prices)->basePrice );
             update_post_meta( $product_id, '_price', ($product_data->prices)->basePrice );

        }

    

}
add_action('update_room_hook', 'update_room_function');
