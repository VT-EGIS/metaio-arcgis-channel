require([
'esri/tasks/QueryTask',
'esri/tasks/query',
'dojo/_base/array',
'dojo/request/xhr',
'dojo/domReady!'
], function (QueryTask, Query, array, xhr) {
  var init, getQuery, filterFeatures, augmentFeatures, createPOIs, getCentroids, features;

  getQuery = function () {
    var query;

    query = new Query();
    query.outSpatialReference = { 'wkid': 4326 };
    query.outFields = ['NAME', 'URL'];
    query.returnGeometry = true;
    query.where = "BLDG_NUM <> ' ' AND URL IS NOT NULL";

    return query;
  };

  filterFeatures = function (fset) {
    return array.map(fset.features, function (feature) {
      return {
        name : feature.attributes.NAME,
        url : feature.attributes.URL,
        geometry: feature.geometry
      };
    });
  };

  augmentFeatures = function (features, centroids) {
    array.forEach(JSON.parse(centroids).labelPoints, function (centroid, index) {
      features[index].latitude = centroid.y;
      features[index].longitude = centroid.x;
    });
    return features;
  };

  createPOIs = function (features) {
    array.forEach(features, function (feature, index) {
      var poi;

      poi = new arel.Object.POI();
      poi.setLocation(new arel.LLA(feature.latitude, feature.longitude, 0));
      poi.setTitle(feature.name);
      poi.setID(index);
      poi.setThumbnail('./resources/thumb_a1.png');
      poi.setIcon('./resources/icon_a1.png');

      arel.Scene.addObject(poi);
    });
  };

  getCentroids = function (features) {
    var geometries, geometryServiceUrl;

    geometries = array.map(features, function (feature) { return feature.geometry; });
    geometryServiceUrl = 'http://arc01.gis.vt.edu/arcgis/rest/services/Utilities/Geometry/GeometryServer/labelPoints';

    // Not using the labelPoints task from the ArcGIS API as it uses HTTP GET
    // to get the label points. There is a limit on the length of a url
    // and as we're sending rings of polygons, the request exceeds that limit
    // and an error occurs where the ArcGIS API asks us to use a proxy, which is
    // unnecessary.
    // We simply use a POST instead
    xhr.post(geometryServiceUrl, { 
      data: {
        polygons: JSON.stringify(geometries),
        f: 'json',
        sr: 4326
      },
      headers: {'X-Requested-With': ''}
    }).then(augmentFeatures.bind(undefined, features)).then(createPOIs);
  };

  init = function () {
    var queryTask;

    queryTask = new QueryTask('http://arcgis-central.gis.vt.edu/arcgis/rest/services/vtcampusmap/Buildings/MapServer/0/');

    queryTask.execute(getQuery()).then(filterFeatures).then(getCentroids);
  };

  arel.sceneReady(init);
});
