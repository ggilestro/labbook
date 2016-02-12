<?php

function oligodb_admin_menu() 
{
    global $oligodb_admin_userlevel;
    add_object_page('oligodb', 'oligos', $oligodb_admin_userlevel, 'oligodb', 'oligodb_oligos_management');
}

function oligodb_addoligo($oligo_seq, $owner_id = 1, $template = "", $tags = "", $comments = "", $oligo_name="", $ordered=0)
{
    if(!$oligo_seq) return __('Nothing added to the database.', 'oligodb');
    
    global $wpdb;
    $table_name = $wpdb->prefix . "oligodb";
    
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
        return __('Database table not found', 'oligodb');
    else //Add the oligo data to the database
    {
        global $allowedposttags;
        if (!($oligo_name))
            $oligo_name = oligodb_getnewID();

        $oligo_seq = wp_kses( stripslashes($oligo_seq), $allowedposttags );
        $tags = strip_tags( stripslashes($tags) );
        
        $oligo_tm = oligodb_calculate_tm($oligo_seq);
        $oligo_len = oligodb_calculate_len($oligo_seq);
        
        $tags = explode(',', $tags);
        foreach ($tags as $key => $tag)
            $tags[$key] = trim($tag);
        $tags = implode(',', $tags);

        $insert = $wpdb->prepare (
            "INSERT INTO " . $table_name . "(oligo_name, oligo_seq, oligo_len, oligo_tm, owner, tags, template, comments, time_added, ordered) VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW() )" ,
            array( $oligo_name, $oligo_seq, $oligo_len, $oligo_tm, $owner_id, $tags, $template, $comments, $ordered ) 
            );
            
        $results = $wpdb->query( $insert );
        
        if(FALSE === $results)
            return __('There was an error in the MySQL query', 'oligodb');
        else
            return __('oligo added', 'oligodb');
   }
}

function oligodb_editoligo($oligo_id, $oligo_name, $oligo_seq, $owner_id = 1, $template = "", $tags = "", $comments = "")
{
    if(!$oligo_id) return oligodb_addoligo($oligo_seq, $owner_id, $template, $tags, $comments);
    
    global $wpdb;
    $table_name = $wpdb->prefix . "oligodb";
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) 
        return __('Database table not found', 'oligodb');
    else 
    {
        global $allowedposttags;
        $oligo_seq = wp_kses( stripslashes($oligo_seq), $allowedposttags );
        $tags = strip_tags( stripslashes($tags) );

        $tags = explode(',', $tags);
        foreach ($tags as $key => $tag)
            $tags[$key] = trim($tag);
        $tags = implode(',', $tags);
        
        $oligo_tm = oligodb_calculate_tm($oligo_seq);
        $oligo_len = oligodb_calculate_len($oligo_seq);

        $update = $wpdb->prepare (
            "UPDATE " . $table_name . " SET oligo_seq=%s, oligo_len=%s, oligo_tm=%s, owner=%s, tags=%s, template=%s, comments=%s, time_updated=NOW() WHERE oligo_id = %s ", 
            array( $oligo_seq, $oligo_len, $oligo_tm, $owner_id, $tags, $template, $comments, $oligo_id ) 
            );

        $results = $wpdb->query( $update );
        if(FALSE === $results)
            return __('There was an error in the MySQL query', 'oligodb');        
        else
            return __('Changes saved', 'oligodb');
   }
}


function oligodb_deleteoligo($oligo_id)
{
    if($oligo_id) {
        global $wpdb;
        $sql = "DELETE from " . $wpdb->prefix ."oligodb" .
            " WHERE oligo_id = " . $oligo_id;
        if(FALSE === $wpdb->query($sql))
            return __('There was an error in the MySQL query', 'oligodb');        
        else
            return __('oligo deleted', 'oligodb');
    }
    else return __('The oligo cannot be deleted', 'oligodb');
}

function oligodb_getoligodata($oligo_id)
{
    global $wpdb;
    $sql = "SELECT oligo_name, oligo_id, oligo_seq, oligo_tm, oligo_len, owner, tags, template, comments, ordered, time_added
        FROM " . $wpdb->prefix . "oligodb 
        WHERE oligo_id = {$oligo_id}";
    $oligo_data = $wpdb->get_row($sql, ARRAY_A);    
    return stripslashes_deep($oligo_data);
}

