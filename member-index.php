<?php
  require_once('auth.php');
?>
<!DOCTYPE html>
<!--
 --------------------------------------------------------------------------
 Copyright 2012 British Broadcasting Corporation and Vrije Universiteit 
 Amsterdam
 
    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at
 
        http://www.apache.org/licenses/LICENSE-2.0
 
    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
 --------------------------------------------------------------------------
-->
<html>
 <head>
  <title>N-Screen</title>
    
    <script type="text/javascript" src="lib/jquery-1.4.4.min.js"></script>
    <script type="text/javascript" src="lib/jquery-ui-1.8.10.custom.min.js"></script>    
    <script type="text/javascript" src="lib/jquery.ui.touch.js"></script>
    

    <script type="text/javascript" src="lib/strophe.js"></script>
    <script type="text/javascript" src="lib/buttons.js"></script>
    <script type="text/javascript" src="lib/spin.min.js"></script>
    <script type="text/javascript" src="lib/play_video.js"></script>

  <link type="text/css" rel="stylesheet" href="css/new.css" />

<!-- workaround - http://stackoverflow.com/questions/2894230/fake-user-initiated-audio-tag-on-ipad -->

  <script type="text/javascript">
        document.onclick = function(){
          document.getElementById("a1").load();
          document.getElementById("a2").load();
        }

  </script>
 </head>


<body onload="javascript:init()">

<!-- workaround for audio problems on ipad - http://stackoverflow.com/questions/2894230/fake-user-initiated-audio-tag-on-ipad -->

<div id="junk" style="display:none">
  <audio src="sounds/x4.wav" id="a1" preload="auto"  autobuffer="autobuffer" controls="" ></audio>
  <audio src="sounds/x1.wav" id="a2" preload="auto"  autobuffer="autobuffer" controls="" ></audio>
</div>

<script type="text/javascript">

//the main buttons object
var buttons = null;

