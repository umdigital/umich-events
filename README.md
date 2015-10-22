UMich Events
============
Wordpress widget to get and display events from http://events.umich.edu.

> Using default options will be slow when requesting data from events.umich.edu.  It is highly recommended to use at least one filtering option (featured, ongoing, tags, groups, locations) to increase the events request speed.

## Features
### Widget
* Unstyled so you can make it your own as easy as possible
* Display event image, caches local resized copy for optimal performance, cached for 7 days
* Caches feed for 5 minutes to improve performance
* Ability to specify Tags, Groups, & Locations to display events for
* Customize event template in your theme
  - THEME/umich-events/widget.tpl
  - THEME/umich-events/event-widget.tpl

### Shortcode
```
[umichevents showimage="0" imagesize="full" featured="0" ongoing="0" tags="" groups="" locations="" morelink="0" morelinktext="See all events" limit="25"]
```
* Override templates in your theme
  - THEME/umich-events/shortcode.tpl (list of events, loads single event template)
  - THEME/umich-events/event-shortcode.tpl (single event)

#### Shortcode options
| Option       | Values      | Default        |
| ------------ | ----------- | -------------- |
| showimage    | 1, 0        | 0              |
| imagesize    | string      | full           |
| featured     | 1, 0        | 0              |
| ongoing      | 1, 0        | 0              |
| tags         | string list | null           |
| groups       | num list    | null           |
| locations    | num list    | null           |
| morelink     | 1, 0        | 0              |
| morelinktext | string      | See all events |
| limit        | number      | 25             |
*lists are comma seperated*


## Template variables
* $events
  - this contains the event JSON object. See *JSON Object Description* on http://events.umich.edu/feeds/

## To Do
* Admin interface for cache timeouts
  - Feed Timeout (currently 5 min), minimum 1 minute
  - Image Timeout (currently 7 days), minimum 1 day
* Multisite support
  - Network admin cache management
  - Network admin cache storage (single or per site)
