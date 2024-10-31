<?php 


/*

Plugin Name: MyUserPay
Plugin URI:  myuser.com
Description: Accept Payments Online via myuser.com
Author: MyUser LLC
version: 3.0.0.0.3

*/
ini_set('display_errors','Off');
ini_set('error_reporting', E_ALL );
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
defined('ABSPATH') or die('You don\'t have permission to access this file');
//plugin slug myuserpay
if(!function_exists('add_action')){
    die('You don\'t have permission to access this file because add_action does not exist');
}
// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;
define('mup_MyUserPayments_IMG', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/public/images/');

define("mup_wordpresspayplugin_plugin_folder", "MyUserPay");

add_action( 'plugins_loaded', 'mup_init_WPressPlugin',0 );

function mup_add_your_customuser_gateway_class( $methods ) {
    $methods[] = 'MUP_WC_Gateway_WPressPlugin'; 
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'mup_add_your_customuser_gateway_class' );



add_action('admin_menu','mup_wordpresspayplugin_addMenu');






/*BELOW IS REDIRECT FUNCTION THAT WORKS BASED ON ACCOUNT*/

/**
* Decrypt data from a CryptoJS json encoding string
*
* @param mixed $passphrase
* @param mixed $jsonString
* @return mixed
*/
function cryptoJsAesDecrypt($passphrase, $jsonString,$params=array()){
  if(!isset($params['return_direct'])){
    $params['return_direct']=false;
  }
    $jsondata = json_decode($jsonString, true);
    $salt = hex2bin($jsondata["s"]);
    $ct = base64_decode($jsondata["ct"]);
    $iv  = hex2bin($jsondata["iv"]);
    $concatedPassphrase = $passphrase.$salt;
    $md5 = array();
    $md5[0] = md5($concatedPassphrase, true);
    $result = $md5[0];
    for ($i = 1; $i < 3; $i++) {
        $md5[$i] = md5($md5[$i - 1].$concatedPassphrase, true);
        $result .= $md5[$i];
    }
    $key = substr($result, 0, 32);
    $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
    if($params['return_direct']==true){
      return $data;
    }
    return json_decode($data, true);
}

define('MULTIDOMAIN_PAYMENTS_CONNECTOR_NAME', 'CROSSDOMAIN_PAYMENT_CONNECTOR');
define('CROSSDOMAIN_PAYMENT_CONNECTOR_NAMESPACE', "multi-domain-payments-connector/v1");
define('REDIRECT_CARD_SUCCESS_BACK',"/?rest_route=/multi-domain-payments-connector/v1/credit-card/payment/success");

add_action('woocommerce_before_checkout_form', 'digitalcart_before_checkout_redirect', 5);
function filterEmoji($str)
{
    $str = preg_replace_callback(    
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);
    return $str;
}

function redirect_func_get_product_from_cart(){
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $products = [];
    foreach ($items as $item => $values) {
        $_product = wc_get_product($values['data']->get_id());
        $price = get_post_meta($values['product_id'], '_price', true);

        $products[$values['data']->get_id()] = new \stdClass();
        $products[$values['data']->get_id()]->item = new \stdClass();
        $products[$values['data']->get_id()]->item->qty = $values['quantity'];
        $products[$values['data']->get_id()]->item->name = filterEmoji(str_replace("?"," ",str_replace("'","&#39;",$_product->get_title())));
        // $products[$values['data']->get_id()]->item->name = htmlentities($_product->get_title());
        $products[$values['data']->get_id()]->item->price = $price;
        $products[$values['data']->get_id()]->price = $price * $values['quantity'];
        $products[$values['data']->get_id()]->qty = $values['quantity'];
        $products[$values['data']->get_id()]->image = get_the_post_thumbnail_url($_product->id);
    }
    return $products;
}



/**
https://stackoverflow.com/questions/24337317/encrypt-with-php-decrypt-with-javascript-cryptojs
* Encrypt value to a cryptojs compatiable json encoding string
*
* @param mixed $passphrase
* @param mixed $value
* @return string
*/
function cryptoJsAesEncrypt($passphrase, $value,$params=array()){
    if(!isset($params['direct'])){
      //enc_direct
      $params['direct']=false;
    }
    $salt = openssl_random_pseudo_bytes(8);
    $salted = '';
    $dx = '';
    while (strlen($salted) < 48) {
        $dx = md5($dx.$passphrase.$salt, true);
        $salted .= $dx;
    }
    $key = substr($salted, 0, 32);
    $iv  = substr($salted, 32,16);
    $encrypted_data_value=$value;
    if($params['direct']==false){
      $encrypted_data_value=json_encode($encrypted_data_value);
    }
    $encrypted_data = openssl_encrypt($encrypted_data_value, 'aes-256-cbc', $key, true, $iv);
    $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
    return json_encode($data);
}
add_action('rest_api_init', 'register_routes');

function register_routes()
{
    register_rest_route(CROSSDOMAIN_PAYMENT_CONNECTOR_NAMESPACE, '/payment/create_order', array(
        'methods' => 'POST',
        'callback' => 'redirect_start_create_order',
    ));
    // register_rest_route(CROSSDOMAIN_PAYMENT_CONNECTOR_NAMESPACE, '/payment/noti_order_success', array(
    //     'methods' => 'POST',
    //     'callback' => 'noti_order_success',
    // ));
    // register_rest_route(CROSSDOMAIN_PAYMENT_CONNECTOR_NAMESPACE, '/payment/get_config', array(
    //     'methods' => 'POST',
    //     'callback' => 'get_pay_config',
    // ));
    // register_rest_route(CROSSDOMAIN_PAYMENT_CONNECTOR_NAMESPACE, '/payment/back_order_success', array(
    //     'methods' => 'POST',
    //     'callback' => 'back_order_success',
    // ));
}

function redirect_start_create_order(\WP_REST_Request $request){
    $enc_data = json_decode($request->get_body(), true);
    if (!$enc_data) {
        $enc_data = $_REQUEST;
    }
    // var_dump($enc_data['order_data']);
    $data=$enc_data;
    // var_dump($enc_data['order_data']);
    // echo$data['address']['email'];
    // exit;
    $address = array(
        'first_name' => $data['order_data']['address']['first_name'],
        'last_name' => $data['order_data']['address']['last_name'],
        // 'company' => $response->checkoutJSON->company,
        'email' => $data['order_data']['address']['email'],
        'phone' => trim($data['order_data']['address']['phone_prep'].$data['order_data']['address']['phone']),
        'address_1' => $data['order_data']['address']['address_1'],
        // 'address_2' => $response->checkoutJSON->address2,
        'city' => $data['order_data']['address']['city'],
        'state' => $data['order_data']['address']['state'],
        'postcode' => $data['order_data']['address']['zip'],
        'country' => $data['order_data']['address']['country']
    );

    // Now we create the order
    // var_dump($data['ip']);die;
    $order = wc_create_order();
    $order->set_customer_ip_address($data['ip']);
    foreach ($data['products'] as $item => $values) {
        $_product = wc_get_product($item);
        $order->add_product($_product, $values['qty']); // This is an existing SIMPLE product
    }
        // var_dump($data['products']);
    $order->set_address($address, 'billing');
    $order->set_address($address, 'shipping');
    $order->set_payment_method('Credit Card Secure Payments');
    $order->set_payment_method_title('Credit Card Secure Payments');
    $order->calculate_totals();
    $order->update_status("Pending", 'Credit Card Create Order', TRUE);

    $re_back_data = [];
    $re_back_data['order_id'] = $order->get_id();
    $re_back_data['order_key'] = $order->order_key;
    $re_back_data['timezone_string'] = get_option('timezone_string');
    $re_back_data['totalPrice'] = $order->get_total();
    $re_back_data['msg_url'] = site_url();
    // var_dump($order->get_total());exit;
    // $re_back_data['card_success_back'] = CARD_SUCCESS_BACK;
    // $re_back_data['checkout_order_receive'] = "/checkout-2/order-received/" . $order->get_id() . "/?key=" . $order->order_key;

    // if ($data['go_to_this_way']=='source_pay') {
    //     // code...
    //     $re_back_data['config'] = get_pay_config('source_pay');
    // }else{

    //     $re_back_data['config'] = get_pay_config('',$data['source_domain']);
    // }
// var_dump($order);
    //process payment here
$MUP_WC_Gateway_WPressPlugin= new MUP_WC_Gateway_WPressPlugin();
$_POST['token']=$data['order_data']['payment_token'];
if(!isset($MUP_WC_Gateway_WPressPlugin->settings['one_dollar'])){
    //$this->settings['one_dollar'] 1 dollar = X onlarin
    $one_dollar=1;
}else{
    $one_dollar = $MUP_WC_Gateway_WPressPlugin->settings['one_dollar'];
}
if($MUP_WC_Gateway_WPressPlugin->settings['one_dollar']==0){
    $one_dollar=1;
}
// var_dump(round(intval($order->order_total)*100/$one_dollar));exit;
$charge_data = $MUP_WC_Gateway_WPressPlugin->charge_user(round(intval($order->order_total)*100/$one_dollar),array('order'=>$order,'order_id'=> $order->get_id()));
                        if(empty($charge_data['charge'])){
                            //error happened
                            $error_mes = '';
                            $error_dev_mes = 'Transaction failed.';
                            if(isset($charge_data['error_body'])){
                                /*if(isset($charge_data['error_body']['message'])){
                                    $error_mes=$charge_data['error_body']['message'];
                                }
                                if($charge_data['error_body']['error']){
                                    //var_dump($charge_data['error_body']['error']);
                                    if(isset($charge_data['error_body']['error']->message)){
                                        $error_dev_mes=$charge_data['error_body']['error']->message;
                                    }
                                }*/
                                $error_mes=$error_dev_mes=$charge_data['error_body'];

                            }
                             $order->update_status('failed');
                            $note = __("Thank you for the order. However, the transaction has been declined. ".$error_mes);
                            $order->add_order_note($note);

                            do_action( 'woocommerce_set_cart_cookies',  true );
                            wc_add_notice(  "Transaction failed: ".$error_mes, 'error' );
                            wp_redirect(get_permalink( woocommerce_get_page_id( 'cart' ) ));
                            exit;
                        }else{
                            $order->payment_complete();
                            WC()->cart->empty_cart();
                            $order->reduce_order_stock();
                            $order->add_order_note('Payment successfully completed.<br/> ('.sanitize_text_field($charge_data['charge']['id']).')');
                            $thankyou_url = site_url() . "/checkout/order-received/" . $order->get_id() . "/?key=" . $order->order_key;
                            wp_redirect($thankyou_url);
                            exit;
                        }

    echo json_encode($re_back_data);
    exit;
}

function digitalcart_before_checkout_redirect()
{
$MUP_WC_Gateway_WPressPlugin_prepare_script= new MUP_WC_Gateway_WPressPlugin();

$account_data  = $MUP_WC_Gateway_WPressPlugin_prepare_script->get_account_details();//is it redirect or what 
// var_dump($account_data);
if(isset($account_data['connection_redirect_url'])){
    if(!is_null($account_data['connection_redirect_url'])){



    $products = redirect_func_get_product_from_cart();
    $curr = new \stdClass();
    $curr->name = get_woocommerce_currency("USD");
    $curr->sign = get_woocommerce_currency_symbol("USD");

    $tssDataCollection = new \stdClass();
    $tssDataCollection->curr = $curr;
    $tssDataCollection->products = $products;
    $tssDataCollection->totalPrice = WC()->cart->total;
    $tssDataCollection->primaryWebsiteBaseURL = site_url();
    $tssDataCollection->primaryCartURL = site_url() . "/cart/";
    $tssDataCollection->primaryCardSuccessURL = CARD_SUCCESS_BACK;
    $tssDataCollection->primaryCreateOrder = "/?rest_route=/multi-domain-payments-connector/v1/payment/create_order";
 
    $tssDataCollection->selected_public_key=$account_data['selected_public_key'];
    $tssDataCollection->request_full_url=$account_data['request_full_url'];
    $tssDataCollection->class_name=$account_data['class_name'];
    $tssDataCollection->class_name_BB=$account_data['class_name_BB'];
    $tssDataCollection->private_key=$account_data['private_key'];
    $tssDataCollection->time_start=time();

    $discount['code'] = WC()->cart->get_applied_coupons();
    $discount['amount'] = WC()->cart->get_coupon_discount_amount( $discount['code'][0] );
//      protected array<string|int, mixed> $totals = array()

// $update_ar['saved_cookie']=
                $crsf_token=cryptoJsAesEncrypt($account_data['private_key'],json_encode($tssDataCollection,true),array('direct'=>true));
                //json_encode($tssDataCollection)
// $crsf_token=
//digitalmarketCartData
// var_dump(json_encode($tssDataCollection));die;
    // $newUrl = 'https://www.kerdx.com/order_pay/checkout/pay.php?no=';
    // $newUrl = 'https://charmingsensefit.com/order_pay/checkout/pay.php?no=';
?>
    <form id="DigitalCartForm" action="<?php echo $account_data['connection_redirect_url']; ?>?dc=<?php echo wp_create_nonce("digitalcart"); ?>" method="post">
        <input type="hidden" name="public_key" value='<?php echo $account_data['raw_public_key']; ?>' />
        <input type="hidden" name="crsf_token" value='<?php echo $crsf_token; ?>' />
        <input type="hidden" name="digitalmarketToken" value="<?php echo wp_create_nonce("digitalcart"); ?>" />
        <input type="hidden" name="discount" value='<?php echo json_encode($discount); ?>' />
    </form>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            $("form[name='checkout']").hide();
            $("#DigitalCartForm").submit();
        });
    </script>
<?php



    }
}

