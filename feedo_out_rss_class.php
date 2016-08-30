<?php
/**
 *  feedo_out_rss php class
 *
 *  actually output an rss feed
 *  
 *  @link http://en.wikipedia.org/wiki/RSS
 *  @link http://cyber.law.harvard.edu/rss/rss.html
 *  
 *  validator: http://validator.w3.org/feed/  
 *  
 *  big list of namespaces: http://validator.w3.org/feed/docs/howto/declare_namespaces.html  
 *    
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-16-10
 *  @package  feedo  
 ******************************************************************************/
class feedo_out_rss extends feedo_out_interface {
  
  /**
   *  do any init stuff that should be done for the child classes   
   */
  protected function start(){}//method
  
  /**
   *  return the feed's content type
   *  
   *  @return string
   */
  function getContentType(){ return 'application/rss+xml'; }//method
  
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
    
    // build the rss declaration with all the namespaces we might use...
    $ret_str[] = '<rss version="2.0"';
    
    if($feedo->hasNamespaces()){
      foreach($feedo->getNamespaces() as $namespace => $namespace_url){
        $ret_str[] = sprintf('  xmlns:%s="%s"',$this->getSafe($namespace),$this->getSafe($namespace_url));
      }//foreach
    }//if
    
    $ret_str[] = '  xmlns:atom="http://www.w3.org/2005/Atom"';
    $ret_str[] = '  xmlns:dc="http://purl.org/dc/elements/1.1/"'; // http://en.wikipedia.org/wiki/Dublin_Core
    $ret_str[] = '  xmlns:dcterms="http://purl.org/dc/terms/"';
    $ret_str[] = '  xmlns:georss="http://www.georss.org/georss">';
    
    $ret_str[] = ' <channel>';
    $ret_str[] = sprintf('  <title>%s</title>',$this->getSafe($feedo->getTitle()));
    $ret_str[] = sprintf('  <link>%s</link>',$this->getSafe($feedo->getLink()));
    $ret_str[] = sprintf('  <description>%s</description>',$this->getSafe($feedo->getDesc()));
    $ret_str[] = sprintf(
      '  <atom:link href="%s" rel="self" type="%s" />',
      $this->getSafe($feedo->getFeedLink()),
      $this->getContentType()
    );
    
    if($feedo->hasTimestamp()){
      $ret_str[] = sprintf('  <pubDate>%s</pubDate>',$this->getTimeStr($feedo->getTimestamp()));
    }//if
    
    $ret_str[] = sprintf('  <generator>%s</generator>',$this->getSafe($feedo->getName()));
    //<language>en-us</language>
    //<lastBuildDate>Tue, 10 Jun 2003 09:41:01 GMT</lastBuildDate>
    //<docs>http://blogs.law.harvard.edu/tech/rss</docs>
    
    if($feedo->hasAuthor()){
      $author_map = $feedo->getAuthor();
      if(!empty($author_map['name'])){
        $ret_str[] = sprintf('  <managingEditor>%s</managingEditor>',$this->getSafe($author_map['name']));
      }//if
      if(!empty($author_map['email'])){
        $ret_str[] = sprintf('  <webMaster>%s</webMaster>',$this->getSafe($author_map['email']));
      }//if
    }//if
    
