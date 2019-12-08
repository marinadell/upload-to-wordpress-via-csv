<?php

namespace FsrImporter;

const SEGMENT_MAPPING = [
    'airline catering'                                           => 'BE',
    'business and industry (serving food to employees)'          => 'BE',
    'casino & gaming'                                            => 'BE',
    'bakery'                                                     => 'DA',
    'bars/taverns'                                               => 'DA',
    'casual/theme/dinner house'                                  => 'DA',
    'caterers (food preparation for off-site events)'            => 'DA',
    'college and university'                                     => 'DA',
    'fine dining/white tablecloth restaurant'                    => 'DA',
    'limited/quick service'                                      => 'DA',
    'convenience store'                                          => 'S1P',
    'hospitals'                                                  => 'PAC',
    'long term care/senior living'                               => 'PAC',
    'midscale/family restaurant/coffee shop/cafeteria'           => 'DA',
    'military (serving food to service members)'                 => 'S1P',
    'motel/hotel/resort/lodging'                                 => 'S1P',
    'other onsite (other non-restaurant foodservices)'           => 'BE',
    'other retail (food served at general merchandise stores)'   => 'S1P',
    'recreation (golf/country club, camp, stadiums)'             => 'BE',
    'supermarket deli'                                           => 'S1P',
    'vending/office coffee service (servicing vending machines)' => 'S1P',
    'corrections (serving food to correctional inmates)'         => 'S1P',
    'cruises'                                                    => 'S1P',
];

const VALID_OPERATOR_TITLE = [
    "Accounting",
    "Administrative",
    "Area Food Service Supervisor",
    "Area Supervisor School Nutrition",
    "Assistant Superintendent",
    "Cafeteria Worker",
    "CEO/President",
    "CFO",
    "Chef",
    "Contract Manager",
    "Contract Specialist",
    "COO",
    "Co-owner",
    "CPO",
    "Deputy Superintendent",
    "Director of Finance",
    "Director of Operations",
    "Director of Purchasing/Supply Chain",
    "Director of School Nutrition",
    "Distributor Operations Manager",
    "Executive",
    "Executive Chef/Culinary",
    "Food & Beverage Manager",
    "Food Service Director",
    "Food Service Manager",
    "Food Service Worker",
    "General Manager",
    "Kitchen Manager",
    "Management",
    "Manager",
    "Manager of School Nutrition",
    "Nutritionist",
    "Other",
    "Owner",
    "Owner/Operator",
    "Principal",
    "Purchasing Agent",
    "Purchasing Manager",
    "Regional Manager",
    "Senior Director",
    "Superintendent",
    "Teacher",
    "Vice President",
    "VP of Finance",
    "VP of Operations",
    "VP of Purchasing/Supply Chain",
];

