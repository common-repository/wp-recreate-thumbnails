<?php
/**
 *
 * Plugin Name: WP Recreate Thumbnails
 * Description: This Plugin helps to create thumbnails of uploaded images
 * Version: 1.2.0
 * Author: Yudiz Solution Ltd.
 * Author URI: http://www.yudiz.com/
 *
 **/

if( !defined('ABSPATH') ) die();

define( 'YSPL_RBT_PLUGIN', __FILE__ );
/**
* Plugin directory path
*/
define( 'YSPL_RBT_PLUGIN_DIR', untrailingslashit( dirname( YSPL_RBT_PLUGIN ) ) );

require_once(ABSPATH . '/wp-admin/includes/media.php');
require_once(ABSPATH . '/wp-admin/includes/image.php');


function yspl_rbt_enqueue_styles() {

	wp_enqueue_script('custom-script', plugin_dir_url( __FILE__ ).'js/create_thumb.js');
	wp_enqueue_style('fontawesome-style', plugin_dir_url( __FILE__ ).'css/font-awesome.min.css');
	wp_enqueue_style('custom-style', plugin_dir_url( __FILE__ ).'css/style.css');
	wp_enqueue_script('redirect-script', plugin_dir_url( __FILE__ ).'js/jquery.redirect.js');

	// Bind attributes needed for ajax
    $passedValues = array( 				
                        "url" => admin_url(sanitize_file_name('admin-ajax.php')) , 
                        "action" => 'yspl_rbt_create_thumbnails' , 
                        "ajax_loader" => plugin_dir_url( __FILE__ ).'css/loader.gif'
                    );
    wp_localize_script( 'custom-script', 'passed_object', $passedValues );
}
add_action( 'admin_enqueue_scripts', 'yspl_rbt_enqueue_styles' );

function yspl_rbt_create_thumbnails(){
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	global $_wp_additional_image_sizes; 
	
	$args = array(
			    'post_type' => 'attachment',
			    'post_mime_type' =>'image',
			    "posts_per_page" => -1
		);
	$thumbnails = get_posts( $args );
	$i=0;
	$result = array();
	if(!empty($thumbnails)){
		foreach ($thumbnails as $key => $thumbnail) {
			$id = $thumbnail->ID;
			
			$metadata = wp_get_attachment_metadata( $id );
		
		  	$fullsizepath = $thumbnail->guid;
		  	$file_info = pathinfo($metadata['file']);
			$dirname = $file_info['dirname'];
		  	$file_original_size = getimagesize($fullsizepath);
		  	$result[$i]['file']['filename'] = $file_info['basename'];
		  	$result[$i]['file']['thumbnails'] = array();

		  	$file_check = get_attached_file($id);
		  	
		  	if ( false === $file_check || !file_exists($file_check) )
	            continue;
		  	
	        $upload_dir = wp_upload_dir()['basedir'];
	        
	        foreach ($metadata['sizes'] as $key1 => $size) {
				$file = $upload_dir.'/'.$dirname.'/'.$size['file'];

				if(file_exists($file)) unlink($file);

	        	$image = wp_get_image_editor($fullsizepath);
		        if (!is_wp_error($image)) {
		            $image->resize($size['width'], $size['height'], true);
		            $image->save($file);
		            $result[$i]['file']['thumbnails'][$key1] = $size['file'];
		        }		
	        }
	        $i++;
		}
    	wp_send_json_success(array('success'=>"true","data"=>$result));   
	}else{
		wp_send_json_error();
	}
}
add_action( 'wp_ajax_yspl_rbt_create_thumbnails', 'yspl_rbt_create_thumbnails' );
add_action( 'wp_ajax_nopriv_yspl_rbt_create_thumbnails', 'yspl_rbt_create_thumbnails' );

add_action('admin_menu', 'yspl_rbt_thumbnail_page');
function yspl_rbt_thumbnail_page() {
    add_menu_page("Recreate Thumbnails","Recreate Thumbnails",'manage_options',"yspl_rbt_recreate_thumbnails",'yspl_rbt_recreate_thumbnails');
}

