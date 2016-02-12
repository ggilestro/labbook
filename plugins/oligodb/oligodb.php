<?php
/*
Plugin Name: oligodb
Plugin URI: https://github.com/ggilestro/labbook
Description: oligodb plugin 
Version: 0.1
Author: Giorgio Gilestro
Author URI: http://gilest.ro
License: GPL2
Credits: Thanks to Srini G for the quotes-collection code upon which this plugins was created
*/

/*  Copyright 2014 Giorgio Gilestro (email : giorgio@gilest.ro)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



/*    The 'Next oligo »' link text
    By default, this is 'Next oligo »' (or the corresponding translation).
    You can change it if you wish */
$oligodb_next_oligo = "";



/*    The maximum number iterations for the 'auto refresh'. Set this number to 0 
    if you want the auto refresh to happen infinitely. */
$oligodb_auto_refresh_max = 30;


/*  Refer http://codex.wordpress.org/Roles_and_Capabilities */
$oligodb_admin_userlevel = 'edit_posts'; 

$oligodb_version = '0.1';
$oligodb_db_version = '0.1'; 


require_once('oligodb-ajax.php');
//require_once('oligodb-widget.php');
require_once('oligodb-admin.php');
require_once('oligodb-shortcodes.php');

function oligodb_get_oligos($condition = "")
{
    global $wpdb;
    $sql = "SELECT oligo_id, oligo_name, oligo_seq, oligo_len, oligo_tm, owner, tags, template, comments, time_added, time_updated
        FROM " . $wpdb->prefix . "oligodb"
        . $condition;
    
    if($oligos = $wpdb->get_results($sql, ARRAY_A))
        return stripslashes_deep($oligos);
    else
        return array();

}


function oligodb_get_oligo($oligo_name="")
{
    global $wpdb;
    $sql = "SELECT oligo_id, oligo_name, oligo_seq, oligo_len, oligo_tm, owner, tags, template, comments, time_added, time_updated
        FROM " . $wpdb->prefix . "oligodb WHERE oligo_name LIKE '". $oligo_name ."'";
    
    $oligo = $wpdb->get_row($sql, ARRAY_A);
    return $oligo;

}


function oligodb_getnewID($prefix = "pGG")
{
    global $wpdb;
    $tablename = $wpdb->prefix . "oligodb";
    
    $sql = "SELECT oligo_name from " . $tablename ." ORDER BY oligo_id DESC LIMIT 1";
    $lastid = $wpdb->get_var($sql);
    
    $bits = explode("-", $lastid);
    $number = intval( $bits[1] ) + 1;
   
    return $prefix."-".$number;
}

function oligodb_calculate_tm($oligo_seq)
{
    $tm = 0;
    $oligo_seq = strtoupper($oligo_seq);
    $count = array ( "A", "C", "T", "G", "all");
        
    $count["A"] = substr_count($oligo_seq, 'A');
    $count["C"] = substr_count($oligo_seq, 'C');
    $count["T"] = substr_count($oligo_seq, 'T');
    $count["G"] = substr_count($oligo_seq, 'G');
    $count["all"] =  strlen($oligo_seq);
    
    if ($count["all"] > 0 && $count["all"] < 14) {
        $tm = (2 * ($count["A"] + $count["T"]) + 4 * ($count["G"] + $count["C"]));
    }
    if ($count["all"] > 13 ) {
        $tm = (64.9 + 41 * (($count["G"] + $count["C"] - 16.4) / $count["all"]));
    }
    
    $tm = number_format((float)$tm, 2, '.', ''); 
    return intval($tm);
}

function oligodb_calculate_len($oligo_seq){
    return strlen($oligo_seq);
}

function oligodb_to_order($condition = "")
{
    global $wpdb;
    $sql = "SELECT * FROM " . $wpdb->prefix . "oligodb WHERE ordered = '127'".$condition;
    

    if($oligos = $wpdb->get_results($sql, ARRAY_A))
        return stripslashes_deep($oligos);
    else
        return array();

}


function oligodb_count($condition = "")
{
    global $wpdb;
    $sql = "SELECT COUNT(*) FROM " . $wpdb->prefix . "oligodb ".$condition;
    $count = $wpdb->get_var($sql);
    return $count;
}

