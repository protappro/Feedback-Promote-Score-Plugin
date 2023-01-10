<?php
if (!class_exists('WP_List_Table')) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
require_once(ABSPATH . 'wp-admin/includes/template.php' );

class Fps_logs_Table extends WP_List_Table {

	public function __construct() {
		global $status, $page;
		parent::__construct( [
			'singular' => __( 'fps_logs', 'npslogstable' ), //singular name of the listed records
			'plural'   => __( 'fps_logs', 'npslogstable' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?

		] );

		add_action('admin_head', array(&$this, 'admin_header'));
		add_action('admin_init', array(&$this, 'delete_fps_data'));
		$this->process_bulk_action();
	}

	public function get_data() {
		global $wpdb;

		 //echo "<pre>"; print_r($_REQUEST); echo"</pre>";

		if($_REQUEST['apply_filter'] == "Apply"){
			$where = '';
			if(!empty($_REQUEST['customer_type'])){
				$where .= ' AND designation = "'.$_REQUEST['customer_type'].'"';
			}
			//if((int)$_REQUEST['score_field'] >=0){
			if(!empty($_REQUEST['score_field'])){
				$score = ltrim($_REQUEST['score_field'],"-");
				$where .= ' AND score = "'.$score.'"';
			}
			if(!empty($_REQUEST['fps_from_date']) && !empty($_REQUEST['fps_to_date'])){

				$from_date = date("Y-m-d", strtotime(str_replace("-", "/", $_REQUEST['fps_from_date'])));
				$to_date = date("Y-m-d", strtotime(str_replace("-", "/", $_REQUEST['fps_to_date'])));

				$where .= ' AND date(submitted_on) BETWEEN "'.$from_date.'" AND "'.$to_date.'"';
			}

		}


		$sql = "SELECT * FROM ".$wpdb->prefix."net_promoter_score ORDER BY submitted_on DESC";
		$results = $wpdb->get_results($sql, ARRAY_A);

		$process_result =[];
		foreach($results as $value){
			$value['designation'] = $value['designation'];
			$value['submitted_on'] = date("m-d-Y H:i:s", strtotime($value['submitted_on']));
			array_push($process_result, $value);
		}



		$search_sql = "SELECT * FROM wp_net_promoter_score WHERE 1 = 1";

		if(!empty($_REQUEST['s'] )){
			$search = esc_sql( $_REQUEST['s'] );			
		}

		if(!empty($_REQUEST['apply_filter'])){
			$search_sql .= $where;
		}

		 
		if( ! empty( $search ) && empty($_REQUEST['apply_filter']) ) {

			$pos = strpos($search, '-');
			if(false !== $pos) {
				$search =  date("Y-m-d", strtotime(str_replace("-", "/", $search)));
			}

			$search_sql .= " AND designation LIKE '%{$search}%'";
			$search_sql .= " OR score LIKE '%{$search}%'";
			$search_sql .= " OR ip_address LIKE '%{$search}%'";
			$search_sql .= " OR city LIKE '%{$search}%'";
			$search_sql .= " OR state LIKE '%{$search}%'";
			$search_sql .= " OR country LIKE '%{$search}%'";
			$search_sql .= " OR submitted_on LIKE '{$search}%'";
			$search_sql .= " ORDER BY submitted_on DESC";
			
		}else{			
			$search_sql .= " ORDER BY submitted_on DESC";
		}

		// echo "<pre>"; print_r($search_sql);  echo "</pre>";

		$arr_search_result = $wpdb->get_results( $search_sql, ARRAY_A);

		$arr_process_result = [];
		foreach($arr_search_result as $search_value){
			$search_value['designation'] = $search_value['designation'];
			$search_value['submitted_on'] = date("m-d-Y H:i:s", strtotime($search_value['submitted_on']));
			array_push($arr_process_result, $search_value);
		}				
		$process_result = $arr_process_result;	

		return $process_result;
	}

	function admin_header() {
		$page = ( isset($_GET['page']) ) ? esc_attr($_GET['page']) : false;

		if ($page == 'fps_manager'){			
			echo '<style type="text/css">';
			echo '.wp-list-table .column-designation  { width: 20%; }';
			echo '.wp-list-table .column-score { width: 10%; }';
			echo '.wp-list-table .column-ip_address { width: 15%; }';
			echo '.wp-list-table .column-city { width: 10%;}';
			echo '.wp-list-table .column-state { width: 10%;}';
			echo '.wp-list-table .column-country { width: 15%;}';
			echo '.wp-list-table .column-submitted_on { width: 20%;}';
			echo '</style>';
		}
	}

	function no_items() {
		_e('No data found.');
	}

	function column_default($item, $column_name) {
		switch ($column_name) {
			case 'designation':
			case 'score':
			case 'ip_address':
			case 'city':
			case 'state':
			case 'country':
			case 'submitted_on':
			return $item[$column_name];
			default:
            return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function get_columns() {
    	$columns = array(
    		'designation' => __('Customer Type', 'npslogstable'),
    		'score' => __('Score', 'npslogstable'),
    		'ip_address' => __('IP Address', 'npslogstable'),
    		'city' => __('City', 'npslogstable'),
    		'state' => __('State', 'npslogstable'),
    		'country' => __('Country', 'npslogstable'),
    		'submitted_on' => __('Date', 'npslogstable')
    	);
    	return $columns;
    }

    /*function usort_reorder($a, $b) {
    	$orderby = (!empty($_GET['orderby']) ) ? $_GET['orderby'] : 'score';       	
    	$order = (!empty($_GET['order']) ) ? $_GET['order'] : 'asc';
    	$result = strcmp($a[$orderby], $b[$orderby]);
    	return ( $order === 'asc' ) ? $result : -$result;
    }*/
    function column_designation($item) {
    	$actions = array(
    		// 'edit' => sprintf('<a href="?page=%s&action=%s&book=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['id']),
    		'delete' => sprintf('<a href="?page=%s&action=%s&id=%s" class="delete_fps_value">Delete</a>', $_REQUEST['page'], 'delete', $item['id']),
    	);
    	return sprintf('%1$s %2$s', $item['designation'], $this->row_actions($actions));
    }
    function get_sortable_columns() {
    	$sortable_columns = array(
    		'designation' => array('designation', true),
    		'score' => array('score', false),
    		'city' => array("city"),
    		'state' => array("state"),
    		'country' => array("country"),
    		'submitted_on' => array('submitted_on', true)
    	);
    	return $sortable_columns;
    }
    function column_cb($item) {
    	return sprintf(
    		'<input type="checkbox" name="item[]" value="%s" />', $item['id']
    	);
    }

    function prepare_items() {
    	$data=$this->get_data();
    	$columns = $this->get_columns();
    	$hidden = array();		
    	//$sortable = $this->get_sortable_columns();        
    	$this->_column_headers = array($columns, $hidden, $sortable);
    	$per_page = 200;        
    	$current_page = $this->get_pagenum();        
    	$total_items = count($this->get_data());
    	$found_data = array_slice($this->get_data(), ( ( $current_page - 1 ) * $per_page), $per_page);
    	$this->set_pagination_args(array(
	        'total_items' => $total_items, //WE have to calculate the total number of items
	        'per_page' => $per_page   //WE have to determine how many items to show on a page
	    ));
    	$this->items = $found_data;
    }

    function delete_fps_data( $id ) {
    	global $wpdb;
    	if($_REQUEST['action'] == "delete"){
    		$id = $_REQUEST['id'];
    		$wpdb->delete(
    			"{$wpdb->prefix}net_promoter_score",
    			[ 'id' => $id ],
    			[ '%d' ]
    		);

    		wp_redirect( admin_url( '/admin.php?page=fps_manager' ) );
    	}
    }

}
add_action('admin_menu', 'add_fps_submenu');

function add_fps_submenu() {
    // $hook = add_submenu_page('admin.php?page=rps_manager', __('Net Promoter Managers', 'menu-nps-log'), __('Net Promoter Managers', 'menu-nps-log'), 'manage_options', 'npslogs', 'fps_logs_render_list_page');
    $user_id = get_current_user_id();
    $user_meta = get_userdata($user_id);
    $user_roles = $user_meta->roles;
	// $hook = add_submenu_page( 'fps_manager_menu', 'Net Promoter Managers', 'Net Promoter Managers', 'administrator', 'fps_manager', 'fps_logs_render_list_page');
	if(in_array('administrator', $user_roles)) {
		$hook = add_submenu_page( 'fps_manager_menu', 'Net Promoter Managers', 'Net Promoter Managers', 'administrator', 'fps_manager', 'fps_logs_render_list_page');
	} else {
		$hook = add_submenu_page( 'fps_manager_menu', 'Net Promoter Managers', 'Net Promoter Managers', 'fps_manager', 'fps_manager', 'fps_logs_render_list_page');
	}
	add_action("load-$hook", 'add_fps_loger');
}

function add_fps_loger() {
	global $myListTable;
	$option = 'per_page';
	$args = array(
		'label' => 'Number of items per page:',
		'default' => 5,
		'option' => 'items_per_page'
	);
	add_screen_option($option, $args);
	$myListTable = new Fps_logs_Table();
}

function fps_logs_render_list_page() {
	global $myListTable;
	?>

	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<div class="wrap">
		<h2 style="margin-bottom:5px;">Net Promoter Score List</h2>
		<?php $myListTable->prepare_items(); ?>
		<form method="post" action="/wp-admin/?export_net_promoter_score_data=1">
			<!-- <input type="hidden" name="page" value="fps_list_table"> -->
			<input type="hidden" name="page" value="fps_manager">
			<?php //$myListTable->search_box('Search', 'search_id'); ?>
			<br/>
			<div class="actions">

					<div id="customer_type_sec" style="display: none;">
						Customer Type : 
						<select name="customer_type" id="customer_type">
							<option value="">Select any type</option>
							<?php 
								$arr_customer_type = [
									"Homeowner" => "Homeowner",
									"Business Owner" => "Business Owner",
									"Plumbing Professional" => "Plumbing Professional",
									"Distributor" => "Distributor",
									"Other" => "Other"
								];
								foreach($arr_customer_type as $customer_type) { ?>
									<option value="<?php echo $customer_type;?>" <?php echo ($customer_type == $_REQUEST['customer_type'])?  'selected="selected"' : '' ?> >
										<?php echo $customer_type;?>
									</option>
								<?php } ?> 
						</select>
					</div>

					<div id="score_field" style="display: none; margin-left:20px;">
						Score : 
						<select name="score_field" id="score_data_fields">
							 <option value="" ></option> 
							<?php 
							for($i=0; $i<=10; $i++) { $val = "-".$i; ?>
								<option value="<?php echo $val;?>" <?php  echo ($val == $_REQUEST['score_field'])?  'selected="selected"' : '' ?>><?php echo $i ?></option>
							<?php }
							?> 
						</select>
					</div>

					<div id="date-field" style="display: inline-block; margin-left: 0px;">
						<strong>From Date :</strong> <input type="text" name="fps_from_date" id="fps_from_date" placeholder="mm-dd-yyyy" class="fps_from_date" value="<?php if(!empty($_REQUEST['fps_from_date'])) { echo $_REQUEST['fps_from_date']; } ?>" autocomplete="off" style="width: 25%;"> 
						&nbsp;&nbsp;&nbsp;&nbsp;
						<strong>To Date :</strong> <input type="text" name="fps_to_date" id="fps_to_date" placeholder="mm-dd-yyyy" class="fps_to_date" value="<?php if(!empty($_REQUEST['fps_to_date'])) { echo $_REQUEST['fps_to_date']; } ?>" autocomplete="off" style="width: 25%;"> 
					</div>

					<input type="submit" name="export_nps" id="export_fps_btn" class="button export_fps_btn" value="Export" style="display: inline-block;">
					<!-- <input type="submit" name="apply_filter" id="filter_action" class="button action" value="Apply" style="display: inline-block; margin-left: -70px;"> -->

					</div>
					<?php
					/*$string ='';
					if($_REQUEST['apply_filter'] == "Apply"){
						$string .= '&filter=1';
						if(!empty($_REQUEST['customer_type'])){
							$string .= '&customer_type='.$_REQUEST['customer_type'];
						}
				
						if(!empty($_REQUEST['score_field'])){
							$score_val = ltrim($_REQUEST['score_field'],"-");
							$string .= '&score_field='.$score_val;
							
						}
						if(!empty($_REQUEST['fps_from_date']) && !empty($_REQUEST['fps_to_date'])){
							$string .= '&fps_from_date='.$_REQUEST['fps_from_date'].'&fps_to_date='.$_REQUEST['fps_to_date'];
						}
					} else if (!empty($_REQUEST['s'])) {
						$string .= '&filter=1';
						$string .= '&s='.$_REQUEST['s'];
					}
					*/
					?>

					<!-- <div style="padding-left: 0px;margin-bottom: -37px; margin-top: 10px;">
						<a class="button export_fps_btn" href="/wp-admin/?export_net_promoter_score_data=1<?php echo $string; ?>" target="_blank">
							Export Data
						</a> 
					</div>-->
					<script>
						jQuery(document).ready(function(event) {							
							jQuery("#fps_from_date").datepicker({
								dateFormat: 'mm-dd-yy',
								autoclose: true,		        
								onSelect: function (selected) {
									var dt = new Date(selected);
									dt.setDate(dt.getDate() + 1);
									jQuery("#fps_to_date").datepicker("option", "minDate", dt);
								}
							});

							jQuery("#fps_to_date").datepicker({	
								dateFormat: 'mm-dd-yy',
								autoclose: true,	       
								onSelect: function (selected) {
									var dt = new Date(selected);
									dt.setDate(dt.getDate() - 1);
									jQuery("#fps_from_date").datepicker("option", "maxDate", dt);
								}
							});
						});
					</script>
					<?php //$myListTable->display(); ?>
				</form>
			</div>
		<?php }