# Google Calendar Events Wordpress Plugin

This is a very simple Wordpress 5.x plugin that uses the Google Calendar API PHP Library to pull events from a single Google Calendar and display them in a few widget styles.

## Setup instructions

## Find your Google Calendar ID

This is subject to change and I won't be updating this documentation to follow all of the whims of Google's design team. For now (January 2020) the following instructions work:

1. Open your Google Calendar app page using your Google account.
2. Navigate to your subscribed/available Google calendars list (usually bottom left side).
3. To get to your calendar settings, use your mouse point to hover over the calendar you wish to work with and click the three vertical dots that appear to the right – this will bring up a dropdown menu, click Settings and sharing.
4. A new page will open. Find the Calendar ID at the bottom under the Integrate Calendar section - copy that ID into your `index.php` file (line #16.)

## Download your Service Account Key JSON file

This is subject to change and I won't be updating this documentation to follow all of the whims of Google's design team. For now (January 2020) the following instructions work:

1. Visit: https://console.cloud.google.com, sign in with the account that owns the desired Google Calendar.
2. Select the "Create” button.
3. Give this project a name that is memorable to you. Ex: "My Org's Wordpress Calendar” and you may need to select an organization - I’m not *quite* sure how you’ve set up your org's google account and it will differ slightly per account.
4. There should be a card for “Google Calendar API” - select that and then select the “Enable” button.
5. Now we need to create the access tokens. Go to the Credentials section, press the Create Credentials button, and select the Service account key option.
6. Now we need to define what can be accessed. Choose the New service account option and type in a name for the service account. In the Role field, select the Role Viewer option.
7. Once you press the Create button, you'll receive a JSON file containing a private key and a client ID. That file should be uploaded along side this plugin and placed at the same level as the `index.php` file. Do *NOT* add that file to this code repository.

### Plugin Setup and Installation

1. Place the files in this repository into a new director underneath your `wp-content/plugins/` directory.
2. Download the [Google API PHP Client](https://github.com/googleapis/google-api-php-client/releases) and unzip into a directory underneath the directory created in step one. The name of this directory should be `google-api-php-client` or you should update the line #8 to match your new directory name:  
	`require __DIR__ . '/google-api-php-client/vendor/autoload.php';`
3. There are a few other configurations values you need to set:  
	* `define("SC_GOOGLE_API_CONFIG_JSON", "CHANGE_THIS");`  
	This needs to match the name of the service account key JSON file you created earlier.
	* `define("SC_TIMEZONE", "America/Los_Angeles");`  
	This should match your organization's timezone.
	* `define("SC_CALENDAR_ID", "CHANGE_THIS");`  
	This should match the Google Calendar ID you'd like to pull events from - you probably setup this up if you've followed the instruction from earlier.
4. Load up your Wordpress Admin panel and make sure the plugin is activated. You'll find the new widgets in your Apperance>Widgets area for use.

## Look'n'feel

There's a sylesheet `sc-event-styles-v2.css` where all of the colors and layout properties are defined. The general HTML structure should work for a variety of layouts but if you need to alter it, that would be done inside of `index.php` for any of the three widgets that are specified within.

## Assumptions

This plugin was developed on a rather small budget so it makes a number of assumptions to simplify matters:

1. It assumes you have a page at the route `"/events/"` where you intend to direct folks for a more comprehensive listing of events. This could be resolved somewhat be moving this detail to a constant variable. It is unlikely that I'd go so far as to make a wordpress plugin settings page for this tool (which would be the most ideal solution.)
2. The "event detail" widget is somewhat of a hack. The way we implemented was relying on  a third party plugin, [amr shortcode any widget](https://wordpress.org/plugins/amr-shortcode-any-widget/), that we embed on a page at the route `"/event/details/"` will it load the event content. There's surely a smarter way to do this but I'm not a WordPress5 plugin expert. What's happening here is the path is constructed as `/event/details/$UUID` where the UUID is the Google Calendar Event UUID so we can pull the correct info from the Google Calendar API.

## TODO

1. There's some clearly unnecessarily duplicated blocks of code that could be rolled up into a function/etc. Someday.
2. Try to make the routes bit slightly more configuration friendly.