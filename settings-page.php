<?php

namespace FsrImporter;

const SETTINGS_SECTION = 'importer_settings';

// Settings main group settings.
const SETTINGS_MAIN_OPTION_GROUP   = 'importer_main_group';
const SETTING_ACTIVATION_PAGE      = 'importer_activation_page';
const SETTING_QUALIFICATION_PAGE   = 'importer_qualification_page';
const SETTING_POST_ACTIVATION_PAGE = 'importer_post_activation_page';

// Activation group settings.
const SETTINGS_ACTIVATION_OPTION_GROUP = 'importer_activation_group';
const SETTING_ACTIVIATION_EMAIL        = 'importer_act_email';
const SETTING_ACTIVIATION_SUBJECT      = 'importer_act_subject';
const SETTING_ACTIVIATION_BODY         = 'importer_act_body';

use const \WpSkyloader\PostTypes\DistributorAccount\FIELD_SKYLOADER_ERROR;
use const \WpSkyloader\PostTypes\DistributorAccount\FIELD_HISTORY;
use const \WpSkyloader\PostTypes\DistributorAccount\ACCOUNT_LINK_NO_ERROR;
use const \WpSkyloader\PostTypes\DistributorAccount\ACCOUNT_LINK_ERROR;


/**
 * WordPress hook to setup the settings page.
 */
add_action( 'admin_menu', '\FsrImporter\settings_page' );
function settings_page(  )
{
    add_options_page(
        'BI Accounts',
        'BI Accounts',
        'manage_options',
        'importer-options-page',
        '\FsrImporter\options_page_html'
    );
}


/**
 * Render the options page and its tabs.
 */
function options_page_html(  )
{
    $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings';

    ?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <a href="?page=importer-options-page&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=importer-options-page&tab=upload" class="nav-tab <?php echo $active_tab == 'upload' ? 'nav-tab-active' : ''; ?>">Upload Users</a>
            <a href="?page=importer-options-page&tab=export" class="nav-tab <?php echo $active_tab == 'export' ? 'nav-tab-active' : ''; ?>">Export Users</a>
            <a href="?page=importer-options-page&tab=notify" class="nav-tab <?php echo $active_tab == 'notify' ? 'nav-tab-active' : ''; ?>">Activation Notification</a>
            <a href="?page=importer-options-page&tab=update" class="nav-tab <?php echo $active_tab == 'stats' ? 'nav-tab-active' : ''; ?>">Update Uploaded Data</a>
            <a href="?page=importer-options-page&tab=stats" class="nav-tab <?php echo $active_tab == 'stats' ? 'nav-tab-active' : ''; ?>">Statistics</a>
        </h2>
        <?php

        if ( $active_tab == 'settings' ) {
            settings_tab_html();
        }
        else if( $active_tab == 'upload') {
           user_upload_tab_html();
        }
        else if( $active_tab == 'export') {
           export_tab_html();
        }
        else if ( $active_tab == 'notify' ) {
           notify_tab_html();
        }
        else if ( $active_tab == 'stats' ) {
           stats_tab_html();
        }
        else if ( $active_tab == 'update' ) {
           update_tab_html();
        }
        ?>
    </div>
    <?php
}


/**
 * Display the HTML for the plugins settings.
 */
function settings_tab_html(  )
{
    ?>
    <form action='options.php' method='post'>
        <?php
        settings_fields( SETTINGS_MAIN_OPTION_GROUP );
        do_settings_sections( SETTINGS_MAIN_OPTION_GROUP );
        submit_button();
        ?>
    </form>
    <?php
}


/**
 * Display the HTML for the export tab.
 */
