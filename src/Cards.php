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

declare(strict_types=1);

namespace GlpiPlugin\Customcards;

use CommonDBTM;
use CommonITILObject;
use JsonException;
use GlpiPlugin\Customcards\CustomcardsCard;
use Search;
use Session;
use Ticket;
use Toolbox;

class Cards extends CommonDBTM
{
    /**
     * @param array<string, mixed>|null $cards
     * @return array<string, mixed>
     */
    public static function getCards(?array $cards = null): array
    {
        $cards ??= [];
        $newCards = [];

        // $newCards = [
        //     'plugin_my_assigned_tickets' => [
        //         'widgettype' => ['bigNumber'],
        //         'label'      => __('My assigned tickets', 'customcards'),
        //         'provider'   => __CLASS__ . '::myAssignedTicketsBigNumber',
        //         'group'      => 'Assistance',
        //     ],
        // ];

        // Dynamische Cards aus der DB
        $cardsObj = new CustomcardsCard();
        /** @var array<int|string, array<string, mixed>> $dbCards */
        $dbCards = $cardsObj->find([], ['ORDER' => 'label']);

        foreach ($dbCards as $id => $data) {
            $widgetType = isset($data['widgettype']) && is_string($data['widgettype']) ? trim($data['widgettype']) : '';
            $label      = isset($data['label']) && is_string($data['label']) ? trim($data['label']) : '';

            // Ohne Typ oder Label hat die Card keinen Sinn
            if ($widgetType === '' || $label === '') {
                continue;
            }

            // Eindeutiger Card-Key für GLPI
            $cardKey = 'plugin_customcards_card_' . (int) $id;

            $group = 'Custom Cards';
            if (isset($data['group']) && is_string($data['group']) && trim($data['group']) !== '') {
                $group = __($data['group']);
            } else {
                $group = __('Custom Cards', 'customcards');
            }

            $newCards[$cardKey] = [
                'widgettype' => [$widgetType], // z.B. 'bigNumber'
                'label'      => __($label, 'customcards'),
                'provider'   => __CLASS__ . '::customCardBigNumberProvider',
                'group'      => $group,
            ];
        }

        return array_merge($cards, $newCards);
    }

    /**
     * @param array<string, mixed> $params
     * @return array{number:int, url:string, label:string, icon:string}
     */
    public static function customCardBigNumberProvider(array $params = []): array
    {
        $label = is_string($params['label'] ?? null) ? ' (' . $params['label'] . ')' : '';
        $defaultParams = [
            'number' => 0,
            'url'    => '',
            'label'  => 'error' . $label,
            'icon'   => '',
            'apply_filters' => [], // kommt vom Dashboard
        ];
        $cardId = self::getCardIdFromRequest();
        if ($cardId === null) {
            return $defaultParams;
        }
        $card = new CustomcardsCard();
        if (!$card->getFromDB($cardId)) {
            return $defaultParams;
        }
        $criteria = self::decodeJsonArray(isset($card->fields['criteria_json']) && is_string($card->fields['criteria_json'])
        ? $card->fields['criteria_json']
        : null);
        // 1) Kriterien für die Ticket-Liste definieren
        $sCriteria = [
            'is_deleted'   => 0,
            'as_map'       => 0,
            'browse'       => 0,
            'unpublished'  => 1,
            'criteria'     => $criteria,
            'params'       => [
                'hide_criteria'      => 0,
                'hide_controls'      => 0,
                'showmassiveactions' => 1,
            ],
            'itemtype'     => isset($card->fields['itemtype']) && is_string($card->fields['itemtype']) ? $card->fields['itemtype'] : 'Ticket',
            'start'        => 0,
        ];

        /** @var array<string, mixed> $data */
        $data = self::customcards_getSqlFromCriteria($card->fields);
        $count = isset($data['data']) && is_array($data['data']) && isset($data['data']['totalcount']) && $data['data']['totalcount'] && is_integer($data['data']['totalcount']) ? $data['data']['totalcount'] : 999;

        /** @var array<string, mixed> $mergedParams */
        $mergedParams = array_merge($defaultParams, $params);
        return [
            'number' => $count,
            'url'    => Ticket::getSearchURL(false) . '?' . Toolbox::append_params($sCriteria),
            'label'  => is_string($mergedParams['label']) ? $mergedParams['label'] : '',
            'icon'   => isset($card->fields['icon']) && is_string($card->fields['icon']) ? $card->fields['icon'] : '',
        ];
    }


    /**
     * Gibt die von GLPI generierten Daten für eine Custom Card zurück (u.a. totalcount).
     *
     * @param array<mixed> $card  Zeile/Felder aus glpi_plugin_customcards_cards
     * @return array{data: array{totalcount:int}, sql: array{search:string}}
     */
    public static function customcards_getSqlFromCriteria(array $card): array
    {
        $fallbackResult = [
            'data' => [
                'totalcount' => 999,
            ],
            'sql' => [
                'search' => '',
            ],
        ];
        $criteriaJson     = isset($card['criteria_json']) && is_string($card['criteria_json']) ? $card['criteria_json'] : null;
        $metaCriteriaJson = isset($card['metacriteria_json']) && is_string($card['metacriteria_json']) ? $card['metacriteria_json'] : null;

        $criteria     = self::decodeJsonArray($criteriaJson);
        $metaCriteria = self::decodeJsonArray($metaCriteriaJson);

        // Such-Parameter wie bei Search::showList()
        $params = [
            'start'        => 0,
            'is_deleted'   => 0,
            'criteria'     => $criteria,
            'metacriteria' => $metaCriteria,
        ];

        /** @var class-string<CommonDBTM> $itemtype */
        $itemtype = isset($card['itemtype']) && is_string($card['itemtype']) ? $card['itemtype'] : '';
        if ($itemtype === '') {
            return $fallbackResult;
        }
        if (!is_a($itemtype, CommonDBTM::class, true)) {
            return $fallbackResult;
        }
        /** @var array<string, array<string, mixed>> $datas */
        $datas = Search::getDatas($itemtype, $params);

        if (
            !isset($datas['sql'], $datas['sql']['search'])
            || !is_string($datas['sql']['search'])
            || !isset($datas['data'], $datas['data']['totalcount'])
            || !is_int($datas['data']['totalcount'])
        ) {
            return $fallbackResult;
        }
        $sql = $datas['sql'] ?? null;
        $data = $datas['data'] ?? null;

        if (!is_array($sql) || !is_array($data)) {
            return $fallbackResult;
        }
        $search = $sql['search'] ?? '';
        $totalcount = $data['totalcount'] ?? 999;
        return [
            'data' => [
                'totalcount' => $totalcount,
            ],
            'sql' => [
                'search' => $search,
            ],
        ];
    }

    /**
     * @return int|null
     */
    private static function getCardIdFromRequest(): ?int
    {
        $raw = $_REQUEST['card_id'] ?? null;

        if ($raw === null && isset($_REQUEST['args']) && is_array($_REQUEST['args'])) {
            $raw = $_REQUEST['args']['card_id'] ?? null;
        }

        if (!is_string($raw) && !is_int($raw)) {
            return null;
        }

        $cardId = (string) $raw;

        $prefix = 'plugin_customcards_card_';
        if (str_starts_with($cardId, $prefix)) {
            $idPart = substr($cardId, strlen($prefix));
            return ctype_digit($idPart) ? (int) $idPart : null;
        }

        return ctype_digit($cardId) ? (int) $cardId : null;
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function decodeJsonArray(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
