<?php

/**
 * -------------------------------------------------------------------------
 * CustomCards plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * SPDX-License-Identifier: GPL-3.0-only
 * Copyright (C) 2024-2025  crossdelta
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2025 by the crossdelta.
 * @license   GPLv3 https://opensource.org/license/gpl-3-0
 * @link      https://github.com/crossdelta/glpi-customcards
 * -------------------------------------------------------------------------
 */

use GlpiPlugin\Customcards\Card;
use GlpiPlugin\Customcards\CustomcardsCard;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

include '../../../inc/includes.php';

Session::checkRight('config', READ);

Html::header(
    __('Custom Cards', 'customcards'),
    $_SERVER['PHP_SELF'],
    'config',
    'plugins',
    'customcards',
);

// Basis-URLs
$plugindir = Plugin::getWebDir('customcards');
$form_url  = $plugindir . '/front/card.form.php';

// Cards aus der DB holen
$card_obj = new CustomcardsCard();

// nach Label sortieren (oder id, wenn gewünscht)
$cards = $card_obj->find([], ['ORDER' => 'label']);

echo '<div class="d-flex justify-content-between align-items-center mb-3">';
echo '<h2 class="mb-0">' . __('Custom Cards', 'customcards') . '</h2>';

// Add-Dropdown (GET, nur Anzeige -> kein CSRF nötig)
echo '<div class="btn-group">';
echo '  <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
echo '    <i class="ti ti-plus"></i> ' . __('Add', 'customcards');
echo '  </button>';
echo '  <div class="dropdown-menu dropdown-menu-end">';
echo '    <a class="dropdown-item" href="' . $form_url . '?itemtype=Computer">' . __('Computer') . '</a>';
echo '    <a class="dropdown-item" href="' . $form_url . '?itemtype=Ticket">' . __('Tickets') . '</a>';
echo '  </div>';
echo '</div>'; // btn-group

echo '</div>'; // header row

// Tabelle mit Cards
echo '<div class="card card-sm">';
echo '<div class="card-body p-0">';
echo '<div class="table-responsive">';
echo '<table class="table card-table table-hover mb-0">';
echo '<thead>';
echo '<tr>';
echo '<th>' . __('Name') . '</th>';
echo '<th>' . __('Entity') . '</th>';
echo '<th>' . __('Associated item type') . '</th>';
echo '<th>' . __('Type') . '</th>';
echo '<th>' . __('Criteria', 'customcards') . '</th>';
echo '<th class="text-end">' . __('Actions') . '</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if (!empty($cards)) {
    // Ein CSRF-Token für alle Aktionen dieser Seite
    $token = Session::getNewCSRFToken();

    foreach ($cards as $id => $data) {

        $name        = $data['label']      ?? ('#' . $id);
        $itemtype    = $data['itemtype']   ?? '';
        $widget_type = $data['widgettype'] ?? '';

        // Entitätsname ermitteln
        $entity_name = '';
        if (isset($data['entities_id'])) {
            $entity_name = Dropdown::getDropdownName('glpi_entities', $data['entities_id']);
        }

        // Kriterien aus criteria_json aufbereiten (nur Kurzansicht)
        $criteria_str = '';
        if (!empty($data['criteria_json'])) {
            $criteria = json_decode($data['criteria_json'], true);

            if (is_array($criteria)) {
                $parts = [];
                foreach ($criteria as $c) {
                    $field      = $c['field']      ?? '?';
                    $searchtype = $c['searchtype'] ?? '';
                    $value      = $c['value']      ?? '';

                    $parts[] = sprintf('%s %s %s', $field, $searchtype, $value);
                }
                $criteria_str = implode('; ', $parts);
            } else {
                // Fallback: Rohdaten gekürzt
                $criteria_str = mb_substr($data['criteria_json'], 0, 120);
                if (mb_strlen($data['criteria_json']) > 120) {
                    $criteria_str .= '…';
                }
            }
        }

        echo '<tr>';
        echo '<td>' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($entity_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($itemtype, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($widget_type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($criteria_str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';

        echo '<td class="text-end">';

        // Ein Formular pro Zeile, alle Actions über POST
        echo '<form method="post" action="' . $form_url . '" class="d-inline">';

        echo Html::hidden('id', ['value' => (int) $id]);
        echo Html::hidden('_glpi_csrf_token', ['value' => $token]);

        // Edit-Button (Stift)
        echo '<button type="submit" name="action" value="edit" '
           . 'class="btn btn-sm btn-secondary me-1" '
           . 'title="' . __s('Edit') . '">'
           . '<i class="ti ti-pencil"></i>'
           . '</button>';

        // Clone-Button (Doppelsymbol)
        echo '<button type="submit" name="action" value="clone" '
           . 'class="btn btn-sm btn-info me-1" '
           . 'title="' . __s('Clone') . '">'
           . '<i class="ti ti-copy"></i>'
           . '</button>';

        // Delete-Button (Papierkorb) mit Confirm-Dialog
        echo '<button type="submit" name="action" value="delete" '
           . 'class="btn btn-sm btn-danger" '
           . 'title="' . __s('Delete') . '" '
           . 'onclick="return confirm(\'' . __s('Delete this cards?') . '\');">'
           . '<i class="ti ti-trash"></i>'
           . '</button>';

        echo '</form>';

        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr>';
    echo '<td colspan="6" class="text-muted text-center p-3">';
    echo __('No cards defined yet', 'customcards');
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>'; // table-responsive
echo '</div>'; // card-body
echo '</div>'; // card

Html::footer();
