<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for paygw_mercadopago.
 *
 * @package    paygw_mercadopago
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Mercado Pago';
$string['gatewayname'] = 'Mercado Pago';
$string['gatewaydescription'] = 'Pay with Pix, card or bank slip through Mercado Pago Checkout Pro. The amount is split automatically between the seller and the platform.';

// Site settings.
$string['appheading'] = 'Platform application';
$string['appheading_desc'] = 'Credentials of the Mercado Pago application that belongs to the platform, not to the seller. Register this exact redirect URI in the Mercado Pago panel: <code>{$a}</code>';
$string['clientid'] = 'Client ID';
$string['clientid_desc'] = 'Application number shown in the Mercado Pago developer panel.';
$string['clientsecret'] = 'Client secret';
$string['clientsecret_desc'] = 'Used only to exchange the authorisation code for the seller token. Never sent to the browser.';
$string['defaultfeepercent'] = 'Default commission (%)';
$string['defaultfeepercent_desc'] = 'Percentage the platform keeps on each sale. Mercado Pago deducts its own fee first, then this commission applies to the remainder.';

// Account settings.
$string['oauthstatus'] = 'Mercado Pago account';
$string['linkaccount'] = 'Link Mercado Pago account';
$string['oauthlinked'] = 'Linked to Mercado Pago user {$a->mpuserid}. Authorisation valid until {$a->expires}.';
$string['oauthexpired'] = 'The authorisation expired. Link the account again.';

// Errors.
$string['errornotlinked'] = 'Link the Mercado Pago account before enabling this gateway.';
$string['errorcurl'] = 'Could not reach Mercado Pago: {$a}';
$string['errorinvalidresponse'] = 'Mercado Pago returned an unexpected response for {$a}';
$string['errorapi'] = 'Mercado Pago rejected the request. {$a}';
$string['errormissingappconfig'] = 'The platform application is not configured. Set the client ID and secret in the plugin settings.';
$string['errorstatemismatch'] = 'The authorisation could not be verified. Start the process again.';
$string['testmode'] = 'Test mode';
$string['testmode_desc'] = 'Issue test tokens when sellers link their account, so the whole flow runs in the Mercado Pago sandbox. Buyer, seller and the platform application must all be on the same side: a real application with a test seller is refused with "one of the parties is a test account". Changing this does not convert existing links — sellers must link again. Never leave this on in production: real payments would stop working.';
$string['relinkaccount'] = 'Link a different account';
$string['unlinkaccount'] = 'Unlink account';
$string['unlinkconfirm'] = 'Unlink Mercado Pago user {$a} from this payment account? The gateway will be disabled and this company will stop selling until an account is linked again. Courses already bought keep their access. This does not revoke the authorisation inside Mercado Pago — the seller can remove it from their own account settings.';
$string['unlinkdone'] = 'Account unlinked. The gateway was disabled.';
$string['oauthcurrency'] = 'Payouts in {$a}.';
$string['errorverifyaccount'] = 'The account was authorised but could not be verified with Mercado Pago, so it was not linked. Try again. ({$a})';
$string['errorsitemismatch'] = 'This marketplace operates in {$a->platform} and the account you authorised is from {$a->seller}. Mercado Pago only splits payments between accounts of the same country, so this account cannot be linked. Use an account from {$a->platform}.';
$string['platformsite'] = 'Marketplace country';
$string['platformsite_desc'] = 'Country of the Mercado Pago account that receives the commission. It sets where sellers authorise and which accounts may be linked: the split only works between accounts of the same country, because the commission lands in the platform account and an account only holds its own currency. Sellers from other countries need a separate marketplace with its own application.';

// Task.
$string['taskrefreshtokens'] = 'Refresh Mercado Pago seller tokens';

// Checkout.
$string['errorcreatingpreference'] = 'Could not start the payment. Try again in a moment.';
$string['paymentapproved'] = 'Payment approved. Enjoy your course!';
$string['paymentpending'] = 'We are waiting for Mercado Pago to confirm your payment. With Pix this usually takes a few seconds. Access is released automatically as soon as it clears — you do not need to pay again.';
$string['paymentrejected'] = 'The payment was not completed. Nothing was charged.';

$string['savebeforelinking'] = 'Save this gateway first, then come back to link the Mercado Pago account.';

// Privacy.
$string['privacy:metadata:paygw_mercadopago'] = 'Payment transactions handled by this gateway.';
$string['privacy:metadata:paygw_mercadopago:userid'] = 'The person who paid.';
$string['privacy:metadata:paygw_mercadopago:amount'] = 'The amount charged.';
$string['privacy:metadata:paygw_mercadopago:currency'] = 'The currency charged.';
$string['privacy:metadata:paygw_mercadopago:status'] = 'Whether the payment was approved, rejected or is pending.';
$string['privacy:metadata:paygw_mercadopago:mppaymentid'] = 'The payment identifier at Mercado Pago.';
$string['privacy:metadata:paygw_mercadopago:timecreated'] = 'When the payment was started.';
$string['privacy:metadata:mercadopago'] = 'Data sent to Mercado Pago so the payment can be taken. Mercado Pago is the controller of what it receives.';
$string['privacy:metadata:mercadopago:amount'] = 'The amount to charge.';
$string['privacy:metadata:mercadopago:currency'] = 'The currency to charge in.';
$string['privacy:metadata:mercadopago:itemname'] = 'A description of what is being bought.';

$string['privacy:metadata'] = 'The Mercado Pago plugin stores the seller authorisation token on the payment account and sends the payment amount and buyer email to Mercado Pago.';
