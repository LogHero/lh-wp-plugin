#!/usr/bin/env bash
vagrant ssh -c 'cd /var/www/html/wp-content/plugins/loghero/sdk && php vendor/phpunit/phpunit/phpunit test'