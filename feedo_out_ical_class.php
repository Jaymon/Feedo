<?php
/**
 *  feedo_out_ical php class
 *
 *  actually output an icalendar feed
 *  
 *  @link http://en.wikipedia.org/wiki/ICalendar
 *  @link http://tools.ietf.org/html/rfc2445
 *  
 *  by far the best ical documentation available:
 *    http://www.kanzaki.com/docs/ical/   
 *  
 *  calendar validator: http://severinghaus.org/projects/icv/?url= 
 *  better validator: http://icalvalid.cloudapp.net/Default.aspx
 *  most strict validator: http://arnout.engelen.eu/icalendar-validator (found problem the others missed)   
 *    
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-16-10
 *  @package  feedo  
 ******************************************************************************/
class feedo_out_ical extends feedo_out_interface {
  
  /**
   *  do any init stuff that should be done for the child classes   
   */
  protected function start(){}//method
  
  /**
   *  return the feed's content type
   *  
   *  @return string
   */
  function getContentType(){ return 'text/calendar'; }//method
  
  /**
   *  return the feed's extension (eg, ics for ical, or xml for rss)      
   *  
   *  @return string
   */
  function getExtension(){ return 'ics'; }//method
  
  /**
   *  return the feed's body/content
   *  
   *  @return string  the rendered feed ready to be outputted to the browser
   */
  function getBody(){
  
    $feedo = $this->getFeed();
    $ret_header_str = array();
    $ret_body_str = array();
    $tzid_map = array();
    $feedo_item_list = $feedo->getItems();
  
    $ret_header_str[] = 'BEGIN:VCALENDAR';
    $ret_header_str[] = 'VERSION:2.0';
    $ret_header_str[] = sprintf('PRODID:-//%s//NONSGML v1.0//EN',$feedo->getName());
    $ret_header_str[] = 'METHOD:PUBLISH';
    $ret_header_str[] = sprintf('X-WR-CALNAME:%s',$feedo->getTitle('feedo cal'));
    $ret_header_str[] = sprintf('X-WR-CALDESC:%s',$feedo->getDesc('feedo cal'));
    
    if(count($feedo_item_list) === 1){
      // fix for Outlook to not open the one calendar item as a new calendar...
      // http://www.eggheadcafe.com/software/aspnet/32677088/usage-of-xmsolkforceinspectoropen-property-in-icalendar-file.aspx
      // http://msdn.microsoft.com/en-us/library/ee203486%28v=exchg.80%29.aspx
      $ret_header_str[] = 'X-MS-OLK-FORCEINSPECTOROPEN:TRUE';
    }//if
    
    
    if($feedo->hasTzid()){
      $tzid = $feedo->getTzid();
      $ret_header_str[] = sprintf('X-WR-TIMEZONE:%s',$tzid);
      $tzid_map[$tzid] = $this->getTzInfo($tzid);
    }//if
    
    foreach($feedo_item_list as $feedo_item){
      
      // canary...
      if(!$feedo_item->hasStart()){ continue; }//if
        
      // http://www.kanzaki.com/docs/ical/vevent.html
      $ret_body_str[] = 'BEGIN:VEVENT';
      
      // set timezone stuff...
      $tzid = $feedo_item->getTzid();
      $has_tzid = false;
      if(!empty($tzid)){
        
        if(!isset($tzid_map[$tzid])){
          $tzid_map[$tzid] = $this->getTzInfo($tzid);
        }//if
        
        $has_tzid = true;
        
      }//if
      
      // do an all day event...
      if($this->isDaySpan($feedo_item->getStart(),$feedo_item->getStop())){
      
        $tz = ($has_tzid) ? sprintf('TZID=%s;',$tzid) : '';
        $ret_body_str[] = sprintf(
          'DTSTART;%sVALUE=DATE:%s',
          $tz,
          $this->getDayTimeStr($feedo_item->getStart())
        );
        $ret_body_str[] = sprintf(
          'DTEND;%sVALUE=DATE:%s',
          $tz,
          $this->getDayTimeStr($feedo_item->getStop())
        );
      
      }else{
      
        // a normal date has a defined beginning and end time...
        
        // http://www.kanzaki.com/docs/ical/dtstart.html
        if($has_tzid){
          
          $ret_body_str[] = sprintf(
            'DTSTART;TZID=%s:%s',
            $tzid,
            $this->getTimeStr($feedo_item->getStart(),$tzid_map[$tzid])
          );
    
        }else{
        
          $ret_body_str[] = sprintf(
            'DTSTART:%s',
            $this->getTimeStr($feedo_item->getStart())
          );
          
        }//if/else
          
        if($feedo_item->hasStop()){
        
          // http://www.kanzaki.com/docs/ical/dtend.html
          if($has_tzid){
            
            $ret_body_str[] = sprintf(
              'DTEND;TZID=%s:%s',
              $tzid,
              $this->getTimeStr($feedo_item->getStop(),$tzid_map[$tzid])
            );
      
          }else{
          
            $ret_body_str[] = sprintf(
              'DTEND:%s',
              $this->getTimeStr($feedo_item->getStop())
            );
            
          }//if/else
          
        }//if
        
      }//if/else
      
      if($this->isMultiDay($feedo_item->getStart(),$feedo_item->getStop())){
      
        // http://www.kanzaki.com/docs/ical/transp.html
        $ret_body_str[] = 'TRANSP:TRANSPARENT';
        
      }//if
      
      if($feedo_item->hasFrequency()){
        
        // from: http://www.kanzaki.com/docs/ical/rrule.html
        $format_str = 'RRULE:FREQ=%s;INTERVAL=1'; // WKST=SU;
        $format_vals = array($feedo_item->getFrequency());
        if($feedo_item->isWeekly()){
          $format_str .= ';BYDAY=%s';
          $format_vals[] = $this->getDay($feedo_item->getStart());
        }else if($item_map->isYearly()){
          $format_str .= ';BYMONTH=%s';
          $format_vals[] = date('n',$feedo_item->getStart());
        }//if/else if
        
        $ret_body_str[] = vsprintf($format_str,$format_vals);
        
      }//if
      
      if($feedo_item->hasId()){
        $ret_body_str[] = sprintf('UID:%s',$feedo_item->getId());
      }//if
      
      if($feedo_item->hasTitle()){
        $ret_body_str[] = sprintf('SUMMARY:%s',$this->getFormatted($feedo_item->getTitle()));
      }//if
      
      $ret_body_str[] = sprintf(
        'DESCRIPTION:%s',
        $this->getFormatted(
          sprintf('%s %s',$feedo_item->getDesc(),$feedo_item->getLink())
        )
      );
      
      if($feedo_item->hasLink()){
        $ret_body_str[] = sprintf('URL:%s',$this->getSafe($feedo_item->getLink()));
      }//if
      
      // http://www.kanzaki.com/docs/ical/organizer.html
      /* @note 2-23-10 - I took this out since it was causing iCal to not let the user change the
      calendar information when importing an ical event, we should think about moving this info into
      http://www.kanzaki.com/docs/ical/attendee.html or
      http://www.kanzaki.com/docs/ical/contact.html
      if($feedo_item->hasAuthor()){
      
        $author_map = $feedo_item->getAuthor();
        if(!empty($author_map['name'])){
          
          $format_str = 'ORGANIZER;CN=%s';
          $format_vals = array($this->getSafe($author_map['name']));
          
          if(!empty($author_map['url'])){
            $format_str .= ';DIR="%s"';
            $format_vals[] = $this->getSafe($author_map['url']);
          }//if
          
          $format_str .= ':';
          
          if(!empty($author_map['email'])){
            $format_str .= 'MAILTO:%s';
            $format_vals[] = $this->getSafe($author_map['email']);
          }//if
          
          $ret_body_str[] = vsprintf($format_str,$format_vals);
          
        }//if
        
      }//if */
      
      // http://www.kanzaki.com/docs/ical/categories.html
      if($feedo_item->hasTags()){
        $tag_list = array();
        foreach($feedo_item->getTags() as $tag_map){
          $tag_list[] = $this->getFormatted($tag_map['tag']);
        }//foreach
        $ret_body_str[] = sprintf('CATEGORIES:%s',join(',',$tag_list));
      }//if
      
      /// CLASS:PUBLIC
      
      // http://www.kanzaki.com/docs/ical/location.html
      if($feedo_item->hasLocation()){
        $location_map = $feedo_item->getLocation();
        $ret_body_str[] = sprintf('LOCATION:%s',$this->getFormatted($location_map['name']));
      }//if
      
      // http://www.kanzaki.com/docs/ical/dtstamp.html
      if($feedo_item->hasTimestamp()){
        $ret_body_str[] = sprintf(
          'DTSTAMP:%s',
          $this->getTimeStr($feedo_item->getTimestamp(),array('offset' => 0))
        );
      }//if
      
      $ret_body_str[] = 'END:VEVENT';
           
    }//foreach
    
    // set timezone info in the header...
    if(!empty($tzid_map)){
  
      // timezone stuff: http://www.kanzaki.com/docs/ical/vtimezone.html...  
      foreach($tzid_map as $tzid => $tz_map){
      
        $dst_offset = $st_offset = (isset($tz_map['standard']) ? $tz_map['standard']['offset'] : 0);
      
        $ret_header_str[] = 'BEGIN:VTIMEZONE';
        $ret_header_str[] = sprintf('TZID:%s',$tzid);
        $ret_header_str[] = sprintf('X-LIC-LOCATION:%s',$tzid);
        
        if(isset($tz_map['daylight'])){
        
          $dst_offset = $tz_map['daylight']['offset'];
        
          $ret_header_str[] = 'BEGIN:DAYLIGHT';
          $ret_header_str[] = sprintf('TZOFFSETFROM:%s',$this->getHours($st_offset,false));
          $ret_header_str[] = sprintf('TZOFFSETTO:%s',$this->getHours($dst_offset,false));
          if(!empty($dst_map['daylight']['abbr'])){
            $ret_header_str[] = sprintf('TZNAME:%s',$tz_map['daylight']['abbr']);
          }//if
          
          $ret_header_str[] = sprintf('DTSTART:1970%sT020000',date('md',$tz_map['daylight']['ts']));
          $ret_header_str[] = sprintf(
            'RRULE:FREQ=YEARLY;BYMONTH=%s;BYDAY=%s%s',
            date('n',$tz_map['daylight']['ts']),
            $this->getWeekOfMonth($tz_map['daylight']['ts']),
            $this->getDay($tz_map['daylight']['ts'])
          );
          $ret_header_str[] = 'END:DAYLIGHT';
        
        }//if
        
        if(isset($tz_map['standard'])){
  
          $ret_header_str[] = 'BEGIN:STANDARD';
          $ret_header_str[] = sprintf('TZOFFSETFROM:%s',$this->getHours($dst_offset,false));
          $ret_header_str[] = sprintf('TZOFFSETTO:%s',$this->getHours($st_offset,false));
          
          if(!empty($dst_map['daylight']['abbr'])){
            $ret_header_str[] = sprintf('TZNAME:%s',$tz_map['standard']['abbr']);
          }//if
          
          $ret_header_str[] = sprintf('DTSTART:1970%sT020000',date('md',$tz_map['standard']['ts']));
          $ret_header_str[] = sprintf(
            'RRULE:FREQ=YEARLY;BYMONTH=%s;BYDAY=%s%s',
            date('n',$tz_map['standard']['ts']),
            $this->getWeekOfMonth($tz_map['standard']['ts']),
            $this->getDay($tz_map['standard']['ts'])
          );
          $ret_header_str[] = 'END:STANDARD';
          
        }//if
        
        $ret_header_str[] = 'END:VTIMEZONE';
      
      }//foreach
    
    }//if
    
    $ret_body_str[] = 'END:VCALENDAR';
    
    return join("\r\n",array_merge($ret_header_str,$ret_body_str));

  }//method
  
