var aktualniBarva = false;
var layerLoaded = 0;
var groupSet = false;
var showlabel = true;
var clickDoMapy = 0;
var vrstvyConf = {};
var vrstvyVektorove = {};
var map;
var mapView;
var progress;
var styleCache = {};
var datafile = 'urban_map';
var PROJEKCE = 'EPSG:102067';
proj4.defs(PROJEKCE, "+title=Krovak JTSK +proj=krovak +lat_0=49.5 +lon_0=24.83333333333333 +alpha=30.28813975277778 +k=0.9999 +x_0=0 +y_0=0 +ellps=bessel +units=m +towgs84=570.8,85.7,462.8,4.998,1.587,5.261,3.56 +no_defs");
var JTSK = new ol.proj.Projection({
  code: 'EPSG:102067',
  extent: [-910410.000000, -1233540.000000, -423062.500000, -932432.500000],//CR
  //extent: [-584668.75, -1157047.09, -499684.75, -1024297.68],//OL kraj
  units: 'm'
});
ol.proj.addProjection(JTSK);
var geoJSONFormat = new ol.format.GeoJSON();
var zoomlimit = 5;//zoom limit kdy se nebude zobrazovat vrstva

$(document).ready(
function()
{
//var mapCenter

mapdata = mapdata.replace('.json', '');


mapCenter = [-552311.3024902923, -1089392.9956055833];
mapCenter = [-452311.3024902923, -1089392.9956055833];  
extent = [-910410.000000, -1233540.000000, -423062.500000, -932432.500000];
PROJEKCEVEKTOR = JTSK;

//WGS
/*
PROJEKCE = new ol.proj.Projection({
    code: 'EPSG:4326',
    units: 'm'
})
PROJEKCEVEKTOR = PROJEKCE;
mapCenter = [11.159586, 63.882131];
datafile = 'obce3';
/**/
//eof wgs
    
    
    extent = PROJEKCEVEKTOR.getExtent();
    zoom = 5;
    resolutions = [
4759.2529288359, 1903.701171875, 951.8505859375, 475.9252929688, 297.4533081, 237.9626464844, 118.9813232422, 59.4906616211, 37.18166351, 29.7453308105, 14.8726654053, 7.4363327026, 3.7181663513, 1.8590831757, 0.9295415878, 0.4647707939, 0.232385397, 0.1161926985, 0.0580963492
];



  map = new ol.Map({
  interactions: ol.interaction.defaults({pinchRotate:false}),
  controls: ol.control.defaults().extend([
    new ol.control.ScaleLine({
    className: 'ol-scale-line', target: document.getElementById('scale-line'),
    units: 'metric'    
    })
  ]),
  target: 'map',
  layers: [],
  view: new ol.View({
    projection: PROJEKCE,
    center: mapCenter,
    //extent: extent,
    zoom: zoom,
    //resolutions: resolutions
  })
  });
  zoomslider = new ol.control.ZoomSlider();
  //map.addControl(zoomslider);
  map.removeControl(map.getControls().getArray()[2]);//openlayer attributes jde to udelat i jinak
  map.removeControl(map.getControls().getArray()[0]);//openlayer zoom in/out button

  mapView=map.getView();
  
/*EOF map*/

function featureGetAttribute(feature, attributeName)
{
  return feature.get(attributeName);
}
        var url = 'maps/'+((mapdata == 'obce')?datafile:mapdata)+'.json';
        var vektorLoader = function(extent, resolution, projection)
        {
          var me = this;
          var url = 'maps/'+((mapdata == 'obce')?datafile:mapdata)+'.json';
          if(!me.stop)
          {
            //progress.addLoading();
            $.ajax({
              url: url,
              success: function(data) {
                var features = geoJSONFormat.readFeatures(data);
                me.clear(true);
                me.addFeatures(features);
                me.stop = true;
                layerLoaded++;
                var extent = obcelayer.getSource().getExtent();
                map.getView().fit(extent, map.getSize())
                nastavBarvy();
              }
            });
          }
          me.stop = false;
        };
        var source = new ol.source.Vector({
          format: geoJSONFormat,
          loader: vektorLoader,
          projection: PROJEKCEVEKTOR
          //,useSpatialIndex: false
          ,strategy: ol.loadingstrategy.all
        });
        var source = new ol.source.Vector({
        projection: PROJEKCEVEKTOR,
        url: url,
        format: geoJSONFormat
        });
        source._loader = vektorLoader;
        
        var listenerKey = source.on('change', function(e) {
          if (source.getState() == 'ready') {
            // hide loading icon
            // ...
            // and unregister the "change" listener 
            ol.Observable.unByKey(listenerKey);
            // or vectorSource.unByKey(listenerKey) if
            // you don't use the current master branch
            // of ol3
            var extent = obcelayer.getSource().getExtent();
            map.getView().fit(extent, map.getSize())
            nastavBarvy();
            var features = source.getFeatures()
            for(f in features)
            {
              span = $('.lSpan[data-region=\''+features[f].get('ID')+'\']');
              if(span.length)
              {
                g = span.closest('.group').attr('data-group').replace('group', '');
                features[f].set('group', Number(g)+1);
              }
              else
              {
                features[f].set('group', 999);
              }
            }
            var writer = new ol.format.GeoJSON();
            var geojsonStr = writer.writeFeatures(source.getFeatures());
            var url = 'data:text/plain;charset=utf-8,' + encodeURIComponent(geojsonStr);
            //return url;
            $('#geolink').attr('href', url);
            var name = $('input[name=origname]').val().replace(/\..*$/, '');
            name += '_'+$('input[name=parameter]').val();
            name += '_'+$('input[name=limitpocet]').val();
            name += '.geojson';
            $('#geolink').attr('download', name);
          }
        });

var createObceStyle =  function(feature, resolution, txt)
{
  barva = ((feature.get('gcolor') && aktualniBarva)?feature.get('gcolor'):color(feature.get('REGION_NAME')));//ORP_NAZORP
return new ol.style.Style({
    stroke: new ol.style.Stroke({
      color: 'black',
      width: 1
    }),
    fill: new ol.style.Fill({
      color: barva
    })
  })
};

        obcelayer = new ol.layer.Vector({
          source: source,
          //strategy: ol.loadingstrategy.all,//bbox({ratio: 1}),
          style: createObceStyle
        });
         map.addLayer(obcelayer);
         

    filterSelection = new ol.interaction.Select({
      style: function(feature, resolution) {
      //cacheKey = feature.get('ID')+'_'+showlabel;
      //if(styleCache[cacheKey]) return styleCache[cacheKey];
      bbb = (!aktualniBarva)?color(feature.get('REGION_NAME')):feature.get('gcolor');//ORP_RGB
      if(showlabel)
      {
        text = new ol.style.Text(
          {
            text: featureGetAttribute(feature, 'NAME'),//NAZOB
            font:"normal 15px Arial",
            fill: new ol.style.Fill({color: "#ffffff"}),
            stroke: new ol.style.Stroke({
              color: '#000000',
              width: 3
            })
          });
      }
      else
      {
        text = null;
      }
      var out = new ol.style.Style({
          stroke: new ol.style.Stroke({
            color: 'red',
            width: 2
          }),
          text: text,
          fill: new ol.style.Fill({
            color: bbb
          })
        });
        //styleCache[cacheKey] = out;
        return out;//styleCache[cacheKey];
      }
    });
    filterSelection.on('select', function(e) {

      /*if(clickDoMapy<=0)
      {
        vybrana = $('.groupv:contains("('+feature.get('ID')+')")');
        if(vybrana.length)
        {
          vybrana.trigger('click');
          return false;
        }
      }
      clickDoMapy--;*/
    
      if(e.selected.length == 0)
      {
        //kliknul jsem mimo
        if($('.group.bold span').length)
        {
          $('.group.bold span').eq(0).trigger('click')
        }
      }
      else
      {
        vybrana = $('.groupv:contains("('+e.selected[0].get('ID')+')")');
        if(vybrana && vybrana.length == 0 && $('.group.bold').length)
        {
          //kliknul jsem mimo
          $('.group.bold').removeClass('bold');
        }
        else
        {
          vybrana.trigger('click');
        }
      }
    });
    map.addInteraction(filterSelection);

  //nasetovani barev pro groupy
  groupseen = 0;
  $('.group').each(function( index ) {
    if(groupseen < 20)
    {
      $( this ).attr('data-color', color($(this).html()));
      groupseen++;
    }
    else
    {
      $( this ).attr('data-color', '#f0f0f0');//e5e5e5');
    }
  });
  
  $('#colorbutton').click(function()
  {
    txt = (aktualniBarva)?'Colours by groups':'Colours by regions';
    $(this).attr('value', txt);
    nastavBarvy()
  });
  
  $('#labelbutton').click(function()
  {
    txt = $(this).attr('value') == 'Show labels'?'Hide labels':'Show labels';
    $(this).attr('value', txt);
    showlabel = !showlabel;
    vybrane = filterSelection.getFeatures().getArray();
    if(vybrane.length)
    {
      var pole = [];
      for(i in vybrane)
      {
        pole.push(vybrane[i].get('ID'));
      }
      zvyraznivmape(pole);
    }
  });
  
  
  
});