function export_tab_html(  )
{
    $results      = null;
    $import_group = null;

    if ( isset( $_POST['import_group'] ) ) {
        $import_group = $_POST['import_group'];
    }

    if ( $import_group ) {
        $results = get_csv_text_for_export( $import_group );
    }

    ?>
    <h2>Create CSV for an Imported User Group</h2>

    <div>
        This tab will allow you to create a CSV of user activation links for an imported user group.
    </div>

    <form method="post" action="" name="myform" enctype="multipart/form-data">
        <label for="import_group">Import Group Name</label>
        <input type="text" id="import_group" name="import_group" value="<?php echo $import_group; ?>" required>

        <?php submit_button('Generate CSV'); ?>
    </form>
    <?php

    if ( !empty( $results ) ) {
        ?>
        <textarea rows="20" cols="100" readonly><?php echo $results;?></textarea>
        <?php
    }
}


/**
 * Get a string of CSV content for the export of a import_group.
 */
function get_csv_text_for_export( string $import_group ): string
{
    $csv   = "first_name,last_name,email,fsr_id,activation_link\n";

    $users = get_users( array(
        'number'     => -1,
        'meta_key'   => 'import_group',
        'meta_value' => $import_group,
    ) );

    foreach ( $users as $user ) {

        $activation_link = \FsrImporter\get_activation_link( $user );
        $fsr_id          = get_user_meta( $user->ID, 'operator_fsr_id', true );

        $csv .= $user->first_name . ',';
        $csv .= $user->last_name . ',';
        $csv .= $user->user_email . ',';
        $csv .= $fsr_id . ',';
        $csv .= $activation_link . "\n";
    }

    return $csv;
}


/**
 * Display the HTML for uploading a User CSV file.
 */
function user_upload_tab_html(  )
{
    $import_group = null;
    $results = null;

    if ( isset( $_POST['import_group'] ) ) {
        $import_group = $_POST['import_group'];
    }

    if ( isset( $_POST['submit'] ) ) {

        if ( $_FILES['file']['name'] != '' ) {

            $uploaded_file     = $_FILES['file'];
            $upload_overrides = array( 'test_form' => false );
            $upload_result    = wp_handle_upload( $uploaded_file, $upload_overrides );
            $file_path        = null;

            if ( $upload_result && ! isset( $upload_result['error'] ) ) {

                $file_path = $upload_result['file'];
                $results = \FsrImporter\upload_from_csv( $file_path, $import_group);

                // Remove the file, it's no longer needed on the server.
                @unlink( $file_path );
            }
            else
            {
                $results = $upload_result['error'];
            }
        }
    }

    ?>
    <h2>Upload Merchant Account CSV File</h2>

    <div>
        The CSV is expected to have these columns, in any order: OPERATOR_FIRST_NAME, OPERATOR_LAST_NAME, OPERATOR_EMAIL, OPERATOR_BUSINESS_NAME, OPERATOR_ADDRESS_STREET, OPERATOR_ADDRESS_CITY, OPERATOR_ADDRESS_STATE, OPERATOR_ADDRESS_POSTALCODE, OPERATOR_PHONE, DATE_OPERATOR_SELF_QUALIFIED, DATE_FSR_VERIFIED, OPERATOR_FSR_ID, SEGMENT, SUB_SEGMENT, OPERATOR TITLE
    </div>

    <form method="post" action="" name="myform" enctype="multipart/form-data">
        <br/>
        <table>
            <tr>
                <td>Import Group Name</td>
                <td>
                    <input type="text" id="import_group" name="import_group" value="<?php echo $import_group; ?>" required>
                </td>
            </tr>
            <tr>
                <td>Upload file</td>
                <td><input type="file" name="file"></td>
            </tr>
        </table>
        <?php submit_button('Upload File'); ?>
    </form>

    <?php

    if ( !empty( $results ) ) {
        ?>
        <textarea rows="20" cols="100" readonly><?php echo $results;?></textarea>
        <?php
    }
}


/**
 * The HTML for the activation notificiation tab.
 */
function notify_tab_html(  ) {
    ?>
    <form action='options.php' method='post'>
        <?php
        settings_fields( SETTINGS_ACTIVATION_OPTION_GROUP );
        do_settings_sections( SETTINGS_ACTIVATION_OPTION_GROUP );
        submit_button();
        ?>
    </form>
    <?php
}


