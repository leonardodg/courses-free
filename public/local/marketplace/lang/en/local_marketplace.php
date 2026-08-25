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
 * Strings for local_marketplace.
 *
 * @package    local_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accessdays'] = 'Access for {$a} days';
$string['accessgranted'] = 'Access granted. Enjoy your course!';
$string['accesslifetime'] = 'Lifetime access';
$string['accessrecurring'] = 'Subscription, renewed every {$a} days';
$string['accessrecurringlimited'] = 'Subscription: {$a->billing} days per payment, up to {$a->cycles} payments';
$string['accessrecurringopen'] = 'Subscription: {$a->billing} days per payment, no end date';
$string['accessuntil'] = 'Access until {$a}';
$string['addmember'] = 'Add seller';
$string['addmember_help'] = 'Links an existing platform user to this company and grants them the seller role in the company category. The person must already have an account.';
$string['alreadyowned'] = 'You already have access to this offer.';
$string['buynow'] = 'Buy now';
$string['cancelconfirm'] = 'Cancel <strong>{$a->offer}</strong>? You keep access until {$a->date} — you paid for that period and it is not taken away. After that the access simply ends, and we stop reminding you to renew.';
$string['cancelconfirmlifetime'] = 'Stop renewal reminders for <strong>{$a}</strong>? Your access does not expire, so nothing changes except the reminders.';
$string['canceldone'] = 'Subscription cancelled. Your access runs until {$a}.';
$string['cancelledbut'] = 'Cancelled. Your access runs until {$a} — the period you paid for is not taken away.';
$string['cancelledlifetime'] = 'Renewal reminders are off. Your access does not expire.';
$string['cancelsubscription'] = 'Cancel subscription';
$string['cancelundo'] = 'Reactivate';
$string['cancelundone'] = 'Subscription reactivated. You will be reminded before it expires.';
$string['cannotsell'] = 'Free courses only';
$string['cansell'] = 'Selling';
$string['cansellyes'] = 'Ready to sell. Active gateway: {$a}';
$string['commissionpct'] = 'Platform commission (%)';
$string['companies'] = 'Companies';
$string['company'] = 'Company';
$string['companycnpj'] = 'CNPJ';
$string['companycnpj_help'] = 'Optional. An individual may sell without a CNPJ.';
$string['companycommission'] = 'Commission (%)';
$string['companycommission_help'] = 'Percentage the platform keeps on sales by this company, from 0 to 100. Leave empty to use the site default — empty and zero are different: empty means nothing was negotiated, zero means the partner is exempt.';
$string['companycreated'] = 'Company {$a} created. Add the other sellers below.';
$string['companyhostname'] = 'Custom domain';
$string['companyname'] = 'Name';
$string['companyowner'] = 'Owner';
$string['companyowner_help'] = 'The person who manages the company and links its Mercado Pago account. Must already have an account on the platform.';
$string['companypanel'] = 'Marketplace: company panel';
$string['companyshortname'] = 'Short name';
$string['companyshortname_help'] = 'Used in the company URL. Letters, numbers and hyphens only.';
$string['companystatus'] = 'Status';
$string['companytheme'] = 'Theme';
$string['companyupdated'] = 'Company {$a} updated.';
$string['configurepayment'] = 'Configure Mercado Pago';
$string['createcompany'] = 'Create company';
$string['createcompanyintro'] = 'Creating a company provisions a course category, assigns the seller role to the owner in that category and creates a payment account. Close the partnership first — this screen only carries it out.';
$string['defaultthemename'] = 'Site default theme';
$string['editcompanyintro'] = 'The short name cannot be changed: it is the category ID number and appears in storefront and dashboard links the seller may already have shared. The owner is changed on the sellers screen, where you can promote someone else first.';
$string['domainsuspendedtitle'] = 'This store is unavailable';
$string['domainsuspendedbody'] = 'The company behind this address is not selling at the moment. If you already bought a course, you can still reach it through the main platform.';
$string['erroraccessdays'] = 'Enter at least one day of access.';
$string['erroralreadymember'] = 'This person is already a seller of this company.';
$string['errorbillingdays'] = 'Enter the billing interval in days.';
$string['errorcannotremoveowner'] = 'The owner cannot be removed. Make someone else the owner first — a company without an owner has nobody responsible for its payment account.';
$string['errorcannotsell'] = 'This company cannot sell yet: configure a payment method first.';
$string['errorcommissionrange'] = 'Use a number from 0 to 100, or leave it empty to inherit the site default.';
$string['errorcurrencymismatch'] = 'This company receives in {$a->expected}, so it cannot sell an offer priced in {$a->given}. To sell in {$a->given}, link a Mercado Pago account from that country.';
$string['errordomainmap'] = 'Could not write the seller domain map. Check that the Moodle data directory is writable.';
$string['errorhostnametaken'] = 'This domain is already linked to another company.';
$string['errormaxcycles'] = 'Use zero for no limit, or a positive number.';
$string['errornoaccount'] = 'This company has no payment account. Reinstall or recreate the company.';
$string['errornocourses'] = 'Choose at least one course, or use the Whole catalogue type.';
$string['errorplatformhostingunavailable'] = 'Hosting video on the platform is not available yet.';
$string['errorrecurringfree'] = 'A subscription needs a price. A free one would expire with no way to renew.';
$string['errorsellerrolemissing'] = 'The seller role is missing. Reinstall the Marketplace plugin.';
$string['errorshortnametaken'] = 'This short name is already in use.';
$string['errorsinglemanycourses'] = 'A single course offer releases one course. Use Bundle for more than one.';
$string['expiringbody'] = 'Hello,

