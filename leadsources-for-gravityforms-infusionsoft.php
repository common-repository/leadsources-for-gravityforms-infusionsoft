<?php

/*
Plugin Name: LeadSources for Gravity Forms Infusionsoft Add-On
Plugin URI: https://www.aod-tech.com
Description: Create and assign LeadSources to your Infusionsoft contacts automatically through Gravity Forms
Version: 1.06
Author: AoD Technologies LLC
Author URI: https://www.aod-tech.com
Text Domain: leadsources-for-gravityforms-infusionsoft
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2016 AoD Technologies LLC.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

class GFInfusionsoftLeadsources {

	/* From parent */
	private static $classLoader;
    private static $min_gravityforms_version = "1.3.9";
    private static function is_gravityforms_installed(){
        return class_exists("RGForms");
    }

    private static function is_gravityforms_supported(){
        if(class_exists("GFCommon")) {
            $is_correct_version = version_compare(GFCommon::$version, self::$min_gravityforms_version, ">=");
            return $is_correct_version;
        }
        else{
            return false;
        }
    }
    //Returns true if the current page is an Feed pages. Returns false if not
    private static function is_infusionsoft_page(){
        global $plugin_page; $current_page = '';
        $infusionsoft_pages = array("gf_infusionsoft");

        if(isset($_GET['page'])) {
            $current_page = trim(strtolower($_GET["page"]));
        }

        return (in_array($plugin_page, $infusionsoft_pages) || in_array($current_page, $infusionsoft_pages));
    }
	
	static private function clean_utf8($string) {

        if(function_exists('mb_convert_encoding') && !seems_utf8($string)) {
            $string = mb_convert_encoding($string, "UTF-8", 'auto');
        }

        // First, replace UTF-8 characters.
        $string = str_replace(
            array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
            array("'", "'", '"', '"', '-', '--', '...'),
        $string);

        // Next, replace their Windows-1252 equivalents.
        $string = str_replace(
            array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
            array("'", "'", '"', '"', '-', '--', '...'),
        $string);

        return $string;
    }
	
	private static function test_api($echo = false) {
        $works = true; $message = ''; $class = '';
        $key = GFInfusionsoft::get_setting('key');
        $appname = GFInfusionsoft::get_setting('appname');

        if(empty($appname) && empty($key)) {

            $message = sprintf( '%s<h3>%s</h3><p>%s</p><p><a href="http://infusionsoft.force.com/analytics?PartnerId=001j0000011SQBM&AffiliateCode=a69272&CampaignId=701j0000001mlwy&TrackingLinkId=a1Bj0000002IwR6&Link_Posted_By__c=005j000000Flk9k" class="button button-primary">%s</a> <a href="http://infusionsoft.force.com/analytics?PartnerId=001j0000011SQBM&AffiliateCode=a69272&CampaignId=701j0000001mlwy&TrackingLinkId=a1Bj0000002JwD4&Link_Posted_By__c=005j000000Flk9k" class="button button-secondary">%s</a></p>',
		        '<a href="http://infusionsoft.force.com/analytics?PartnerId=001j0000011SQBM&AffiliateCode=a69272&CampaignId=701j0000001mlwy&TrackingLinkId=a1Bj0000002JwD4&Link_Posted_By__c=005j000000Flk9k"><img alt="Infusionsoft Logo" src="' . esc_attr( plugins_url(null, WP_PLUGIN_DIR.'/infusionsoft/infusionsoft.php').'/images/infusion-logo.png' ) .'" style="display:block; margin:15px 7px 0 0;" width="200" height="33"/></a>',
		        sprintf( esc_html__('Don\'t have an %sInfusionsoft%s account?', 'gravity-forms-infusionsoft'), '<a href="http://infusionsoft.force.com/analytics?PartnerId=001j0000011SQBM&AffiliateCode=a69272&CampaignId=701j0000001mlwy&TrackingLinkId=a1Bj0000002JwD4&Link_Posted_By__c=005j000000Flk9k">', '</a>' ),
		        esc_html__('This plugin requires an Infusionsoft account. If you have an Infusionsoft account, fill out the settings form below. Otherwise, you should sign up for an Infusionsoft account and start taking advantage of the world\'s best CRM.', 'gravity-forms-infusionsoft'),
		        esc_html__('Sign up for Infusionsoft Today!', 'gravity-forms-infusionsoft'),
		        esc_html__('Visit Infusionsoft.com', 'gravity-forms-infusionsoft')
            );

            $works = false;
            $class = 'updated';
        } else if(empty($appname)) {

            $message = sprintf( esc_html__("Your Account Subdomain (also called \"Application Name\") is required. %sEnter it below%s.", 'gravity-forms-infusionsoft'), "<label for='gf_infusionsoft_appname'><a>", "</a></label>" );
            $message .= "<span class='howto'>";
            $message .= sprintf( esc_attr__("If you access your Infusionsoft account from %sexample123%s.infusionsoft.com%s, your Account Subdomain is %sexample123%s", 'gravity-forms-infusionsoft'), "<span class='code' style='font-style:normal'><strong>", "</strong>", "</span>", "<strong class='code' style='font-style:normal;'>", "</strong>" );
            $message .= "</span>";

            $works = false;
        } elseif(empty($key)) {
            $message = wpautop( sprintf( esc_attr__('Your API Key is required, please %senter your API key below%s.', 'gravity-forms-infusionsoft'), '<label for="gf_infusionsoft_key"><a>', '</a></label>' ) );
            $works = false;
        } else {
            self::get_api();

            $app = Infusionsoft_AppPool::getApp();

            if(Infusionsoft_DataService::ping('ProductService')){

                try {
                    Infusionsoft_WebFormService::getMap($app);
                    $message .= wpautop(sprintf(esc_attr__("It works: everything is communicating properly and your settings are correct. Now go %sconfigure form integration with Infusionsoft%s!", "gravity-forms-infusionsoft"), '<a href="'.esc_url( admin_url('admin.php?page=gf_infusionsoft') ).'">', '</a>'));
                }
                catch(Exception $e){
                    $works = false;
                    if(strpos($e->getMessage(), "[InvalidKey]") !== FALSE){
                        $message .= wpautop(sprintf(esc_attr__('Your API Key is not correct, please double check your %sAPI key setting%s.', 'gravity-forms-infusionsoft'), '<label for="gf_infusionsoft_key"><a>', '</a></label>'));
                    }
                    else{
                        $message .= wpautop(sprintf(esc_attr__('Failure to connect: %s', 'gravity-forms-infusionsoft'), $e->error));
                    }
                }
            }
            else{
                $works = false;
                $message .= wpautop(esc_attr__('Something is wrong. See below for details, check your settings and try again.', 'gravity-forms-infusionsoft'));
            }

            $exceptions = Infusionsoft_AppPool::getApp()->getExceptions();

            if(!empty($exceptions)) {
                $message .= '<ul class="ul-square">';
                foreach($exceptions as $exception){
                    $messagetext = str_replace('[', esc_attr__('Error key: [', 'gravity-forms-infusionsoft'), str_replace(']', ']<br />Error message: ', $exception->getMessage()));
                    $message .= '<li style="list-style:square;">'.$messagetext.'</li>';
                }
                $message .= '</ul>';
            }
        }

        $class = empty($class) ? ($works ? "updated" : "error") : $class;

        if($message && $echo) {
            echo sprintf('<div id="message" class="%s">%s</div>', $class, wpautop($message));
        }

        return $works;
    }
	
	private static function get_address($entry, $field_id){
        $street_value = str_replace("  ", " ", trim($entry[$field_id . ".1"]));
        $street2_value = str_replace("  ", " ", trim($entry[$field_id . ".2"]));
        $city_value = str_replace("  ", " ", trim($entry[$field_id . ".3"]));
        $state_value = str_replace("  ", " ", trim($entry[$field_id . ".4"]));
        $zip_value = trim($entry[$field_id . ".5"]);
        $country_value = GFCommon::get_country_code(trim($entry[$field_id . ".6"]));

        $address = $street_value;
        $address .= !empty($address) && !empty($street2_value) ? "  $street2_value" : $street2_value;
        $address .= !empty($address) && (!empty($city_value) || !empty($state_value)) ? "  $city_value" : $city_value;
        $address .= !empty($address) && !empty($city_value) && !empty($state_value) ? "  $state_value" : $state_value;
        $address .= !empty($address) && !empty($zip_value) ? "  $zip_value" : $zip_value;
        $address .= !empty($address) && !empty($country_value) ? "  $country_value" : $country_value;

        return $address;
    }
	
	public static function display_plugin_message($message, $is_error = false){
        $style = '';
        if($is_error)
            $style = 'style="background-color: #ffebe8;"';

        echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
    }
	/* End from parent */
	
	static function entry_info_link_to_infusionsoft($form_id, $entry) {
        $leadsource_id = gform_get_meta($entry['id'], 'infusionsoft_leadsource_id');
        if(!empty($leadsource_id)) {
            echo sprintf(__('<p>Infusionsoft Lead Source ID: <a href="%s">Lead Source #%s</a></p>', self::$text_domain), self::get_leadsource_url($leadsource_id), $leadsource_id);
        }
    }

	public static function is_gravity_forms_installed($asd = '', $echo = true) {
        global $pagenow, $page; $message = '';

        $installed = 0;
        $name = self::$name;
        if(!class_exists('RGForms')) {
            if(file_exists(WP_PLUGIN_DIR.'/gravityforms/gravityforms.php')) {
                $installed = 1;
                $message .= sprintf( esc_attr__('%sGravity Forms is installed but not active. %sActivate Gravity Forms%s to use the %s plugin.%s', 'gravity-forms-infusionsoft' ), '<p>', '<strong><a href="'.wp_nonce_url(admin_url('plugins.php?action=activate&plugin=gravityforms/gravityforms.php'), 'activate-plugin_gravityforms/gravityforms.php').'">', '</a></strong>', esc_html( $name ),'</p>');
            } else {
                $message .= <<<EOD
<p><a href="https://katz.si/gravityforms?con=banner" title="Gravity Forms Contact Form Plugin for WordPress"><img src="http://gravityforms.s3.amazonaws.com/banners/728x90.gif" alt="Gravity Forms Plugin for WordPress" width="728" height="90" style="border:none;" /></a></p>
        <h3><a href="https://katz.si/gravityforms" target="_blank">Gravity Forms</a> is required for the $name</h3>
        <p>You do not have the Gravity Forms plugin installed. <a href="https://katz.si/gravityforms">Get Gravity Forms</a> today.</p>
EOD;
            }

            if(!empty($message) && $echo) {
                echo '<div id="message" class="updated">'.$message.'</div>';
            }
        } else {
            return true;
        }
        return $installed;
    }
	
	private static function is_infusionsoft_addon_installed($asd = '', $echo = true) {
        global $pagenow, $page; $message = '';

        $installed = 0;
        $name = self::$name;
        if(!class_exists('GFInfusionsoft')) {
            if(file_exists(WP_PLUGIN_DIR.'/infusionsoft/infusionsoft.php')) {
                $installed = 1;
                $message .= sprintf( esc_attr__('%Gravity Forms Infusionsoft Add-On is installed but not active. %sActivate Gravity Forms Infusionsoft Add-On%s to use the %s plugin.%s', self::$text_domain ), '<p>', '<strong><a href="'.wp_nonce_url(admin_url('plugins.php?action=activate&plugin=infusionsoft/infusionsoft.php'), 'activate-plugin_infusionsoft/infusionsoft.php').'">', '</a></strong>', esc_html( $name ),'</p>');
            } else {
                $message .= <<<EOD
        <h3><a href="https://wordpress.org/plugins/infusionsoft/" target="_blank">Gravity Forms Infusionsoft Add-On</a> is required for the $name</h3>
        <p>You do not have the Gravity Forms Infusionsoft Add-On plugin installed. <a href="https://wordpress.org/plugins/infusionsoft/">Get the Gravity Forms Infusionsoft Add-On</a> today.</p>
EOD;
            }

            if(!empty($message) && $echo) {
                echo '<div id="message" class="updated">'.$message.'</div>';
            }
        } else {
            return true;
        }
        return $installed;
    }
	
	public static function plugin_row(){
        if(!self::is_gravityforms_supported()){
            $message = sprintf(esc_html__("%sGravity Forms%s is required. %sPurchase it today!%s", 'gravity-forms-infusionsoft'), "<a href='https://katz.si/gravityforms'>", "</a>", "<a href='https://katz.si/gravityforms'>", "</a>");
            self::display_plugin_message($message, true);
        }
		if(!class_exists('GFInfusionsoft')) {
			$message = sprintf(esc_html__("%sGravity Forms Infusionsoft Add-On%s is required. %sGet it today!%s", 'gravityforms-infusionsoft-leadsource'), "<a href='https://wordpress.org/plugins/infusionsoft/'>", "</a>", "<a href='https://wordpress.org/plugins/infusionsoft/'>", "</a>");
			self::display_plugin_message($message, true);
		}
    }
	
	public static function get_api(){
        if(!class_exists("Infusionsoft_Classloader")) {
            require_once(WP_PLUGIN_DIR."/infusionsoft/Infusionsoft/infusionsoft.php");
		}

        self::$classLoader = new Infusionsoft_Classloader();
		
		if ( Infusionsoft_AppPool::getApp() === null ) {
			$infusionsoft_host = sprintf('%s.infusionsoft.com', GFInfusionsoft::get_setting('appname'));
			$infusionsoft_api_key = GFInfusionsoft::get_setting('key');
	
			//Below is just some magic...  Unless you are going to be communicating with more than one APP at the SAME TIME.  You can ignore it.
			Infusionsoft_AppPool::addApp(new Infusionsoft_App($infusionsoft_host, $infusionsoft_api_key, 443));
		}
    }

	private static $version = '1.06';
	private static $name = "LeadSources for Gravity Forms Infusionsoft Add-On";
	private static $text_domain = 'leadsources-for-gravityforms-infusionsoft';
	private static $path = "leadsources-for-gravityforms-infusionsoft/leadsources-for-gravityforms-infusionsoft.php";
	
	private static $landing_page_params = array();

    //Plugin starting point. Will load appropriate files
    public static function init(){
	    global $pagenow;

        load_plugin_textdomain( self::$text_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

        if($pagenow === 'plugins.php') {
			remove_action("admin_notices", array('GFInfusionsoft', 'is_gravity_forms_installed'), 10);
            add_action("admin_notices", array('GFInfusionsoftLeadsources', 'is_gravity_forms_installed'), 10);
        }
		
		if(self::is_infusionsoft_addon_installed(false, false) === 0){
			add_action('after_plugin_row_leadsources-for-gravityforms-infusionsoft/leadsources-for-gravityforms-infusionsoft.php', array('GFInfusionsoftLeadsources', 'plugin_row') );
           return;
        }

        if(self::is_gravity_forms_installed(false, false) === 0){
            remove_action('after_plugin_row_gravity-forms-infusionsoft/infusionsoft.php', array('GFInfusionsoft', 'plugin_row') );
			add_action('after_plugin_row_leadsources-for-gravityforms-infusionsoft/leadsources-for-gravityforms-infusionsoft.php', array('GFInfusionsoftLeadsources', 'plugin_row') );
           return;
        }

        if(!self::is_gravityforms_supported()){
           return;
        }
		
		if(is_admin()){
			// Enable debug with Gravity Forms Logging Add-on
            add_filter( 'gform_logging_supported', array( 'GFInfusionsoftLeadsources', 'add_debug_settings' ) );
        }

        if(self::is_infusionsoft_page()){
			$view = isset($_GET["view"]) ? $_GET["view"] : '';
			if($view == "edit") {
				// Add to the edit infusionsoft feed page
				add_action( 'admin_footer', array('GFInfusionsoftLeadsources', 'edit_page') );
			}
		} else if(in_array(RG_CURRENT_PAGE, array("admin-ajax.php"))){
			add_action('wp_ajax_gf_select_infusionsoft_form2', array('GFInfusionsoftLeadsources', 'select_infusionsoft_form'));
		} else {
            //handling post submission. (gform_post_submission deprecated)
			if ( isset( $_GET[ 'leadsources_for_gravityforms_infusionsoft_landing_page_parameters' ] ) ) {
				parse_str( base64_decode( $_GET[ 'leadsources_for_gravityforms_infusionsoft_landing_page_parameters' ] ), self::$landing_page_params );
				
				// Do not override existing $_GET parameters
				self::$landing_page_params = array_diff( self::$landing_page_params, $_GET );
				
				add_filter( 'gform_pre_submission', array('GFInfusionsoftLeadsources', 'populate_landing_page_parameters'));
			}
            add_action("gform_after_submission", array('GFInfusionsoftLeadsources', 'export'), 20, 2);
			
			add_action( 'wp_enqueue_scripts', array('GFInfusionsoftLeadsources', 'enqueue_scripts') );
			
			// This might enqueue scripts twice, but that is ok
			add_action( 'gform_enqueue_scripts', array('GFInfusionsoftLeadsources', 'enqueue_scripts') );
        }
		
		add_action('gform_entry_info', array('GFInfusionsoftLeadsources', 'entry_info_link_to_infusionsoft'), 10, 2);
	}
	
	public static function enqueue_scripts() {
		wp_enqueue_script( 'leadsources-for-gravityforms-infusionsoft-main', plugins_url( 'js/main.js', __FILE__ ), array( 'jquery' ), self::$version );
	}
	
	public static function populate_landing_page_parameters( $form ) {
		foreach( $form[ 'fields' ] as $field ) {
			if ( $field[ 'allowsPrepopulate' ] && isset( self::$landing_page_params[ $field[ 'inputName' ] ] ) && $_POST[ 'input_' . $field[ 'id' ] ] === '' ) {
				$_POST[ 'input_' . $field[ 'id' ] ] = GFFormsModel::get_parameter_value( $field['inputName'], self::$landing_page_params, $field );
			}
		}
	}
	
	/**
     * Enables debug with Gravity Forms logging add-on
     * @param array $supported_plugins List of plugins
     */
    public static function add_debug_settings( $supported_plugins ) {
        $supported_plugins['leadsources-for-gravityforms-infusionsoft'] = 'LeadSources for Gravity Forms Infusionsoft Add-on';
        return $supported_plugins;
    }
	
    public static function edit_page() {
		if(isset($_REQUEST['cache'])) {
            delete_site_transient('gf_infusionsoft_leadsource_default_fields');
            //delete_site_transient('gf_infusionsoft_leadsource_custom_fields');
        }
	?>
		<style type="text/css">
			#infusionsoft_leadsource_field_list { padding-left: 220px; }
			#infusionsoft_leadsource_field_list table { width: 500px; border-collapse: collapse; margin-top: 1em; }

			#infusionsoft_leadsource_field_list .left_header { margin-top: 1em; }
		</style>
		<script>
			(function($){ 

				$(document).ready(function() {
					$('a[href="https://katz.si/inhome"]').attr('href', 'http://infusionsoft.force.com/analytics?PartnerId=001j0000011SQBM&AffiliateCode=a69272&CampaignId=701j0000001mlwy&TrackingLinkId=a1Bj0000002JwD4&Link_Posted_By__c=005j000000Flk9k');

					$('#infusionsoft_field_list').after($('#infusionsoft_leadsource_template').html());
					
					var oldEndSelectForm = window.EndSelectForm;
					
					window.EndSelectForm = function(fieldList, form_meta) {
						var mysack = new sack("<?php echo esc_js( admin_url('admin-ajax.php') ); ?>" );
						mysack.execute = 1;
						mysack.method = 'POST';
						mysack.setVar( "action", "gf_select_infusionsoft_form2" );
						mysack.setVar( "gf_select_infusionsoft_form2", "<?php echo wp_create_nonce("gf_select_infusionsoft_form2") ?>" );
						mysack.setVar( "form_id", form_meta.id);
						mysack.onError = function() {$("#infusionsoft_wait").hide(); alert('<?php echo esc_js( __("Ajax error while selecting a form", "gravity-forms-infusionsoft") ); ?>' )};
						mysack.onCompletion = function() {
							oldEndSelectForm(fieldList, form_meta);
						};
						mysack.runAJAX();
						return true;
					};
					
					window.EndSelectForm2 = function(fieldList) {
						if ( fieldList ) {
							$('#infusionsoft_leadsource_field_list').html(fieldList);
							$('#infusionsoft_leadsource_field_list').slideDown();
						} else {
							$('#infusionsoft_leadsource_field_list').slideUp();
							$('#infusionsoft_leadsource_field_list').html("");
						}
					};
				});
			})(jQuery);
		</script>
	<?php
		
		//getting Infusionsoft API
        self::get_api();

        //getting setting id (0 when creating a new one)
        $id = !empty($_POST["infusionsoft_setting_id"]) ? $_POST["infusionsoft_setting_id"] : absint($_GET["id"]);
        $config = empty($id) ? array("meta" => array(), "is_active" => true) : GFInfusionsoftData::get_feed($id);

		//getting merge vars
        $merge_vars = array();

        //updating meta information
        if(isset($_POST["gf_infusionsoft_submit"])) {
			$config = GFInfusionsoftData::get_feed($id);
            $config["form_id"] = absint($_POST["gf_infusionsoft_form"]);

            $is_valid = true;

            $merge_vars = self::get_leadsource_fields();

            $leadsource_map = array();
            foreach($merge_vars as $key => $var){
                $field_name = "infusionsoft_map_leadsource_field_" . $var['tag'];
                if(isset($_POST[$field_name])) {
                    if(is_array($_POST[$field_name])) {
                        foreach($_POST[$field_name] as $k => $v) {
                            $_POST[$field_name][$k] = stripslashes($v);
                        }
                        $mapped_field = $_POST[$field_name];
                    } else {
                        $mapped_field = stripslashes($_POST[$field_name]);
                    }
                }
                if(!empty($mapped_field)){
                    $field_map[$var['tag']] = $mapped_field;
                }
                else{
                    unset($field_map[$var['tag']]);
                    if(!empty($var['req'])) {
                        $is_valid = false;
                    }
                }
                unset($_POST["{$field_name}"]);
            }

            $config["meta"]["leadsource_map"] = $field_map;
            if($is_valid){
                $id = GFInfusionsoftData::update_feed($id, $config["form_id"], $config["is_active"], $config["meta"]);
            }
        }
?>
		<script id="infusionsoft_leadsource_template" type="text/template"><div id="infusionsoft_leadsource_field_list">
                    <?php

					if(!empty($config["form_id"]) ) {
						if(empty($merge_vars))
							$merge_vars = self::get_leadsource_fields();

						//getting field map UI
						echo self::get_leadsource_field_mapping($config, $config['form_id'], $merge_vars);
					}

                    ?>
                    </div></script>
	<?php
	}
	
	private static function get_leadsource_field_mapping($config = array(), $form_id, $merge_vars){
        $str = $custom = $standard = '';

        //getting list of all fields for the selected form
        $form_fields = GFInfusionsoft::get_form_fields($form_id);

        $str = "<table cellpadding='0' cellspacing='0'><thead><tr><th scope='col' class='infusionsoft_col_heading'>" . esc_html__("Lead Source Fields", self::$text_domain) . "</th><th scope='col' class='infusionsoft_col_heading'>" . esc_html__("Form Fields", "gravity-forms-infusionsoft") . "</th></tr></thead><tbody>";


        foreach($merge_vars as $var){

            $selected_field = (isset($config["meta"]) && isset($config["meta"]["leadsource_map"]) && isset($config["meta"]["leadsource_map"][$var["tag"]])) ? $config["meta"]["leadsource_map"][$var["tag"]] : '';


            $field_list = self::get_mapped_leadsource_field_list($var["tag"], $selected_field, $form_fields);
            $name = stripslashes( $var["name"] );

            $required = $var["req"] === true ? "<span class='gfield_required' title='This field is required.'>*</span>" : "";
            $error_class = $var["req"] === true && empty($selected_field) && !empty($_POST["gf_infusionsoft_submit"]) ? " feeds_validation_error" : "";
            $field_desc = '';
            $row = "<tr class='$error_class'><th scope='row' class='infusionsoft_field_cell' id='infusionsoft_map_leadsource_field_{$var['tag']}_th'><label for='infusionsoft_map_leadsource_field_{$var['tag']}'>" . $name ." $required</label><small class='description' style='display:block'>{$field_desc}</small></th><td class='infusionsoft_field_cell'>" . $field_list . "</td></tr>";

            $str .= $row;

        } // End foreach merge var.

        $str .= "</tbody></table>";

        return $str;
    }
	
	private static function get_leadsource_fields() {
		$lists = array();

        $fields = get_site_transient('gf_infusionsoft_leadsource_default_fields');
        if(!empty($fields) && !isset($_REQUEST['cache'])) {
            $fields = maybe_unserialize($fields);
        } else {
            self::$classLoader->loadClass('LeadSource');
			self::$classLoader->loadClass('LeadSourceCategory');
            $LeadSource = new Infusionsoft_LeadSource();
            $fields = array_diff( $LeadSource->getFields(), array( 'Id', 'LeadSourceCategoryId' ) );
			
			$LeadSourceCategory = new Infusionsoft_LeadSourceCategory();
			$leadSourceCategoryFields = array_map( function($fieldName) {
				return 'Category' . $fieldName;
			}, array_diff( $LeadSourceCategory->getFields(), array( 'Id' ) ) );
			
			$fields = array_merge( $fields, $leadSourceCategoryFields );
			

            // Cache the results for two months; Infusionsoft says that their defaults won't change often.
            set_site_transient('gf_infusionsoft_leadsource_default_fields', maybe_serialize($fields), 60 * 60 * 24 * 60);
        }

        foreach($fields as $key => $field) {

            $lists[] = array(
                'name' => esc_js($field),
                'req' => false,
                'tag' => esc_js($field),
            );
        }
		/*
		// For If/When IS ever implements custom leadsource fields
        $custom_fields = get_site_transient('gf_infusionsoft_leadsource_custom_fields');
        if(!empty($custom_fields) && !isset($_REQUEST['cache'])) {
            $custom_fields = maybe_unserialize($custom_fields);
        } else {
			self::$classLoader->loadClass('LeadSource');
            $custom_fields = Infusionsoft_DataService::getCustomFields(new Infusionsoft_LeadSource());

            // Cache the results for one day; will change more often than not often...
            set_site_transient('gf_infusionsoft_leadsource_custom_fields', maybe_serialize($custom_fields), 60 * 60 * 24);
        }

        if(!empty($custom_fields)) {
            foreach($custom_fields as $key => $field) {

                if(!is_array($field)) { continue; }

                foreach($field as $k => $v) {

                    if(!is_a($v, 'Infusionsoft_DataFormField')) { continue; }

                    $lists[] = array(
                        'name' => esc_js($v->__get('Label')),
                        'req' => false,
                        'tag' => esc_js($v->__get('Name')),
                    );
                }
            }
        }
		*/

        return $lists;
	}
	
	public static function get_mapped_leadsource_field_list($variable_name, $selected_field, $fields){
        $field_name = "infusionsoft_map_leadsource_field_" . $variable_name;
        $str = "<select name='$field_name' id='$field_name'><option value=''></option>";
		foreach($fields as $field){
			$field_id = $field[0];
			$field_label = $field[1];
			$str .= "<option value='" . $field_id . "' ". selected(($field_id == $selected_field), true, false) . ">" . $field_label . "</option>";
		}
        $str .= "</select>";
        return $str;
    }
	
	public static function select_infusionsoft_form(){
        check_ajax_referer("gf_select_infusionsoft_form2", "gf_select_infusionsoft_form2");
        //error_reporting(0);
        $form_id =  intval($_POST["form_id"]);
        $setting_id =  0;

        // Not only test API, but include necessary files.
        $valid = self::test_api();
        if(empty($valid)) {
            die("EndSelectForm2();");
        }

        //getting list of all Infusionsoft merge variables for the selected app
        $merge_vars = self::get_leadsource_fields();

        //getting configuration
        $config = GFInfusionsoftData::get_feed($setting_id);

        //getting field map UI
        $str = self::get_leadsource_field_mapping($config, $form_id, $merge_vars);

        //$fields = $form["fields"];
        die("EndSelectForm2('" . str_replace("'", "\'", str_replace(")", "\)", $str)) . "');");
    }
	
	public static function export($entry, $form){
        GFInfusionsoft::log_debug( 'init export. Entry ID: ' . $entry['id'] );

        //Login to Infusionsoft
        $api = self::get_api();
        if(!empty($api->lastError)) {
            GFInfusionsoft::log_debug( 'Infusionsoft API Error: ' . print_r( $api->lastError, true ) );
            return;
        }

        //getting all active feeds
        $feeds = GFInfusionsoftData::get_feed_by_form($form["id"], true);
        foreach($feeds as $feed){
            //Always export the user
            self::export_feed($entry, $form, $feed, $api);
        }
    }

    public static function export_feed($entry, $form, $feed, $api){
		// Old version
		if(!function_exists('gform_get_meta')) { return; }
		
		$contact_id = gform_get_meta($entry['id'], 'infusionsoft_id');
		
		if ( $contact_id === false ) {
			return;
		}
		
		$merge_vars = array();
		foreach($feed["meta"]["leadsource_map"] as $var_tag => $field_id){
			$field = RGFormsModel::get_field($form, $field_id);
			$input_type = RGFormsModel::get_input_type($field);

			if( $field_id == intval($field_id) && RGFormsModel::get_input_type($field) == "address") {
				//handling full address
				$merge_vars[$var_tag] = self::get_address($entry, $field_id);
				$merge_vars[$var_tag] = self::clean_utf8( $merge_vars[$var_tag] );

			} elseif ( $input_type === 'date' && !empty( $entry[$field_id] ) ) {
				$original_timezone = date_default_timezone_get();
				date_default_timezone_set('America/New_York');
				$date = strtotime($entry[$field_id]);
				$date = date('Ymd\TH:i:s', $date);
				date_default_timezone_set($original_timezone);

				$merge_vars[$var_tag] = $date;
			} elseif ( $input_type === 'radio' && isset( $entry[ $field_id ] ) ) {

				// Radio buttons are sent to infusionsoft as strings by default.
				$merge_vars[$var_tag] = apply_filters( 'gf_infusionsoft_radio_value', $entry[ $field_id ], $field_id );

				// Yes/No fields in infusionsoft only work with integer
				if( in_array( $merge_vars[$var_tag], array( '0', '1') ) ) {
					$merge_vars[$var_tag] = (int)$merge_vars[$var_tag];
				}

			} elseif ( $input_type === 'number' && isset( $entry[ $field_id ] ) ) {
				$merge_vars[$var_tag] = (float)$entry[ $field_id ];

			} else if( $var_tag != "EMAIL" ) { //ignoring email field as it will be handled separatelly
				$merge_vars[$var_tag] = $entry[$field_id];
				$merge_vars[$var_tag] = self::clean_utf8( $merge_vars[$var_tag] );
			}
			
			if ( strval($merge_vars[$var_tag]) === '' ) {
				unset( $merge_vars[$var_tag] );
			}
		}
		
		$category_data = array();
		if ( isset( $merge_vars['CategoryName'])) {
			$category_data['Name'] = $merge_vars['CategoryName'];
			unset($merge_vars['CategoryName']);
		}
		if ( isset( $merge_vars['CategoryName'])) {
			$category_data['Description'] = $merge_vars['CategoryDescription'];
			unset($merge_vars['CategoryDescription']);
		}
		
		if ( count( $merge_vars ) === 0 ) {
			// Nothing to do! Blank lead source...
			return;
		}
		
		if ( !isset( $merge_vars['Name'] ) ) {
			$merge_vars['Name'] = ( isset( $merge_vars['Source'] ) ? $merge_vars['Source'] . ' - ' : '' ) . ( isset( $merge_vars['Medium'] ) ? $merge_vars['Medium'] : '' );
			if (strlen($merge_vars['Name']) === 0) {
				// We will create a static one later, since it might exist from previous manual input ( but would not have our name! )
				unset( $merge_vars['Name'] );
			}
		}
		
		if ( count( $category_data ) > 0 ) {
			$leadsource_category_id = self::get_leadsource_category_id($category_data);
			if ( empty( $leadsource_category_id ) ) {
				GFInfusionsoft::log_debug( '[Entry ID: '. $entry['id'] . '] Infusionsoft Lead Source Category Merge Data: ' . print_r( $category_data, true ) );
				$leadsource_category_id = self::add_leadsource_category( $category_data );
			} else {
				$leadsource_category_id = $leadsource_category_id[0]->Id;
			}
			
			$merge_vars['LeadSourceCategoryId'] = $leadsource_category_id;
		}
		
		$leadsource = self::get_leadsource($merge_vars);
		if ( empty( $leadsource ) ) {
			GFInfusionsoft::log_debug( '[Entry ID: '. $entry['id'] . '] Infusionsoft Lead Source Merge Data: ' . print_r( $merge_vars, true ) );
			$merge_vars['Status'] = 'Active';
			if ( !isset( $merge_vars['Name'] ) ) {
				$merge_vars['Name'] = 'Auto-Generated by LeadSources for Gravity Forms Infusionsoft Add-On';
			}
			$leadsource = array_merge( $merge_vars );
			$leadsource['Id'] = self::add_leadsource($merge_vars);
		} else {
			$leadsource = $leadsource[0]->toArray();
		}
		
		$valid = self::test_api();

		if($valid) {
			self::update_contact( $contact_id, array( 'LeadSourceId' => $leadsource['Id'], 'Leadsource' => $leadsource['Name'] ) );

			GFInfusionsoft::log_debug( '[Entry ID: '. $entry['id'] . '] Contact ID: ' . print_r( $contact_id, true ) );

			if(GFInfusionsoft::is_debug()) {
				echo '<h3>'.esc_html__('Admin-only Form Debugging', 'gravity-forms-infusionsoft').'</h3>';
				self::r(array(
						'Infusionsoft Posted Lead Source Category Merge Data' => $category_data,
						'Infusionsoft Posted Lead Source Merge Data' => $merge_vars,
						'Lead Source Category ID' => isset( $merge_vars['LeadSourceCategoryId'] ) ? $merge_vars['LeadSourceCategoryId'] : 'none',
						'Lead Source ID' => isset( $leadsource['Id'] ) ? $leadsource['Id'] : 'none'
				));
			}

			self::add_note($entry, $leadsource);
	   }
	}
	
	static function add_note($entry, $leadsource) {
		global $current_user;

		// Old version
		if(!function_exists('gform_update_meta')) { return; }

		@RGFormsModel::add_note($entry['id'], $current_user->ID, $current_user->display_name, stripslashes(sprintf(__('Assigned Infusionsoft LeadSource: %s. View this LeadSource at %s', 'gravity-forms-addons', self::$text_domain), $leadsource['Name'], self::get_leadsource_url($leadsource['Id']))));
		
		@gform_update_meta($entry['id'], 'infusionsoft_leadsource_id', $leadsource['Id']);

	}
	
	static function get_leadsource_url($leadsource_id) {
		return add_query_arg(array('view' => 'edit', 'ID' => $leadsource_id), 'https://'.GFInfusionsoft::get_setting('appname').'.infusionsoft.com/LeadSource/manageLeadSource.jsp');
	}
	
	static private function get_leadsource($leadsource_data) {
		self::$classLoader->loadClass('DataService');

        $DataService = new Infusionsoft_DataService();

        return $DataService->queryWithOrderBy(new Infusionsoft_LeadSource(), $leadsource_data, 'Id', false, 1, 0, ['CostPerLead', 'Description', 'EndDate', 'Id', 'LeadSourceCategoryId', 'Medium', 'Message', 'Name', 'StartDate', 'Status', 'Vendor']);
	}
	
	static private function get_leadsource_category_id($category_data) {
		self::$classLoader->loadClass('DataService');

        $DataService = new Infusionsoft_DataService();

        return $DataService->queryWithOrderBy(new Infusionsoft_LeadSourceCategory(), $category_data, 'Id', false, 1, 0, ['Id']);
	}
	
	static private function add_leadsource_category($data) {
	    self::$classLoader->loadClass('DataService');

        $DataService = new Infusionsoft_DataService();
		
		$leadSourceCategory = new Infusionsoft_LeadSourceCategory();
		$leadSourceCategory->loadFromArray($data, true);

        return $DataService->save($leadSourceCategory);
	}
	
	static private function add_leadsource($data) {
	    self::$classLoader->loadClass('DataService');

        $DataService = new Infusionsoft_DataService();
		
		$leadSource = new Infusionsoft_LeadSource();
		$leadSource->loadFromArray($data, true);

        return $DataService->save($leadSource);
	}
	
	static private function update_contact($contact_id, $merge_vars) {
        self::$classLoader->loadClass('ContactService');

        $ContactService = new Infusionsoft_ContactService();
		
        return $ContactService->update($contact_id, $merge_vars);
    }
}

add_action('init',  array('GFInfusionsoftLeadsources', 'init'), 11);