/**
* Upload distributors from a CSV. The expectd CSV columns are:
*     OPERATOR_FIRST_NAME
*     OPERATOR_LAST_NAME
*     OPERATOR_EMAIL
*     OPERATOR_BUSINESS_NAME
*     OPERATOR_ADDRESS_STREET
*     OPERATOR_ADDRESS_CITY
*     OPERATOR_ADDRESS_STATE
*     OPERATOR_ADDRESS_POSTALCODE
*     OPERATOR_PHONE
*     DATE_OPERATOR_SELF_QUALIFIED
*     DATE_FSR_VERIFIED
*     OPERATOR_FSR_ID
*     SEGMENT
*     SUB_SEGMENT
*     OPERATOR_TITLE
*/
function upload_from_csv( string $file_path, string $import_group ) : string
{
    // Read in the CSV as an associative array.
    $csv     = array_map( 'str_getcsv', file( $file_path, FILE_SKIP_EMPTY_LINES ) );
    $keys    = array_shift( $csv );
    $results = '';

    foreach ( $csv as $i => $row ) {
        $csv[$i] = array_combine( $keys, $row );
    }

    $current_row = 0;

    foreach ( $csv as $row ) {
        $current_row ++;
        // Checks to make sure every email address is valid.
        if ( !is_email( $row['OPERATOR_EMAIL'] ) ) {
            $results .= "Line " . $current_row . " " . $row['OPERATOR_EMAIL'] . " email not vaild" . "\n";
            continue;
        }

        // Checks to make sure the user does not already exist in WordPress
        if ( get_user_by('email', $row['OPERATOR_EMAIL'] ) ) {
            $results .= "Line ". $current_row . " This user already exist with the email: " . $row['OPERATOR_EMAIL'] . "\n";
            continue;
        }

        //Gets BE Brand Abbreviation
        $be_brand_abbr = find_be_brand_abbr( $row['SUB_SEGMENT'] );

        // any user without valid operator title can not be uploaded
        if ( !$be_brand_abbr ) {
            $results .= "User has invalid sub segment and no Brand Abbr. was added" . $current_row . "\n";
            continue;
        }

        //Checks to make sure all operator titles are valid
        $valid_operator_title = confirm_operator_title( $row['OPERATOR_TITLE']);

        // any user without valid operator title can not be uploaded
        if ( !$valid_operator_title ) {
            $results .= "Line ". $current_row . " This user's operator title is not valid \n";
            continue;
        }

        // Insert the user account
        $user_id = insert_user(
            $row['OPERATOR_FIRST_NAME'],
            $row['OPERATOR_LAST_NAME'],
            $row['OPERATOR_EMAIL'],
        );

        if ( !$user_id ) {
            $results .= "User unable to be added on line" . $current_row . "\n";
            continue;
        }

        add_user_meta(
            $user_id,
            "operator_business_name",
            $row['OPERATOR_BUSINESS_NAME'],
            false
        );
        add_user_meta(
            $user_id,
            "operator_address_street",
            $row['OPERATOR_ADDRESS_STREET'],
            false
        );
        add_user_meta(
            $user_id,
            "operator_address_city",
            $row['OPERATOR_ADDRESS_CITY'],
            false
        );
        add_user_meta(
            $user_id,
            "operator_address_state",
            $row['OPERATOR_ADDRESS_STATE'],
            false
        );
        add_user_meta(
            $user_id,
            "operator_address_postalcode",
            $row['OPERATOR_ADDRESS_POSTALCODE'],
            false
        );
        add_user_meta(
            $user_id,
            "operator_phone",
            $row['OPERATOR_PHONE'],
            false
        );
        add_user_meta(
            $user_id,
            "date_operator_self_qualified",
            $row['DATE_OPERATOR_SELF_QUALIFIED'],
            false
        );
        add_user_meta(
            $user_id,
            "date_fsr_verified",
            $row['DATE_FSR_VERIFIED'],
            false
        );
        add_user_meta(
            $user_id,
            "activation_code",
            generate_activation_code( ),
            false
        );
        add_user_meta(
            $user_id,
            "import_group",
            $import_group,
            false
        );
        add_user_meta(
            $user_id,
            "operator_fsr_id",
            $row['OPERATOR_FSR_ID'],
            false
        );
        add_user_meta(
            $user_id,
            "segment",
            $row['SEGMENT'],
            false
        );
        add_user_meta(
            $user_id,
            "sub_segment",
            $row['SUB_SEGMENT'],
            false
        );
        add_user_meta(
            $user_id,
            "operator_title",
            $row['OPERATOR_TITLE'],
            false
        );
        add_user_meta(
            $user_id,
            "be_brand_abbr",
            $be_brand_abbr,
            false
        );
    }

    $results = "Processed " . $current_row . " rows.\n" . $results;

    return $results;
}


/**
* Inserts users into WordPress, with random password
*/
function insert_user( string $merchant_f_name, string $merchant_l_name, string $merchant_email ) : ?int
{
    $user_data = array(
        'user_login'  =>  $merchant_email,
        'user_email'  =>  $merchant_email,
        'user_pass'   =>  random_password( ),
        'first_name'  =>  $merchant_f_name,
        'last_name'   =>  $merchant_l_name,
        'role'        =>  'operator',
    );

    $user_id = wp_insert_user( $user_data ) ;

    if ( is_wp_error( $user_id ) ) {
        return null;
    }
    return $user_id;
}


/**
* Generates a random password for users
*/
function random_password( int $length = 15 ) : string
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    $password = substr( str_shuffle( $chars ), 0, $length );
    return $password;
}


/**
 * Generate a random code for use as an activation code.
 */
function generate_activation_code(  ) : string
{
    $code = md5( uniqid( mt_rand(), true ) );
    return $code;
}

function find_be_brand_abbr( $sub_segment ) : ?string
{
    $lower_sub = strtolower( $sub_segment );

    if( array_key_exists( $lower_sub, SEGMENT_MAPPING ) ) {
        return SEGMENT_MAPPING[$lower_sub];
    }

    return null;
}

function confirm_operator_title( $operator_title ) : bool
{
    return in_array($operator_title, VALID_OPERATOR_TITLE);

}



