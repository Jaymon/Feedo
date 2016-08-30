<?php
/**
 *  feedo php class
 *
 *  provides an interface for outputting atom, json and rss feeds 
 *  
 *
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 9-6-07
 *  @project  stdlib
 *  @package  feedo  
 ******************************************************************************/     
abstract class feedo_base {
  
  /**#@+
   *  supported feed types
   */        
  const RSS = 'rss';
  const ATOM = 'atom';
  const ICAL = 'ical';
  /**#@-*/
  
  /**
   *  holds the key/value mapping for different tags of the feed
   *  
   *  @var  array
   */
  protected $field_map = array();
  
  /**
   *  set the $val into $key
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return mixed return $val
   */
  function setField($key,$val){
    $this->field_map[$key] = $val;
    return $this->field_map[$key];
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  function hasField($key){ return !empty($this->field_map[$key]); }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  function existsField($key){ return array_key_exists($key,$this->field_map); }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  function getField($key,$default_val = null){
    return $this->existsField($key) ? $this->field_map[$key] : $default_val;
  }//method
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return mixed the value of key before it was removed
   */
  function killField($key){
    $ret_val = null;
    if($this->hasField($key)){
      $ret_val = $this->field_map[$key];
      unset($this->field_map[$key]);
    }//if
    return $ret_val;
  }//method
  
  /**
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $key  the name
   *  @param  string  $val  the value to compare to the $key's set value
   *  @return boolean
   */
  function isField($key,$val){
    $ret_bool = false;
    if($this->existsField($key)){
      $ret_bool = $this->getField($key) == $val;
    }//if
    return $ret_bool;
  }//method
  
  /**
   *  set a field with the given namespace
   *  
   *  @since  1-5-11
   *  @param  string  $namespace  the namespace the $key will live under
   *  @param  string  $key  the field's key
   *  @param  mixed $val  the $key's value               
   *  @return mixed return $val
   */
  public function setNamespaceField($namespace,$key,$val){
  
    if(!isset($this->field_map['namespaces'])){
      $this->field_map['namespaces'] = array();
    }//if
  
    if(!isset($this->field_map['namespaces'][$namespace])){
      $this->field_map['namespaces'][$namespace] = array();
    }//if
  
    $this->field_map['namespaces'][$namespace][$key] = $val;
  
    return $val;
  
  }//method
  
  public function hasNamespaceFields(){ return !empty($this->field_map['namespaces']); }//method
  public function getNamespaceFields(){ return $this->field_map['namespaces']; }//method
  
  function setTitle($val){ return $this->setField('title',$val); }//method
  function getTitle($default_val = ''){ return $this->getField('title',$default_val); }//method
  function hasTitle(){ return $this->hasField('title'); }//method
  
  function setDesc($val){ return $this->setField('desc',$val); }//method
  function getDesc($default_val = ''){ return $this->getField('desc',$default_val); }//method
  function hasDesc(){ return $this->hasField('desc'); }//method
  
  function setLink($val){ return $this->setField('link',$val); }//method
  function getLink(){ return $this->getField('link',''); }//method
  function hasLink(){ return $this->hasField('link'); }//method
  
  /**
   *  corresponds to the BEGIN:VEVENT UID in icalendar
   */
  function setId($val){ return $this->setField('id',$val); }//method
  function getId(){ return $this->getField('id',''); }//method
  function hasId(){ return $this->hasField('id'); }//method
  
  function setTimestamp($val){ return $this->setField('timestamp',$val); }//method
  function getTimestamp(){ return $this->getField('timestamp',''); }//method
  function hasTimestamp(){ return $this->hasField('timestamp'); }//method
  
  /**
   *  set the author information
   *  
   *  @param  string  $name
   *  @param  string  $url   
   *  @param  string  $email
   *  @return array()
   */
  function setAuthor($name,$url = '',$email = ''){
    if(empty($name)){ return array(); }//if
    $author_map = array('name' => $name, 'email' => $email, 'url' => $url);
    return $this->setField('author',$author_map);
  }//method
  function getAuthor(){ return $this->getField('author',array()); }//method
  function hasAuthor(){ return $this->hasField('author'); }//method
  
  /**
   *  corresponds to the TZID in an icalendar event
   */
  function setTzid($val){ return $this->setField('tzid',$val); }//method
  function getTzid(){ return $this->getField('tzid',''); }//method
  function hasTzid(){ return $this->hasField('tzid'); }//method
  
  /**
   *  append a tag to this instance
   *  
   *  @param  string  $val  the tag name
   *  @param  string  $url  the url where the tag can be found         
   *  @return array the tag list
   */
  function appendTag($tag,$url = ''){
    $tag_list = $this->getField('tag',array());
    $tag_list[] = array('url' => $url, 'tag' => $tag);
    return $this->setField('tag',$tag_list);
  }//method
  function getTags(){ return $this->getField('tag',array()); }//method
  function hasTags(){ return $this->hasField('tag'); }//method
  
  /**
   *  output the saved data to the given feed_type.
   *  
   *  this will output the set info to the given feed type
   *  
   *  @param  string  $feed_type  see {@link type()} for another way to set this
   *  @return string  the given feed in one giant string ready to be output to the screen
   */
  ///abstract function out($type = '');

}//class