function yspl_rbt_create_single_thumb($id){
		if(!empty($_POST['id'])){
			$id = sanitize_text_field($_POST['id']);
		}
		
		$thumbnail = get_post($id);
		$result = array();
		$metadata = wp_get_attachment_metadata( $id );

		if(!empty($thumbnail)){

		  	$fullsizepath = $thumbnail->guid;
		  	$file_info = pathinfo($metadata['file']);
			$dirname = $file_info['dirname'];
		  	$file_original_size = getimagesize($fullsizepath);
		  	$result['file']['filename'] = $file_info['basename'];
		  	$result['file']['thumbnails'] = array();
		  	
		  	$file_check = get_attached_file($id);
		  	
		  	if ( false === $file_check || !file_exists($file_check) )
	            return;
		  	
	        $upload_dir = wp_upload_dir()['basedir'];
	        
	        foreach ($metadata['sizes'] as $key1 => $size) {
	        	$file = $upload_dir.'/'.$dirname.'/'.$size['file'];

				if(file_exists($file)) unlink($file);

	        	$image = wp_get_image_editor($fullsizepath);
		        if (!is_wp_error($image)) {
		            $image->resize($size['width'], $size['height'], true);
		            $image->save($file);
		            $result['file']['thumbnails'][$key1]['file'] = $size['file'];
		            $result['file']['thumbnails'][$key1]['size'] = $size['width']." x ".$size['height'];
		        }		
	        }
	        if(!empty($_POST['id'])){
	        	wp_send_json_success(array( "message"=>"Created thumbnails for ".$result['file']['filename'] ,
										  	"filename"=>$result['file']['filename'],
										  	"url"=>get_admin_url().'admin.php?page=yspl_rbt_recreate_thumbnails&post='.$id,
							  			)
				);
				exit;
			}
		}
		return $result;
}
add_action( 'wp_ajax_yspl_rbt_create_single_thumb', 'yspl_rbt_create_single_thumb' );
add_action( 'wp_ajax_nopriv_yspl_rbt_create_single_thumb', 'yspl_rbt_create_single_thumb' );

function yspl_rbt_recreate_thumbnails(){
	ob_start(); ?>
	<div class="img-container">
		<h1>Recreate Thumbnails</h1>
		<?php
		if(!empty($_POST)){
			$id = sanitize_text_field($_GET['post']);
			$fileData = yspl_rbt_create_single_thumb($id);
			$imgsrc = wp_get_attachment_image_src($id,'medium')[0];
			$title = sanitize_text_field($_POST['file']);
			?>
			<h4><?php echo $title; ?></h4>
			<div class='single-img'>
				<img src="<?php echo $imgsrc;?>">
			</div>
			<div>
				<h4>Generated Thumbnails</h4>
				<ul>
				<?php
					foreach ($fileData['file']['thumbnails'] as $size => $file) {
						?>
							<li><span class="size-label"><?php echo $size; ?></span> : <span class="size-text"><?php echo $file['size'];?> Pixels </span> : <span> <?php echo $file['file']; ?></span></li>
						<?php
					}
				?>
			</div>
			<?php
		}else{
			?>
			<h3>Recreate Thumbnails For All Images</h3>
			<button class="btn_regen button-secondary button-large"><span class="fa fa-picture-o"></span> Recreate Thumbnails</button>
			<div class="progress"></div>
			<?php
		}
		?>
			<div id="result"></div>
	</div>
	<?php
	echo ob_get_clean();
}

function yspl_rbt_attachment_fields_to_edit($form_fields, $post) {
    $form_fields["custom1"]["label"] = __("");
    $form_fields["custom1"]["input"] = "html";
    $form_fields["custom1"]["html"] = '<a href="javascript:singleThumb('.$post->ID.');" class="button-secondary button-large" title="' . esc_attr( __( 'Recreate the thumbnails for this single image', 'recreate-thumbnails' ) ) . '">' . __( 'Recreate thumbnail', 'recreate-thumbnails' ) . '</a>';
    return $form_fields;
}
add_filter("attachment_fields_to_edit", "yspl_rbt_attachment_fields_to_edit", null, 2);