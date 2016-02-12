<?php
/*
 * labbook_datetitle.php
 * 
 * Copyright 2015 Giorgio Gilestro <gg@turing>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */

/*
DATETITLE 
CODE taken from http://www.sanisoft.com/blog/2009/06/30/datetitle-a-wordpress-plugin-for-automatic-post-titles/

F j, Y g:i a – November 6, 2010 12:50 am
F j, Y – November 6, 2010
F, Y – November, 2010
g:i a – 12:50 am
g:i:s a – 12:50:48 am
l, F jS, Y – Saturday, November 6th, 2010
M j, Y @ G:i – Nov 6, 2010 @ 0:50
Y/m/d \a\t g:i A – 2010/11/06 at 12:50 AM
Y/m/d \a\t g:ia – 2010/11/06 at 12:50am
Y/m/d g:i:s A – 2010/11/06 12:50:48 AM
Y/m/d – 2010/11/06
*/

if (!class_exists('DateTitle')) {
    
    class DateTitle {
        
        /**
        * PHP 4 Compatible Constructor
        */
        function DateTitle() {$this->__construct();}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct() {
            add_action('admin_menu', array(&$this,'add_admin_pages'));
            add_action('publish_post', array(&$this,'wp_title_intercept'), 10, 2);

            load_textdomain('DateTitle', dirname(__FILE__) . '/languages/DateTitle-.'. get_locale() .'.mo');
        }
        
        /**
        * Retrieves the options from the database.  Initialize with the defaults if the
        * option isn't set
        *
        * @return array An array of the options.
        */
        function getOptions() {
            $defaults = array('title' => 'l - jS F Y @ h:i:s A'); //Keeping an array for future enhancements
            $options = array();

            // Not needed right now but adding more options later will be easy :-)
            foreach ($defaults as $key=>$value) {
                $options[$key] = get_option( "DateTitle_$key" );

                if ( empty($options[$key]) ) {
                    add_option( "DateTitle_$key", $value);
                    $options[$key] = $value;
                }
            }

            return $options;
        }

        /**
        * Registers the options page.
        */        
        function add_admin_pages() {
            add_submenu_page('options-general.php', 'DateTitle', 'DateTitle', 'manage_options', 'DateTitle', array(&$this,'output_sub_admin_page_0'));
        }

        /**
        * Outputs the HTML for the admin sub page.
        */
        function output_sub_admin_page_0() {
            $options = $this->getOptions();
            ?>
            <div class="wrap">
                <h2><?php _e('DateTitle Options', 'DateTitle'); ?></h2>
                <form method="post" action="options.php">
                <?php wp_nonce_field('options-options'); ?>
                <input type="hidden" name="action" value="update" />
                <input type='hidden' name='option_page' value='options' />
                <input type="hidden" name="page_options" value="DateTitle_title" />

                <p><?php _e("Enter the date format for the title you'd like to automatically use. See <a href='http://in.php.net/manual/en/function.date.php' >this page</a> for more options on formatting the date", 'DateTitle'); ?><br/>
                <p><?php _e("The default option shown below outputs something similar to <b>Thursday – 25th June 2009 @ 06:27:33 PM</b>", 'DateTitle'); ?><br/>

                <input name="DateTitle_title" type="text" id="DateTitle_title" value="<?php echo attribute_escape($options['title']); ?>" class="regular-text code" /></p>
                <p class="submit"><input type="submit" name="Update" value="<?php _e('Save Changes') ?>" class="button-primary" /></p>
                </form>
            </div>
            <?php
        } 

        /**
        * If a title of a post is empty when it is published, sets our lazy title and,
        * if needed, updates the slug.
        *
        * @param int $postID ID number of the post to operate on.
        * @param object $post The WP Post object to operate on.
        */
        function wp_title_intercept($postID, $post) {
            $options = $this->getOptions();
            
            if ($post->post_title === '') {
                $post->post_title = date($options['title']);

                if ($post->post_name == $postID) {
                    $post->post_name = sanitize_title($post->post_title);
                }

                $result = wp_update_post($post);

            }
        }
    }
}

//instantiate the class
if (class_exists('DateTitle')) {
    $DateTitle = new DateTitle();
}

?>
