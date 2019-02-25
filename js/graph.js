var svg;
var datasub;
var datagroup = {};
var margin = {top: -5, right: -5, bottom: -5, left: -5};
var color;

function GRAPH()
{


//var width = 300,
//    height = 200;
color = d3.scale.category20();

if($('#map').length)
{
 //return false;
}

var zoom = d3.behavior.zoom()
    .scaleExtent([1, 10])
    .on("zoom", redraw);

var container = d3.select('#graphBox');
//var svg = d3.select('svg');

var width = $('svg').parent().width()*0.95;
var height = $('svg').parent().height()*0.95;

var force = d3.layout.force()
    .charge(-120)
    .linkDistance(30)
    .size([width, height]);

/*var svg = d3.select("#graphBox").append("svg")
    .attr("width", width)
    .attr("height", height);*/
svg = d3.select('svg').attr("width", width)
    .attr("height", height)
    .call(d3.behavior.zoom().on("zoom", redraw))
    .attr("pointer-events", "all")
.append('g')
  .append('g')
  ;
    
function redraw() {
  svg.attr("transform",
      "translate(" + d3.event.translate + ")"
      + " scale(" + d3.event.scale + ")");
}


d3.json(JSONFILE, function(error, graph) {
  if (error) throw error;

  force
      .nodes(graph.nodes)
      .links(graph.links)
      .linkDistance(50)
      .start();
      
      datasub = graph.datasub;
      var htmlcols = '';
      for(i in graph.nodes)
      {
        grp = graph.nodes[i];
        if(!datagroup[grp.group])
        {
          datagroup[grp.group] = new Array();
          bbb = color(grp.group);
          htmlcols += '<div><span class="regionCircle" style="background-color: '+bbb+';" data-old="'+bbb+'">&nbsp;</span> '+grp.group+'</div>';
        }
        datagroup[grp.group].push(grp.name);
        datagroup['subj_'+grp.name] = grp.group;
      }

      var ele = $('#legendBox').html(htmlcols);
      var spans = $('#klikyScroll span');
      for(i in spans)
      {
        if(spans.eq(i).html())
        {
          var subj = spans.eq(i).html().replace(/<\/?[^>]*>/g, '');
          m = subj.match(/\((.*)\)/);
          if(m) subj = m[1];
          if(datagroup['subj_'+subj])
          {
            bbb = color(datagroup['subj_'+subj]);
            spans.eq(i).prepend('<pre class="lSpan" style="background-color: '+bbb+';" data-old="'+bbb+'" data-region="'+subj+'">&nbsp;</pre> ');
          }
        }
      }
      
      $('#klikyScroll').height(newheight-90-$('#legendBox').height());
      $('#klikyTxt').height($('#map').height()-$('#thisform').height()-20);
      
      if(typeof nekresligraf != 'undefined' && nekresligraf)
      {
        return true;
      }
      /*BOF nechci nic kreslit*/
      
  var link = svg.selectAll(".link")
      .data(graph.links)
    .enter().append("line")
      .attr("class", "link")
      .attr("data-similarity", function(d) { return d.similarity; })
      .style("stroke-width", function(d) { return Math.sqrt(d.value); });
      
  svg.selectAll(".link").on('click', function()
  {
    var similarity = $(this).attr('data-similarity');
    $('#similarityLabel').html(similarity);
  });
      

  var node = svg.selectAll(".node")
      .data(graph.nodes)
    .enter()
        .append("g")
        .attr("transform", function(d){return "translate("+d.x+",80)"})
        //.attr("transform", "translate(" + margin.left + "," + margin.right + ")").call(zoom);
        //        .attr("transform", function(d){return "translate("+d.x+",80)"}).call(zoom);
        
                
  node.append("circle").attr("class", "node")
      .attr("r", 5)
      .style("fill", function(d) { return color(d.group); })
      .call(force.drag)
      ;


  node.append("text")
  .attr("class", "label")
      .attr("dx", 12)
      .attr("dy", ".35em")
      .text(function(d) { return d.name });

  force.on("tick", function() {
    link.attr("x1", function(d) { return d.source.x; })
        .attr("y1", function(d) { return d.source.y; })
        .attr("x2", function(d) { return d.target.x; })
        .attr("y2", function(d) { return d.target.y; });

    node.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
        
  });
  
  /* nechci nic kreslit */
  
  /*$('.label').click(function()
  {
    $('#klikyTxt').html('');
    $( ".label.high").each(function()
    {
      $(this).attr('class', 'label');
    });
    
    var span = $("span:contains('"+$(this).html()+"')" )
    
    spans = span.parent().children('span').each(function () {
      subj = $(this).html();
      if(datasub && datasub[subj])
      {
        html = $('#klikyTxt').html()+subj+": "+datasub[subj]+"<br />";
        $('#klikyTxt').html(html);
        $(".label:contains('"+subj+"')" ).attr('class', 'label high');
      }
    });
  });*/
  
});

}

function createPermaLink(randname)
{
	$.ajax({
	  async: false,
		type: 'post',
		cache: false,
		url: 'moveperma.php', 
		data: {source: randname}
	});
	window.location = location.href.replace('/\?.*$/', '')+"?source="+randname;
  return false;
}