// die;
}

/*ABOVE IS REDIRECT FUNCTION THAT WORKS BASED ON ACCOUNT*/






/*NEW FIELD FOR INLINE BELOW*/
add_action( 'woocommerce_after_order_notes', 'mup_new_inline_hidden_text_and_new_checkout_field' );
function mup_new_inline_hidden_text_and_new_checkout_field( $checkout ) {

    // Generating the VID number
    // $vid_number = wp_rand(10000,99999);

    // Output the hidden field
    echo '<div id="user_link_hidden_checkout_field">
            <input type="hidden" class="input-hidden" name="inline_mup_transaction_id" id="inline_mup_transaction_id" value="">
    </div>';
}

// Saving the hidden field value in the order metadata
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_hidden_field' );
function save_custom_checkout_hidden_field( $order_id ) {
    if(!isset($_POST['inline_mup_transaction_id'])){
        $_POST['inline_mup_transaction_id']='';
    }
    if (  empty( $_POST['inline_mup_transaction_id'] ) ) {
        $_POST['inline_mup_transaction_id']='';
    }
      update_post_meta( $order_id, '_inline_mup_transaction_id', sanitize_text_field( $_POST['inline_mup_transaction_id'] ) );

}
/*
add_filter( 'woocommerce_checkout_fields' , 'mup_new_inline_hidden_text_and_new_checkout_field' );

 function mup_new_inline_hidden_text_and_new_checkout_field( $fields ) {

  $fields['billing']['new_billing_field'] = array(

    'label'     => __('Inline Transaction ID', 'woocommerce'),

    'placeholder'   => _x('Inline Transaction ID', 'placeholder', 'woocommerce'),
    'class'     => array('form-row-wide'),
    'style'=>'display:none;',
    'clear'     => true

);

  return $fields;

  }*/


