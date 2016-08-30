<?php
/**
 *  feedo_out_atom php class
 *
 *  http://en.wikipedia.org/wiki/Atom_(standard)
 *  
 *  the best spec description: http://www.atomenabled.org/developers/syndication/
 *  official spec: http://atompub.org/rfc4287.html   
 *    
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-16-10
 *  @package  feedo  
 ******************************************************************************/
class feedo_out_atom extends feedo_out_interface {
  
  /**
   *  do any init stuff that should be done for the child classes   
   */
  protected function start(){}//method
  
  /**
   *  return the feed's content type
   *  
   *  @return string
   */
  function getContentType(){ return 'application/atom+xml'; }//method
  
  /**
   *  return the feed's extension (eg, ics for ical, or xml for rss)      
   *  
   *  @return string
   */
  function getExtension(){ return 'xml'; }//method
  
  /**
   *  return the feed's body/content
   *  
   *  @return string  the rendered feed ready to be outputted to the browser
   */
  function getBody(){
  
    $feedo = $this->getFeed();

    $ret_str = array();
    $ret_str[] = sprintf('%s?xml version="1.0" encoding="%s"?%s','<',$feedo->getCharset(),'>');
    $ret_str[] = '<feed xmlns="http://www.w3.org/2005/Atom">';
    $ret_str[] = sprintf(' <title type="html">%s</title>',$this->getSafe($feedo->getTitle()));
    $ret_str[] = sprintf(' <subtitle type="html">%s</subtitle>',$this->getSafe($feedo->getDesc()));
    $ret_str[] = sprintf(' <link href="%s" rel="alternate"/>',$this->getSafe($feedo->getLink()));
    $ret_str[] = sprintf(' <id>%s</id>',$this->getSafe($feedo->getId()));
    $ret_str[] = sprintf(' <generator>%s</generator>',$this->getSafe($feedo->getName()));
    
    if($feedo->hasTimestamp()){
      $ret_str[] = sprintf(' <updated>%s</updated>',$this->getTimeStr($feedo->getTimestamp()));
    }//if
    
    if($feedo->hasAuthor()){
      $ret_str[] = ' <author>';
      
      $author_map = $feedo->getAuthor();
      if(!empty($author_map['name'])){
        $ret_str[] = sprintf('  <name>%s</name>',$this->getSafe($author_map['name']));
      }//if
      if(!empty($author_map['url'])){
        $ret_str[] = sprintf('  <uri>%s</uri>',$this->getSafe($author_map['url']));
      }//if
      if(!empty($author_map['email'])){
        $ret_str[] = sprintf('  <email>%s</email>',$this->getSafe($author_map['email']));
      }//if
      
      $ret_str[] = ' </author>';
    }//if
    
    foreach($feedo->getItems() as $feedo_item){
    
      $ret_str[] = ' <entry>';
      $ret_str[] = sprintf('  <title type="html">%s</title>',$this->getSafe($feedo_item->getTitle()));
      
      if($feedo_item->hasLink()){
        $ret_str[] = sprintf('  <link rel="alternate" href="%s"/>',$this->getSafe($feedo_item->getLink()));
      }//if
      
      if($feedo_item->hasId()){
        $ret_str[] = sprintf('  <id>%s</id>',$this->getSafe($feedo_item->getId()));
      }//if
      
      $ret_str[] = sprintf('  <summary type="html">%s</summary>',$this->getSafe($feedo_item->getDesc()));
      $ret_str[] = sprintf('  <updated>%s</updated>',$this->getTimeStr($feedo_item->getTimestamp()));
    
      if($feedo_item->hasAuthor()){
        $ret_str[] = '  <author>';
        $author_map = $feedo_item->getAuthor();
        if(!empty($author_map['name'])){
          $ret_str[] = sprintf('   <name>%s</name>',$this->getSafe($author_map['name']));
        }//if
        if(!empty($author_map['url'])){
          $ret_str[] = sprintf('   <uri>%s</uri>',$this->getSafe($author_map['url']));
        }//if
        if(!empty($author_map['email'])){
          $ret_str[] = sprintf('   <email>%s</email>',$this->getSafe($author_map['email']));
        }//if
        $ret_str[] = '  </author>';
      }//if
    
      if($feedo_item->hasTags()){
        foreach($feedo_item->getTags() as $tag_map){
          $ret_str[] = sprintf(
            '  <category scheme="%s" term="%s"/>',
            $this->getSafe($tag_map['url']),
            $this->getSafe($tag_map['tag'])
          );
        }//foreach
      }//if
    
      $ret_str[] = ' </entry>';
    
    }//foreach
    
    $ret_str[] = '</feed>';
    return join("\n",$ret_str);
  
  }//method
  
  protected function getTimeStr($timestamp){
    return empty($timestamp) ? '' : date('Y-m-d\TH:i:sP',$timestamp);
  }//method

}//class
