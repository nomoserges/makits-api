<?php namespace App\Models;

class Activationmodel extends \Codeigniter\Model {
    protected $table      = 'users_activation';

    /** Insert token in database
     * $data is an array: array('email', 'token')
     */
    public function createToken($datas) {
        if( $this->db->table($this->table)->insert($datas) ){
            return true;
        }else{
            $this->db->error();
        }
    }

    /**
     * We check if the key token->email exist
     */
    public function checkToken($token, $email){
        $sql = "SELECT * FROM ".$this->table
            ." WHERE token = '".$token
            ."' AND email = '".$email."' LIMIT 1 ";
        $query = $this->db->query( $sql );
        if( count($query->getResult()) > 0 ) { return true; } 
        else { return false; }
    }
    /**
     * Hard delete of token in the database
     */
    public function deleteToken($token, $email){
        $sql = "DELETE FROM ".$this->table
            ." WHERE token = '".$token
            ."' AND email = '".$email."' ";
        $query = $this->db->query( $sql );
        if( !$query ) { return false; } 
        else { return true; }
    }

}