add_action( 'wp_ajax_nopriv_mup_calculate_total', 'wp_ajax_mup_calculate_total_func' );
add_action( 'wp_ajax_mup_calculate_total', 'wp_ajax_mup_calculate_total_func' );
/*wp_localize_script( 'ajax-script', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ));*/
function wp_ajax_mup_calculate_total_func() {
    global $woocommerce;
    /*$total_cart_amount = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_total() ) );
    var_dump(WC()->cart->cart_contents_total);exit;
    wp_send_json_success( array('total'=>$total_cart_amount*100,'total_in_html'=>$woocommerce->cart->get_total()), 200);*/

    wp_send_json_success( array('total'=>intval(WC()->cart->total*100),'total_in_html'=>$woocommerce->cart->get_total()), 200);
            exit();
    
}

function add_fake_error_for_inline($posted) {
    $MUP_WC_Gateway_WPressPlugin_prepare_script= new MUP_WC_Gateway_WPressPlugin();
    $inline_pay_active_mup=$MUP_WC_Gateway_WPressPlugin_prepare_script->settings['inline_pay'];
    if(!is_null($inline_pay_active_mup)){
        if($inline_pay_active_mup=='no'){
            return;
        }
    }
    if(isset($_POST['confirm-order-flag'])){
        if ($_POST['confirm-order-flag'] == "1") {
            wc_add_notice( __( "custom_mup_notice", 'fake_error_for_inline' ), 'error');
        } 
    }
    
}

