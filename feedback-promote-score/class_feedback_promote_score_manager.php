<?php
/**
 * Feedback Promote Score use to create feedback rating meter popup.
 *
 * @package   FeedbackPromoteScore
 * @author    Protap Mondal <protap.p1993@gmail.com>
 * @license   GPL-3.0+
 * @link      http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright 2020 Protap Git
 *
 * @wordpress-plugin
 * Plugin Name: Feedback Promote Score
 * Plugin URI:  https://github.com/protappro
 * Description: The plugin allows to create feedback rating meter popup and rating manager table.
 * Version:     1.0.0.0
 * Author:      Protap Mondal
 * Author URI:  https://github.com/protappro
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

//* Register activation hook to add Blog Manager role
register_activation_hook( __FILE__ , 'feedback_promote_score_activation' );

//* Register deactivation hook to remove Blog Manager role
register_deactivation_hook( __FILE__ , 'feedback_promote_score_deactivation' );

function feedback_promote_score_activation(){
    $caps = [
        //* Meta capabilities
        'read'                   => true,
        'edit_blog'              => false,
        'read_blog'              => true,
        'delete_blog'            => false,

        //* Primitive capabilities used outside of map_meta_cap()
        'edit_blogs'             => true,
        'edit_others_blogs'      => true,
        'publish_blogs'          => true,
        'read_private_blogs'     => true,

        //* Primitive capabilities used within of map_meta_cap()
        'delete_blogs'           => false,
        'delete_private_blogs'   => false,
        'delete_published_blogs' => false,
        'delete_others_blogs'    => false,
        'edit_private_blogs'     => false,
        'edit_published_blogs'   => false,
    ];

    $role_object = get_role( "administrator" );  //get admin role
    $role_object->add_cap( 'read' );
    $role_object->add_cap( 'edit_blog' );
    $role_object->add_cap( 'read_blog');
    $role_object->add_cap( 'delete_blog' );

    $role_object->add_cap( 'edit_blogs' );
    $role_object->add_cap( 'edit_others_blogs' );
    $role_object->add_cap( 'publish_blogs' );
    $role_object->add_cap( 'read_private_blog' );
    
    $role_object->add_cap( 'delete_blogs' );
    $role_object->add_cap( 'delete_private_blogs' );
    $role_object->add_cap( 'delete_published_blogs' );
    $role_object->add_cap( 'delete_others_blogs' );
    $role_object->add_cap( 'edit_private_blogs' );
    $role_object->add_cap( 'edit_published_blogs' );

    add_role( 'fps_manager', 'NPS Manager', $caps );
}

function feedback_promote_score_deactivation(){
    remove_role( 'fps_manager' );
}

add_filter( 'user_has_cap', 'author_cap_filter', 10, 4 );

function author_cap_filter( $allcaps, $caps, $args, $user ) {
    return $allcaps;
}

Class FeedbackPromoteScoreManager {

    /** Start :: All Wordpress Hook initialization * */
    function __construct() {
        add_action('wp_head', array($this, 'fps_modal_enqueues'));

        add_action('wp_footer', array($this, 'feedback_promote_score_modal'));

        add_action('admin_menu', array($this, 'pm_feedback_promote_score_options'), 10);

        add_action('init', array($this, 'pm_export_fps_data'), 9);

        add_action('init', array($this, 'migrate_fps_data'), 9);

        add_action('wp_ajax_nopriv_rating_form_manager_action', array($this, 'rating_form_manager_action_callback'));
        add_action('wp_ajax_rating_form_manager_action', array($this, 'rating_form_manager_action_callback'));
        add_action( 'plugins_loaded', array($this,'net_crreate_table'));

        $this->show_fps_log_list();
    }

    public function migrate_fps_data(){
        global $wpdb;
        if (isset($_GET['migrate_fps_data'])) { die('fps_import');
            // $sql = "SELECT * FROM " . $wpdb->prefix . "feedback_promote_score WHERE id !=8 ORDER BY submitted_on ASC ";
            $sql = "SELECT * FROM " . $wpdb->prefix . "feedback_promote_score WHERE `id` BETWEEN 151 AND 500 ORDER BY submitted_on ASC ";            
            $results = $wpdb->get_results($sql, ARRAY_A);
            echo "<pre>"; 
            print_r($results); 
            exit;
            $fps_id = [];
            foreach($results as $npsData){   

                $user_info = [
                  "site_url" => "https://www.example.com",
                  "ip_address" => $npsData['ip_address'],
                  "user_city" => $npsData['city'],
                  "user_state" => $npsData['state'],
                  "user_country" => $npsData['country'],
                  "action" => "initial_action",
                ];
                $initial_res = $this->send_user_details($user_info, "data_migration");

                print_r($initial_res); 

                if($initial_res['session_id'] > 0){
                    $data_attribute = [
                        'designation' => $npsData['designation'],
                        'score' => $npsData['score'],
                        'ip_address' => $npsData['ip_address'],
                        'city' => $npsData['city'],
                        'state' => $npsData['state'],
                        'country' => $npsData['country']
                    ];
                    $json_data = json_encode($data_attribute);
                    
                    $fps_data = [           
                        "data_attribute" => $json_data,
                        "href_text" => "", 
                        "href_value" => "",
                        "group_name" => 'Net Promoter Score',
                        "current_url" => "https://www.example.com",
                        "pm_track_session_id" => $initial_res['session_id'],
                        "visited_time" => $npsData['submitted_on'],
                        "action" => "fps_data_tracking"
                    ];
                    $fps_response = $this->send_user_details($fps_data, 'data_migration'); 


                    array_push($fps_id, $npsData['id']);
                }   
                print_r($fps_response); 
                print_r($fps_id);

                // exit;
            }
            echo "<pre>";
            print_r($fps_id);
            exit();
        }
    }

    function fps_modal_enqueues() {
        wp_enqueue_style('nps-main-style', plugin_dir_url(__FILE__) . 'assets/css/fps_main.css', microtime(), true);
       // wp_enqueue_script('nps-main-script', plugin_dir_url(__FILE__) . 'assets/js/fps_main.js', array('jquery'), '1.0.0', true);

        //wp_enqueue_style('nps-datatable-style', '//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css', '1.0.0', true);
        //wp_enqueue_script('nps-datatable-script', '//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js', array('jquery'), '1.0.0', true);
    }

    function net_crreate_table() {
        global $wpdb;
        $installed_ver = get_option( "feedback_promote_score_db_version" );
        if(empty($installed_ver)) {
            $sql = "
            CREATE TABLE `wp_feedback_promote_score` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `designation` varchar(60) NOT NULL,
            `score` varchar(20) NOT NULL,
            `ip_address` varchar(15) NOT NULL,
            `city` varchar(90) DEFAULT NULL,
            `state` varchar(90) DEFAULT NULL,
            `country` varchar(90) DEFAULT NULL,
            `submitted_on` datetime NOT NULL,
            PRIMARY KEY `net_id` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            ";
            require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
            dbDelta($sql);
            add_option( 'feedback_promote_score_db_version', '1.0' );
        }
    }

    function getClientIP()
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) 
        {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        };

        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key)
        {
            if (array_key_exists($key, $_SERVER)) 
            {
                foreach (explode(',', $_SERVER[$key]) as $ip)
                {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
                    {
                        return $ip;
                    };
                };
            };
        };

        return false;
    }

    function feedback_promote_score_modal() { ?>
       <?php
        global $wpdb, $wp;
        $current_url = home_url(add_query_arg(array(), $wp->request));

        //$fps_data = $wpdb->get_row("SELECT * FROM wp_feedback_promote_score WHERE ip_address = '100.19.74.129'");

        $ipaddress = '';
        /*
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        */

           $ipaddress = $this->getClientIP();
           if(!$ipaddress){
            $ipaddress = "UNKNOWN";
           }

            $request_url = $_SERVER['REQUEST_URI'];
                       
            $url = trim($request_url, "/");

            $modal_open_url = array("my-water-heater-information",
                "documentation/usa/residential/gas",
                "documentation/usa/residential/electric",
                "documentation/canada/commercial/gas",
                "documentation/canada/commercial/electric",
                "rightspec-tools",
                "contractors"                                
            );

            if(in_array($url, $modal_open_url)) {        
                if ($ipaddress != "UNKNOWN") {  
                    // $fps_data = $wpdb->get_row("SELECT * FROM wp_feedback_promote_score WHERE ip_address = '" . $ipaddress . "'");
                    
                    $parse_url = parse_url($current_url); 
                    $check_fps_data = [           
                        "url" => $parse_url['host'],
                        "ip_address" => $ipaddress, 
                        "action" => "check_fps_data"
                    ]; 

                    $fps_res = $this->send_user_details($check_fps_data, 'checking_nps'); 

                    if (empty($fps_res) || $fps_res['response'] == 'not_exist') { 
                        if (empty($_COOKIE['pm_feedback'])) {

                            ?>
                            <input type="hidden" id="ip_address" class="form-control" name="ip_address" value="<?php echo $ipaddress ?>">
                            <div class="modal fade" id="fps_modal" data-backdrop="static" data-keyboard="false">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form method="post" action="" id="rating-form"> 
                                            <div class="modal-body">    
                                                <button type="button" class="close close-nps-btn" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>                       
                                                <h2 class="modal-title">
                                                    Help us with your feedback!
                                                </h2>
                                                <div id="err_msg_section">
                                                    <span id="bothValidator" class="text-danger font-14">Please provide rating and customer type.</span>
                                                    <span id="designationValidator" class="text-danger font-14">Please provide a customer type.</span>
                                                    <span id="ratingValidator" class="text-danger font-14">Please provide a rating.</span>  
                                                </div>                      

                                                <p class="score-info-txt">
                                                    How likely are you to recommend Bradford White Water Heaters to a friend or colleague?
                                                </p>    

                                                <div class="rating-header clearfixs">
                                                    <div class="left"><span class="italic">Not likely</span></div>
                                                    <div class="right"><span class="italic">Very likely</span></div>
                                                </div>
                                                <div class="rating-container">
                                                    <span>0</span>
                                                    <span>1</span>
                                                    <span>2</span>
                                                    <span>3</span>
                                                    <span>4</span>
                                                    <span>5</span>
                                                    <span>6</span>
                                                    <span>7</span>
                                                    <span>8</span>
                                                    <span>9</span>
                                                    <span>10</span>
                                                </div>
                                                <input type="hidden" name="score_field" id="ScoreHiddenField" value="">

                                                <div class="deg-sec">
                                                    <?php
                                                    $arr_designation = [
                                                        "Homeowner" => "Homeowner",
                                                        "Business Owner" => "Business Owner",
                                                        "Plumbing Professional" => "Plumbing Professional",
                                                        "Distributor" => "Distributor",
                                                        "Other" => "Other",
                                                    ];
                                                    ?>      
                                                    <span class="mgr-5">I'm a:</span><br>                           
                                                    <?php foreach ($arr_designation as $key => $value) { ?>
                                                        <input type="radio" name="designation" class="designation" value="<?php echo $key; ?>">
                                                        <span class="mgr-7 font-14"><?php echo $value; ?></span><br class="class-visiable-small"/>                  
                                                    <?php } ?>
                                                </div>

                                                <button type="submit" id="nps-btn-submit" class="btn btn-primary nps-btn">Submit</button>                           
                                            </div>
                                        </form>

                                        <div class="success-box">
                                            <div class="modal-body" style="">
                                                <button type="button" class="close close-nps-btns" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>   
                                                <img src="<?php echo plugin_dir_url( __FILE__ ) . 'assets/img/like.png'?>" width="11%">
                                                <h2>
                                                    Thank you for your valuable feedback.
                                                </h2>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php }
                    } 
                
            } 
        }
        ?>

        <script>
            jQuery(document).ready(function (event) {
                var ip = jQuery('#ip_address').val();
                var cookie_val = getCookie('feedback_promote_score');
                if (cookie_val != undefined) {
                    var items = cookie_val.split(',');
                    var cookie_ip = items["0"];
                    var cookie_value = items["1"];
                }
                // alert(ip);                  

                <?php  if(in_array($url, $modal_open_url)) { ?>         
                    if (cookie_ip == ip && cookie_value == "yes") {
                        jQuery('#fps_modal').hide();
                    } else {
                        setTimeout(function () {
                            jQuery('#fps_modal').modal();
                        }, 3000);
                    }
                <?php } ?>


                jQuery(".close-nps-btn").on('click', function () {
                    document.cookie = "pm_feedback=yes; path=/;";             
                });

                jQuery('div.rating-container span').on('click', onRatingSelection);

                function getCookie(name) {
                    var value = "; " + document.cookie;
                    var parts = value.split("; " + name + "=");
                    if (parts.length >= 2)
                        return parts.pop().split(";").shift();
                }
            });

            function onRatingSelection(e) {
                jQuery('div.rating-container span').removeClass('selected');
                jQuery(this).addClass('selected');
                jQuery('#ScoreHiddenField').val(jQuery(this).text());
                jQuery('#ScoreCustomValidator').hide();
            }
            function Score_ClientValidate(sender, e) {
                e.IsValid = jQuery('div.rating-container > span.selected').length == 1;
            }



            jQuery("#bothValidator").hide();
            jQuery("#designationValidator").hide();
            jQuery("#ratingValidator").hide();

            jQuery('#rating-form').on('submit', function (e) {

                //jQuery("#nps-btn-submit").on('click', function () {

                var score_field = jQuery("#ScoreHiddenField").val();
                var is_checked = jQuery(".designation").is(':checked');

                if (score_field == "" && is_checked === false) {
                    jQuery("#bothValidator").show();
                    return false;
                } else {
                    jQuery("#bothValidator").hide();
                }
                if (score_field == "") {
                    jQuery("#ratingValidator").show();
                    return false;
                }
                if (is_checked === false) {
                    jQuery("#designationValidator").show();
                    return false;
                }
                <?php //$_SESSION['pm_php_feedback_session'] = 'Yesp'; ?>

                // var rating_data = jQuery("#rating-form").serialize();    

                var input_ip_address = jQuery("#ip_address").val();
                var score_field = jQuery("#ScoreHiddenField").val();
                var designation = jQuery(".designation:checked").val();
                var pm_tracking_cookies = getCookie('pm_track_session_id');
                var current_url = "<?php echo $current_url ?>";

                var data = {
                    'action': 'rating_form_manager_action',
                    // 'rating_data': rating_data,
                    'input_ip_address': input_ip_address,
                    'score_field': score_field,
                    'customer_type': designation,
                    'pm_tracking_cookies' : pm_tracking_cookies,
                    'current_url' : current_url
                };
                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: data,
                    success: function (response) {

                        var res_data = JSON.parse(response);
                        if (res_data == "true") {                            

                            //Set cookies value from form data.             
                            var form_data = jQuery("#rating-form").serializeArray();
                            // var input_ip = form_data[0]['value'];
                            var input_ip = jQuery('#ip_address').val();

                            var date = new Date();
                            date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));
                            expires = "expires=" + date.toUTCString();

                            var feedback_promote_score = [input_ip, 'yes'];
                            document.cookie = "feedback_promote_score =" + feedback_promote_score + ";" + expires + ";path=/";

                            jQuery('.success-box').show();
                            jQuery('#rating-form').hide();

                        } else {
                            jQuery('.success-box').hide();
                            jQuery('#rating-form').show();
                            jQuery('#form_error').show();
                        }

                    }
                });
                return false;
            });
        </script>

        <?php
    }

    function rating_form_manager_action_callback() {
        global $wpdb;

        $parse_url = parse_url($_POST['current_url']); 
        $check_fps_data = [           
            "url" => $parse_url['host'],
            "ip_address" => $_POST['input_ip_address'], 
            "action" => "check_fps_data"
        ]; 
        $fps_res = $this->send_user_details($check_fps_data, 'checking_nps'); 

        $status = "";
        if ($_POST['input_ip_address'] != "UNKNOWN") {

            $ip_url = "https://api.ipdata.co/" . $_POST['input_ip_address'];
            $get_apiKey = "9a5eedf49cc5f03aded3ac98cf171a82e193d627487966e91822d2d4";
            $api_url = $ip_url . '?api-key=' . $get_apiKey;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $getlocationinfo = json_decode($response, true);

            $date = new DateTime();
            $date->setTimezone(new DateTimeZone('America/New_York'));
            $current_date = $date->format('Y-m-d H:i:s');



            $data_attribute = array(
                'designation' => $_POST['customer_type'],
                'score' => $_POST['score_field'],
                'ip_address' => $_POST['input_ip_address'],
                'city' => !empty($getlocationinfo['city'])?$getlocationinfo['city']:"",
                'state' => !empty($getlocationinfo['region'])?$getlocationinfo['region']:"",
                'country' => !empty($getlocationinfo['country_name'])?$getlocationinfo['country_name']:"",
            );
            $data_attribute = json_encode($data_attribute);

            $fps_data = [           
                "data_attribute" => $data_attribute,
                "href_text" => "", 
                "href_value" => "",
                "group_name" => 'Net Promoter Score',
                "current_url" => $_POST['current_url'],
                "pm_track_session_id" => $_POST['pm_tracking_cookies'],
                "visited_time" => $current_date,
                "action" => "fps_data_tracking"
            ]; 

            if (empty($fps_res) || $fps_res['response'] == 'not_exist') { 
                // $wpdb->insert($wpdb->prefix . 'feedback_promote_score', $data, array('%s', '%s'));
                
                $fps_response = $this->send_user_details($fps_data, ""); 

                if ($fps_response['fps_last_id'] > 0) {
                    $status = 'true';
                } else {
                    $status = 'false';
                }
            }else{
                $status = 'false';            
            }

        } else {
            $status = 'false';
        }
        die(json_encode($status));
    }

    public function send_user_details($data, $type){              

        if($type == 'checking_nps'){            
            $curl = curl_init( 'https://example.com/checking_fps_data.php' );
        } else if($type == 'data_migration'){
            $curl = curl_init( 'https://example.com/migration.php' );
        } else if($type == 'export_fps_data'){
            $curl = curl_init( 'https://example.com/export_pm_tracking_data.php' );
        } else {
            $curl = curl_init( 'https://example.com/pm_tracking.php' );
        }

        curl_setopt( $curl, CURLOPT_POST, true );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, false );

        $response = curl_exec( $curl );
        curl_close( $curl );
        return json_decode($response, true);  
        // return $response;       
    }

    function pm_feedback_promote_score_options() {
        $user_id = get_current_user_id();
        $user_meta = get_userdata($user_id);
        $user_roles = $user_meta->roles;

        if(in_array('administrator', $user_roles)) {
            add_menu_page('Net Promoter Score Manager', 'Net Promoter Score', 'administrator', 'fps_manager', array($this, 'fps_reporting_section'), "dashicons-performance", 99);
        } else if(in_array('fps_manager', $user_roles)) {
            add_menu_page('Net Promoter Score Manager', 'Net Promoter Score', 'fps_manager', 'fps_manager', array($this, 'fps_reporting_section'), "dashicons-performance", 99);
        }
    }

    function fps_reporting_section() { ?>
        <style type="text/css">
            #toplevel_page_fps_manager .wp-menu-name{
                width: 99%;
            }
            #toplevel_page_fps_manager .wp-menu-image{
                margin-top: 3px;
            }   
        </style>
    <?php
    }

    function pm_export_fps_data() {
        global $wpdb;
        $current_url = home_url(add_query_arg(array(), $wp->request));
        $parse_url = parse_url($current_url); 
 
        date_default_timezone_set('America/New_York');
        $get_current_date = date('Y-m-d H:i:s', time());
        
        $where = '';
        if (isset($_POST['page'])) {
            /*if(!empty($_GET['customer_type'])){
                $where .= ' AND designation = "'.$_GET['customer_type'].'"';
            } 
            if(trim($_GET['score_field']) !="") {           
                if((int)$_GET['score_field']>=0) {                
                    $where .= ' AND score = "'.$_GET['score_field'].'"';
                }
            }*/
            if(!empty($_POST['fps_from_date']) && !empty($_POST['fps_to_date'])){
                $from_date = date("Y-m-d", strtotime(str_replace("-", "/", $_POST['fps_from_date'])));
                $to_date = date("Y-m-d", strtotime(str_replace("-", "/", $_POST['fps_to_date'])));
                // $where .= ' AND date(submitted_on) BETWEEN "'.$from_date.'" AND "'.$to_date.'"';
            }
        } 
        if(!empty($_POST['s'])) {
            $search = esc_sql( $_POST['s'] );
            $pos = strpos($search, '-');
            if(false !== $pos) {
                $search =  date("Y-m-d", strtotime(str_replace("-", "/", $search)));
            }

            $where .= " AND designation LIKE '%{$search}%'";
            $where .= " OR score LIKE '%{$search}%'";
            $where .= " OR ip_address LIKE '%{$search}%'";
            $where .= " OR city LIKE '%{$search}%'";
            $where .= " OR state LIKE '%{$search}%'";
            $where .= " OR country LIKE '%{$search}%'";
            $where .= " OR submitted_on LIKE '{$search}%'";            
        }

        if (isset($_GET['export_feedback_promote_score_data'])) {

            if ((is_admin() && current_user_can("edit_users")) || in_array('fps_manager', $user_roles)) {           
                // $sql = "SELECT * FROM " . $wpdb->prefix . "feedback_promote_score where 1=1 " . $where . " ORDER BY submitted_on DESC";
                //$results = $wpdb->get_results($sql, ARRAY_A);

                $check_fps_data = [           
                    "url" => $parse_url['host'],
                    "action" => "export_nps",
                    "from_date" => $from_date,
                    "to_date" => $to_date,
                ];
                $results = $this->send_user_details($check_fps_data, 'export_fps_data');   

                $fps_csv_data = '"Customer Type","Score","IP Address","City","State","Country","Date"';
                $fps_csv_data .= chr(13);

                if (count($results) > 0) {
                    foreach ($results as $key => $value) {
                        $rating_data = json_decode($value['data_attribute'], true);

                        $fps_csv_data .= '"' . $rating_data['designation'] . '",';
                        $fps_csv_data .= '"' . $rating_data['score'] . '",';
                        $fps_csv_data .= '"' . $rating_data['ip_address'] . '",';
                        $fps_csv_data .= '"' . $rating_data['city'] . '",';
                        $fps_csv_data .= '"' . $rating_data['state'] . '",';
                        $fps_csv_data .= '"' . $rating_data['country'] . '",';
                        $fps_csv_data .= '"' . date("m-d-Y H:i:s", strtotime($results[$key]['visited_time'])) . '",';
                        $fps_csv_data .= chr(13);
                    }
                }

                $filename = "Feedback-Promote-Score-".date("m-d-Y").".csv";
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.$filename);
                header('Content-Transfer-Encoding: binary');
                header('Connection: Keep-Alive');
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                header('Content-Length: ' . strlen($fps_csv_data));
                echo $fps_csv_data;
                exit();
            }
        }
    }

    function show_fps_log_list() {
        require_once plugin_dir_path(__FILE__) . "class-wp-nps-logs-table.php";
        $obj_wp_fps_logs = new fps_logs_Table();
    }

}

$obj_feedback_promote_score_manager = new FeedbackPromoteScoreManager();
