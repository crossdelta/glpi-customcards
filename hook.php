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
 * @license   MIT https://opensource.org/license/gpl-3-0
 * @link      https://github.com/crossdelta/glpi-customcards
 * -------------------------------------------------------------------------
 */

function plugin_customcards_install(): bool
{
    global $DB;

    $default_charset   = \DBConnection::getDefaultCharset();
    $default_collation = \DBConnection::getDefaultCollation();
    $default_key_sign  = \DBConnection::getDefaultPrimaryKeySignOption();

    if (!$DB->tableExists('glpi_plugin_customcards_cards')) {
        $query = "CREATE TABLE `glpi_plugin_customcards_cards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int(10) unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
  `label` varchar(255) NOT NULL,
  `widgettype` varchar(100) NOT NULL,
  `itemtype` varchar(100) NOT NULL,
  `criteria_json` longtext DEFAULT NULL,
  `metacriteria_json` longtext DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `group` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;";
        $DB->doQuery($query);
    }

    return true;
}

function plugin_customcards_uninstall(): bool
{
    global $DB;
    if ($DB->tableExists('glpi_plugin_customcards_cards')) {
        $DB->doQuery(
            "DROP TABLE `glpi_plugin_customcards_cards`",
        );
    }
    $DB->doQuery(
        "DELETE FROM `glpi_dashboards_items` WHERE card_id LIKE 'plugin_customcards_card_%'",
    );
    return true;
}