//if there's more than one TV you'll get more than one message so this is to limit the duplication
var lastmsg="";
//this is so we can link to the TV and perhaps the api easily
var clean_loc = String(window.location).replace(/\#.*/,"");

//group name is based on the part after # in the url, handled in init()
var my_group=null;

//for api calls
//could be a web service
var api_root = "data/";

var recommendations_url=api_root+"recommendations.js"
var channels_url=api_root+"channels.js";
var search_url = api_root+"search.js";
var start_url = "get_suggestions.php";
var random_url = "get_random.php"

//jabber server
var server = "localhost";
//var server = "jabber.notu.be";

//polling interval (for changes to channels)
var interval = null;


var list = new Array(); //initialazing array

var list_watch_later = [];
var watch_later_json = {};

var list_recently_viewed = [];
var recently_viewed_json = {};

var ted_key = "xbsdfg4uhxf6prsp8c7adrty";
var ted_api_request = "https://api.ted.com/v1/talks.json?api-key=xbsdfg4uhxf6prsp8c7adrty"
var ted_api_filter_id= "filter=id:>"; //Remember to make "&" between them and provide int!!
var ted_api_filter_limit = "limit=";
var ted_api_filter_offset = "offset=";
var local_search = false;
var suggestions = null;
   

function init(){

//detect ipads etc
   //console.log("platform "+navigator.platform);
   if(navigator.platform.indexOf("iPad") != -1 || navigator.platform.indexOf("Linux armv7l") != -1){
       $("#inner").addClass("inner_noscroll");
       $(".slidey").addClass("slidey_noscroll");
       $("#search_results").css("width","80%");
   }else{
     $("#inner").addClass("inner_scroll");
   }

   create_buttons();

   $("#main_title").html("Browse Programmes");

   $sr=$("#search_results");
   $sr.css("display","none");
   // $container=$("#browser");
   // $container.css("display","block");

   // $browse=$("#browse");
   // $browse.addClass("blue").removeClass("grey");

   // $random=$("#random");
   // $random.addClass("grey").removeClass("blue");

   var grp = window.location.hash;
   if(grp){
     my_group = grp.substring(1);
     //$("#header").show();
     // $("#roster_wrapper").show();
     //$(".about").hide();
     //create_buttons();
   }else{
     $.ajax({
        url: "get_group.php",
        async: false,
        success: function (response) {
            console.log("Th group is = " + response);
            my_group = response;
            //$("#header").show();
            //$("#roster_wrapper").show();
        }
      });
   }
   history.pushState(state, "N-Screen", "/N-Screen/");
   clean_loc = String(window.location);
   window.location.hash=my_group;
   //$("#group_name").html(my_group);
   // $("#grp_link").html(clean_loc+"#"+my_group);
   // $("#grp_link").attr("href",clean_loc+"#"+my_group);
   var state = {"canBeAnything": true};
   add_name();

   //load the start url or random if no start_url (see conf.js)
   //get a random set of starting points


   // ??????????????????????????????????????????????????
   //do_start("progs","get_suggestions.php");

}

function show_browse_programmes(){
  $("#main_title").html("Browse Programmes");
  $sr=$("#search_results");
  $sr.css("display","none");

  $browse=$("#browse");
  $browse.removeClass("grey").addClass("blue");

  $random=$("#random");
  $random.removeClass("blue").addClass("grey");
  $container=$("#browser");
  $container.css("display","block");
}

//creates and initialises the buttons object                              

function create_buttons(){
   //$("#inner").addClass("inner_noscroll");
   // $(".slidey").addClass("slidey_noscroll");
   //$(".about").hide();
   // $("#header").show();
    $("#roster_wrapper").show();
    
   //set up notifications area
   $("#notify").toggle(
     function (){
       //console.log("SHOW");
       $("#notify_large").show();
     },
     function (){
       //console.log("HIDE");
       $("#notify_large").hide();
       $("#notify").html("");
       $("#notify_large").html("");
       $("#notify").hide();
     }
   );

   //initialise buttons object and start the link
   buttons = new ButtonsLink({"server":server});
}

// called when buttons link is created

function blink_callback(blink){
  console.log("INSIDE BLINK CALLBACK --> GOING TO CALL GET CHANNEL")
  //var delay = 60000;

  //interval = setInterval(get_channels, delay);

  get_roster(blink);

  // ???????????????????????????????????????????

  // $(document).trigger('refresh');
  // $(document).trigger('refresh_buttons');
  // $(document).trigger('refresh_group');
  // $(document).trigger('refresh_history');
  // $(document).trigger('refresh_recs');
  // $(document).trigger('refresh_search');

  // //my new channels
  // $(document).trigger('refresh_later');
  // $(document).trigger('refresh_ld');
}

//http://fgnass.github.com/spin.js/
var opts = {
  lines: 12, // The number of lines to draw
  length: 5, // The length of each line
  width: 3, // The line thickness
  radius: 5, // The radius of the inner circle
  color: '#fff', // #rbg or #rrggbb
  speed: 1, // Rounds per second
  trail: 100, // Afterglow percentage
  shadow: true // Whether to render a shadow
};


//display suggestions based on id

function insert_suggest2(id) {


      var div = $("#"+id);

      var title = div.find(".p_title").text();
      var description = div.find(".description").text();
      var explanation = div.find(".explain").text();
      var keywords = div.find(".keywords").text();
      var video = div.attr("href");
      var pid = div.attr("pid");
      var img = div.find(".img").attr("src");

      html = [];
      html.push("<div id=\""+id+"_history\" pid=\""+pid+"\" href=\""+video+"\"  class=\"ui-widget-content button programme ui-draggable\">");
      html.push("<img class=\"img\" src=\""+img+"\" />");
      html.push("<span class=\"p_title\">"+title+"</span>");
      html.push("<p class=\"description large\">"+description+"</p>");
      html.push("</div>");
      $('#history').prepend(html.join(''));


      html2 = [];
/*
      html2.push("<div id=\""+id+"_overlay\" pid=\""+pid+"\" href=\""+video+"\"  class=\"ui-widget-content large_prog\">");
      html2.push("<img class=\"img\" src=\""+img+"\" />");
      html2.push("<div class=\"play_button\"><img src=\"images/play.png\" /></a></div>");
      html2.push("<span style='float:left;' class=\"p_title_large\">"+title+"</span>");
      html2.push("<br clear=\"both\"/>");
      html2.push("<p class=\"description\">"+description+"</p>");
      html2.push("<p class=\"explain\">"+explanation+"</p>");
      html2.push("</div>");
*/

      html2.push("<div class='close_button'><img src='images/close.png' width='30px' onclick='javascript:hide_overlay();'/></div>");
      html2.push("<div id=\""+id+"_overlay\" pid=\""+pid+"\" href=\""+video+"\"  class=\"ui-widget-content large_prog\">");
      html2.push("<div style='float:left;'> <img class=\"img\" src=\""+img+"\" />");
      html2.push("<div class=\"play_button\"><img src=\"images/play.png\" /></a></div></div>");
      html2.push("<div style='padding-left:20px;padding-right:20px;'>");
      html2.push("<div class=\"p_title_large\">"+title+"</div>");
      html2.push("<p class=\"description\">"+description+"</p>");
      html2.push("<p class=\"explain\">"+explanation+"</p>");
//      html2.push("<p class=\"keywords\">"+keywords+"</p>");
      html2.push("<p class=\"link\"><a href=\"http://www.ted.com/talks/view/id/"+pid+"\" target=\"_blank\">Sharable Link</a></p></div>");
      html2.push("</div>");
      // html2.push("<br clear=\"both\"/>");
      html2.push("</div>");

      $('#new_overlay').html(html2.join(''));
   
      $('#new_overlay').show();  
      show_grey_bg();


      // FIXXXX PLAY BUTTON!!!!! ??????????? **************************************************

      $('.play_button').click(function(){
              var res = {};
              res["id"]=id;
              res["pid"]=pid;
              res["title"]=title;
              res["video"]=video;
              res["description"]=description;
              res["explanation"]=explanation;
              res["img"]=id;
              sendProgrammeTVs(res,my_tv); 
              return false;

      }).addTouch();


      $('#new_overlay').append("<div class='dotted_spacer2'></div><span class=\"sub_title\">MORE LIKE THIS</span><span class=\"more_blue\"><a onclick=\"show_more('"+title+"','"+pid+"');\">View All</a></span>");
      // $('#new_overlay').append("<br clear=\"both\"/>");
      $('#new_overlay').append("<div id='spinner'></div>");
      // var target = document.getElementById('spinner');//??
      // var spinner = new Spinner(opts).spin(target);

//       $.ajax({
//        url: get_related_url(pid),
//        dataType: "json",
//          success: function(data){
//            recommendations(data,"spinner",false,title);
// //           recommendations(data,"new_overlay",false,title);
//          },
//          error: function(jqXHR, textStatus, errorThrown){
//          //alert("oh dear "+textStatus);
//          }
//       });

}

//print out who is in the group and what sort of thing they are

function get_roster(blink){

  //console.log("GETTING RROOOOSSSTEERRRR")
  var roster = blink.look();
  console.log("THIS IS ROSTER === ");
  console.log(roster);

   if(roster["me"]){
     $("#title").html(roster["me"].name);
   }
  $("#roster").empty();

  var html=[];

  if(roster){
    //console.log("I AM INSIDE ROSTER BECAUSE I SEE IT!")

     html.push("<h3 class=\"contrast\">SHARE WITH</h3>");

    // html.push("<div class='snaptarget_group person' id='group'>");
    // html.push("<img class='img_person' src='images/group.png'  />");
    // html.push("<div class='friend_name' id='grp'>Group #"+my_group+"</div>");
    // html.push("</div>");

    // html.push("<br clear=\"both\" />");

    for(r in roster){

      item = roster[r];
      console.log("printing roster[r]")
      console.log(roster[r]);

       //i.e. not me
      if(item && item.name!=buttons.me.name){

        // if a person
        if(item.obj_type=="person"){
          html.push("<div class='snaptarget person ui-droppable' id='"+item.name+"'>");
          html.push("<img class='img_person' src='images/person.png'  />");
          html.push("<div class='friend_name'>"+item.name+"</div>");
          html.push("</div>");
          // html.push("<br clear=\"both\" />");
        }

        // if a bot
        if(item.obj_type=="bot"){
          html.push("<div class='snaptarget_bot person' id='"+item.name+"'>");
          html.push("<img class='img_person' src='images/bot.png'  />");
          html.push("<div class='friend_name'>"+item.name+"</div>");
          html.push("</div>");
          // html.push("<br clear=\"both\" />");
        }

        // if a TV

        if(item && item.obj_type=="tv"){
            var html_tv = [];
            html_tv.push("<div class='snaptarget_tv telly' id='tv_title'>");
            
            html_tv.push("<div id='tv_name' style='float:right;font-size:16px;padding-top:10px;padding-right:40px;'>My TV</div>");
            html_tv.push("<div style='float:left'><img class='img_tv' src='images/tiny_tv.png' /></div>");
            
            // html_tv.push("<br clear=\"both\" />");
    
            html_tv.push("<div class='dotted_spacer'>");
            var nowp = item.nowp;
            if(nowp && nowp["title"]){
              html_tv.push(nowp["title"]);
              $("#tv").attr("pid",nowp["pid"]);
            }else{
              html_tv.push("Nothing currently playing");
              $("#tv").attr("pid","");
            }
            html_tv.push("</div>");
            html_tv.push("</div>");
            // html_tv.push("<br clear=\"both\"></br>");
            $('#tv').html(html_tv.join(''));
            $("#tv").unbind('click');
            $("#tv").click(function() {
               var pid = $("#tv").attr("pid");
               if(pid && pid!=""){
                 insert_suggest_from_prog_id(pid,true);
               }
            }).addTouch();

         }
        }
      }

    }
    $('#roster').html(html.join(''));

}

// Hook up touch events
$.fn.addTouch = function() {
        if ($.support.touch) {
                this.each(function(i,el){
                        el.addEventListener("touchstart", iPadTouchHandler, false);
                        el.addEventListener("touchmove", iPadTouchHandler, false);
                        el.addEventListener("touchend", iPadTouchHandler, false);
                        el.addEventListener("touchcancel", iPadTouchHandler, false);
                });
        }
};
//show all recommendations
function show_more_recommendations(){

  $("#main_title").html("Suggestions For You");

  $sr=$("#search_results");
  $sr.css("display","block");
  
  $container=$("#browser");
  $container.css("display","none");

  $browse=$("#browse");
  $browse.removeClass("blue").addClass("grey");

  $random=$("#random");
  $random.removeClass("blue").addClass("grey");


  $sr.empty();

  //do_start("search_results",start_url);
  do_start("search_results","get_suggestions.php");

}

function show_shared(){

  $("#main_title").html("Shared By Friends");

  $sr=$("#search_results");
  $sr.css("display","inline");
  
  $container=$("#browser");
  $container.css("display","none");

  $browse=$("#browse");
  $browse.removeClass("blue").addClass("grey");

  $random=$("#random");
  $random.removeClass("blue").addClass("grey");

  $sr.empty();

//@@
  $sr.html($("#results").clone());
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');

}

//ON CLICK LISTENER TO ADD TO WATCH LATER

$("#addtowatchlater").live( "click", function() {
  var father = $(this).parents().eq(2);
  var this_div = $(this).attr('id');
  var the_program= $(this).parents().eq(2).attr('id');
  console.log('THE DIV ID OF THE PROGRAM IS   ' + the_program );
  insert_watchlater_from_div(the_program);
  console.log("clicked watch later");
  return false;
});

// list of movies ---- > HAVE TO CHANGE IT TO MAKE IT BETTER

function insert_watchlater_from_div(id){
  var div = $("#"+id);
  var j = get_data_from_programme_html(div);
  var prog_id = j["id"];
  console.log(j);
  var not_in_the_list = true;

  //checking wheter is already in the list or not
  for (var i = 0; i < list_watch_later.length; i++){
    if(list_watch_later[i] == prog_id) not_in_the_list = false;
  }
  if(not_in_the_list){ 
    list_watch_later.push(prog_id);
    insert_watchlater(j);
    watch_later_json.suggestions.push(j);

    jsObject_json = JSON.stringify(watch_later_json);

    $.ajax({
        url: "set_watch_later.php",
        type: "POST",
        data: {data : jsObject_json},
        dataType: "json",
        success: function (response) {
            console.log("Correct watch_later updated");
        }
      });
  }
}

// Call as
//setUsername(3, "Thomas");

function insert_watchlater(j){
  var id = j["id"];
  console.log("passing to addlater");
  console.log(j);
  console.log("passing to addlater");
  var html3 = generate_html_for_programme(j,null,id);
  $('#list_later').append(html3.join(''));
}

function show_history(){

  $("#main_title").html("Recently Viewed");

  $sr=$("#search_results");
  $sr.css("display","inline");
  
  $container=$("#browser");
  $container.css("display","none");

  $browse=$("#browse");
  $browse.removeClass("blue").addClass("grey");


  $sr.empty();

//@@
  $sr.html($("#history").clone());
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');
}

function show_later(){

  $("#main_title").html("Watch Later");

  $sr=$("#search_results");
  $sr.css("display","inline");
  
  $container=$("#browser");
  $container.css("display","none");

  $browse=$("#browse");
  $browse.removeClass("blue").addClass("grey");


  $sr.empty();
  do_start("search_results",start_url);
}


function show_more(title,pid){

//console.log("[1] "+pid);

  $("#main_title").html("Related to "+title);

  $sr=$("#search_results");
  $sr.css("display","block");
  
  $container=$("#browser");
  $container.css("display","none");

  $browse=$("#browse");
  $browse.removeClass("blue").addClass("grey");

  $random=$("#random");
  $random.removeClass("blue").addClass("grey");

//@@
//  $sr.html($("#more").clone(true));
  //console.log("related url is "+get_related_url(pid));
  $("#search_results").empty();

      $.ajax({
       url: get_related_url(pid),
       dataType: "json",
         success: function(data){
           recommendations(data,"search_results",false,title);
         },
         error: function(jqXHR, textStatus, errorThrown){
         //alert("oh dear "+textStatus);
         }
      });
  hide_overlay();
}


//get a random selection

function do_random(el){

  $("#main_title").html("Random Selection");

  $sr=$("#search_results");
  $sr.css("display","block");
  
  $container=$("#browser");
  $container.css("display","none");

  $browse=$("#browse");
  $browse.removeClass("blue").addClass("grey");

  $random=$("#random");
  $random.removeClass("grey").addClass("blue");

  //id for element to add it to
  if(!el){
    el = "search_results";
  }

  $.ajax({
    url: "get_random_tedtalks.php",
    dataType: "json",
    success: function(data){
      var result = changeData(data);
      console.log(JSON.stringify(result));
      random(result,el);
    },
    error: function(jqXHR, textStatus, errorThrown){
    //console.log("nok "+textStatus);
    }

  });

}


// start url if different from do_random

function do_start(el,start_url){

  //do_start("search_results",start_url);


  //id for element to add it to
  if(!el){
    el = "progs";
  }

  if(start_url){

    $.ajax({
      url: start_url,
      dataType: "json",
      success: function(data){
        random(data,el);
      },
      error: function(jqXHR, textStatus, errorThrown){
      //console.log("nok "+textStatus);
      }

    });

  }else{
     //do_random(el);
  }

}

//search for txt
       
function do_search(txt){

  txt = txt.toLowerCase();
  $('#main_title').html("Search for '"+txt+"'");

  $sr=$("#search_results");
  $sr.css("display","block");

  $container=$("#browser");
  $container.css("display","none");

  $browse=$("#browse");
  $browse.addClass("grey").removeClass("blue");

  $random=$("#random");
  $random.addClass("grey").removeClass("blue");


  $.ajax({
    url: get_search_url(txt),
    dataType: "json",
    success: function(data){
      search_results(data,txt,"search_results");
    },
    error: function(jqXHR, textStatus, errorThrown){
    //console.log("nok "+textStatus);
    }

  });

}    


//make titles look a bit more readable

function capitalise(txt){

  txt = txt.replace(/:/g," : ");
  var arr = txt.split(/[.|,| |\(|\)|\[|\]]/);
  var arr2 = [];
  for(x in arr){
    var str = arr[x];
    var letter = str.substr(0,1);
    arr2.push(letter.toUpperCase() + str.substr(1).toLowerCase());
  }

  return arr2.join(" ");
}



//called when random results are returned

function random(result,el){
//pass to the common bit of processing

  var suggestions = [];

  if(local_search){
  //if it's local search then random just returns everything
  //so do some processing
    for (var i =0;i<11;i++){
      var rand = Math.floor(Math.random()*result.length)
      suggestions.push(result[rand]);
    }
  }else{
    if(result && result["suggestions"]){
      suggestions = result["suggestions"];//??
    }else{
      suggestions = result;
      //console.log("no results");
    }
  }

//randomise what we have
  if(suggestions){
      suggestions.sort(function() {return 0.5 - Math.random()});
  }

  process_json_results(suggestions,el,null,true);
}


//called when recommendations are returned

function recommendations(result,el,add_stream,stream_title){

   if(!el){
     el = "progs";
   }
   if(result){
          var suggestions = result["suggestions"];
          var pid_title = result["title"];
          if(suggestions.length==0){
            if(pid_title){
               $("#pane2").html("<h3>Sorry, nothing found related to "+pid_title+"</h3>");
            }else{
               $("#pane2").html("<h3>Sorry, nothing found</h3>");
            }
            $("#"+el).append("");
          }else{
            if(pid_title){
               $("#pane2").html("<h3>Related to "+pid_title+"</h3>");
            }else{
               $("#pane2").html("<h3>Related</h3>");
            }
//            process_json_results(suggestions,el,pid_title,null,add_stream,stream_title);
            process_json_results(suggestions,el,pid_title,true,add_stream,stream_title);
          }
    }else{
//tmp@@ for when offline
/*
       var s = {    "pid": "b0074fpm",
    "core_title": "Doctor Who - Series 2 - The Satan Pit",
    "channel": "bbcthree",
    "description": "As Rose battles the murderous Ood, the Doctor finds his beliefs challenged.",
    "image": "http://dev.notu.be/2011/04/danbri/crawler/images/b0074fpm_512_288.jpg",
    "series_title": "Doctor Who",
    "date_time": "2010-10-03T18:00:00+00:00"};
       var suggestions = [];
       suggestions.push(s);
       process_json_results(suggestions,el,pid_title,null,add_stream,title);
*/
//console.log("OOPS!");

    }
}


//handle inserted search results
function search_results(result,current_query,el){
   if(!el){
     el = "progs";
   }

   suggestions = [];

   if(local_search){
     //if it's local search then search just returns everything
     //so do some processing

       for (r in result){
         var title = result[r]["title"];
         var desc = result[r]["description"];
         if(title.toLowerCase().match(current_query)||(desc.toLowerCase().match(current_query))){
            suggestions.push(result[r]);
         }
       }
   }else{
      suggestions = result;
   }

   if(!suggestions || suggestions.length==0){
      $("#"+el).html("<div class='sub_title' style='padding-top:26px;padding-left:8px'>Sorry, nothing found for '"+current_query+"'</div>   <div class='bluebutton'><a href='javascript:do_random()'>Give me a random selection</a></div>");
   }else{
      $("#rec_pane").html("<h3>Search results for "+current_query+"</h3>");
      var replace_content=true;
      process_json_results(suggestions,el,null,true);
   }

}



//process the results for displaying

function process_json_results(result,ele,pid_title,replace_content,add_stream,stream_title){
          var max = 12
          var s ="";
          var html = [];
          suggestions = result;
          console.log("THIS IS THE RESULT OF RESULT VAR");
          console.log(result);

          if (suggestions && suggestions.length>0){
            console.log("---------WE ARE NOW IN DIV " +  ele + "----------and json size is " + suggestions.length);
            var count = 0;
            var num = suggestions.length/2;
            for (var r in suggestions){
              if(count<max){
                count = count + 1;
                var title = suggestions[r]["core_title"];//@@
                if(!title){
                  title = suggestions[r]["title"];
                }
                var desc="";
                var desc = suggestions[r]["description"];
//                desc = desc.replace(/\"/g,"'");
                var id = suggestions[r]["id"];
                if(!id){
                  id = suggestions[r]["pid"];
                }

                var img = suggestions[r]["image"];

                var channel = suggestions[r]["channel"];
                var date_time = suggestions[r]["date_time"];

                var time_offset = suggestions[r]["time_offset"];
                var explanation=suggestions[r]["explanation"];
                //var vid = suggestions[r]["video"];
                var vid = "http://video.ted.com/talks/dynamic/IsabelleAllende_2007-high.flv";  //*************************TODO

                var whatever = id.toString();

                var string = "<div id=\""+id+"\" pid=\""+id+"\" class=\"ui-widget-content button programme ui-draggable\" " + " onclick= \"javascript:insert_suggest2("+whatever+");return true\">";

/*
                var vid = suggestions[r]["media"]["swf"]["uri"];


//processing for local files option
                if(video_files){
                    vid = video_files+""+vid;
                }

//processing for a particular form of time offsets
//T00:18:31:15F25
                if(vid && time_offset){
                   var offs = time_offset.replace(/T/,"")
                   var aa = offs.split(":");
                   var secs = parseInt(aa[1])*60+parseInt(aa[2]);
                   video = video+"#"+secs
                }
*/
                if(id){
                  if(pid_title){
                    
                    console.log(string);
                     html.push(string);
                  }else{
                     html.push(string);
                  }
                  html.push("<div><img class=\"img\" src=\""+img+"\" /></div>");
                  html.push("<span class=\"p_title p_title_small\"><a href=''>"+title+"</a></span>");
                  // html.push("<div clear=\"both\"></div>");
                  if(desc && desc!=""){
                    html.push("<span class=\"large description\">"+desc+"</span>");
                  }
                  if(explanation && explanation!=""){
                    //see tidy_dbpedia.js
                    //idea is that the user doesn't need to see piles of junk
                    ///explanation = clean_up(explanation);
                    //i.s. if this is caled because it's related content, say why
                    if(explanation){
                        html.push("<span class=\"explain large\">"+explanation+"</span>");
                    }

                  }
//                  var cats = suggestions[r]["keywords"];
  //                if(cats && cats!=""){
    //                html.push("<span class=\"large keywords\"><i>"+cats+"</i></span>");
      //            }
                  html.push("</div>");
                }
              }//end if count < max
            }

//console.log("[1]");
           if(replace_content){
              $("#"+ele).html(html.join(''));
           }else{
              $("#"+ele).append("<div id=\"more\">"+html.join('')+"</div>");
           }
           if(add_stream){
              $("#side-c").prepend("<span class='sub_title'>Related to '"+stream_title+"'</span>\n<div class='slidey'>"+html.join('')+"</div>");
           }
          }else{
            $("#"+ele).append('');
          }


   $(document).trigger('refresh');
   $(document).trigger('refresh_buttons');
}

        
//show disconnect overlay

function show_disconnect(){
   //console.log("disconnecting");

   $('#disconnected').show();
   show_grey_bg();
   $("#nick1").focus();

}

//when the user enters their name, tell buttons

function get_name() {
    var name = null;

    $.ajax({
        url: 'get_name.php',
        async: false,
        success: function(response) {
            name = response;
        }
    });
    console.log('The name retrieved from session variable is ' + name);
    return name;
}


function add_name(){
  
  var name = get_name();
  if(name){

    var me = new Person(name,name);
    buttons.me = me;
    $(document).trigger('send_name');
    //$("#ask_name").hide();
    //$("#bg").hide();

//get some 'personalised recommendations' 

    //get some 'personalised recommendations' 

    $.ajax({
      type: "GET",
      url: "get_suggestions.php",
      dataType: "json",
      success: function(data){
        //console.log(data);
        //var whatever = changeData(data);
        recommendations(data,"progs");
      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nokkkk "+textStatus);
      }
    });

    //Get user based content (if stored)

    $.ajax({
      type: "GET",
      url: "get_watch_later.php",
      dataType: "json",
      success: function(data){
        console.log(data);

        watch_later_json = data;
        var watch_later_items = watch_later_json.suggestions;
        for(var i in watch_later_items){
          var id = watch_later_items[i].id;
          list_watch_later.push(id);
          console.log("STORED IN WATCH LATER IDS:  " + id);
        }
        //recommendations(data,"list_later");

      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nok "+textStatus);
      }

    });

    $.ajax({
      type: "GET",
      url: "get_recently_viewed.php",
      dataType: "json",
      success: function(data){
        console.log(data);
        recently_viewed_json = data;
        var recently_viewed_items = recently_viewed_json.suggestions;
        for(var i in recently_viewed_items){
          var id = recently_viewed_items[i].id;
          list_recently_viewed.push(id);
          console.log("STORED IN RECENTLY VIEWED:  " + id);
        }
        recommendations(data,"history");

      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nok "+textStatus);
      }

    });

    $.ajax({
        url: 'get_tedtalks.php',
        dataType: "json",
        async: false,
        success: function(data) {
            var whatever = changeData(data);
            console.log(whatever);
            console.log("TED TALKS RETREIVED");
        }
    });
    console.log('The name retrieved from session variable is ' + name);
    return name;


  }
  var state = {"canBeAnything": true};
  //history.pushState(state, "N-Screen", "/N-Screen/");
  window.location.hash=my_group;
  $("#logoutspan").show();
}

//ensure the drag and drop is working

$(document).bind('refresh', function () {
                $( "#draggable" ).draggable();
                $( ".programme" ).draggable(
                        {
                        opacity: 0.7,
                        helper: "clone",
                        zIndex: 2700,
      start: function() {
                          $(".snaptarget").addClass( "dd_highlight"); 
                          $(".snaptarget_tv").addClass( "dd_highlight"); 
                          $(".snaptarget_group").addClass( "dd_highlight"); 
                          $(".snaptarget_bot").addClass( "dd_highlight"); 
      },
      drag: function() {
                          $(".snaptarget").addClass( "dd_highlight"); 
                          $(".snaptarget_tv").addClass( "dd_highlight"); 
                          $(".snaptarget_group").addClass( "dd_highlight"); 
                          $(".snaptarget_bot").addClass( "dd_highlight"); 
      },
      stop: function() {
                          $(".snaptarget").removeClass( "dd_highlight"); 
                          $(".snaptarget_tv").removeClass( "dd_highlight"); 
                          $(".snaptarget_group").removeClass( "dd_highlight"); 
                          $(".snaptarget_bot").removeClass( "dd_highlight"); 
      }

                }).addTouch();
                $( ".large_prog" ).draggable(
                        {
                        opacity: 0.7,
                        helper: "clone",
                        zIndex: 2700
                }).addTouch();

                $( ".snaptarget" ).droppable({
           
                        hoverClass: "dd_highlight_dark",
                        drop: function(event, ui) {
     
                                var el = $(this);
                                var jid = el.attr('id');
                                var el3 = ui.helper;
                                var el2 = el3.parent();

                                var a = get_object("a1");
                                a.play();

                                var res = get_data_from_programme_html(el3);//??
                                var url = el3.attr('href');
                                buttons.share(res,new Person(jid,jid));

                                $( this ).addClass( "dd_highlight",10,function() {
                                        setTimeout(function() {
                                                el.removeClass( "dd_highlight" ,100);
                                        }, 1500 );

                                });
                        }

                }).addTouch();
                $( ".snaptarget_group" ).droppable({
           
                        hoverClass: "dd_highlight_dark",
                        drop: function(event, ui) {
     
                                var el = $(this);
                                var jid = el.attr('id');
 
                                var el3 = ui.helper;
                                var el2 = el3.parent();

                                var a = get_object("a1");
                                a.play();

                                var res = get_data_from_programme_html(el3);//??
                                var url = el3.attr('href');
                                buttons.share(res);

                                $( this ).addClass( "dd_highlight",10,function() {
                                        setTimeout(function() {
                                                el.removeClass( "dd_highlight" ,100);
                                        }, 1500 );

                                });
                        }

                }).addTouch();


                $( ".snaptarget_bot" ).droppable({
           
                        hoverClass: "dd_highlight_dark",
                        drop: function(event, ui) {
     
                                var el = $(this);
                                var jid = el.attr('id');
 
                                var el3 = ui.helper;
                                var el2 = el3.parent();

                                var a = get_object("a1");
                                a.play();
                                var res = get_data_from_programme_html(el3);//??


                                html3 = [];
                                html3.push("<div id=\""+res["id"]+"_favs\" pid=\""+res["pid"]+"\" href=\""+recs["video"]+"\" class=\"ui-widget-content button programme ui-draggable open_win\">");
                                html3.push("<img class=\"img\" src=\""+res["image"]+"\" />");
                                html3.push("<span class=\"p_title\">"+res["title"]+"</a>");
                                html3.push("<p class=\"description large\">"+res["description"]+"</b></p>");
                                html3.push("</div>");
                                $('#favs').prepend(html3.join(''));
                                buttons.share(res,new Person(jid,jid))

                                $( this ).addClass( "dd_highlight",10,function() {
                                        setTimeout(function() {
                                                el.removeClass( "dd_highlight" ,100);
                                        }, 1500 );

                                });
                        }

                }).addTouch();

                $( ".snaptarget_tv" ).droppable({  //for tvs

                        hoverClass: "dd_highlight_dark",
                        drop: function(event, ui) {

                                var el = $(this);
                                var jid = el.attr('id');
                         
                                var el3 = ui.helper;
                                var el2 = el3.parent();

                                var a = get_object("a1");
                                a.play();

                                var res = get_data_from_programme_html(el3);//??
                                res["action"]="play";
                                res["shared_by"] = buttons.me.name;
                                var url = el3.attr('href');
                                var name = jid;
//go throgh the roster and send to all tvs
                                share_to_tvs(res);
                                $( this ).addClass( "dd_highlight",10,function() {
                                        setTimeout(function() {
                                                el.removeClass( "dd_highlight" ,100);
                                
                                        }, 1500 );
                                
                                });
                        }
                                
                }).addTouch();

});


function share_to_tvs(res){
////hm this should be an ajax call
                                var roster = buttons.blink.look();
                                if(roster){
                                  for(r in roster){
                                    var item = roster[r];
                                    if(item.obj_type =="tv"){
                                      var nm = item.name;
                                      buttons.share(res, new TV(nm,nm));//need to send this to a list of tvs

                                    }
                                  }
                                }

}

// various triggered things

// Connect to the service as me

$(document).bind('send_name', function () {
  console.log("sending name and connecting "+buttons.me.name);
  buttons.connect(buttons.me,my_group,false); // third arg is debugging
});


//when connection is confirmed
$(document).bind('connected', function (ev,blink) {
  //get the initial stuff
  console.log("SUPER CONECTED --> CALLING BLINK_cALLBACK")
  blink_callback(blink);
});

//what to do when disconnected

$(document).bind('disconnected',function(){
   console.log("disconnecting");
   if(interval){
     clearInterval(interval);//stop polling
   }
   $('#disconnected').show();
   show_grey_bg();
   $("#nick1").focus();
   Logout();
});

///webpages - this is early stuff
function generate_html_for_webpage(j,n,id){

      var title=j["title"];
      var link=j["link"];
      var classes = null;
      var html = [];

      html.push("<div id=\""+id+"\" pid=\""+id+"\" href=\""+link+"\" ");
      if(classes){
        html.push("class=\""+classes+"\">");
      }else{
        html.push("class=\"ui-widget-content button programme open_win_web\">");
      }
      var img = "images/webpage.png";
//      html.push("<div><a href='"+link+"' target='_blank'><img class=\"img\" src=\""+img+"\" /></a></div>");
      html.push("<div><img class=\"img\" src=\""+img+"\" /></div>");
      html.push("<span class=\"p_title p_title_small\"><a href='"+link+"' target='_blank'>"+title+"</a></span>");
      html.push("<div clear=\"both\"></div>");
      if(n){                    
        html.push("<span class=\"shared_by\">Shared by "+n+"</span>");
      }
      html.push("</div>");
      return html
}    


//video on the client - even earlier stuff
function generate_html_for_video(j,n,id){

      var title=j["title"];
      var link=j["link"];
      var classes = null;
      var html = [];

      html.push("<div id=\""+id+"\" pid=\""+id+"\" href=\""+link+"\"");
      if(classes){
        html.push("class=\""+classes+"\">");
      }else{
        html.push("class=\"ui-widget-content button programme open_vid_win\">");
      }
      var img = "images/video.png";
      html.push("<div><img class=\"img\" src=\""+img+"\" /></div>");

      html.push("<span class=\"p_title p_title_small\">"+title+"</span>");
      // html.push("<div clear=\"both\"></div>");
      if(n){                    
        html.push("<span class=\"shared_by\">Shared by "+n+"</span>");
      }
      html.push("</div>");
      return html
}    


//from a programme html element, get the json

function get_data_from_programme_html(el){
     var item_type = "programme";
     var id = el.attr('id');
     var pid = el.attr('pid');
     var video = el.attr('href');
     var more = el.attr('more');
     var service = el.attr('service');
     var is_live = el.attr('is_live');
     var manifest = el.attr('manifest');
     var img = el.find("img").attr('src');
     var title=el.find(".p_title").text();
     if(!title){
        title=el.find(".p_title_large").text();
     }
     var desc=el.find(".description").text();
     var explain=el.find(".explain").text();
                                                
     var res = {};                 
     res["id"]=id;
     res["pid"]=pid;
     res["video"]=video;
     res["image"]=img;
     res["title"]=title; 
     res["more"]=more; 
     res["service"]=service; 
     res["item_type"]=item_type; 
     res["is_live"]=is_live; 

     res["manifest"]=manifest; 
     res["description"]=desc;
     res["explanation"]=explain;
     return res;
                                
}

//Adapt ted-talks http requesst to our ow data format

function changeData(data){

  var random_ted = {
    suggestions: []
  };
  

  for(var i = 0; i < data.talks.length; i++) {  var item = data.talks[i];
      for(var j = 0; j < data.talks[i].talk.photo_urls.length; j++){
        if(data.talks[i].talk.photo_urls[j].size == "240x180"){
          var image = data.talks[i].talk.photo_urls[j].url;
        }
      } 
      random_ted.suggestions.push({ 
          "pid"   : item.talk.id,
          "title" : item.talk.name,          
          "description" : item.talk.description,
          "date_time" : item.talk.published_at,
          "media_profile_uris" : item.talk.media_profile_uris,
          "url" : item.talk.media_profile_uris, //TODO CHANGE THIS
          "video" : item.talk.media_profile_uris,
          "speaker" : item.talk.speakers,
          "image" : image,
          "manifest" : {
              "pid"   : item.talk.id,
              "id" : item.talk.id,          
              "title" : item.talk.name,
              "image" : image,
              "provider" : "ted",
              "duration" : 1750,
              "media": {
                "swf": {
                  "type": "video/x-swf",
                  "uri": item.talk.media_profile_uris
                }
              }
          },
          "tags" : item.talk.tags
      });
  }console.log(random_ted);return random_ted;
}

//when the group changes, update the roster

$(document).bind('items_changed',function(ev,blink){
    get_roster(blink);
     $(document).trigger('refresh');
     $(document).trigger('refresh_buttons');
     //$(document).trigger('refresh_group');
    // $(document).trigger('refresh_history');
    // $(document).trigger('refresh_recs');
    // $(document).trigger('refresh_search');
    // //my new channels
    // $(document).trigger('refresh_later');
    // $(document).trigger('refresh_ld');

});

//creates a new id from a programme and a person name string
function generate_new_id(j,n){
  var i = j["pid"]+"_"+n; //not really unique enough
  return i;
}

//when someone shares something, put a copy of it in the right place

$(document).bind('shared_changed', function (e,programme,name,msg_type) {
  var a = get_object("a2");
  a.play();

  var id = generate_new_id(programme,name);

  console.log("THE ID OF THE PROGRAM SHARED IS " + id);
  var msg_text = "";
  var html = null

  if(programme.item_type=="webpage"){
    html = generate_html_for_webpage(programme,name,id);
    msg_text = name+" shared <a onclick='show_webpage(\""+programme["link"]+")'>"+programme["title"]+"</a> with you";
    if(msg_type=="groupchat"){
      msg_text = name+" shared <a onclick='show_webpage(\""+programme["link"]+")'>"+programme["title"]+"</a> with the group";
    }
  }else{
    if(programme.item_type=="video"){
      html = generate_html_for_video(programme,name,id);
      msg_text = name+" shared <a onclick='show_video(\""+programme["link"]+"\")'>"+programme["title"]+"</a> with you";
      if(msg_type=="groupchat"){
        msg_text = name+" shared <a onclick='show_video(\""+programme["link"]+"\")'>"+programme["title"]+"</a> with the group";
      }
    }else{
      html = generate_html_for_programme(programme,name,id);
      msg_text = name+" shared "+programme["title"]+" with you";
      if(msg_type=="groupchat"){
        msg_text = name+" shared "+programme["title"]+" with the group";
      }
    }
  }

  $('#shared').append(html.join(''));

//notifications 
  build_notification(msg_text,programme,name);

  // $(document).trigger('refresh_group');
  // $(document).trigger('refresh_buttons');
  // $(document).trigger('refresh_history');
  // $(document).trigger('refresh_recs');
  // $(document).trigger('refresh_search');

  // //my new channels
  // $(document).trigger('refresh_later');
  // $(document).trigger('refresh_ld');

});

function build_notification(msg_text,programme,name){

  console.log("Detected shared item");
  if(lastmsg!=msg_text){
    var p = $("#notify").text();
    var num = parseInt(p);

    if(!num){
      num=1;
    }else{
      num = num+1;
    }

    lastmsg = msg_text;
    var nid = generate_new_id(programme,name)+"_notification";
    $("#notify").html(num);
    $("#notify").show();
    $("#notify_large").prepend("<div id='"+nid+"' class='dotty_bottom'>"+msg_text+" </div>");//not sure if append / prepend makes most sense

  }            
}

//create html from a programme
function generate_html_for_programme(j,n,id){

      var pid=j["pid"];
      var video = j["video"];
      var title=j["title"];
      if(!title){
         title = j["core_title"];
      }
      var img=j["image"];
      if(!img){
        img=j["depiction"];
      }
      var manifest=j["manifest"];
      var more=j["more"];
      var explanation=j["explanation"];
      var desc=j["description"];
      var classes= j["classes"];
      var is_live = false;
      if(j["live"]==true || j["live"]=="true" || j["is_live"]==true || j["is_live"]=="true"){
        is_live = true;
      }
      var service = j["service"];
      var channel = j["channel"];
      if(channel && is_live){
        img = "channel_images/"+channel.replace(" ","_")+".png";
      }


      var html = [];
      html.push("<div id=\""+id+"\" pid=\""+pid+"\"");
      if(more){
        html.push(" more=\""+more+"\"");
      }
      html.push(" is_live=\""+is_live+"\"");

      if(video){
        html.push("  href=\""+video+"\"");
      }
      if(service){
        html.push("  service=\""+service+"\"");
      }
      if(manifest){
        html.push("  manifest=\""+manifest+"\"");
      }
      if(classes){
        html.push("class=\""+classes+"\">");
      }else{
        html.push("class=\"ui-widget-content button programme open_win\">");
      }
      html.push("<div class='img_container'><img class=\"img\" src=\""+img+"\" />");
      html.push("</div>");
      if(is_live){
       html.push("Live: ");

      }else{
      }
      html.push("<span class=\"p_title p_title_small\">"+title+"</span>");
      // html.push("<div clear=\"both\"></div>");
      if(n){                    
        html.push("<span class=\"shared_by\">Shared by "+n+"</span>");
      }
      if(desc){
        html.push("<span class=\"description large\">"+desc+"</span>");
      }
/*
      if(explanation){

        //string.charAt(0).toUpperCase() + string.slice(1);
        explanation = explanation.replace(/_/g," ");
        var exp = explanation.replace(/,/g," and ");

        html.push("<span class=\"explain_small\">Matches "+exp+" in your profile</span>");
      }
*/
      html.push("</div>");
      return html
}    

//when the TV changes, print out what's being watched

$(document).bind('tv_changed', function (ev,item) {
  var ct,cid;
  var ot = item.obj_type;
  var id = "tv";
  if(ot=="tv"){
      ct = item.nowp["title"];
      cid = item.nowp["id"];
  }
  var pid = item.nowp["pid"];

  $("#tv").find(".dotted_spacer").html(ct)
  $("#tv").attr("pid",pid);

//notifications

  $("#tv").find(".dotted_spacer").html(item.nowp["title"]);
  
  var msg_text = "TV started playing "+item.nowp["title"];
  if(item["nowp"]["state"]=="pause"){
    msg_text = "TV paused "+item.nowp["title"];
  }
  build_notification(msg_text,item.nowp, item.name);

});


//**************************TO DO*****************************

// $(document).bind('refresh_group', function () {

//                 $(".snaptarget_group").unbind('click');
//                 $( ".snaptarget_group" ).click(function() {

//                         $('.new_overlay').hide();
// //open a new overlay containing group shared
//                         $('#results').addClass("new_overlay");
//                         $('#results').show();
//                         show_grey_bg();
//                         return false;

//                 });

//                 $("#grp").unbind('click');
//                 $( "#grp" ).click(function() {

//                         $('.new_overlay').hide();
// //open a new overlay containing group shared
//                         $('#results').addClass("new_overlay");
//                         $('#results').show();
//                         show_grey_bg();
//                         return false;

//                 });

// });

//annoying bloody audio stuff
//http://codingrecipes.com/documentgetelementbyid-on-all-browsers-cross-browser-getelementbyid
function get_object(id) {
   var object = null;
   if (document.layers) {       
    object = document.layers[id];
   } else if (document.all) {
    object = document.all[id];
   } else if (document.getElementById) {
    object = document.getElementById(id);
   }
   return object;
}


function remove_search_text(){
  $("#search_text").attr("value","");
}

function close_notifications(){
  $("#notify_large").hide();
}

function show_grey_bg(){
 $("#bg").show();
}

function hide_overlay(){
 $("#bg").hide();
 $("#new_overlay").hide();
          
}

//FACEBOOK SDK for JavaScript-->
function userLogin(){
        FB.login(function(response){
           if (response.authResponse){
             console.log('Welcome!  Fetching your information.... ');
              FB.api('/me', function(response) {
              console.log('Successful login for: ' + response.name);
              document.forms["myname"].login.value = response.name;
              var id = response.id;
              var name = response.name;
              console.log('Your id is  ' + id);
              register(id,name);
              });
            } 
           else{
             console.log('User cancelled login or did not fully authorize.');
           }
         });
  };

  function register(id, name) {
    $.ajax({
        url: "facebook-register.php",
        type: "POST",
        data: "facebook_id="+id+"&firstname="+name,
        dataType: "json",
        success: function (response) {
            create_buttons();
            //add_name();
            //SHOULD REDIRECT TO US ONLY AREA---HAVE TO WORK ON IT
            window.location.href= "http://localhost/N-Screen/member-index.php";
        }
      });

  }

  window.fbAsyncInit = function() {
  FB.init({
    appId      : '710256039061787',
    cookie     : true,  // enable cookies to allow the server to access 
                        // the session
    xfbml      : true,  // parse social plugins on this page
    version    : 'v2.1' // use version 2.1
  });

  // Now that we've initialized the JavaScript SDK, we call 
  // FB.getLoginStatus().  This function gets the state of the
  // person visiting this page and can return one of three states to
  // the callback you provide.  They can be:
  //
  // 1. Logged into your app ('connected')
  // 2. Logged into Facebook, but not your app ('not_authorized')
  // 3. Not logged into Facebook and can't tell if they are logged into
  //    your app or not.
  //
  // These three cases are handled in the callback function.

  // FB.getLoginStatus(function(response) {
  //   statusChangeCallback(response);
  // });

  };

  // Load the SDK asynchronously
  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));

  // Logout Function
  function Logout() {

    var lalala = 'whatever';
    $.ajax({
       url: 'logout.php',
       async : false,
         success: function(){
           window.location.href= "http://localhost/N-Screen/index.html";
         }
      });
    //console.log("RESPUESTA  "+ lalala):
    //FB.logout(function () { document.location.reload(); });
  }
