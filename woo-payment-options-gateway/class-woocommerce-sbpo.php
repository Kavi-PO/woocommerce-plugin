<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class WC_Payment_sbpo extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'sbpo';
        $this->method_title = __('WooCommerce Payment Options','woocommerce-payment-sbpo');
        $this->title = $this->get_option('sbpo_title');
        $this->has_fields = true;
        $this->init_form_fields();
        $this->init_settings();
        $this->sbpo_install();
        $this->enabled = $this->get_option('enabled');
        $this->description = $this->get_option('sbpo_description');
       
        $this->sbpo_sandboxmode = $this->get_option('sbpo_sandboxmode');
        $this->sbpo_auto_capture = $this->get_option('sbpo_auto_capture'); 

        if( $this->sbpo_auto_capture == 'yes'){
            $this->auto_capture = true;
        } else{
            $this->auto_capture = false;
        }

        if($this->sbpo_sandboxmode){
            $this->merchant_id = $this->get_option('sbpo_sn_merchant_id');
            $this->api_url = $this->get_option('sbpo_sn_api_url');
            $this->api_key = $this->get_option('sbpo_sn_api_key');
        }else{
           $this->merchant_id = $this->get_option('sbpo_merchant_id');
           $this->api_url = $this->get_option('sbpo_api_url');
           $this->api_key = $this->get_option('sbpo_api_key');
        }

        
        add_action( 'admin_enqueue_scripts', array($this, 'sbpo_load_admin_scripts') );

        add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_sbpo_callback', array( $this, 'sbpo_callback_handler'));
        add_action('woocommerce_api_sbpo_check_status', array( $this, 'sbpo_check_status_handler'));
        add_action('woocommerce_api_sbpo_redirect', array( $this, 'sbpo_redirect_handler'));
        add_action('woocommerce_api_sbpo_cancel', array( $this, 'sbpo_cancel_handler'));

        add_filter( 'woocommerce_endpoint_order-received_title', array($this, 'filter_woocommerce_endpoint_order_received_title'), 10, 3 );
    }

    public function sbpo_load_admin_scripts(){ 
        wp_enqueue_media();
        wp_register_script('sbpo-admin-script',get_site_url() . '/wp-content/plugins/woo-payment-options-gateway/assets/main.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('sbpo-admin-script'); 
    }

    public function get_icon() {

        $icon_html = '';
        $schemes = $this->get_option('sbpo_logos_scheme');
             
        if(isset($schemes) && !empty($schemes)){
            foreach ($schemes as $i ) {
               $src = get_site_url() . "/wp-content/plugins/woo-payment-options-gateway/assets/{$i}.png";
               $icon_html .= '<img style="height: 45px;" src="' . esc_attr( $src ) . '"  />';
            } 
        }else{
            $icon_html = '';
        }     
        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

  
    function filter_woocommerce_endpoint_order_received_title( $title, $endpoint, $action ) {   
        global $wp;

        $order_id  = absint( $wp->query_vars['order-received'] );
        $order     = wc_get_order($order_id);

        if ($order->get_status() == 'failed') $title = __( 'Payment failed', 'woocommerce' );
        
        return $title;
    }


    public function cards_scheme(){
        $scheme = array(
           'visa' =>  __( 'Visa', 'woocommerce-payment-sbpo' ),
           'visa_debit' =>  __( 'Visa Debit', 'woocommerce-payment-sbpo' ),
           'maestro' =>  __( 'Maestro', 'woocommerce-payment-sbpo' ),
           'mastercard' =>  __( 'MasterCard', 'woocommerce-payment-sbpo' ),
           'mastercard_debit' =>  __( 'MasterCard Debit', 'woocommerce-payment-sbpo' ),
           'amex' =>  __( 'AMEX', 'woocommerce-payment-sbpo' ),
           'diners' =>  __( 'Diners', 'woocommerce-payment-sbpo' ),
           'discover_card' =>  __( 'Discover card', 'woocommerce-payment-sbpo' ),
           'jcb' =>  __( 'JCB', 'woocommerce-payment-sbpo' ),         
        );
        return  $scheme;
    }

    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable', 'woocommerce-payment-sbpo' ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable Payment Options Plugin', 'woocommerce-payment-sbpo' ),
                'default' 		=> 'no'
            ),
            'sbpo_title' => array(
                'title'       => __( 'Title', 'woocommerce-payment-sbpo' ),
                'type'        => 'text',
                'description' => __( 'Enter a title to be displayed on the checkout page', 'woocommerce-payment-sbpo' ),
            ),
            'sbpo_description' => array(
                'title'       => __( 'Description', 'woocommerce-payment-sbpo' ),
                'type'        => 'text',
                'description' => __( 'Enter a description to be displayed on the checkout page', 'woocommerce-payment-sbpo' ),
            ),
            'sbpo_logos_scheme' => array(
                'title'       => __( 'Cards Scheme Icons', 'woocommerce-payment-sbpo' ),
                'type'        => 'multiselect',
                'class'       => 'wc-enhanced-select',
                'css'         => 'width: 400px;',
                'options'           => $this->cards_scheme(),
                'description' => __( 'Select the card icons to display on the checkout page.', 'woocommerce-payment-sbpo' ),
            ),
            
            'sbpo_sandboxmode' => array(
                'title'       => __( 'Sandbox Mode', 'woocommerce-payment-sbpo' ),
                'type'        => __( 'checkbox' ),
                'label' => __( 'Allows you to test a payment processor without having to pay the transaction you have submitted', 'woocommerce-payment-sbpo' ),
                'default'       => 'no'
            ),
            
            'sbpo_merchant_id' => array(
                'title'       => __( 'Merchant ID', 'woocommerce-payment-sbpo' ),
                'type'        => __( 'text' ),
                'description' => __( 'Enter the Merchant ID provided by Payment Options', 'woocommerce-payment-sbpo' ),
            ),
            'sbpo_api_url' => array(
                'title'       => __( 'API URL', 'woocommerce-payment-sbpo' ),
                'type'        => __( 'text' ),
                'description' => __( 'Enter the API URL provided by Payment Options', 'woocommerce-payment-sbpo' ),
            ),

            'sbpo_api_key' => array(
                'title'       => __( 'API Key', 'woocommerce-payment-sbpo' ),
                'type'        => __( 'text' ),
                'description' => __( 'Enter the API key provided by Payment Options', 'woocommerce-payment-sbpo' ),
            ),

            'sbpo_sn_merchant_id' => array(
                'title'       => __( 'Merchant ID', 'woocommerce-payment-sbpo' ),
                'type'        => __( 'text' ),
                'description' => __( 'Enter the Merchant ID provided by Payment Options', 'woocommerce-payment-sbpo' ),
            ),
            'sbpo_sn_api_url' => array(
                'title'       => __( 'API URL', 'woocommerce-payment-sbpo' ),
                'type'        => __( 'text' ),
                'description' => __( 'Enter the API URL provided by Payment Options', 'woocommerce-payment-sbpo' ),
            ),

            'sbpo_sn_api_key' => array(
                'title'       => __( 'API Key', 'woocommerce-payment-sbpo' ),
                'type'        => __( 'text' ),
                'description' => __( 'Enter the API key provided by Payment Options', 'woocommerce-payment-sbpo' ),
            ),
        );
    }

    public function admin_options() {
        ?>
       
        <h3><?php _e( 'Payment Options Plugin Settings', 'woocommerce-payment-sbpo' ); ?></h3>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <table class="form-table">
                        <?php $this->generate_settings_html();?>
                    </table>
                </div>
            </div>
        </div>
        <div class="clear"></div>
        <?php
    }

    function sbpo_install() {
        global $wpdb;

        $table_name = $wpdb->prefix . "sbpo_orders";

        $charset_collate = $wpdb->get_charset_collate();

        if($wpdb->get_var( "show tables like '$table_name'" ) != $table_name) {
            $sql = "CREATE TABLE $table_name (
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                        order_id int(11) NOT NULL,
                        transaction_token varchar(50) DEFAULT '' NOT NULL,
                        status varchar(15),
                        PRIMARY KEY  (id),
                        UNIQUE KEY id_unique_trans (order_id, transaction_token)
                ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

    public function process_payment($order_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sbpo_orders';

        $customer_order = new WC_Order($order_id);

        $result = $wpdb->get_row("SELECT * FROM $table_name WHERE (order_id = $order_id AND transaction_token = '')");

        if ($result) {
            $wpdb->update(
                $table_name,
                array(
                    'date' => current_time( 'mysql'),
                ),
                array(
                    'order_id'          => $order_id,
                    'transaction_token' => ''
                )
            );
        } else {
            $wpdb->query(
                $wpdb->prepare("
                    INSERT INTO `$table_name`( date, order_id, status )
                    VALUES ( %s, %d, %s )
                ",
                    current_time( 'mysql'), $order_id, 'pending'
                )
            );
        }

       $params = array(
            "return_url"=> array(
                "webhook_url"=> get_site_url() . "/wc-api/sbpo_callback?order_id=" . $order_id,
                "success_url"=> get_site_url()."/wc-api/sbpo_redirect?order_id=" . $order_id,
                "decline_url"=> get_site_url() . "/wc-api/sbpo_cancel?order_id=" . $order_id,
                "cancel_url"=> get_site_url() . "/wc-api/sbpo_cancel?order_id=" . $order_id
            ),
            "amount"=> $customer_order->get_total(), 
            "currency" => get_woocommerce_currency(),
            "merchant_id"=>  $this->merchant_id,
            "merchant_txn_ref"=> $customer_order->get_order_number(),
            "billing_address"=> array(
                "country"     => $customer_order->get_billing_country(),
                "email"       => $customer_order->get_billing_email(),
                "phone"       => $customer_order->get_billing_phone(),
                "address1"     => $customer_order->get_billing_address_1(),
                "city"        => $customer_order->get_billing_city(),
                "state"       => $customer_order->get_billing_state(),
                "postal_code" => $customer_order->get_billing_postcode()
            ),

            "shipping_address"=> array(
                "country"     => $customer_order->get_shipping_country(),
                "email"       => $customer_order->get_billing_email(),
                "phone"       => $customer_order->get_billing_phone(),
                "address1"     => $customer_order->get_shipping_address_1(),
                "city"        => $customer_order->get_shipping_city(),
                "state"       => $customer_order->get_shipping_state(),
                "postal_code" => $customer_order->get_shipping_postcode()
            ),
        );

        try {
            $response = json_decode($this->getPaymentURL($this->api_url, $params));
        }
        catch (Exception $e) {
            echo $e->getMessage();
            die();
        }

        $url = $response->url;

        $customer_order->update_status('pending payment');
    
        return array(
           'result'   => 'success',
           'redirect'=> $url
        ); 
    
    }

    public function getPaymentURL($url, $params) {

        $ch = curl_init();

        curl_setopt_array($ch, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode($params),
          CURLOPT_HTTPHEADER => array(
            "authorization: Basic ".$this->api_key,
            "cache-control: no-cache",
            "content-type: application/json",
            "x-api-key: bLm8c1C0fL3FtPzrjSr0"
          ),
        ));

        $response = curl_exec($ch);
        $err = curl_error($ch);

        curl_close($ch);

        if ($err) {
           wc_add_notice(  'Connection error.', 'error: '.$err );
           return;
        } else {
          return $response;
        }

    }

    public function sbpo_check_status_handler() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sbpo_orders';

        $order_id = $_POST['order_id'];

        $result = $wpdb->get_row("SELECT status FROM $table_name WHERE (order_id = $order_id AND transaction_token = '')");

        echo $result->status; die;
    }
    
    public function sbpo_cancel_handler() {

        $order_id = $_GET['order_id'];
        $order    = wc_get_order($order_id);
        $order_status  = $order->get_status();
        if($order_status != "failed"){ 
             $order->update_status( 'cancelled');
        }

		wp_redirect(wc_get_cart_url());
    }

    public function sbpo_redirect_handler() {
 
        $order_id = $_GET['order_id'];
        $order    = wc_get_order($order_id);
        wp_redirect($this->get_return_url($order));
        die;
    }

    public function sbpo_callback_handler() {
        $post_data = json_decode(file_get_contents("php://input"));
        
        $order_id          = $_GET['order_id'];
        $success           = $post_data->success;
        $transaction_token = $post_data->transaction_details->id;
        $order             = wc_get_order($order_id);
        

        if ($success) {
            $order->update_status( 'processing');
            $order->payment_complete();
            wc_reduce_stock_levels($order_id);

        } else {
            $order->update_status('failed');
            $order->needs_payment();
        }

        $note = __("Transation ID is '{$transaction_token}' from Payment Options");

        // Add the note
        $order->add_order_note( $note );

        global $wpdb;
        $table_name = $wpdb->prefix . 'sbpo_orders';

        $wpdb->update(
            $table_name,
            array(
                'transaction_token' => $transaction_token,
                'status'            => $success ? 'success' : 'failed'
            ),
            array(
                'order_id'          => $order_id,
                'transaction_token' => ''
            )
        );

    }
}