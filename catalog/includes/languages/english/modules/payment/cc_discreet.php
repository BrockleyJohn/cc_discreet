<?php
/*

  payment module 
  - use when merchant name not site name
  - configurable statement info
  - authorizenet payments 
  - via portal script 
  author: John Ferguson @BrockleyJohn oscommerce@sewebsites.net
 
  version: 1.0 July 2020
  copyright (c) 2020 SE Websites
 
  released under MIT licence without warranty express or implied
*/
// module translations
$pfx = 'MODULE_PAYMENT_CC_DISCREET_';
define($pfx . 'TEXT_TITLE','Discreet CC Payments');
define($pfx . 'TEXT_DESCRIPTION','Accept card payments discreetly via authorizenet');
define($pfx . 'TEXT_PUBLIC_TITLE','Credit Card (Discreet Processing)');
//define($pfx . 'TEXT_PUBLIC_DESCRIPTION', '<br><b> Visa/MasterCard/Discover</b>');
define($pfx . 'TEXT_PUBLIC_DESCRIPTION', '<br><img src="images/ccdiscreet.png">');

define($pfx . 'ERROR_ADMIN_CURL', 'This module requires cURL to be enabled in PHP and will not load until it has been enabled on this webserver.');
define($pfx . 'ERROR_ADMIN_CONFIGURATION', 'This module will not load until the Login ID, Transaction Key and MD5 Hash for the chosen environment has been configured. Please edit the settings.');
define($pfx . 'TEXT_WARNING_DEMO_MODE', 'Transaction performed in test mode.');
define($pfx . 'SHIP_BILL_DIFFERENT_ERROR', 'We only ship to the card billing address. Please choose another payment method.');

// confirmation 
// preamble
define($pfx . 'CC_FIELDS_TITLE', 'Discreet Card Payment');
if (defined($pfx . 'STATEMENT_MERCHANT_NAME')) define($pfx . 'CC_FIELDS_DESCRIPTION', 'The charge will appear on your CC statement as:<br/>"<strong>' . constant($pfx . 'STATEMENT_MERCHANT_NAME') . '</strong>"<br/>and the transaction for:<br/>"<strong>' . constant($pfx . 'STATEMENT_TRANSACTION_DESCRIPTION') . '</strong>"');
// alert
define($pfx . 'CREDIT_CARD_ACCEPTED', 'We accept Visa/Mastercard/Discover only');
// field prompts
define($pfx . 'CREDIT_CARD_OWNER_FIRSTNAME', 'Card Owner First Name:');
define($pfx . 'CREDIT_CARD_OWNER_LASTNAME', 'Card Owner Last Name:');
define($pfx . 'CREDIT_CARD_NUMBER', 'Card Number:');
define($pfx . 'CREDIT_CARD_EXPIRES', 'Card Expiry Date:');
define($pfx . 'CREDIT_CARD_CCV', 'Card Code Number (CCV):');
define($pfx . 'CREDIT_CARD_ZIP', 'Card Owner Zip Code:');

// additional order status names (changing these does not take effect unless you uninstall & reinstall)
define($pfx . 'TRANSACTIONS_ORDER_STATUS_NAME', 'CC Discreet [Transactions]');
define($pfx . 'CHECK_ORDER_STATUS_NAME', 'Check Order [CC Discreet]');

// authorisation code translations
define($pfx . 'TEXT_AVS_A', 'Address (Street) matches, ZIP does not');
define($pfx . 'TEXT_AVS_B', 'Address information not provided for AVS check');
define($pfx . 'TEXT_AVS_E', 'AVS error');
define($pfx . 'TEXT_AVS_G', 'Non-U.S. Card Issuing Bank');
define($pfx . 'TEXT_AVS_N', 'No Match on Address (Street) or ZIP');
define($pfx . 'TEXT_AVS_P', 'AVS not applicable for this transaction');
define($pfx . 'TEXT_AVS_R', 'Retry – System unavailable or timed out');
define($pfx . 'TEXT_AVS_S', 'Service not supported by issuer');
define($pfx . 'TEXT_AVS_U', 'Address information is unavailable');
define($pfx . 'TEXT_AVS_W', 'Nine digit ZIP matches, Address (Street) does not');
define($pfx . 'TEXT_AVS_X', 'Address (Street) and nine digit ZIP match');
define($pfx . 'TEXT_AVS_Y', 'Address (Street) and five digit ZIP match');
define($pfx . 'TEXT_AVS_Z', 'Five digit ZIP matches, Address (Street) does not');

