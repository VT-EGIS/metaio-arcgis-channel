<?php
$arcgis_url = 'http://arcgis-central.gis.vt.edu/arcgis/rest/services/vtcampusmap/Buildings/MapServer/0';
$arcgis_query = '/query?where=1=1&f=json';
$complete_url = $arcgis_url . $arcgis_query;
$req = new HttpRequest($complete_url, HttpRequest::METH_GET);
try {
  $req->send();
  if($req->getResponseCode() == 200) {
    $json = json_decode($req->getResponseBody());
    var_dump($json->features);
  }
} catch (HttpException $ex) {
  echo $ex;
}
?>
