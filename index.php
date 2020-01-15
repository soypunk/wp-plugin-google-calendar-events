<?php
/**
 * Plugin Name: Sustainable Claremont Google Calendar Plugin
 * Description: Displays upcoming events the Sustainable Claremont Google Calendar
 * Version: 1.0.0
 */

require __DIR__ . '/google-api-php-client/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("SC_GOOGLE_API_CONFIG_JSON", "CHANGE_THIS");
define("SC_TIMEZONE", "America/Los_Angeles");
define("SC_CALENDAR_ID", "CHANGE_THIS");
define("PATH_TO_PLUGIN_DIR", plugin_dir_path( __FILE__ ));

function sc_event($event) {
    /* normalize event details into a data structure we can use for our widgets */
    $timezone = new DateTimeZone(SC_TIMEZONE);
    
    $event_def = [];
    $event_def['id'] = $event->getId(); 
    $event_def['is_all_day'] = false;
    $event_def['start'] = NULL;
    $event_def['end'] = NULL;
    $event_def['summary'] = $event->getSummary();
    $event_def['gcal_obj'] = $event;
    
    $event_def['start'] = $event->getStart()->getDate();
    
    if ($event_def['start']) {
        // all day event
        $event_def['is_all_day'] = true;
        $event_def['end'] = $event->getEnd()->getDate();
    } else {
        // day event with start/end time
        $event_def['start'] = $event->getStart()->getDateTime();
        $event_def['end'] = $event->getEnd()->getDateTime();            
    }   
    
    $event_def['start'] = new DateTime($event_def['start'], $timezone);
    $event_def['end'] = new DateTime($event_def['end'], $timezone);
    
    return $event_def;
}

class SC_GCal_Widget extends WP_Widget {
    /* this is the 10 item home page event box widget */
    
    private $client;

    public function __construct() {
    
        $this->client = new Google_Client();
        $this->client->setAuthConfig(__DIR__ . SC_GOOGLE_API_CONFIG_JSON);
        
        $this->client->setScopes(
            "https://www.googleapis.com/auth/calendar.events.readonly"
        );      
    
        parent::__construct('sc_gcal_widget', 'SC: Event List Widget',
            array(
                'classname' => 'sc_gcal_widget',
                'description' => 'Shows 10 events as a list from our calendar'
            )
        );
        
        // Register style sheet.
        add_action('wp_enqueue_scripts', array( $this, 'register_plugin_styles' ));
    }
    
    /**
     * Register and enqueue style sheet.
     */
    public function register_plugin_styles() {
        wp_register_style( 'SC_GCal_Widget', plugins_url( 'sc-event-calendar/sc-event-styles-v2.css' ) );
        wp_enqueue_style( 'SC_GCal_Widget' );
    }   
    
    public function widget($args, $instance) {
        $calendarService = new Google_Service_Calendar($this->client);
        $timezone = new DateTimeZone(SC_TIMEZONE);
        $myCalendarID = SC_CALENDAR_ID;
        $events = $calendarService->events
                                  ->listEvents($myCalendarID, array(
                                        'singleEvents' => true,
                                        'orderBy' => 'startTime',
                                        'timeMin' => date(DATE_RFC3339), 
                                        'maxResults' => 10)
                                  )->getItems();        
        echo "<h2>Upcoming</h2>";
        echo "<ol class='sc-events'>";

        foreach ($events as $e) {           
            $sc_event = sc_event($e);
            echo "<li class='cf'>";
                echo "<div class='event-date'>";
                    echo "<span class='day'>" . $sc_event['start']->format("D") . "</span>";
                    echo "<span class='date'>" . $sc_event['start']->format("j") . "</span>";
                    echo "<span class='month'>" . $sc_event['start']->format("M") . "</span>";
                echo "</div>";
                echo "<div class='event-details'>";         
                    echo "<h5 class='event-title'><a href='/events/detail/" . $sc_event['id'] . "'>" . $sc_event['summary'] . "</a></h5>";
                    if (!$sc_event['is_all_day']) {
                        echo "<p class='event-time'>" . $sc_event['start']->format("g:iA") . " - " . $sc_event['end']->format("g:iA") . "</p>";
                    }
                echo "</div>";
            echo "</li>";
        }       
        echo "</ol>";
    }
}

add_action('widgets_init', function(){
    register_widget('SC_GCal_Widget');
});

class SC_GCal_Long_Widget extends WP_Widget {
    /* This is the event list used on /events/ */
    private $client;

    public function __construct() {
    
        $this->client = new Google_Client();
        $this->client->setAuthConfig(__DIR__ . SC_GOOGLE_API_CONFIG_JSON);
        
        $this->client->setScopes(
            "https://www.googleapis.com/auth/calendar.events.readonly"
        );      
    
        parent::__construct('sc_gcal_long_widget', 'SC: Event Long List Widget',
            array(
                'classname' => 'sc_gcal_long_widget',
                'description' => 'Shows 60 events as a list from our calendar'
            )
        );
        
        // Register style sheet.
        add_action('wp_enqueue_scripts', array( $this, 'register_plugin_styles' ));
    }
    