  /**
   *  checks if the set start and stop times span exactly a day or exactly multiple days
   *  
   *  @return boolean true if the times span 1 or more full days
   */
  protected function isDaySpan($start,$stop){
  
    // canary...
    if(empty($start) || empty($stop)){ return false; }//if
    if(($start - $stop) == 0){ return false; }//if
  
    $ret_bool = false;
  
    $start_map = getdate($start);
    $stop_map = getdate($stop);
  
    $ret_bool = empty($start_map['hours']) 
      && empty($start_map['minutes'])
      && empty($stop_map['hours']) 
      && empty($stop_map['minutes']);
  
    return $ret_bool;
  
  }//method
  
  /**
   *  checks if the set start and stop times span more than 24 hours
   *  
   *  @return boolean true if the times spans more than 24 hours
   */
  protected function isMultiDay($start,$stop){
  
    // canary...
    if(empty($start) || empty($stop)){ return false; }//if
    
    $ret_bool = false;
    $diff = $stop - $start;
    
    if($diff > 0){
    
      $ret_bool = $diff >= 86400;
    
    }//if
    
    return $ret_bool;
  
  }//method
  
  protected function getDayTimeStr($timestamp){
    return empty($timestamp) ? '' : date('Ymd',$timestamp);
  }//method
  