/**
 * Display the HTML for Updating User Meta Brand Abbr Data
 * This was created post 1st round user upload due to
 * Skyloader API expecting differect Brand Abbr then what was previously listed
 */
function update_tab_html(  )
{
    $count_updated = 0;

    if ( isset( $_POST['submit'] ) && $_POST['submit'] == 'Update BE Brand Code' ){
        $users = get_users( );
        foreach ( $users as $user ){
            $sub_segment = get_user_meta($user->ID, "sub_segment", true );
            $be_brand_abbr = \FsrImporter\find_be_brand_abbr( $sub_segment );
            update_user_meta( $user->ID, "be_brand_abbr", $be_brand_abbr );
        }
    }
    else if ( isset( $_POST['submit'] ) && $_POST['submit'] == 'Add Zero To Short Zipcodes' ){
        // Some users were imported with zipcodes that had the leading zeroes removed.
        // This migration adds leading zeroes to any zipcode that is not empty and less than
        // 5 characters long.
        $users = get_users( );
        foreach ( $users as $user ){
            $zip = get_user_meta($user->ID, "operator_address_postalcode", true );

            if ( $zip && strlen( $zip ) < 5 ) {
                $zip = str_pad( $zip, 5, '0', STR_PAD_LEFT );
                update_user_meta($user->ID, "operator_address_postalcode", $zip );
                $count_updated++;
            }
        }
    }
    else if ( isset( $_POST['submit'] ) && $_POST['submit'] == 'Fix Error Link Status' ){
        // Some Distrubutor Accounts Verify Credentials were returning with an error but
        // did not update the correct field
        // This migration updates the correct Field byt getting the wrong feilds error status and updating it

        $accounts = get_posts( array(
            'numberposts' => -1,
            'post_type'   => \WpSkyloader\PostTypes\DistributorAccount\SLUG,
            'orderby'     => 'title',
            'order'       => 'ASC',
        ) );
        foreach ( $accounts as $account ) {
            $account_id  = $account->ID;
            $history     = get_post_meta( $account_id, FIELD_HISTORY, true );
            $history_log = explode( "\n", $history );
            $last_log    = array_slice( $history_log, -1 );

            $has_error = false;
            if ( array_key_exists( 0, $last_log ) ) {
                $has_error = has_error_message( $last_log[0] );
            }

            if( $has_error ){
                update_post_meta( $account_id, FIELD_LINK_ERROR_STATUS, ACCOUNT_LINK_ERROR );
            }
            else {
                update_post_meta( $account_id, FIELD_LINK_ERROR_STATUS, ACCOUNT_LINK_NO_ERROR );
            }
        }
    }
    else if ( isset( $_POST['submit'] ) && $_POST['submit'] == 'Get all DCNs' ) {
        $aggregator = new \FsrImporter\Errors\ErrorAggregation();
        $aggregator->intialize();
        $aggregator->sendErrorEmailsToUsers();
    }
    else if ( isset( $_POST['submit'] ) && $_POST['submit'] == 'Send Test Errors Email' ) {
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory;
        $factory->readYamlFile();

        $errors[] = $factory->createError( ':invalid-password' );
        $errors[0]->setDistributorName( 'US Foods' );
        $errors[0]->setDCN( '111' );

        $errors[] = $factory->createError( ':password-reset-required' );
        $errors[1]->setDistributorName( 'US Foods' );
        $errors[1]->setDCN( '2222222222' );

        $errors[] = $factory->createError( ':click-location-link' );
        $errors[2]->setDistributorName( 'US Foods' );
        $errors[2]->setDCN( '33333333333333' );

        $errors[] = $factory->createError( ':account-disabled' );
        $errors[3]->setDistributorName( 'US Foods' );
        $errors[3]->setDCN( '4444444' );

        $errors[] = $factory->createError( ':account-locked' );
        $errors[4]->setDistributorName( 'US Foods' );
        $errors[4]->setDCN( '5555555555' );

        $errors[] = $factory->createError( ':missing-permissions' );
        $errors[5]->setDistributorName( 'US Foods' );
        $errors[5]->setDCN( '666666' );

        $errors[] = $factory->createError( ':requires-user-agreement' );
        $errors[6]->setDistributorName( 'US Foods' );
        $errors[6]->setDCN( '777777777777777777777' );


        $email = new \FsrImporter\Errors\ErrorEmail( $errors );
        $email->send( 'jay@clockwork.com' );
        $email->send( 'toth@clockwork.com' );
        $email->send( 'clockworkred@litmustest.com' );
    }
    else if ( isset( $_POST['submit'] ) && $_POST['submit'] == 'Send Test Error Email' ) {
        $factory = new \FsrImporter\Errors\SkyloaderErrorFactory;
        $factory->readYamlFile();

        $errors[] = $factory->createError( ':invalid-password' );
        $errors[0]->setDistributorName( 'US Foods' );
        $errors[0]->setDCN( 'oneoneoneoneone' );

        $email = new \FsrImporter\Errors\ErrorEmail( $errors );
        $email->send( 'jay@clockwork.com' );
        $email->send( 'toth@clockwork.com' );
        $email->send( 'clockworkred@litmustest.com' );
    }

    ?>
    <form action="" method="post">
        <?php
        submit_button( 'Update BE Brand Code' );

        submit_button( 'Add Zero To Short Zipcodes' );

        submit_button( 'Fix Error Link Status' );

        submit_button( 'Get all DCNs' );

        submit_button( 'Send Test Error Email' );

        submit_button( 'Send Test Errors Email' );
        ?>
    </form>
    <?php

    if ( $count_updated ) {
        echo "<p>Updated {$count_updated} short Zipcodes.</p>";
    }
}


