#!/usr/bin/env bash
#DESCRIPTION: disable csrf

php -r "require_once __DIR__(sic!).'/_dev-ops/common/ConfigHelper.php'; ConfigHelper::disableCsrfProtection();"