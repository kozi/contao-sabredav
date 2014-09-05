<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2014 Leo Feyer
 *
 *
 * PHP version 5
 * @copyright  Martin Kozianka 2014 <http://kozianka.de/>
 * @author     Martin Kozianka <http://kozianka.de/>
 * @package    contao-fullcalendar
 * @license    LGPL
 * @filesource
 */

namespace ContaoFullcalendar;


use Contao\Controller;
use Contao\DC_Table;
use Contao\CalendarEventsModel;


class EventMapper {
    const TMPL_DESCRIPTION = '<h3>%s</h3><p class="desc">%s</p>';
    /* Convert "Contao-Event-Array" to json representation for fullcalendar
     * @param integer
     * @param array
     * @param string
     * @return object
     */
    public static function convert($intDay, array $event, $color = null) {
        $arrCSS              = array_map('trim', explode(' ', $event['class']));
        $arrCSS[]            = 'jsonEvent';
        $newEvent            = new \stdClass();
        $newEvent->id        = $event['id'];
        $newEvent->title     = \String::decodeEntities($event['title']);

        $dateBegin = \Date::parse('Y-m-d', $event['begin']);
        $dateEnd   = \Date::parse('Y-m-d', $event['end']);

        $timeBegin = \Date::parse('H:i', $event['begin']);
        $timeEnd   = \Date::parse('H:i', $event['end']);


        if ($event['begin'] === $event['end']) {
            // Ein Event mit Startzeit ohne Endzeit
            $newEvent->start = \Date::parse('c', $event['begin']);
            $arrCSS[]        = 'oneDayTime';
        }
        elseif ($event['addTime'] === '') {
            // Ohne Zeitangaben
            $newEvent->start   = $dateBegin;
            // Ein oder mehrere Tage?
            if ($dateBegin !== $dateEnd) {
                // Es muss 1 Tag hinzugefügt werden
                $newEvent->end    = \Date::parse('Y-m-d', strtotime('+1 day', $event['end']));
                $arrCSS[]         = 'days';
            } else {
                $arrCSS[]        = 'oneDay';
            }
        }
        elseif($timeBegin === $timeEnd) {
            // Nur eine Startzeit
            $newEvent->start     = \Date::parse('c', $event['begin']);
            $newEvent->end       = $dateEnd;
            $arrCSS[]            = 'daysStart';
        }
        else {
            // Mehrere Tage mit Start- und Endzeit
            $newEvent->start     = \Date::parse('c', $event['begin']);
            $newEvent->end       = \Date::parse('c', $event['end']);
            $arrCSS[]            = 'daysTime';
        }


        $newEvent->className = implode(' ', $arrCSS);
        $newEvent->details   = strip_tags($event['details']);

        $tmpl = new \FrontendTemplate("fullcalendar_tooltip");
        foreach($event as $k=>$v) {
            $tmpl->$k = $v;
        }
        $newEvent->description = $tmpl->parse();

        // echo '<br><code>'.implode(' _ ', array($dateBegin, $dateEnd, $timeBegin, $timeEnd, $newEvent->title, $newEvent->className));

        if ($color !== null) {
            $newEvent->backgroundColor = $color;
        }

        return $newEvent;
    }

    /**
     * Get CalendarEventsModel from VEvent
     * @param \Sabre\VObject\Component\VEvent
     * @param \CalendarModel
     * @return \CalendarEventsModel
     */
    public static function getCalendarEventsModel(\Sabre\VObject\Component\VEvent $vevent, \CalendarModel $calObj) {

        $eData       = static::serializeVevent($vevent);
        $start       = strtotime($eData['dtstart']);
        $end         = strtotime($eData['dtend']);
        $addTime     = !(strlen($eData['dtstart']) === 10 && strlen($eData['dtend']) === 10);
        $eventId     = $eData['uid'].'_'.$start;

        $eventObject = CalendarEventsModel::findOneBy('fullcal_id', $eventId);

        if ($eventObject === null) {
            $isNew       = true;
            $eventObject = new CalendarEventsModel();
        }
        else {
            $eventObject->fullcal_flagNew = false;
        }

        $eventObject->fullcal_id   = $eventId;
        $eventObject->fullcal_uid  = $eData['uid'];
        $eventObject->fullcal_desc = $eData['description'];
        $eventObject->teaser       = $eData['description'];
        $eventObject->pid          = $calObj->id;
        $eventObject->tstamp       = strtotime($eData['last-modified']);
        $eventObject->title        = $eData['summary'];
        $eventObject->location     = $eData['location'];
        $eventObject->published    = '1';
        $eventObject->addTime      = $addTime ? '1' : '';
        $eventObject->startDate    = $start;
        $eventObject->endDate      = $end;

        $eventObject->startTime    = $start;
        $eventObject->endTime      = $end;

        if(\Date::parse('dmY', $start) === \Date::parse('dmY',$end)) {
            $eventObject->endDate = null;
        }
        if (!$addTime) {
            if (\Date::parse('dmY', strtotime('+ 1 day', $start)) === \Date::parse('dmY', $end)) {
                $eventObject->endDate = null;
            }
            $eventObject->endDate = strtotime('- 1 day', $end);
        }

        $eventObject->save();

        // After first save() because the id is necessary for alias generation
        static::generateAlias($eventObject);

        // Das einzelne Event als ics Datei speichern
        static::saveEventAsIcs($eventObject, $vevent);

        // Must be last because of Database statement updating the values
        static::addDateTime($eventObject);

        $eventObject->fullcal_flagNew = $isNew;
        return $eventObject;

        return $eventObject;
    }

    /* Get a flat array with the event infos
     * @param \Sabre\VObject\Component\VEvent
     * @return array
     */
    public static function serializeVevent(\Sabre\VObject\Component\VEvent $vevent) {
        $values  = array();
        $jsonObj = $vevent->jsonSerialize();
        foreach($jsonObj[1] as $arrAttr) {
            if (count($arrAttr) === 4) {
                $key          = $arrAttr[0];
                $values[$key] = $arrAttr[3];
            }
        }
        return $values;
    }

    private static function saveEventAsIcs(CalendarEventsModel $eventObject, \Sabre\VObject\Component\VEvent $vevent) {
        $strFile = 'share/ics/'.$eventObject->alias.'.ics';

        $cal = new \Sabre\VObject\Component\VCalendar();
        $cal->add($vevent);

        $file = new \Contao\File($strFile);
        $file->write($cal->serialize());
        $file->close();

        $eventObject->fullcal_ics = $strFile;
        $eventObject->save();
    }

    /* Use the contao class tl_calendar_events to adjust time
     * @param \CalendarEventsModel
     */
    private static function addDateTime(CalendarEventsModel $eventObj) {
        Controller::loadDataContainer('tl_calendar_events');
        $dc = new DC_Table('tl_calendar_events');
        $dc->__set('id', $eventObj->id);
        $dc->__set('activeRecord', $eventObj);

        $tle = new \tl_calendar_events();
        $tle->adjustTime($dc);
    }

    /* Generate alias for CalendarEventsModel
     * @param \CalendarEventsModel
     */
    private static function generateAlias(CalendarEventsModel $eventObj) {
        $strAlias = standardize(\String::restoreBasicEntities($eventObj->title));

        $objAlias = \Database::getInstance()->prepare("SELECT id FROM tl_calendar_events WHERE alias=?")
            ->execute($strAlias);
        if ($objAlias->numRows > 1) {
            $strAlias .= '-'.$eventObj->id;
        }
        $eventObj->alias = $strAlias;
        $eventObj->save();
    }
}

