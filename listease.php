<?php



// Hook for ajax receiver for listicle status

add_action('wp_ajax_listicle_tease_status', 'listicle_tease_status_callback');

function listicle_tease_status_callback() {
	global $wpdb; // this is how you get access to the database
	
	
	
	
$querystr = "
    SELECT $wpdb->posts.* , $wpdb->postmeta.*
    FROM $wpdb->posts, $wpdb->postmeta
    WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
    AND $wpdb->postmeta.meta_key = 'listicle_name' 
    AND $wpdb->postmeta.meta_value = '" . $_POST['listname'] . "' 
    AND $wpdb->posts.post_type = 'listicle'
    ORDER BY $wpdb->posts.post_date DESC
 ";
	 
	 
 $pageposts = $wpdb->get_results($querystr, OBJECT);
 
 if (!empty($pageposts)) { // If the query returned something

 foreach ($pageposts as $post) {
 
//echo "update " .  $post->ID . " for list name" .  $_POST['listname'] ;
	  add_post_meta( $post->ID, 'listicle_tease',  1, true ) || update_post_meta( $post->ID, 'listicle_tease',  1 );
	  
	  echo "1";
 
 
 
		} //for loop
	} //if empty
	else {
	echo "0";
	}
	

	die(); // this is required to return a proper result
}


// Hook for adding admin menus
add_action('admin_menu', 'listicle_add_pages');

function listicle_get_the_excerpt($post_id) {
  global $post;  
  $save_post = $post;
  $post = get_post($post_id);
  $output = get_the_excerpt();
  $post = $save_post;
  return $output;
}


function listicle_escape_s($str) {
	$cleaned_string = trim(str_replace('\n', '', $str));
	return strtr($cleaned_string, array (
		"'"  => "\'"
	));
}

function listicle_clean_string($str) {
// Strip HTML Tags
$clear = strip_tags($str);
// Clean up things like &amp;
$clear = html_entity_decode($clear);
// Strip out any url-encoded stuff
$clear = urldecode($clear);
// Replace non-AlNum characters with space
$clear = preg_replace('/[^A-Za-z0-9]/', ' ', $clear);
// Replace Multiple spaces with single space
$clear = preg_replace('/ +/', ' ', $clear);
// Trim the string of leading/trailing space
$clear = trim($clear);
return $clear;
}


function listicle_truncate($string,$char_width) {
if (strlen($string) > $char_width) 
{
    $string = wordwrap($string, $char_width);
    $string = substr($string, 0, strpos($string, "\n"));
}
return $string;
}


function getListicles($teased=0) {

//echo "listicle post get";
$postID = 0;
global $wpdb;
$char_width=250;
					
//global $post;


 $querystr = "
   SELECT wposts.* , wpostmeta.meta_value, wpostmeta.meta_key
    FROM $wpdb->posts wposts
    LEFT JOIN $wpdb->postmeta wpostmeta ON wposts.ID = wpostmeta.post_id
    LEFT JOIN $wpdb->postmeta AS listname ON(
	wposts.ID = listname.post_id
	AND listname.meta_key = 'listicle_name'
	)
    WHERE  wpostmeta.meta_key = 'listicle_name' 
    AND wposts.post_status = 'publish' 
    AND wposts.post_type = 'listicle'
    AND wposts.post_date < NOW()
	GROUP BY listname.meta_value
    ORDER BY listname.meta_value ASC
	 ";
	 
	 
//echo $querystr;
 $pageposts = $wpdb->get_results($querystr, OBJECT);
$unteasedListsArr = array();
 

 if (!empty($pageposts)) { // If the query returned something

$distinctPerma = "";
 foreach ($pageposts as $post) {  // Loop though our results!
					 $postID = $post->ID;
					 $meta_values = get_post_meta($postID);
					 
					  //add_post_meta( $postID, 'listicle_tease',  0, true ) || update_post_meta( $postID, 'listicle_tease',  0  );
					 $listicle_tease = $meta_values["listicle_tease"][0] ;
					 $listicle_excerpt = $meta_values["listicle_excerpt"][0] ;
					 $listicle_parent = $meta_values["listicle_parentID"][0] ;
					 $listicle_name = $meta_values["listicle_name"][0] ;
					 $parentPerm = get_permalink($listicle_parent);
					 
					 
					 //setup_postdata($post); 
					 
					 $parentExcerpt = listicle_get_the_excerpt($listicle_parent);
					 
					 $parentTitle = get_the_title($listicle_parent);
					 
					 
					 //echo "tease is: " .$listicle_tease . "<br/>";
					 if(($listicle_tease==$teased) && ($distinctPerma !=$parentPerm) && ($parentTitle!="") ) {
					 $unteasedListtsArr[$postID]["name"] = $listicle_name; 
					 $unteasedListtsArr[$postID]["parentTitle"] = $parentTitle; 
					 $unteasedListtsArr[$postID]["parentURL"] = $parentPerm; 
					 
					 if($listicle_excerpt=="") {
					 $listicle_excerpt = $parentExcerpt; 
					 }
					 
					 
					 $listicle_excerpt = listicle_clean_string($listicle_excerpt);
					 $unteasedListtsArr[$postID]["excerpt"] = listicle_truncate($listicle_excerpt,$char_width);
					 
					 $distinctPerma = $parentPerm;
					 }
					 
					 }
				}
 
return $unteasedListtsArr;

}


