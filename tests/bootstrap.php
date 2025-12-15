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
 * @license   MIT https://opensource.org/license/gpl-3-0
 * @license   MIT https://opensource.org/licenses/mit-license.php
 * @link      https://github.com/crossdelta/glpi-customcards
 * @link      https://github.com/pluginsGLPI/customcards
 * -------------------------------------------------------------------------
 */

require __DIR__ . '/../../../tests/bootstrap.php';

if (!Plugin::isPluginActive("customcards")) {
    throw new RuntimeException("Plugin customcards is not active in the test database");
}
