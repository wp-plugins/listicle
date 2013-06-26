<?php 
/*
Plugin Name: listicle
Plugin URI: http://listicle.us/about/?page_id=5
Description: listicle generator
Version: 0.3
Author: alxgrlk
Author URI: http://listicle.us
License: GPL2
*/
// Create out post type




add_action( 'init', 'create_post_type' );
function create_post_type() {
	$args = array(
        'labels' => post_type_labels( 'Listicle' ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => false, 
        'show_in_menu' => false, 
        'query_var' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'has_archive' => true, 
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title',
            'editor',
            'author',
            'thumbnail',
            'excerpt',
            'comments'
        )
    ); 

	register_post_type( 'listicle', $args );
}



function listicle_activate() {
	// register taxonomies/post types here
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'listicle_activate' );

function listicle_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'listicle_deactivate' );


// A helper function for generating the labels
function post_type_labels( $singular, $plural = '' )
{
    if( $plural == '') $plural = $singular .'s';
    
    return array(
        'name' => _x( $plural, 'post type general name' ),
        'singular_name' => _x( $singular, 'post type singular name' ),
        'add_new' => __( 'Add New' ),
        'add_new_item' => __( 'Add New '. $singular ),
        'edit_item' => __( 'Edit '. $singular ),
        'new_item' => __( 'New '. $singular ),
        'view_item' => __( 'View '. $singular ),
        'search_items' => __( 'Search '. $plural ),
        'not_found' =>  __( 'No '. $plural .' found' ),
        'not_found_in_trash' => __( 'No '. $plural .' found in Trash' ), 
        'parent_item_colon' => ''
    );
}


add_action( 'admin_init', 'listicle_admin' );
add_action( 'save_post', 'add_listicle', 10, 2 );

function listicle_admin() {
    add_meta_box( 'listicle_name_meta_box',
        'listicle Name',
        'display_listicle_name_meta_box',
        'listicle', 'normal', 'high'
    );
/*
add_meta_box( 'listicle_parent_meta_box',
        'listicle parent',
        'display_listicle_parent_meta_box',
        'listicle', 'normal', 'high'
    );
*/
}


function display_listicle_name_meta_box( $listicle ) {
    
	// Retrieve current name of the listicle
    

	$listicle_name = esc_html( get_post_meta( $listicle->ID, 'listicle_name', true ) );
    
	echo ' <input type="text" style="width: 70%;" value="' .  esc_attr($listicle_name) . '" name="listicle_name" id="listicle_name"> ' ;
        
	
}

/*
function display_listicle_parent_meta_box( $listicle ) {
    
	// Retrieve current name of the listicle
    


	$listicle_perma = esc_html( get_post_meta( $listicle->ID, 'listicle_permalink', true ) );

	echo $listicle_perma;


	global $wpdb;
	
$querystr = "
     SELECT wposts.*
    FROM $wpdb->posts wposts
    WHERE  wposts.post_type = 'Post'
	AND wposts.post_title != ''	
    ORDER BY wposts.ID DESC
    
 ";


 $postslist = $wpdb->get_results($querystr, OBJECT);  

echo "<select name='listicle_parent_post_id'>";


foreach ($postslist as $orig) : setup_postdata($orig);

$parentTitle  = get_the_title($orig->ID);

echo'<option value="'.$orig->ID.'">'.$parentTitle.'</option>';
endforeach;
echo"</select>";       
	
}
*/
//if post save parse shortcode
function save_listicle_from_post($shortcode,$parentpostID) {

if (strpos( $shortcode,'[listicle') === false) {

 return $shortcode; //shouldn't happen, got here by mistake

}
       

    //extract shortcode (could use regex)
    $shortcode_start = explode('[listicle', $shortcode);
    $shortcode_end = explode('[/listicle]', $shortcode_start[1]);
	
	$lstContent = $shortcode_end[0];
	
    $lstName = do_shortcode($shortcode);
	

$lstOrder=1;
$deleteExcludeList = 0;
$lstParentID = $parentpostID;

$lstArr = listicle_parse_function($lstName,$lstContent);

$lstName = $lstArr['name'];

$lstTease = $lstArr['tease'];


foreach ($lstArr["items"] as $itemVar) {


$iTitle = $itemVar['title'];
$iBody = $itemVar['body'];

$getListicle = listicle_post_get($iTitle, $lstName);
//echo "get llisticle before add " . $getListicle . "~";
$updatepid = $getListicle;

$deleteExcludeList.=",".$updatepid;
	if($getListicle==0) {
	//echo "add listicle";
	$addpid = listicle_add($iTitle, $lstName, $lstTease, $lstOrder,$iBody,$iAuthor,$lstParentID);
	//echo "added pid " . $addpid;
	$deleteExcludeList.=",".$addpid;
	} else {
	//echo "update listicle";
	$uppid = listicle_update($updatepid, $iTitle, $lstName, $lstTease, $iBody, $lstOrder,$lstParentID);
	}
$lstOrder++;
}
//echo $deleteExcludeList;
listicle_delete($deleteExcludeList,$lstName);
}



