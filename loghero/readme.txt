=== Log-Hero.com Plugin ===
Contributors: loghero
Donate link: https://log-hero.com
License: MIT
License URI: https://opensource.org/licenses/MIT
Tags: SEO, Log Files, Crawlers, Log Analysis, Search Engine Bots, Logs
Requires at least: 3.6
Tested up to: 4.9.6
Stable tag: 0.2.5
Requires PHP: 5.2.4

Analyze how search engines and other bots crawl and understand your web page. The official PHP Wordpress plugin for [log-hero.com](https://log-hero.com).

== Description ==

= Log-Hero.com =

Do you want to understand how bots crawl your website? Log Hero helps you track and visualize bots, spiders, and crawlers by making them visible in real time in your Google Analytics account so that you can analyze them like human visitors.

His additional dimensions and metrics help you analyze the bot data even better. Using Log Hero, you won't have to fetch your server logs and analyze them in a lousy UI. From now on, your logs are available in real-time in Google Analytics.

= Data that you can monitor in Google Analytics =

* Page path of your crawled sites (see how often a bot crawls sites).
* User agent (how the bot identifies himself).
* IP addresses (only for bots, so 100% GDPR and privacy compliant).
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
2. Entering the API key in the Wordpress backend.
3. Seeing real-time data of the Log Hero plugin in Google Analytics.
4. Status code report in Google Analytics.
5. Bot report in Google Analytics by page.
