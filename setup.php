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
 * @copyright Copyright (C) 2025 by the CustomCards plugin team.
 * @license   GPLv3 https://opensource.org/license/gpl-3-0
 * @link      https://github.com/crossdelta/glpi-customcards
 * -------------------------------------------------------------------------
 */

/** @phpstan-ignore theCodingMachineSafe.function (safe to assume this isn't already defined) */
define('PLUGIN_CUSTOMCARDS_VERSION', '0.7');

// Minimal GLPI version, inclusive
/** @phpstan-ignore theCodingMachineSafe.function (safe to assume this isn't already defined) */
define("PLUGIN_CUSTOMCARDS_MIN_GLPI_VERSION", "11.0.0");

// Maximum GLPI version, exclusive
/** @phpstan-ignore theCodingMachineSafe.function (safe to assume this isn't already defined) */
define("PLUGIN_CUSTOMCARDS_MAX_GLPI_VERSION", "11.0.99");

use Glpi\Plugin\Hooks;
use GlpiPlugin\Customcards\Cards;

require_once __DIR__ . '/src/Cards.php';

/**
 * Init hooks of the plugin.
 * REQUIRED
 */
function plugin_init_customcards(): void
{
    /** @var array<string, array<string, mixed>> $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    if (isset($PLUGIN_HOOKS[Hooks::DASHBOARD_CARDS])) {

        // Plugin::registerClass('PluginCustomcardsCard'); // ???

        // add new cards to the dashboard
        $PLUGIN_HOOKS[Hooks::DASHBOARD_CARDS]['customcards'] = [
            Cards::class,
            'getCards',
        ];
        // Config page
        // Plugin::registerClass(Config::class, ['addtabon' => 'Config']);
    }
    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['customcards'] = 'front/card.php';
    }
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array{
 *      name: string,
 *      version: string,
 *      author: string,
 *      license: string,
 *      homepage: string,
 *      requirements: array{
 *          glpi: array{
 *              min: string,
 *              max: string,
 *          }
 *      }
 * }
 */
function plugin_version_customcards(): array
{
    return [
        'name'         => 'Custom Cards',
        'version'      => PLUGIN_CUSTOMCARDS_VERSION,
        'author'       => 'crossdelta',
        'license'      => 'GPLv3',
        'homepage'     => 'https://www.crossdelta.de/',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_CUSTOMCARDS_MIN_GLPI_VERSION,
                'max' => PLUGIN_CUSTOMCARDS_MAX_GLPI_VERSION,
            ],
        ],
    ];
}

/**
 * Check pre-requisites before install
 * OPTIONAL
 */
function plugin_customcards_check_prerequisites(): bool
{
    return true;
}

/**
 * Check configuration process
 * OPTIONAL
 *
 * @param bool $verbose Whether to display message on failure. Defaults to false.
 */
function plugin_customcards_check_config(bool $verbose = false): bool
{
    // Your configuration check
    return true;

    // Example:
    // if ($verbose) {
    //    echo __('Installed / not configured', 'customcards');
    // }
    // return false;
}
