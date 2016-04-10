<?php
namespace Concrete\Package\Dseventcalendar\Block\EventCalendar;
use Concrete\Core\Block\BlockController;
use Loader;
use Config;
use DsEventCalendar;

defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends BlockController
{

    protected $btName = "Event Calendar";
    protected $btDescription = "";
    public $btTable = 'btEventCalendar';
    public $btInterfaceWidth = '400';
    public $btInterfaceHeight = '200';
    protected $btWrapperClass = 'ccm-ui';
    private $lang_list = array("ar-ma", "ar-sa", "ar", "bg", "ca", "cs", "da", "de-at", "de", "el", "en-au", "en-ca", "en-gb", "es", "fa", "fi", "fr-ca", "fr", "he", "hi", "hr", "hu", "id", "is", "it", "ja", "ko", "lt", "lv", "nl", "pl", "pt-br", "pt", "ro", "ru", "sk", "sl", "sr-cyrl", "sr", "sv", "th", "tr", "uk", "vi", "zh-cn", "zh-tw");

    public function getBlockTypeDescription()
    {
        return $this->btDescription;
    }

    public function getBlockTypeName()
    {
        return t("Event Calendar");
    }

    public function __construct($b = null)
    {
        parent::__construct($b);
    }

    public function view()
    {
        $this->requireAsset('javascript', 'jquery');

        $db = Loader::db();
        if ($this->calendarID == 0) {
            $calendar = array(array(
                'title' => t('All calendars')
            ));
        } else {
            $calendar = $db->GetAll("SELECT * FROM dsEventCalendar WHERE calendarID=" . $this->calendarID);
        }
        $this->set('calendar', $calendar);

        $dsEventCalendar = new \dsEventCalendar\dsEventCalendar();

        $json_events = $dsEventCalendar->getEventsFromCalendar($this->calendarID, $this->typeID);

        $this->set('events', $json_events);
        $this->set('settings', $dsEventCalendar->settingsProvider());
        $this->set('typeID', $this->typeID);

        if (method_exists($this->getBlockObject(), 'getProxyBlock')) {
            $this->set(
                'blockIdentifier',
                $this->getBlockObject()->getProxyBlock()
                    ? $this->getBlockObject()->getProxyBlock()->getInstance()->getIdentifier()
                    : $this->getIdentifier()
            );
        } else {
            $this->set('blockIdentifier', rand(12, 512));
        }

    }

    public function action_ical()
    {
        $db = Loader::db();
        $caltitle = $db->fetchColumn("select title from dsEventCalendar where calendarID = ?", array($this->calendarID), 0);
        if ($this->calendarID == 0) {
            $events = $db->query("select * from dsEventCalendarEvents");
        } else {
            $events = $db->prepare("select * from dsEventCalendarEvents where calendarID = :cid");
            $events->bindValue("cid", $this->calendarID);
            $events->execute();
        }
        
        $ical = "";
        $this->icalPush($ical, "BEGIN", "VCALENDAR");
        $this->icalPush($ical, "VERSION", "2.0");
        $this->icalPush($ical, "METHOD", "PUBLISH");
        $this->icalPush($ical, "X-WR-CALNAME", $caltitle, ";VALUE=TEXT");
        $this->icalPush($ical, "PRODID", "-//".Config::get('concrete.site')."//".$caltitle);

        while ( $e = $events->fetch()) {
            $this->icalPush($ical, "BEGIN", "VEVENT");
            $this->icalPush($ical, "METHOD", "PUBLISH");
            $this->icalPush($ical, "DTSTART", $this->icalDateToCal( $e["date"] ));
            $this->icalPush($ical, "DTEND", $this->icalDateToCal( $e["end"] ));
            $this->icalPush($ical, "SUMMARY", $this->icalConvertSpecialCharacters($e["title"]));
            $this->icalPush($ical, "UID", $e["eventID"]);
            $this->icalPush($ical, "URL", $e["url"]);
            $this->icalPush($ical, "DESCRIPTION", $this->icalConvertSpecialCharacters($e["description"]));
            $this->icalPush($ical, "CLASS", "PUBLIC");
            $this->icalPush($ical, "STATUS", "CONFIRMED");
            $this->icalPush($ical, "END", "VEVENT");
        }

        $this->icalPush($ical, "END", "VCALENDAR");

        $response = new \Symfony\Component\HttpFoundation\Response();
        $response->setContent($ical);
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        return $response;
    }
    
    private function icalPush(&$icalFile, $Name, $Value, $Options = "")
    {
        if ($Name != "" && $Value != "")
            $icalFile .= $Name.$Options.":".$Value."\n";
    }
    
    private function icalDateToCal($timestamp) {
        return gmdate("Y-m-d\TH:i:s\Z", strtotime($timestamp));
    }
    
    private function icalConvertSpecialCharacters($s) 
    {
        return preg_replace(
            array ('/"/','/,/','/\n/','/\r/','/:/','/;/','/\\//'), 
            array ('\"','\\,','\\n','','\:','\\;','\\\\'), 
            $s
        );
    }
    
    function save($data)
    {
        $args['calendarID'] = isset($data['calendarID']) ? intval($data['calendarID']) : 0;
        $args['typeID'] = isset($data['typeID']) ? intval($data['typeID']) : 0;
        $args['lang'] = isset($data['lang']) ? $data['lang'] : 'en-gb';
        $args['contentHeight'] = isset($data['contentHeight']) ? $data['contentHeight'] : 'auto';
        parent::save($args);
    }

    function add()
    {
        $db = Loader::db();
        $calendars = $db->GetAll("SELECT * FROM dsEventCalendar");
        array_unshift($calendars, array(
            'calendarID' => 0,
            'title' => 'All calendars'
        ));
        $this->set('calendars', $calendars);

        $this->set('langs', $this->lang_list);
        $this->set('lang', 'en-gb');

        $dsEventCalendar = new \dsEventCalendar\dsEventCalendar();
        $types = $dsEventCalendar->getEventTypesForBlock();
        $this->set('types', $types);
        $this->set('types', $types);
    }

    function edit()
    {
        $db = Loader::db();
        $calendars = $db->GetAll("SELECT * FROM dsEventCalendar");
        array_unshift($calendars, array(
            'calendarID' => 0,
            'title' => 'All calendars'
        ));
        $this->set('calendars', $calendars);
        $this->set('calendarID', $this->calendarID);

        $this->set('langs', $this->lang_list);
        $this->set('lang', $this->lang);
        $this->set('contentHeight', $this->contentHeight);

        $dsEventCalendar = new \dsEventCalendar\dsEventCalendar();
        $types = $dsEventCalendar->getEventTypesForBlock();
        $this->set('types', $types);
        $this->set('typeID', $this->typeID);
    }
}

?>
