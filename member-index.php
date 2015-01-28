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
    <script type="text/javascript" src="lib/jquery.ui.touch-punch.min.js"></script> 

    <script type="text/javascript" src="lib/strophe.js"></script>
    <script type="text/javascript" src="lib/buttons.js"></script>
    <script type="text/javascript" src="lib/spin.min.js"></script>
    <script type="text/javascript" src="lib/play_video.js"></script>

  <link type="text/css" rel="stylesheet" href="css/new.css" />


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
var start_url = "get_channel.php";
var random_url = "get_random.php"

//jabber server
var server = "jabber.notu.be";
//var server = "jabber.notu.be";

//polling interval (for changes to channels)
var interval = null;

//handle back and forward navigating
var overlay_navigation = [];
var overlaycounter = null;

//var list = new Array(); //initialazing array

// Set of local variables that help us to store later (if so)

var list_watch_later = [];
var watch_later_json = {};

var list_recently_viewed = [];
var recently_viewed_json = {};

var list_shared_by_friends = [];
var shared_by_friends_json = {};

var list_likes = [];
var list_dislikes = [];

var likes_json = {};
var real_likesdislikes_json = {}; //to update later
var dislikes_json = {};

var random_json = {};
var recommendations_json = {};

var related_json = {};

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

   // $("#main_title").html("N-SCREEN");

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
   history.pushState(state, "N-Screen", "http://localhost/N-Screen/");
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

