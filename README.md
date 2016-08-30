# Feedo

**NOTE** - This code is way way way out of date, it was the code that Plancast used to generate all its feeds (eg, rss, ical). With the death of Plancast I've been feeling nostalgic and going through the old codebase and I figured I would upload some of the more self contained parts of the codebase that weren't already public.


## What is Feedo?

You can use it to map random data to a standardized generic feed item that you could then use to generate different feed types like RSS or iCal.


### How do I use it?

This is roughly how we did it at Plancast, using Symphony 1.5 or something like that:

```php
$feedo = new feedo($request->getParameter('feed'), $response->getCharset());
$is_valid_feed = $feedo->isValidType();

if($is_valid_feed)
{ 
  // used for all the unique ids of the feed and the feed items...
  $host = $request->getHost();

  // set the feed attributes...
  $feedo->setTitle("the feed title");
  $feedo->setDesc("the feed description);
  $feedo->setLink("http://site.com");
  $feedo->setId("the feed id");
  $feedo->setFeedLink("http://the.permalink.url.com/for/feed");

  $timezone = "UTC"

  // we need to get the stuff we found into the right format...
  if(!empty($plans))
  {
    foreach($plans as $key => $plan)
    { 
      // create a Feedo instance we can populate
      $feedo_item = $feedo->getItemInstance();

      $feedo_item->setTimestamp(strtotime($plan->getCreatedAt()));
      $feedo_item->setTitle($plan->getWhatString());
      $feedo_item->setLink($plan->getPermalink());
      $feedo_item->setId(sprintf('a%s@%s',$plan->getId(),$host));
      $feedo_item->setTzid($timezone);
      $feedo_item->setStart($plan->getWhenStart());
      $feedo_item->setStop($plan->getWhenStop());

      $item_desc = array();
      if($plan->hasDescription())
      {
        $item_desc[] = $plan->getDescription();
      }//if

      $item_desc[] = '';
      $item_desc[] = sprintf('When: %s',$plan->getWhenString());

      $place_name = $plan->getWhereString();
      if($place = $plan->getPlace())
      {
        if($item_point = $place->getPoint())
        {
          $feedo_item->setLocation(
            $place->getName(),
            $item_point->getArray()
          );

        }//if

        $item_desc[] = sprintf('Where: %s',$place->getName());

      }//if

      $item_desc[] = sprintf('Attendees: %s',$plan->getAttendeeCount());
      $item_desc[] = sprintf('Comments: %s',$plan->getCommentCount());
      $feedo_item->setDesc(join(PHP_EOL,$item_desc));

      if($plan->getUser())
      {
        $feedo_item->setAuthor(
          $plan->getUser()->getName(),
          Url::get($plan->getUser()->getRoute())
        );

      }//if

      if($plan->hasCategories())
      {
        foreach($plan->getCategories() as $category)
        {
          $feedo_item->appendTag($category->getTitle(), Url::get($category->getRoute()));
        }//foreach

      }//if

      // add the Feedo item to the feed...
      $feedo->appendItem($feedo_item);

    }//foreach

  }//if

  $feedo_out = $feedo->getOutInstance();

  // set a name the feed can be downloaded as...
  $response->setHttpHeader(
    'Content-Disposition',
    sprintf(
      'inline; filename="plancast-%s.%s"',
      Formatting::path($feedo->getTitle(),50),
      $feedo_out->getExtension()
    )
  );

  $response->setContentType($feedo_out->getContentType());
  $response->setContent($feedo_out->getBody());

}//if
```

You're on your own from here on out :)