function has_error_message( $message )
{
    if ( strpos($message, 'Error Validating credentials') !== false ) {
        return true;
    }

    if ( strpos($message, 'failed') !== false ) {
        return true;
    }

    if ( strpos($message, ':invalid-password') !== false ) {
        return true;
    }

    if ( strpos($message, ':password-reset-required') !== false ) {
        return true;
    }

    if ( strpos($message, ':click-location-link') !== false ) {
        return true;
    }
    return false;
}


/**
 * The HTML for the statistics  tab.
 */
function stats_tab_html(  )
{
    echo "<h2>WordPress User Account Counts</h2>";
    echo_activation_count();
    echo_import_group_count( 'group-a' );
    echo_import_group_count( 'group-b' );
    echo_import_group_count( 'group-c' );
    echo_import_group_count( 'group-2a' );
    echo_import_group_count( 'group-2b' );
    echo "<br/>";
    echo "<h2>Distributor Account Counts</h2>";
    echo_login_creds_count();
    echo_accounts_with_error();
    echo_accounts_linked();
}


function echo_login_creds_count(  ): void
{
    $query = new \WP_Query( [
        'numberposts' => -1,
        'post_type'   => \WpSkyloader\PostTypes\DistributorAccount\SLUG,
        'meta_query'  => [
            [
                'key'     => \WpSkyloader\PostTypes\DistributorAccount\FIELD_CREDS_PROVIDED_DATE,
                'compare' => '!=',
                'value'   => '',
            ],
        ],
    ] );

    $count = $query->found_posts;

    echo "<div>With login creds: <b>${count}</b></div>";
}


function echo_accounts_with_error(  ): void
{
    $query = new \WP_Query( [
        'numberposts' => -1,
        'post_type'   => \WpSkyloader\PostTypes\DistributorAccount\SLUG,
        'meta_key'    => FIELD_SKYLOADER_ERROR,
        'meta_value'  => \WpSkyloader\PostTypes\DistributorAccount\ACCOUNT_LINK_ERROR,
    ] );

    $count = $query->found_posts;

    echo "<div>With an error: <b>${count}</b></div>";
}


