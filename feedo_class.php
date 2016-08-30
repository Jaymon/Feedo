<?php
/**
 *  feedo php class
 *
 *  provides a common interface for outputting different feed types
 *
 *  @version 0.8
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 9-6-07
 *  @package  feedo  
 ******************************************************************************/     
class feedo extends feedo_base {
  
  const DEFAULT_CHARSET = 'UTF-8';
  const DEFAULT_NAME = 'Feedo Feed';
  
  /**#@+
   *  supported feed types
   *  
   *  these should all be lowercase      
   */
  const TYPE_RSS = 'rss';
  const TYPE_ATOM = 'atom';
  const TYPE_ICAL = 'ical';
  const TYPE_KML = 'kml';
  /**#@-*/
  
  /**
   *  holds all the feed items
   *  @see  item()   
   */     
  protected $item_list = array();
  
  /**
   *  holds information about the feed type
   *  @var  array
   */        
  protected $type_map = '';
  
  /**
   *  holds the namespace names as the key, and their doc url as the value
   *  
   *  @since  1-5-11
   *  @var  array         
   */
  protected $namespace_map = array();
  
  /**
   *  override default constructor
   *  
   *  @param  string  $type one of the TYPE_* class constants
   *  @param  string  $charset  the charset (eg, UTF-8)   
   */        
  function __construct($type = '',$charset = ''){
    
    $this->setType($type);
    $this->setCharset($charset);
    
    $this->type_map = array(
      self::TYPE_RSS => array('out_class' => 'feedo_out_rss'),
      self::TYPE_ATOM => array('out_class' => 'feedo_out_atom'),
      self::TYPE_ICAL => array('out_class' => 'feedo_out_ical'),
      self::TYPE_KML => array('out_class' => 'feedo_out_kml')
    );
      
  }//method
  
  /**
   *  add a namespace to the feed
   *  
   *  this is handy for adding support to rss, though other out interfaces could also
   *  use it if they want
   *  
   *  @link http://www.disobey.com/detergent/2002/extendingrss2/
   *  @link http://feed2.w3.org/docs/howto/declare_namespaces.html
   *   
   *  @since  1-5-11
   *  @param  string  $name the name of the namespace
   *  @param  string  $url  the url where info about the namespace can be used, I've made this
   *                        optional, but it should probably be some value, even though the RSS
   *                        spec doesn't ever check to see if the url is legit, other out interfaces
   *                        might not care at all though. It should end with either # or /      
   *  @return boolean
   */
  public function setNamespace($name,$url = ''){
  
    // canary...
    if(empty($name)){ return false; }//if
  
    $this->namespace_map[$name] = $url;
  
    return true;
  
  }//method
  
  public function hasNamespace($name){ return empty($name) ? false : isset($this->namespace_map[$name]); }//method
  public function hasNamespaces(){ return !empty($this->namespace_map); }//method
  public function getNamespaces(){ return $this->namespace_map; }//method
  
  function setType($val){ return $this->setField('type',mb_strtolower($val)); }//method
  function getType(){ return $this->getField('type',''); }//method
  function hasType(){ return $this->hasField('type'); }//method
  function isType($val){ return $this->isField('type',mb_strtolower($val)); }//method
  
  /**
   *  encoding is the charset (eg, UTF-8)
   */
  function setCharset($val){ return $this->setField('charset',$val); }//method
  function getCharset(){ return $this->getField('charset',self::DEFAULT_CHARSET); }//method
  function hasCharset(){ return $this->hasField('charset'); }//method
  
  function setName($val){ return $this->setField('name',$val); }//method
  function getName(){ return $this->getField('name',self::DEFAULT_NAME); }//method
  function hasName(){ return $this->hasField('name'); }//method
  
  /**
   *  set the link to the actual feed that this instance is rendering
   *  
   *  required by RSS
   *  
   *  @since  1-5-11
   *  @param  string  $val  a url (presumably with http://) where you can get this 
   *                        same feed that's currently being rendered with this object instance
   */
  function setFeedLink($val){ return $this->setField('feed_link',$val); }//method
  function getFeedLink(){ return $this->getField('feed_link',''); }//method
  function hasFeedLink(){ return $this->hasField('feed_link'); }//method
  
  /**
   *  get an instance of the class that can display the given feed's type
   *  
   *  @return feedo_out_interface actually, a child that extends feedo_out_interface
   */
  function getOutInstance(){
  
    $type = $this->getType();
    $class = $this->type_map[$type]['out_class'];
    return new $class($this);
  
  }//method
  
  /**
   *  returns whether the $type is a supported output type for this class
   *  
   *  @param  string  $type  makes sure the $type corresponds to one of the supported type constants, if null is 
   *                         passed in then it will use the class's internally set type   
   *  @return boolean
   */
  function isValidType($type = ''){
    
    // canary...
    if(empty($type)){
      $type = $this->getType();
    }else{
      $orig_type = $this->getType();
      $this->setType($type);
      $type = $this->getType();
      $this->setType($orig_type);
    }//if/else
    
    return isset($this->type_map[$type]);
    
  }//method
  
  /**
   *  get a new item instance
   *  
   *  @return feedo_item  an item instance that can be appended to this feed with {@link appendItem()}
   */
  function getItemInstance(){ return new feedo_item(); }//method
  
  /**
   *  append an item to the feed
   *  
   *  @param  feedo_item  $val  teh feedo_item instance         
   */
  function appendItem(feedo_item $val){
    $this->item_list[] = $val;
  }//method
  
  /**
   *  return the feed's items
   *  
   *  @return array         
   */
  function getItems(){ return $this->item_list; }//method
  
  
  /**
   *  output the saved data to the given feed_type.
   *  
   *  this will output the set info to the given feed type      
   *  
   *  echo the given feed in one giant string to STD_OUT      
   *  
   *  @param  string  $feed_type  see {@link setType()} for another way to set this
   *  @throws UnexpectedValueException
   */
  function out($type = ''){
  
    if(!empty($type)){ $this->setType($type); }//if
    if(!$this->isValidType()){
      throw new UnexpectedValueException('$type is not a TYPE_* constant value');
    }//if
    
    $type = $this->getType();
    $feedo_out = $this->getOutInstance();
    
    // send the header...
    if(!headers_sent()){
      header(
        sprintf(
          'Content-Type: %s; charset=%s',
          $feedo_out->getContentType(),
          $this->getCharset()
        )
      );
      
      // specify what the file will be called if it is saved...
      // http://en.wikipedia.org/wiki/MIME#Content-Disposition
      if($this->hasTitle()){
        header(
          sprintf(
            'Content-Disposition: inline; filename="%s.%s"',
            $this->getTitle(),
            $feedo_out->getExtension()
          )
        );
      }//if
      
    }//if
  
    echo $feedo_out->getBody();
  
  }//method

}//class