function add_name(){
  
  var name = get_name();
  if(name){

    var me = new Person(name,name);
    buttons.me = me;
    $(document).trigger('send_name');

    //get some 'recommendations' --> this should be rendered by a recommendation algorithm 
    $.ajax({
      type: "POST",
      url: "get_channel.php",
      //async: false,
      data: {channel: "recommendations"},
      dataType: "json",
      success: function(data){
        //console.log(data);
        //var whatever = changeData(data);
        recommendations_json = data; //set global variable to use later if so
        //console.log(recommendations_json);
        recommendations(data,"progs");
      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nokkkk "+textStatus);
      }
    });

    //Get WATCH LATER
    $.ajax({
      type: "POST",
      url: "get_channel.php",
      //async: false,
      data: {channel: "watch_later"},
      dataType: "json",
      success: function(data){
        if(data!= null){

            watch_later_json = data;
            // console.log(watch_later_json);
            var watch_later_items = watch_later_json.suggestions;
            for(var i in watch_later_items){
              var pid = watch_later_items[i].pid;
              list_watch_later.push(pid);
            }
            recommendations(data,"list_later");
        }
        else{
         watch_later_json = {
              suggestions: []
          };
         list_watch_later = [];

        }

      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nok "+textStatus);
      }

    });

    //GET RECENTLY VIEWED

    $.ajax({
      type: "POST",
      url: "get_channel.php",
      //async: false,
      data: {channel: "recently_viewed"},
      dataType: "json",
      success: function(data){
        if(data!= null){
        //console.log(data);
        recently_viewed_json = data;
        var recently_viewed_items = recently_viewed_json.suggestions;
        //console.log(recently_viewed_items);
        if(recently_viewed_items.length != 0){
          for(var i in recently_viewed_items){
            var pid = recently_viewed_items[i].pid;
            list_recently_viewed.push(pid);
          }
        }
        recommendations(data,"history");
        }
        else{
         recently_viewed_json = {
              suggestions: []
          };
         list_recently_viewed = [];

        }

      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nok "+textStatus);
      }

    });

    $.ajax({
      type: "POST",
      url: "get_channel.php",
      //async: false,
      data: {channel: "shared_by_friends"},
      dataType: "json",
      success: function(data){
        if(data!= null){
        //console.log(data);
        //var whatever = changeData(data);
        shared_by_friends_json = data; //set global variable to use later if so
        var shared_by_friends_items = shared_by_friends_json.suggestions;
        if(shared_by_friends_items.length != 0){
          for(var i in shared_by_friends_items){
            var pid = shared_by_friends_items[i].pid;
            list_shared_by_friends.push(pid);
          }
        }
        //console.log(recommendations_json);
        recommendations(data,"results");
      }
      else{
         shared_by_friends_json = {
              suggestions: []
          };
         list_shared_by_friends = [];
        }

      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nokkkk "+textStatus);
      }
    });

    $.ajax({
      type: "POST",
      url: "get_channel.php",
      //async: false,
      data: {channel: "like_dislike"},
      dataType: "json",
      success: function(data){
        real_likesdislikes_json = data;
        // console.log(JSON.stringify(data));
        //console.log(data);
        likes_json = {
              suggestions: []
        };

        dislikes_json = {
              suggestions: []
         };
        if(data.likes.length >0){
          // ---- LIKES ----
          // likes_json = data.likes;
          likes_json.suggestions = JSON.parse(JSON.stringify(data.likes));//set global variable to use later if so
          var likes_items = likes_json.suggestions;
          if(likes_items.length != 0){
            for(var i in likes_items){
              var pid = likes_items[i].pid;
              list_likes.push(pid);
            }
          }
          console.log(likes_json);
          recommendations(likes_json,"list_likes");
        }
         if(data.dislikes.length >0){
          // ---- DISLIKES ----
          dislikes_json = {
              suggestions: []
          };
          dislikes_json.suggestions = JSON.parse(JSON.stringify(data.dislikes));//set global variable to use later if so
          var dislikes_items = dislikes_json.suggestions;
          if(dislikes_items.length != 0){
            for(var i in dislikes_items){
              var pid = dislikes_items[i].pid;
              list_dislikes.push(pid);
            }
          }
          
          //console.log(recommendations_json);
          console.log(likes_json);
          console.log(dislikes_json);
          recommendations(dislikes_json,"list_dislikes");
        }
      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nokkkk "+textStatus);
      }
    });
  }
  var state = {"canBeAnything": true};
  //history.pushState(state, "N-Screen", "/N-Screen/");
  window.location.hash=my_group;
  $("#logoutspan").show();
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
  // console.log("INSIDE BLINK CALLBACK --> GOING TO CALL GET CHANNEL")
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

//UPDATE REGARDING COLUMN IN CONTENT DATABASE 
// watch_later, recently_viewed, shared_by_friends....
function update_channel(channel, data){

  if(channel == "like_dislike"){
    console.log(JSON.stringify(real_likesdislikes_json));
    real_likesdislikes_json.likes = likes_json.suggestions;
    real_likesdislikes_json.dislikes = dislikes_json.suggestions;
    console.log(JSON.stringify(real_likesdislikes_json));
    $.ajax({
      url: "set_channel.php",
      type: "POST",
      data: {data : JSON.stringify(real_likesdislikes_json), channel : channel},
      dataType: "json",
      success: function (response) {
          console.log("Correct " + channel + " updated");
      }
    });
  }
  else{
    $.ajax({
      url: "set_channel.php",
      type: "POST",
      data: {data : JSON.stringify(data), channel : channel},
      dataType: "json",
      success: function (response) {
          console.log("Correct " + channel + " updated");
      }
    });
  }
}


//display suggestions based on id 
// RECENTLY VIEWED !!

function insert_suggest2(id) {

  // console.log("list shared by friends");
  // console.log(list_shared_by_friends);
      if (overlaycounter == null){
        overlaycounter = 0;
      }
      else{
        overlaycounter ++;
      }
      overlay_navigation.splice(overlaycounter, 0, id);
      for (var i= overlay_navigation.length - 1; i > overlaycounter ; i--){
        overlay_navigation.splice(i, 1);
      }
      var item = {};

      var flag = false //to set recently viewed icon or not

      //code to check how to place the element in recently viewed 
      if(update_list(id,list_recently_viewed) == false){ //this means that the element IS already in the list
        flag = true;
        $('#history').find("#"+id).remove();
        for(var i in recently_viewed_json.suggestions){
          if(recently_viewed_json.suggestions[i].pid == ('' + id)){
            // console.log("inside if");
            item = recently_viewed_json.suggestions[i]; 
            recently_viewed_json.suggestions.splice(i,1); //we remove the item from the local json
          }
        }recently_viewed_json.suggestions.splice(0,0,item);
      }
      else{
        $.ajax({
          url: "get_tedtalks_by_id.php",
          type: "POST",
          async: false,
          data: {id: id},
          dataType: "json",
          success: function (data) {
              item =  changeData(data); //JSON with suggestions format
              recently_viewed_json.suggestions.splice(0,0,item.suggestions[0]);
          }
        });
      }//recently_viewed_json.suggestions[0] is the current video now displayed   
      update_channel("recently_viewed", recently_viewed_json);
      var div = $("#"+id);

      var speaker = (/(.*):.*?/.exec(recently_viewed_json.suggestions[0].title))[1];
      var title = (/.*?:(.*)/.exec(recently_viewed_json.suggestions[0].title))[1];
      var description = recently_viewed_json.suggestions[0].description;
      // var tags = lalala **********************TO DO*********************
      var video = recently_viewed_json.suggestions[0].video;
      var pid = recently_viewed_json.suggestions[0].pid;
      var img = recently_viewed_json.suggestions[0].image;

      var tags = Object.keys(recently_viewed_json.suggestions[0]["tags"]);
      // var tags = (/[^,]*/.exec(tags));
      // tags = (/[^, ]*/.exec(tags)); //array of tags
      html = [];
      html.push("<div id=\""+id+"\" pid=\""+pid+"\" href=\""+video+"\" class=\"ui-widget-content button programme ui-draggable\"" + "onclick=\"javascript:insert_suggest2("+pid+");return true\">");
      html.push("<img class=\"img img_small\" src=\""+img+"\" />");
      html.push("<span class=\"p_title p_title_small\"><a>"+title+"</a></span>");
      html.push("<p class=\"description large\">"+description+"</p>");
      html.push("</div>");
      $('#history').prepend(html.join(''));


      html2 = [];

      html2.push("<div class='close_button'><img src='images/icons/exit.png' width='12px' onclick='javascript:hide_overlay();'/></div>");
      html2.push("<div class='navigation_buttons'><img onclick='javascript:navigation(-1);' style='display: inline; margin: 0 5px; cursor:pointer;' title='back' src='images/icons/backward.png' width='30'/><img onclick='javascript:navigation(+1);' style='display: inline; margin: 0 5px; cursor:pointer;' title='forward' src='images/icons/forward.png' width='30'/></div>");
      html2.push("<div id=\""+id+"\" pid=\""+pid+"\" href=\""+video+"\"  class=\"large_prog\" style=\"position: relative;\">");
      html2.push("<div class=\"gradient_div\" style=\"text-align: center;  margin-left: 45%; position: absolute; \"> <img class=\"img\" src=\""+img+"\" />");
      html2.push("<div class=\"play_button\"><img style='width: 120px;' src=\"images/icons/play.png\" /></a></div></div>");
      html2.push("<div style='padding-left: 20px; padding-right: 20px; width: 50%; left: 0px; position: absolute;'>");
      html2.push("<div class=\"p_title_large_speaker\">"+speaker+':'+"</div>");
      html2.push("<div class=\"p_title_large\">"+title+"</div>");
      html2.push("<p class=\"description\">"+description+"</p>");
      html2.push("<div class=\"list_tags\" style='display:inline;'>");
      for(var i=0; i<tags.length; i++){
        if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){ }
        else{
          html2.push("<span class=\"item_tag\" onclick=\"javascript:insert_suggest_by_tag('"+tags[i]+"');return true\">#"+tags[i]+"</span>");
        }
        
      }
      html2.push("</div>");
      // html2.push("<p class=\"explain\">"+explanation+"</p>");
//      html2.push("<p class=\"keywords\">"+keywords+"</p>");
      html2.push("<p class=\"link\"><a href=\"http://www.ted.com/talks/view/id/"+pid+"\" target=\"_blank\">Sharable Link</a></p></div>");

      html2.push("<div class='vertical_buttons' style='display:table-cell; vertical-align: middle; margin-right: 7%; position: absolute; text-align: center; right:0; top: 10px'>");

      if(not_in_list(id,list_watch_later)){
              html2.push("<div id='watchlater'class=\"interactive_icon\"><img id='addtowatchlater' style='width: 40px;' src=\"images/icons/watch_later.png\" /><span style='display: block'; class ='inter_span'>Watch Later</span></div>");      
      }
      else{
              html2.push("<div id='watchlater'class=\"interactive_icon\"><img id='deletewatchlater' style='width: 40px;' src=\"images/icons/on_watch_later.png\" /><span style='display: block'; class ='on_inter_span'>Watch Later</span></div>");      
      }

      if(not_in_list(id,list_likes)){
        html2.push("<div id='like' class=\"interactive_icon\"><img id='addtolike' style='width: 40px;' src=\"images/icons/like.png\" /><span style='display: block'; class ='inter_span'>Like</span></div>");
      }
      else{
        html2.push("<div id='like' class=\"interactive_icon\"><img id='deletelike' style='width: 40px;' src=\"images/icons/on_like.png\" /><span style='display: block'; class ='on_inter_span'>Like</span></div>");
      }
      if(not_in_list(id,list_dislikes)){
      html2.push("<div id='dislike' class=\"interactive_icon\"><img id = 'addtodislike' style='width: 40px;' src=\"images/icons/dislike.png\" /><span style='display: block'; class ='inter_span'>Dislike</span></div>");
      }
      else{
      html2.push("<div id='dislike' class=\"interactive_icon\"><img id = 'deletedislike' style='width: 40px;' src=\"images/icons/on_dislike.png\" /><span style='display: block'; class ='on_inter_span'>Dislike</span></div>");
      }
      if(not_in_list(id,list_shared_by_friends)){
        html2.push("<div class=\"interactive_icon\"><img style='width: 40px;' src=\"images/icons/shared.png\" /><span style='display: block'; class ='inter_span'>Shared by friends</span></div>");
      }
      else{
        html2.push("<div class=\"interactive_icon\"><img style='width: 40px;' src=\"images/icons/on_shared.png\" /><span style='display: block'; class ='on_inter_span'>Shared by friends</span></div>");
      }
      if(flag == false){
        html2.push("<div class=\"interactive_icon\"><img style='width: 40px;' src=\"images/icons/recently_viewed.png\" /><span style='display: block'; class ='inter_span'>Recenlty Viewed</span></div></div>");
      }
      else{
        html2.push("<div class=\"interactive_icon\"><img style='width: 40px;' src=\"images/icons/on_recently_viewed.png\" /><span style='display: block'; class ='on_inter_span'>Recenlty Viewed</span></div></div>");
      }
      html2.push("</div>");
      html2.push("</div>");

      if(recently_viewed_json.suggestions[0].manifest){
      var manifest = recently_viewed_json.suggestions[0].manifest;
      var data =  recently_viewed_json.suggestions[0].manifest; 
      set_playable(data);

      // $.ajax({
      //  url: recently_viewed_json.suggestions[0].manifest,
      //  dataType: "json",
      //    success: function(data){
      //      set_playable(data);
      //    },
      //    error: function(jqXHR, textStatus, errorThrown){
      //      alert("oh dear "+textStatus);
      //    }
      // });
    }

      

      $('#new_overlay').html(html2.join(''));
    
      $('#new_overlay').show();
      show_grey_bg();

      // $(".play_button").live( "click", function() {

      // console.log("PLAY PRESSED!!!");
      //   var res = {};
      //   // res["id"]=id;
      //   res["pid"]=pid;
      //   res["title"]=title;
      //   res["video"]=video;
      //   res["description"]=description;
      //   // res["explanation"]=explanation;
      //   res["img"]=id;
      //   sendProgrammeTVs(res,my_tv); 
      //   return false;

      // });


      $('#new_overlay').append("<div id=\"more_like_this\" class=\"more_like_this\" style=\"margin-top: 400px;\"><span class=\"sub_title\">MORE LIKE THIS</span><span class=\"more_blue\"><a id ='more_related' onclick='show_related();''>View All &triangledown;</a></span></div>");
      // $('#new_overlay').append("<br clear=\"both\"/>");
      $('#new_overlay').append("<div id='spinner' style=\"float: left;\"></div>");
      $('#new_overlay').append("<div class='clear'></div>");
      
      // var target = document.getElementById('spinner');//??
      // var spinner = new Spinner(opts).spin(target);

      for(var i=0; i<tags.length; i++){
      //console.log("THIS IS TAG " + tags[i]);
      if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){}
      else {
        tag = tags[i];
        break;
        }
      }

      $.ajax({
       url: "get_tedtalks_related.php",
       data: {tag : tag},
       type: 'POST',
       dataType: "json",
         success: function(data){
          var related = changeData(data);
          related_json = related;
           recommendations(related,"spinner",false,title);
//           recommendations(data,"new_overlay",false,title);
         },
         error: function(jqXHR, textStatus, errorThrown){
         //alert("oh dear "+textStatus);
         }
      });

}

