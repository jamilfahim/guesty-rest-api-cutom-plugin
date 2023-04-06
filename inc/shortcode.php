<?php 
//shortcode for update rooms

function id_by_sku_shortcode($sku){

   global $wpdb;

   $product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

   return $product_id;
}

function update_product_shortcode(){
	ob_start();


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
            $product_id = id_by_sku_shortcode( $sku );
            //wp_set_object_terms( $product_id, 'ovacrs_car_rental', 'product_type' );
             update_post_meta( $product_id, '_regular_price', ($product_data->prices)->basePrice );
             update_post_meta( $product_id, '_price', ($product_data->prices)->basePrice );

        }



	return ob_get_clean();

}
add_shortcode( 'update-product-room', 'update_product_shortcode' );