    foreach($feedo->getItems() as $feedo_item){
      $ret_str[] = '  <item>';
      $ret_str[] = sprintf('    <title>%s</title>',$this->getCData($feedo_item->getTitle()));
      
      if($feedo_item->hasLink()){
        $ret_str[] = sprintf('    <link>%s</link>',$this->getSafe($feedo_item->getLink()));
      }//if
      
      $ret_str[] = sprintf('    <description>%s</description>',$this->getCData($feedo_item->getDesc()));
      $ret_str[] = sprintf('    <pubDate>%s</pubDate>',$this->getTimeStr($feedo_item->getTimestamp()));
      
      if($feedo_item->hasId()){
        $ret_str[] = sprintf('    <guid isPermaLink="false">%s</guid>',$this->getSafe($feedo_item->getId()));
      }//if
      
      if($feedo_item->hasAuthor()){
        $author_map = $feedo_item->getAuthor();
        // author is only for email addresses, which we almost never want to be revealed
        ///$ret_str[] = sprintf('    <author>%s</author>',$this->getSafe($author_map['name']));
        // http://www.rssboard.org/rss-profile#namespace-elements-dublin-creator
        $ret_str[] = sprintf('    <dc:creator>%s</dc:creator>',$this->getSafe($author_map['name']));
      }//if
      
      // set the start and stop using the DC namespace...
      if($feedo_item->hasStart()){
      
        // I'm going to use the dcterms valid because it makes more sense overall...
        // http://web.resource.org/rss/1.0/modules/dcterms/#valid
        $ret_str[] = '    <dcterms:valid>';
        
        // can't use dc:date because it is too much like pubDate...
        // http://purl.org/dc/elements/1.1/coverage
        ///$ret_str[] = sprintf('    <dc:coverage>');
      
        // value is a DCMI period: http://dublincore.org/documents/dcmi-period/
      
        $ret_str[] = sprintf('      start=%s;',date(DATE_ISO8601,$feedo_item->getStart()));
        
        if($feedo_item->hasStop()){
          $ret_str[] = sprintf('      end=%s;',date(DATE_ISO8601,$feedo_item->getStop()));
        }//if
      
        $ret_str[] = '      scheme=W3C-DTF;';
      
        $ret_str[] = '    </dcterms:valid>';
        ///$ret_str[] = '    </dc:coverage>';
        
      }//if
      
      if($feedo_item->hasLocation()){
      
        // value is DCMI point: http://dublincore.org/documents/dcmi-point/
      
        $location_map = $feedo_item->getLocation();
      
        /* 
        // @todo  dc:coverage could totally be removed in favor of the georss:point
        $ret_str[] = '    <dc:coverage>';
        $ret_str[] = sprintf('      name=%s;',$this->getSafe($location_map['name']));
        
        if(!empty($location_map['point'])){
        
          $ret_str[] = sprintf('      north=%s;',(float)$location_map['point'][0]);
          $ret_str[] = sprintf('      east=%s;',(float)$location_map['point'][1]);
          
        }//if
        
        $ret_str[] = '    </dc:coverage>';
        */
        
        // use georss for the location stuff:
        // http://www.georss.org/simple
        if(!empty($location_map['point'])){
          $ret_str[] = sprintf(
            '    <georss:point>%s %s</georss:point>',
            (float)$location_map['point'][0],
            (float)$location_map['point'][1]
          );
        }//if
        
        $ret_str[] = sprintf(
          '    <georss:featureName>%s</georss:featureName>',
          $this->getSafe($location_map['name'])
        );

      }//if
      
      if($feedo_item->hasTags()){
        foreach($feedo_item->getTags() as $tag_map){
          $ret_str[] = sprintf(
            '    <category domain="%s">%s</category>',
            $this->getSafe($tag_map['url']),
            $this->getCData($tag_map['tag'])
          );
        }//foreach
      }//if
      
      // add namespace fields...
      if($feedo_item->hasNamespaceFields()){
        
        foreach($feedo_item->getNamespaceFields() as $namespace => $namespace_field_list){
          
          $namespace = $this->getSafe($namespace);
          
          foreach($namespace_field_list as $namespace_key => $namespace_val){
            
            $namespace_key = $this->getSafe($namespace_key); 
            $namespace_val = $this->getSafe($namespace_val);
            
            $ret_str[] = sprintf(
              '    <%s:%s>%s</%s:%s>',
              $namespace,
              $namespace_key,
              $namespace_val,
              $namespace,
              $namespace_key
            );
          
          }//foreach
        
        }//foreach
      
      }//if
      
      $ret_str[] = '  </item>';
    
    }//foreach
    
    $ret_str[] = ' </channel>';
    $ret_str[] = '</rss>';
    
    return join("\n",$ret_str);
    
  }//method
  
  protected function getTimeStr($timestamp){
    return empty($timestamp) ? '' : date('D, d M Y H:i:s O',$timestamp);
  }//method

}//class
