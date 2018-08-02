<?php namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\Files\UploadedFile;
use App\Models\Usersmodel;
use App\Models\Avatarsmodel;

class Avatars extends Controller {
    protected $request;
    private $status;
    private $message;
    private $datas;

    /* Constructor */
    public function __constructor() {
        parent::__constructor();
    }
    /* adding avatar */
    public function setAvatar() {
        # taking content of input
        $data = json_decode(trim(file_get_contents('php://input')), true);
        $avatarsModel = new Avatarsmodel;
        $theRawName = $avatarsModel->generateRawName();
        # create the datas for the model
        $datasModel = array(
            'raw_name'      => $theRawName,
            'filename'      => $theRawName.'.'.explode(".", $data['image_name'])[1],
            'userid'        => $data['userid'],
            'image_type'    => $data['image_type'],
            'image_size'    => $data['image_size']
        );
        # we insert before create the file on filesystem
        if ( $avatarsModel->create($datasModel) === true ) {
            # we create the file
            if ( file_put_contents(
                WRITEPATH.'uploads/'.$datasModel['filename'],
                base64_decode($data['image_binary'])
            ) === false ) { 
                $this->status = "nok"; 
                $this->message = "Error occured when transfert the file";
                $this->datas = null;
             } 
            else { 
                $usersModel = new Usersmodel();
                $this->status = "ok"; 
                $this->message = "Avatar saved";
                $this->datas = $usersModel->findWithCredentials(
                    $datasModel['userid'], false, null
                );
            }
        } else {
            $this->status = "nok"; 
            $this->message = "Error occured";
            $this->datas = null;
        }
        # output the result
        echo json_encode([
            'status' => $this->status, 
            'message' => $this->message, 
            'datas' => $this->datas[0]
        ]);
    }
}