function navigate_by_id(id) {
      var item = {};

      var flag = false //to set recently viewed icon or not
      $.ajax({
        url: "get_tedtalks_by_id.php",
        type: "POST",
        async: false,
        data: {id: id},
        dataType: "json",
        success: function (data) {
            item =  changeData(data); //JSON with suggestions format
        }
      });  
      var div = $("#"+id);

      var speaker = (/(.*):.*?/.exec(item.suggestions[0].title))[1];
      var title = (/.*?:(.*)/.exec(item.suggestions[0].title))[1];
      var description = item.suggestions[0].description;
      // var tags = lalala **********************TO DO*********************
      var video = item.suggestions[0].video;
      var pid = item.suggestions[0].pid;
      var img = item.suggestions[0].image;

      var tags = Object.keys(item.suggestions[0]["tags"]);
      // var tags = (/[^,]*/.exec(tags));
      // tags = (/[^, ]*/.exec(tags)); //array of tags
      html = [];
      html.push("<div id=\""+id+"\" pid=\""+pid+"\" href=\""+video+"\" class=\"ui-widget-content button programme ui-draggable\"" + "onclick=\"javascript:insert_suggest2("+pid+");return true\">");
      html.push("<img class=\"img img_small\" src=\""+img+"\" />");
      html.push("<span class=\"p_title p_title_small\"><a>"+title+"</a></span>");
      html.push("<p class=\"description large\">"+description+"</p>");
      html.push("</div>");
      $('#history').prepend(html.join(''));


      html2 = [];

      html2.push("<div class='close_button'><img src='images/icons/exit.png' width='12px' onclick='javascript:hide_overlay();'/></div>");
      html2.push("<div class='navigation_buttons'><img onclick='javascript:navigation(-1);' style='display: inline; margin: 0 5px; cursor:pointer;' title='back' src='images/icons/backward.png' width='30'/><img onclick='javascript:navigation(+1);' style='display: inline; margin: 0 5px; cursor:pointer;' title='forward' src='images/icons/forward.png' width='30'/></div>");
      html2.push("<div id=\""+id+"\" pid=\""+pid+"\" href=\""+video+"\"  class=\"large_prog\" style=\"position: relative;\">");
      html2.push("<div class=\"gradient_div\" style=\"text-align: center;  margin-left: 45%; position: absolute; \"> <img class=\"img\" src=\""+img+"\" />");
      html2.push("<div class=\"play_button\"><img style='width: 120px;' src=\"images/icons/play.png\" /></a></div></div>");
      html2.push("<div style='padding-left: 20px; padding-right: 20px; width: 50%; left: 0px; position: absolute;'>");
      html2.push("<div class=\"p_title_large_speaker\">"+speaker+':'+"</div>");
      html2.push("<div class=\"p_title_large\">"+title+"</div>");
      html2.push("<p class=\"description\">"+description+"</p>");
      html2.push("<div class=\"list_tags\" style='display:inline;'>");
      for(var i=0; i<tags.length; i++){
        if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){ }
        else{
          html2.push("<span class=\"item_tag\" onclick=\"javascript:insert_suggest_by_tag('"+tags[i]+"');return true\">#"+tags[i]+"</span>");
        }
        
      }
      html2.push("</div>");
      // html2.push("<p class=\"explain\">"+explanation+"</p>");
//      html2.push("<p class=\"keywords\">"+keywords+"</p>");
      html2.push("<p class=\"link\"><a href=\"http://www.ted.com/talks/view/id/"+pid+"\" target=\"_blank\">Sharable Link</a></p></div>");

      html2.push("<div class='vertical_buttons' style='display:table-cell; vertical-align: middle; margin-right: 7%; position: absolute; text-align: center; right:0; top: 10px'>");

      if(not_in_list(id,list_watch_later)){
              html2.push("<div id='watchlater'class=\"interactive_icon\"><img id='addtowatchlater' style='width: 40px;' src=\"images/icons/watch_later.png\" /><span style='display: block'; class ='inter_span'>Watch Later</span></div>");      
      }
      else{
              html2.push("<div id='watchlater'class=\"interactive_icon\"><img id='deletewatchlater' style='width: 40px;' src=\"images/icons/on_watch_later.png\" /><span style='display: block'; class ='on_inter_span'>Watch Later</span></div>");      
      }

      if(not_in_list(id,list_likes)){
        html2.push("<div id='like' class=\"interactive_icon\"><img id='addtolike' style='width: 40px;' src=\"images/icons/like.png\" /><span style='display: block'; class ='inter_span'>Like</span></div>");
      }
      else{
        html2.push("<div id='like' class=\"interactive_icon\"><img id='deletelike' style='width: 40px;' src=\"images/icons/on_like.png\" /><span style='display: block'; class ='on_inter_span'>Like</span></div>");
      }
      if(not_in_list(id,list_dislikes)){
      html2.push("<div id='dislike' class=\"interactive_icon\"><img id = 'addtodislike' style='width: 40px;' src=\"images/icons/dislike.png\" /><span style='display: block'; class ='inter_span'>Dislike</span></div>");
      }
      else{
      html2.push("<div id='dislike' class=\"interactive_icon\"><img id = 'deletedislike' style='width: 40px;' src=\"images/icons/on_dislike.png\" /><span style='display: block'; class ='on_inter_span'>Dislike</span></div>");
      }
      if(not_in_list(id,list_shared_by_friends)){
        html2.push("<div class=\"interactive_icon\"><img style='width: 40px;' src=\"images/icons/shared.png\" /><span style='display: block'; class ='inter_span'>Shared by friends</span></div>");
      }
      else{
        html2.push("<div class=\"interactive_icon\"><img style='width: 40px;' src=\"images/icons/on_shared.png\" /><span style='display: block'; class ='on_inter_span'>Shared by friends</span></div>");
      }
      if(flag == false){
        html2.push("<div class=\"interactive_icon\"><img style='width: 40px;' src=\"images/icons/recently_viewed.png\" /><span style='display: block'; class ='inter_span'>Recenlty Viewed</span></div></div>");
      }
      else{
        html2.push("<div class=\"interactive_icon\"><img style='width: 40px;' src=\"images/icons/on_recently_viewed.png\" /><span style='display: block'; class ='on_inter_span'>Recenlty Viewed</span></div></div>");
      }
      html2.push("</div>");
      html2.push("</div>");

      if(item.suggestions[0].manifest){
      var manifest = item.suggestions[0].manifest;
      var data =  item.suggestions[0].manifest; 
      set_playable(data);

      // $.ajax({
      //  url: recently_viewed_json.suggestions[0].manifest,
      //  dataType: "json",
      //    success: function(data){
      //      set_playable(data);
      //    },
      //    error: function(jqXHR, textStatus, errorThrown){
      //      alert("oh dear "+textStatus);
      //    }
      // });
    }

      

      $('#new_overlay').html(html2.join(''));
    
      $('#new_overlay').show();
      show_grey_bg();

      // $(".play_button").live( "click", function() {

      // console.log("PLAY PRESSED!!!");
      //   var res = {};
      //   // res["id"]=id;
      //   res["pid"]=pid;
      //   res["title"]=title;
      //   res["video"]=video;
      //   res["description"]=description;
      //   // res["explanation"]=explanation;
      //   res["img"]=id;
      //   sendProgrammeTVs(res,my_tv); 
      //   return false;

      // });


      $('#new_overlay').append("<div id=\"more_like_this\" class=\"more_like_this\" style=\"margin-top: 400px;\"><span class=\"sub_title\">MORE LIKE THIS</span><span class=\"more_blue\"><a id ='more_related' onclick='show_related();''>View All &triangledown;</a></span></div>");
      // $('#new_overlay').append("<br clear=\"both\"/>");
      $('#new_overlay').append("<div id='spinner' style=\"float: left;\"></div>");
      $('#new_overlay').append("<div class='clear'></div>");
      
      // var target = document.getElementById('spinner');//??
      // var spinner = new Spinner(opts).spin(target);

      for(var i=0; i<tags.length; i++){
      //console.log("THIS IS TAG " + tags[i]);
      if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){}
      else {
        tag = tags[i];
        break;
        }
      }

      $.ajax({
       url: "get_tedtalks_related.php",
       data: {tag : tag},
       type: 'POST',
       dataType: "json",
         success: function(data){
          var related = changeData(data);
          related_json = related;
           recommendations(related,"spinner",false,title);
//           recommendations(data,"new_overlay",false,title);
         },
         error: function(jqXHR, textStatus, errorThrown){
         //alert("oh dear "+textStatus);
         }
      });

}

