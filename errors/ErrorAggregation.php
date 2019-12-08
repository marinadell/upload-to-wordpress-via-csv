<?php

namespace FsrImporter\Errors;

const DCN_SLUG                 = 'distributor-account';
const DCN_FIELD_LINK_ERROR     = 'skyloader_dist_link_error';
const DCN_FIELD_USER_ID        = 'skyloader_dist_config_user_id';
const DCN_FIELD_DISTRIBUTOR_ID = 'skyloader_dist_id';
const DCN_FIELD_HISTORY        = 'skyloader_dist_history';


/**
* This class aggregates all the data needed for an email to be sent to the user
*/
class ErrorAggregation {

    private $errorFactory=null;

    public function intialize()
    {
        $this->errorFactory = new SkyloaderErrorFactory();
        $this->errorFactory->readYamlFile();
    }


    public function sendErrorEmailsToUsers()
    {
        $dcnPostIDs = $this->getDCNPostIDs();

        $errorsGroupedByUser = $this->groupAllErrorsByUser($dcnPostIDs);
        $this->sendEmailPerUser($errorsGroupedByUser);
    }


    /**
    * Gets all DCN post IDs with an error and ignores DCNs without errors
    */
    public function getDCNPostIDs()
    {
        $query = new \WP_Query( [
            'numberposts' => -1,
            'post_type'   => DCN_SLUG,
            'meta_query'  => [
                [
                    'key'     => DCN_FIELD_LINK_ERROR,
                    'compare' => '!=',
                    'value'   => '',
                ],
            ],
        ] );

        $postIDs = wp_list_pluck( $query->posts, 'ID' );

        return $postIDs;
    }


    /**
    * Constructs the full email and and sends it to the user
    */
    public function sendEmailPerUser(array $allErrorsGroupedByUser)
    {
        foreach ($allErrorsGroupedByUser as $userID => $userErrors){
            $userEmail = $this->getUserEmail($userID);

            if (!$userEmail){
                $userEmail = 'team-associates@clockwork.com';
            }

            $errors = $this->createErrorClass($userErrors);

            $email = new \FsrImporter\Errors\ErrorEmail($errors);
            $email->send($userEmail);

            $this->updateUserDcnLogs($errors);
        }
    }


    /**
     * Update the distributor account log with a messaging stating an error email 
     * message was sent.
     */
    public function updateUserDcnLogs(array $errors) 
    {
        foreach ($errors as $error) {
            $message  = 'Sent error email message to user.';
            $history  = get_post_meta($error->getDcnPostId(), DCN_FIELD_HISTORY, true);
            $history .= "\n" . date('Y-m-d H:i:s') . ' > ' . $message;

            update_post_meta($error->getDcnPostId(), DCN_FIELD_HISTORY, $history);
        }
    }


    public function getUserEmail($userID): ?string
    {
        $userData = get_userdata( $userID );

        if (!$userData){
            return null;
        }

        return $userData->user_email;
    }


    /**
    * Takes all errors and groups them by Users
    */
    public function groupAllErrorsByUser(array $erroredDCNAccountIDs): array
    {
        $errorsGroupedByUser = [];
        foreach ( $erroredDCNAccountIDs as $dcnAccountID ) {
            $errorCode = $this->checkErrorFieldReportable($dcnAccountID);

            if (!$errorCode){
                continue;
            }

            $userID = get_post_meta( $dcnAccountID, DCN_FIELD_USER_ID, true);

            if($userID){
                $errorsGroupedByUser[$userID][] = $dcnAccountID;
            }
        }
        return $errorsGroupedByUser;
    }


    /**
    * Build the correct skyloader error class for each User
    */
    public function createErrorClass(array $dcnPostIDs): array
    {
        $errors = [];

        foreach ($dcnPostIDs as $dcnPostID){

            $distAccountHash = $this->getDistAccountHash($dcnPostID);

            $error = $this->createSklyloaderError($distAccountHash);

            $errors[] = $error;
        }

        return $errors;
    }


    /**
    * Get correct DCN Account Information
    * Including Distributor Name and Error Code
    */
    public function getDistAccountHash(string $distID): array
    {
        $DCNPost = get_post( $distID );

        $errorCode = get_post_meta( $distID, DCN_FIELD_LINK_ERROR, true );

        $distributorID   = get_post_meta( $DCNPost->ID, DCN_FIELD_DISTRIBUTOR_ID, true );
        $distributorPost = get_post( $distributorID );

        return [
            'DCN'             => $DCNPost->post_title,
            'distributorName' => $distributorPost->post_title,
            'errorCode'       => $errorCode,
            'dcnPostId'       => $distID,
        ];
    }


    /**
    * Creates Skyloader error for 1 error at a time
    * And updates it with the correct DCN and DistributorName
    */
    public function createSklyloaderError(array $accountHash): ?SkyloaderError
    {
        $error = $this->errorFactory->createError( $accountHash['errorCode'] );

        if (!$error){
            return null;
        }

        $error->setDCN($accountHash['DCN']);
        $error->setDcnPostId($accountHash['dcnPostId']);
        $error->setDistributorName($accountHash['distributorName']);
        return $error;
    }


    /**
    * Confirm that the error code is an error we are using
    */
    public function checkErrorFieldReportable(string $distID): ?string
    {
        $errorCode = get_post_meta( $distID, DCN_FIELD_LINK_ERROR, true );

        $reportableError  = $this->errorFactory->isReportableError($errorCode);

        if ($reportableError){
            return $errorCode;
        }
        return null;
    }
}
