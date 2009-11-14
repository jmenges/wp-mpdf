<?php
/*
Plugin Name: wp-mpdf
Plugin URI: http://www.fkrauthan.de/wordpress/wp-mpdf
Description: Print a wordpress page as PDF with optional Geshi Parsing.
Version: 1.9
Author: Florian 'fkrauthan' Krauthan
Author URI: http://www.fkrauthan.de

Copyright 2009  Florian Krauthan
*/

/*
 * This file is part of wp-mpdf.
 * wp-mpdf is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free  
 * Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * wp-mpdf is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or  
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with wp-mpdf. If not, see <http://www.gnu.org/licenses/>.
 */

define('WP_MPDF_ALLOWED_POSTS_DB', 'wp_mpdf_allowed');

function mpdf_install() {
	global $wpdb;

   	$table_name = $wpdb->prefix . WP_MPDF_ALLOWED_POSTS_DB;
	if($wpdb->get_var('SHOW TABLES LIKE "'.$table_name.'"') != $table_name) {
		$sql = 'CREATE TABLE ' . $table_name . ' (
	  		id mediumint(9) NOT NULL AUTO_INCREMENT,
	  		post_type VARCHAR(4) DEFAULT "post" NOT NULL,
	  		post_id mediumint(9) NOT NULL,
	  		enabled smallint(1) NOT NULL,
	  		UNIQUE KEY id (id)
		);';

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);


		add_option('mpdf_theme', 'default');
		add_option('mpdf_geshi', false);
		add_option('mpdf_caching', true);
		add_option('mpdf_allow_all', true);
	}

	if(is_dir(dirname(__FILE__).'/themes/')) {
		//Move the Theme dir
		if(!is_dir(dirname(__FILE__).'/../../wp-mpdf-themes')) {
			if(!@mkdir(dirname(__FILE__).'/../../wp-mpdf-themes')) {
				echo '<p>Can\'t create mpdf themes dir. Please create the dir "wp-content/wp-mpdf-themes" and give your webserver write permission to it.</p>';
			}
		}

		$dh = opendir(dirname(__FILE__).'/themes/');
		while(($file = readdir($dh)) !== false) {
    			if($file != "." && $file != "..") {
				if(!@rename(dirname(__FILE__).'/themes/'.$file, dirname(__FILE__).'/../../wp-mpdf-themes/'.$file)) {
					echo '<p>Can\'t move the file "'.'wp-content/plugins/wp-mpdf/themes/'.$file.'" to "'.'wp-content/wp-mpdf-themes/'.$file.'". Please do this by your self.</p>';
				}
			}
		}
		closedir($dh);

		if(!@rmdir(dirname(__FILE__).'/themes/')) {
			echo '<p>Can\'t delete the folder "wp-content/plugins/wp-mpdf/themes/". Please to this by your self.</p>';
		}
	}
}

function mpdf_output($wp_content = '', $do_pdf = false ) {
	global $post;
	$pdf_filename = $post->post_name . '.pdf';
	
	$wp_content = mpdf_filter($wp_content, $do_pdf);
	if(function_exists('polyglot_filter')) $wp_content = polyglot_filter($wp_content);
	if(function_exists('filter_bbcode')) $wp_content = filter_bbcode($wp_content);
	
	/**
	 * Geshi Support
	 */
	if(get_option('mpdf_geshi')==true) {
		require_once('wp-content/plugins/wp-mpdf/geshi.inc.php');
		$wp_content = ParseGeshi($wp_content);
	}
	 
	
	if($do_pdf === false) {
		echo $wp_content;
	}
	else {
		define('_MPDF_PATH',dirname(__FILE__).'/mpdf/');
		require_once(_MPDF_PATH.'mpdf.php');
		
		$mpdf=new mPDF(); 

		$mpdf->SetUserRights();
		$mpdf->title2annots = true;
		//$mpdf->annotMargin = 12;
		$mpdf->use_embeddedfonts_1252 = true;	// false is default
		$mpdf->SetBasePath(dirname('../../../'.dirname(__FILE__)));

		$user_info = get_userdata($post->post_author);
		$mpdf->SetAuthor($user_info->first_name.' '.$user_info->last_name.' ('.$user_info->user_login.')');
		$mpdf->SetCreator('wp-mpdf');
		
		
		//The Header and Footer
		global $pdf_footer;
		global $pdf_header;
		
		$mpdf->startPageNums();	// Required for TOC use after AddPage(), and to use Headers and Footers
		$mpdf->setHeader($pdf_header);
		$mpdf->setFooter($pdf_footer);
		
		
		if(get_option('mpdf_theme')!=''&&file_exists('wp-content/wp-mpdf-themes/'.get_option('mpdf_theme').'.css')) {
			//Read the StyleCSS
			$tmpCSS = file_get_contents('wp-content/wp-mpdf-themes/'.get_option('mpdf_theme').'.css');
			$mpdf->WriteHTML($tmpCSS, 1);
		}
		
		//My Filters
		require_once('wp-content/plugins/wp-mpdf/myfilters.inc.php');
		$wp_content = mpdf_myfilters($wp_content);
		//die($wp_content);
		$mpdf->WriteHTML($wp_content);
		
		if(get_option('mpdf_caching')==true) {
			file_put_contents('wp-content/plugins/wp-mpdf/cache/'.get_option('mpdf_theme').'_'.$pdf_filename.'.cache', $post->post_modified_gmt);
			$mpdf->Output('wp-content/plugins/wp-mpdf/cache/'.get_option('mpdf_theme').'_'.$pdf_filename, 'F');
			$mpdf->Output($pdf_filename, 'I');
		}
		else {
			$mpdf->Output($pdf_filename, 'I');
		}
	}
}

