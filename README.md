# GLPI Plugin – Custom Cards

Custom Cards is a GLPI plugin that allows you to define and display custom dashboard cards.  
The goal of the plugin is to make GLPI dashboards more flexible by enabling dynamic cards that can be configured and loaded from the database.

## Features

- Adds custom cards to GLPI dashboards
- Cards can be dynamically loaded from the database
- Uses GLPI’s native dashboard and search mechanisms

## Supported Widget Types

Currently, the plugin supports **only one widget type**:

- **BigNumber**  
  Displays a single numeric value, optionally with:
  - a label
  - an icon

Additional widget types are planned, but not implemented yet.

## How It Works

- Cards are registered via the `getCards()` method
- Each card defines:
  - a unique card ID
  - a widget type (`bigNumber`)
  - a label
  - an optional provider method
  - an optional group
- For dynamic cards, configuration (criteria, item type, widget type, etc.) is stored in the database
- Provider methods resolve the card ID from the request and generate the data shown in the widget

## Status

This plugin is currently under active development.  
The focus at the moment is on stabilizing and extending the **BigNumber** widget type before adding new widget types.