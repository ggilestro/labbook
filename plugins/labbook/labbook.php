<?php
/*
Plugin Name: LabBook
Plugin URI: http://lab.gilest.ro
Description: A collection of functions used for gglabbook
Version: 0.1
Author: Giorgio Gilestro
Author URI: http://www.gilest.ro
*/

/*
labbook plugin (Wordpress Plugin)
Copyright (C) 2015 Giorgio Gilestro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

define('labbook_VERSION', '0.2');
define('labbook_PLUGIN_URL', plugin_dir_url( __FILE__ ));


if ( !class_exists( 'ListAuthorsWidget' ) ) {
    include_once( 'widgets/labbook_list_authors_widget.php' );
}

if ( !class_exists( 'WP_Widget_List_Categories' ) ) {
    include_once( 'widgets/labbook_list_projects_widget.php' );
}
 
if ( !class_exists( 'DateTitle' ) ) {
    include_once( 'labbook_datetitle.php' );
}

if ( !class_exists( 'labbook_projects' ) ) {
    include_once( 'labbook_projects_access.php' );
}


if (!class_exists('labbook_users')) {

    class labbook_users {

        /**
        * PHP 4 Compatible Constructor
        */
        function labbook_users() {$this->__construct();}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct() {

            if ( !get_role ('alumnus') or !get_role ('lab_member') or !get_role ('pi') ) {
            
                $this->create_custom_roles();
            }

        }

        function get_lab_members($user_role, $display_style) {
            
            if ( $user_role == "lab_member" ) {
            
                $members_query = new WP_User_Query(array( 'role' => 'lab_member' ));
                $members = $members_query->results;

                $pi = new WP_User_Query(array( 'role' => 'pi' ));
                $pi = $pi->results;
                
                $lab_members = array_merge($members, $pi);
            }

            if ( $user_role == "alumnus" ) {
                $user_query = new WP_User_Query(array( 'role' => 'alumnus' ));
                $lab_members = $user_query->results;
            }

            $result = "";
            
            if ( $display_style == "list") {  $result .= '<ul>'; }
            if ( $display_style == "dropdown") {  $result .= '<select id="users-dropdown" name="users-dropdown" onchange=\'document.location.href=this.options[this.selectedIndex].value;\'><option value="">Select lab member</option>'; }
            
            if ( ! empty( $lab_members ) ) {
                foreach ( $lab_members as $user ) {
                    if ( $display_style == "list") {
                        $result .= '<li><a href="' . get_author_posts_url($user->ID, $user->user_nicename) . '" title="' . esc_attr( sprintf(__("Posts by %s"), $user->display_name) ) . '">' . $user->display_name .' ('. count_user_posts( $user->ID ) .')' . '</a></li>';
                    } else if ( $display_style == "dropdown") {
                        $result .= '<option value="'. get_author_posts_url($user->ID, $user->user_nicename) .'">'.$user->display_name.'</option>';
                    } else if ( $display_style == "comma") {
                        $result .= '<a href="' . get_author_posts_url($user->ID, $user->user_nicename) . '" title="' . esc_attr( sprintf(__("Posts by %s"), $user->display_name) ) . '">' . $user->display_name .' ('. count_user_posts( $user->ID ) .')' . '</a>, ';
                    }
                }
            } else {
                $result .= 'No users found.';
            }

            if ( $display_style == "list") {  $result .= "</ul>"; }
            if ( $display_style == "dropdown") {  $result .= "</select>"; }

            return $result;
        }

        function get_alumni($display_style){
            return get_lab_members("alumnus", $display_style);
        }
        
        function create_custom_roles() {
            // Add custom user roles
        
            if ( !get_role ('alumnus') )
            {
                $result = add_role( 'alumnus', __(
                 
                    'Alumnus' ),
                 
                    array(
                     
                    'read' => true, // true allows this capability
                    'edit_posts' => false, // Allows user to edit their own posts
                    'edit_pages' => false, // Allows user to edit pages
                    'edit_others_posts' => false, // Allows user to edit others posts not just their own
                    'create_posts' => false, // Allows user to create new posts
                    'manage_categories' => false, // Allows user to manage post categories
                    'publish_posts' => false, // Allows the user to publish, otherwise posts stays in draft mode
                    'edit_themes' => false, // false denies this capability. User can’t edit your theme
                    'install_plugins' => false, // User cant add new plugins
                    'update_plugin' => false, // User can’t update any plugins
                    'update_core' => false // user cant perform core updates
                        )
                    );
            }

            if ( !get_role ('lab_member') )
            {
                $result = add_role( 'lab_member', __(
                 
                    'Lab Member' ),
                 
                    array(
                     
                    'read' => true, // true allows this capability
                    'edit_posts' => true, // Allows user to edit their own posts
                    'edit_pages' => false, // Allows user to edit pages
                    'edit_others_posts' => false, // Allows user to edit others posts not just their own
                    'create_posts' => true, // Allows user to create new posts
                    'manage_categories' => true, // Allows user to manage post categories
                    'publish_posts' => true, // Allows the user to publish, otherwise posts stays in draft mode
                    'edit_themes' => false, // false denies this capability. User can’t edit your theme
                    'install_plugins' => false, // User cant add new plugins
                    'update_plugin' => false, // User can’t update any plugins
                    'update_core' => false // user cant perform core updates
                        )
                 
                    );
            }
            
            if ( !get_role ('pi') )
            {
                $result = add_role( 'pi', __(
                 
                    'PI' ),
                 
                    array(
                     
                    'read' => true, // true allows this capability
                    'edit_posts' => true, // Allows user to edit their own posts
                    'edit_pages' => true, // Allows user to edit pages
                    'edit_others_posts' => true, // Allows user to edit others posts not just their own
                    'create_posts' => true, // Allows user to create new posts
                    'manage_categories' => true, // Allows user to manage post categories
                    'publish_posts' => true, // Allows the user to publish, otherwise posts stays in draft mode
                    'edit_themes' => true, // false denies this capability. User can’t edit your theme
                    'install_plugins' => true, // User cant add new plugins
                    'update_plugin' => true, // User can’t update any plugins
                    'update_core' => true // user cant perform core updates
                        )
                 
                    );
            }
            
        }
    }
}