function mpdf_filter($wp_content = '', $do_pdf = false, $convert = false) {
	$delimiter1 = 'screen';
	$delimiter2 = 'print';

	if($do_pdf === false ) {
		$d1a = '[' . $delimiter1 . ']';
		$d1b = '[/' . $delimiter1 . ']';
		$d2a = '\[' . $delimiter2 . '\]';
		$d2b = '\[\/' . $delimiter2 . '\]';
	} else {
		$d1a = '[' . $delimiter2 . ']';
		$d1b = '[/' . $delimiter2 . ']';
		$d2a = '\[' . $delimiter1 . '\]';
		$d2b = '\[\/' . $delimiter1 . '\]';
	}

	format_to_post('the_content');

	$wp_content = str_replace($d1a , '', $wp_content);
	$wp_content = str_replace($d1b , '', $wp_content);

	$ctpdf_wp_content = preg_replace("/$d2a(.*?)$d2b/s", '', $wp_content);


	if ($convert == true) {
		$wp_content = mb_convert_encoding($wp_content, "ISO-8859-1", "UTF-8");
	}

	return $wp_content;
}

function mpdf_mysql2unix($timestamp) {
// stolen cold-blooded from the Polyglot plugin
	$year = substr($timestamp,0,4);
	$month = substr($timestamp,5,2);
	$day = substr($timestamp,8,2);
	$hour = substr($timestamp,11,2);
	$minute = substr($timestamp,14,2);
	$second = substr($timestamp,17,2);
	return mktime($hour,$minute,$second,$month,$day,$year);
}

function mpdf_pdfbutton($opennewtab=false, $buttontext = '', $print_button = true) {
	if(get_option('mpdf_allow_all')!=true) {
		global $wpdb;
		global $post;
		$table_name = $wpdb->prefix . WP_MPDF_ALLOWED_POSTS_DB;
		
		$sql = 'SELECT id FROM '.$table_name.' WHERE post_id='.$post->ID.' AND post_type="'.$post->post_type.'" AND enabled=1 LIMIT 1';
		$db_id = $wpdb->get_var($sql);
		if(($db_id == null&&get_option('mpdf_allow_all')==2)||($db_id != null&&get_option('mpdf_allow_all')==3)) {
			if($print_button === true) {
				echo '';
				return;
			} else {
				return '';
			}
		}
	}
	
	
	if(empty($buttontext))
		$buttontext = '<img src="' . get_bloginfo('home') . '/wp-content/plugins/wp-mpdf/pdf.png" alt="This page as PDF" border="0" />';
	
	$x = !strpos(apply_filters('the_permalink', get_permalink()), '?') ? '?' : '&amp;';
	$pdf_button = '<a ';
	if($opennewtab==true) $pdf_button .= 'target="_blank" ';
	$pdf_button .= 'id="pdfbutton" href="' . apply_filters('the_permalink', get_permalink()) . $x . 'output=pdf">' . $buttontext . '</a>';
	
	if($print_button === true) {
		echo $pdf_button;
	} else {
		return $pdf_button;
	}
}

function mpdf_readcachedfile($name) {
	$fp = fopen('wp-content/plugins/wp-mpdf/cache/'.get_option('mpdf_theme').'_'.$name, 'rb');
	if(!$fp) die('Couldn\'t Read cache file');
	fclose($fp);
	
	Header('Content-Type: application/pdf');
	Header('Content-Length: '.filesize('wp-content/plugins/wp-mpdf/cache/'.get_option('mpdf_theme').'_'.$name));
	Header('Content-disposition: inline; filename='.$name);
	
	echo file_get_contents('wp-content/plugins/wp-mpdf/cache/'.get_option('mpdf_theme').'_'.$name, FILE_BINARY | FILE_USE_INCLUDE_PATH);
}