function najdipodleatributu(vrstva, atribut, hodnota)
{
  var features = vrstva.getSource().getFeatures();
  var out = new Array();
  for(i in features)
  {
    if( typeof someVar === 'string' )
    {
      if(features[i].get(atribut) == hodnota)
      {
        out.push(features[i]);
      }
    }
    else
    {
      if(hodnota.indexOf(features[i].get(atribut)) > -1)
      {
        out.push(features[i]);
      }
    }
  }
  return out;
}

function zvyraznivmape(hodnoty)
{
filterSelection.getFeatures().clear();
    pole = najdipodleatributu(obcelayer, 'ID', hodnoty);
    clickDoMapy = pole.length;
    if(pole.length)
    {
      for(i in pole)
      {
        filterSelection.getFeatures().push(pole[i]);
      }
    }
}

function nastavBarvy()
{
  //if(layerLoaded < 1) return;
  if(!groupSet)
  {
    groupseen = 0;
    f = obcelayer.getSource().getFeatures()
    f.forEach(function(feature)
    {
      vybrana = $('.groupv:contains("('+feature.get('ID')+')")');//ICZUJ
      if(vybrana.length)
      {
        feature.set('gcolor', vybrana.parent().attr('data-color'));
      }
      else
      {
        feature.set('gcolor', 'transparent');
      }
    });
    groupSet = true;
  }
  if(aktualniBarva)
  {
    $('.group .lSpan').each(function(index)
    {
      $(this).css('background-color', $(this).attr('data-old'));
    })
    $('.regionCircle').each(function(index)
    {
      $(this).css('background-color', $(this).attr('data-old'));
    })
  }
  else
  {
    $('.group').each(function(index)
    {
      $(this).find('.lSpan').css('background-color', $(this).attr('data-color'));
    })
    $('.regionCircle').css('background-color', 'white');
  }
  aktualniBarva = !aktualniBarva;
  obcelayer.getSource().dispatchEvent('change');
  vybrane = filterSelection.getFeatures().getArray();
  if(vybrane.length)
  {
    var pole = [];
    for(i in vybrane)
    {
      pole.push(vybrane[i].get('ID'));
    }
    zvyraznivmape(pole);
  }
  
  
}

function exportGeoJson()
{
  var writer = new ol.format.GeoJSON();
  var geojsonStr = writer.writeFeatures(map.getLayers().getArray()[0].getSource().getFeatures());
  var url = 'data:text/plain;charset=utf-8,' + encodeURIComponent(geojsonStr);
  //$('#geolink').setAttribute('href', url);
  //return url;
  window.open(url, '_blank');
}
