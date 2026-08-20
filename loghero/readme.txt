=== Log-Hero.com Plugin ===
Contributors: loghero
Donate link: https://log-hero.com
License: MIT
License URI: https://opensource.org/licenses/MIT
Tags: seo, log files, crawlers, log analysis, bots
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 0.3.0
Requires PHP: 7.4

See which search engines, AI crawlers and other bots visit your site, and what they find. The official plugin for log-hero.com.

== Description ==

= Log Hero =

Do you want to know which bots visit your site, and what they find there? Log Hero records every request your server answers and turns it into a clear picture of how search engines, AI crawlers and all the other bots move through your site.

Because the plugin works on the server, it sees what a tracking script never will. Crawlers do not run JavaScript, so they are invisible to ordinary analytics. Log Hero catches every one of them, including the requests to robots.txt and your sitemaps, the 404s they run into and the redirects they follow. No downloading log files, no grep.

= Search bots, AI crawlers and everything else =

Next to the classic search engine bots, Log Hero recognises the AI crawlers that now make up a large share of automated traffic, and it tells you which is which:

* Search engines such as Googlebot, bingbot, YandexBot, Amazonbot and Sogou.
* AI crawlers and assistants such as GPTBot, ClaudeBot and ChatGPT-User.
* SEO tools such as AhrefsBot and SemrushBot.
* Social networks and link previews such as facebookexternalhit.

Bots that identify themselves with an unknown user agent are grouped so you can still see how much traffic they cause.

= Where your data lives =

The data is available in two places, and you can use both.

**In the Keyword Hero app.** A bot activity dashboard: hits, error rate, how many bots are active and how fast your site answers them, daily traffic split per bot, the paths that get crawled most and the errors bots run into.

**In Google Analytics 4.** Log Hero pushes the data into your GA4 property, so you can build your own explorations across bot name, landing page and status code, and combine bot traffic with the rest of your reporting.

= What you can analyse =

Everything below is included, there is no paid tier holding features back.

* The path each bot requested, so you can see what gets crawled and how often.
* The bot name, checked against the IP address rather than taken at face value, so a crawler claiming to be Googlebot has to prove it.
* The user agent it identified itself with.
* IP addresses, used for that verification (see "External services" below for exactly what this plugin transmits).
* HTTP status codes, to find broken links, redirect chains and server errors.
* Response times, so you can see how long your site kept a bot waiting.
* The request method.
* Referrer and channel, to see how bots reached a page.
* Sessions and users, so you can follow a bot through your site the way you would a human visitor.
* Spam bots, marked as such, so you can tell junk traffic apart from the crawlers you care about.

= Analysis in Google Analytics 4 =

Because the data lands in your own GA4 property, it stays yours and remains available even if you stop using Log Hero.

* Free form explorations across bot name, landing page and status code.
* Filtering and segmentation, regular expressions included.
* Export to Sheets, Excel or CSV, and access through the GA4 API.
* Dashboards and alerts alongside your existing reporting.

= Monitoring critical technical issues =

Log Hero helps you to identify many issues concerning technical optimization of your site and can derive quick action items to resolve these.

* Which bots crawl my site and how often?
* Which orphan pages has my site that are never crawled?
* Which status codes does my site return to search engines?
* Are my robots.txt and sitemap.xml crawled by search engine bots?
* How long does the bot need to download the resources of my site?
* Which AI crawlers read my content, and how much of my traffic do they account for?
* How much traffic never reaches my analytics because it does not run JavaScript?
* and much more. Visit [log-hero.com](https://log-hero.com)! for more information

== External services ==

This plugin relies on Log Hero, an external service operated by Cross Platform Solutions GmbH. The plugin has no function without it: its only purpose is to forward request data to that service, which then makes bot and crawler traffic visible in your analytics account.

= What is sent, and when =

The plugin registers a handler on the WordPress `shutdown` action. For **every** request served by your site -- from bots and from human visitors alike -- it collects the following data about that single request and transmits it to the Log Hero API at `https://in.app.log-hero.com/logs`:

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

1. Upload the `loghero` folder to the `/wp-content/plugins/` directory.
2. Activate the Log Hero plugin through the 'Plugins' menu in WordPress.
3. Follow the steps on "after activation" below.

= After activation =

1. Click on Settings > LogHero.
2. Now you have to enter the API key you received in the sign-up flow on [log-hero.com](https://log-hero.com). If you don't have an API key, get one for free on [https://log-hero.com](https://log-hero.com)!
3. You're done. Bot traffic starts showing up in the Log Hero dashboard in the Keyword Hero app, and in the Google Analytics 4 property named "Log Hero - your-domain.com".

If you have any problems, write our support team.

== Frequently Asked Questions ==

You'll find answers to many of your questions on [log-hero.com](https://log-hero.com) or write our support team.

== Changelog ==

= [0.3.0] =
= Security =
* Removed flush.php, a directly accessible PHP file in the plugin directory that ran without loading WordPress. Flushing is now a REST route, loghero/v1/flush, authenticated in its permission callback with a constant time token comparison.
* All settings values are sanitized when saved and escaped when displayed. The developer endpoint field is restricted to http and https URLs.
* Every plugin file now refuses to run when called directly.

= Fixed =
* The API endpoint on the developer settings page could never be saved. It was registered under its pre 0.2.3 name while the form posted the prefixed one, so WordPress discarded the value.
* Admin notices linked to /wp-admin/ instead of the real admin URL, which was wrong on installations in a subdirectory.
* Log events are delivered again. The API endpoint the plugin shipped with, api.loghero.io, no longer exists, so no data could reach Log Hero. The plugin now talks to the current endpoint.

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
Important fix: earlier versions sent log events to an endpoint that no longer exists, so no data arrived. This release restores delivery. It also requires WordPress 6.0 and PHP 7.4 or newer and documents the data transmitted to the Log Hero service.

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

1. Bot activity at a glance: hits, error rate, number of active bots and average response time, with daily traffic broken down per bot.
2. Every bot that crawled the site, with its share of traffic, the mix of status codes it received and how fast your site answered it.
3. The paths bots crawl most, next to the errors they ran into.
4. The same data in Google Analytics 4, as a free form exploration by bot name, landing page and status code.
5. Plugin settings: enter the API key you received from log-hero.com.