function insert_suggest_by_tag(tag) {

      // console.log("list shared by friends");
      // console.log(list_shared_by_friends);
      if (overlaycounter == null){
        overlaycounter = 0;
      }
      else{
        overlaycounter ++;
      }
      overlay_navigation.splice(overlaycounter, 0, tag);
      for (var i= overlay_navigation.length - 1; i > overlaycounter; i--){
        overlay_navigation.splice(i, 1);
      }
      html2 = [];

      html2.push("<div class='close_button'><img src='images/icons/exit.png' width='12px' onclick='javascript:hide_overlay();'/></div>");
      html2.push("<div class='navigation_buttons'><img onclick='javascript:navigation(-1);' style='display: inline; margin: 0 5px; cursor:pointer;' title='back' src='images/icons/backward.png' width='30'/><img onclick='javascript:navigation(+1);' style='display: inline; margin: 0 5px; cursor:pointer;' title='forward' src='images/icons/forward.png' width='30'/></div>");

      html2.push("<div class=\"p_title_large\" style='text-align:center;'>"+tag+"</div>");

      // $.ajax({
      //  url: recently_viewed_json.suggestions[0].manifest,
      //  dataType: "json",
      //    success: function(data){
      //      set_playable(data);
      //    },
      //    error: function(jqXHR, textStatus, errorThrown){
      //      alert("oh dear "+textStatus);
      //    }
      // });
      $('#new_overlay').html(html2.join(''));
   
      $('#new_overlay').show();  
      show_grey_bg();

      // $(".play_button").live( "click", function() {

      // console.log("PLAY PRESSED!!!");
      //   var res = {};
      //   // res["id"]=id;
      //   res["pid"]=pid;
      //   res["title"]=title;
      //   res["video"]=video;
      //   res["description"]=description;
      //   // res["explanation"]=explanation;
      //   res["img"]=id;
      //   sendProgrammeTVs(res,my_tv); 
      //   return false;

      // });

      $('#new_overlay').append("<div class=\"more_like_this_tag\" style=\"margin-top: 40px;\"></div>");
      // $('#new_overlay').append("<br clear=\"both\"/>");
      $('#new_overlay').append("<div id='spinner_tag' style=\"float: left; height:'100%';\"></div>");
      // var target = document.getElementById('spinner');//??
      // var spinner = new Spinner(opts).spin(target);

      $.ajax({
       url: "get_tedtalks_related.php",
       data: {tag : tag},
       type: 'POST',
       dataType: "json",
         success: function(data){
          var related = changeData(data);
          related_json = related;
           recommendations(related,"spinner_tag",false,title);
//           recommendations(data,"new_overlay",false,title);
         },
         error: function(jqXHR, textStatus, errorThrown){
         //alert("oh dear "+textStatus);
         }
      });
}

function navigate_by_tag(tag) {

      html2 = [];

      html2.push("<div class='close_button'><img src='images/icons/exit.png' width='12px' onclick='javascript:hide_overlay();'/></div>");
      html2.push("<div class='navigation_buttons'><img onclick='javascript:navigation(-1);' style='display: inline; margin: 0 5px; cursor:pointer;' title='back' src='images/icons/backward.png' width='30'/><img onclick='javascript:navigation(+1);' style='display: inline; margin: 0 5px; cursor:pointer;' title='forward' src='images/icons/forward.png' width='30'/></div>");

      html2.push("<div class=\"p_title_large\" style='text-align:center;'>"+tag+"</div>");

      // $.ajax({
      //  url: recently_viewed_json.suggestions[0].manifest,
      //  dataType: "json",
      //    success: function(data){
      //      set_playable(data);
      //    },
      //    error: function(jqXHR, textStatus, errorThrown){
      //      alert("oh dear "+textStatus);
      //    }
      // });
      $('#new_overlay').html(html2.join(''));
   
      $('#new_overlay').show();  
      show_grey_bg();

      // $(".play_button").live( "click", function() {

      // console.log("PLAY PRESSED!!!");
      //   var res = {};
      //   // res["id"]=id;
      //   res["pid"]=pid;
      //   res["title"]=title;
      //   res["video"]=video;
      //   res["description"]=description;
      //   // res["explanation"]=explanation;
      //   res["img"]=id;
      //   sendProgrammeTVs(res,my_tv); 
      //   return false;

      // });

      $('#new_overlay').append("<div class=\"more_like_this_tag\" style=\"margin-top: 40px;\"></div>");
      // $('#new_overlay').append("<br clear=\"both\"/>");
      $('#new_overlay').append("<div id='spinner_tag' style=\"float: left; height:'100%';\"></div>");
      // var target = document.getElementById('spinner');//??
      // var spinner = new Spinner(opts).spin(target);

      $.ajax({
       url: "get_tedtalks_related.php",
       data: {tag : tag},
       type: 'POST',
       dataType: "json",
         success: function(data){
          var related = changeData(data);
          related_json = related;
           recommendations(related,"spinner_tag",false,title);
//           recommendations(data,"new_overlay",false,title);
         },
         error: function(jqXHR, textStatus, errorThrown){
         //alert("oh dear "+textStatus);
         }
      });
}

//Function to show navigation backward and forward 

function navigation(data){
  //backwards
  if(data == -1){
    
    //not the end
    if((overlaycounter -1) > -1){
      overlaycounter = overlaycounter -1; 
      if (overlay_navigation[overlaycounter].substring) { //if tag or metadata 
        navigate_by_tag(overlay_navigation[overlaycounter]);
      // do string thing
      } 
      else{ //if programme directly
        navigate_by_id(overlay_navigation[overlaycounter]);
      // do other thing
      }
    }
  }
  //forwards ---> +1
  else{
    //not the end
    if((overlaycounter+1) <= (overlay_navigation.length -1)){
      overlaycounter = overlaycounter +1; 
      if (overlay_navigation[overlaycounter].substring) { //if tag or metadata 
        navigate_by_tag(overlay_navigation[overlaycounter]);
      // do string thing
      } 
      else{ //if programme directly
        navigate_by_id(overlay_navigation[overlaycounter]);
      // do other thing
      }
    }

  }

}


function test_for_playability(formats, provider){
console.log("formats");
console.log(formats);
  //@@tmp - for ipads etc should say no for flash
  //might want also to exclude some providers
  if(navigator.platform.indexOf("iPad") != -1 || navigator.platform.indexOf("iPhone") !=-1){
    if(formats["mp4"]){
      return true;
    }else{
      return false;
    }
  }else{
    return true;
  }
}

