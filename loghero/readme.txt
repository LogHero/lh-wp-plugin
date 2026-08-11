=== Log-Hero.com Plugin ===
Contributors: loghero
Donate link: https://log-hero.com
License: MIT
License URI: https://opensource.org/licenses/MIT
Tags: seo, log files, crawlers, log analysis, bots
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 0.2.5
Requires PHP: 7.4

Analyze how search engines and other bots crawl your site. The official WordPress plugin for log-hero.com.

== Description ==

= Log-Hero.com =

Do you want to understand how bots crawl your website? Log Hero helps you track and visualize bots, spiders, and crawlers by making them visible in real time in your Google Analytics account so that you can analyze them like human visitors.

His additional dimensions and metrics help you analyze the bot data even better. Using Log Hero, you won't have to fetch your server logs and analyze them in a lousy UI. From now on, your logs are available in real-time in Google Analytics.

= Data that you can monitor in Google Analytics =

* Page path of your crawled sites (see how often a bot crawls sites).
* User agent (how the bot identifies himself).
* IP addresses (used to verify that a bot is who it claims to be; see "External services" below for exactly what this plugin transmits).
* Sessions and users (monitor bots and their flow as if they were human visitors).
* HTTP status codes (monitor status codes of individual sites, find broken links, redirect chains, server failures).
* Download times (how long the bot needs to download your content).
* **[Premium]** Device category (which device category the bot used. I.e., see whether Google crawled you with the mobile or desktop bot).
* **[Premium]** Bot name (Using user agent and the IP address, we check whether the bot is whom he claims he is).
* **[Premium]** Request method (see what request method (PUT or GET) bots used on your site).
* **[Premium]** Referral / channel (analyze through which links bots came to your site to crawl you).
* **[Premium]** Location (see where the visitor or bot is from).
* **[Premium]** IsBot (see at a first glance whether it's a bot or a human).
* **[Premium]** Spam detection (check how many spam bots visit your site and what type they are).
* **[Premium]** Attack detection (get a warning if someone tries to attack or hack your site).

> Note: some of those features are premium features. They are only available in paid plans. You can [get them here](https://log-hero.com)!

= Analysis in Google Analytics =

All data is safely stored in your Google Analytics account and available even if you won't continue using Log Hero.

* Easy filtering of your data in Google Analytics (works with regular expressions, too).
* Advanced and fast segment analysis.
* Real-time monitoring of your data.
* Easy exporting of your data as Excel, CSV, or Google Sheets.
* Analysis of large data sets through Google Analytics' API.
* Easy setup of dashboards and alerts.

= Monitoring critical technical issues =

Log Hero helps you to identify many issues concerning technical optimization of your site and can derive quick action items to resolve these.

* Which bots crawl my site and how often?
* Which orphan pages has my site that are never crawled?
* Which status codes does my site return to search engines?
* Are my robots.txt and sitemap.xml crawled by search engine bots?
* Do search engine bots crawl my page with a desktop or mobile device?
* How long does the bot need to download the resources of my site?
* How many users does my normal Google Analytics system not track because they have disabled the tracker or Javascript?
* and much more. Visit [log-hero.com](https://log-hero.com)! for more information

== External services ==

This plugin relies on Log Hero, an external service operated by Cross Platform Solutions GmbH. The plugin has no function without it: its only purpose is to forward request data to that service, which then makes bot and crawler traffic visible in your analytics account.

= What is sent, and when =

The plugin registers a handler on the WordPress `shutdown` action. For **every** request served by your site -- from bots and from human visitors alike -- it collects the following data about that single request and transmits it to the Log Hero API at `https://api.loghero.io/logs/`:

* The host name of your site.
* The protocol used (http or https).
* The requested path, including its query string.
* The HTTP request method.
* The HTTP status code your site returned.
* The User-Agent header sent by the client.
* The Referer header sent by the client, if present.
* The IP address of the client.
* The timestamp of the request.
* The time your site needed to generate the response, in milliseconds.

Filtering bots from human visitors happens on the Log Hero side, not in the plugin. The IP address of every visitor is therefore transmitted, not only that of bots. Depending on your jurisdiction, IP addresses may be personal data; if you are subject to the GDPR, transmitting them to a processor requires a legal basis and, as a rule, a data processing agreement with the operator. Please assess this for your own site before activating the plugin.

Requests are buffered locally and sent in batches. No data is transmitted until you have entered a valid API key under Settings > LogHero.

= Terms and privacy =

* Service: [log-hero.com](https://log-hero.com)
* Privacy policy: [https://log-hero.com/privacy-policy](https://log-hero.com/privacy-policy)

== Bug reports ==

Bug reports for Log Hero are [appreciated on GitHub](https://github.com/LogHero/lh-wp-plugin/issues). Please note that GitHub is not a support forum, and issues that aren't adequately qualified as bugs are closed.

== Further Reading ==

For more information about logs or this plugin, visit the [Log-Hero](https://log-hero.com) homepage.


== Installation ==

= From within WordPress =

1. Visit 'Plugins > Add New'.
2. Search for 'Log Hero'.
3. Activate Log Hero in your plugins page.
4. Follow the steps on "after activation" below.

= Manually =

1. Upload the `log-hero` folder to the `/wp-content/plugins/` directory.
2. Activate the Log Hero plugin through the 'Plugins' menu in WordPress.
3. Follow the steps on "after activation" below.

= After activation =

1. Click on Settings > LogHero.
2. Now you have to enter the API key you received in the sign-up flow on [log-hero.com](https://log-hero.com). If you don't have an API key, get one for free on [https://log-hero.com](https://log-hero.com)!
3. You're done! You should see the data coming into your Log Hero Google Analytics account.

If you have any problems, write our support team.

== Frequently Asked Questions ==

You'll find answers to many of your questions on [log-hero.com](https://log-hero.com) or write our support team.

== Changelog ==

= [0.3.0] =
= Changed =
* Declared compatibility with current WordPress and PHP versions. The plugin now requires WordPress 6.0 or newer and PHP 7.4 or newer.
* Documented the Log Hero service as an external service, including the exact data transmitted for each request and a link to the privacy policy.
* Removed an inaccurate claim from the plugin description: IP addresses are transmitted for all requests, not only for bots.

= [0.2.5] =
= Added =
* Support for IPv6

= [0.2.4] =
= Added =
* Limit batch size of buffered log events (split into multiple batches if number of buffered log events exceeds limit).
* Fix custom API settings (did not affect the WordPress plugin).

= [0.2.3] =
= Added =
* Redis log buffer as an alternative to the file log buffer
* Option to disable flush of log events
* Added prefix to plugin options to avoid name collisions

= [0.2.2] =
= Added =
* Admin error message if plugin cannot write to log buffer
* Send protocol version (http or https)
* Evaluation of IP ranges to improve bot detection

= [0.2.1] =
= Added =
* Workaround for sites using Cloudflare
* Admin warnings in case of unexpected errors
* Protection from running out of disk space in case of flush errors
* Option to switch from asynchronous mode to synchronous mode

== Upgrade Notice ==

= [0.3.0] =
Compatibility with current WordPress and PHP versions. Now requires WordPress 6.0 and PHP 7.4 or newer. Adds full documentation of the data transmitted to the Log Hero service.

= [0.2.5] =
Added support for IPv6

= [0.2.4] =
Limit batch size of buffered log events

= [0.2.3] =
Added Redis log buffer as an alternative to the file log buffer, added prefix to plugin options to avoid name collisions.

= [0.2.2] =
Evaluation of IP ranges to improve bot detection, send protocol version (http or https), improved error handling and reporting.

= [0.2.1] =
Added workaround for sites using Cloudflare, improved error handling and reporting.

== Screenshots ==

1. Comparing hits from a Google bot vs. a Bing bot.
2. Entering the API key in the WordPress backend.
3. Seeing real-time data of the Log Hero plugin in Google Analytics.
4. Status code report in Google Analytics.
5. Bot report in Google Analytics by page.