  /**
   *  build an appropriate date string
   *  
   *  @param  integer $timestamp
   *  @param  array $tzid_map if passed in, the offset will be checked to see if we have a UTC timezone
   *  @return string
   */
  protected function getTimeStr($timestamp,$tzid_map = array()){
  
    // canary...
    if(empty($timestamp)){ return ''; }//if
  
    // a normal date has a defined beginning and end time...
    // A Z on the end means UTC: http://www.kanzaki.com/docs/ical/dateTime.html
    
    $date_format = 'Ymd\THis';
    if(!empty($tzid_map) && empty($tzid_map['offset'])){
      $date_format = 'Ymd\THis\Z';
    }//if
    
    return date($date_format,$timestamp);
  
  }//method
  
  /**
   *  build timezone information
   *  
   *  @param  string  $tzid something like Americ/Denver or UTC
   *  @return array all the assembled tz info found
   */
  protected function getTzInfo($tzid){
  
    $ret_map = array();
    $ret_map['offset'] = 0;
    $ret_map['tzid'] = $tzid;
    
    try{
      
      $tz = new DateTimeZone($tzid);
      $ret_map['offset'] = (int)$tz->getOffset(new DateTime());
      
      $year = date('Y');
      
      /* 5.3 only
      $transitions = $tz->getTransitions(
        mktime(0,0,0,1,1,$year),
        mktime(23,59,59,12,31,$year)
      );
      
      if(!empty($transitions)){
      
        $has_dst = $has_std = false;
        foreach($transitions as $transition){
        
          if($transition['isdst']){
          
            $ret_map['daylight'] = $transition;
            $has_dst = true;
          
          }else{
          
            $ret_map['standard'] = $transition;
            $has_std = true;
          
          }//if/else
          
          if($has_dst && $has_std){ break; }//if
          
        }//foreach
      
      }//if */
      
      // 5.2 compatible...
      $transitions = $tz->getTransitions();
      if(!empty($transitions)){
      
        $has_dst = $has_std = false;
        
        foreach($transitions as $key => $transition){
      
          if(mb_strpos($transition['time'],$year) === 0){
            
            if($transition['isdst']){
            
              $ret_map['daylight'] = $transition;
              $has_dst = true;
            
            }else{
            
              $ret_map['standard'] = $transition;
              $has_std = true;
            
            }//if/else
            
            if($has_dst && $has_std){ break; }//if
            
          }//if
        
        }//foreach
        
      }//if
      
    }catch(Exception $e){}//try/catch
    
    if(empty($ret_map['standard'])){
      $ret_map['standard'] = array('abbr' => $tzid, 'offset' => $ret_map['offset'], 'isdst' => false, 'ts' => 0);
    }//if
    
    return $ret_map;
  
  }//method
  
