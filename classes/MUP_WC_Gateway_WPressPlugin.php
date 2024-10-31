<?php 
use \MyUser\MyUserPay;
include __DIR__.'/../MyUserPay/init.php';
class MUP_WC_Gateway_WPressPlugin extends WC_Payment_Gateway {
public $notices = array();
public $get_connected_puadvertiser=array();


	public static function http_get_contents($url) {
	    //if WORDPRESS PLUGIN:
	    $wresponse  = wp_remote_get( $url );
	    $response = wp_remote_retrieve_body( $wresponse );
	    return $response;
	}

		public function __construct(){

add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
			$this->id = 'mup_wordpresspayplugin';
//$this->icon  = mup_MyUserPayments_IMG.'/myuser_payment_gateway.png';
//$this->icon  = 'https://api.myuser.com/pay/images/myuser_payment_gateway_wordpress.png';
$this->pvar=$this->id;
$this->has_fields = false;
$this->settings['show_logo']='no';
$this->method_title = "MyUser Payments";
$this->title = "MyUser Payments"; 
$this->method_description = "MyUser Payments works by adding payment fields on the checkout and then sending the details to MyUser for verification. <a href='https://pay.myuser.com' >Sign up</a> for a MyUser account, and get your MyUser Payments account keys.";

$this->init_form_fields();
$this->init_settings();
add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

 add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

//global $woocommerce;
// var_dump($woocommerce->cart);
// echo  $amount2 = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->get_cart_total() ) );



$test_title			= '';	
			$test_description	= '';
			if ( 'test' == $this->settings['test_mode'] ) {
				$test_title 		= ' [TEST MODE]';
				$test_description 	= '<br/><br/><u>Test Mode is <strong>ACTIVE</strong>, use following Credit Card details:-</u><br/>'."\n"
									 .'Test Card Name: <strong><em>any name</em></strong><br/>'."\n"
									 .'Test Card Number: <strong>4111 1111 1111 1111</strong><br/>'."\n"
									 .'Test Card CVV: <strong>424</strong><br/>'."\n"
									 .'Test Card Expiry: <strong>04/'.date('y', strtotime('+1 year')).'</strong>';
				 
			} //END--test_mode=yes
			$this->title 			= $this->settings['title'].$test_title; // Title as displayed on Frontend
			$this->description 		= $this->settings['description'].$test_description; // Description as displayed on Frontend
			
if(isset($this->settings['show_logo'])){
	if ( $this->settings['show_logo'] != "no" ) { // Check if Show-Logo has been allowed
			//$this->icon 		= get_site_url().'/wp-content/plugins/MyUserPay/public/images/myuser_payment_gateway.png';
			//$this->icon  = 'https://api.myuser.com/pay/images/myuser_payment_gateway_wordpress.png';
			//Logo make it common, no logo
			//mup_wordpresspayplugin_IMG . 'logo_' . $this->settings['show_logo'] . '.png';
		}
}

 $this->msg['message']	= '';
            $this->msg['class'] 	= '';
			
			add_action('init', array(&$this, 'check_mup_wordpresspayplugin_response'));
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_mup_wordpresspayplugin_response')); //update for woocommerce >2.0

            if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) ); //update for woocommerce >2.0
                 } else {
                    add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) ); // WC-1.6.6
                }
            add_action('woocommerce_receipt_'.$this->pvar, array(&$this, 'receipt_page'));	 

$test_mode = $this->settings['test_mode'];

