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

include '../../../inc/includes.php';

use GlpiPlugin\Customcards\CustomcardsCard;

Session::checkRight('config', UPDATE);
$card   = new CustomcardsCard();
$list_url = Plugin::getWebDir('customcards') . '/front/card.php';

// erlaubte Itemtypes, kommen aus dem Add-Dropdown
$allowed_itemtypes = ['Computer', 'Ticket'];

// -----------------------------------------------------
// Löschen
// -----------------------------------------------------
if (isset($_REQUEST['action'], $_REQUEST['id']) && $_REQUEST['action'] === 'delete') {
    $id = (int) $_REQUEST['id'];

    if ($card->getFromDB($id)) {
        $card->delete(['id' => $id]);
    }
    Html::redirect($list_url);
    exit;
}

// -----------------------------------------------------
// Modus bestimmen: neu / edit / clone
// -----------------------------------------------------
$card_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
$is_clone  = !empty($_REQUEST['clone']);

// Default-Werte
$itemtype      = $_REQUEST['itemtype']   ?? 'Computer';
$label         = $_REQUEST['label']      ?? '';
$icon         = $_REQUEST['icon']      ?? '';
$widgettype    = $_REQUEST['widgettype'] ?? 'bigNumber';
$group    = $_REQUEST['group'] ?? 'Custom Cards';
$criteria      = [];
$metacriteria  = [];

// vorhandene Card laden (für Edit / Clone)
if ($card_id > 0 && $card->getFromDB($card_id)) {

    // Werte aus DB übernehmen
    $itemtype   = $card->fields['itemtype'];
    $label      = $card->fields['label'];
    $icon      = $card->fields['icon'];
    $widgettype = $card->fields['widgettype'];
    $group = $card->fields['group'];

    // Kriterien aus JSON-Feldern dekodieren
    if (!empty($card->fields['criteria_json'])) {
        $decoded = json_decode($card->fields['criteria_json'], true);
        if (is_array($decoded)) {
            $criteria = $decoded;
        }
    }

    if (!empty($card->fields['metacriteria_json'])) {
        $decoded = json_decode($card->fields['metacriteria_json'], true);
        if (is_array($decoded)) {
            $metacriteria = $decoded;
        }
    }

    // Daten in die Session schreiben, damit der QueryBuilder sie anzeigt
    $search_params = [];
    if (!empty($criteria)) {
        $search_params['criteria'] = $criteria;
    }
    if (!empty($metacriteria)) {
        $search_params['metacriteria'] = $metacriteria;
    }

    if (!empty($search_params)) {
        Search::manageParams($itemtype, $search_params, true);
    }

    // beim Klonen neue ID erzwingen
    if ($is_clone) {
        $card_id = 0;
        $label = $label . ' (Copy)';
    }

} else {
    // Itemtype absichern, falls aus Add-Dropdown
    if (!in_array($itemtype, $allowed_itemtypes, true)) {
        $itemtype = 'Computer';
    }
}

// -----------------------------------------------------
// Formular-Submit verarbeiten (Speichern)
// -----------------------------------------------------
if (!empty($_REQUEST['criteria']) || !empty($_REQUEST['metacriteria'])) {

    // aktuelle Suchparameter von GLPI normalisieren lassen
    // (nutzt intern $_REQUEST und setzt auch link / field-IDs)
    // $search_params = Search::manageParams($itemtype);

    $criteria     = $_REQUEST['criteria']     ?? [];
    $metacriteria = $_REQUEST['metacriteria'] ?? [];

    // Basisfelder aus Request lesen
    $label      = $_REQUEST['label']      ?? '';
    $icon      = $_REQUEST['icon']      ?? '';
    $widgettype = $_REQUEST['widgettype'] ?? 'bigNumber';
    $itemtype   = $_REQUEST['itemtype']   ?? $itemtype;
    $group    = $_REQUEST['group'] ?? 'Custom Cards';

    $input = [
        'entities_id'        => ($card_id > 0 && !$is_clone)
                                ? $card->fields['entities_id']
                                : Session::getActiveEntity(),
        'label'              => $label,
        'widgettype'         => $widgettype,
        'itemtype'           => $itemtype,
        'icon'               => $icon,
        'group'              => $group,
        'criteria_json'      => json_encode($criteria),
        'metacriteria_json'  => json_encode($metacriteria),
    ];

    if ($card_id > 0 && !$is_clone) {
        $input['id'] = $card_id;
        $card->update($input);
    } else {
        $card->add($input);
    }

    Html::redirect($list_url);
    exit;
}