    /**
     * Register and enqueue style sheet.
     */
    public function register_plugin_styles() {
        wp_register_style( 'SC_GCal_Long_Widget', plugins_url( 'sc-event-calendar/sc-event-styles-v2.css' ) );
        wp_enqueue_style( 'SC_GCal_Long_Widget' );
    }   
    
    public function widget($args, $instance) {

        $calendarService = new Google_Service_Calendar($this->client);
        $timezone = new DateTimeZone(SC_TIMEZONE);
        $myCalendarID = SC_CALENDAR_ID;
        $events = $calendarService->events
                                  ->listEvents($myCalendarID, array(
                                        'singleEvents' => true,
                                        'orderBy' => 'startTime',
                                        'timeMin' => date(DATE_RFC3339), 
                                        'maxResults' => 60)
                                  )->getItems();
        
        echo "<h2>Upcoming Events</h2>";
        
        $prev_event_month = NULL;
        
        foreach ($events as $e) {       
            $sc_event = sc_event($e);       
            $event_month_year = $sc_event['start']->format("F Y");
                        
            if ($event_month_year != $prev_event_month) {
                /* this handles the %%MONTH%% %%YEAR%% header */
                if ($prev_event_month != NULL) {
                    echo "</ol>";
                }
                echo "<h3>" . $event_month_year . "</h3>";
                echo "<ol class='long-form-events sc-events'>";
            }

            echo "<li class='cf'>";
                echo "<div class='event-details'>";         
                    echo "<h5 class='event-title'><a href='/events/detail/" . $sc_event['id'] . "'>" . $sc_event['summary'] . "</a></h5>";
                    echo "<p class='event-time'>";
                        echo $sc_event['start']->format("l F jS, Y");
                        if (!$sc_event['is_all_day']) {
                            echo "<br/>";
                            echo $sc_event['start']->format("g:iA") . " - " . $sc_event['end']->format("g:iA");
                        }
                    echo "</p>";
                echo "</div>";
            echo "</li>";           
            $prev_event_month = $event_month_year;
        }       
        echo "</ol>";
    }
}

add_action('widgets_init', function(){
    register_widget('SC_GCal_Long_Widget');
});

class SC_GCal_Widget_Event_Detail extends WP_Widget {
    /* this is a bit of a hack - when an event link is clicked it takes you to a wordpress 
    page /events/detail/ with a Google Calendar Event ID in the URL path. That ID is 
    passed down to this widget which shows the full event details. */
    private $client;

    public function __construct() {
    
        $this->client = new Google_Client();
        $this->client->setAuthConfig(__DIR__ . SC_GOOGLE_API_CONFIG_JSON);
        
        $this->client->setScopes(
            "https://www.googleapis.com/auth/calendar.events.readonly"
        );      
    
        parent::__construct('sc_gcal_detail_widget', 'SC: Event Detail Widget',
            array(
                'classname' => 'sc_gcal_detail_widget',
                'description' => 'Shows events as a Detail from our calendar'
            )
        );
        
        // Register style sheet.
        add_action('wp_enqueue_scripts', array( $this, 'register_plugin_styles' ));
        // flush_rewrite_rules();
        add_rewrite_rule('events/detail/(.+?)/?$', 'index.php?page_id=7499&event_id=$matches[1]', 'top');
        add_rewrite_tag( '%event_id%', '([^&]+)' );
    }
    
    /**
     * Register and enqueue style sheet.
     */
    public function register_plugin_styles() {
        wp_register_style( 'SC_GCal_Widget_Event_Detail', plugins_url( 'sc-event-calendar/sc-event-styles-v2.css' ) );
        wp_enqueue_style( 'SC_GCal_Widget_Event_Detail' );
    }   
    
    public function widget($args, $instance) {
        #global $wp;
        
        $calendarService = new Google_Service_Calendar($this->client);
        $timezone = new DateTimeZone(SC_TIMEZONE);
        $myCalendarID = SC_CALENDAR_ID;
        
        $event_id = get_query_var('event_id');

        if (!$event_id) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            get_template_part( 404 ); exit();       
        }    
        
        $event = $calendarService->events->get($myCalendarID, $event_id);
        $sc_event = sc_event($event);
        
        echo "<article class='sc-event-detail'>";
        echo "<h2 class='event-title'><a href='/events/'>Events:</a> " . $sc_event['summary'] . "</h2>";
        
        echo "<p class='event-date'>" . $sc_event['start']->format("F j, Y");
        if (!$sc_event['is_all_day']) {
            echo "<br/>";
            echo $sc_event['start']->format("g:iA") . " - " . $sc_event['end']->format("g:iA");
        }
        echo "</p>";
        
        $description = $sc_event['gcal_obj']->getDescription();
        
        if ($description) {
            echo "<div>";
            echo "<h4>Description</h4>";
            echo $description;
            echo "</div>";
        }
        
        echo "<br>";
        
        $location = $sc_event['gcal_obj']->getLocation();
        
        if ($location) {
            echo "<div>";
            echo "<h4>Location</h4>";
            echo $location;
            echo "</div>";
        }
        
        echo "</article>";
    }
}

add_action('widgets_init', function(){
    register_widget('SC_GCal_Widget_Event_Detail');
});