Your access to {$a->offer}, from {$a->company}, ends on {$a->date}.

There is no automatic charge — to keep your access, pay again here:
{$a->url}

If you would rather stop, do nothing and the access simply ends.';
$string['expiringbodyhtml'] = '<p>Hello,</p><p>Your access to <strong>{$a->offer}</strong>, from {$a->company}, ends on <strong>{$a->date}</strong>.</p><p>There is no automatic charge — to keep your access, <a href="{$a->url}">pay again here</a>.</p><p>If you would rather stop, do nothing and the access simply ends.</p>';
$string['expiringsubject'] = 'Your access to {$a->offer} ends in {$a->days} day(s)';
$string['free'] = 'Free';
$string['getfree'] = 'Get free access';
$string['hostingexternal'] = 'Outside the platform';
$string['hostingplatform'] = 'On the platform';
$string['hostingtype'] = 'Video hosting';
$string['linkednotenabled'] = 'The Mercado Pago account is linked but the gateway is switched off, so nothing can be sold yet. Open the payment settings and enable it.';
$string['makeowner'] = 'Make owner';
$string['makeseller'] = 'Make seller';
$string['managecourses'] = 'Manage courses';
$string['managemembers'] = 'Sellers';
$string['marketplace:createcompany'] = 'Create a company';
$string['marketplace:manageall'] = 'Manage every company on the platform';
$string['marketplace:managecompany'] = 'Manage the company';
$string['marketplace:managepayment'] = 'Manage the company payment account';
$string['marketplace:publishcourse'] = 'Publish a course for the company';
$string['marketplace:viewreport'] = 'View the company financial report';
$string['memberadded'] = 'Seller added.';
$string['memberowner'] = 'Owner';
$string['memberremoved'] = 'Seller removed.';
$string['memberrolechanged'] = 'Role changed.';
$string['members'] = 'Sellers';
$string['memberseller'] = 'Seller';
$string['membersof'] = 'Sellers of {$a}';
$string['messageprovider:expiring'] = 'Access about to expire';
$string['modedays'] = 'Fixed period';
$string['modelifetime'] = 'Lifetime';
$string['moderecurring'] = 'Subscription';
$string['mysubsactive'] = 'Subscriptions';
$string['mysubscriptions'] = 'My subscriptions';
$string['mysubspayments'] = 'Payments';
$string['nocompanies'] = 'No companies yet.';
$string['nocompany'] = 'You do not belong to any company.';
$string['nomembers'] = 'This company has no sellers.';
$string['nooffers'] = 'This company has no published offers yet.';
$string['nopaymentaccount'] = 'This company has no payment method configured, so it can only publish free courses.';
$string['nopayments'] = 'No payments yet.';
$string['nosubscriptions'] = 'You have not bought anything yet.';
$string['offeraccess'] = 'Access and billing';
$string['offeraccessdays'] = 'Days of access per payment';
$string['offeraccessdays_help'] = 'How long each payment unlocks. In a subscription this can exceed the billing interval to give a grace period: billing every 30 days while granting 35 lets a late payment through without cutting the learner off.';
$string['offeraccessmode'] = 'Access model';
$string['offeraccessmode_help'] = 'Lifetime never expires. Fixed period grants a number of days per purchase. Subscription grants a period and expects renewal.';
$string['offerbillingdays'] = 'Billing interval (days)';
$string['offerbillingdays_help'] = 'How often the learner is expected to pay again. Used for the expiry reminder.';
$string['offercourses'] = 'Courses released';
$string['offercourses_help'] = 'Which courses this offer unlocks. Not needed for Whole catalogue, which follows the company category.';
$string['offercreate'] = 'New offer';
$string['offeredit'] = 'Offer';
$string['offerincludes'] = 'Includes {$a} course(s)';
$string['offermaxcycles'] = 'Maximum payments';
$string['offermaxcycles_help'] = 'How many times this subscription may be charged in total. Zero means no limit. Use 12 for a monthly plan that runs for a year, or 3 for a yearly plan that runs for three years.';
$string['offername'] = 'Offer';
$string['offerprice'] = 'Price';
$string['offerprice_help'] = 'Zero makes the offer free. Free offers skip Mercado Pago entirely.';
$string['offerpublication'] = 'Publication';
$string['offerrecurringwarning'] = 'Mercado Pago has no recurring charge with split payments, so nothing is debited automatically. The learner receives a reminder before expiry with a link to pay again.';
$string['offersaved'] = 'Offer saved.';
$string['offersortorder'] = 'Display order';
$string['offerssection'] = 'Offers';
$string['offerstatus_help'] = 'Only published offers appear in the storefront. Archiving does not revoke access already bought.';
$string['offertype'] = 'Type';
$string['offertype_help'] = 'Single course sells one course. Bundle sells a chosen set — this is how you build tiers such as Basic, Standard and Complete for the same company. Whole catalogue follows the company category, so new courses join automatically.';
$string['offerunlocks'] = 'This is the offer that unlocks the content you were viewing.';
$string['pagesection'] = 'Storefront';
$string['pagetitle'] = 'Storefront title';
$string['pagetitle_help'] = 'Shown as the page heading. Leave empty to use the company name.';
$string['pageintro'] = 'Opening text';
$string['pageintro_help'] = 'Appears above the offers. Written as rich text and filtered by Moodle — it is sales copy, not a place for scripts.';
$string['pageaccent'] = 'Accent colour';
$string['pageaccent_help'] = 'Hexadecimal, such as #B85410. Colours the buy buttons and is exposed to your CSS as the --mp-accent variable.';
$string['pagecss'] = 'Custom stylesheet';
$string['pagecss_help'] = 'A .css file loaded after the theme, so it can override it. Served as a stylesheet, never inline — the browser can never treat it as script. For a fully custom page, build it anywhere you like and read the offers through the API instead.';
$string['errorpageaccent'] = 'Use a hexadecimal colour such as #B85410, or leave it empty.';
$string['paymentcurrency'] = 'Payout currency: {$a}';
$string['paymentcurrencyunknown'] = 'Payout currency unknown. Link the Mercado Pago account again to detect it.';
$string['paymentsection'] = 'Payment method';
$string['pluginname'] = 'Marketplace';
$string['privacy:metadata'] = 'The Marketplace plugin stores companies, their sellers and payment credentials.';
$string['privacy:metadata:entitlement'] = 'What a learner bought and how long their access runs.';
$string['privacy:metadata:entitlement:companyid'] = 'The company that sold it.';
$string['privacy:metadata:entitlement:cycles'] = 'How many payments have been made.';
$string['privacy:metadata:entitlement:offerid'] = 'The offer bought.';
$string['privacy:metadata:entitlement:status'] = 'Whether the access is active, expired or revoked.';
$string['privacy:metadata:entitlement:timeend'] = 'When access ends. Zero means it does not expire.';
$string['privacy:metadata:entitlement:timestart'] = 'When access started.';
$string['privacy:metadata:entitlement:userid'] = 'The learner.';
$string['privacy:metadata:member'] = 'Which companies a person sells for.';
$string['privacy:metadata:member:companyid'] = 'The company.';
$string['privacy:metadata:member:memberrole'] = 'Whether they own the company or sell for it.';
$string['privacy:metadata:member:timecreated'] = 'When the link was made.';
$string['privacy:metadata:member:userid'] = 'The person linked to the company.';
$string['renewnotice'] = 'Your access ends on {$a}. Renew to keep it.';
$string['renewnow'] = 'Renew now';
$string['reportaccessuntil'] = 'Access until';
$string['reportall'] = 'All time';
$string['reportcommission'] = 'Platform commission';
$string['reportcoursesnotice'] = 'A bundle counts in full for every course it unlocks — nobody buys a third of a bundle. So this column adds up to more than your total revenue, and is meant for comparing courses against each other, not for summing.';
$string['reportdays'] = 'Last {$a} days';
$string['reportentries'] = 'Sales';
$string['reportgross'] = 'Gross';
$string['reportlastpayment'] = 'Last payment';
$string['reportmppayment'] = 'Mercado Pago payment';
$string['reportnetnotice'] = 'The Mercado Pago fee is not shown here because it is not reported back to us: it varies by payment method and payout term, and is deducted on their side before the platform commission. Your net amount is the one in your Mercado Pago statement.';
$string['reportnocourses'] = 'No course has been sold yet.';
$string['reportnosales'] = 'No approved sales in this period.';
$string['reportnosubs'] = 'No subscription offers, or nobody has subscribed yet.';
$string['reportpayments'] = 'Payments';
$string['reportsales'] = 'Approved sales';
$string['reportsaleswith'] = 'Sales including it';
$string['reportsection'] = 'Sales';
$string['reportsubactive'] = 'Active';
$string['reportsubcancelled'] = 'Cancelled';
$string['reportsubduesoon'] = 'Ends in {$a} d';
$string['reportsubexpired'] = 'Expired';
$string['reportsubsnotice'] = 'There is no billing schedule to show: Mercado Pago has no recurring payments with split, so each renewal is a separate purchase that extends access. This screen shows how many times each learner has paid and how long their access still runs.';
$string['reportviewcourses'] = 'Courses sold';
$string['reportviewsubscriptions'] = 'Subscriptions';
$string['reportviewtransactions'] = 'Transactions';
$string['sellerrole'] = 'Seller';
$string['sellerroledesc'] = 'Publishes courses for a company. Cannot upload files, so course videos must be hosted outside the platform.';
$string['statusactive'] = 'Active';
$string['statusarchived'] = 'Archived';
$string['statusdraft'] = 'Draft';
$string['statuspublished'] = 'Published';
$string['statussuspended'] = 'Suspended';
$string['tasknotifyexpiring'] = 'Notify learners of expiring access';
$string['typebundle'] = 'Bundle';
$string['typecatalog'] = 'Whole catalogue';
$string['typesingle'] = 'Single course';
$string['unavailable'] = 'Not available for purchase yet.';
$string['viewstorefront'] = 'View storefront';