define($pfx . 'TEXT_CVV2_M', 'Match');
define($pfx . 'TEXT_CVV2_N', 'No Match');
define($pfx . 'TEXT_CVV2_P', 'Not Processed');
define($pfx . 'TEXT_CVV2_S', 'Should have been present');
define($pfx . 'TEXT_CVV2_U', 'Issuer unable to process request');

define($pfx . 'TEXT_CAVV_0', 'CAVV not validated because erroneous data was submitted');
define($pfx . 'TEXT_CAVV_1', 'CAVV failed validation');
define($pfx . 'TEXT_CAVV_2', 'CAVV passed validation');
define($pfx . 'TEXT_CAVV_3', 'CAVV validation could not be performed; issuer attempt incomplete');
define($pfx . 'TEXT_CAVV_4', 'CAVV validation could not be performed; issuer system error');
define($pfx . 'TEXT_CAVV_5', 'Reserved for future use');
define($pfx . 'TEXT_CAVV_6', 'Reserved for future use');
define($pfx . 'TEXT_CAVV_7', 'CAVV attempt – failed validation – issuer available (U.S.-issued card/non-U.S. acquirer)');
define($pfx . 'TEXT_CAVV_8', 'CAVV attempt – passed validation – issuer available (U.S.-issued card/non-U.S. acquirer)');
define($pfx . 'TEXT_CAVV_9', 'CAVV attempt – failed validation – issuer unavailable (U.S.-issued card/non-U.S. acquirer)');
define($pfx . 'TEXT_CAVV_A', 'CAVV attempt – passed validation – issuerunavailable (U.S.-issued card/non-U.S. acquirer)');
define($pfx . 'TEXT_CAVV_B', 'CAVV passed validation, information only, no liability shift');

// error translations
define($pfx . 'ERROR_TITLE', 'There has been an error processing your credit card');
define($pfx . 'ERROR_GENERAL', 'Please try again and if problems persist, please try another payment method.');
define($pfx . 'ERROR_DECLINED', 'I\'m sorry, your charge has been declined. Various possibilities, but very often a security measure... helping to ensure against possible fraudulent use. You will have to contact your card company, advise them that this is a legitimate purchase. They may have already contacted you for verification.
<br><br>
<strong>PLEASE NOTE:</strong> My DBA (doing business as) name is <span style=\'color:#f00;font-weight:bold\'>GA Online Books</span>. If you speak to a credit card rep, that\'s the name they will need. 
<br><br>
Feel free to let me know if there is anything I can do to assist.');
define($pfx . 'ERROR_INVALID_EXP_DATE', 'The credit card expiration date is invalid. Please check the card information and try again.');
define($pfx . 'ERROR_EXPIRED', 'The credit card has expired. Please try again with another card or payment method.');
define($pfx . 'ERROR_AVS', 'Your order was declined due to an AVS (Address Verification System) issue. The zip code provided does not match up with what your card company has on file. If you believe the zip you entered to be correct, please contact your cardholder, ensure what they have matches up. Feel free to try again when resolved.<br><br>Please let me know if there is anything I can do to assist');
define($pfx . 'ERROR_CCV', 'The credit card code number (CCV) is invalid. Please check the card information and try again.');
define($pfx . 'ERROR_MERCHANT_ACCOUNT', 'The API Login ID or Transaction Key is invalid or the account is inactive. Please review your module configuration settings and try again.');
define($pfx . 'ERROR_CURRENCY', 'The supplied currency code is either invalid, not supported, not allowed for this merchant or doesn\'t have an exchange rate. Please review your currency and module configuration settings and try again.');

define($pfx . 'DIALOG_CONNECTION_LINK_TITLE', 'Test API Server Connection');
define($pfx . 'DIALOG_CONNECTION_TITLE', 'API Server Connection Test');
define($pfx . 'DIALOG_CONNECTION_GENERAL_TEXT', 'Testing connection to server..');
define($pfx . 'DIALOG_CONNECTION_BUTTON_CLOSE', 'Close');
define($pfx . 'DIALOG_CONNECTION_TIME', 'Connection Time:');
define($pfx . 'DIALOG_CONNECTION_SUCCESS', 'Success!');
define($pfx . 'DIALOG_CONNECTION_FAILED', 'Failed! Please review the Verify SSL Certificate settings and try again.');
define($pfx . 'DIALOG_CONNECTION_ERROR', 'An error occurred. Please refresh the page, review your settings, and try again.');
