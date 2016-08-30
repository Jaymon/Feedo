<?php
/**
 *  feedo php class
 *
 *  an item that can be appended to the feed
 *
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-15-10
 *  @package  feedo  
 ******************************************************************************/     
class feedo_item extends feedo_base {
  
  /**#@+
   *  the different frequency values for the frequency() function
   */     
  const FREQ_DAILY = 'DAILY';
  const FREQ_WEEKLY = 'WEEKLY';
  const FREQ_MONTHLY = 'MONTHLY';
  const FREQ_YEARLY = 'YEARLY';
  /**#@-*/
  
  function setType($val){ return $this->setField('type',mb_strtolower($val)); }//method
  function getType(){ return $this->getField('type',''); }//method
  function hasType(){ return $this->hasField('type'); }//method
  
  function setEncoding($val){ return $this->setField('encoding',$val); }//method
  function getEncoding(){ return $this->getField('encoding',''); }//method
  function hasEncoding(){ return $this->hasField('encoding'); }//method
  
  /**
   *  corresponds to the BEGIN:VEVENT DTSTART in icalendar
   */
  function setStart($val){ return $this->setField('start',$val); }//method
  function getStart(){ return $this->getField('start',''); }//method
  function hasStart(){ return $this->hasField('start'); }//method
  
  /**
   *  corresponds to the BEGIN:VEVENT DTEND in icalendar
   */
  function setStop($val){ return $this->setField('stop',$val); }//method
  function getStop(){ return $this->getField('stop',''); }//method
  function hasStop(){ return $this->hasField('stop'); }//method
  
  /**
   *  set the location of the item
   *  
   *  @since  1-31-11
   *  @param  string  $name the name of the location
   *  @param  array $point  the point array($lat,$long)
   *  @param  string  $url  the permalink of the location
   */
  function setLocation($name,array $point = array(),$url = ''){
  
    $location_map = array('name' => $name, 'point' => $point, 'url' => $url);
    return $this->setField('location',$location_map);
    
  }//method
  function getLocation(){ return $this->getField('location',''); }//method
  function hasLocation(){ return $this->hasField('location'); }//method
  
  /**
   *  corresponds to the BEGIN:VEVENT RRULE:FREQ in icalendar
   *  
   *  @param  string  $val  one of the FREQ_* constants      
   */
  function setFrequency($val){ return $this->setField('frequency',$val); }//method
  function getFrequency(){ return $this->getField('frequency',''); }//method
  function hasFrequency(){ return $this->hasField('frequency'); }//method
  function isFrequency($val){ return $this->isField('frequency',$val); }//method
  function isWeekly(){ return $this->isFrequency(self::FREQ_WEEKLY); }//method
  function isYearly(){ return $this->isFrequency(self::FREQ_YEARLY); }//method

}//class