if($test_mode=='test'){
	$show_er=false;
	if(!isset($this->settings['test_public_key'],$this->settings['test_private_key'])){
		$show_er=true;
	}else if(empty(trim($this->settings['test_public_key'])) || empty(trim($this->settings['test_private_key']))){
		$show_er=true;
	}else if(substr( $this->settings['test_public_key'], 0, 7 ) != "pk_test" || substr( $this->settings['test_private_key'], 0, 7 ) != "sk_test"){
		$show_er=true;
	}

	if($show_er===true){
		$this->add_admin_notice( 'keys', 'notice notice-error', sprintf( __( 'MyUser is in test mode however your test keys may not be valid. Test keys start with pk_test for public keys and sk_test for private keys (secret keys). Please go to your settings and, <a href="%s">set your MyUserPay account keys</a>.', 'woo_mup_wordpresspayplugin' ), $this->get_setting_link() ), true );
	}
}else{
	$show_er=false;
	if(!isset($this->settings['live_public_key'],$this->settings['live_private_key'])){
		$show_er=true;
	}else if(empty(trim($this->settings['live_public_key'])) || empty(trim($this->settings['live_private_key']))){
		$show_er=true;
	}else if(substr( $this->settings['live_public_key'], 0, 7 ) != "pk_live" || substr( $this->settings['live_private_key'], 0, 7 ) != "sk_live"){
		$show_er=true;
	}

	if($show_er===true){
		$this->add_admin_notice( 'keys', 'notice notice-error', sprintf( __( 'MyUser is in live mode however your live keys may not be valid. Live keys start with pk_live for public keys and sk_live for private keys (secret keys). Please go to your settings and, <a href="%s">set your MyUserPay account keys</a>.', 'woo_mup_wordpresspayplugin' ), $this->get_setting_link() ), true );
	}
	
}



		}

 

		public function init_form_fields(){
			 $currency = get_woocommerce_currency();

			$this->form_fields = array(
    // 'test_mode' => array(
    //     'title' => __( 'Enable/Disable', 'woocommerce' ),
    //     'type' => 'checkbox',
    //     'label' => __( 'Enable Test Mode', 'woocommerce' ),
    //     'default' => 'yes'
    // ),
				// Activate the Gateway
	'enabled' => array(
		'title' 		=> __('Enable/Disable:', 'woo_mup_wordpresspayplugin'),
		'type' 			=> 'checkbox',
		'label' 		=> __('Enable MyUser Payments', 'woo_mup_wordpresspayplugin'),
		'default' 		=> 'yes',
		'description' 	=> 'Show in the Payment List as a payment option'
	),
	'inline_pay' => array(
		'title' 		=> __('Enable/Disable:', 'woo_mup_wordpresspayplugin'),
		'type' 			=> 'checkbox',
		'label' 		=> __('Inline Payments', 'woo_mup_wordpresspayplugin'),
		'default' 		=> 'yes',
		'description' 	=> 'This will let customers to fill payment details along with checkout details. If disabled, payment & checkout form will be 2 different pages'
	),
	// Title as displayed on Frontend
	'title' => array(
		'title' 		=> __('Title:', 'woo_mup_wordpresspayplugin'),
		'type'			=> 'text',
		'default' 		=> __('Credit Card (secure)', 'woo_mup_wordpresspayplugin'),
		'description' 	=> __('This controls the title which the user sees during checkout.', 'woo_mup_wordpresspayplugin'),
		'desc_tip' 		=> true
	),
// Description as displayed on Frontend
	'description' => array(
		'title' 		=> __('Description:', 'woo_mup_wordpresspayplugin'),
		'type' 			=> 'textarea',
		'default' 		=> __("Options for payment in next page:\n - Credit or Debit Cards.", 'woo_mup_wordpresspayplugin'),
		'description' 	=> __('This controls the description which the user sees during checkout.', 'woo_mup_wordpresspayplugin'),
		'desc_tip' 		=> true
	),
	'test_mode' => array(
		'title' 		=> __('Mode:', 'woo_mup_wordpresspayplugin'),
		'type' 			=> 'select',
		'label' 		=> __('MyUserPay Test/Live Mode.', 'woo_mup_wordpresspayplugin'),
		'options' 		=> array('test'=>'Test Mode','secure'=>'Live Mode'),
		'default' 		=> 'test',
		'description' 	=> __('Mode of MyUserPay activities'),
		'desc_tip' 		=> true
     ),
	'button_text' => array(
        'title' => __( 'Button Text', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Text of MyUser checkout button.', 'woocommerce' ),
        'default' => __( 'Pay Now', 'woocommerce' ),
        'desc_tip'      => true,
    ),
	'redirect_page' => array(
					'title' 			=> __('Return Page'),
					'type' 			=> 'select',
					'options' 		=> $this->mup_wordpresspayplugin_get_pages('Select Page'),
					'description' 	=> __('URL of success page', 'woo_mup_wordpresspayplugin'),
					'desc_tip' 		=> true
                ),
    'one_dollar' => array(
        'title' => __( 'How much '.$currency.' is one dollar', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Currency Converter, $1= X '.$currency, 'woocommerce' ),
        'default' => __( '1.00', 'woocommerce' ),
        'desc_tip'      => true,
    'custom_attributes' => array( 'required' => 'required'),

    ),
     'test_public_key' => array(
        'title' => __( 'Test Public key', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'You can get this key from https://pay.myuser.com.', 'woocommerce' ),
            'custom_attributes' => array( 'required' => 'required' ),

        'default' => __( '', 'woocommerce' ),
        'desc_tip'      => true,
    ),
    'test_private_key' => array(
        'title' => __( 'Test Private Key', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'You can get this key from https://pay.myuser.com.', 'woocommerce' ),
            'custom_attributes' => array( 'required' => 'required' ),

        'default' => __( '', 'woocommerce' ),
        'desc_tip'      => true,
    ),
    'live_public_key' => array(
        'title' => __( 'Live Public key', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'You can get this key from https://pay.myuser.com/dashboard.', 'woocommerce' ),
        'default' => __( '', 'woocommerce' ),
         'custom_attributes' => array( 'required' => 'required' ),
        'desc_tip'      => true,
    ),
    'live_private_key' => array(
        'title' => __( 'Live Private Key', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'You can get this key from https://pay.myuser.com.', 'woocommerce' ),
          'custom_attributes' => array( 'required' => 'required' ),
        'default' => __( '', 'woocommerce' ),
        'desc_tip'      => true,
    ),
    'button_text' => array(
        'title' => __( 'Button Text', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'Text of MyUser checkout button.', 'woocommerce' ),
        'default' => __( 'Pay Now', 'woocommerce' ),
        'desc_tip'      => true,
    ),
     'image_url_of_store' => array(
        'title' => __( 'Image Url of Store', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'This will be shown when user click pay button.', 'woocommerce' ),
        'default' => __( '', 'woocommerce' ),
        'desc_tip'      => true,
    ),
      'store_description' => array(
        'title' => __( 'Description of your store', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'This will be shown when user click pay button.', 'woocommerce' ),
        'default' => __( 'Your store description', 'woocommerce' ),
        'desc_tip'      => true,
    ),
    'store_name' => array(
        'title' => __( 'Name of your store', 'woocommerce' ),
        'type' => 'text',
        'description' => __( 'This will be shown when user click pay button.', 'woocommerce' ),
        'default' => __('Your Store Name', 'woocommerce' ),
        'desc_tip'      => true,
    ),
    'payments_card' => array(
		'title' 		=> __('Enable/Disable:', 'woo_mup_wordpresspayplugin'),
		'type' 			=> 'checkbox',
		'label' 		=> __('Card payments', 'woo_mup_wordpresspayplugin'),
		'default' 		=> 'yes',
	),
	'payments_ach' => array(
		'title' 		=> __('Enable/Disable:', 'woo_mup_wordpresspayplugin'),
		'type' 			=> 'checkbox',
		'label' 		=> __('Bank payments', 'woo_mup_wordpresspayplugin'),
		'default' 		=> 'yes',
	),
	'payments_paypal' => array(
		'title' 		=> __('Enable/Disable:', 'woo_mup_wordpresspayplugin'),
		'type' 			=> 'checkbox',
		'label' 		=> __('Paypal payments', 'woo_mup_wordpresspayplugin'),
		'default' 		=> 'yes',
	),
    // 'description' => array(
    //     'title' => __( 'Customer Message', 'woocommerce' ),
    //     'type' => 'textarea',
    //     'default' => ''
    // )
); 

if(!isset($this->settings['one_dollar'])){
	//$this->settings['one_dollar'] 1 dollar = X onlarin
	$this->settings['one_dollar']=1;
}
if($this->settings['one_dollar']==0){
	$this->settings['one_dollar']=1;
}


		}




		 

public function add_payment_method() {
        return array(
            'result'   => 'failure',
            'redirect' => wc_get_endpoint_url( 'payment-methods' ),
        );
    }


public function admin_options(){
			echo '<h3>'.__('MyUser Payment', 'woo_mup_wordpresspayplugin').'</h3>';
			echo '<p>'.__('MyUser Payments works by adding payment fields on the checkout and then sending the details to MyUser for verification. <a href=\'https://pay.myuser.com\' >Sign up</a> for a MyUser account, and get your MyUser Payments account keys.', 'woo_mup_wordpresspayplugin').'</p>';
			echo '<p><small><strong>'.__('Confirm your Mode: Is it LIVE or TEST.').'</strong></small></p>';
			echo '<table class="form-table">';
			// Generate the HTML For the settings form.
			$this->generate_settings_html();
			echo '</table>';
		} //END-admin_options

        /**
         *  There are no payment fields, but we want to show the description if set.
         **/
		function payment_fields(){
			if(isset($this->settings['inline_pay'])){
			$inline_pay_active_mup=$this->settings['inline_pay'];

			}else{
				$inline_pay_active_mup='yes';

			}
			$inline_mode=true;
			if(!is_null($inline_pay_active_mup)){
			    if($inline_pay_active_mup=='no'){
			        $inline_mode=false;
			    }
			}

			if($inline_mode==false || true){
				if( $this->description ) {
					echo wpautop( wptexturize( $this->description ) );
				}
			}else{
				echo '<fieldset>
					<div>AWESOME</div>
					<input name="cards_test_final" />
				</fieldset>';
			}
	
			
		} //END-payment_fields
		
        /**
         * Receipt Page
         **/
        public static $receipt_page_called=0;
        public static $receipt_page_executed_page=0;
		function receipt_page($order){
			// var_dump($order,$a);
			// var_dump(self::$receipt_page_called);
			// var_dump(self::$receipt_page_executed_page);
			if(self::$receipt_page_called==0 || self::$receipt_page_called>(self::$receipt_page_executed_page+2) ){
				self::$receipt_page_executed_page=self::$receipt_page_called;
				self::$receipt_page_called++;
				echo $this->generate_mup_wordpresspayplugin_form($order);
				return;
			}else{
				self::$receipt_page_called++;
			}
			//echo '<p><strong>' . __('Thank you for your order.', 'woo_mup_wordpresspayplugin').'</strong><br/>' . __('The payment will be processed or new payment page will open soon.', 'woo_mup_wordpresspayplugin').'</p>';
		} //END-receipt_page
    	/**
         * Generate button link
         **/

    	public function get_account_details(){
    			$test_mode = $this->settings['test_mode'];

				if($test_mode=='test'){
					//$public_key = $this->settings['payments_card'];
					$public_key = $this->settings['test_public_key'];
				}else{
					$public_key = $this->settings['live_public_key'];
				}

				if($test_mode=='test'){
					$private_key = $this->settings['test_private_key'];
				}else{
					$private_key = $this->settings['live_private_key'];
				}
				$fcontents = self::http_get_contents('https://pay.myuser.com/get_connected_puadvertiser/'.$private_key);
				$fcontents = utf8_encode($fcontents);
				$get_connected_puadvertiser = json_decode($fcontents,true); 
				$get_connected_puadvertiser['private_key']=$private_key;
				$get_connected_puadvertiser['raw_public_key']=$public_key;

				$request_full_url = 'https://api.myuser.com';
				//if someone advertising then let them handle everything.
				if($get_connected_puadvertiser['url']!='no_ad'){
					$request_full_url=$get_connected_puadvertiser['url'].'/req_p_main_subdomain_api_1';
				}
				$get_connected_puadvertiser['request_full_url']=$request_full_url;

				$class_name='myuser';
				if($get_connected_puadvertiser['class_name']!='no_ad'){
					$class_name=$get_connected_puadvertiser['class_name'];
				}
				if($class_name=='myuser'){
					$class_name_BB='MyUserPay';
				}else{
				$class_name_BB=ucfirst($class_name.'Pay');;//big first letter and big Pay	
				}

				
				$class_name_SB=($class_name.'Pay');//small first letter and big Pay


				$get_connected_puadvertiser['class_name']=$class_name;
				$get_connected_puadvertiser['class_name_BB']=$class_name_BB;

				$this->get_connected_puadvertiser=$get_connected_puadvertiser;
				//var_dump($get_connected_advertiser); 
				/*if(isset($get_connected_puadvertiser['selected_public_key'])){
					//---processor_connect_token---
					$public_key=$get_connected_puadvertiser['selected_public_key'];
				}*/
				return $get_connected_puadvertiser;
				
    	}
    	public function generate_mup_form_scripts($params=array()){
    			$mup_javascript_mode='popup';
    			if(isset($params['mup_javascript_mode'])){
    				$mup_javascript_mode=$params['mup_javascript_mode'];
    			}
    			if(!isset($params['mup_wordpresspayplugin_args'])){
    				$params['mup_wordpresspayplugin_args']=array();
    			}
    			if(!isset($params['mup_wordpresspayplugin_args_array'])){
    				$params['mup_wordpresspayplugin_args_array']=array();
    			}
    			if(!isset($params['order'])){
    				$params['order']=array();
    			}
    			if(!isset($params['order_id'])){
    				$params['order_id']=null;
    			}
    			if(!isset($params['inline_mup_transaction_id'])){
    				$params['inline_mup_transaction_id']='';
    			}
    			if(!isset($params['redirect_url'])){
    				$params['redirect_url']='';
    			}
    			$mup_wordpresspayplugin_args=$params['mup_wordpresspayplugin_args'];
    			$mup_wordpresspayplugin_args_array=$params['mup_wordpresspayplugin_args_array'];
    			$order=$params['order']; 
    			$order_id=$params['order_id'];
    			$inline_mup_transaction_id=$params['inline_mup_transaction_id'];
    			$redirect_url=$params['redirect_url'];

    			if(isset($params['amount'])){
    				$mup_wordpresspayplugin_args['amount']=$params['amount'];
    			}

				if(!isset($this->settings['one_dollar'])){
					//$this->settings['one_dollar'] 1 dollar = X onlarin
					$one_dollar=1;
				}else{
					$one_dollar = $this->settings['one_dollar'];
				}
				if($this->settings['one_dollar']==0){
					$one_dollar=1;
				}

				/*if(!ctype_digit($this->settings['one_dollar'])){
					$one_dollar=1;
				}*/


					$currency = get_woocommerce_currency();
				// $total_amount = max( 0, apply_filters( 'woocommerce_calculated_total', round( WC()->cart->cart_contents_total + WC()->cart->fee_total + WC()->cart->tax_total, WC()->cart->dp ), WC()->cart ) );


				if(!isset($this->settings['image_url_of_store'])){
					$this->settings['image_url_of_store']='';
				}
				if(!isset($this->settings['button_text'])){
					$this->settings['button_text']='Pay by Card';
				}
				$total_amount_in_dollar = round($mup_wordpresspayplugin_args['amount']*100/$this->settings['one_dollar']);
				$test_mode = $this->settings['test_mode'];

				if($test_mode=='test'){
					//$public_key = $this->settings['payments_card'];
					$public_key = $this->settings['test_public_key'];
				}else{
					$public_key = $this->settings['live_public_key'];
				}

				if($test_mode=='test'){
					$private_key = $this->settings['test_private_key'];
				}else{
					$private_key = $this->settings['live_private_key'];
				}

				$not_allowed_methods='';
				if(!isset($this->settings['payments_card'])){
					$this->settings['payments_card']='yes';
				}
				if(!isset($this->settings['payments_ach'])){
					$this->settings['payments_ach']='yes';
				}
				if(!isset($this->settings['payments_paypal'])){
					$this->settings['payments_paypal']='yes';
				}

				if($this->settings['payments_card']=='no'){
					$not_allowed_methods.='card,';
				}
				if($this->settings['payments_ach']=='no'){
					$not_allowed_methods.='ach,';
				}
				if($this->settings['payments_paypal']=='no'){
					$not_allowed_methods.='paypal,';
				}
				$fcontents = self::http_get_contents('https://pay.myuser.com/get_connected_puadvertiser/'.$private_key);
				$fcontents = utf8_encode($fcontents);
				$get_connected_puadvertiser = json_decode($fcontents,true); 
				$this->get_connected_puadvertiser=$get_connected_puadvertiser;
				//var_dump($get_connected_advertiser); 
				$request_full_url = 'https://api.myuser.com';
				//if someone advertising then let them handle everything.
				if($get_connected_puadvertiser['url']!='no_ad'){
					$request_full_url=$get_connected_puadvertiser['url'].'/req_p_main_subdomain_api_1';
				}

				if(isset($get_connected_puadvertiser['selected_public_key'])){
					//---processor_connect_token---
					$public_key=$get_connected_puadvertiser['selected_public_key'];
				}

				$class_name='myuser';
				if($get_connected_puadvertiser['class_name']!='no_ad'){
					$class_name=$get_connected_puadvertiser['class_name'];
				}
				if($class_name=='myuser'){
					$class_name_BB='MyUserPay';
				}else{
				$class_name_BB=ucfirst($class_name.'Pay');;//big first letter and big Pay	
				}
				
				$class_name_SB=($class_name.'Pay');//small first letter and big Pay

				$mup_script_tag_class=''.$class_name_SB.'-button';
				if($mup_javascript_mode=='inline'){
					$mup_script_tag_class='';
				}
				//remove below
				// $mup_script_tag_class='';


				$extra_mup_fields='';
				$mup_auto_submit_form=false;
				if(trim($inline_mup_transaction_id)!='' && !is_null($inline_mup_transaction_id)){
					// && $mup_javascript_mode=='inline'
					$extra_mup_fields.="<input type='hidden' name='token' value='".$inline_mup_transaction_id."'/>";
					$mup_auto_submit_form=true;
					$mup_script_tag_class='';
				}
				$woocommerce_details_billing=array();
				foreach ($mup_wordpresspayplugin_args as $field => $value) {
					/*firstname
					email
					phone
					lastname
					address1
					address2
					city
					state
					country
					zipcode*/
					if(!is_null($value) && trim($value)!=''){
						$woocommerce_details_billing[$field]=$value;
					}
				}

					$mup_script_return_content='<form id="mup_main_payment_form_submit"  action="'.$redirect_url.'" method="post"  >'.$extra_mup_fields;
					//if($mup_auto_submit_form==false){
					$mup_script_tag_class='';
						$mup_script_return_content.=implode('', $mup_wordpresspayplugin_args_array).
						 '<script 
						    src="'.$request_full_url.'/js/checkout.js" 
						    class="'.$mup_script_tag_class.'" 
						    data-button-text="'.$this->settings['button_text'].'"
						    data-button="false"
						    data-public_key="'.$public_key.'"
						    data-amount="'.($currency).'"
						    data-currency=" '.($total_amount_in_dollar).'"
						    data-amount_string="'.$currency.''.sprintf("%.2f",round($mup_wordpresspayplugin_args['amount'],2)).'"
						    data-description="'.$this->settings['store_description'].'"
						    data-name="'.$this->settings['store_name'].'" 
							data-image="'.$this->settings['image_url_of_store'].'"
							data-not_allowed_methods="'.$not_allowed_methods.'"
							data-version="1"
							data-wordpress="1"
							data-avs_check="{
				            \'firstname\':\''.$woocommerce_details_billing['firstname'].'\',
				            \'lastname\':\''.$woocommerce_details_billing['lastname'].'\',
				            \'email\':\''.$woocommerce_details_billing['email'].'\',
				            \'phone\':\''.$woocommerce_details_billing['phone'].'\',
				            \'address_country\':\''.$woocommerce_details_billing['country'].'\',
				            \'address_city\':\''.$woocommerce_details_billing['city'].'\',
				           \'address_line1\':\''.$woocommerce_details_billing['address1'].'\',
				            \'address_line2\':\''.$woocommerce_details_billing['address2'].'\',
				            \'address_state\':\''.$woocommerce_details_billing['state'].'\',
				            \'address_postcode\':\''.$woocommerce_details_billing['zipcode'].'\'
				            }"
						    data-submit-ajax="0">
						 </script>';
					//}
						
				
$loader_xxx='<style class="hover_disable sortable_disabled ui-sortable ui-sortable-handle do_not_add_empty_button" style="cursor:default">.mfp-wrap{display:none;}.after_uvi_loader{border:16px solid #f3f3f3;border-radius:50%;border-top:16px solid #3498db;width:120px;height:120px;-webkit-animation:spin 2s linear infinite;animation:spin 2s linear infinite}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0)}100%{-webkit-transform:rotate(360deg)}}@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}</style><div class="hover_disable sortable_disabled ui-sortable ui-sortable-handle div_box_shadow loading-page_js padding-10px" style=text-align:center;padding-top:200px;display:block!important;background:#fff;width:100%;height:100%;position:fixed;top:0;left:0;cursor:default;z-index:123123123123123 contenteditable=false><h2 class="hover_disable sortable_disabled ui-sortable ui-sortable-handle do_not_add_empty_button" style=font-size:23px;font-family:inherit;font-weight:500;cursor:default;>Please Wait (DONT REFRESH PAGE), Payment Processing...</h2><div class="hover_disable sortable_disabled ui-sortable ui-sortable-handle div_box_shadow after_uvi_loader do_not_add_empty_button" style="margin:0 auto;cursor:default"></div></div>';
				$mup_script_return_content.='</form>';
				 
					$elem_timestamp=time();

				if($mup_javascript_mode=='popup' && $mup_auto_submit_form==false){
					
					$mup_script_return_content.='<h3 id="pay_below_description_id" style="
    text-align: center;
    font-weight: 500;
    font-family: Helvetica Neue, Helvetica, Arial, sans-serif;
    color: #1a2635;
">Please pay using form below <div style="
    color: #0a520a;
    font-size: 13px;
">all card details are encrypted</div></h3><div style="
    margin: 0px auto;
    width: 501px;
    text-align: center;
    /* background-color: #f6f9fc; */
    background-color: #f6f9fc;
    padding: 13px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-shadow: rgb(50 50 93 / 15%) 0px 1px 3px, rgb(0 0 0 / 2%) 0px 1px 0px;max-width: 100%;
"><p id="mup_wordpresspayplugin_error_message" class="mup_wordpresspayplugin_error_message-'.$elem_timestamp.'" style="margin-top: 0px;font-size: 14px;line-height: 24px;border-radius: 2px;box-sizing: border-box;position: relative;color: rgb(169, 68, 67);background-image: initial;background-position: initial;background-size: initial;background-repeat: initial;background-attachment: initial;background-origin: initial;background-clip: initial;padding: 9px;border-color: rgb(235, 204, 210);margin-bottom: 18px;fill: rgb(180, 29, 52) !important;background-color: rgb(254, 217, 219) !important;font-weight: 400 !important;/* width: 90%; */width: 306px;/* position: absolute; */left: 0;right: 0;margin: 0px auto;max-width: 100%;min-width: 100%;display:none;" segoe=""></p><div  style="display: block;
    margin: 10px 0 20px 0;
    max-width: 500px;
    padding: 10px 14px;
    box-shadow: rgb(50 50 93 / 15%) 0px 1px 3px, rgb(0 0 0 / 2%) 0px 1px 0px;
    border-radius: 4px;
    background: white;" id="mup_iframe_container_js-'.$elem_timestamp.'" class="mup_iframe_container_js-'.$elem_timestamp.'" ></div><button id="submit_inline_pay_button"  onclick="submit_inline_pay();" style="
    width: 100%;
    height: 56px;
    margin-top: 10px;
    background: #2b7ede;
    border-radius: 4px;
    cursor: pointer;
    display: block;
    color: #FFFFFF;
    font-size: 16px;
    line-height: 24px;
    font-weight: 700;
    letter-spacing: 0;
    text-align: center;
    -webkit-transition: background .2s ease-in-out;
    -moz-transition: background .2s ease-in-out;
    -ms-transition: background .2s ease-in-out;
    transition: background .2s ease-in-out;
">Pay '.$currency.''.sprintf("%.2f",round($mup_wordpresspayplugin_args['amount'],2)).'</button><a style="
					    margin: 0px auto;
					    display: block;
					    text-align: center;
					    width: 400px;
					    margin-top: 28px;max-width: 100%;
					" class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woo_mup_wordpresspayplugin').'</a></div>';

					$mup_script_return_content.='<script type="text/javascript">mup_checkout_plugin_initiator_from_github='.$class_name_BB.';</script>
					<script type="text/javascript">
					jQuery(window).on(\'load\', function() {
						jQuery([document.documentElement, document.body]).animate({
					        scrollTop: (jQuery("#pay_below_description_id").offset().top-30)
					    }, 500);
					});
					try{
						$(\'.et_pb_row\').not(\':last\').remove();
					}catch(e){}
					mup_inline_element_num =0;
					mup_checkout_plugin_initiator_from_github='.$class_name_BB.';
					function mup_finish_setup_inline_iframe(amount){
						var style=\'body{}\'; //insert style tag inside of iframe.
						var total_amount_in_dollar_cents=parseInt(amount/'.$one_dollar.');
					    '.$class_name_BB.'.setKey(\''.$public_key.'\'); 
					    mup_inline_element_num = '.$class_name_BB.'.createElement(\'.mup_iframe_container_js-'.$elem_timestamp.':last\',{style:style,amount:total_amount_in_dollar_cents,email:\''.$woocommerce_details_billing['email'].'\'});

					    /*mup_inline_element_num = '.$class_name_BB.'.createElement(\'.mup_iframe_container_js-'.$elem_timestamp.':last\',{style:style,amount:total_amount_in_dollar_cents});*/
					    //We will use element_num to create token for that specific element
					}
					var submit_inline_pay_in_progress=false;
					function submit_inline_pay(){
						if(submit_inline_pay_in_progress==true){
							return;
						}
						submit_inline_pay_in_progress=true;
						jQuery(\'.mup_wordpresspayplugin_error_message-'.$elem_timestamp.':last\').hide();
						var mup_loading_icon=\'<div class="mup_loader loader--style3" title="2"> <svg style="fill: #fff;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="40px" height="40px" viewBox="0 0 50 50" style="enable-background:new 0 0 50 50;" xml:space="preserve"> <path style="fill: #fff;" fill="#000" d="M43.935,25.145c0-10.318-8.364-18.683-18.683-18.683c-10.318,0-18.683,8.365-18.683,18.683h4.068c0-8.071,6.543-14.615,14.615-14.615c8.072,0,14.615,6.543,14.615,14.615H43.935z"> <animateTransform attributeType="xml" attributeName="transform" type="rotate" from="0 25 25" to="360 25 25" dur="0.6s" repeatCount="indefinite"/> </path> </svg></div>\';
var mup_old_place_order_text=jQuery(\'#submit_inline_pay_button\').html();
jQuery(\'#submit_inline_pay_button\').html(mup_loading_icon);
						 mup_checkout_plugin_initiator_from_github.createElementToken(mup_inline_element_num,function(data){
						 	jQuery(\'#submit_inline_pay_button\').html(mup_old_place_order_text);
								if(data.status){
				                    //success
				                    //data.token is MyUserToken that you can use to process charges; you need to insert it into this form.
				              //then submit the form. Please look at the Charges section to see what to write on the server-side.
				              var mup_payment_page_loader = \''.$loader_xxx.'\';
										jQuery(\'body\').append(mup_payment_page_loader);
										jQuery(\'#mup_main_payment_form_submit\').append(\'<input name=MyUserToken value=\'+data.token+\'>\');
										jQuery(\'#mup_main_payment_form_submit\').submit();
				                }else{
						 	submit_inline_pay_in_progress=false;
						 	jQuery(\'.mup_wordpresspayplugin_error_message-'.$elem_timestamp.':last\').show();
				                jQuery(\'.mup_wordpresspayplugin_error_message-'.$elem_timestamp.':last\').html(data.error.message);
				                 //alert(data.error.message);
				                }			
										},{
											\'avs_check\':{
									            \'firstname\':\''.$woocommerce_details_billing['firstname'].'\',
									            \'lastname\':\''.$woocommerce_details_billing['lastname'].'\',
									            \'email\':\''.$woocommerce_details_billing['email'].'\',
									            \'phone\':\''.$woocommerce_details_billing['phone'].'\',
									            \'address_country\':\''.$woocommerce_details_billing['country'].'\',
									            \'address_city\':\''.$woocommerce_details_billing['city'].'\',
									           \'address_line1\':\''.$woocommerce_details_billing['address1'].'\',
									            \'address_line2\':\''.$woocommerce_details_billing['address2'].'\',
									            \'address_state\':\''.$woocommerce_details_billing['state'].'\',
									            \'address_postcode\':\''.$woocommerce_details_billing['zipcode'].'\'
								            },
								            \'amount\':'.($total_amount_in_dollar).'
						});

					}
					
					</script>
					';
					
/*$mup_script_return_content.='<a style="
					    position: relative;
					    top: 2px;
					    left: 8px;
					" class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woo_mup_wordpresspayplugin').'</a>';*/
					if($mup_auto_submit_form==false){
						$mup_script_return_content.='<script type="text/javascript">
						mup_finish_setup_inline_iframe('.$total_amount_in_dollar.');


							</script>
							';

						/*$mup_script_return_content.='<script type="text/javascript">
							'.$class_name_BB.'.onButtonReady(function(form_id){
									
							try{
								'.$class_name_SB.'_openPaymentModal(1);
							}catch(e){}

								});</script>
							';*/
					}
					
				}else if($mup_javascript_mode=='inline'){
					$mup_script_return_content.='
					<script type="text/javascript">
					mup_inline_element_num =0;
					mup_checkout_plugin_initiator_from_github='.$class_name_BB.';
					function mup_finish_setup_inline_iframe(amount){
						var style=\'body{}\'; //insert style tag inside of iframe.
						var total_amount_in_dollar_cents=parseInt(amount/'.$one_dollar.');
					    '.$class_name_BB.'.setKey(\''.$public_key.'\'); 
					    mup_inline_element_num = '.$class_name_BB.'.createElement(\''.$params['container_element_selector'].'\',{style:style,amount:total_amount_in_dollar_cents,email:jQuery(\'#billing_email\').val()});

					    /*mup_inline_element_num = '.$class_name_BB.'.createElement(\''.$params['container_element_selector'].'\',{style:style,amount:total_amount_in_dollar_cents});*/
					    //We will use element_num to create token for that specific element
					}
					
					
					</script>
					';
				}
				if($mup_auto_submit_form==true){
					$mup_script_return_content.='
					<script type="text/javascript">
					//add full screen saying wait
					var mup_payment_page_loader = \'<style class="hover_disable sortable_disabled ui-sortable ui-sortable-handle do_not_add_empty_button" style="cursor:default">.mfp-wrap{display:none;}.after_uvi_loader{border:16px solid #f3f3f3;border-radius:50%;border-top:16px solid #3498db;width:120px;height:120px;-webkit-animation:spin 2s linear infinite;animation:spin 2s linear infinite}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0)}100%{-webkit-transform:rotate(360deg)}}@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}</style><div class="hover_disable sortable_disabled ui-sortable ui-sortable-handle div_box_shadow loading-page_js padding-10px"style=text-align:center;padding-top:200px;display:block!important;background:#fff;width:100%;height:100%;position:fixed;top:0;left:0;cursor:default;z-index:123123123123123 contenteditable=false><h2 class="hover_disable sortable_disabled ui-sortable ui-sortable-handle do_not_add_empty_button" style=font-size:23px;font-family:inherit;font-weight:500;cursor:default;>Please Wait (DONT REFRESH PAGE), Payment Processing...</h2><div class="hover_disable sortable_disabled ui-sortable ui-sortable-handle div_box_shadow after_uvi_loader do_not_add_empty_button"style="margin:0 auto;cursor:default"></div></div>\';
						jQuery("body").append(mup_payment_page_loader);
						jQuery("#mup_main_payment_form_submit").submit();
					
					
					</script>
					';
				}
				
				$mup_script_return_content.=" <script type=\"text/javascript\">
				try{

					".$class_name_BB.".onPaymentSucceed(function(form_num,token){
					jQuery('body').append('<style class=\'hover_disable sortable_disabled ui-sortable ui-sortable-handle do_not_add_empty_button\' style=\'cursor:default\'>.mfp-wrap{display:none;}.after_uvi_loader{border:16px solid #f3f3f3;border-radius:50%;border-top:16px solid #3498db;width:120px;height:120px;-webkit-animation:spin 2s linear infinite;animation:spin 2s linear infinite}@-webkit-keyframes spin{0%{-webkit-transform:rotate(0)}100%{-webkit-transform:rotate(360deg)}}@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}</style><div class=\'hover_disable sortable_disabled ui-sortable ui-sortable-handle div_box_shadow loading-page_js padding-10px\' style=\'text-align:center;padding-top:200px;display:block!important;background:#fff;width:100%;height:100%;position:fixed;top:0;left:0;cursor:default;opacity: 0.96;z-index: 99999;z-index:123123123123123\' contenteditable=false><h2 class=\'hover_disable sortable_disabled ui-sortable ui-sortable-handle do_not_add_empty_button\' style=font-size:23px;font-family:sans-serif;cursor:default>Payment Processing (DONT REFRESH PAGE), Please wait and do not close the page...</h2><div class=\'hover_disable sortable_disabled ui-sortable ui-sortable-handle div_box_shadow after_uvi_loader do_not_add_empty_button\' style=\'margin:0 auto;cursor:default\'></div></div>');
setTimeout(function(){jQuery('.loading-page_js').remove()},20000);

				});



				}catch(e){}

				


				</script>";
				return $mup_script_return_content;				
    	}

        /**
         * Generate button link
         **/
		function generate_mup_wordpresspayplugin_form($order_id){
			global $woocommerce;
			$order = new WC_Order( $order_id );

			// Redirect URL
			if ( '' == $this->redirect_page  || 0 == $this->redirect_page ) {
				$redirect_url = get_site_url() . "/";
			} else {
				$redirect_url = get_permalink( $this->redirect_page );
			}
			// Redirect URL : For WooCoomerce 2.0
			if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				$redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
			}

            $productinfo = "Order $order_id";

			$txnid = $order_id.'_'.date("ymds");
			// hash-string = key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||<SALT>
			$str = "$this->txnid|$order->order_total|$productinfo|$order->billing_first_name|$order->billing_email|$order_id||||||||||";
			$hash = strtolower(hash('sha512', $str));
			$service_provider = '';
//die($redirect_url);
			$mup_wordpresspayplugin_args = array(
				 
				'hash' 			=> $hash,
				'txnid' 		=> $txnid,
				'amount' 		=> $order->order_total,
				'firstname'		=> $order->billing_first_name,
				'email' 		=> $order->billing_email, 
				'phone' 		=> substr( $order->billing_phone, -10 ),
				'productinfo'	=> $productinfo,
				'surl' 			=> $redirect_url,
				'furl' 			=> $redirect_url,
				'lastname' 		=> $order->billing_last_name,
				'address1' 		=> $order->billing_address_1,
				'address2' 		=> $order->billing_address_2,
				'city' 			=> $order->billing_city,
				'state' 		=> $order->billing_state,
				'country' 		=> $order->billing_country,
				'zipcode' 		=> $order->billing_postcode,
				'curl'			=> $redirect_url,
				'pg' 			=> 'NB',
				'udf1' 			=> $order_id,
				'service_provider'	=> $service_provider
			);
			$mup_wordpresspayplugin_args_array = array();
			foreach($mup_wordpresspayplugin_args as $key => $value){
				$mup_wordpresspayplugin_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
			}
			$inline_mup_transaction_id=null;
			/*if(property_exists($order,'meta_data')){
				for ($i=0; $i < count($order->meta_data); $i++) { 
					var_dump($order->meta_data[0]);
				}
				foreach ($order->meta_data as $meta_data_val) {
					if(property_exists($meta_data_val,'current_data')){
						var_dump($current_data);
						$current_data=$meta_data_val->current_data;
						var_dump($current_data);
						var_dump($current_data['key']);
						if($meta_data_val->current_data['key']=='_inline_mup_transaction_id'){
							$inline_mup_transaction_id=$meta_data_val->current_data['value'];
						}
					}
				}
			}*/

			$inline_mup_transaction_id= get_post_meta($order_id, "_inline_mup_transaction_id", true);
			//if this is not '' then redirect to charge
			
			return $this->generate_mup_form_scripts(array(
				'mup_wordpresspayplugin_args'=>$mup_wordpresspayplugin_args,
				'mup_wordpresspayplugin_args_array'=>$mup_wordpresspayplugin_args_array,
				'order'=>$order,
				'order_id'=>$order_id,
				'inline_mup_transaction_id'=>$inline_mup_transaction_id,//bring our token here and auto send if detected to charge
				'redirect_url'=>$redirect_url,
			));	
		 
		} //END-generate_mup_wordpresspayplugin_form

        /**
         * Process the payment and return the result
         **/
        function process_payment($order_id){
			global $woocommerce;
            $order = new WC_Order($order_id);
			//die('process_payment');
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) { // For WC 2.1.0
			  	$checkout_payment_url = $order->get_checkout_payment_url( true );
			} else {
				$checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
			}

			return array(
				'result' => 'success', 
				'redirect' => add_query_arg(
					'order', 
					$order->id, 
					add_query_arg(
						'key', 
						$order->order_key, 
						$checkout_payment_url						
					)
				)
			);
		} //END-process_payment



		public function charge_user($amount,$params=array()){
			$response_data = array();
			$response_data['charge']=array();
			$response_data['error_body']=array();
			 //path/to/MyUserPay library
			$test_mode = $this->settings['test_mode'];

			if($test_mode=='test'){
				$private_key = $this->settings['test_private_key'];
			}else{
				$private_key = $this->settings['live_private_key'];
			}
			MyUserPay::setPrivateKey($private_key);
			$order=array();

			$charge_data=array(
			'amount'=>$amount,
			'currency'=>get_woocommerce_currency(),
			//If request was post
			'wp_wocommerce'=>1,
			);
			$charge_data['metadata']=array();

			if(isset($params['order'])){
				$order=$params['order'];
				if(true){
					//property_exists($order, $billing_email)
					$charge_data['email']=$order->billing_email;
					$charge_data['metadata']['billing_details']=array(
						'amount' 		=> $order->order_total,
						'amount_currency' => get_woocommerce_currency(),
						'firstname'		=> $order->billing_first_name,
						'email' 		=> $order->billing_email, 
						'phone' 		=> substr( $order->billing_phone, -10 ),
						'lastname' 		=> $order->billing_last_name,
						'address1' 		=> $order->billing_address_1,
						'address2' 		=> $order->billing_address_2,
						'city' 			=> $order->billing_city,
						'state' 		=> $order->billing_state,
						'country' 		=> $order->billing_country,
						'zipcode' 		=> $order->billing_postcode,
					);
				}
				if(isset($params['order_id'])){ 
					$charge_data['metadata']['order_id']=$params['order_id'];
				}
			}

			$process = MyUserPay::charge($charge_data
			);
			if($process['status']){
			//success
			$response_data['charge']=$process;
			}else{
			//error
			$response_data['error_body']=$process['error']['message'];
			}
return $response_data;

		}
        /**
         * Check for valid gateway server callback
         **/
        function check_mup_wordpresspayplugin_response(){
            global $woocommerce;
            // $order = new WC_Order( 27 );
            // var_dump($order->order_total);
            // die(max( 0, apply_filters( 'woocommerce_calculated_total', round( WC()->cart->cart_contents_total + WC()->cart->fee_total + WC()->cart->tax_total, WC()->cart->dp ), WC()->cart ) ));
//die($order->get_checkout_payment_url( true ));
          //  var_dump($_REQUEST);exit;
			if( isset($_REQUEST['udf1']) ){
$trans_authorised=true;
				$order_id = $_REQUEST['udf1'];
				if($order_id != ''){
					try{
if(!isset($this->settings['one_dollar'])){
	//$this->settings['one_dollar'] 1 dollar = X onlarin
	$one_dollar=1;
}else{
	$one_dollar = $this->settings['one_dollar'];
}
if($this->settings['one_dollar']==0){
	$one_dollar=1;
}
						$order = new WC_Order( $order_id );
						$charge_data = $this->charge_user(round($order->order_total*100/$one_dollar),array('order'=>$order,'order_id'=>$order_id));
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
							
							$this->msg['class'] = 'error';
							$this->msg['message'] = "Thank you for the order. However, the transaction has been declined. ".$error_mes;
							$order->add_order_note('Transaction ERROR: '.$error_dev_mes.'<br/> ('.sanitize_text_field($_REQUEST['txnid']).')<br/>');
							$order->update_status('failed');
						}else{
							//charge was successful
							$this->msg['message'] = "Thank you for the order. Your account has been charged and your transaction is successful.";
							$this->msg['class'] = 'success';
							$order->payment_complete();
							//MyUser
							$order->add_order_note('Payment successfully completed.<br/> ('.sanitize_text_field($_REQUEST['txnid']).')');
							$order->reduce_order_stock();
							$woocommerce->cart->empty_cart();
							//$order->update_status('completed');
							$order->update_status('processing');
						}
						 
					}catch(Exception $e){
                        // $errorOccurred = true;
                        $msg = "Error";
					}
				}


				if ( function_exists( 'wc_add_notice' ) ) {
					wc_add_notice( $this->msg['message'], $this->msg['class'] );

				} else {
					if( 'success' == $this->msg['class'] ) {
						$woocommerce->add_message( $this->msg['message']);
					}else{
						$woocommerce->add_error( $this->msg['message'] );

					}
					$woocommerce->set_messages();
				}	
				//die($this->msg['message']);
				// @see: https://wordpress.org/support/topic/enabling-default-woocommerce-redirects/#post-9728440
				if('success' == $this->msg['class']) {
					if ( '' == $this->redirect_page || 0 == $this->redirect_page ) {
						$redirect_url = $this->get_return_url( $order );
					} else {
						$redirect_url = get_permalink( $this->redirect_page );
					}
				} else {
					$redirect_url = wc_get_checkout_url();

				}
				
				//wc_print_notices();
				
				wp_redirect( $redirect_url );
                exit;
	
			}

        }



	function mup_wordpresspayplugin_get_pages($title = false, $indent = true) {
			$wp_pages = get_pages('sort_column=menu_order');
			$page_list = array();
			if ($title) $page_list[] = $title;
			foreach ($wp_pages as $page) {
				$prefix = '';
				// show indented child pages?
				if ($indent) {
                	$has_parent = $page->post_parent;
                	while($has_parent) {
                    	$prefix .=  ' - ';
                    	$next_page = get_post($has_parent);
                    	$has_parent = $next_page->post_parent;
                	}
            	}
            	// add to page list array array
            	$page_list[$page->ID] = $prefix . $page->post_title;
        	}
        	return $page_list;
		}


		public function get_setting_link() {
			$use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;

			//$section_slug = $use_id_as_section ? 'mup_wordpresspayplugin' : strtolower( 'MUP_WC_Gateway_WPressPlugin' );
			$section_slug = $use_id_as_section ? $this->id : strtolower( 'MUP_WC_Gateway_WPressPlugin' );

			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
		}

		public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
			$this->notices[ $slug ] = array(
				'class'       => $class,
				'message'     => $message,
				'dismissible' => $dismissible,
			);
		}

	    public static $admin_notices=false;
		public function admin_notices() {
			if(self::$admin_notices===true){
				return;
			}else{
				self::$admin_notices=true;
			}
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

				if ( $notice['dismissible'] ) {
				?>
					<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-myuser-hide-notice', $notice_key ), 'wc_myuser_hide_notices_nonce', '_wc_myuser_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:absolute;right:1px;padding:9px;text-decoration:none;"></a>
				<?php
				}

				echo '<p>';
				echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
				echo '</p></div>';
			}
		}


	    }