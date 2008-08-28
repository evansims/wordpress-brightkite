=== Brightkite Location for Wordpress ===
Contributors: evansims
Donate link: http://evansims.com/projects/brightkite-location
Tags: brightkite, location, gps, widget
Requires at least: 2.5
Tested up to: 2.6
Stable tag: trunk

The Brightkite Location plugin for WordPress pulls your latest location data and notes/photos and displays it on your blog.

== Description ==

The Brightkite Location plugin for WordPress pulls your latest location data and notes/photos and displays it on your blog.

You will need to know how to edit your WordPress template files, and will require a Google Map API key, which you can get from: http://code.google.com/apis/maps/signup.html

== Installation ==

1. Extract the zip archive and upload the contents to your /wp-contents/plugins/ folder. The files should end up in a directory named "brightkite-location".
2. Log into your WordPress Dashboard and activate the plugin.
3. In your template of choice, use the tag: 

   <?php bkRenderLocation('username', 'google maps api key'); ?>
   
   Replacing 'username' with the username of the Brighkite user you wish to display data on, and the API key for Google Maps that you requested.
   An optional third argument is available that allows you to customize the output. See the FAQ for acceptable values.

That's it!

== Frequently Asked Questions ==

= What are the system requirements? =

Brightkite Location was built for PHP5, but may work under PHP4. It is simply untested. Your web host must support fsockopen().

= What customization options can I use with Brighkite Location? =

As a template tag, Brightkite Location offers a third argument you can use to customize the look of your Brightkite information. The following settings can be used, and should be passed as an array.

* map_width
  Width of returned Google Map in pixels.
* map_height
  Height of returned Google Map in pixels.
* map_style
  Can be either 'roadmap' (default) or 'mobile'. Mobile is usually better for smaller maps, but isn't as pretty.
* map_zoom
  Can be anywhere between 0 and 19. 14 is the default.
* map_beacon_color
  Color of your indicated position on the map; can be black, brown, green, purple, yellow, blue, gray, orange, red or white.
* map_beacon_size
  Size of the beacon indicating your position; can be tiny, mid or small.

= I'm getting write permission errors. =

Please CHMOD your "brightkite-location" folder as 0755 and/or manually create an empty file matching the filename in the error and CHMOD it as 0777. Depending on your web host, you may need or want to adjust these values.

== Screenshots ==

1. Brighkite Location on http://evansims.com

== Changes ==

Version 1.0 Beta 3

* Fixes an issue with brightkite accounts with certain privacy settings applied.
* Adds a dynamic Google Map option; pass "map_provider" as "dynamic" to enable. Still needs more work.
* Hotfix A - Resolves a problem with locations not showing up.

Version 1.0 Beta 2

* Removed dependency on SimpleXML; now uses the wonderful little xmlize library by Hans Anderson.
* Added third settings argument to bkRenderLocation(), which allows for more customization over the output.
  Pass arguments in the form of a function, like so:
  
  bkRenderLocation('username', 'api key', array('map_width' => 100, 'map_height' => '200', 'map_zoom' => 14));
  
  The following setting options are available: map_width, map_height, map_style, map_zoom, map_beacon_color, map_beacon_size.
  
  map_width = Width of returned Google Map in pixels.
  map_height = Height of returned Google Map in pixels.
  map_style = Can be either 'roadmap' (default) or 'mobile'. Mobile is usually better for smaller maps, but isn't as pretty.
  map_zoom = Can be anywhere between 0 and 19. 14 is the default.
  map_beacon_color = Color of your indicated position on the map; can be black, brown, green, purple, yellow, blue, gray, orange, red or white.
  map_beacon_size = Size of the beacon indicating your position; can be tiny, mid or small.

Version 1.0 Beta

* First public release. Expect bugs.