function oligodb_pagenav($total, $current = 1, $format = 0, $paged = 'paged', $url = "")
{
    if($total == 1 && $current == 1) return "";
    
    if(!$url) {
        $url = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$url .= "s";}
        $url .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
        } else {
            $url .= $_SERVER["SERVER_NAME"];
        }

        if ( get_option('permalink_structure') != '' ) {
            if($_SERVER['REQUEST_URI']) {
                $request_uri = explode('?', $_SERVER['REQUEST_URI']);
                $url .= $request_uri[0];
            }
            else $url .= "/";
        }
        else {
            $url .= $_SERVER["PHP_SELF"];
        }
        
        if($query_string = $_SERVER['QUERY_STRING']) {
            $parms = explode('&', $query_string);
            $y = '';
            foreach($parms as $parm) {
                $x = explode('=', $parm);
                if($x[0] == $paged) {
                    $query_string = str_replace($y.$parm, '', $query_string);
                }
                else $y = '&';
            }
            if($query_string) {
                $url .= '?'.$query_string;
                $a = '&';
            }
            else $a = '?';    
        }
        else $a = '?';
    }
    else {
        $a = '?';
        if(strpos($url, '?')) $a = '&';    
    }
    
    if(!$format || $format > 2 || $format < 0 || !is_numeric($format)) {    
        if($total <= 8) $format = 1;
        else $format = 2;
    }
    
    
    if($current > $total) $current = $total;
        $pagenav = "";

    if($format == 2) {
        $first_disabled = $prev_disabled = $next_disabled = $last_disabled = '';
        if($current == 1)
            $first_disabled = $prev_disabled = ' disabled';
        if($current == $total)
            $next_disabled = $last_disabled = ' disabled';

        $pagenav .= "<a class=\"first-page{$first_disabled}\" title=\"".__('Go to the first page', 'oligodb')."\" href=\"{$url}\">&laquo;</a>&nbsp;&nbsp;";

        $pagenav .= "<a class=\"prev-page{$prev_disabled}\" title=\"".__('Go to the previous page', 'oligodb')."\" href=\"{$url}{$a}{$paged}=".($current - 1)."\">&#139;</a>&nbsp;&nbsp;";

        $pagenav .= '<span class="paging-input">'.$current.' of <span class="total-pages">'.$total.'</span></span>';

        $pagenav .= "&nbsp;&nbsp;<a class=\"next-page{$next_disabled}\" title=\"".__('Go to the next page', 'oligodb')."\" href=\"{$url}{$a}{$paged}=".($current + 1)."\">&#155;</a>";

        $pagenav .= "&nbsp;&nbsp;<a class=\"last-page{$last_disabled}\" title=\"".__('Go to the last page', 'oligodb')."\" href=\"{$url}{$a}{$paged}={$total}\">&raquo;</a>";
    
    }
    else {
        $pagenav = __("Goto page:", 'oligodb');
        for( $i = 1; $i <= $total; $i++ ) {
            if($i == $current)
                $pagenav .= "&nbsp;<strong>{$i}</strong>";
            else if($i == 1)
                $pagenav .= "&nbsp;<a href=\"{$url}\">{$i}</a>";
            else 
                $pagenav .= "&nbsp;<a href=\"{$url}{$a}{$paged}={$i}\">{$i}</a>";
        }
    }
    return $pagenav;
}

function oligodb_txtfmt($oligodata = array())
{
    if(!$oligodata)
        return;

    foreach($oligodata as $key => $value){
        $value = make_clickable($value); 
        $value = wptexturize(str_replace(array("\r\n", "\r", "\n"), '', nl2br(trim($value))));
        $oligodata[$key] = $value;
    }
    
    return $oligodata;
}

