jQuery(document).ready(function($) {

$('#listForm').submit(function() {

alert("yo");
  var sitename = $("#sitename").val();
  var listname = $("#listname").val();
  var tease = $("#tease").val();
  var listurl = $("#listurl").val();
  var childsubject = $("select#gnmessage").val();
  var parentsubject = $("select#parentSubject").val();
  var profile_id=0;
  if(childsubject!=0) {
  profile_id=childsubject;
  } else {
  profile_id=parentsubject;
  }
  var dataString = "sitename="+sitename+"&listname="+listname+"&tease="+tease+"&listurl="+listurl+"&profile_id="+profile_id;
 
  addListicle(dataString);
  return false;
});


});