function mark_as_ordered($oligo_id = ""){
    global $wpdb;
    $table_name = $wpdb->prefix . "oligodb";
    
    $conditions = " WHERE 1=1";
    if ($oligo_id)
        $conditions .= " AND oligo_id = '".$oligo_id."'";

    $update = "UPDATE " . $table_name . " SET ordered = 1, time_updated=NOW() ".$conditions." AND ordered = 127";

    $results = $wpdb->query( $update );
    if(FALSE === $results)
        return __('There was an error in the MySQL query', 'oligodb');
    else
        return __('Oligos marked as ordered', 'oligodb');
    
}


function oligodb_upload_CSV($movefile) {
    global $wpdb;
    $table_name = $wpdb->prefix . "oligodb";
    $values = array();

    if (!file_exists($movefile['file']) || !is_readable($movefile['file'])) {
        return __('File is not readable', 'oligodb');
    }
    
    $header = null;
    $data   = array();
    
    if (($handle = fopen($movefile['file'], "r")) !== false) {

        
        
        while (($csv_array = fgetcsv($handle, 1000, ',', '"')) !== FALSE) {
            
            $oligo_name = $csv_array[0];
            $oligo_seq = $csv_array[1];
            $owner = $csv_array[2];
            $tags = $csv_array[3];
            $template = $csv_array[4];
            $comments = $csv_array[5];
            $oligo_len = oligodb_calculate_len($oligo_seq);
            $oligo_tm = oligodb_calculate_tm($oligo_seq);

            $values[] = $wpdb->prepare ( "( %s, %s, %s, %s, %s, %s, %s, %s, NOW(), 1 )", array($oligo_name, $oligo_seq, $oligo_len, $oligo_tm, $owner, $tags, $template, $comments) );
            
            }
        
        $insert = "INSERT INTO " . $table_name . "(oligo_name, oligo_seq, oligo_len, oligo_tm, owner, tags, template, comments, time_added, ordered) VALUES ";
        $insert .= implode( ",\n", $values );
        $results = $wpdb->query( $insert );
        
        print_r($insert);
        fclose($handle);

    } else {
        return __('Cannot open the file', 'oligodb');
    }
    
    if(FALSE === $results)
        return __('There was an error in the MySQL query', 'oligodb');
    else
        return __('Contents of CSV file were succesfully added to the database', 'oligodb');
}


function oligodb_upload_CSV_Form() {
    $submit_value = __('Upload CSV', 'oligodb');
    $form_name = "upload";
    $action_url = get_bloginfo('wpurl')."/wp-admin/admin.php?page=oligodb#upload";
    
    $display = <<< EDITFORM
<form name="{$form_name}" enctype="multipart/form-data" method="post" action="{$action_url}  ">
  <input type="file" accept=".csv" name="CSVfile" />
  <input type="submit" name="submit" value="{$submit_value}" class="button button-primary" />
</form>
<p>The CSV file should have the following structure: no headings, one line per entry, 
columns in the following order: oligo name, sequence, owner ID, tags, template, comments </p>
EDITFORM;

    return $display;
}

function oligodb_editform($oligo_id = 0)
{
    $submit_value = __('Add oligo', 'oligodb');
    $form_name = "addoligo";
    $action_url = get_bloginfo('wpurl')."/wp-admin/admin.php?page=oligodb#addnew";
    $oligo = $owner = $source = $tags = $hidden_input = $back = "";

    if($oligo_id) {
        $form_name = "editoligo";
        $oligo_data = oligodb_getoligodata($oligo_id);
        foreach($oligo_data as $key => $value)
            $oligo_data[$key] = $oligo_data[$key];
        extract($oligo_data);
        $oligo_seq = htmlspecialchars($oligo_seq);
        $owner = htmlspecialchars($owner);
        $tags = implode(', ', explode(',', $tags));
        $hidden_input = "<input type=\"hidden\" name=\"oligo_id\" value=\"{$oligo_id}\" />";
        $submit_value = __('Save changes', 'oligodb');
        $back = "<input type=\"submit\" name=\"submit\" value=\"".__('Back', 'oligodb')."\" />&nbsp;";
        $action_url = get_bloginfo('wpurl')."/wp-admin/admin.php?page=oligodb";
    }

    $oligo_label = __('The oligo', 'oligodb');
    $owner_label = __('Author', 'oligodb');
    $source_label = __('Source', 'oligodb');
    $tags_label = __('Tags', 'oligodb');
    $optional_text = __('optional', 'oligodb');
    $comma_separated_text = __('comma separated', 'oligodb');
    
    $newID = oligodb_getnewID("pGG");
    $owner_id = get_current_user_id();
    
    $display = <<< EDITFORM
<form name="{$form_name}" method="post" action="{$action_url}">
    {$hidden_input}
    <table class="addoligo-table" cellpadding="2" cellspacing="4" border=0>
        <tbody>
        <tr>
        <th>ID</th>
        <th>Oligo sequence</th>
        <th>Len</th>
        <th>TM</th>
        <th>Tags (comma separated)</th>
        <th>Template</th>
        <th>Comments</th>
        </tr>
        <tr>
        <td>{$newID}</td>
        <td><input type="text" id="oligo_seq" name="oligo_seq" value="${oligo_seq}" size="40" onkeyup="calculate_properties(this)" ONBLUR="calculate_properties(this)"></td>
        <td><input type="text" id="oligo_len" name="oligo_len" size="3" value="${oligo_len}"></td>
        <td><input type="text" id="oligo_tm" name="oligo_tm" size="3" value="${oligo_tm}"></td>
        <td><input type="text" id="oligodb_tags" name="tags" size="40" value="{$tags}" /></td>
        <td><input type="text" id="oligodb_template" name="template" size="40" value="{$template}" /></td>
        <td><input type="text" id="oligodb_comments" name="comments" size="60" value="{$comments}" /></td>
        </tr>
        </tbody>
    </table>
        <input type="hidden" name="owner" value="{$owner_id}" />

    <p class="submit">{$back}<input name="submit" value="{$submit_value}" type="submit" class="button button-primary" /></p>
</form>
EDITFORM;

    return $display;
}