function set_playable(manifest_data){

    if(manifest_data && manifest_data["limo"]){
       //two kinds of manifest - one with events and one not
       // this is the events one

       if(manifest_data["limo"]["event-resources"][0]["link"]){

         $.ajax({
           url: manifest_data["limo"]["event-resources"][0]["link"],
           dataType: "json",
           success: function(data){
           process_events(data);
           },
           error: function(jqXHR, textStatus, errorThrown){
           console.log("oh dear2 "+textStatus);
           }
         });

       }

       if(manifest_data["limo"]["media-resources"][0]["link"]){

        $.ajax({
         url: manifest_data["limo"]["media-resources"][0]["link"],
         dataType: "json",
           success: function(data){
             set_playable(data);
           },
           error: function(jqXHR, textStatus, errorThrown){
             alert("oh dear "+textStatus);
           }
        });
          
       }else{
         console.log("broken manifest limo file");
       }

    }else{

      var locally_playable = false;

      if(manifest_data && manifest_data["media"]){
         var swf = manifest_data["media"]["swf"];
         var mp4 = manifest_data["media"]["mp4"];

         var provider = manifest_data["provider"];
         var formats = {"swf":swf,"mp4":mp4};
         locally_playable = test_for_playability(formats, provider);
         if(locally_playable){
           // $(".play_button").show();
           // $(".play_button").unbind('click');
           $(".play_button").live( "click", function() {
                      //get the prpgramme
                      var el = $( this ).parent().parent();
                      var programme = get_data_from_programme_html(el);
                      $("#new_overlay").html("<div class='close_button'><img src='images/icons/exit.png' width='12px' onclick='javascript:hide_overlay();'/></div><div id='player'></div>");
                      process_video(programme,formats,provider);
                      return false;

           });
         }
      }
   }
}

//print out who is in the group and what sort of thing they are

function get_roster(blink){

  var roster = blink.look();
  // console.log("THIS IS ROSTER === ");
  // console.log(roster);

   if(roster["me"]){
     $("#title").html(roster["me"].name);
     $("#small_title").html("<a href='player.html#"+my_group+"' target='_blank'>Open Virtual TV</a>");
   }
  $("#roster").empty();

  var html=[];

  if(roster){

     html.push("<h3 class=\"contrast\">SHARE WITH</h3>");

    html.push("<div class='snaptarget_group person' id='group'>");
    html.push("<img class='img_person'  src='images/icons/group.png'  />");
    html.push("<div class='friend_name' id='grp'>Group #"+my_group+"</div>");
    html.push("</div>");

    // html.push("<br clear=\"both\" />");

    for(r in roster){

      item = roster[r];
      var video;
      // console.log("printing roster[r]")
      // console.log(roster[r]);

       //i.e. not me
      if(item && item.name!=buttons.me.name){

        // if a person
        if(item.obj_type=="person"){
          html.push("<div class='snaptarget person ui-droppable' id='"+item.name+"'>");
          html.push("<img class='img_person'  src='images/icons/user.png'  />");
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
            
            html_tv.push("<div id='tv_name' style='font-size:16px;padding-top:10px;padding-right:40px;'>My TV</div>");
            html_tv.push("<div style='float: left; margin-right: 15px; margin-top: 30px;'><img class='img_tv' src='images/tiny_tv.png' /></div>");
            
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
                 $.ajax({
                    url: "get_tedtalks_by_id.php",
                    type: "POST",
                    async: false,
                    data: {id: pid},
                    dataType: "json",
                    success: function (data) {
                        video =  changeData(data); //JSON with suggestions format
                        // recently_viewed_json.suggestions.splice(0,0,item.suggestions[0]);
                    }
                    });
                    var tags = Object.keys(video.suggestions[0]["tags"]);
                    //var tag = (/[^, ]*/.exec(tags)[0]);
                    var tags = (/[^, ]*/.exec(tags)); //array of tags
                    var tag = ""; //initialiazing
                    //check for a tag without whitespace
                    for(var i=0; i<tags.length; i++){
                      //console.log("THIS IS TAG " + tags[i]);
                      if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){}
                      else {
                        tag = tags[i];
                        break;
                      }
                    }
                 insert_suggest2(pid);
               }
            })

         }
        }
      }

    }
    $('#roster').html(html.join(''));
    $(document).trigger('refresh');

}


function show_browse_programmes(){
  // $("#main_title").html("N-SCREEN");
  $sr=$("#search_results");
  $sr.css("display","none");

  $browse=$("#browse");
  $browse.removeClass("grey").addClass("blue");

  $random=$("#random");
  $random.removeClass("blue").addClass("grey");
  $container=$("#browser");
  $container.css("display","block");
  $(document).trigger('refresh');
   $(document).trigger('refresh_buttons');
}

function show_more_recommendations(){

  var content = $('#side-b');
  //if expanded-->contract
  if (content[0].style.height == '100%'){
     //jquery bug animayion pertentage
     // content.animate(content.height()*.100,400);
      content.css('height','293px');
     $('a#moreprogs').html('View All &triangledown;');
     $('#lessprogs').remove();

  }
  else{
    // content.animate({height:'100%'},400);
    content.css('height','100%');
    $('a#moreprogs').html('View Less &utri;');
    content.append("<span id='lessprogs' class='more_blue'><a onclick='show_more_recommendations();'>View Less &utri;</a></span>");

  }
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');
}

function show_shared(){

  var content = $('#content');
  //if expanded-->contract
  if (content[0].style.height == '100%'){
    content.css('height','293px');
    $('a#moreshared').html('View All &triangledown;');
    $('#lessshared').remove();
  }
  else{
    content.css('height','100%');
    $('a#moreshared').html('View Less &triangle;');
    content.append("<span id='lessshared' class='more_blue'><a onclick='show_shared();'>View Less &utri;</a></span>");
  }
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');
}

function show_history(){
  var content = $('#content2');
  //if expanded-->contract
  if (content[0].style.height == '100%'){
    content.css('height','293px');
    $('a#morerecently').html('View All &triangledown;');
    $('#lessrecently').remove();
  }
  else{
    content.css('height','100%');
    $('a#morerecently').html('View Less &utri;');
    content.append("<span id='lessrecently' class='more_blue'><a onclick='show_history();'>View Less &utri;</a></span>");
  }
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');

}
function show_later(){
  var content = $('#content3');
  //if expanded-->contract
  if (content[0].style.height == '100%'){
    content.css('height','293px');
    $('a#morelater').html('View All &triangledown;');
    $('#lesslater').remove();
  }
  else{
    content.css('height','100%');
    $('a#morelater').html('View Less &utri;');
    content.append("<span id='lesslater' class='more_blue'><a onclick='show_later();'>View Less &utri;</a></span>");
  }
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');
}
function show_likes(){
  var content = $('#content4');
  //if expanded-->contract
  if (content[0].style.height == '100%'){
    content.css('height','293px');
    $('a#morelikes').html('View All &triangledown;');
    $('#lesslikes').remove();
  }
  else{
    content.css('height','100%');
    $('a#morelikes').html('View Less &utri;');
    content.append("<span id='lesslikes' class='more_blue'><a onclick='show_likes();'>View Less &utri;</a></span>");
  }
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');

}
function show_dislikes(){
  var content = $('#content5');
  //if expanded-->contract
  if (content[0].style.height == '100%'){
    content.css('height','293px');
    $('a#moredislikes').html('View All &triangledown;');
    $('#lessdislikes').remove();
  }
  else{
    content.css('height','100%');
    $('a#moredislikes').html('View Less ' + '&utri;');
    content.append("<span id='lessdislikes' class='more_blue'><a onclick='show_dislikes();'>View Less &utri;</a></span>");
  }
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');
}

function show_related(){
  var content = $('#spinner');
  //if expanded-->contract
  if (content[0].style.height == '100%'){
    content.css('height','268px');
    $('a#more_related').html('View All &triangledown;');
    $('#lessrelated').remove();
  }
  else{
    content.css('height','100%');
    $('a#more_related').html('View Less ' + '&utri;');
    $('#new_overlay').append("<span id='lessrelated' class='more_blue'><a onclick='show_related();'>View Less &utri;</a></span>");
    // content.append("<span id='lessrelated' class='more_blue'><a onclick='show_related();'>View Less &utri;</a></span>");
  }
  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');
}
//ON CLICK LISTENER TO ADD TO WATCH LATER

