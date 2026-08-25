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
 * Strings do availability_marketplace.
 *
 * @package    availability_marketplace
 * @copyright  2026 Leonardo Della Giustina
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Restrição por compra';
$string['title'] = 'Compra';
$string['description'] = 'Só acessa o conteúdo quem tem direito de acesso vigente.';
$string['label_offer'] = 'Oferta exigida';
$string['anyoffer'] = 'Qualquer oferta que inclua este curso';
$string['requires_access'] = 'Você comprou o acesso a este curso';
$string['requires_noaccess'] = 'Você <b>não</b> comprou o acesso a este curso';
$string['requires_offer'] = 'Você comprou <b>{$a}</b>';
$string['requires_notoffer'] = 'Você <b>não</b> comprou <b>{$a}</b>';
$string['unknownoffer'] = '(oferta removida)';
$string['privacy:metadata'] = 'O plugin Restrição por compra não armazena dados pessoais; ele lê os direitos mantidos pelo local_marketplace.';
