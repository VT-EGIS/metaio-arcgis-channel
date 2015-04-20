<?php
  require_once './junaio-php-helper-library-master/arel_xmlhelper.class.php';

  function make_http_request($url, $method, $fields) {
    $curl_handle = curl_init();

    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl_handle, $method, true);

    if ($method == CURLOPT_HTTPGET) {
      $url .= http_build_query($fields);
    } else {
      curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $fields);
    }

    curl_setopt($curl_handle, CURLOPT_URL, $url);

    $json = json_decode(curl_exec($curl_handle));

    curl_close($curl_handle);

    return $json;
  }

  $arcgis_url = 'http://arcgis-central.gis.vt.edu/arcgis/rest/services/vtcampusmap/Buildings/MapServer/0';
  $geometry_service_url = "http://arc01.gis.vt.edu/arcgis/rest/services/Utilities/Geometry/GeometryServer";
  $label_points_url = "/labelPoints";
  $projection_url = "/project";
  $arcgis_query_url = "/query?";
  $inSR = 102747;
  $outSR = 4326;
  $where_clause = "BLDG_NUM IS NOT NULL AND BLDG_NUM <> ' ' AND URL IS NOT NULL";
  $outFields = "NAME,URL";

  ArelXMLHelper::start(NULL, "./arel/index.html", ArelXMLHelper::TRACKING_GPS);

  $json = make_http_request($arcgis_url . $arcgis_query_url, CURLOPT_HTTPGET,
    array(
      'where' => $where_clause,
      'f' => 'json',
      'inSR' => $inSR,
      'outSR' => $outSR,
      'outFields' => $outFields
    ));

  $id = 0;

  foreach ($json->features as $building) {
    $name = $building->attributes->NAME;
    $url = $building->attributes->URL;
    $geometry = json_encode(array($building->geometry));

    if (empty($name) == FALSE) {
      $centroid = make_http_request($geometry_service_url . $label_points_url, CURLOPT_POST,
                                    array( 'polygons' => $geometry, 'sr' => $outSR, 'f' => 'json'));
      $latitude = $centroid->labelPoints[0]->y;
      $longitude = $centroid->labelPoints[0]->x;

      ArelXMLHelper::outputObject(ArelXMLHelper::createLocationBasedPOI(
        $id++,
        $name,
        array($latitude, $longitude, 0),
        './resources/thumb_a1.png',
        './resources/icon_a1.png',
        $name,
        array(array("Visit website", "website-link", $url))
      ));
    }
  }

  ArelXMLHelper::end();
?>