$("#addtowatchlater").live( "click", function() {
  var father = $(this).parents().eq(2);
  var this_div = $(this).attr('id');
  var id= $(this).parents().eq(2).attr('id');

  $("#watchlater").html("<img id='deletewatchlater' style='width: 40px;' src=\"images/icons/on_watch_later.png\" /><span style='display: block'; class ='on_inter_span'>Watch Later</span>");

  // console.log('THE DIV ID OF THE PROGRAM IS   ' + the_program );
  $.ajax({
    url: "get_tedtalks_by_id.php",
    type: "POST",
    async: false,
    data: {id: id},
    dataType: "json",
    success: function (data) {
        item =  changeData(data); //JSON with suggestions format
        // recently_viewed_json.suggestions.splice(0,0,item.suggestions[0]);
    }
    });
    //recently_viewed_json.suggestions[0] is the current video now displayed   
    // update_channel("recently_viewed", recently_viewed_json);
    var div = $("#"+id);

    var speaker = (/(.*):.*?/.exec(item.suggestions[0].title))[1];
    var title = (/.*?:(.*)/.exec(item.suggestions[0].title))[1];
    var description = item.suggestions[0].description;
    // var tags = lalala **********************TO DO*********************
    var video = item.suggestions[0].video;
    var pid = item.suggestions[0].pid;
    var img = item.suggestions[0].image;

    var tags = Object.keys(item.suggestions[0]["tags"]);
    //var tag = (/[^, ]*/.exec(tags)[0]);
    var tags = (/[^, ]*/.exec(tags)); //array of tags
    var tag = ""; //initialiazing
    //check for a tag without whitespace
    for(var i=0; i<tags.length; i++){
      //console.log("THIS IS TAG " + tags[i]);
      if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){}
      else {
        tag = tags[i];
        break;
      }
    }
    watch_later_json.suggestions.splice(0,0,item.suggestions[0]);
    list_watch_later.push(id);
    update_channel("watch_later", watch_later_json);
    html = [];
    html.push("<div id=\""+id+"\" pid=\""+pid+"\" href=\""+video+"\" class=\"ui-widget-content button programme ui-draggable\"" + "onclick=\"javascript:insert_suggest2("+pid+");return true\">");
    html.push("<img class=\"img img_small\" src=\""+img+"\" />");
    html.push("<span class=\"p_title p_title_small\"><a>"+title+"</a></span>");
    html.push("<p class=\"description large\">"+description+"</p>");
    html.push("</div>");
    $('#list_later').prepend(html.join(''));

  // insert_watchlater_from_div(the_program);
  // console.log("clicked watch later");

  $(document).trigger('refresh');
  $(document).trigger('refresh_buttons');
  return false;
});

//ON CLICK LISTENER TO ADD TO LIKES

$("#addtolike").live( "click", function() {
  var father = $(this).parents().eq(2);
  var this_div = $(this).attr('id');
  var id= $(this).parents().eq(2).attr('id');

  $("#like").html("<img id='deletelike' style='width: 40px;' src=\"images/icons/on_like.png\" /><span style='display: block'; class ='on_inter_span'>Like</span>");

  // console.log('THE DIV ID OF THE PROGRAM IS   ' + the_program );
  $.ajax({
    url: "get_tedtalks_by_id.php",
    type: "POST",
    async: false,
    data: {id: id},
    dataType: "json",
    success: function (data) {
        item =  changeData(data); //JSON with suggestions format
        // recently_viewed_json.suggestions.splice(0,0,item.suggestions[0]);
    }
    });
    //recently_viewed_json.suggestions[0] is the current video now displayed   
    // update_channel("recently_viewed", recently_viewed_json);
    var div = $("#"+id);

    var speaker = (/(.*):.*?/.exec(item.suggestions[0].title))[1];
    var title = (/.*?:(.*)/.exec(item.suggestions[0].title))[1];
    var description = item.suggestions[0].description;
    // var tags = lalala **********************TO DO*********************
    var video = item.suggestions[0].video;
    var pid = item.suggestions[0].pid;
    var img = item.suggestions[0].image;

    var tags = Object.keys(item.suggestions[0]["tags"]);
    //var tag = (/[^, ]*/.exec(tags)[0]);
    var tags = (/[^, ]*/.exec(tags)); //array of tags
    var tag = ""; //initialiazing
    //check for a tag without whitespace
    for(var i=0; i<tags.length; i++){
      //console.log("THIS IS TAG " + tags[i]);
      if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){}
      else {
        tag = tags[i];
        break;
      }
    }
    likes_json.suggestions.splice(0,0,item.suggestions[0]);
    list_likes.push(id);
    update_channel("like_dislike", likes_json);
    html = [];
    html.push("<div id=\""+id+"\" pid=\""+pid+"\" href=\""+video+"\" class=\"ui-widget-content button programme ui-draggable\"" + "onclick=\"javascript:insert_suggest2("+pid+");return true\">");
    html.push("<img class=\"img img_small\" src=\""+img+"\" />");
    html.push("<span class=\"p_title p_title_small\"><a>"+title+"</a></span>");
    html.push("<p class=\"description large\">"+description+"</p>");
    html.push("</div>");
    $('#list_likes').prepend(html.join(''));

  // insert_watchlater_from_div(the_program);
  // console.log("clicked watch later");

  // $(document).trigger('refresh');
  // $(document).trigger('refresh_buttons');
  return false;
});

//ON CLICK LISTENER TO ADD TO DISLIKES
$("#addtodislike").live( "click", function() {
  var father = $(this).parents().eq(2);
  var this_div = $(this).attr('id');
  var id= $(this).parents().eq(2).attr('id');

  $("#dislike").html("<img id='deletedislike' style='width: 40px;' src=\"images/icons/on_dislike.png\" /><span style='display: block'; class ='on_inter_span'>Dislike</span>");

  // console.log('THE DIV ID OF THE PROGRAM IS   ' + the_program );
  $.ajax({
    url: "get_tedtalks_by_id.php",
    type: "POST",
    async: false,
    data: {id: id},
    dataType: "json",
    success: function (data) {
        item =  changeData(data); //JSON with suggestions format
        // recently_viewed_json.suggestions.splice(0,0,item.suggestions[0]);
    }
    });
    //recently_viewed_json.suggestions[0] is the current video now displayed   
    // update_channel("recently_viewed", recently_viewed_json);
    var div = $("#"+id);

    var speaker = (/(.*):.*?/.exec(item.suggestions[0].title))[1];
    var title = (/.*?:(.*)/.exec(item.suggestions[0].title))[1];
    var description = item.suggestions[0].description;
    // var tags = lalala **********************TO DO*********************
    var video = item.suggestions[0].video;
    var pid = item.suggestions[0].pid;
    var img = item.suggestions[0].image;

    var tags = Object.keys(item.suggestions[0]["tags"]);
    //var tag = (/[^, ]*/.exec(tags)[0]);
    var tags = (/[^, ]*/.exec(tags)); //array of tags
    var tag = ""; //initialiazing
    //check for a tag without whitespace
    for(var i=0; i<tags.length; i++){
      //console.log("THIS IS TAG " + tags[i]);
      if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){}
      else {
        tag = tags[i];
        break;
      }
    }
    dislikes_json.suggestions.splice(0,0,item.suggestions[0]);
    list_dislikes.push(id);
    update_channel("like_dislike", dislikes_json);
    html = [];
    html.push("<div id=\""+id+"\" pid=\""+pid+"\" href=\""+video+"\" class=\"ui-widget-content button programme ui-draggable\"" + "onclick=\"javascript:insert_suggest2("+pid+");return true\">");
    html.push("<img class=\"img img_small\" src=\""+img+"\" />");
    html.push("<span class=\"p_title p_title_small\"><a>"+title+"</a></span>");
    html.push("<p class=\"description large\">"+description+"</p>");
    html.push("</div>");
    $('#list_dislikes').prepend(html.join(''));

  // insert_watchlater_from_div(the_program);
  // console.log("clicked watch later");

  // $(document).trigger('refresh');
  // $(document).trigger('refresh_buttons');
  return false;
});