</script>

  <div id="header">
    <span id='main_title'>Browse Programmes</span>
    <span id='small_title'><a target='_blank' href='player.html'>Open player in new window</a></span>
    <span class="form" >
      <form onsubmit='javascript:do_search(this.search_text.value);return false;'>
        <input type="text" id="search_text" name="search_text" value="search programmes" onclick="javascript:remove_search_text();return false;"/>
      </form>
    </span>
    <div id="title"></div>

  </div>

       
<!-- <br clear="both"/> -->

  <div class="notifications_red" id="notify"></div>
  <div class="notifications_red_large" id="notify_large" onclick="javascript:close_notifications();"></div>


<div id="container">


  <div id="inner">
   

    <div id="browser">
      <div id="side-b" class="slidey">
        <span class="sub_title">SUGGESTIONS FOR YOU</span> 
        <span class="more_blue"><a onclick='show_more_recommendations();'>View All</a></span>
        <div id="progs"> </div>
      </div>
     

      <div id="content" class="slidey">
        <span class="sub_title">SHARED BY FRIENDS</span>
        <span class="more_blue"><a onclick='show_shared();'>View All</a></span>
        <div id="results">
         <div class='dotted_box'> </div>
        </div>
      </div>

      <!-- <br clear="all" /> -->

      <div id="content2" class="slidey">
        <span class="sub_title">RECENTLY VIEWED</span>
        <span class="more_blue"><a onclick='show_history();'>View All</a></span>
        <div id="history">
          <div class='dotted_box'> </div>
        </div>
      </div>

      
      <div id="content3" class="slidey">
        <span class="sub_title">WATCH LATER</span>
        <span class="more_blue"><a onclick='show_later();'>View All</a></span>
        <div id="list_later">
          <div class='dotted_box'> </div>
        </div>
      </div>
 
      <div id="side-c">
      </div>
    </div>

      <div id="search_results"></div>
    <!-- <br clear="both" /> -->

  </div>