function add_listicle( $post_id, $listicle ) {

      // verify this came from the our screen and with proper authorization,
      // because save_post can be triggered at other times
/*
if ( !wp_verify_nonce( $_REQUEST['listicle_name'], plugin_basename(__FILE__) ) ) {
          return $date; 
}

*/


if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
        return $post_id;
}




if ( ! wp_is_post_revision( $post_id ) ){
    // Check post type for listicle
    if ( get_post_type($post_id) == 'listicle' ) {
        // Store data in post meta table if present in post data
         /* Request passes all checks; update the post's metadata */
		 
	
    if (isset($_REQUEST['listicle_name'])) {
	
       // $res = update_post_meta($_REQUEST['post_ID'], 'listicle_name', $_REQUEST['listicle_name']);
	   add_post_meta( $_REQUEST['post_ID'], 'listicle_name',  $_REQUEST['listicle_name'], true ) || update_post_meta( $_REQUEST['post_ID'], 'listicle_name',  $_REQUEST['listicle_name'] );
	 //  add_post_meta( $_REQUEST['post_ID'], 'listicle_order',  0, true ) || update_post_meta( $_REQUEST['post_ID'], 'listicle_order', 0 );
		
		//echo "response: " .$res;
    }




    } else {
	// not a listicle
	// search post for shortcode
	//echo "we are not in a listicle, extract list<br/>";
$matches = array();
$pattern = get_shortcode_regex();
preg_match('/'.$pattern.'/s', $listicle->post_content, $matches);
if (is_array($matches) && $matches[2] == 'listicle') {
   $shortcode = $matches[0];
   //echo do_shortcode($shortcode);


   $lstArr = save_listicle_from_post($shortcode,$listicle->ID);

   }
   } //else

} //wp_is_post_revision

 return $post_id;
}

