<?php
/**
 * Description of BugzillaAdaptor
 *
 * @author applect
 */

App::uses('BugzillaConnector', "Lib");

class BugzillaAdaptor implements BugzillaInterface{
    private $bugzilla ;
    public function init($url , $username , $password , $options=array()){
        $this->bugzilla = new BugzillaConnector($url);
        $user_id = $this->bugzilla->login($username, $password, true);
        if($user_id){
            return true;
        }  else {
            return false;
        }
    }
    
    public function addBug(Bug $bug) {
        /**
         * Search the exiting bugs in customer component
         * and Meritnation product 
         */
        //print_r($bug);
        $result = array(
            'bug_status' => 0, //0 means error,  1 means bug added and 2 means comment added,
            'id' => null , // bug_id created or updated
            'comment_id' => null,
            'attachments' => array() // names of files to be added as attachement
        );
        
        $search_params = array(
            'summary' => "[" . $bug->report_customer_email . "]" . $bug->title,
            'component'   => $bug->component,
            'product'     => $bug->product,
            'version'     => $bug->version
        );
        
        $search_res = $this->bugzilla->searchBug($search_params);
        
        if(empty($search_res['bugs'])){
            
            $bugzillaBug = array(
                'summary' => "[" . $bug->report_customer_email . "]" . $bug->title,
                'description'  => $bug->description,
                'assigned_to' => $bug->assigned_to_email,
                'product'     => $bug->product,
                'version'     => $bug->version,
                'component'   => $bug->component,
                'op_sys'      => 'All',
                'platform'    => "All"
            );
            $response = $this->bugzilla->createBug($bugzillaBug);
            if(isset($response['id'])){
                $result['bug_status']   = 1;
                $result['id']           = $response['id'];
            }
        }else{
            
            
            $last_found_bug = array_pop($search_res['bugs']);
            $comment = "--------------------- Comment added by report cron ------------\n";
            $comment .="Sent by:$bug->report_customer_email\n";
            $comment .="---------------------------------------------------------------\n";
            $comment .="$bug->description";
            $comment_arr = array(
                'id' => $last_found_bug['id'],
                'comment' => $comment,
                'is_private' => 0
            );
            $response = $this->bugzilla->addComment($comment_arr);
            if(isset($response['id'])){
                $result['bug_status']   = 2;
                $result['id']           = $last_found_bug['id'];
                $result['comment_id']   = $response['id'];
            }
        }
        
        /**
         * Finally look to see if there is any attachemnt to be added
         */
        /** 
         * This block is not working due to encoding issues
         * We will fix it later 
         */
        /*
        foreach($bug->attachments as $attachment){
            $attachment_arr = array(
                'ids' => array($result['id']),
                'data'=> base64_encode($attachment['content']),
                'file_name' => $attachment['name'],
                'summary' => "added by bugzilla cron by user $bug->report_customer_email",
                'content_type' => $attachment['type'],
            );
            
            //file_put_contents("/home/applect/Desktop/as" . $attachment['name'], $attachment_arr['data']);
            
            $attach_response = $this->bugzilla->createAttachment($attachment_arr);
            if(isset($attach_response['ids']) && is_array($attach_response['ids'])){
                $result['attachments'][] = array(
                    'name' => $attachment['name'],
                    'type' => $attachment['type']
                );
            }
        }
         
        */
        return $result;
    }
}



?>