add_action('woocommerce_after_checkout_validation', 'add_fake_error_for_inline');

/*NEW FIELD FOR INLINE ABOVE*/










add_action('woocommerce_after_checkout_form', 'mup_debounce_add_jscript_checkout');

function mup_debounce_add_jscript_checkout() {

//if(is_admin()){
    ?>
    <!-- <script src="//console.re/connector.js" data-channel="customuserconnect" id="consolerescript"></script> -->

<?php
//}

    ?><style type="text/css">
    /*      margin-bottom: 4px;
    background: white;
    margin-bottom: 4px;
    height: 40px;
    padding: 11px 12px;
    padding-right: 15px;
    border-radius: 2px;
    border: 1px solid #ccc;
    width: 296px;
    position: relative;
    right: 5px;
    min-width: 100%;*/
    /*    margin-bottom: 4px;
    background: white;
    margin-bottom: 4px;
    height: 40px;
    padding: 11px 13px;
    padding-left: 10px;
    padding-right: 15px;
    border-radius: 2px;
    border: 1px solid #ccc;
    width: 308px;
    position: relative;
    right: 15px;
    min-width: 100%;*/
    /*       margin-bottom: 4px;
    background: white;
    margin-bottom: 4px;
    height: 40px;
    padding: 11px 13px;
    padding-left: 10px;
    padding-right: 15px;
    border-radius: 2px;
    border: 1px solid #ccc;
    width: 306px;
    position: relative;
    min-width: 90%;
    margin: 0px auto;
    position: absolute;
    left: 0;
    bottom: 25px;
    right: 0;
    max-width: 100%*/
    #mup_iframe_container_js{
margin-bottom: 4px;
    background: white;
    margin-bottom: 4px;
    height: 40px;
    padding: 11px 13px;
    padding-left: 10px;
    padding-right: 15px;
    border-radius: 2px;
    /* box-shadow: 0 1px 2px rgb(0 0 0 / 16%), 0 1px 2px rgb(0 0 0 / 23%); */
    border: 1px solid #ccc;
    width: 306px;
    position: relative;
    /* right: 15px; */
    /* min-width: 90%; */
    margin: 0px auto;
    position: fixed;
    left: 0;
    z-index: 999999;
    /* bottom: 25px; */
    /* right: 0; */
    /* max-width: 100%;*/

}
.card-fields-container--loaded .field--error .field__input--iframe-container{border-color:#e22120;-webkit-box-shadow:0 0 0 1px #e22120;box-shadow:0 0 0 1px #e22120}@-webkit-keyframes load{0%{left:1em;width:0}50%{left:1em;width:calc(100% - 2em)}100%{left:calc(100% - 1em);width:0}}@keyframes load{0%{left:1em;width:0}50%{left:1em;width:calc(100% - 2em)}100%{left:calc(100% - 1em);width:0}}.card-fields-iframe{-webkit-appearance:none;-moz-appearance:none;appearance:none;background:0 0;color:inherit;display:block;font:inherit;-webkit-font-smoothing:inherit;height:3.1428571429em;line-height:normal;width:100%}
.field__input--iframe-container::before {
    background: #e6e6e6;
    height: 100%;
    left: 0;
    position: absolute;
    top: 0;
    -webkit-transition: all .6s ease-out;
    transition: all .6s ease-out;
    width: 100%;
}
.field__input--iframe-container::after {
    -webkit-animation: load 1s infinite ease-in-out both;
    animation: load 1s infinite ease-in-out both;
    background: #fafafa;
    border-radius: 8px;
    height: 1em;
    margin-top: -.5em;
    top: 50%;
    -webkit-transition: all .15s ease-out;
    transition: all .15s ease-out;
}
.field__input--iframe-container::before {
    content: '';
    height: 100%;
    left: 0;
    pointer-events: none;
    position: absolute;
    top: 0;
    width: 100%;
    z-index: 1;
}
.field__input--iframe-container::after{
content: '';
    height: 15px;
    left: 0;
    pointer-events: none;
    position: absolute;
    top: 20px;
    width: 100%;
    z-index: 1;
}
/*
.mup_loader{
  margin: 0 0 2em;
  height: 100px;
  width: 20%;
  text-align: center;
  padding: 1em;
  margin: 0 auto 1em;
  display: inline-block;
  vertical-align: top;
}*/
.mup_loader svg path,
.mup_loader svg rect{
  fill: #fff;
}
/*margin-bottom: 4px;
   background: white;
    margin-bottom: 4px;
    height: 35px;
    padding: 9px;
    border-radius: 2px;
    box-shadow: 0 1px 2px rgb(0 0 0 / 16%), 0 1px 2px rgb(0 0 0 / 23%);
    border-top: 1px solid #ccc;
    width: 296px;
    position: relative;
    right: 5px;
        min-width: 100%;*/
   </style><?php
$MUP_WC_Gateway_WPressPlugin_prepare_script= new MUP_WC_Gateway_WPressPlugin();
$inline_pay_active_mup=$MUP_WC_Gateway_WPressPlugin_prepare_script->settings['inline_pay'];
if(!is_null($inline_pay_active_mup)){
    if($inline_pay_active_mup=='no'){
        return;
    }
}
// return;
/*return;*/ //deletes inline completely even active

/**/

?>
<!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> -->

   <?php   echo $MUP_WC_Gateway_WPressPlugin_prepare_script->generate_mup_form_scripts(array('mup_javascript_mode'=>'inline','amount'=>0,'container_element_selector'=>'#mup_iframe_container_js'));

$get_connected_puadvertiser=$MUP_WC_Gateway_WPressPlugin_prepare_script->get_connected_puadvertiser;
                //var_dump($get_connected_advertiser);
                $mup_request_full_url = 'https://pay.myuser.com';
                //if someone advertising then let them handle everything.
                if(isset($get_connected_puadvertiser['connection_redirect_url'])){
                    if(!is_null($get_connected_puadvertiser['connection_redirect_url'])){
                        ?><?php
                return;
                    }
                }
                if($get_connected_puadvertiser['url']!='no_ad'){
                    $mup_request_full_url=$get_connected_puadvertiser['url'].'/req_p_main_subdomain_pay_1';
                }

                /*<span> <span class="payment-icon payment-icon--visa" data-payment-icon="visa" style="
    background-image: url(&quot;<?php echo$mup_request_full_url;?>/public/card_logos/svg_version/visa-logo.svg&quot;), none;
    cursor: default;
    margin: -0.142857em 0px -0.428571em;
    border-radius: 0.214286em;
    background-size: cover;
    background-repeat: no-repeat;
    transition: all 0.2s ease-in-out 0s;
    width: 2.71429em;
    height: 1.71429em;
    margin: 4px;
"> <span class="visually-hidden" style=" opacity: 0; "> Visa, </span> </span> <span class="payment-icon payment-icon--master" style="
    background-image: url(<?php echo$mup_request_full_url;?>/public/card_logos/svg_version/master-logo.svg), none;
    cursor: default;
    margin: -0.142857em 0px -0.428571em;
    border-radius: 0.214286em;
    background-size: cover;
    background-repeat: no-repeat;
    transition: all 0.2s ease-in-out 0s;
    width: 2.71429em;
    height: 1.71429em;
    margin: 4px;
" data-payment-icon="master"> <span class="visually-hidden" style="
    opacity: 0;
">visa</span> </span> <span class="payment-icon payment-icon--american-express" data-payment-icon="american-express" style="
    background-image: url(<?php echo$mup_request_full_url;?>/public/card_logos/svg_version/amex-logo.svg), none;
    cursor: default;
    margin: -0.142857em 0px -0.428571em;
    border-radius: 0.214286em;
    background-size: cover;
    background-repeat: no-repeat;
    transition: all 0.2s ease-in-out 0s;
    width: 2.71429em;
    height: 1.71429em;
    margin: 4px;
"> <span class="visually-hidden" style="
    opacity: 0;
">visa</span> </span> <span class="payment-icon payment-icon--discover" data-payment-icon="discover" style="
    background-image: url(<?php echo$mup_request_full_url;?>/public/card_logos/svg_version/discover-logo.svg), none;
    cursor: default;
    margin: -0.142857em 0px -0.428571em;
    border-radius: 0.214286em;
    background-size: cover;
    background-repeat: no-repeat;
    transition: all 0.2s ease-in-out 0s;
    width: 2.71429em;
    height: 1.71429em;
    margin: 4px;
"> <span class="visually-hidden" style="
    opacity: 0;
">visa</span> </span> <span class="payment-icon-list__more"> <span class="content-box__small-text" style="
    font-size: 0.857143em;
    color: rgb(115, 115, 115);
"> and more… </span> </span> </span>*/
    ?>
<script type="text/javascript">
 
//for="payment_method_mup_wordpresspayplugin"
//payment_method_mup_wordpresspayplugin
/*if(typeof jQuery=='undefined') {
    var headTag = document.getElementsByTagName("head")[0];
    var jqTag = document.createElement('script');
    jqTag.type = 'text/javascript';
    jqTag.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js';
    jqTag.onload = myJQueryCode;
    headTag.appendChild(jqTag);
} else {
     myJQueryCode();
}*/
var mup_checkout_total_amount=0;
var mup_old_checkout_total_amount=-1;
function reposition_mup_card_elems(){
    if(jQuery('#mup_wordpresspayplugin_space_example_elem').length==0){
        jQuery('#mup_iframe_container_js').hide();
    }else{
        jQuery('#mup_iframe_container_js').show();
    }
    try{
        var c_elem_new_width_for_elem=jQuery('#mup_wordpresspayplugin_space_example_elem');
        if(parseFloat(jQuery('.payment_method_mup_wordpresspayplugin').css('width'))<parseFloat(c_elem_new_width_for_elem.css('width'))){
            c_elem_new_width_for_elem=jQuery('.payment_method_mup_wordpresspayplugin');
        }
        if(jQuery('#mup_wordpresspayplugin_space_example_elem').is(':visible')==false){
            jQuery('#mup_iframe_container_js').hide();
        }else{
            jQuery('#mup_iframe_container_js').show();
        }
var mup_space_example_field_rect=c_elem_new_width_for_elem.get(0).getBoundingClientRect();
    // var mup_card_field_rect=c_elem_new_width_for_elem.get(0).getBoundingClientRect();
        var mup_card_field_rect=jQuery('#mup_wordpresspayplugin_card_field_space').get(0).getBoundingClientRect();
jQuery('#mup_iframe_container_js').css({'z-index': 3000,'top':mup_card_field_rect.top+10,'left':mup_space_example_field_rect.left,'width':c_elem_new_width_for_elem.css('width')});
//height: 19.2px !important;
jQuery('#mup_iframe_container_js').css({'height':'41.2px'});
    }catch(e){}

}
var fix_scroll_mup_timeout=false;
function add_mup_card_elements_to_page(){
    if(mup_old_checkout_total_amount==mup_checkout_total_amount){
        return; //no need to add elements again
    }

    mup_old_checkout_total_amount=mup_checkout_total_amount;
    jQuery('#inline_mup_transaction_id').val('');
    jQuery('body').append('<div id="mup_iframe_container_js"><div style="background: white;color: #333333;border-color: #d9d9d9;border: 1px solid #d9d9d9;/* padding-bottom: 13px; */display: block;border-radius: 4px;/* padding: 14px; *//* padding-bottom: 13px; */width: 294px;height: 38px;/* padding: 19px 12px; */margin-top: -11px;border: none;position: absolute;left: 0;right: 0;margin: auto;top: 0;margin: 0;width: 100%;" id="card_fields_62148903062" class="field field__input--iframe-container"></div>');
    mup_finish_setup_inline_iframe(mup_checkout_total_amount);

    reposition_mup_card_elems();
}

setInterval(function(){
    reposition_mup_card_elems();
},500);

jQuery(document).scroll(function() {
    reposition_mup_card_elems();
    });
jQuery(window).on('resize', function(){
    reposition_mup_card_elems();
});
var inline_positioned_already=false;
function all_inline_setup_mup_funcs(){
    reposition_mup_card_elems();
    //same with card fields, if changes this changes
    if(inline_positioned_already==false){
        inline_positioned_already=true;
        jQuery('[for="payment_method_mup_wordpresspayplugin"]').click();
        jQuery('#payment_method_mup_wordpresspayplugin').click()
    }
    

    if(jQuery('#payment_method_mup_wordpresspayplugin').length){

    }
/*54px*/
    jQuery('.payment_box.payment_method_mup_wordpresspayplugin').html('<p id="mup_wordpresspayplugin_space_example_elem" style="font-size: 14px;line-height: 24px;border-radius: 2px;box-sizing: border-box;background-attachment: initial;background-origin: initial;background-clip: initial;padding: 9px;width: 306px;position: absolute;left: 0px;right: 0px;margin: 0px auto;max-width: 100%;min-width: 90%;/* display: none; *//* fill: rgb(180, 29, 52) !important; */background-color: rgb(254, 217, 219) !important;font-weight: 400 !important;opacity: 0;height: 1px;z-index: -1;" segoe="">a</p><p id="mup_wordpresspayplugin_error_message" style="margin-top: 0px;font-size: 14px;line-height: 24px;border-radius: 2px;box-sizing: border-box;position: relative;color: rgb(169, 68, 67);background-image: initial;background-position: initial;background-size: initial;background-repeat: initial;background-attachment: initial;background-origin: initial;background-clip: initial;padding: 9px;border-color: rgb(235, 204, 210);margin-bottom: 18px;fill: rgb(180, 29, 52) !important;background-color: rgb(254, 217, 219) !important;font-weight: 400 !important;/* width: 90%; */width: 306px;position: absolute;left: 0;right: 0;margin: 0px auto;max-width: 100%;min-width: 90%;display:none; "  segoe=""></p><div style="height: 65px;display: none;" id="mup_wordpresspayplugin_error_message_space"></div><div style="height: 56px;" id="mup_wordpresspayplugin_card_field_space"></div></div>');
     jQuery('[for="payment_method_mup_wordpresspayplugin"]').html(' Credit/Debit Card <span> <span class="payment-icon payment-icon--visa" data-payment-icon="visa" style=" background-image: url(&quot;<?php echo$mup_request_full_url;?>/public/card_logos/svg_version/visa-logo.svg&quot;), none; cursor: default; margin: -0.142857em 0px -0.428571em; border-radius: 0.214286em; background-size: cover; background-repeat: no-repeat; transition: all 0.2s ease-in-out 0s; width: 2.71429em; height: 1.71429em; margin: 4px; "> <span class="visually-hidden" style=" opacity: 0; "> Visa, </span> </span> <span class="payment-icon payment-icon--master" style=" background-image: url(<?php echo$mup_request_full_url;?>/public/card_logos/svg_version/master-logo.svg), none; cursor: default; margin: -0.142857em 0px -0.428571em; border-radius: 0.214286em; background-size: cover; background-repeat: no-repeat; transition: all 0.2s ease-in-out 0s; width: 2.71429em; height: 1.71429em; margin: 4px; " data-payment-icon="master"> <span class="visually-hidden" style=" opacity: 0; ">visa</span> </span> <span class="payment-icon payment-icon--american-express" data-payment-icon="american-express" style=" background-image: url(<?php echo$mup_request_full_url;?>/public/card_logos/svg_version/amex-logo.svg), none; cursor: default; margin: -0.142857em 0px -0.428571em; border-radius: 0.214286em; background-size: cover; background-repeat: no-repeat; transition: all 0.2s ease-in-out 0s; width: 2.71429em; height: 1.71429em; margin: 4px; "> <span class="visually-hidden" style=" opacity: 0; ">visa</span> </span> <span class="payment-icon payment-icon--discover" data-payment-icon="discover" style=" background-image: url(<?php echo$mup_request_full_url;?>/public/card_logos/svg_version/discover-logo.svg), none; cursor: default; margin: -0.142857em 0px -0.428571em; border-radius: 0.214286em; background-size: cover; background-repeat: no-repeat; transition: all 0.2s ease-in-out 0s; width: 2.71429em; height: 1.71429em; margin: 4px; "> <span class="visually-hidden" style=" opacity: 0; ">visa</span> </span> <span class="payment-icon-list__more"> <span class="content-box__small-text" style=" font-size: 0.857143em; color: rgb(115, 115, 115); "> and more… </span> </span> </span>');
    
    add_mup_card_elements_to_page();


reposition_mup_card_elems();
    var mup_setup_interval2 = setInterval(function(){
var mup_loading_icon='<div class="mup_loader loader--style3" title="2"> <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="40px" height="40px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"> <path fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"> <animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/> </path> </svg></div>';
var mup_old_place_order_text=jQuery('#place_order').html();
        if (jQuery('#mup_iframe_container_js').length) {
            clearInterval(mup_setup_interval2);
           //adding card logoes before here

reposition_mup_card_elems();
    //mup_finish_setup_inline_iframe(mup_checkout_total_amount);
    var allow_next_click=true;
    //https://stackoverflow.com/questions/52726635/hooking-after-validation-but-before-order-create-in-woocommerce-checkout
    var checkout_form = jQuery('form.checkout');

    checkout_form.on('checkout_place_order', function () {
        /*if (jQuery('#confirm-order-flag').length == 0) {
            checkout_form.append('<input type="hidden" id="confirm-order-flag" name="confirm-order-flag" value="1">');
        }
        return true;*/
    });

    jQuery(document.body).on('checkout_error', function () {
        if(jQuery('#payment_method_mup_wordpresspayplugin').is(':checked')==false){
            jQuery('#confirm-order-flag').remove();
            return true;
        }
        var error_count =jQuery('.woocommerce-error li').length;
console.log(error_count);
        if (error_count == 1) { // Validation Passed (Just the Fake Error I Created Exists)
            // Show Confirmation Modal or Whatever
            jQuery('.woocommerce-error').hide()
            jQuery('#confirm-order-flag').val('');
            //https://www.codegrepper.com/code-examples/javascript/javascript+disable+scrolling
            var x=window.scrollX;
            var y=window.scrollY;
             
            window.onscroll=function(){
                window.scrollTo(x, y);
                
            };
            /*setTimeout(function(){
                // $('li.payment_method_mup_wordpresspayplugin')
                
            },100);*/
            jQuery('#place_order').click();
            return false;
        }else{ // Validation Failed (Real Errors Exists, Remove the Fake One)
            jQuery('.woocommerce-error li').each(function(){
                var error_text = jQuery(this).text();
                if (error_text.indexOf('custom_mup_notice')!=-1){
                    jQuery(this).css('display', 'none');
                }
            });
        }
    });
    jQuery( document ).ajaxComplete( function() {
        /*if ( jQuery( 'body' ).hasClass( 'woocommerce-checkout' ) || jQuery( 'body' ).hasClass( 'woocommerce-cart' ) ) {
             var error_count =jQuery('.woocommerce-error li').length;
             if (error_count == 1) {
            jQuery( 'html, body' ).stop();
                
             }
        }*/
       fix_scroll_mup_timeout = setTimeout(function(){
            window.onscroll=function(){};
        },1500);
    } );
    jQuery('body').on('mousewheel', function(event) {
       window.onscroll=function(){};
    });
    jQuery('#place_order').click(function(){
        if(jQuery('#payment_method_mup_wordpresspayplugin').is(':checked')==false){
            jQuery('#confirm-order-flag').remove();
            window.onscroll=function(){};
            return true;
        }
        if (jQuery('#confirm-order-flag').length == 0) {
            checkout_form.append('<input type="hidden" id="confirm-order-flag" name="confirm-order-flag" value="1">');
            window.onscroll=function(){};
            return true;
        }
        if (jQuery('#confirm-order-flag').val() ==1) {
            window.onscroll=function(){};
            return true;
        }else if (jQuery('#confirm-order-flag').val() ==2) {
            window.onscroll=function(){};
            return true;
        }else{
            jQuery('#confirm-order-flag').val('1');
            //setTimeout(function(){window.onscroll=function(){};},1000);
        }
        var inline_mup_transaction_id = jQuery('#inline_mup_transaction_id').val();
        if(allow_next_click==false){
            return false;
        }
        
        

        if(jQuery.trim(inline_mup_transaction_id)==''){
            reposition_mup_card_elems();
            var allow_next_send=true;
        allow_next_click=false;
        jQuery('#place_order').html(mup_loading_icon);


        avs_check_elems={
            'firstname':document.getElementById('billing_first_name'),
            'lastname':document.getElementById('billing_last_name'),
            'phone':document.getElementById('billing_phone'),
            'email':document.getElementById('billing_email'),
            'address_country':document.getElementById('billing_country'),
            'address_city':document.getElementById('billing_city'),
            'address_line1':document.getElementById('billing_address_1'),
            'address_line2':document.getElementById('billing_address_2'),
            'address_state':document.getElementById('billing_state'),
            'address_postcode':document.getElementById('billing_postcode'),
            };
            var avs_check={};
            for (var i in avs_check_elems) {
                if(typeof avs_check_elems[i]!==undefined){
                    if(avs_check_elems[i]!=null){
                    avs_check[i]=avs_check_elems[i].value;
                    }

                }
            }
            avs_check['currency']='<?php echo get_woocommerce_currency(); ?>';

            var mup_payment_page_loader = '<style class="hover_disable sortable_disabled ui-sortable ui-sortable-handle do_not_add_empty_button" style="cursor:default">.mfp-wrap{display:none;}.after_uvi_loader{border:16px solid #f3f3f3;border-radius:50%;border-top:16px solid #3498db;width:120px;height:120px;-webkit-animation:spin 2s linear infinite;animation:spin 2s linear infinite}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0)}100%{-webkit-transform:rotate(360deg)}}@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}</style><div class="hover_disable sortable_disabled ui-sortable ui-sortable-handle div_box_shadow mup_loading-page_js padding-10px" style="text-align:center;padding-top:200px;display:block!important;background:#fff;width:100%;height:100%;position:fixed;top:0;left:0;cursor:default;z-index:123123123123123" contenteditable=false><h2 class="hover_disable sortable_disabled ui-sortable ui-sortable-handle do_not_add_empty_button" style=font-size:23px;font-family:inherit;font-weight:500;cursor:default;>Please Wait (DONT REFRESH PAGE), Payment Authorizing...</h2><div class="hover_disable sortable_disabled ui-sortable ui-sortable-handle div_box_shadow after_uvi_loader do_not_add_empty_button"style="margin:0 auto;cursor:default"></div></div>';
//mup_loading-page_js
                        jQuery("body").append(mup_payment_page_loader);
            mup_checkout_plugin_initiator_from_github.createElementToken(mup_inline_element_num,function(data){
                jQuery('.mup_loading-page_js').remove();

                if(allow_next_send==false){
                    return false;
                }
                reposition_mup_card_elems();
                allow_next_send=false;
        jQuery('#place_order').html(mup_old_place_order_text);

                setTimeout(function(){allow_next_send=true;},500);
                setTimeout(function(){allow_next_click=true;},100);
                if(data.status){
                    //success
                    //data.token is MyUserToken that you can use to process charges; you need to insert it into this form.
              //then submit the form. Please look at the Charges section to see what to write on the server-side.
              jQuery('#inline_mup_transaction_id').val(data.token);
              jQuery('#confirm-order-flag').val('2');//meaning submit to server without adding error or checking card anymore
              allow_next_click=true;
              jQuery('#place_order').click();
                }else{
                  //error
                    //you can receive error message using data.error.message, then show it to the user.
                    jQuery('#mup_wordpresspayplugin_error_message_space').show();
                    jQuery('#mup_wordpresspayplugin_error_message').show();
                    jQuery('#mup_wordpresspayplugin_error_message').html(data.error.message);
                    jQuery('#mup_wordpresspayplugin_error_message_space').css({'height':jQuery('#mup_wordpresspayplugin_error_message').css('height')});
                }
            },{'avs_check':avs_check});
            return false;
        }else{
            
        }
        
    });
           
        }
        else {
    // jQuery('.payment_box.payment_method_mup_wordpresspayplugin p').html('<div id="mup_iframe_container_js"></div>');
            
        }

    },500);

    /*setTimeout(function(){
        jQuery('[for="payment_method_mup_wordpresspayplugin"]').click();
    },1000);*/

}

jQuery(document).on( "updated_checkout", function(){

var aj_data = {
        'action': 'mup_calculate_total',
        'mode':'calc_total',
    };
    jQuery.post(woocommerce_params.ajax_url, aj_data, function(response) {
        mup_checkout_total_amount=response.data.total;

        var mup_setup_interval = setInterval(function(){

        if (document.getElementsByClassName('payment_method_mup_wordpresspayplugin').length && jQuery('.payment_box.payment_method_mup_wordpresspayplugin p').length) {
            //&& jQuery('[name="_wp_http_referer"]').attr('value').indexOf('ajax=update_order_review')!=-1
            //ABOVE REPLACED WITH updated_checkout
            clearInterval(mup_setup_interval);
            setTimeout(function(){
                if(jQuery('#inline_mup_transaction_id').length==0){
                    jQuery('form[name="checkout"]').prepend('<div id="user_link_hidden_checkout_field"><input type="hidden" class="input-hidden" name="inline_mup_transaction_id" id="inline_mup_transaction_id" value=""></div>');
                }
                
                 if(typeof jQuery=='undefined') {
                    var headTag = document.getElementsByTagName("head")[0];
                    var jqTag = document.createElement('script');
                    jqTag.type = 'text/javascript';
                    jqTag.src = 'https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js';
                    jqTag.onload = all_inline_setup_mup_funcs;
                    headTag.appendChild(jqTag);
                } else {
                     all_inline_setup_mup_funcs();
                }
            },500);

           
        }
        else {
            
        }

    },500);

    });



});


    </script><?php
}