//ON CLICK LISTENER TO DELETE FROM WATCH LATER IST

$("#deletewatchlater").live( "click", function() {
  var father = $(this).parents().eq(2);
  var this_div = $(this).attr('id');
  var id= $(this).parents().eq(2).attr('id');

  //remove from json
  for (var i = 0; i < watch_later_json.suggestions.length; i++) {
    if (watch_later_json.suggestions[i].pid == id) {
        watch_later_json.suggestions.splice(i, 1);
        break;
    }
  }
  //remove from list
  for (var i = 0; i < list_watch_later.length; i++) {
    if (list_watch_later[i] == id) {
        list_watch_later.splice(i, 1);
        break;
    }
  }

  $("#watchlater").html("<img id='addtowatchlater' style='width: 40px;' src=\"images/icons/watch_later.png\" /><span style='display: block'; class ='inter_span'>Watch Later</span>");
  $('#list_later').children('#'+ id).remove();
  update_channel("watch_later", watch_later_json);

  // insert_watchlater_from_div(the_program);
  // console.log("clicked watch later");

  // $(document).trigger('refresh');
  // $(document).trigger('refresh_buttons');
  return false;
});

//ON CLICK LISTENER TO DELETE FROM LIKES LIST

$("#deletelike").live( "click", function() {
  var father = $(this).parents().eq(2);
  var this_div = $(this).attr('id');
  var id= $(this).parents().eq(2).attr('id');

  //remove from json
  for (var i = 0; i < likes_json.suggestions.length; i++) {
    if (likes_json.suggestions[i].pid == id) {
        likes_json.suggestions.splice(i, 1);
        break;
    }
  }
  //remove from list
  for (var i = 0; i < list_likes.length; i++) {
    if (list_likes[i] == id) {
        list_likes.splice(i, 1);
        break;
    }
  }

  $("#like").html("<img id='addtolike' style='width: 40px;' src=\"images/icons/like.png\" /><span style='display: block'; class ='inter_span'>Like</span>");
  $('#list_likes').children('#'+ id).remove();
  update_channel("like_dislike", likes_json);
  return false;
});

//FUnction to sheck whether a programme in on a personal list or not
function not_in_list(pid, list){
  var not_in_the_list = true;
  for (var i = 0; i < list.length; i++){
    if(list[i] == pid){
      not_in_the_list = false;
    }
  }
  return not_in_the_list; //returns true if element not in the list
}

//ON CLICK LISTENER TO DELETE FROM LIKES LIST

$("#deletedislike").live( "click", function() {
  var father = $(this).parents().eq(2);
  var this_div = $(this).attr('id');
  var id= $(this).parents().eq(2).attr('id');

  //remove from json
  for (var i = 0; i < dislikes_json.suggestions.length; i++) {
    if (dislikes_json.suggestions[i].pid == id) {
        dislikes_json.suggestions.splice(i, 1);
        break;
    }
  }
  //remove from list
  for (var i = 0; i < list_dislikes.length; i++) {
    if (list_dislikes[i] == id) {
        list_dislikes.splice(i, 1);
        break;
    }
  }

  $("#dislike").html("<img id='addtodislike' style='width: 40px;' src=\"images/icons/dislike.png\" /><span style='display: block'; class ='inter_span'>Dislike</span>");
  $('#list_dislikes').children('#'+ id).remove();
  update_channel("like_dislike", dislikes_json);
  return false;
});

//FUnction to sheck whether a programme in on a personal list or not
function not_in_list(pid, list){
  var not_in_the_list = true;
  for (var i = 0; i < list.length; i++){
    if(list[i] == pid){
      not_in_the_list = false;
    }
  }
  return not_in_the_list; //returns true if element not in the list
}


//FUnctin to update internal list of programmes within each section and to add properly html
//in order to prevent duplicate items
function update_list(pid, list){
  var not_in_the_list = true;
  for (var i = 0; i < list.length; i++){
    if(list[i] == pid){
      not_in_the_list = false;
    }
  }
  if(not_in_the_list){
    list.push(pid);
  }
  return not_in_the_list; //returns true if element not in the list
}

// // list of movies ---- > HAVE TO CHANGE IT TO MAKE IT BETTER

// function insert_watchlater_from_div(id){
//   var div = $("#"+id);
//   var j = get_data_from_programme_html(div);
//   var prog_id = j["pid"];
//   // console.log(j);
//   var not_in_the_list = true;

//   //checking wheter is already in the list or not
//   for (var i = 0; i < list_watch_later.length; i++){
//     if(list_watch_later[i] == prog_id) not_in_the_list = false;
//   }
//   if(not_in_the_list){ 
//     list_watch_later.push(prog_id);
//     insert_watchlater(j);
//     watch_later_json.suggestions.push(j);

//     jsObject_json = JSON.stringify(watch_later_json);

//     $.ajax({
//         url: "set_channel.php",
//         type: "POST",
//         data: {data : jsObject_json, channel : "watch_later"},
//         dataType: "json",
//         success: function (response) {
//             console.log("Correct watch_later updated");
//         }
//       });
//   }
// }

// // Call as
// //setUsername(3, "Thomas");

// function insert_watchlater(j){
//   var id = j["pid"];
//   // console.log("passing to addlater");
//   // console.log(j);
//   // console.log("passing to addlater");
//   var html3 = generate_html_for_programme(j,null,id);
//   $('#list_later').append(html3.join(''));
// }


//get a random selection

function do_random(el){

  $('#search_results').html(''); //clear previous display

  // $("#main_title").html("Random Selection");  
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
    // async: false,
    success: function(data){
      var result = changeData(data);
      random_json = result; //set global variable in order to store
      // console.log(JSON.stringify(result));
      random(result,el);
    },
    error: function(jqXHR, textStatus, errorThrown){
    // console.log("nok "+textStatus);
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
      type: "POST",
      url: "get_channel.php",
      //async: false,
      data: {channel: "recommendations"},
      dataType: "json",
      success: function(data){
        random(data,el);
      },
      error: function(jqXHR, textStatus, errorThrown){
        console.log("!!nokkkk "+textStatus);
      }
    });

  }else{
     //do_random(el);
  }

}

//search for txt
       