</div>

<div id="roster_wrapper">
  
  <div id="side-a">
    <div id="tv"></div>
      <!-- <br clear="both"/> -->
      <!-- <h3 class="contrast">YOUR FRIENDS</h3> -->
    <div id="roster"></div>
  </div>
</div>

<div id="footer">
  <div id="button_container">

   <div id="browse" class="blue menu"><a href="javascript:show_browse_programmes()">BROWSE PROGRAMMES</a></div>
   <div id="random" class="grey menu"><a href="javascript:do_random()">RANDOM SELECTION</a></div>

  </div>
</div>



<p style="display: none;"><small>Status:
<span id="demo">
<span id="out"></span>
</span></small></p>

<!-- overlays -->

<div id='new_overlay' style='display:none;'><div class='close_button'><img src='images/close.png'/></div></div>
<div id='bg' style='display:none;' onclick='javascript:hide_overlay()'></div>


        
            <div id="ask_name" style="display: none;" class="alert">
            <h2 id="inline1_sub">Please enter your name:</h2>
              <form onsubmit="javascript:add_name();return false;" id="myname">
                 <input class="forminput" type="text" name="nick" id="login" spellcheck="false"  autocorrect="off"/>
                 <input class='bluesubmit' type="submit" name="go" value="Start" />               
              </form>
              </div>
        

                
            <div id="disconnected" style="overflow:auto;display: none;" class="alert">
              <h2>Sorry, you've been disconnected - please reload the page.</h2>
            </div>


</body>
</html>



