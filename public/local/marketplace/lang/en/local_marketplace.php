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

$string['pluginname'] = 'Marketplace';

// Capabilities.
$string['marketplace:createcompany'] = 'Create a company';
$string['marketplace:managecompany'] = 'Manage the company';
$string['marketplace:managepayment'] = 'Manage the company payment account';
$string['marketplace:publishcourse'] = 'Publish a course for the company';
$string['marketplace:viewreport'] = 'View the company financial report';
$string['marketplace:manageall'] = 'Manage every company on the platform';

// Seller role.
$string['sellerrole'] = 'Seller';
$string['sellerroledesc'] = 'Publishes courses for a company. Cannot upload files, so course videos must be hosted outside the platform.';

// Company.
$string['company'] = 'Company';
$string['companies'] = 'Companies';
$string['companyname'] = 'Name';
$string['companyshortname'] = 'Short name';
$string['companyshortname_help'] = 'Used in the company URL. Letters, numbers and hyphens only.';
$string['companycnpj'] = 'CNPJ';
$string['companycnpj_help'] = 'Optional. An individual may sell without a CNPJ.';
$string['companyhostname'] = 'Custom domain';
$string['companytheme'] = 'Theme';
$string['companystatus'] = 'Status';
$string['statusactive'] = 'Active';
$string['statussuspended'] = 'Suspended';

// Members.
$string['members'] = 'Sellers';
$string['memberowner'] = 'Owner';
$string['memberseller'] = 'Seller';

// Payment gate.
$string['linkednotenabled'] = 'The Mercado Pago account is linked but the gateway is switched off, so nothing can be sold yet. Open the payment settings and enable it.';
$string['nopaymentaccount'] = 'This company has no payment method configured, so it can only publish free courses.';

// Course policy.
$string['hostingtype'] = 'Video hosting';
$string['hostingexternal'] = 'Outside the platform';
$string['hostingplatform'] = 'On the platform';
$string['commissionpct'] = 'Platform commission (%)';

// Errors.
$string['errorshortnametaken'] = 'This short name is already in use.';
$string['errorhostnametaken'] = 'This domain is already linked to another company.';
$string['errorsellerrolemissing'] = 'The seller role is missing. Reinstall the Marketplace plugin.';
$string['errorcannotsell'] = 'This company cannot sell yet: configure a payment method first.';
$string['errorplatformhostingunavailable'] = 'Hosting video on the platform is not available yet.';

// Storefront.
$string['nooffers'] = 'This company has no published offers yet.';
$string['offerunlocks'] = 'This is the offer that unlocks the content you were viewing.';
$string['offerincludes'] = 'Includes {$a} course(s)';
$string['accesslifetime'] = 'Lifetime access';
$string['accessdays'] = 'Access for {$a} days';
$string['accessrecurring'] = 'Subscription, renewed every {$a} days';
$string['buynow'] = 'Buy now';
$string['getfree'] = 'Get free access';
$string['alreadyowned'] = 'You already have access to this offer.';
$string['unavailable'] = 'Not available for purchase yet.';
$string['accessgranted'] = 'Access granted. Enjoy your course!';

// Company dashboard.
$string['companypanel'] = 'Marketplace: company panel';
$string['paymentsection'] = 'Payment method';
$string['offerssection'] = 'Offers';
$string['configurepayment'] = 'Configure Mercado Pago';
$string['cansellyes'] = 'Ready to sell. Active gateway: {$a}';
$string['errornoaccount'] = 'This company has no payment account. Reinstall or recreate the company.';
$string['paymentcurrency'] = 'Payout currency: {$a}';
$string['paymentcurrencyunknown'] = 'Payout currency unknown. Link the Mercado Pago account again to detect it.';
$string['errorcurrencymismatch'] = 'This company receives in {$a->expected}, so it cannot sell an offer priced in {$a->given}. To sell in {$a->given}, link a Mercado Pago account from that country.';
$string['viewstorefront'] = 'View storefront';
$string['managecourses'] = 'Manage courses';
$string['offername'] = 'Offer';
$string['offertype'] = 'Type';
$string['typesingle'] = 'Single course';
$string['typebundle'] = 'Bundle';
$string['typecatalog'] = 'Whole catalogue';
$string['statusdraft'] = 'Draft';
$string['statuspublished'] = 'Published';
$string['statusarchived'] = 'Archived';
$string['free'] = 'Free';
$string['nocompany'] = 'You do not belong to any company.';

