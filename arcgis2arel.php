<?php
  $arcgis_url = 'http://arcgis-central.gis.vt.edu/arcgis/rest/services/vtcampusmap/Buildings/MapServer/0';
  $service_url = "http://arc01.gis.vt.edu/arcgis/rest/services/Utilities/Geometry/GeometryServer";
  $label_points_url = "/labelPoints";
  $projection_url = "/project";
  $arcgis_query = "/query?where=1=1&f=json";

  $req = new HttpRequest($arcgis_url . $arcgis_query, HttpRequest::METH_GET);
  $req->send();

  if($req->getResponseCode() == 200) {
    $json = json_decode($req->getResponseBody());
    $spatialReference = $json->spatialReference->wkid;

    $x = 0;
    foreach ($json->features as $building) {
      $name = $building->attributes->NAME;
      $geometry = json_encode(array($building->geometry));

      if (!empty(trim($name))) {
        $req = new HttpRequest($service_url . $label_points_url, HttpRequest::METH_POST);
        $req->addPostFields(array('polygons' => $geometry, 'sr' => $spatialReference, 'f' => 'json'));
        $req->send();

        $centroid = json_decode($req->getResponseBody());
        $geometries = array('geometryType' => 'esriGeometryPoint', 'geometries' => $centroid->labelPoints);

        $req = new HttpRequest($service_url . $projection_url, HttpRequest::METH_GET);
        $req->addQueryData(array('geometries' => json_encode($geometries), 'inSR' => $spatialReference, 'outSR' => 4326, 'f' => 'json'));
        $req->send();

        $latlong = json_decode($req->getResponseBody());
        echo "<p>{$name} : {$latlong->geometries[0]->x}, {$latlong->geometries[0]->y}<p>";
      }
    }
  }
?>