function do_search(txt){

  txt = txt.toLowerCase();
  // $('#main_title').html("Search for '"+txt+"'");

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



//process the results for displaying (small display) welcome page

function process_json_results(result,ele,pid_title,replace_content,add_stream,stream_title){
          var max = 100;
          var s ="";
          var html = [];
          suggestions = result;
          // console.log(result);

          if (suggestions && suggestions.length>0){
            //console.log("---------WE ARE NOW IN DIV " +  ele + "----------and json size is " + suggestions.length);
            var count = 0;
            var num = suggestions.length/2;
            for (var r in suggestions){
              if(count<max){
                count = count + 1;
                var title = suggestions[r]["core_title"];//@@
                if(!title){
                  title = suggestions[r]["title"];
                }
                var shared = suggestions[r]["shared"];
                var tags = Object.keys(suggestions[r]["tags"]);
                //var tag = (/[^, ]*/.exec(tags)[0]);
                var tags = (/[^, ]*/.exec(tags)); //array of tags
                var tag = ""; //initialiazing
                //check for a tag without whitespace
                for(var i=0; i<tags.length; i++){
                  //console.log("THIS IS TAG " + tags[i]);
                  if(tags[i].indexOf(' ') >= 0 || /^[A-Z]/.test(tags[i])){}
                  else {
                    tag = tags[i];
                    break;
                  }
                }
                // console.log("THIS IS TAG!!!!!!! " + tag);
                var desc="";
                var desc = suggestions[r]["description"];
//                desc = desc.replace(/\"/g,"'");
                var id = suggestions[r]["pid"];
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

                var program_id = id.toString();

                var string = "<div id=\""+id+"\" pid=\""+id+"\" class=\"ui-widget-content button programme ui-draggable\" " ;
                string += " onclick= \"javascript:insert_suggest2(";
                string += program_id+");return true\">";
                //console.log(string);

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
                    
                     // console.log(string);
                     html.push(string);
                  }else{
                     html.push(string);
                  }
                  html.push("<div><img class=\"img img_small\" src=\""+img+"\" /></div>");
                  //html.push("<span class=\"p_title p_title_small\"><a href=''>"+title+"</a></span>");
                  html.push("<span class=\"p_title p_title_small\"><a >"+title+"</a></span>");
                  if(shared){     
                    list_shared_by_friends.push(id);              
                    html.push("<span class=\"shared_by\">Shared by "+shared+"</span>");
                  }
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

  if(data.talks == null){
    return random_ted;
  }  

  for(var i = 0; i < data.talks.length; i++) {  var item = data.talks[i];
      for(var j = 0; j < data.talks[i].talk.photo_urls.length; j++){
        if(data.talks[i].talk.photo_urls[j].size == "240x180"){
          var image = data.talks[i].talk.photo_urls[j].url;
        }
      } 

      if(item.talk.media_profile_uris["internal"]){

      random_ted.suggestions.push({ 
          "pid"   : item.talk.id,
          "title" : item.talk.name,          
          "description" : item.talk.description,
          "date_time" : item.talk.published_at,
          // "media_profile_uris" : item.talk.media_profile_uris,
          "url" : item.talk.media_profile_uris["internal"]["950k"].uri, //TODO CHANGE THIS
          "video" : item.talk.media_profile_uris["internal"]["950k"].uri,
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
                "mp4": {
                  // "type": "video/x-swf",
                  "uri": item.talk.media_profile_uris["internal"]["950k"].uri,
                  "is_live": "false"
                }
              },
              "type": "video/mp4"
          },
          "tags" : item.talk.tags
      });

      }
      else{

        random_ted.suggestions.push({ 
          "pid"   : item.talk.id,
          "title" : item.talk.name,          
          "description" : item.talk.description,
          "date_time" : item.talk.published_at,
          // "media_profile_uris" : item.talk.media_profile_uris,
          "url" : "", //TODO CHANGE THIS
          "video" : "",
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
                "mp4": {
                  // "type": "video/x-swf",
                  "uri": "",
                  "is_live": "false"
                }
              },
              "type": "video/mp4"
          },
          "tags" : item.talk.tags
      });

      }
      
  }return random_ted; 
}

//when the group changes, update the roster

$(document).bind('items_changed',function(ev,blink){
    get_roster(blink);
     $(document).trigger('refresh');
     // $(document).trigger('refresh_buttons');
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
  var i = j["pid"];
  // var i = j["pid"]+"_"+n; //not really unique enough
  return i;
}

//when someone shares something, put a copy of it in the right place

$(document).bind('shared_changed', function (e,programme,name,msg_type) {
  var a = get_object("a2");
  a.play();

  var id = generate_new_id(programme,name);
  // var id = programme.pid;
  // console.log("THIS IS GENERATED ID");
  // console.log(id);

  console.log("THE ID OF THE PROGRAM SHARED IS " + id);
  var msg_text = "";
  var html = "";

  $.ajax({
    url: "get_tedtalks_by_id.php",
    type: "POST",
    async: false,
    data: {id: id},
    dataType: "json",
    success: function (data) {
      item =  changeData(data);
      item.suggestions[0].shared = name;
      //item.suggestions[item.suggestions.length].push = "shared" : name;
      shared_by_friends_json.suggestions.splice(0,0,item.suggestions[0]);
      update_channel("shared_by_friends", shared_by_friends_json);
      recommendations(shared_by_friends_json,"results");        
    }
  });

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
      //html = generate_html_for_programme(programme,name,id);
      msg_text = name+" shared "+programme["title"]+" with you";
      if(msg_type=="groupchat"){
        msg_text = name+" shared "+programme["title"]+" with the group";
      }
    }
  }

  //$('#results').prepend(html.join(''));

//notifications 
  build_notification(msg_text,programme,name);
  $(document).trigger('refresh');
   $(document).trigger('refresh_buttons');

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

//ensure the drag and drop is working

$(document).bind('refresh', function () {
                $( "#draggable" ).draggable();
                $( ".programme" ).draggable(
                        {
                        appendTo: 'body',
                        containment:"#container",
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

                });
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

                });
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

                });


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

                })

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
                                
                });

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
  overlaycounter = null;
  overlay_navigation = [];
$("#new_overlay").children().filter("video").each(function(){
    this.pause();
    this.remove();
});
$("#new_overlay").empty();
 // $("#new_overlay").get(0).pause();
 // $("#new_overlay")[0].pause();
 $("#bg").hide();
 $("#myvid").html("");
 $("#new_overlay").hide();

          
}

  // Logout Function
  function Logout() {
    $.ajax({
       url: 'logout.php',
       async : false,
         success: function(){
           window.location.href= "http://localhost/N-Screen/";
         }
      });
    //console.log("RESPUESTA  "+ lalala):
    //FB.logout(function () { document.location.reload(); });
  }

</script>

  <div id="header">
    <span id='main_title'><a href="javascript:show_browse_programmes()" style='color: #FFFFFF;'>N-SCREEN</a></span>
    <span id='small_title'></span>

    <!-- <span id="logoutspan" href="#" onclick="Logout();">LOGOUT</a> -->

    <!-- NOT WORKING BY NOW -->

<!--         <span class="form" >
      <form >
        <input type="text" id="search_text" name="search_text" value="search programmes" />
      </form> -->
    </span>

<!--     <span class="form" >
      <form onsubmit='javascript:do_search(this.search_text.value);return false;'>
        <input type="text" id="search_text" name="search_text" value="search programmes" onclick="javascript:remove_search_text();return false;"/>
      </form>
    </span> -->
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
        <span class="more_blue"><a id="moreprogs" onclick='show_more_recommendations();'>View All &triangledown;</a></span>
        <div id="progs"> </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
     

      <div id="content" class="slidey">
        <span class="sub_title">SHARED BY FRIENDS</span>
        <span class="more_blue"><a id='moreshared' onclick='show_shared();'>View All &triangledown;</a></span>
        <div id="results">
         <div class='dotted_box'> </div>
        </div>
         <div class="clear"></div>
      </div>
       <div class="clear"></div>
      <!-- <br clear="all" /> -->

      <div id="content2" class="slidey">
        <span class="sub_title">RECENTLY VIEWED</span>
        <span  class="more_blue"><a id="morerecently" onclick='show_history();'>View All &triangledown;</a></span>
        <div id="history">
          <div class='dotted_box'> </div>
        </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>

      
      <div id="content3" class="slidey">
        <span class="sub_title">WATCH LATER</span>
        <span  class="more_blue"><a id="morelater" onclick='show_later();'>View All &triangledown;</a></span>
        <div id="list_later">
          <div class='dotted_box'> </div>
        </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>

      <div id="content4" class="slidey">
        <span class="sub_title">LIKES</span>
        <span  class="more_blue"><a id="morelikes" onclick='show_likes();'>View All &triangledown;</a></span>
        <div id="list_likes">
          <div class='dotted_box'> </div>
        </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>

      <div id="content5" class="slidey">
        <span class="sub_title">DISLIKES</span>
        <span  class="more_blue"><a id="moredislikes" onclick='show_dislikes();'>View All &triangledown;</a></span>
        <div id="list_dislikes">
          <div class='dotted_box'> </div>
        </div>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
 
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

   <div id="browse" class="blue menu"><a href="javascript:show_browse_programmes()">HOME</a></div>
   <div id="random" class="grey menu"><a href="javascript:do_random()">RANDOM SELECTION</a></div>
  </div>
  <div id="logoutspan" onclick="Logout();" href="#"></div>
</div>



<p style="display: none;"><small>Status:
<span id="demo">
<span id="out"></span>
</span></small></p>

<!-- overlays -->
<div id='new_overlay' style='display:none;'>

  <div class='close_button'>
    <img width="12px" onclick="javascript:hide_overlay();" src="images/icons/exit.png"/>
  </div>
</div>
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