// Admin: companies.
$string['createcompany'] = 'Create company';
$string['createcompanyintro'] = 'Creating a company provisions a course category, assigns the seller role to the owner in that category and creates a payment account. Close the partnership first — this screen only carries it out.';
$string['companycreated'] = 'Company {$a} created. Add the other sellers below.';
$string['companyupdated'] = 'Company {$a} updated.';
$string['editcompanyintro'] = 'The short name cannot be changed: it is the category ID number and appears in storefront and dashboard links the seller may already have shared. The owner is changed on the sellers screen, where you can promote someone else first.';
$string['companyowner'] = 'Owner';
$string['companyowner_help'] = 'The person who manages the company and links its Mercado Pago account. Must already have an account on the platform.';
$string['nocompanies'] = 'No companies yet.';
$string['cansell'] = 'Selling';
$string['cannotsell'] = 'Free courses only';
$string['managemembers'] = 'Sellers';
$string['defaultthemename'] = 'Site default theme';

// Admin: members.
$string['membersof'] = 'Sellers of {$a}';
$string['addmember'] = 'Add seller';
$string['addmember_help'] = 'Links an existing platform user to this company and grants them the seller role in the company category. The person must already have an account.';
$string['memberadded'] = 'Seller added.';
$string['memberremoved'] = 'Seller removed.';
$string['memberrolechanged'] = 'Role changed.';
$string['makeowner'] = 'Make owner';
$string['makeseller'] = 'Make seller';
$string['nomembers'] = 'This company has no sellers.';
$string['erroralreadymember'] = 'This person is already a seller of this company.';
$string['errorcannotremoveowner'] = 'The owner cannot be removed. Make someone else the owner first — a company without an owner has nobody responsible for its payment account.';

// Financial report.
$string['reportsection'] = 'Sales';
$string['reportall'] = 'All time';
$string['reportdays'] = 'Last {$a} days';
$string['reportnosales'] = 'No approved sales in this period.';
$string['reportsales'] = 'Approved sales';
$string['reportgross'] = 'Gross';
$string['reportcommission'] = 'Platform commission';
$string['reportentries'] = 'Sales';
$string['reportmppayment'] = 'Mercado Pago payment';
$string['reportviewtransactions'] = 'Transactions';
$string['reportviewcourses'] = 'Courses sold';
$string['reportviewsubscriptions'] = 'Subscriptions';
$string['reportnocourses'] = 'No course has been sold yet.';
$string['reportsaleswith'] = 'Sales including it';
$string['reportcoursesnotice'] = 'A bundle counts in full for every course it unlocks — nobody buys a third of a bundle. So this column adds up to more than your total revenue, and is meant for comparing courses against each other, not for summing.';
$string['reportnosubs'] = 'No subscription offers, or nobody has subscribed yet.';
$string['reportsubsnotice'] = 'There is no billing schedule to show: Mercado Pago has no recurring payments with split, so each renewal is a separate purchase that extends access. This screen shows how many times each learner has paid and how long their access still runs.';
$string['reportpayments'] = 'Payments';
$string['reportlastpayment'] = 'Last payment';
$string['reportaccessuntil'] = 'Access until';
$string['reportsubactive'] = 'Active';
$string['reportsubduesoon'] = 'Ends in {$a} d';
$string['reportsubexpired'] = 'Expired';
$string['reportsubcancelled'] = 'Cancelled';
$string['reportnetnotice'] = 'The Mercado Pago fee is not shown here because it is not reported back to us: it varies by payment method and payout term, and is deducted on their side before the platform commission. Your net amount is the one in your Mercado Pago statement.';

