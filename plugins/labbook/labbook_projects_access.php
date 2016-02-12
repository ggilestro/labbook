<?php
/*
 * labbook_projects.php
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

if (!class_exists('labbook_projects')){
    
    class labbook_projects {
     
        /**
        * PHP 4 Compatible Constructor
        */
        function labbook_projects() {$this->__construct();}
        
        /**
        * PHP 5 Constructor
        */        
        function __construct() {

            $this->hooks();

            if (is_admin()){
                $this->adminHooks();                
            }

        }
     
     
        public function hooks(){
            
            // save user field
            add_action( 'personal_options_update', array( $this,'save_extra_user_profile_fields' ));
            add_action( 'edit_user_profile_update', array( $this,'save_extra_user_profile_fields' ));
            // add user field
            add_action( 'show_user_profile', array( $this,'extra_user_profile_fields' ));
            add_action( 'edit_user_profile', array( $this,'extra_user_profile_fields' ));

            //xmlrpc post insert hook and quickpress
            add_filter('xmlrpc_wp_insert_post_data', array($this, 'user_default_category'),2);
            add_filter('pre_option_default_category',array($this, 'user_default_category_option'));

            //post by email cat
            add_filter( 'publish_phone',array($this,'post_by_email_cat'));
        }


        /**
         * hooks add all action and filter hooks for admin side
         * 
         * @since 0.7
         * @return void
         */
        public function adminHooks(){
            //remove quick and bulk edit
            global $pagenow;
            if ('edit.php' == $pagenow)
                add_action('admin_print_styles',array(&$this,'remove_quick_edit'));

            //add metabox 
            add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
            //plugin links row
            #add_filter( 'plugin_row_meta', array($this,'_my_plugin_links'), 10, 2 );

            #//add admin panel
            #if (!class_exists('SimplePanel')){
            #    require_once(plugin_dir_path(__FILE__).'inc/Simple_Panel_class.php');
            #    require_once(plugin_dir_path(__FILE__).'inc/author_category_Panel_class.php');
            #}
        }

        /**
         * This will generate the category field on the users profile
         * @author Ohad   Raz
         * @since 0.1
         * @param  (object) $user 
         * @return void
         */
        public function extra_user_profile_fields( $user ){ 
            //only admin can see and save the categories
            if ( !current_user_can( 'manage_options' ) ) { return false; }
            global $current_user;
            get_currentuserinfo();
            #if ($current_user->ID == $user->ID) { return false; } # this is to avoid auto editing
            $select = wp_dropdown_categories(array(
                            'orderby'      => 'name',
                            'show_count'   => 0,
                            'hierarchical' => 1,
                            'hide_empty'   => 0,
                            'echo'         => 0,
                            'name'         => 'author_cat[]'));
            $saved = get_user_meta($user->ID, '_author_cat', true ); # return an array with selected cat
            foreach((array)$saved as $c){
                $select = str_replace('value="'.$c.'"','value="'.$c.'" selected="selected"',$select);
            }
            $select = str_replace('<select','<select multiple="multiple"',$select);
            echo '<h3>'.__('Author Category', 'author_cat').'</h3>
            <table class="form-table">
                <tr>
                    <th><label for="author_cat">'.__('Category',$this->txtDomain).'</label></th>
                    <td>
                        '.$select.'
                        <br />
                    <span class="description">'.__('select a category to limit an author to post just in that category (use Crtl to select more then one).',$this->txtDomain).'</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="author_cat_clear">'.__('Clear Category',$this->txtDomain).'</label></th>
                    <td>
                        <input type="checkbox" name="author_cat_clear" value="1" />
                        <br />
                    <span class="description">'.__('Check if you want to clear the limitation for this user.',$this->txtDomain).'</span>
                    </td>
                </tr>
            </table>';
        }


        /**
         * This will save category field on the users profile
         * @author Ohad   Raz
         * @since 0.1
         * @param  (int) $user_id 
         * @return VOID
         */
        public function save_extra_user_profile_fields( $user_id ) {
            //only admin can see and save the categories
            if ( !current_user_can( 'manage_options') ) { return false; }

            
            $cats = (array)$_POST['author_cat'];
            $cats = array_merge($cats, $this->get_child_projects($cats) );

            update_user_meta( $user_id, '_author_cat', $cats );
                                                                                
            if (isset($_POST['author_cat_clear']) && $_POST['author_cat_clear'] == 1)
                delete_user_meta( $user_id, '_author_cat' );
        }

        /**
         * user_default_category
         * 
         * function to handle XMLRPC calls
         * 
         * @author Ohad   Raz
         * @since 0.3
         * 
         * @param  array $post_data  post data
         * @param  array $con_stactu xmlrpc post data
         * @return array 
         */
        public function user_default_category($post_data,$con_stactu){
            $cat = $this->get_user_cat($post_data['post_author']);
            if (!empty($cat) && $cat > 0){
                $post_data['tax_input']['category'] = array($cat);
            }
            return $post_data;
        }
     
         /**
         * user_default_category_option
         * 
         * function to overwrite the defult category option per user
         * 
         * @author Ohad   Raz
         * @since 0.3
         * 
         * @param  boolea $false 
         * @return mixed category id if user as a category set and false if he doesn't
         */
        public function user_default_category_option($false){
            $cat = $this->get_user_cat();
            if (!empty($cat) && count($cat) > 0){
                return $cat;
            }
            return false;
        }



        /**
         * post_by_email_cat 
         * 
         * @author Ohad   Raz
         * @since 0.5
         * 
         * @param  int $post_id 
         * @return void
         */
        public function post_by_email_cat($post_id){
            $p = get_post($post_id);
            $cat = $this->get_user_cat($p['post_author']);
            if ($cat){
                $email_post = array();
                $email_post['ID'] = $post_id;
                $email_post['post_category'] = array($cat);
                wp_update_post($email_post);
            }           
        }

        /**
         * remove_quick_edit
         * @author Ohad   Raz
         * @since 0.1
         * @return void
         */
        public function remove_quick_edit(){
           global $current_user;
            get_currentuserinfo();
            $cat = $this->get_user_cat($current_user->ID);
            if (!empty($cat) && count($cat) > 0){
                echo '<style>.inline-edit-categories{display: none !important;}</style>';
            }
        }

        
        public function add_meta_box(){

            global $current_user;
            get_currentuserinfo();

            //get author categories
            $cat = $this->get_user_cat($current_user->ID);
            if (!empty($cat) && count($cat) > 0){
                //remove default metabox
                remove_meta_box('categorydiv', 'post', 'side');
                //add user specific categories
                add_meta_box( 
                     'author_cat'
                    ,__( 'Author category',$this->txtDomain )
                    ,array( &$this, 'render_meta_box_content' )
                    ,'post' 
                    ,'side'
                    ,'low'
                );
            }
        }

        public function get_child_projects($cat_array) {
            $children = array();
            
            foreach($cat_array as $sel_cat) { # for each of selected categories
                $cc = get_categories(array('child_of' => $sel_cat));
                foreach($cc as $c) { array_push($children, $c->cat_ID); }
            }
            return $children;
        }

        
        /**
         * Render Meta Box content
         * @author Ohad   Raz
         * @since 0.1
         * @return Void
         */
        public function render_meta_box_content(){
            global $current_user;
            get_currentuserinfo();
            $cats = get_user_meta($current_user->ID,'_author_cat',true);
            $children_cats = $this->get_child_projects($cats);
            
            $cats = array_merge($cats, $children_cats);
            $cats = (array)$cats;

            // Use nonce for verification
            wp_nonce_field( plugin_basename( __FILE__ ), 'author_cat_noncename' );
            if (!empty($cats) && count($cats) > 0){
                if (count($cats) == 1){
                    $c = get_category($cats[0]);
                    echo __('this will be posted in: <strong>',$this->txtDomain) . $c->name .__('</strong> Category',$this->txtDomain);
                    echo '<input name="post_category[]" type="hidden" value="'.$c->term_id.'">';
                }else{
                    echo '<span style="color: #f00;">'.__('Make Sure you select only the categories you want: <strong>',$this->txtDomain).'</span><br />';
                    $options = get_option('author_cat_option');
                    $checked =  (!isset($options['check_multi']))? ' checked="checked"' : '';

                    foreach($cats as $cat ){
                        $c = get_category($cat);
                        echo '<label><input name="post_category[]" type="checkbox"'.$checked.' value="'.$c->term_id.'"> '.$c->name .'</label><br />';
                    }
                }
            }
            do_action('in_author_category_metabox',$current_user->ID);
        }

        /**
         * save category on post 
         * @author Ohad   Raz
         * @since 0.1
         * @deprecated 0.3
         * @param  (int) $post_id 
         * @return Void
         */
        public function author_cat_save_meta( $post_id ) {
        }

        public function get_user_cat($user_id = null){
            if ($user_id === null){
                global $current_user;
                get_currentuserinfo();
                $user_id = $current_user->ID;
            }
            $cat = get_user_meta($user_id,'_author_cat',true);
            if (empty($cat) || count($cat) <= 0 || !is_array($cat))
                return 0;
            else
                return $cat[0];

        }
       
        
       public function get_user_projects($user_ID) {
            $cats = get_user_meta($user_ID,'_author_cat',true);
            $children_cats = $this->get_child_projects($cats);
            return array_merge($cats, $children_cats);
    
       }
    
    }
}


//initiate the class on admin pages only
if (is_admin()){
    $ac = new labbook_projects();
}





?>