// -----------------------------------------------------
// Erstanzeige: Formular mit allgemeinen Infos + QueryBuilder
// (inkl. Vorbelegung für Edit/Klon)
// -----------------------------------------------------

// Button-Konfiguration für das Search-Form
$actionname  = 'save_card';
$actionvalue = __('Speichern', 'customcards');

Html::header(
    __('Custom Cards', 'customcards'),
    $_SERVER['PHP_SELF'],
    'config',
    'plugins',
    'customcards',
);

// passender Titel
if ($card_id > 0) {
    echo '<h2>' . __('Edit card', 'customcards') . '</h2>';
} elseif ($is_clone) {
    echo '<h2>' . __('Clone card', 'customcards') . '</h2>';
} else {
    echo '<h2>' . __('New card', 'customcards') . '</h2>';
}

echo '<p>' . __('Enter the basic information and then select the search criteria for the card.', 'customcards') . '</p>';
// echo '<pre>';
// echo "CRITERIA:\n";
// var_dump($criteria);
// echo '</pre>';

// Parameter für das Suchformular
$params = [
    'target'             => '',
    'actionname'         => $actionname,
    'actionvalue'        => $actionvalue,
    'showbookmark'       => false,
    'showmassiveactions' => false,
    'showreset'          => false,
    'criteria'           => $criteria,
    'metacriteria'       => $metacriteria,
];

// QueryBuilder-Formular rendern
Search::showGenericSearch($itemtype, $params);

// HTML für die allgemeinen Felder vorbereiten
$label_esc     = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$icon_esc     = htmlspecialchars($icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$group_esc     = htmlspecialchars($group, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$bigNumber_sel = ($widgettype === 'bigNumber') ? ' selected' : '';

$general_html = '
<input type="hidden" name="id" value="' . (int) $card_id . '">
' . ($is_clone ? '<input type="hidden" name="clone" value="1">' : '') . '
<div class="card-body">
   <div class="mb-3">
      <label class="form-label required" for="cc_label">' . __('Label') . '</label>
      <input type="text" class="form-control" id="cc_label" name="label" value="' . $label_esc . '" required>
   </div>
   <div class="mb-3">
      <label class="form-label" for="cc_icon">' . __('Icon') . '</label>
      <input type="text" class="form-control" id="cc_icon" name="icon" value="' . $icon_esc . '" placeholder="' . __("Font Awesome Icon, e.g.: fa fa-user") . '">
   </div>
   <div class="mb-3">
      <label class="form-label" for="cc_group">' . __('Group') . '</label>
      <input type="text" class="form-control" id="cc_group" name="group" value="' . $group_esc . '">
   </div>
   <div class="mb-3">
      <label class="form-label" for="cc_widgettype">' . __('Widget') . '</label>
      <select class="form-select" id="cc_widgettype" name="widgettype">
         <option value="bigNumber"' . $bigNumber_sel . '>' . __('Big number') . '</option>
      </select>
   </div>
</div>
';


$general_html_js = json_encode($general_html);

// JS: allgemeine Felder über dem Such-Block einfügen und Button submitten lassen
echo Html::scriptBlock("
document.addEventListener('DOMContentLoaded', function() {
    var form = document.querySelector('form[data-glpi-search-form]');
    if (!form) {
        return;
    }

    // Karte mit allgemeinen Feldern vor die bestehende Such-Card einfügen
    var searchCard = form.querySelector('.search-form.card');
    if (searchCard) {
        var wrapper = document.createElement('div');
        wrapper.className = 'card card-sm mb-3';
        wrapper.innerHTML = {$general_html_js};
        form.insertBefore(wrapper, searchCard);
    }

    // Speichern-Button (type=button) soll Formular abschicken - aber nur wenn gültig
    form.addEventListener('click', function(e) {
        var btn = e.target.closest('button[name=\"{$actionname}\"]');
        if (!btn) {
            return;
        }

        e.preventDefault();

        // HTML5-Validierung (required, pattern, minlength, ...)
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            if (typeof form.reportValidity === 'function') {
                form.reportValidity();
            }
            return;
        }

        form.submit();
    });
});
");

Html::footer();