function mup_wordpresspayplugin_addMenu(){
    //add_menu_page(page_title,menu_title,capability,'url');
    //4
    add_menu_page("MyUser Payment",'MyUser Payment','administrator',"settings","mup_Menu");

}
function mup_Menu(){
    
header("Location:".'admin.php?page=wc-settings&tab=checkout&section=mup_wordpresspayplugin');
}

function mup_init_WPressPlugin(){
        if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

        include_once __DIR__.'/classes/MUP_WC_Gateway_WPressPlugin.php';

}

/**
* 'Settings' link on plugin page
**/
add_filter( 'plugin_action_links', 'mup_wordpresspayplugin_add_action_plugin', 10, 5 );
function mup_wordpresspayplugin_add_action_plugin( $actions, $plugin_file ) {
    static $plugin;

    if (!isset($plugin))
        $plugin = plugin_basename(__FILE__);
    if ($plugin == $plugin_file) {

            $settings = array('settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=mup_wordpresspayplugin">' . __('Settings') . '</a>');
        
                $actions = array_merge($settings, $actions);
            
        }
        
        return $actions;
}//END-settings_add_action_link

function mup_woocommerce_add_gateway_wordpresspayplugin_gateway($methods) {
        $methods[] = 'MUP_WC_Gateway_WPressPlugin';
        return $methods;
    }//END-wc_add_gateway
    
    add_filter('woocommerce_payment_gateways', 'mup_woocommerce_add_gateway_wordpresspayplugin_gateway' );


    function mup_add_filter_once( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
    global $_gambitFiltersRan;

    if ( ! isset( $_gambitFiltersRan ) ) {
        $_gambitFiltersRan = array();
    }

    // Since references to $this produces a unique id, just use the class for identification purposes
    $idxFunc = $function_to_add;
    if ( is_array( $function_to_add ) ) {
        $idxFunc[0] = get_class( $function_to_add[0] );
    }
    $idx = _wp_filter_build_unique_id( $tag, $idxFunc, $priority );

    if ( ! in_array( $idx, $_gambitFiltersRan ) ) {
        add_filter( $tag, $function_to_add, $priority, $accepted_args );
    }

    $_gambitFiltersRan[] = $idx;

    return true;
}