// Offer CRUD.
$string['offercreate'] = 'New offer';
$string['offeredit'] = 'Offer';
$string['offersaved'] = 'Offer saved.';
$string['offertype_help'] = 'Single course sells one course. Bundle sells a chosen set — this is how you build tiers such as Basic, Standard and Complete for the same company. Whole catalogue follows the company category, so new courses join automatically.';
$string['offercourses'] = 'Courses released';
$string['offercourses_help'] = 'Which courses this offer unlocks. Not needed for Whole catalogue, which follows the company category.';
$string['offerprice'] = 'Price';
$string['offerprice_help'] = 'Zero makes the offer free. Free offers skip Mercado Pago entirely.';
$string['offeraccess'] = 'Access and billing';
$string['offeraccessmode'] = 'Access model';
$string['offeraccessmode_help'] = 'Lifetime never expires. Fixed period grants a number of days per purchase. Subscription grants a period and expects renewal.';
$string['offeraccessdays'] = 'Days of access per payment';
$string['offeraccessdays_help'] = 'How long each payment unlocks. In a subscription this can exceed the billing interval to give a grace period: billing every 30 days while granting 35 lets a late payment through without cutting the learner off.';
$string['offerbillingdays'] = 'Billing interval (days)';
$string['offerbillingdays_help'] = 'How often the learner is expected to pay again. Used for the expiry reminder.';
$string['offermaxcycles'] = 'Maximum payments';
$string['offermaxcycles_help'] = 'How many times this subscription may be charged in total. Zero means no limit. Use 12 for a monthly plan that runs for a year, or 3 for a yearly plan that runs for three years.';
$string['offerrecurringwarning'] = 'Mercado Pago has no recurring charge with split payments, so nothing is debited automatically. The learner receives a reminder before expiry with a link to pay again.';
$string['offerpublication'] = 'Publication';
$string['offerstatus_help'] = 'Only published offers appear in the storefront. Archiving does not revoke access already bought.';
$string['offersortorder'] = 'Display order';
$string['modelifetime'] = 'Lifetime';
$string['modedays'] = 'Fixed period';
$string['moderecurring'] = 'Subscription';
$string['accessrecurringopen'] = 'Subscription: {$a->billing} days per payment, no end date';
$string['accessrecurringlimited'] = 'Subscription: {$a->billing} days per payment, up to {$a->cycles} payments';
$string['accessuntil'] = 'Access until {$a}';
$string['erroraccessdays'] = 'Enter at least one day of access.';
$string['errorbillingdays'] = 'Enter the billing interval in days.';
$string['errormaxcycles'] = 'Use zero for no limit, or a positive number.';
$string['errorrecurringfree'] = 'A subscription needs a price. A free one would expire with no way to renew.';
$string['errornocourses'] = 'Choose at least one course, or use the Whole catalogue type.';
$string['errorsinglemanycourses'] = 'A single course offer releases one course. Use Bundle for more than one.';

// Expiry reminder.
$string['tasknotifyexpiring'] = 'Notify learners of expiring access';
$string['renewnow'] = 'Renew now';
$string['renewnotice'] = 'Your access ends on {$a}. Renew to keep it.';
$string['messageprovider:expiring'] = 'Access about to expire';
$string['expiringsubject'] = 'Your access to {$a->offer} ends in {$a->days} day(s)';
$string['expiringbody'] = 'Hello,

Your access to {$a->offer}, from {$a->company}, ends on {$a->date}.

There is no automatic charge — to keep your access, pay again here:
{$a->url}

If you would rather stop, do nothing and the access simply ends.';
$string['expiringbodyhtml'] = '<p>Hello,</p><p>Your access to <strong>{$a->offer}</strong>, from {$a->company}, ends on <strong>{$a->date}</strong>.</p><p>There is no automatic charge — to keep your access, <a href="{$a->url}">pay again here</a>.</p><p>If you would rather stop, do nothing and the access simply ends.</p>';

$string['privacy:metadata'] = 'The Marketplace plugin stores companies, their sellers and payment credentials.';