/*
UPDATE wp_term_taxonomy SET count = (
SELECT COUNT(*) FROM wp_term_relationships rel 
    LEFT JOIN wp_posts po ON (po.ID = rel.object_id) 
    WHERE 
        rel.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id 
        AND 
        wp_term_taxonomy.taxonomy NOT IN ('link_category')
        AND 
        po.post_status IN ('publish', 'future', 'private')
)
*/

//The following hook is used to count private posts too
function change_category_arg() {
    global $wp_taxonomies;
    if ( ! taxonomy_exists('category') )
        return false;

    $wp_taxonomies['category']->update_count_callback = '_update_generic_term_count';

}
add_action( 'init', 'change_category_arg' );

function labbook_styles_scripts() {
    wp_register_style( 'labbook-style', plugins_url( 'style.css', __FILE__ ) );
    wp_enqueue_style( 'labbook-style' );
    wp_enqueue_style( 'google-code-prettify', 'https://google-code-prettify.googlecode.com/svn/loader/run_prettify.js' );
    
    #wp_enqueue_script( 'labbook-script', get_template_directory_uri() . '/js/example.js', array(), '1.0.0', true );
}

add_action( 'wp_enqueue_scripts', 'labbook_styles_scripts' );

//Allow MIME files to be uploaded
add_filter('upload_mimes', 'labbook_upload_types');
function labbook_upload_types($existing_mimes=array()){
$existing_mimes['gb'] = 'text/genbank';
$existing_mimes['fas'] = 'text/fasta';
return $existing_mimes;
}

