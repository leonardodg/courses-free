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

$string['privacy:metadata'] = 'The Marketplace plugin stores companies, their sellers and payment credentials.';