  /**
   *  gets the 2 letter abbreviation name of a day of the week, uppercased, handy for iCal stuff
   *  
   *  @param  integer $timestamp  the timestamp to use to get the day
   *  @return string  something like 'SU' for 'Sunday'
   */
  protected function getDay($timestamp){
    return mb_strtoupper(mb_substr(date('D',$timestamp),0,2));
  }//method
  
  /**
   *  format a TEXT type according to ical spec
   *  
   *  http://www.ietf.org/rfc/rfc2445.txt 4.1 says that text should not be longer than
   *  75 octets (see also: http://drupal.org/node/84740#comment-449178)   
   *      
   *  @link http://www.kanzaki.com/docs/ical/text.html 
   *  @link http://www.kanzaki.com/docs/ical/summary.html
   *  @link http://www.kanzaki.com/docs/ical/description.html
   *  
   *  @param  string  $val  the value to be formatted for ical
   *  @return string  the formatted $val
   */
  protected function getFormatted($val){
  
    // canary...
    if(empty($val)){ return ''; }//if
  
    $width = 65; // spec says 75 but we want to be safe
  
    $val = str_replace(
      array('\\',"\r","\n",',',';'),
      array('\\\\','',"\\n\n",'\,','\;'),
      $val
    );
  
    $val = wordwrap($val,$width,"\n",true);
    $lines = explode("\n",$val);
    
    // create the right formatting for the lines...
    $total_lines = count($lines);
    for($i = 1; $i < $total_lines ;$i++){
      $lines[$i] = sprintf(' %s',$lines[$i]);
    }//for
    
    $val = join("\r\n",$lines);
    return $val;
  
  }//method
  
