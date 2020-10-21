<?php
/***************************************************************
*
* payment module 
* - use when merchant name not site name
* - configurable statement info
* - authorizenet payments 
* - via portal script 
*
* initial version against authorize.net AIM
*
* designed for OSCOM Phoenix
* version: 1.0 July 2020
* author: John Ferguson @BrockleyJohn oscommerce@sewebsites.net
* copyright (c) 2020 SE Websites
* 
* released under MIT licence without warranty express or implied
****************************************************************/

class cc_discreet {
  
  var $code, $title, $description, $enabled;
  var $portal_url = 'https://golden-age-erotica-books.com/catalog/ext/modules/payment/cc_discreet/payment_portal.php';
//  var $portal_url = 'https://phoenix.oscommercesites.com/1.0.5.0/ext/modules/payment/cc_discreet/payment_portal.php';

  const CONFIG_KEY_BASE = 'MODULE_PAYMENT_CC_DISCREET_';
  const DEBUG = false;

  function __construct() {
    global $PHP_SELF, $order;

    $this->signature = 'authorizenet|cc_discreet|1.0|1.0.5.0';
    $this->api_version = '3.1';

    $this->code = get_class($this);
    $this->title = constant(self::CONFIG_KEY_BASE . 'TEXT_TITLE');
    $this->description = constant(self::CONFIG_KEY_BASE . 'TEXT_DESCRIPTION');
    $this->sort_order = defined(self::CONFIG_KEY_BASE . 'SORT_ORDER') ? constant(self::CONFIG_KEY_BASE . 'SORT_ORDER') : 0;
    $this->enabled = defined(self::CONFIG_KEY_BASE . 'STATUS') && constant(self::CONFIG_KEY_BASE . 'STATUS') == 'True' ? true : false;
    if ( $this->enabled === true ) {
      if ( isset($order) && is_object($order) ) {
        $this->update_status();
      }
    }

    $this->public_title = constant(self::CONFIG_KEY_BASE . 'TEXT_PUBLIC_TITLE') . constant(self::CONFIG_KEY_BASE . 'TEXT_PUBLIC_DESCRIPTION');
    $this->sort_order = $this->sort_order ?? 0;
    $this->order_status = defined(self::CONFIG_KEY_BASE . 'PREPARE_ORDER_STATUS_ID') && ((int)constant(self::CONFIG_KEY_BASE . 'PREPARE_ORDER_STATUS_ID') > 0) ? (int)constant(self::CONFIG_KEY_BASE . 'PREPARE_ORDER_STATUS_ID') : 0;

    if ( defined(self::CONFIG_KEY_BASE . 'STATUS') ) {
      if ( constant(self::CONFIG_KEY_BASE . 'TRANSACTION_SERVER') == 'Sandbox' ) {
        $this->title .= ' [Sandbox]';
        $this->public_title .= ' (' . $this->code . '; Sandbox)';
        $this->login_id = constant(self::CONFIG_KEY_BASE . 'LOGIN_ID_TEST');
        $this->transaction_key = constant(self::CONFIG_KEY_BASE . 'TRANSACTION_KEY_TEST');
        $this->hash = constant(self::CONFIG_KEY_BASE . 'HASH_TEST');
        $this->gateway_url = 'https://test.authorize.net/gateway/transact.dll';
      } else {
        $this->login_id = constant(self::CONFIG_KEY_BASE . 'LOGIN_ID_LIVE');
        $this->transaction_key = constant(self::CONFIG_KEY_BASE . 'TRANSACTION_KEY_LIVE');
        $this->hash = constant(self::CONFIG_KEY_BASE . 'HASH_LIVE');
        $this->gateway_url = 'https://secure.authorize.net/gateway/transact.dll';
      }
      $this->description .= $this->getTestLinkInfo();
    }

    if ( !function_exists('curl_init') ) {
      $this->description = '<div class="secWarning alert alert-warning">' . constant(self::CONFIG_KEY_BASE . 'ERROR_ADMIN_CURL') . '</div>' . $this->description;

      $this->enabled = false;
    }

    if ( $this->enabled === true ) {
      if ( !tep_not_null($this->login_id) || !tep_not_null($this->transaction_key) || !tep_not_null($this->hash) ) {
        $this->description = '<div class="secWarning alert alert-warning">' . constant(self::CONFIG_KEY_BASE . 'ERROR_ADMIN_CONFIGURATION') . '</div>' . $this->description;

        $this->enabled = false;
      }
    }

    if ( $this->enabled === true ) {
      if ( isset($order) && is_object($order) ) {
        $this->update_status();
      }
    }

    if ( ($PHP_SELF == 'modules.php') && isset($_GET['action']) && ($_GET['action'] == 'install') && isset($_GET['subaction']) && ($_GET['subaction'] == 'conntest') ) {
      echo $this->getTestConnectionResult();
      exit;
    }
  }

  function getTestLinkInfo() {}