function oligodb_bulkdelete($oligo_ids)
{
    if(!$oligo_ids)
        return __('Nothing done!', 'oligodb');
    global $wpdb;
    $sql = "DELETE FROM ".$wpdb->prefix."oligodb 
        WHERE oligo_id IN (".implode(', ', $oligo_ids).")";
    $wpdb->query($sql);
    return __('oligo(s) deleted', 'oligodb');
}



function oligodb_oligos_management() {

    global $oligodb_db_version;
    global $wpdb;
    $tablename = $wpdb->prefix . "oligodb";
    
    $options = get_option('oligodb');
    $display = $msg = $oligos_list = $alternate = "";
    
    if($options['db_version'] != $oligodb_db_version )
        oligodb_install();

    if(isset($_REQUEST['submit'])) {
        if($_REQUEST['submit'] == __('Add oligo', 'oligodb')) {
            extract($_REQUEST);
            $msg = oligodb_addoligo($oligo_seq, $owner, $template, $tags, $comments);
        }
        else if($_REQUEST['submit'] == __('Save changes', 'oligodb')) {
            extract($_REQUEST);
            $msg = oligodb_editoligo($oligo_id, $oligo_name, $oligo_seq, $owner, $template, $tags, $comments);
        }
        else if($_REQUEST['submit'] == __('Upload CSV', 'oligodb')) {
            if ($_FILES) {
                // Get the type of the uploaded file. This is returned as "type/extension"
                $arr_file_type      = wp_check_filetype($_FILES['CSVfile']['tmp_name']);
                $uploaded_file_type = $arr_file_type['type'];
                // Set an array containing a list of acceptable formats
                $csv_file_types = array('text/csv', 'text/plain', 'application/csv');
                if (!function_exists('wp_handle_upload'))
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
        
                $uploadedfile = $_FILES['CSVfile'];
                
                if ($_FILES['CSVfile']['type'] !== 'text/csv'){
                    $msg = 'ERROR, Only upload files in the CSV format! You uploaded: '.$_FILES['CSVfile']['type'];
                } else {
                    $upload_overrides = array('test_form' => false);
                    $movefile  = wp_handle_upload($uploadedfile, $upload_overrides);
                    
                    if ( $movefile && !isset( $movefile['error'] ) ) {
                        $msg = oligodb_upload_CSV($movefile);
                    } else {
                        /**
                         * Error generated by _wp_handle_upload()
                         * @see _wp_handle_upload() in wp-admin/includes/file.php
                         */
                        $msg = "Error: ". $movefile['error'];
                    }
                }
            }
        } //end else of file type check
    }
    else if(isset($_REQUEST['action'])) {
        if($_REQUEST['action'] == 'editoligo') {
            $display .= "<div class=\"wrap\">\n<h2>oligodb &raquo; ".__('Edit oligo', 'oligodb')."</h2>";
            $display .=  oligodb_editform($_REQUEST['id']);
            $display .= "</div>";
            echo $display;
            return;
        }
        else if($_REQUEST['action'] == 'deloligo') {
            $msg = oligodb_deleteoligo($_REQUEST['id']);
        }
    }
    else if(isset($_REQUEST['bulkactionsubmit']))  {
        if($_REQUEST['bulkaction'] == 'delete') 
            $msg = oligodb_bulkdelete($_REQUEST['bulkcheck']);
    }
        
    $display .= "<div class=\"wrap\">";
    
    if($msg)
        $display .= "<div id=\"message\" class=\"updated fade\"><p>{$msg}</p></div>";

    $display .= "<h2>oligodb <a href=\"#addnew\" class=\"add-new-h2\">".__('Add new oligo', 'oligodb')."</a></h2>";

    $num_oligos = oligodb_count();
   
    if(!$num_oligos) {

        $display .= "<p>".__('No oligos in the database', 'oligodb')."</p>";

    } else {

        $listall_sql = "SELECT oligo_name, oligo_id, oligo_seq, oligo_len, oligo_tm, owner, tags, template, comments, ordered
            FROM " . $tablename;
            
        $option_selected = array (
            'oligo_id' => '',
            'oligo_seq' => '',
            'oligo_tm' => '',
            'oligo_len' => '',
            'owner' => '',
            'template' => '',
            'tags' => '',
            'comments' => '',
            'time_added' => '',
            'time_updated' => '',
            'ordered' => '',
            'ASC' => '',
            'DESC' => '',
        );
        if(isset($_REQUEST['orderby'])) {
            $listall_sql .= " ORDER BY " . $_REQUEST['orderby'] . " " . $_REQUEST['order'];
            $option_selected[$_REQUEST['orderby']] = " selected=\"selected\"";
            $option_selected[$_REQUEST['order']] = " selected=\"selected\"";
        }
        else {
            $listall_sql .= " ORDER BY oligo_id ASC";
            $option_selected['oligo_id'] = " selected=\"selected\"";
            $option_selected['ASC'] = " selected=\"selected\"";
        }
        
        if(isset($_REQUEST['paged']) && $_REQUEST['paged'] && is_numeric($_REQUEST['paged']))
            $paged = $_REQUEST['paged'];
        else
            $paged = 1;

        $limit_per_page = 20;
        
        $total_pages = ceil($num_oligos / $limit_per_page);
        
        
        if($paged > $total_pages) $paged = $total_pages;

        $admin_url = get_bloginfo('wpurl'). "/wp-admin/admin.php?page=oligodb";
        if(isset($_REQUEST['orderby']))
            $admin_url .= "&orderby=".$_REQUEST['orderby']."&order=".$_REQUEST['order'];
        
        $page_nav = oligodb_pagenav($total_pages, $paged, 2, 'paged', $admin_url);
        
        $start = ($paged - 1) * $limit_per_page;
            
        $listall_sql .= " LIMIT {$start}, {$limit_per_page}"; 

        // Get all the oligos from the database
        $oligos = stripslashes_deep( $wpdb->get_results($listall_sql)) ;
        
        foreach($oligos as $oligo_data) {
            if($alternate) $alternate = "";
            else $alternate = " class=\"alternate\"";
            
            $oligos_list .= "<tr{$alternate}>";
            $oligos_list .= "<th scope=\"row\" class=\"check-column\"><input type=\"checkbox\" name=\"bulkcheck[]\" value=\"".$oligo_data->oligo_id."\" /></th>";
            $oligos_list .= "<td>" . $oligo_data->oligo_id . "</td>";
            $oligos_list .= "<td>";
            $oligos_list .= $oligo_data->oligo_seq;
            $oligos_list .= "<div class=\"row-actions\"><span class=\"edit\"><a href=\"{$admin_url}&action=editoligo&amp;id=".$oligo_data->oligo_id."\" class=\"edit\">".__('Edit', 'oligodb')."</a></span> | <span class=\"trash\"><a href=\"{$admin_url}&action=deloligo&amp;id=".$oligo_data->oligo_id."\" onclick=\"return confirm( '".__('Are you sure you want to delete this oligo?', 'oligodb')."');\" class=\"delete\">".__('Delete', 'oligodb')."</a></span></div>";
            $oligos_list .= "</td>";
            $oligos_list .= "<td>" . strlen($oligo_data->oligo_seq) ."</td>";
            $oligos_list .= "<td>" . $oligo_data->oligo_tm ."</td>";
            $oligos_list .= "<td>" . make_clickable(get_userdata($oligo_data->owner)->display_name)."</td>";
            $oligos_list .= "<td>" . implode(', ', explode(',', $oligo_data->tags)) . "</td>";
            $oligos_list .= "<td>" . $oligo_data->template ."</td>";
            $oligos_list .= "<td>" . $oligo_data->comments ."</td>";

            $oligos_list .= "</tr>";
        }
        
        $display .= "<form id=\"oligodb\" method=\"post\" action=\"".get_bloginfo('wpurl')."/wp-admin/admin.php?page=oligodb\">";
        $display .= "<div class=\"tablenav\">";
        $display .= "<div class=\"alignleft actions\">";
        $display .= "<select name=\"bulkaction\">";
        $display .=     "<option value=\"0\">".__('Bulk Actions')."</option>";
        $display .=     "<option value=\"delete\">".__('Delete', 'oligodb')."</option>";
        $display .= "</select>";    
        $display .= "<input type=\"submit\" name=\"bulkactionsubmit\" value=\"".__('Apply', 'oligodb')."\" class=\"button-secondary\" />";
        $display .= "&nbsp;&nbsp;&nbsp;";
        $display .= __('Sort by: ', 'oligodb');
        $display .= "<select name=\"orderby\">";
        $display .= "<option value=\"oligo_id\"{$option_selected['oligo_id']}>".__('oligo', 'oligodb')." ID</option>";
        $display .= "<option value=\"oligo_seq\"{$option_selected['oligo_seq']}>".__('oligo', 'oligodb')."</option>";
        $display .= "<option value=\"owner\"{$option_selected['owner']}>".__('Owner', 'oligodb')."</option>";
        $display .= "<option value=\"time_added\"{$option_selected['time_added']}>".__('Date added', 'oligodb')."</option>";
        $display .= "<option value=\"time_updated\"{$option_selected['time_updated']}>".__('Date updated', 'oligodb')."</option>";
        $display .= "</select>";
        $display .= "<select name=\"order\"><option{$option_selected['ASC']}>ASC</option><option{$option_selected['DESC']}>DESC</option></select>";
        $display .= "<input type=\"submit\" name=\"orderbysubmit\" value=\"".__('Go', 'oligodb')."\" class=\"button-secondary\" />";
        $display .= "</div>";
        $display .= '<div class="tablenav-pages"><span class="displaying-num">'.sprintf(_n('%d oligo', '%d oligos', $num_oligos, 'oligodb'), $num_oligos).'</span><span class="pagination-links">'. $page_nav. "</span></div>";
        $display .= "<div class=\"clear\"></div>";    
        $display .= "</div>";
        

        
        $display .= "<table class=\"widefat\">";
        $display .= "<thead><tr>
            <th class=\"check-column\"><input type=\"checkbox\" onclick=\"oligodb_checkAll(document.getElementById('oligodb'));\" /></th>
            <th>ID</th><th>".__('Sequence', 'oligodb')."</th>
            <th>".__('Length', 'oligodb')."</th>
            <th>".__('Tm', 'oligodb')."</th>
            <th>".__('Author', 'oligodb')."</th>
            <th>".__('Tags', 'oligodb')."</th>
            <th>".__('Template', 'oligodb')."</th>
            <th>".__('Comments', 'oligodb')."</th>
        </tr></thead>";
        $display .= "<tbody id=\"the-list\">{$oligos_list}</tbody>";
        $display .= "</table>";

        $display .= "<div class=\"tablenav\">";
        $display .= '<div class="tablenav-pages"><span class="displaying-num">'.sprintf(_n('%d oligo', '%d oligos', $oligos_count, 'oligodb'), $oligos_count).'</span><span class="pagination-links">'. $page_nav. "</span></div>";
        $display .= "<div class=\"clear\"></div>";    
        $display .= "</div>";

        $display .= "</form>";
        $display .= "<br style=\"clear:both;\" />";
        $display .= "</div>";
    }
    

    $display .= "<div id=\"addnew\" class=\"wrap\">\n<h2>".__('Add new oligo', 'oligodb')."</h2>";
    $display .= oligodb_editform();
    $display .= "</div>";
    
    $display .= "<div id=\"uploadCSV\" class=\"wrap\">\n<h2>".__('Upload CSV file', 'oligodb')."</h2>";
    $display .= oligodb_upload_CSV_Form();
    $display .= "</div>";
    
    #reset database
    #oligodb_install();
    #oligodb_uninstall();

    echo $display;

}


function oligodb_admin_footer()
{
    ?>
<script type="text/javascript">
function oligodb_checkAll(form) {
    for (i = 0, n = form.elements.length; i < n; i++) {
        if(form.elements[i].type == "checkbox" && !(form.elements[i].hasAttribute('onclick'))) {
                if(form.elements[i].checked == true)
                    form.elements[i].checked = false;
                else
                    form.elements[i].checked = true;
        }
    }
}
</script>

    <?php
}


?>
