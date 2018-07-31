#!/usr/bin/env bash
vagrant ssh -c 'cd /var/www/html/wp-content/plugins/loghero && ./bin/install-wp-tests.sh wordpress_test root loghero'