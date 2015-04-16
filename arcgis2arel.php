<?php
  $arcgis_url = 'http://arcgis-central.gis.vt.edu/arcgis/rest/services/vtcampusmap/Buildings/MapServer/0';
  $service_url = "http://arc01.gis.vt.edu/arcgis/rest/services/Utilities/Geometry/GeometryServer";
  $label_points_url = "/labelPoints";
  $projection_url = "/project";
  $arcgis_query = "/query?where=1=1&f=json&inSR=102747&outSR=4326";

  $req = new HttpRequest($arcgis_url . $arcgis_query, HttpRequest::METH_GET);
  $req->send();

  class SimpleXMLExtended extends SimpleXMLElement {
    public function addCData($cdata_text) {
      $node = dom_import_simplexml($this); 
      $no   = $node->ownerDocument; 
      $node->appendChild($no->createCDATASection($cdata_text)); 
    } 
  }

  $xml = new SimpleXMLExtended('<xml/>');
  $results = $xml->addChild('results');
  $results->addAttribute('trackingurl', 'GPS');

  if($req->getResponseCode() == 200) {
    $json = json_decode($req->getResponseBody());
    $spatialReference = $json->spatialReference->wkid;

    $id = 0;
    foreach ($json->features as $building) {
      $name = $building->attributes->NAME;
      $geometry = json_encode(array($building->geometry));

      if (!empty(trim($name))) {
        $req = new HttpRequest($service_url . $label_points_url, HttpRequest::METH_POST);
        $req->addPostFields(array('polygons' => $geometry, 'sr' => $spatialReference, 'f' => 'json'));
        $req->send();
        $centroid = json_decode($req->getResponseBody());
        $latitude = $centroid->labelPoints[0]->y;
        $longitude = $centroid->labelPoints[0]->x;
        $obj = $results->addChild('object');
        $obj->addAttribute('id', $id);
        $title = $obj->addChild('title');
        $title->addCData($name);
        $thumbnail = $obj->addChild('thumbnail');
        $thumbnail->addCData('http://dev.junaio.com/publisherDownload/thumb_a1.png');
        $icon = $obj->addChild('icon');
        $icon->addCData('http://dev.junaio.com/publisherDownload/icon_a1.png');
        $location = $obj->addChild('location');
        $lat = $location->addChild('lat', $latitude);
        $lon = $location->addChild('lon', $longitude);
        $alt = $location->addChild('alt', 0);
      }
    }
  }

  $xml->saveXML('arel.xml');
  echo 'done';
?>
