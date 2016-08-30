<?php
/**
 *  feedo_out_kml php class
 *
 *  actually output an kml feed
 *  
 *  @link http://en.wikipedia.org/wiki/Keyhole_Markup_Language
 *  
 *  @link http://placesurf.com/
 *  @link http://code.google.com/apis/kml/documentation/kml_tut.html 
 *  
 *  validator: http://www.kmlvalidator.com/home.htm
 *    
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-11-11
 *  @package  feedo  
 ******************************************************************************/
class feedo_out_kml extends feedo_out_interface {
  
  /**
   *  do any init stuff that should be done for the child classes   
   */
  protected function start(){}//method
  
  /**
   *  return the feed's content type
   *  
   *  @return string
   */
  function getContentType(){ return 'application/vnd.google-earth.kml+xml'; }//method
  
  /**
   *  return the feed's extension (eg, ics for ical, or xml for rss)      
   *  
   *  @return string
   */
  function getExtension(){ return 'kml'; }//method
  
  /**
   *  return the feed's body/content
   *  
   *  @return string  the rendered feed ready to be outputted to the browser
   */
  function getBody(){

    $feedo = $this->getFeed();

    $ret_str = array();
    $ret_str[] = sprintf('%s?xml version="1.0" encoding="%s"?%s','<',$feedo->getCharset(),'>');
    
    $ret_str[] = '<kml xmlns="http://www.opengis.net/kml/2.2">';
    
    $ret_str[] = ' <Folder>';
    
    $ret_str[] = sprintf('  <name>%s</name>',$this->getSafe($feedo->getTitle()));
    $ret_str[] = sprintf('  <description>%s</description>',$this->getSafe($feedo->getDesc()));
    
    foreach($feedo->getItems() as $feedo_item){
    
      $location_map = $feedo_item->getLocation();
    
      // canary, only add a kml Placemark if there is a geo location...
      if(empty($location_map['point'])){ continue; }//if
    
      $ret_str[] = '  <Placemark>';
      $ret_str[] = sprintf('    <name>%s</name>',$this->getSafe($feedo_item->getTitle()));
      
      $desc = array();
      
      if($feedo_item->hasLink()){
        $desc[] = $feedo_item->getLink();
        $desc[] = '';
      }//if
      
      $desc[] = $feedo_item->getDesc();
      
      $ret_str[] = sprintf('    <description>%s</description>',$this->getCData(join(PHP_EOL,$desc)));
      
      ///$time_str = $this->getTimeStr($feedo_item->getTimestamp());
      ///$ret_str[] = sprintf('    <updated>%s</updated>',$time_str);
      ///$ret_str[] = sprintf('    <published>%s</published>',$time_str);
      
      $ret_str[] = '    <visibility>1</visibility>';
      
      $ret_str[] = '    <Point>';
      $ret_str[] = '      <extrude>1</extrude>';
      $ret_str[] = '      <altitudeMode>relativeToGround</altitudeMode>';
      
      // per http://service.kmlvalidator.com/ets/ogc-kml/2.2/#Geometry-Coordinates
      // it goes (lon,lat[,hgt])
      $ret_str[] = sprintf(
        '      <coordinates>%s,%s,0</coordinates>',
        (float)$location_map['point'][1],
        (float)$location_map['point'][0]
      );
      
      $ret_str[] = '    </Point>';
      
      $ret_str[] = '  </Placemark>';
    
    }//foreach
    
    $ret_str[] = ' </Folder>';
    $ret_str[] = '</kml>';
    
    return join(PHP_EOL,$ret_str);
    
  }//method
  
  protected function getTimeStr($timestamp){
    return empty($timestamp) ? '' : date('D, d M Y H:i:s O',$timestamp);
  }//method

}//class
