<?php
  require_once './junaio-php-helper-library-master/arel_xmlhelper.class.php';
  $arcgis_url = 'http://arcgis-central.gis.vt.edu/arcgis/rest/services/vtcampusmap/Buildings/MapServer/0';
  $service_url = "http://arc01.gis.vt.edu/arcgis/rest/services/Utilities/Geometry/GeometryServer";
  $label_points_url = "/labelPoints";
  $projection_url = "/project";
  $arcgis_query = "/query?where=1=1&f=json&inSR=102747&outSR=4326";

  ArelXMLHelper::start(NULL, "./arel/index.html", ArelXMLHelper::TRACKING_GPS);

  $req = new HttpRequest($arcgis_url . $arcgis_query, HttpRequest::METH_GET);
  $req->send();

  if($req->getResponseCode() == 200) {
    $json = json_decode($req->getResponseBody());
    $spatialReference = $json->spatialReference->wkid;

    $id = 0;
    foreach ($json->features as $building) {
      $name = $building->attributes->NAME;
      $geometry = json_encode(array($building->geometry));

      if (empty($name) == FALSE) {
        $req = new HttpRequest($service_url . $label_points_url, HttpRequest::METH_POST);
        $req->addPostFields(array('polygons' => $geometry, 'sr' => $spatialReference, 'f' => 'json'));
        $req->send();

        $centroid = json_decode($req->getResponseBody());
        $latitude = $centroid->labelPoints[0]->y;
        $longitude = $centroid->labelPoints[0]->x;

        ArelXMLHelper::outputObject(ArelXMLHelper::createLocationBasedPOI(
          $id,
          $name,
          array($latitude, $longitude, 0),
          './resources/thumb_a1.png',
          './resources/icon_a1.png',
          $name,
          array()
        ));
      }
    }
  }
?>