function oligodb_row_format( $oligo_data, $clickable=true )
#TODO
{

    $oligo_data = oligodb_txtfmt($oligo_data);
    $get_url = $_SERVER['REQUEST_URI'];
    
    $href_tags = $oligo_data['tags'];
    $owner_name = get_userdata($oligo_data['owner'])->display_name;
    
    if ($clickable) {

        $href_tags = "";

        $tags = html_entity_decode($oligo_data['tags']);
        $taglist = explode(',', $tags);
        foreach($taglist as $tag) {
            $tag = trim($tag);
            $url = $get_url."?tags=".$tag;
            $href_tags .= "<a href=\"".$url."\">".$tag."</a> ";
        }

        $url = $get_url."?owner=".$oligo_data['owner'];
        $owner_name = "<a href=\"".$url."\">".$owner_name."</a>";
        
    }
    

    $oligo_actions = "<div class='link_hover_class'><a href=\"javascript:void(0)\" class=\"edit\" onclick=\"viewOligo('".$oligo_data['oligo_name']."')\" >".__('View/Edit', 'oligodb')."</a><span class='link_hover_class'> | </span><a href=\"javascript:void(0)\" class=\"copy\" onclick=\"copyToClipboard('#oligo-".$oligo_data['oligo_name']."')\">".__('Copy Sequence', 'oligodb')."</a></div>";
    $display = "<tr><td style=\"width:70px;\">".$oligo_data['oligo_name']."</td><td><span id=\"oligo-".$oligo_data['oligo_name']."\">".$oligo_data['oligo_seq']."</span>".$oligo_actions."</td><td>".$oligo_data['oligo_len']."</td><td>".$oligo_data['oligo_tm']."</td><td>".$href_tags."</td><td>".$oligo_data['template']."</td><td>".$oligo_data['comments']."</td><td>".$owner_name."</td></tr>";
    
    return apply_filters( 'oligodb_output_format', $display );
}


function oligodb_oligo($args = '') 
{
    global $oligodb_instances, $oligodb_next_oligo;
    if(!$oligodb_next_oligo) $oligodb_next_oligo = __('Next oligo', 'oligodb')."&nbsp;&raquo;";
    if(!($instance = $oligodb_instances))
        $instance = $oligodb_instances = 0;
    
    $key_value = explode('&', $args);
    $options = array();
    foreach($key_value as $value) {
        $x = explode('=', $value);
        $options[$x[0]] = $x[1]; // $options['key'] = 'value';
    }
    
    $options_default = array(
        'show_author' => 1,
        'ajax_refresh' => 1,
        'auto_refresh' => 0,
        'tags' => '',
        'char_limit' => 500,
        'echo' => 1,
        'random' => 1,
        'exclude' => '',
        'current' => 0
    );
    
    $options = array_merge($options_default, $options);
    
    $condition = " WHERE 1 = 1";
    
    if($options['random'])
        $current = 0;
    else $current = $options['current'];
    
    if($options['char_limit'] && is_numeric($options['char_limit']))
        $condition .= " AND CHAR_LENGTH(oligo) <= ".$options['char_limit'];
    
    else $options['char_limit'] = 0;
    
    if($options['exclude'])
        $condition .=" AND oligo_id <> ".$options['exclude'];
        
    if($options['tags']) {
        $taglist = explode(',', $options['tags']);
        $tag_condition = "";
        foreach($taglist as $tag) {
            $tag = mysql_real_escape_string(strip_tags(trim($tag)));
            if($tag_condition) $tag_condition .= " OR ";
            $tag_condition .= "tags = '{$tag}' OR tags LIKE '{$tag},%' OR tags LIKE '%,{$tag},%' OR tags LIKE '%,{$tag}'";
        }
        $condition .= " AND ({$tag_condition})";
    }
                
    // We don't want to display the 'next oligo' link if there is no more than 1 oligo
    $oligos_count = oligodb_count($condition); 
    
    if($options['ajax_refresh'] == 1 && $oligos_count > 1) {
        if($options['auto_refresh'])
            $display .= "<script type=\"text/javascript\">oligodb_timer(".$instance.", ".$random_oligo["oligo_id"].", ". $options['show_author'] .", ".$options['show_source'].", '".$options['tags']."', ".$options['char_limit'].", ".$options['auto_refresh'].", ".$options['random'].");</script>";
        else {        
            $display .= "<script type=\"text/javascript\">\n<!--\ndocument.write(\"";
            $display .= '<p class=\"oligodb_nextoligo\" id=\"oligodb_nextoligo-'.$instance.'\"><a class=\"oligodb_refresh\" style=\"cursor:pointer\" onclick=\"oligodb_refresh('.$instance.', '.$random_oligo["oligo_id"].', '. $options['show_author'] .', '.$options['show_source'].', \''.$options['tags'].'\', '.$options['char_limit'].', 0, '.$options['random'].');\">'.$oligodb_next_oligo.'<\/a><\/p>';
            $display .= "\")\n//-->\n</script>\n";
        }
    }
    else if ($options['ajax_refresh'] == 2 && $oligos_count) {
        if($options['auto_refresh'])
            $display .= "<script type=\"text/javascript\">oligodb_timer(".$instance.", ".$random_oligo["oligo_id"].", ". $options['show_author'] .", ".$options['show_source'].", '".$options['tags']."', ".$options['char_limit'].", ".$options['auto_refresh'].", ".$options['random'].");</script>";
        else
            $display .= "<p class=\"oligodb_nextoligo\" id=\"oligodb_nextoligo-".$_REQUEST['refresh']."\"><a class=\"oligodb_refresh\" style=\"cursor:pointer\" onclick=\"oligodb_refresh(".$_REQUEST['refresh'].", ".$random_oligo['oligo_id'].', '. $options['show_author'] .', '.$options['show_source'].', \''.$options['tags'].'\', '.$options['char_limit'].", 0, ".$options['random'].");\">".$oligodb_next_oligo."</a></p>";
        return $display;
    }
    $display = "<div id=\"oligodb_randomoligo-".$instance."\" class=\"oligodb_randomoligo\">{$display}</div>";
    $oligodb_instances++;
    if($options['echo'])
        echo $display;
    else
        return $display;
}


