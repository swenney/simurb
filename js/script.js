var resizeCircle = 2;

$(document).ready(function()
{
  $('select[name=druhazipmetoda]').change(function()
  {
    if($(this).val() == 'vyskyt')
    {
      $('#miravyskytubox').show();
      $('#advisedGraph').hide();
      $('#edges').hide();
      //if($('#advicedbox').length) $('#advicedbox').hide();
    }
    else
    {
      $('#miravyskytubox').hide();
      $('#advisedGraph').show();
      $('#edges').show();
      //if($('#advicedbox').length) $('#advicedbox').show();
    }
  });


  //velikost otevreneho okna
  wh = $(window).height();
  //$('#summaryBox').outerHeight()-
  //odectu velikosti prvku co zavazi grafu
  newheight = (wh-$('#header').outerHeight());
  newheight = newheight-20;
  $('#graphBox').height(newheight);
  $('#klikyBox').height(newheight-20);
  $('#map').height(newheight-10);

  $('#selectmethod').change(function()
  {
    $('#leve2met').hide();
    $('#wunschmet').hide();
    var val = $( this ).val();
    switch(val)
    {
      case 'wunsch':
        $('#wunschmet').show();
      break;
      case 'leve2':
        $('#leve2met').show();
      break;
      default:
      
      break;
    }
  });

  //click do boxu
  $('#klikyBox span').click(function()
  {
    //test odkliknuti
    var odklik = true;
    if($(this).parent().hasClass('bold'))
    {
      odklik = false;
    }
  
    $('#klikyTxt').html('');
    $( ".label.high").each(function()
    {
      $(this).attr('class', 'label');
    });
    $('div.group.bold').removeClass('bold');
    $('circle.bigger').each(function()
    {
       $(this).attr('r', ($(this).attr('r')*1)-resizeCircle);
       $(this).attr('class', 'node');
       $(this).attr('class', $(this).attr('class').replace(' bigger', ''));
    });
    
    var mapdiv = document.getElementById('map');
    if(mapdiv)
    {
      filterSelection.getFeatures().clear();
    }
    
    if(odklik)
    {
      var poleVyberu = new Array();
      $(this).parent().addClass('bold');
      spans = $(this).parent().children('span').each(function () {
        //problem s regexem napr stare mesto
        subj = $(this).html().replace(/^.*>/, '').trim();
        if($('#map').length)
        {
          m = subj.match(/\((.*)\)/);
          if(m) subj = m[1];
        }
        
        if(!subj)
        {
          subj = $(this).html().replace(/^.*\ /, '');
        }

        if(datasub && datasub[subj])
        {
          if(poleVyberu.length == 0 && $('#map').length)
          {
            arr = datasub[subj].slice(2, datasub[subj].length);
            txta = new Array();
            legend = '';
            for(i=0;i<arr.length;i++)
            {
              //letter = String.fromCharCode(65+i);
              letter = convertToNumberingScheme(i+1);
              if(i == arr.length-1)
              {
              	txta.push(letter);
              }
              else
              {
              	txta.push(letter+","+" ".repeat(4-letter.length));
              }
              legend += letter+': '+columnmames[(i+1)]+" weight: "+Number(weight[(i+1)]).toFixed(2)+"<br />";
            }
            var pocetmezer = "    ";
            txt = ' '+pocetmezer+txta.join(' ');
            
            html = $('#klikyTxt').html()+'<label class="tooltip hornitooltip hornitooltip2"><img src="./Info/Lupa.png" alt="Info" height="20" width="20" class="infoImg" /> Legend<span>'+legend+'</span></label> '+jQuery.trim(txt)+"<br />";
            $('#klikyTxt').html(html);
          }
          poleVyberu.push(subj);
          if(datasub[subj].join)
          {
            obec = datasub[subj][0];
            arr = datasub[subj].slice(2, datasub[subj].length);
            for(a in arr)
            {
              arr[a] = Number(arr[a]).toFixed(2);
            }
            txt = arr.join(', ').replace(new RegExp('"', 'g'), '');
            html = $('#klikyTxt').html()+'<label>'+obec+" ("+subj+"):</label> "+jQuery.trim(txt)+"<br />";
          }
          else if(datasub[subj] != -1)
          {
            html = $('#klikyTxt').html()+'<label>'+subj+":</label> "+jQuery.trim(datasub[subj])+"<br />";
          }
          
          if(typeof html  != 'undefined') $('#klikyTxt').html(html);
          //$(".label:contains('"+subj+"')" ).attr('class', 'label high');
          
          regex = new RegExp("^"+subj+"$");
          $(".label" ).filter(function() {
          if(regex.test($(this).text()))
          {
            $(this).attr('class', 'label high');
            circle = $(this).prev();
            if(circle.attr('class') != 'bigger')
            {
              circle.attr('class', circle.attr('class')+' bigger');
              circle.attr('r', (circle.attr('r')*1)+resizeCircle);
            }
          }
          return regex.test($(this).text());
          });

        }
      });
      if(mapdiv && poleVyberu.length)
      {
        zvyraznivmape(poleVyberu);
      }
      maxw=-1;
      $('#klikyTxt label').each(function () {
        maxw = Math.max(maxw, $(this).width());
      });
      $('#klikyTxt label').width(maxw);
    }
  });
  
  if(typeof JSONFILE != 'undefined') GRAPH();

  //advicedbox
  $('#advicedbox div').on('click', function()
  {
    if($('select[name=druhazipmetoda]').val() == 'vyskyt')
    {
      $('#miravyskytu').val($(this).find('span:first').html());
    }
    else
    {
      $('#parameter').val($(this).find('span:first').html());
    }
    $('#compute').trigger('click');//rovnou odesle formular
  });
  
  $('#advicedbox').height(76+($('#rightCol').height()-$('#leftCol').height()));
  
  $('#klikyScroll').height(newheight-40-$('#legendBox').height());

});

function fileinputchange(oInput)
{
  $('#sourcefilename').hide();
  $('#advisedGraph').show();
  $('#druhaZipMetodaBox').hide();
  $('#druhazipmetoda').attr('disabled','disabled');
  if(oInput.files && oInput.files.length)
  {
    //applicatiin/zip
    //application/x-zip-compressed
    if(oInput.files[0].type && oInput.files[0].type.match(/[\/\-]zip/))
    {
      //alert('nahravas zip?');
      //$('#advisedGraph').hide();
      $('#druhaZipMetodaBox').show();
      $('#druhazipmetoda').removeAttr('disabled');
    }
  }
}

function exportToJava()
{
  var DD;
  var seen = [];
  var data = false;
  jQuery.getJSON(JSONFILE,function( data ) {
    var data2 = data;
    $.each(data.links, function( key, val ) {
      k = val.source+"_"+val.target;
      k2 = val.target+"_"+val.source;
      if(seen[k] && seen[k2])
      {
      
      }
      else
      {
        DD += data2.nodes[val.source]['name']+" "+data2.nodes[val.target]['name']+"\n";
        seen[k] = true;
        seen[k2] = true;
      }
    });
    window.open("data:text;charset=utf-8,"+encodeURIComponent(DD).replace('undefined', ''), '_blank');
  });
  
}

function convertToNumberingScheme(number) {
  var baseChar = ("A").charCodeAt(0),
      letters  = "";

  do {
    number -= 1;
    letters = String.fromCharCode(baseChar + (number % 26)) + letters;
    number = (number / 26) >> 0; // quick `floor`
  } while(number > 0);

  return letters;
}