  function update_status() {
    global $order;

    if ( $this->enabled == true && (int)constant(self::CONFIG_KEY_BASE . 'ZONE') > 0 ) {
      $check_flag = false;
      $check_query = tep_db_query("select zone_id from zones_to_geo_zones where geo_zone_id = '" . (int)constant(self::CONFIG_KEY_BASE . 'ZONE') . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
      while ($check = tep_db_fetch_array($check_query)) {
        if ($check['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }
  }

  function javascript_validation() {
    return false;
  }

  function selection() {
    return [
      'id' => $this->code,
      'module' => $this->public_title
    ];
  }

  function pre_confirmation_check() {
    return false;
  }

  function confirmation() {
    global $order, $oscTemplate;
    
    $oscTemplate->addBlock('<script src="includes/card.js"></script>', 'footer_scripts');
    $firstname = $order->billing['firstname'];
    $lastname = $order->billing['lastname'];
    $msg = MODULE_PAYMENT_CC_DISCREET_CREDIT_CARD_ACCEPTED;
    $script = <<<EOS
<script>
//  $('form[name="checkout_confirmation"]').card({
var card = new Card({

    form: document.forms['checkout_confirmation'],
    container: '.card-wrapper', // *required*

    formSelectors: {
        numberInput: 'input[name="cc_number_nh-dns"]', // optional — default input[name="number"]
        expiryInput: 'select[name="cc_expires_month"], select[name="cc_expires_year"]', // optional — default input[name="expiry"]
        cvcInput: 'input[name="cc_ccv_nh-dns"]', // optional — default input[name="cvc"]
        nameInput: 'input[name="cc_owner_firstname"], input[name="cc_owner_lastname"]' // optional - defaults input[name="name"]
    },

//    width: 200, // optional — default 350px
    formatting: true, // optional - default true

    // Default placeholders for rendered fields - optional
    placeholders: {
        name: '$firstname $lastname',
        number: '•••• •••• •••• ••••',
        expiry: '••/••',
        cvc: '•••'
    }
  
  });
  
  const permitted = ['visa', 'visaelectron', 'mastercard', 'discover'];

  $(function(){
      year_selector = 'select[name="cc_expires_year"]';
      month_selector = 'select[name="cc_expires_month"]';

      $(month_selector).change(function(){
          year = $(year_selector).val() == '' ? '••' : $(year_selector).val();
          $('.jp-card-expiry').text($(this).val()+'/'+year);
      });
      $(year_selector).change(function(){
          month = $(month_selector).val() == '' ? '••': $(month_selector).val();
          $('.jp-card-expiry').text(month+'/'+$(this).val());
      });
      $(month_selector).add(year_selector).on('focus', function(){
          $('.jp-card-expiry').addClass('jp-card-focused');
      }).on('blur', function(){
          $('.jp-card-expiry').removeClass('jp-card-focused');
      });
      
      $('input[name="cc_number_nh-dns"]').blur(function() {
        // restrict card types
        if ($.inArray(card.cardType, permitted) < 0) {
//          alert('{$msg}');
          $('#card_msg').css('color', 'red');
          $('input[name="cc_number_nh-dns"]').focus();
        } else {
          $('#card_msg').css('color', 'black');
        }
      });
      
      $('form[name="checkout_confirmation"] .btn-success').click(function(e){
        this.disabled = true;
        if ($.inArray(card.cardType, permitted) < 0) {
          e.preventDefault();
          $('input[name="cc_number_nh-dns"]').focus();
          this.disabled = false;
        } else {
          $('form[name="checkout_confirmation"]').submit();
        }
      }); 
  });
</script>
EOS;
    $oscTemplate->addBlock($script, 'footer_scripts');
    
    $style = '<style>.exp-date .form-control {
    display: inline-block;
    width: revert;
}</style>';
    $oscTemplate->addBlock($style, 'footer_scripts');

    for ($i=1; $i<13; $i++) {
      $expires_month[] = [
        'id' => sprintf('%02d', $i), 
        'text' => sprintf('%02d', $i)
      ];
    }

    $today = getdate(); 
    for ($i=$today['year']; $i < $today['year']+10; $i++) {
      $expires_year[] = [
        'id' => strftime('%y',mktime(0,0,0,1,1,$i)), 
        'text' => strftime('%Y',mktime(0,0,0,1,1,$i))
      ];
    }

    $confirmation = [
      'title' => '<h6 class="mb-1 cc-title">' . constant(self::CONFIG_KEY_BASE . 'CC_FIELDS_TITLE') . "</h6>\n" . '<p class="cc-desc">' . constant(self::CONFIG_KEY_BASE . 'CC_FIELDS_DESCRIPTION') . '</p><div class="card-wrapper"></div>' . "\n",
      'fields' => [
        [
          'title' => constant(self::CONFIG_KEY_BASE . 'CREDIT_CARD_OWNER_FIRSTNAME'),
          'field' => tep_draw_input_field('cc_owner_firstname', $order->billing['firstname'], 'required="required"')
        ],
        [
          'title' => constant(self::CONFIG_KEY_BASE . 'CREDIT_CARD_OWNER_LASTNAME'),
          'field' => tep_draw_input_field('cc_owner_lastname', $order->billing['lastname'], 'required="required"')
        ],
        [
          'title' => constant(self::CONFIG_KEY_BASE . 'CREDIT_CARD_NUMBER'),
          'field' => '<span id="card_msg" style="font-size:75%">' . MODULE_PAYMENT_CC_DISCREET_CREDIT_CARD_ACCEPTED . '</span> ' . tep_draw_input_field('cc_number_nh-dns', null, 'required="required"')
        ],
        [
          'title' => constant(self::CONFIG_KEY_BASE . 'CREDIT_CARD_EXPIRES'),
          'field' => '<div class="exp-date">' . tep_draw_pull_down_menu('cc_expires_month', $expires_month) . '&nbsp;' . tep_draw_pull_down_menu('cc_expires_year', $expires_year) . '</div>'
        ],
        [
          'title' => constant(self::CONFIG_KEY_BASE . 'CREDIT_CARD_CCV'),
          'field' => tep_draw_input_field('cc_ccv_nh-dns', '', 'required="required" size="5" maxlength="4"')
        ],
        [
          'title' => constant(self::CONFIG_KEY_BASE . 'CREDIT_CARD_ZIP'),
          'field' => tep_draw_input_field('cc_owner_zip', $order->billing['postcode'], 'required="required" size="5" maxlength="12"')
        ]
      ]
    ];

    return $confirmation;
  }

  function process_button() {
    return false;
  }

  function before_process() {
    global $customer_id, $order, $sendto, $currency, $response;

    $params = [
      'server' => constant(self::CONFIG_KEY_BASE . 'TRANSACTION_SERVER'),
      'x_login' => substr($this->login_id, 0, 20),
      'x_tran_key' => substr($this->transaction_key, 0, 16),
      'x_version' => $this->api_version,
      'x_type' => ((constant(self::CONFIG_KEY_BASE . 'TRANSACTION_METHOD') == 'Capture') ? 'AUTH_CAPTURE' : 'AUTH_ONLY'),
      'x_method' => 'CC',
      'x_amount' => substr($this->format_raw($order->info['total']), 0, 15),
      'x_currency_code' => substr($currency, 0, 3),
      'x_card_num' => substr(preg_replace('/[^0-9]/', '', $_POST['cc_number_nh-dns']), 0, 22),
      'x_exp_date' => $_POST['cc_expires_month'] . $_POST['cc_expires_year'],
      'x_card_code' => substr($_POST['cc_ccv_nh-dns'], 0, 4),
      'x_description' => constant(self::CONFIG_KEY_BASE . 'STATEMENT_MERCHANT_NAME'),
//      'x_first_name' => substr($order->billing['firstname'], 0, 50),
//      'x_last_name' => substr($order->billing['lastname'], 0, 50),
      'x_first_name' => substr($_POST['cc_owner_firstname'], 0, 50),
      'x_last_name' => substr($_POST['cc_owner_lastname'], 0, 50),
      'x_company' => substr($order->billing['company'], 0, 50),
      'x_address' => substr($order->billing['street_address'], 0, 60),
      'x_city' => substr($order->billing['city'], 0, 40),
      'x_state' => substr($order->billing['state'], 0, 40),
//      'x_zip' => substr($order->billing['postcode'], 0, 20),
      'x_zip' => substr($_POST['cc_owner_zip'], 0, 20),
      'x_country' => substr($order->billing['country']['title'], 0, 60),
      'x_phone' => substr($order->customer['telephone'], 0, 25),
      'x_email' => substr($order->customer['email_address'], 0, 255),
      'x_cust_id' => substr($customer_id, 0, 20),
      'x_customer_ip' => tep_get_ip_address(),
      'x_relay_response' => 'FALSE',
      'x_delim_data' => 'TRUE',
      'x_delim_char' => ',',
      'x_encap_char' => '|'
    ];

    if (is_numeric($sendto) && ($sendto > 0)) {
      $params['x_ship_to_first_name'] = substr($order->delivery['firstname'], 0, 50);
      $params['x_ship_to_last_name'] = substr($order->delivery['lastname'], 0, 50);
      $params['x_ship_to_company'] = substr($order->delivery['company'], 0, 50);
      $params['x_ship_to_address'] = substr($order->delivery['street_address'], 0, 60);
      $params['x_ship_to_city'] = substr($order->delivery['city'], 0, 40);
      $params['x_ship_to_state'] = substr($order->delivery['state'], 0, 40);
      $params['x_ship_to_zip'] = substr($order->delivery['postcode'], 0, 20);
      $params['x_ship_to_country'] = substr($order->delivery['country']['title'], 0, 60);
    }

    if (constant(self::CONFIG_KEY_BASE . 'TRANSACTION_MODE') == 'Test') {
      $params['x_test_request'] = 'TRUE';
    }

    $tax_value = 0;

    foreach ($order->info['tax_groups'] as $key => $value) {
      if ($value > 0) {
        $tax_value += $this->format_raw($value);
      }
    }

    if ($tax_value > 0) {
      $params['x_tax'] = $this->format_raw($tax_value);
    }

    $params['x_freight'] = $this->format_raw($order->info['shipping_cost']);

    $post_string = '';

    foreach ($params as $key => $value) {
      $post_string .= $key . '=' . urlencode(trim($value)) . '&';
    }

    $post_string = substr($post_string, 0, -1);
    $count = 0;

    for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
      //$post_string .= '&x_line_item=' . urlencode($i+1) . '<|>' . urlencode(substr($order->products[$i]['name'], 0, 31)) . '<|>' . urlencode(substr($order->products[$i]['name'], 0, 255)) . '<|>' . urlencode($order->products[$i]['qty']) . '<|>' . urlencode($this->format_raw($order->products[$i]['final_price'])) . '<|>' . urlencode($order->products[$i]['tax'] > 0 ? 'YES' : 'NO');
      $count += $order->products[$i]['qty'];
    }

    $post_string .= '&x_line_item=' . urlencode(1) . '<|>' . urlencode(substr(constant(self::CONFIG_KEY_BASE . 'STATEMENT_TRANSACTION_DESCRIPTION'), 0, 31)) . '<|>' . urlencode(substr(constant(self::CONFIG_KEY_BASE . 'STATEMENT_TRANSACTION_DESCRIPTION'), 0, 255)) . '<|>' . urlencode($count) . '<|>' . urlencode($this->format_raw( ($order->info['total'] - $order->info['shipping_cost']) / $count)) . '<|>' . urlencode($tax_value > 0 ? 'YES' : 'NO');
    
    $transaction_response = $this->sendTransactionToPortal($post_string);
    
    if (self::DEBUG) error_log("response from portal '$transaction_response'");

    $response = [
      'x_response_code' => '-1',
      'x_response_subcode' => '-1',
      'x_response_reason_code' => '-1'
    ];

    if ( !empty($transaction_response) ) {
      $raw = explode('|,|', substr($transaction_response, 1, -1));

      if (self::DEBUG) error_log("raw '" .print_r($raw, true). "'");

      if ( count($raw) > 54 ) {
        $response = [
          'x_response_code' => $raw[0],
          'x_response_subcode' => $raw[1],
          'x_response_reason_code' => $raw[2],
          'x_response_reason_text' => $raw[3],
          'x_auth_code' => $raw[4],
          'x_avs_code' => $raw[5],
          'x_trans_id' => $raw[6],
          'x_invoice_num' => $raw[7],
          'x_description' => $raw[8],
          'x_amount' => $raw[9],
          'x_method' => $raw[10],
          'x_type' => $raw[11],
          'x_cust_id' => $raw[12],
          'x_first_name' => $raw[13],
          'x_last_name' => $raw[14],
          'x_company' => $raw[15],
          'x_address' => $raw[16],
          'x_city' => $raw[17],
          'x_state' => $raw[18],
          'x_zip' => $raw[19],
          'x_country' => $raw[20],
          'x_phone' => $raw[21],
          'x_fax' => $raw[22],
          'x_email' => $raw[23],
          'x_ship_to_first_name' => $raw[24],
          'x_ship_to_last_name' => $raw[25],
          'x_ship_to_company' => $raw[26],
          'x_ship_to_address' => $raw[27],
          'x_ship_to_city' => $raw[28],
          'x_ship_to_state' => $raw[29],
          'x_ship_to_zip' => $raw[30],
          'x_ship_to_country' => $raw[31],
          'x_tax' => $raw[32],
          'x_duty' => $raw[33],
          'x_freight' => $raw[34],
          'x_tax_exempt' => $raw[35],
          'x_po_num' => $raw[36],
          'x_MD5_Hash' => $raw[37],
          'x_cvv2_resp_code' => $raw[38],
          'x_cavv_response' => $raw[39],
          'x_account_number' => $raw[50],
          'x_card_type' => $raw[51],
          'x_split_tender_id' => $raw[52],
          'x_prepaid_requested_amount' => $raw[53],
          'x_prepaid_balance_on_card' => $raw[54],
          'x_SHA_Hash' => $raw[68]
        ];

        unset($raw);
      }
    }

    $error = false;


    if ( ($response['x_response_code'] == '1') || ($response['x_response_code'] == '4') ) {
//      if ( (tep_not_null($this->hash) && (strtoupper($response['x_MD5_Hash']) != strtoupper(md5($this->hash . $this->login_id . $response['x_trans_id'] . $this->format_raw($order->info['total']))))) || ($response['x_amount'] != $this->format_raw($order->info['total'])) ) {

      $order->info['order_status'] = (constant(self::CONFIG_KEY_BASE . 'CHECKED_ORDER_STATUS_ID') > 0 ? (int)constant(self::CONFIG_KEY_BASE . 'CHECKED_ORDER_STATUS_ID') : (int)DEFAULT_ORDERS_STATUS_ID);

      if (self::DEBUG) error_log("hash calculated '" . strtoupper(hash_hmac('sha512','^' . $this->login_id . '^' . $response['x_trans_id'] . '^' . $this->format_raw($order->info['total']) . '^', hex2bin($this->hash))) . "' hash supplied '" . strtoupper($response['x_SHA_Hash']) . "'");

      if ( (tep_not_null($this->hash) && (strtoupper($response['x_SHA_Hash']) != strtoupper(hash_hmac('sha512','^' . $this->login_id . '^' . $response['x_trans_id'] . '^' . $this->format_raw($order->info['total']) . '^', hex2bin($this->hash))) )) || ($response['x_amount'] != $this->format_raw($order->info['total'])) ) {
        if ( constant(self::CONFIG_KEY_BASE . 'REVIEW_ORDER_STATUS_ID') > 0 ) {
          $order->info['order_status'] = constant(self::CONFIG_KEY_BASE . 'REVIEW_ORDER_STATUS_ID');
        }
      }

      if ( $response['x_response_code'] == '4' ) {
        if ( constant(self::CONFIG_KEY_BASE . 'REVIEW_ORDER_STATUS_ID') > 0 ) {
          $order->info['order_status'] = constant(self::CONFIG_KEY_BASE . 'REVIEW_ORDER_STATUS_ID');
        }
      }
    } elseif ($response['x_response_code'] == '2') {
      $error = 'declined';
    } else {
      $error = 'general';
    }

    if ( $error !== false ) {
      switch ($response['x_response_reason_code']) {
        case '7':
          $error = 'invalid_expiration_date';
          break;

        case '8':
          $error = 'expired';
          break;

        case '13':
          $error = 'merchant_account';
          break;

        case '6':
        case '17':
        case '28':
          $error = 'declined';
          break;
          
        case '27':
          $error = 'avs';
          break;

        case '39':
          $error = 'currency';
          break;
          
        case '45':
        case '65':
          if ($response['x_cvv2_resp_code'] == 'N') {
            $error = 'ccv';
          }
          break;

        case '78':
          $error = 'ccv';
          break;
      }
    }

    if ($error !== false) {
      $this->sendDebugEmail($response);

      tep_redirect(tep_href_link('checkout_payment.php', 'payment_error=' . $this->code . '&error=' . $error, 'SSL'));
    }
  }

  function sendTransactionToPortal($parameters) {
    $server = parse_url($this->portal_url);

    if ( !isset($server['port']) ) {
      $server['port'] = ($server['scheme'] == 'https') ? 443 : 80;
    }

    if ( !isset($server['path']) ) {
      $server['path'] = '/';
    }

    $curl = curl_init($server['scheme'] . '://' . $server['host'] . $server['path'] . (isset($server['query']) ? '?' . $server['query'] : ''));
    curl_setopt($curl, CURLOPT_PORT, $server['port']);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    if (self::DEBUG) error_log("send to portal on '{$this->portal_url}' using\n" . print_r($parameters, true));

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
  }
  
  function sendToGateway() {
    $post_string = '';
    foreach ($_POST as $key => $value) {
      if ($key != 'server' && $key != 'x_line_item') {
        $post_string .= $key . '=' . urlencode(trim($value)) . '&';
      }
    }

    $post_string = substr($post_string, 0, -1);

    $linebits = explode('<|>', $_POST['x_line_item']);
    $lineitem = '';
    foreach ($linebits as $linebit) {
      $lineitem .= urlencode($linebit) . '<|>';
    }
    $lineitem = substr($lineitem, 0, -3);
    $post_string .= '&x_line_item=' . $lineitem;
    
    if ( $_POST['server'] == 'Live' ) {
      $gateway_url = 'https://secure.authorize.net/gateway/transact.dll';
    } else {
      $gateway_url = 'https://test.authorize.net/gateway/transact.dll';
    }

    $response = $this->sendTransactionToGateway($gateway_url, $post_string);
    
    if (self::DEBUG) error_log("response from gateway '$response'");
    
    return $response;
  }

  function sendTransactionToGateway($url, $parameters) {
    $server = parse_url($url);

    if ( !isset($server['port']) ) {
      $server['port'] = ($server['scheme'] == 'https') ? 443 : 80;
    }

    if ( !isset($server['path']) ) {
      $server['path'] = '/';
    }

    $curl = curl_init($server['scheme'] . '://' . $server['host'] . $server['path'] . (isset($server['query']) ? '?' . $server['query'] : ''));
    curl_setopt($curl, CURLOPT_PORT, $server['port']);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($curl);

    curl_close($curl);

    return $result;
  }

  function after_process() {
    global $response, $order, $insert_id;

    $status = array();

    if ( tep_not_null($this->hash) ) {
//      if ( strtoupper($response['x_SHA_Hash']) == strtoupper(md5($this->hash . $this->login_id . $response['x_trans_id'] . $this->format_raw($order->info['total']))) ) {
      if ( strtoupper($response['x_SHA_Hash']) == strtoupper(hash_hmac('sha512','^' . $this->login_id . '^' . $response['x_trans_id'] . '^' . $this->format_raw($order->info['total']) . '^', hex2bin($this->hash))) ) {
        $status[] = 'SHA512 Hash: Match';
      } else {
        $status[] = '*** SHA512 Hash Does Not Match ***';
      }
    }

    if ( $response['x_amount'] != $this->format_raw($order->info['total']) ) {
      $status[] = '*** Order Total Does Not Match Transaction Total ***';
    }

    $status[] = 'Response: ' . tep_db_prepare_input($response['x_response_reason_text']) . ' (' . tep_db_prepare_input($response['x_response_reason_code']) . ')';
    $status[] = 'Transaction ID: ' . tep_db_prepare_input($response['x_trans_id']);

    $avs_response = '?';

    if ( !empty($response['x_avs_code']) ) {
      if ( defined(self::CONFIG_KEY_BASE . 'TEXT_AVS_' . $response['x_avs_code']) ) {
        $avs_response = constant(self::CONFIG_KEY_BASE . 'TEXT_AVS_' . $response['x_avs_code']) . ' (' . $response['x_avs_code'] . ')';
      } else {
        $avs_response = $response['x_avs_code'];
      }
    }

    $status[] = 'AVS: ' . tep_db_prepare_input($avs_response);

    $cvv2_response = '?';

    if ( !empty($response['x_cvv2_resp_code']) ) {
      if ( defined(self::CONFIG_KEY_BASE . 'TEXT_CVV2_' . $response['x_cvv2_resp_code']) ) {
        $cvv2_response = constant(self::CONFIG_KEY_BASE . 'TEXT_CVV2_' . $response['x_cvv2_resp_code']) . ' (' . $response['x_cvv2_resp_code'] . ')';
      } else {
        $cvv2_response = $response['x_cvv2_resp_code'];
      }
    }

    $status[] = 'Card Code: ' . tep_db_prepare_input($cvv2_response);

    $cavv_response = '?';

    if ( !empty($response['x_cavv_response']) ) {
      if ( defined(self::CONFIG_KEY_BASE . 'TEXT_CAVV_' . $response['x_cavv_response']) ) {
        $cavv_response = constant(self::CONFIG_KEY_BASE . 'TEXT_CAVV_' . $response['x_cavv_response']) . ' (' . $response['x_cavv_response'] . ')';
      } else {
        $cavv_response = $response['x_cavv_response'];
      }
    }

    $status[] = 'Card Holder: ' . tep_db_prepare_input($cavv_response);

    $sql_data_array = array('orders_id' => $insert_id,
                            'orders_status_id' => constant(self::CONFIG_KEY_BASE . 'TRANSACTION_ORDER_STATUS_ID'),
                            'date_added' => 'now()',
                            'customer_notified' => '0',
                            'comments' => implode("\n", $status));

    tep_db_perform('orders_status_history', $sql_data_array);
  }

  function get_error() {
    $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_GENERAL');

    switch ($_GET['error']) {
      case 'invalid_expiration_date':
        $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_INVALID_EXP_DATE');
        break;

      case 'expired':
        $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_EXPIRED');
        break;

      case 'declined':
        $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_DECLINED');
        break;

      case 'ccv':
        $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_CCV');
        break;
        
      case 'avs':
        $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_AVS');
        break;

      case 'merchant_account':
        $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_MERCHANT_ACCOUNT');
        break;

      case 'currency':
        $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_CURRENCY');
        break;

      default:
        $error_message = constant(self::CONFIG_KEY_BASE .  'ERROR_GENERAL');
        break;
    }

    $error = array('title' => constant(self::CONFIG_KEY_BASE .  'ERROR_TITLE'),
                   'error' => $error_message);

    return $error;
  }

  function check() {
    if (!isset($this->_check)) {
      $check_query = tep_db_query("select configuration_value from configuration where configuration_key = '" . self::CONFIG_KEY_BASE . 'STATUS' . "'");
      $this->_check = tep_db_num_rows($check_query);
    }
    return $this->_check;
  }

  function install($parameter = null) 
  {
    $params = $this->getParams();

    if (isset($parameter)) {
      if (isset($params[$parameter])) {
        $params = array($parameter => $params[$parameter]);
      } else {
        $params = array();
      }
    }

    foreach ($params as $key => $data) {
      $sql_data_array = [
        'configuration_title' => $data['title'],
        'configuration_key' => $key,
        'configuration_value' => (isset($data['value']) ? $data['value'] : ''),
        'configuration_description' => $data['desc'],
        'configuration_group_id' => '6',
        'sort_order' => '0',
        'date_added' => 'now()'
      ];

      if (isset($data['set_func'])) {
        $sql_data_array['set_function'] = $data['set_func'];
      }

      if (isset($data['use_func'])) {
        $sql_data_array['use_function'] = $data['use_func'];
      }

      tep_db_perform('configuration', $sql_data_array);
    }
  }

  function remove() {
    tep_db_query("delete from configuration where configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }

  function keys() {
    $keys = array_keys($this->getParams());

    if ($this->check()) {
      foreach ($keys as $key) {
        if (!defined($key)) {
          $this->install($key);
        }
      }
    }

    return $keys;
  }

// format prices without currency formatting
  function format_raw($number, $currency_code = '', $currency_value = '') {
    global $currencies, $currency;

    if (empty($currency_code) || !$currencies->is_set($currency_code)) {
      $currency_code = $currency;
    }

    if (empty($currency_value) || !is_numeric($currency_value)) {
      $currency_value = $currencies->currencies[$currency_code]['value'];
    }

    return number_format(tep_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
  }

  function sendDebugEmail($response = array()) {
    if (tep_not_null(constant(self::CONFIG_KEY_BASE . 'DEBUG_EMAIL'))) {
      $email_body = '';

      if (!empty($response)) {
        $email_body .= 'RESPONSE:' . "\n\n" . print_r($response, true) . "\n\n";
      }

      if (!empty($_POST)) {
        if (isset($_POST['cc_number_nh-dns'])) {
          $_POST['cc_number_nh-dns'] = 'XXXX' . substr($_POST['cc_number_nh-dns'], -4);
        }

        if (isset($_POST['cc_ccv_nh-dns'])) {
          $_POST['cc_ccv_nh-dns'] = 'XXX';
        }

        if (isset($_POST['cc_issue_nh-dns'])) {
          $_POST['cc_issue_nh-dns'] = 'XXX';
        }

        if (isset($_POST['cc_expires_month'])) {
          $_POST['cc_expires_month'] = 'XX';
        }

        if (isset($_POST['cc_expires_year'])) {
          $_POST['cc_expires_year'] = 'XX';
        }

        if (isset($_POST['cc_starts_month'])) {
          $_POST['cc_starts_month'] = 'XX';
        }

        $email_body .= '$_POST:' . "\n\n" . print_r($_POST, true) . "\n\n";
      }

      if (!empty($_GET)) {
        $email_body .= '$_GET:' . "\n\n" . print_r($_GET, true) . "\n\n";
      }

      if (!empty($email_body)) {
        tep_mail('', constant(self::CONFIG_KEY_BASE . 'DEBUG_EMAIL'), 'CC Bypass Debug E-Mail', trim($email_body), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      }
    }
  }

  function getParams() {

    if (!defined(self::CONFIG_KEY_BASE . 'TRANSACTION_ORDER_STATUS_ID')) {
      $status_name = constant(self::CONFIG_KEY_BASE . 'TRANSACTIONS_ORDER_STATUS_NAME');
      $check_query = tep_db_query("select orders_status_id from orders_status where orders_status_name = '$status_name' limit 1");

      if (tep_db_num_rows($check_query) < 1) {
        $status_query = tep_db_query("select max(orders_status_id) as status_id from orders_status");
        $status = tep_db_fetch_array($status_query);

        $transaction_status_id = $status['status_id']+1;

        $languages = tep_get_languages();

        foreach ($languages as $lang) {
          tep_db_query("insert into orders_status (orders_status_id, language_id, orders_status_name) values ('" . $transaction_status_id . "', '" . $lang['id'] . "', '$status_name')");
        }

        $flags_query = tep_db_query("describe orders_status public_flag");
        if (tep_db_num_rows($flags_query) == 1) {
          tep_db_query("update orders_status set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $transaction_status_id . "'");
        }
      } else {
        $check = tep_db_fetch_array($check_query);

        $transaction_status_id = $check['orders_status_id'];
      }
    } else {
      $transaction_status_id = constant(self::CONFIG_KEY_BASE . 'TRANSACTION_ORDER_STATUS_ID');
    }

    if (!defined(self::CONFIG_KEY_BASE . 'REVIEW_ORDER_STATUS_ID')) {
      $status_name = constant(self::CONFIG_KEY_BASE . 'CHECK_ORDER_STATUS_NAME');
      $check_query = tep_db_query("select orders_status_id from orders_status where orders_status_name = '$status_name' limit 1");

      if (tep_db_num_rows($check_query) < 1) {
        $status_query = tep_db_query("select max(orders_status_id) as status_id from orders_status");
        $status = tep_db_fetch_array($status_query);

        $check_status_id = $status['status_id']+1;

        $languages = tep_get_languages();

        foreach ($languages as $lang) {
          tep_db_query("insert into orders_status (orders_status_id, language_id, orders_status_name) values ('" . $check_status_id . "', '" . $lang['id'] . "', '$status_name')");
        }

        $flags_query = tep_db_query("describe orders_status public_flag");
        if (tep_db_num_rows($flags_query) == 1) {
          tep_db_query("update orders_status set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $check_status_id . "'");
        }
      } else {
        $check = tep_db_fetch_array($check_query);

        $check_status_id = $check['orders_status_id'];
      }
    } else {
      $check_status_id = constant(self::CONFIG_KEY_BASE . 'REVIEW_ORDER_STATUS_ID');
    }

    $params = [
      self::CONFIG_KEY_BASE . 'STATUS' => [
        'title' => 'Enable Discreet Card Payments',
        'desc' => 'Do you want to accept card payments via portal?',
        'value' => 'True',
        'set_func' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
      ],
      self::CONFIG_KEY_BASE . 'STATEMENT_MERCHANT_NAME' => [
        'title' => 'Merchant Name on Statement',
        'desc' => 'How the merchant name is displayed on CC statement'
      ],
      self::CONFIG_KEY_BASE . 'STATEMENT_TRANSACTION_DESCRIPTION' => [
        'title' => 'Transaction Descriptor on Statement',
        'desc' => 'How the transaction is described on CC statement'
      ],
      self::CONFIG_KEY_BASE . 'LOGIN_ID_LIVE' => [
        'title' => 'API Login ID (Live)',
        'desc' => 'The API Login ID for the live Authorize.net service'
      ],
      self::CONFIG_KEY_BASE . 'TRANSACTION_KEY_LIVE' => [
        'title' => 'Transaction Key (Live)',
        'desc' => 'The API Login ID for the live Authorize.net service'
      ],
      self::CONFIG_KEY_BASE . 'HASH_LIVE' => [
        'title' => 'Signature Key (Live)',
        'desc' => 'The (SHA512 Hash) Key used to verify live transactions'
      ],
      self::CONFIG_KEY_BASE . 'TRANSACTION_METHOD' => [
        'title' => 'Transaction Method',
        'desc' => 'The processing method to use for each transaction.',
        'value' => 'Capture',
        'set_func' => 'tep_cfg_select_option(array(\'Pre-Authorization\', \'Capture\'), '
      ],
   /*   self::CONFIG_KEY_BASE . 'TRANSACTION_DESCRIPTION' => [
        'title' => 'Transaction Description',
        'desc' => 'Choose whether the transaction description in ANZ eGate email &amp; result page is:',
        'value' => 'Products bought',
        'set_func' => 'tep_cfg_select_option(array(\'Products bought\', \'Store name\'), '
      ], */
      self::CONFIG_KEY_BASE . 'CHECKED_ORDER_STATUS_ID' => [
        'title' => 'Set Successful Order Status',
        'desc' => 'Set the status of orders which pass all fraud checks to this value',
        'value' => '0',
        'set_func' => 'tep_cfg_pull_down_order_statuses(',
        'use_func' => 'tep_get_order_status_name'
      ],
      self::CONFIG_KEY_BASE . 'REVIEW_ORDER_STATUS_ID' => [
        'title' => 'Review Order Status',
        'desc' => 'Set the status of orders flagged for review to this value',
        'value' => $check_status_id,
        'set_func' => 'tep_cfg_pull_down_order_statuses(',
        'use_func' => 'tep_get_order_status_name'
      ],
      self::CONFIG_KEY_BASE . 'TRANSACTION_ORDER_STATUS_ID' => [
        'title' => 'Transaction Order Status',
        'desc' => 'Include transaction information in this order status level',
        'value' => $transaction_status_id,
        'set_func' => 'tep_cfg_pull_down_order_statuses(',
        'use_func' => 'tep_get_order_status_name'
      ],
      self::CONFIG_KEY_BASE . 'ZONE' => [
        'title' => 'Payment Zone',
        'desc' => 'If a zone is selected, only enable this payment method for that zone.',
        'value' => '0',
        'use_func' => 'tep_get_zone_class_title',
        'set_func' => 'tep_cfg_pull_down_zone_classes('
      ],
      self::CONFIG_KEY_BASE . 'TRANSACTION_MODE' => [
        'title' => 'Transaction Mode',
        'desc' => 'Should transactions be processed in test mode?',
        'value' => 'Live',
        'set_func' => 'tep_cfg_select_option(array(\'Live\', \'Test\'), '
      ],
      self::CONFIG_KEY_BASE . 'TRANSACTION_SERVER' => [
        'title' => 'Transaction Server',
        'desc' => 'Perform transactions on the live or sandbox server. The sandbox server is only be used by developers with Authorize.net test accounts.',
        'value' => 'Live',
        'set_func' => 'tep_cfg_select_option(array(\'Live\', \'Sandbox\'), '
      ],
      self::CONFIG_KEY_BASE . 'LOGIN_ID_TEST' => [
        'title' => 'API Login ID (Test)',
        'desc' => 'The API Login ID for the test Authorize.net service'
      ],
      self::CONFIG_KEY_BASE . 'TRANSACTION_KEY_TEST' => [
        'title' => 'Transaction Key (Test)',
        'desc' => 'The API Login ID for the test Authorize.net service'
      ],
      self::CONFIG_KEY_BASE . 'HASH_TEST' => [
        'title' => 'Signature Key (Test)',
        'desc' => 'The (SHA512 Hash) Key used to verify test transactions'
      ],
      self::CONFIG_KEY_BASE . 'DEBUG_EMAIL' => [
        'title' => 'Debug E-Mail Address',
        'desc' => 'All parameters of an invalid transaction will be sent to this email address if one is entered.'
      ],
      self::CONFIG_KEY_BASE . 'SORT_ORDER' => [
        'title' => 'Sort order of display.',
        'desc' => 'Sort order of display. Lowest is displayed first.',
        'value' => '0'
      ]
    ];

    return $params;
  }
  
}