//add filter to ensure the text listicle, or listicle, is displayed when user updates a listicle 
add_filter('post_updated_messages', 'post_type_updated_messages');
function post_type_updated_messages( $messages ) {
 	global $post, $post_ID;

	$messages['listicle'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('listicle updated. <a href="%s">View listicle</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('listicle updated.'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('listicle restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('listicle published. <a href="%s">View listicle</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('listicle saved.'),
		8 => sprintf( __('listicle submitted. <a target="_blank" href="%s">Preview listicle</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('listicle scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview listicle</a>'),
		// translators: Publish box date format, see http://php.net/date
		date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('listicle draft updated. <a target="_blank" href="%s">Preview listicle</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	);

	return $messages;
}

add_action( 'init', 'register_listicle_shortcodes');


function listicle_delete($deleteExcludeList,$lstName) {

//clears out listicle items that were "not removed" (deleteExcludeList) from the list (ul li)



$postID = 0;
global $wpdb;
//global $post;

$querystr = "
    SELECT $wpdb->posts.* , $wpdb->postmeta.*
    FROM $wpdb->posts, $wpdb->postmeta
    WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
    AND $wpdb->postmeta.meta_key = 'listicle_name' 
    AND $wpdb->postmeta.meta_value = '" . $lstName . "' 
    AND $wpdb->posts.ID not in ($deleteExcludeList)
    AND $wpdb->posts.post_type = 'listicle'
    ORDER BY $wpdb->posts.post_date DESC
 ";
//echo $querystr;
 $pageposts = $wpdb->get_results($querystr, OBJECT);

 $force_delete = true;
 
 if (!empty($pageposts)) { // If the query returned something
	 foreach ($pageposts as $post) {  // Loop though our results!
 	 $postID = $post->ID;
	//echo "deleting postid: " . $postID . " Title: " . $post->post_title;	
	 wp_delete_post( $postID, $force_delete );
		} //for loop
	} //if empty



}


function listicle_update($postid, $lstTitle, $lstName, $lstTease, $lstBody, $lstOrder,$lstParentID) {


// Create post object
$my_post = array(
  'ID'    => $postid,
  'post_title'    => $lstTitle,
  'post_content'  => $lstBody,
  'post_status'   => 'publish',
  'post_type'   => 'listicle',
  'post_excerpt' => $lstTease
  
);

$updatedpost = wp_update_post( $my_post, false );


update_post_meta( $postid, 'listicle_name',  $lstName );
update_post_meta( $postid, 'listicle_order',  $lstOrder );
update_post_meta( $postid, 'listicle_parentID', $lstParentID );
update_post_meta( $postid, 'listicle_excerpt', $lstTease );
return $postid;
}


//adds a listicle using insert_post - no filter
function listicle_add($lstTitle, $lstName, $lstTease, $lstOrder, $lstBody, $lstAuthor,$lstParentID) {


// Create post object
$my_post = array(
  'post_title'    => $lstTitle,
  'post_content'  => $lstBody,
  'post_status'   => 'publish',
  'post_type'   => 'listicle',
  'post_excerpt' => $lstTease
);



// Insert the post into the database

   // remove_action('save_post', 'add_listicle');
    
    
	$postid = wp_insert_post( $my_post, true );
	

	
	// add_action('save_post', 'add_listicle');

	if($postid) {

		$metaadded = add_post_meta( $postid, 'listicle_order',  $lstOrder, true ) || update_post_meta( $postid, 'listicle_order',  $lstOrder );
		$metaadded2 = add_post_meta( $postid, 'listicle_name',  $lstName , true ) || update_post_meta( $postid, 'listicle_name',  $lstName ) ;
		$metaadded4 =   add_post_meta( $postid, 'listicle_excerpt',$lstTease, true ) || update_post_meta( $postid, 'listicle_exceprt',$lstTease );
		$metaadded4 =   add_post_meta( $postid, 'listicle_parentID',$lstParentID, true ) || update_post_meta( $postid, 'listicle_parentID',$lstParentID );

		}

return $postid;
}

//check if listicle exists based on item title and list name
function listicle_post_get($lstTitle, $lstName) {

//echo "listicle post get";
$postID = 0;
global $wpdb;
//global $post;

//echo "item title: " . $lstTitle;
$querystr = "
    SELECT $wpdb->posts.* , $wpdb->postmeta.*
    FROM $wpdb->posts, $wpdb->postmeta
    WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
    AND $wpdb->postmeta.meta_key = 'listicle_name' 
    AND $wpdb->postmeta.meta_value = '" . $lstName . "' 
    AND $wpdb->posts.post_title = '" . $lstTitle . "'
    AND $wpdb->posts.post_type = 'listicle'
    AND $wpdb->posts.post_date < NOW()
    ORDER BY $wpdb->posts.post_date DESC
 ";
//echo $querystr;
 $pageposts = $wpdb->get_results($querystr, OBJECT);

 
 
 if (!empty($pageposts)) { // If the query returned something
					 foreach ($pageposts as $post) {  // Loop though our results!
 
					 $postID = $post->ID;
					 }
				}
 
return $postID;
}



function listicle_parse_function($lstName,$content) {
 //this is the admin view

//$iAuthor = the_author();

$lstArr = array();
$lstArr["name"] = $lstName;
$itmArr = array();
$itmOrder = 0;


$tease="";
	$teaseArr=explode("<em>",$content); 
	
	if(count($teaseArr)){
	$teaseEnd=explode("</em>",$teaseArr[1]);
	$tease=$teaseEnd[0];
	//echo "here's the tease" . $tease;
	}
	
	$lstArr["tease"] = $tease;
	
	
	
	
	
	
	
	
	
	$strarr=explode("<li>",$content); //Breaks every <li>
	
//	echo "<h3> size of strarr: " . count($strarr) . "</h3>";
	
$i=(-1); //Makes -1 as array starts at 0
$arr=array(); //this will be your array
$tarr = array();

foreach($strarr as $expl){ //Go through all li's
$ai=explode("</li>",$expl);//Get between the li. If there is something between </li> and <li>, it won't return it as you don't need it
if($i!=(-1))$arr[$i]=$ai[0]; //First isn't valid
$i=$i+1; //add i plus one to make it a real array
}

// $array should now contain your elements

$itemNameCounter = 1;
foreach($arr as $val){


//replace <B> with <strong>
$val = str_ireplace (  "<b>" , "<strong>",$val);
//replace </B> with </strong>
$val = str_ireplace ( "</b>" , "</strong>",$val);


//we use the <strong> tag to identify the post title
$titleArr=explode("<strong>",$val); //Breaks <strong>
//echo "count of titleArr: " .count($titleArr);
if(count($titleArr)==1) {
$titleArr[0]="<strong>"; 
$titleArr[1] = $lstName . " - " . $itemNameCounter . "</strong>" . $val;
}
//$item should always have an end strong - even if we fake it
$y=(-1); 

$item = $titleArr[1];
$bi=explode("</strong>",$item);//Get between the strong. 



$iTitle = $bi[0];
$iBody = $bi[1];



$itmOrder=$itmOrder+1;

$itmArr[$itmOrder]["title"] = $iTitle;
$itmArr[$itmOrder]["body"] = $iBody;

//echo "title: " . $iTitle;

//echo " body: " . $iBody . "<br/>";

$itemNameCounter++;

}

$lstArr["items"] = $itmArr;



return $lstArr;
}


//shortcode function - parses shortcode for list and creates listicles
function listicle_posts_function($atts, $content = null) {



extract(shortcode_atts(array(
      'name' => 1,
   ), $atts));

   
if( is_admin() ) {

return $atts['name'];

} //this is the admin view

if(is_singular()) { // we're viewing the post / page
//array( 'meta_key' => 'color', 'meta_value' => 'blue' ) 



 ob_start();
  
  
$lstArr = array();

//$args = array( 'numberposts' => 3 );


global $wpdb;

$querystr = "
     SELECT wposts.* , wpostmeta.meta_value, wpostmeta.meta_key
    FROM $wpdb->posts wposts
    LEFT JOIN $wpdb->postmeta wpostmeta ON wposts.ID = wpostmeta.post_id
    LEFT JOIN $wpdb->postmeta AS listorder ON(
	wposts.ID = listorder.post_id
	AND listorder.meta_key = 'listicle_order'
	)
    WHERE  wpostmeta.meta_key = 'listicle_name' 
    AND wpostmeta.meta_value = '" . $atts['name'] . "' 
    AND wposts.post_status = 'publish' 
    AND wposts.post_type = 'listicle'
    AND wposts.post_date < NOW()
    ORDER BY listorder.meta_value ASC
    Limit 1
 ";



 $pageposts = $wpdb->get_results($querystr, OBJECT);


 if ($pageposts): 
  foreach ($pageposts as $list): 
  
  setup_postdata($list); 
  
  //echo "looking for the excerpt";
  
  $listTease = get_post_meta($list->ID,"listicle_excerpt",true);
  
 // echo "the tease is " . $listTease;
 
 
  $listexcerpt = $list->post_excerpt;
  
  $permalink = get_permalink($list->ID  );
  $listTitle = get_the_title($list->ID);
    
 //permalink_anchor(); 
  
  if($listexcerpt!="") {
	echo "<div class='listicle_list_excerpt'>" . $listexcerpt . "</div>";
	// getting the excerpt does not seem to be working consistently
	} else {
  echo "<div class='listicle_list_excerpt'>" . $listTease . "</div>";
	}

  echo "<div class='navigation'><div class='alignleft'><a class='listicle_item_link' href='" . $permalink. "'>";
  echo $listTitle;
  echo "</a></div></div>";
 
 
 
 endforeach;
 endif;
 
 
 $output_string = ob_get_contents();
  ob_end_clean();
  return $output_string;
  
  
} // is singular
  
}

function register_listicle_shortcodes(){
   add_shortcode('listicle', 'listicle_posts_function');
}


function listicle_template_function($content){


    if(is_singular("listicle")) {
    
    global $post;
    global $wpdb;
	
	$post_id = $post->ID; 
    	$meta_values = get_post_meta($post_id);

	$listicle_parent = $meta_values["listicle_parentID"][0] ;
	$listicle_name = $meta_values["listicle_name"][0];
	$listicle_order = $meta_values["listicle_order"][0];
	
  	$parentPerm = get_permalink($listicle_parent);
	$parentTitle = get_the_title($listicle_parent);

	$post_type = get_post_type();
	
	
	
	//$content .= "<h1>Parent Title" . $parentTitle . "</h1>";
	 $content = get_the_content( "more");
	

$querystr = "
     SELECT wposts.* , wpostmeta.meta_value, wpostmeta.meta_key
    FROM $wpdb->posts wposts
    LEFT JOIN $wpdb->postmeta wpostmeta ON wposts.ID = wpostmeta.post_id
    LEFT JOIN $wpdb->postmeta AS listorder ON(
	wposts.ID = listorder.post_id
	AND listorder.meta_key = 'listicle_order'
	And listorder.meta_value < '" .$listicle_order."' 
	)
    WHERE  wpostmeta.meta_key = 'listicle_name' 
    AND wpostmeta.post_id = wposts.ID
    AND wpostmeta.meta_value = '" . $listicle_name . "' 
    AND wposts.post_status = 'publish' 
    AND wposts.post_type = 'listicle'
    AND wposts.ID != " . $post_id. "
    AND wposts.post_date < NOW()
    ORDER BY listorder.meta_value ASC
    Limit 1
 ";



 $prevpost = $wpdb->get_results($querystr, OBJECT);

 
 //echo "here " . $listicle_name;
 //var_dump($prevpost);
//echo '<div class="navigation">';

$content .= '<div class="navigation">';
 if ($prevpost): 

  foreach ($prevpost as $prev): 
  
 
  setup_postdata($prev); 
  if (!empty($prev->ID)) { 
  
  
  $permaprev = get_permalink($prev->ID);
  $prevTitle = get_the_title($prev->ID);


ob_start();

	echo '<div class="alignleft"><span class="older">';
	echo '<a class="listicle_item_link" href="' . $permaprev . '" title="'. $prevTitle.' ">'. $prevTitle . "</a>";
	echo "</span></div>";
 } else {

	echo '<div class="alignleft"><span class="older">';
	echo '<a class="listicle_item_link" href="' . $parentPerm . '" title="'. $parentTitle.' ">stick the parent post here'. $parentTitle . "</a>";
	echo "</span></div>";
 
} 
  $content .= ob_get_contents();
  ob_end_clean();
  
 
 
 endforeach;
 endif;
	
	

$querystr = "
     SELECT wposts.* , wpostmeta.meta_value, wpostmeta.meta_key
    FROM $wpdb->posts wposts
    LEFT JOIN $wpdb->postmeta wpostmeta ON wposts.ID = wpostmeta.post_id
    LEFT JOIN $wpdb->postmeta AS listorder ON(
	wposts.ID = listorder.post_id
	AND listorder.meta_key = 'listicle_order'
	And listorder.meta_value > '" .$listicle_order."' 
	)
    WHERE  wpostmeta.meta_key = 'listicle_name'
    AND wpostmeta.post_id = wposts.ID 
    AND wpostmeta.meta_value = '" . $listicle_name . "' 
    AND wposts.post_status = 'publish' 
    AND wposts.post_type = 'listicle'
    AND wposts.ID != " . $post_id. "
    AND wposts.ID != " .$prev->ID. "
    AND wposts.post_date < NOW()
    ORDER BY listorder.meta_value ASC
    Limit 1
 ";


 $nextpost = $wpdb->get_results($querystr, OBJECT);

 if ($nextpost): 

  foreach ($nextpost as $next): 
  
 
  setup_postdata($next); 
  if (!empty($next->ID)) { 
  
  
  $permalink = get_permalink($next->ID);
  $nextTitle = get_the_title($next->ID);
  ob_start();
  
	echo '<div class="alignright"><span class="newer">';
	echo '<a class="listicle_item_link" href="' . $permalink . '" title="'. $nextTitle.' ">'. $nextTitle . "</a>";
	echo "</span></div>";
 } 
  else {
	echo '<div class="alignright"><span class="newer">';
	echo '<a class="listicle_item_link" href="' . $parentPerm . '" title="'. $parentTitle.' ">stick the parent post here'. $parentTitle . "</a>";
	echo "</span></div>";
 
} 
 
  $content .= ob_get_contents();
  ob_end_clean();
 
 endforeach;
 endif;
	
$content.= '</div><!--navigation-->'; 









    } 


return $content;
	
}

add_filter('the_content', 'listicle_template_function');



function listicle_next_fix($link) {

global $post;
if($post->post_type=="listicle") {
    $post = get_post($post_id);
    $next_post = get_next_post();
    $title = $next_post->post_title;
    //$link = str_replace("rel=", 'title="' . $title . '" rel=', $link);
$nolink = "";

    return $nolink;
} else {
return $link;
}
}


function listicle_prev_fix($link) {

global $post;
if($post->post_type=="listicle") {
    	$post_id = $post->ID; 
    	$meta_values = get_post_meta($post_id);
	$listicle_parent = $meta_values["listicle_parentID"][0] ;
	$listicle_order = $meta_values["listicle_order"][0] ;
	$parentPerma = get_permalink($listicle_parent);
	$parentTitle = get_the_title($listicle_parent);

$nolink = "<li class='listicle_list_parent'><a class='listicle_parent_link' href='" . $parentPerma."'>" . $parentTitle . "</a></li>";

    return $nolink;
} else {
return $link;
}

}
add_filter('next_post_link', 'listicle_next_fix');
add_filter('previous_post_link', 'listicle_prev_fix');


    function listicle_styles()  
    {  
        // Register the style like this for a plugin:  
        wp_register_style( 'listicle-style', plugins_url( '/css/listicle-styles.css', __FILE__ ), array(), '20120208', 'all' );  
       
        // enqueue the style:  
        wp_enqueue_style( 'listicle-style' );  
    }  

    add_action( 'wp_enqueue_scripts', 'listicle_styles' );  

	
	require_once("listease.php");

?>