// action function for above hook
function listicle_add_pages() {
    // Add a new submenu under Settings:
//    add_options_page(__('Test Settings','list-tease'), __('Test Settings','list-tease'), 'manage_options', 'testsettings', 'mt_settings_page');
	
	  add_management_page(  __('Listicle Tease','list-tease'), __('Listicle Tease','list-tease'), 'manage_options', 'listease', 'listicle_tools_page'); 
	}
	
	
	

// mt_settings_page() displays the page content for the Test settings submenu
function listicle_tools_page() {

    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    // variables for the field and option names 
    $hidden_field_name = 'postform';
	
	 if(!get_option('listicle_options'))
  {
    add_option('listicle_options', serialize(array('autotease'=>'0', 'defaultSubject'=>'0')));
  }
    $listicle_options = unserialize(get_option('listicle_options'));

    
    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        
		
  
  //var_dump(get_option('listicle_options'));
  $listicle_options = $listicle_newoptions = maybe_unserialize(get_option('listicle_options'));
  
  // Check if new widget options have been posted from the form below - 
  // if they have, we'll update the option values.
  if ($_POST['autotease']){
    $listicle_newoptions['autotease'] = $_POST['autotease'];
  }
  if ($_POST['defaultSubject']){
    $listicle_newoptions['defaultSubject'] = $_POST['defaultSubject'];
  }
  
  if($listicle_options != $listicle_newoptions){
    $listicle_options = $listicle_newoptions;
    update_option('listicle_options', serialize($listicle_options));
  }

        // Put an settings updated message on the screen

?>
<div class="updated"><p><strong><?php _e('settings saved.', 'list-tease' ); ?></strong></p></div>
<?php

    }
	
	
wp_enqueue_script('jquery');
    // Now display the settings editing screen

    echo '<div class="wrap">';

    // header

    echo "<h2>" . __( 'List Tease', 'list-tease' ) . "</h2>";

    // settings form
     $blog_title = get_bloginfo('name');
	 
	$blog_tagline = get_bloginfo ( 'description' ); 
    ?>

	<script type="text/javascript" src="http://listicle.us/js/subject.js"></script>
	
	<script type="text/javascript">
						jQuery(function($) {
							
						});
						
						
						jQuery(document).ready(function($) {
						var siteVal = "<?php echo $blog_title?>";
						jQuery("#siteNameControl").hide();
						
						if(siteVal=="") {
						jQuery("#siteNameControl").show();
						}
						jQuery("#siteDescripControl").hide();
						jQuery("#childSubjectLabel").hide();
jQuery('#listForm').submit(function() {
  var sitename = $("#sitename").val();
  var listid = $("#listname").val();
  var tease = $(document).data("excerpt");
  var listurl = $(document).data("parentURL");
  var postname = $(document).data("parentTitle");
  var listname = $(document).data("listname");
//  var isValid = validateForm();
  if(listid=="") {
  $("#addRspMsg").html("select a list");
  return false;
  
  }
  
  var childsubject = $("select#Subjectchild").val();
  var parentsubject = $("select#parentSubject").val();
  
  
  
  if(parentsubject == 0) {
  $("#addRspMsg").html("select a category");
  return false;
  }
  
  
  var profile_id=0;
  if(childsubject!=0) {
  profile_id=childsubject;
  } else {
  profile_id=parentsubject;
  }
  
  var dataString = "sitename="+sitename+"&listname="+postname+"&tease="+tease+"&listurl="+listurl+"&profile_id="+profile_id;
  addListicle(dataString,listname);
  $("#listname option:selected").remove();
  return false;
});

});



function validateForm() {


}

