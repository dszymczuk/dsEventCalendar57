<?php
namespace Concrete\Package\Dseventcalendar\Controller\SinglePage\Dashboard\EventCalendar;
use \Concrete\Core\Page\Controller\DashboardPageController;
use Loader;

defined('C5_EXECUTE') or die("Access Denied.");

class Help extends DashboardPageController
{

    public function view()
    {

    }

    public function on_before_render()
    {
        $this->addHeaderItem(Loader::helper('html')->css('dsStyle.css', 'dsEventCalendar'));
    }

}