<?php namespace App\Models;


class Usersmodel extends \CodeIgniter\Model {
    protected $table      = 'users';
    protected $table_avatar = "avatars";
    protected $primaryKey = 'userid';
    protected $db;

    public function __construct() {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    private function generateUserID(){
        // $newUserID = str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789");
        helper('text');
        $newUserID = \random_string('alnum', 32);
        $isFind = true;
        while ($isFind) {
            # Search the generate's ID in the table
            $sql = "SELECT userid FROM ".$this->table." WHERE userid = '".$newUserID."' LIMIT 1";
            $query = $this->db->query( $sql );
            // var_dump($query);
            /** Test de la valeur isFind */
            if( count($query->getResult()) > 0 ) {
                # code existe
                $isFind = true;
            }else {
                # ce code n'existe pas
                $isFind = false;
                break;
            }
        }
        return $newUserID;
    }
    /**
     * Create user in registration proccess
     */
    public function register($datas){
        $data = array(
            'userid'    => $this->generateUserID(),
            'pseudo'    => $datas['pseudo'],
            'email'     => $datas['email'],
            'password'  => sha1( $datas['password'] )
        );
        // return ;
        if( $this->db->table($this->table)->insert($data) ){
            return true;
        }else{
            $this->db->error();
        }
    }
    /**
     * Activated the account
     */
    public function setIsActivated($credential){
        $updateQuery = "UPDATE ".$this->table
            ." SET is_activated = '1' "
            ." WHERE userid = '".$credential
            ."' OR email = '".$credential
            ."' OR pseudo = '".$credential."' ";
        return $this->db->query($updateQuery);
    }
    /**
     * Saving DOB, Gender, Firstname and Lastname
     */
    public function setPersonal( $datas ){
        $updateQuery = "UPDATE ".$this->table
            ." SET dob = '".$datas['dob'].
            "', gender = '".$datas['gender'].
            "', firstname = '".$datas['firstname'].
            "', lastname = '".$datas['lastname']."' "
            ." WHERE userid = '".$datas['userid']."' ";
        return $this->db->query($updateQuery);
    }
    /**
     * Saving Job title, Job description and Job tags
     */
    public function setJob( $datas ){
        $updateQuery = "UPDATE ".$this->table
            ." SET job_title = '".$datas['job_title'].
            "', job_description = '".$datas['job_description'].
            "', job_tags = '".$datas['job_tags']."' "
            ." WHERE userid = '".$datas['userid']."' ";
        return $this->db->query($updateQuery);
    }

    public function changePassword($newpassword, $credential){
        $updateQuery = "UPDATE ".$this->table
            ." SET password = '".sha1($newpassword)."' "
            ."WHERE userid = '".$credential
            ."' OR pseudo='".$credential
            ."' OR email='".$credential."' ";
        return $this->db->query($updateQuery);
    }

    /**
     * Look credentials for user (login)
     */
    public function findWithCredentials($credential, $withPassword, $password){
        /* si la recherche se fait avec le mot de passe */
        $addPasswordCheck = '';
        if ($withPassword == true) {
            $addPasswordCheck = "AND password = '".sha1($password)."' ";
        }
        $sql = "SELECT U.userid, pseudo, email, firstname, lastname, gender, "
        ."dob, job_title, job_description, job_tags, created_at, updated_at, "
        ."(SELECT filename FROM $this->table_avatar A WHERE A.userid = U.userid AND is_used = 1) AS avatar "
        ."FROM $this->table U "
        ."WHERE (userid = '".$credential."' OR pseudo='".$credential."' OR email='".$credential."') "
        .$addPasswordCheck
        ."AND is_activated=1 ";
        $query = $this->db->query($sql);
        if ( !$query ) {
            return $this->db->error();
        } else {
            // return $query->getRowArray();
            return $query->getResult();
        }
        
    }

}
