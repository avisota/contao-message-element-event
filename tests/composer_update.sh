#!/usr/bin/env bash

composer remove avisota/contao-message
composer remove avisota/contao-message-element-headline --no-update
composer update --prefer-dist --no-interaction
composer require avisota/contao-message dev-release/3.2.0  --no-update
composer require avisota/contao-message-element-headline dev-release/3.1.0