function oligodb_uninstall() {

    global $wpdb;
    $table = $wpdb->prefix."oligodb";

    //Delete any options thats stored also?
    //delete_option('$oligodb_version');

    $wpdb->query("DROP TABLE IF EXISTS $table");
}

function oligodb_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "oligodb";

    if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
        $db_charset = 'utf8';
    $db_charset = "CHARACTER SET ".$db_charset;
    if(defined('DB_COLLATE') && $db_collate = DB_COLLATE) 
        $db_collate = "COLLATE ".$db_collate;

    $sql = "CREATE TABLE " . $table_name . " (
        oligo_id mediumint(9) NOT NULL AUTO_INCREMENT,
        oligo_name VARCHAR(255),
        oligo_seq TEXT NOT NULL,
        oligo_len MEDIUMINT,
        oligo_tm VARCHAR(255),
        owner TINYINT,
        tags TEXT,
        template VARCHAR(255),
        comments TEXT,
        ordered BOOLEAN,
        time_added datetime NOT NULL,
        time_updated datetime,
        PRIMARY KEY  (oligo_id)
    ) {$db_charset} {$db_collate};";
    $results = $wpdb->query( $sql );
    
    
    global $oligodb_db_version;
    $options = get_option('oligodb');
    $options['db_version'] = $oligodb_db_version;
    update_option('oligodb', $options);

}

function oligodb_css_head()
{
    global $oligodb_version;
    wp_register_style( 'oligodb-style', plugins_url('styles/oligodb.css', __FILE__), false, $oligodb_version );
    wp_enqueue_style( 'oligodb-style' );

    #remote
    wp_enqueue_style( 'pure-style', 'http://yui.yahooapis.com/pure/0.6.0/pure-min.css' ); # For forms
    wp_enqueue_style( 'tabmenu-style', plugins_url('styles/tabmenu.css', __FILE__) ); # For tabmenu
    #wp_enqueue_script('jquery-ui', 'http://code.jquery.com/ui/1.11.4/jquery-ui.js', array( 'jquery' ) );
}


function add_jquery_ui() {
    wp_enqueue_script('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js', false, '1.11.4');
}
add_action( 'init', 'add_jquery_ui' );

add_action( 'wp_enqueue_scripts', 'oligodb_css_head' );

#ADMIN actions
add_action('admin_menu', 'oligodb_admin_menu');
add_action('admin_footer', 'oligodb_admin_footer');

register_activation_hook( __FILE__, 'oligodb_install' );
?>