function mpdf_exec() {
	if($_GET['output'] == 'pdf') {
		//Check if this Page is allwoed to print as PDF
		if(get_option('mpdf_allow_all')!=true) {
			global $wpdb;
			global $post;
			$table_name = $wpdb->prefix . WP_MPDF_ALLOWED_POSTS_DB;
			
			$sql = 'SELECT id FROM '.$table_name.' WHERE post_id='.$post->ID.' AND post_type="'.$post->post_type.'" AND enabled=1 LIMIT 1';
			$db_id = $wpdb->get_var($sql);
			if(($db_id == null&&get_option('mpdf_allow_all')==2)||($db_id != null&&get_option('mpdf_allow_all')==3)) {
				if($print_button === true) {
					return;
				} else {
					return;
				}
			}
		}
		
	
		//Check for Caching option
		if(get_option('mpdf_caching')==true) {
			global $post;
			$pdf_filename = $post->post_name . '.pdf';
			if(file_exists('wp-content/plugins/wp-mpdf/cache/'.get_option('mpdf_theme').'_'.$pdf_filename.'.cache')&&file_exists('wp-content/plugins/wp-mpdf/cache/'.get_option('mpdf_theme').'_'.$pdf_filename)) {
				$createDate = file_get_contents('wp-content/plugins/wp-mpdf/cache/'.get_option('mpdf_theme').'_'.$pdf_filename.'.cache');
				if($createDate==$post->post_modified_gmt) {
					//We could Read the Cached file
					mpdf_readcachedfile($pdf_filename);
					exit;
				}
			}
		} 
		
		require_once('wp-content/wp-mpdf-themes/'.get_option('mpdf_theme').'.php');
		
		mpdf_output($pdf_output, true);
		
		exit;
	}
}

function mpdf_admin() {
	require_once('wp-mpdf_admin.php');
	mpdf_admin_display();
}

function mpdf_create_admin_menu() {
	add_submenu_page('options-general.php', 'wp-mpdf - config', 'wp-mpdf', 8, dirname(__FILE__), 'mpdf_admin');
	
	if(function_exists('add_meta_box')) {
		add_meta_box('mpdf_admin', 'wp-mpdf', 'mpdf_admin_printeditbox', 'post', 'normal', 'high');
		add_meta_box('mpdf_admin', 'wp-mpdf', 'mpdf_admin_printeditbox', 'page', 'normal', 'high');
	}
	else {
		add_action('dbx_post_advanced', 'mpdf_admin_printeditbox_old');
    	add_action('dbx_page_advanced', 'mpdf_admin_printeditbox_old');
	}
}

function mpdf_admin_printeditbox() {
	global $wpdb;
	global $post;
	
	$table_name = $wpdb->prefix . WP_MPDF_ALLOWED_POSTS_DB;
	$sql = 'SELECT id FROM '.$table_name.' WHERE post_id='.$post->ID.' AND post_type="'.$post->post_type.'" LIMIT 1';
	$db_id = $wpdb->get_var($sql);	
	echo '<input type="hidden" name="wp_mpdf_noncename" id="wp_mpdf_noncename" value="' . wp_create_nonce(plugin_basename(__FILE__)) . '" />';
	echo 'Can Download as PDF: <input ';
	if($db_id != null) {
		echo 'checked="checked" ';
	}
	echo 'type="checkbox" name="wp_mpdf_candownload" />';
}

/* Prints the edit form for pre-WordPress 2.5 post/page */
function mpdf_admin_printeditbox_old() {

	echo '<div class="dbx-b-ox-wrapper">' . "\n";
  	echo '<fieldset id="mpdf_admin" class="dbx-box">' . "\n";
  	echo '<div class="dbx-h-andle-wrapper"><h3 class="dbx-handle">wp-mpdf</h3></div>';   
   
  	echo '<div class="dbx-c-ontent-wrapper"><div class="dbx-content">';

  	// output editing form

  	mpdf_admin_printeditbox();

  	// end wrapper

  	echo '</div></div></fieldset></div>'."\n";
}


function mpdf_admin_savepost($post_id) {
	if(!wp_verify_nonce($_POST['wp_mpdf_noncename'], plugin_basename(__FILE__))) {
    	return $post_id;
  	}
	
	if('page' == $_POST['post_type']) {
    	if(!current_user_can('edit_page', $post_id))
     		return $post_id;
  	} else if($_POST['post_type'] == 'post') {
    	if(!current_user_can('edit_post', $post_id))
      		return $post_id;
  	}
	else
		return $post_id;


	$post_id = wp_is_post_revision($post_id);
	if($post_id==0) return $post_id;
	
	
	global $wpdb;
   	$table_name = $wpdb->prefix . WP_MPDF_ALLOWED_POSTS_DB;
	
	$canPrintAsPDF = isset($_POST['wp_mpdf_candownload']);
	$sql = 'SELECT id FROM '.$table_name.' WHERE post_id='.$post_id.' AND post_type="'.$_POST['post_type'].'" LIMIT 1';
	$db_id = $wpdb->get_var($sql);
	
	if($db_id != null && $canPrintAsPDF == false) {
		$wpdb->query('DELETE FROM '.$table_name.' WHERE id='.$db_id.' LIMIT 1');
	}
	else {
		if($db_id == null && $canPrintAsPDF = true) {
			$sql = 'INSERT INTO '.$table_name.' (post_type, post_id, enabled) VALUES (%s, %d, %d)';
			$wpdb->query($wpdb->prepare($sql, $_POST['post_type'], $post_id, true));
		}
	}
}


//Register Filter
add_action('template_redirect', 'mpdf_exec', 98);
add_action('admin_menu', 'mpdf_create_admin_menu');
add_filter('the_content', 'mpdf_filter');

add_action('save_post', 'mpdf_admin_savepost');

register_activation_hook(__FILE__, 'mpdf_install');
?>