function echo_accounts_linked(  ): void
{
    $query = new \WP_Query( [
        'numberposts' => -1,
        'post_type'   => \WpSkyloader\PostTypes\DistributorAccount\SLUG,
        'meta_query'  => [
            [
                'key'     => \WpSkyloader\PostTypes\DistributorAccount\FIELD_SCHEDULER_ID,
                'compare' => '!=',
                'value'   => '',
            ],
        ],
    ] );

    $count = $query->found_posts;

    echo "<div>Linked: <b>${count}</b></div>";
}


function echo_activation_count(  ): void
{
    $query = new \WP_User_Query( [
        'meta_query' => [
            [
                'key'     => 'activation_date',
                'compare' => '!=',
                'value'   => '',
            ],
        ],
    ] );

    $count = $query->total_users;
    echo "<div>Activated: <b>${count}</b></div>";
}


function echo_import_group_count( string $group_name ): void
{
    $query = new \WP_User_Query( [
        'meta_query' => [
            'relation'      => 'AND',
            [
                'key'     => 'activation_date',
                'compare' => '!=',
                'value'   => '',
            ],
            [
                'key'     => 'import_group',
                'compare' => '==',
                'value'   => $group_name,
            ],
        ],
    ] );

    $count = $query->total_users;
    echo "<div>Activated for import '${group_name}': <b>${count}</b></div>";
}


/**
 * WordPress hook to setup the plugin's main settings fields.
 */
add_action( 'admin_init', '\FsrImporter\settings_init' );
function settings_init(  )
{
    register_setting( SETTINGS_MAIN_OPTION_GROUP, SETTINGS_MAIN_OPTION_GROUP );
    add_settings_section(
        SETTINGS_SECTION,
        'Importer Settings',
        '\FsrImporter\settings_section_callback',
        SETTINGS_MAIN_OPTION_GROUP
    );

    add_settings_field(
        SETTING_ACTIVATION_PAGE,
        'Activation Page Slug',
        '\FsrImporter\text_field_activation_page_render',
        SETTINGS_MAIN_OPTION_GROUP,
        SETTINGS_SECTION
    );

    add_settings_field(
        SETTING_POST_ACTIVATION_PAGE,
        'Post Activation Page Slug',
        '\FsrImporter\text_field_post_activation_page_render',
        SETTINGS_MAIN_OPTION_GROUP,
        SETTINGS_SECTION
    );

    add_settings_field(
        SETTING_QUALIFICATION_PAGE,
        'Qualification Page Slug',
        '\FsrImporter\text_field_qualification_page_render',
        SETTINGS_MAIN_OPTION_GROUP,
        SETTINGS_SECTION
    );
}



/**
 * Setup the settings group and fields for activation notification email settings.
 */
add_action( 'admin_init', '\FsrImporter\activation_notification_settings_init' );
function activation_notification_settings_init(  )
{
    register_setting( SETTINGS_ACTIVATION_OPTION_GROUP, SETTINGS_ACTIVATION_OPTION_GROUP );
    add_settings_section(
        SETTINGS_SECTION,
        'Activation Notification Email Settings',
        '\FsrImporter\settings_section_callback',
        SETTINGS_ACTIVATION_OPTION_GROUP
    );

    add_settings_field(
        SETTING_ACTIVIATION_EMAIL,
        'Account Activation Notification Email',
        '\FsrImporter\text_field_activation_email_render',
        SETTINGS_ACTIVATION_OPTION_GROUP,
        SETTINGS_SECTION
    );

    add_settings_field(
        SETTING_ACTIVIATION_SUBJECT,
        'Activation Email Subject',
        '\FsrImporter\text_field_activation_subject_render',
        SETTINGS_ACTIVATION_OPTION_GROUP,
        SETTINGS_SECTION
    );

    add_settings_field(
        SETTING_ACTIVIATION_BODY,
        'Activation Email Body',
        '\FsrImporter\text_field_activation_body_render',
        SETTINGS_ACTIVATION_OPTION_GROUP,
        SETTINGS_SECTION
    );
}



