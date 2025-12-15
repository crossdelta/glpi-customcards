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

namespace GlpiPlugin\Customcards;

use function Safe\json_decode;
use function Safe\json_encode;

if (!defined('GLPI_ROOT')) {
    return;
}

class CustomcardsCard extends \CommonDBTM
{
    public static $rightname = 'plugin_customcards';

    public static function getTypeName($nb = 0)
    {
        return __('Custom Card', 'customcards');
    }

    public function getTabNameForItem(\CommonGLPI $item, $withtemplate = 0)
    {
        return '';
    }

    public static function getTable($classname = null)
    {
        return 'glpi_plugin_customcards_cards';
    }

    public static function getNameField()
    {
        return 'label';
    }

    public function cleanDBonPurge()
    {
        global $DB;

        $id = (int) (is_integer($this->fields['id']) && $this->fields['id'] ? $this->fields['id'] : 0);
        if ($id <= 0) {
            return;
        }

        $cardKey = 'plugin_customcards_card_' . $id;

        // Exakt löschen (besser als LIKE, wenn das Format fix ist)
        $DB->delete(
            'glpi_dashboards_items',
            ['card_id' => $cardKey],
        );

        parent::cleanDBonPurge();
    }

    public function post_getFromDB()
    {
        if (isset($this->fields['criteria']) && is_string($this->fields['criteria']) && $this->fields['criteria'] !== '') {
            $this->fields['criteria'] = json_decode($this->fields['criteria'], true);
        }
        if (isset($this->fields['metacriteria']) && is_string($this->fields['metacriteria']) && $this->fields['metacriteria'] !== '') {
            $this->fields['metacriteria'] = json_decode($this->fields['metacriteria'], true);
        }
    }

    public function prepareInputForAdd($input)
    {
        if (array_key_exists('criteria', $input)) {
            $input['criteria'] = json_encode($input['criteria'], JSON_UNESCAPED_UNICODE);
        }

        if (array_key_exists('metacriteria', $input)) {
            $input['metacriteria'] = json_encode($input['metacriteria'], JSON_UNESCAPED_UNICODE);
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (array_key_exists('criteria', $input)) {
            $input['criteria'] = json_encode($input['criteria'], JSON_UNESCAPED_UNICODE);
        }

        if (array_key_exists('metacriteria', $input)) {
            $input['metacriteria'] = json_encode($input['metacriteria'], JSON_UNESCAPED_UNICODE);
        }

        return $input;
    }
}