  /**
   *  takes seconds and converts them into +/-HH:MM
   *  
   *  this is mostly handy for turning tz offsets in seconds into tz offset in hours
   *      
   *  @param  integer $secs the seconds to convert
   *  @param  boolean $include_colon  true to separate hours from minutes with :   
   *  @return a plus or minus HH:MM   
   */        
  protected function getHours($secs,$include_colon = true){
    
    $colon = $include_colon ? ':' : '';
    
    // sanity...
    if(empty($secs)){ return '+00'.$colon.'00'; }//if
  
    $hours = intval(abs(floor($secs / 3600)));
    if($hours <= 0){
      $hours = '00';
    }else if($hours < 10){
      $hours = '0'.$hours;
    }//if/else if
    
    $minutes = intval(abs(($secs % 3600) / 60));
    if($minutes <= 0){
      $minutes = '00';
    }else if($minutes < 10){
      $minutes = '0'.$minutes;
    }//if/else if
    
    return (($secs < 0) ? '-' : '+').$hours.$colon.$minutes;
  
  }//method
  
  /**
   *  gets the week of the month of the timestamp. usually between 1-5
   *  
   *  the weeks start on Sunday, not monday like date('W') returns
   *  
   *  built with help from:
   *    - http://i-code-today.blogspot.com/2009/03/calculating-week-of-month-from-given.html
   *    - http://mybroadband.co.za/vb/archive/index.php/t-66311.html   
   *      
   *  @param  integer $timestamp  the timestamp to use to get the current week of the month
   *  @return integer 1 through 5 inclusive
   */        
  protected function getWeekOfMonth($timestamp){

    $current_date_map = getdate($timestamp);
    $start_date_map = getdate(mktime(0,0,0,$current_date_map['mon'],1,$current_date_map['year']));
    
    $days = ($current_date_map['yday'] - $start_date_map['yday']) + ($start_date_map['wday'] + 1);
    return intval(ceil($days / 7));
    
  }//method

}//class
