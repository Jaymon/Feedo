<?php
/**
 *  feedo_out_interface php class
 *
 *  provides an interface for actually outputting a feed so that more feed types
 *  can be easily outputted in the future
 *    
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-16-10
 *  @package  feedo  
 ******************************************************************************/     
abstract class feedo_out_interface {
  
  /**
   *  hold the feed instance that will be outputted
   *  @var  feedo
   */
  protected $feedo = null;
  
  /**
   *  hold the charset for the output
   *  @var  string
   */
  protected $charset = '';
  
  /**
   *  override default constructor
   *  
   *  this will call the implemented start method
   *  
   *  @param  feedo $feed the feed that this instance will use
   *  @param  string  $charset  the charset to use   
   */
  final function __construct(feedo $feed){
  
    $this->setFeed($feed);
    
    $this->start();
  
  }//method
  
  final protected function setFeed($val){ return $this->feedo = $val; }//method
  final function getFeed(){ return $this->feedo; }//method
  final function hasFeed(){ return !empty($this->feedo); }//method
  
  /**
   *  get an entity safe value
   *  
   *  @param  string  $val  the value to make safe
   *  @return string  $val with all entities encoded
   */
  protected function getSafe($val){
    $charset = $this->getFeed()->getCharset();
    $val = mb_convert_encoding($val,$charset);  
    return htmlspecialchars($val,ENT_COMPAT,$charset,false);
  }//method
  
  /**
   *  used to allow html and entities in certain XML based feeds
   *  
   *  @param  string  $val
   *  @return string  $val wrapped in the cdata whatever
   */
  protected function getCData($val){
    return sprintf('<![CDATA[%s]]>',$val);
  }//method
  
  /**
   *  do any init stuff that should be done for the child classes   
   */
  protected abstract function start();
  
  /**
   *  return the feed's content type
   *  
   *  @return string
   */
  abstract function getContentType();
  
  /**
   *  return the feed's extension (eg, ics for ical, or xml for rss)
   *  
   *  this should not have the period (eg, .ext) but just the extension (eg, ext)            
   *  
   *  @return string
   */
  abstract function getExtension();
  
  /**
   *  return the feed's body/content
   *  
   *  @return string  the rendered feed ready to be outputted to the browser
   */
  abstract function getBody();

}//class