/**
 * This function is required for a section. We have no need for it so we return nothing.
 */
function settings_section_callback(  )
{
    echo '';
}


/**
 * Render the user acivation page field.
 */
function text_field_activation_page_render(  )
{
    $slug = get_setting( SETTINGS_MAIN_OPTION_GROUP, SETTING_ACTIVATION_PAGE );
    ?>
    <input type="text" name="<?php echo SETTINGS_MAIN_OPTION_GROUP;?>[<?php echo SETTING_ACTIVATION_PAGE; ?>]" value="<?php echo $slug; ?>">
    <p><i>The slug of the page that will be the activation landing page.</i></p>
    <?php
}


/**
 * Render the activation email address
 */
function text_field_activation_email_render(  ): void
{
    $email = get_setting( SETTINGS_ACTIVATION_OPTION_GROUP, SETTING_ACTIVIATION_EMAIL );
    ?>
    <input type="text" size="75" name="importer_activation_group[<?php echo SETTING_ACTIVIATION_EMAIL; ?>]" value="<?php echo $email; ?>">
    <p><i>The email address that will be notified when a user account is activated.</i></p>
    <?php
}


/**
 * Render the activation email subject line.
 */
function text_field_activation_subject_render(  ): void
{
    $subject = get_setting( SETTINGS_ACTIVATION_OPTION_GROUP, SETTING_ACTIVIATION_SUBJECT );
    ?>
    <input type="text" size="100" name="importer_activation_group[<?php echo SETTING_ACTIVIATION_SUBJECT; ?>]" value="<?php echo $subject; ?>">
    <p><i>The subject line that will be used for the account activiation email.</i></p>
    <?php
}


/**
 * Render the activation email body.
 */
function text_field_activation_body_render(  ): void
{
    $body = get_setting( SETTINGS_ACTIVATION_OPTION_GROUP, SETTING_ACTIVIATION_BODY );
    ?>
    <textarea rows="20" cols="100" name="importer_activation_group[<?php echo SETTING_ACTIVIATION_BODY; ?>]"><?php echo $body;?></textarea>
    <p><i>The body of the activation email. User account information will be appened to this body.</i></p>
    <?php
}


/**
 * Render the post activation page field.
 */
function text_field_post_activation_page_render(  ): void
{
    $slug = get_setting( SETTINGS_MAIN_OPTION_GROUP, SETTING_POST_ACTIVATION_PAGE );
    ?>
    <input type="text" name="<?php echo SETTINGS_MAIN_OPTION_GROUP;?>[<?php echo SETTING_POST_ACTIVATION_PAGE; ?>]" value="<?php echo $slug; ?>">
    <p><i>The slug of the page that will be displayed after a user activates their account.<br/>For example: dashboard</i></p>
    <?php
}


/**
 * Render the self qualification page field.
 */
function text_field_qualification_page_render(  ): void
{
    $slug = get_setting( SETTINGS_MAIN_OPTION_GROUP, SETTING_QUALIFICATION_PAGE );
    ?>
    <input type="text" name="<?php echo SETTINGS_MAIN_OPTION_GROUP;?>[<?php echo SETTING_QUALIFICATION_PAGE; ?>]" value="<?php echo $slug; ?>">
    <p><i>The slug of the page that will be the qualification landing page.</i></p>
    <?php
}


/**
 * Get the value of a plugin setting.
 */
function get_setting( string $option_group, string $setting_key ) : ?string
{
    $options = get_option( $option_group );

    if ( !$options ) {
        return null;
    }

    if ( array_key_exists( $setting_key, $options ) ) {
        return $options[$setting_key];
    }

    return null;
}


/**
 * Return the full URL of the dashboard based on the plugin settings.
 */
function get_dashboard_url(  ): string
{
    $terms_slug = \FsrImporter\get_setting( SETTINGS_MAIN_OPTION_GROUP, SETTING_POST_ACTIVATION_PAGE );
    $result     = get_permalink( get_page_by_path( $terms_slug ) );

    return $result;
}