function addListicle(ds,listname) {
jQuery.ajax({
    	type: "GET",
    	dataType:'jsonp',
		jsonp: 'callback',
		url: jQuery(document).data('baseURL') + "/app/json_add_list.php",
		data: ds,
    	success: function(rsp) {
			jQuery('#addRspMsg').html(rsp.htmlRSP);
			
				var data = {
		action: 'listicle_tease_status',
		whatever: 1234,
		listname: listname
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			//alert('Got this from the server: ' + response);
			
		});

			
		}, 
		error: function (xhr, status) {}
	});

	return false;

}



					</script>
						<h5>Tease Your Listicle at Listicle.us</h5>
						
						
						
	<div id="addRspMsg"></div>
	
	<form name="listForm" id="listForm" action="#"  method="post" class="well">
			
			<div id = "siteNameControl" class="controls">
			<label class="control-label">Site Name:</label>
				<input type="text" name="sitename" id="sitename" value="<?php echo $blog_title?>" placeholder="e.g. My Cool Site">
			</div><!-- .controls -->
		
			<div id = "siteDescripControl" class="controls">
			<label class="control-label">Site Description:</label>
				<input type="text" name="sitetag" id="sitetag" value="<?php echo $blog_tagline?>" placeholder="e.g. The Site at the End of the Internet">
			</div><!-- .controls -->
		
		<div class="controls">
		
		
		<?php 
		$listiclesArr = getListicles();
		//var_dump($listiclesArr);
		//$json = json_encode ( $listiclesArr );

		// 
		
		?>
		 <script type="text/javascript">
		   //<![CDATA[
		   var listicles = new Array();
		   			
		jQuery(function($) {
		var sel = jQuery("#listname");
			
		   $('#listname').change(function() {
				listid = $('#listname').val();
				
				outSelectedListInfo(listid);
			});
		   <?php foreach ($listiclesArr as $listid => $listitem) { 
		   
		   
		   if($listitem["parentTitle"]!="") {
		   ?>
		   listid = <?php echo $listid ?>;
		   listicles[listid] = new Array();
			listicles[listid]["name"] = "<?php echo $listitem["name"]?>";
			listicles[listid]["excerpt"] = '<?php echo listicle_escape_s($listitem["excerpt"])?>';
			listicles[listid]["parentTitle"] = '<?php echo listicle_escape_s($listitem["parentTitle"])?>';
			listicles[listid]["parentURL"] = "<?php echo $listitem["parentURL"]?>";
			sel.append($("<option>").attr('value',listid).text(listicles[listid]["parentTitle"]));
			
		<?php 
			} //if 
		} //for ?>
		
			function outSelectedListInfo(listid) {
			
			$("#listInfo").html("<div id='liName'><strong>List Name: </strong>"  +listicles[listid]["name"] + "</div>");
			$("#liName").append("<div id='liExcerpt'><strong>List Excerpt</strong>: "  +listicles[listid]["excerpt"] + "</div>");
			$("#liExcerpt").append("<div id='liParent'><strong>In Post:</strong> "  +listicles[listid]["parentTitle"] + "</div>");
			$("#liParent").append("<div id='liPURL'><strong>Permalink:</strong> "  +listicles[listid]["parentURL"] + "</div>");
			
			$(document).data("excerpt",listicles[listid]["excerpt"]);
			
			$(document).data("listname",listicles[listid]["name"]);
			
			$(document).data("parentTitle",listicles[listid]["parentTitle"]);
			
			$(document).data("parentURL",listicles[listid]["parentURL"]);
			}
			
				});
					
		  // var json_obj = jQuery.parseJSON ( ' + <?php echo $json; ?> + ' );
		//	console.log(json_obj);
		   //]]>
		 </script>
			<label class="control-label"><strong>List Name:</strong></label>
				<select name="listname" id="listname">
				<option value="">-----select--------</option>
				
				</select>
				
				
				<div id="listInfo"></div>
		</div><!-- .controls -->	
		
		
			
		<fieldset>		
		<div class="controls">
			<label class="control-label"><strong>Select the most appropriate category</strong></label>
			
			<select id="parentSubject" name="parentSubject" onChange='selectSubjectParent("Subject")'><option value="0"></option></select>



		</div> <!-- .controls -->
		
			<div class="controls" id="childSubjectLabel">
			<label class="control-label">Subcategory <em>(Optional)</em></label>
			
			
			<div id="childSubjectContainer">
				<select id="childSubject" name="childSubject">
					<option value="0"></option>
				</select>
			</div>
		
		</div> <!-- .controls -->
		</fieldset>
		
		
		<div class="button-area">
			<input type="submit" value="List it!"/ >
		</div>
		<div id="addheadmsg"></div> <!-- #addheadmsg -->
		<p class="instructions">spam will be canned</p>
		
		



		</form>





<?php
 
 
 
} //mt_tools_page
?>