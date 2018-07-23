<?php namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\Usersmodel;
use App\Models\Activationmodel;

class Users extends Controller {
    
    protected $request;
    private $status;
    private $message;
    private $datas;
    // protected $helpers = ['url', 'form'];

    public function __constructor() {
        parent::__constructor();
    }
	public function index() {
		echo 'index ';
	}

    public function register() {
        // var_dump($request);
        if (! $this->validate([
            'pseudo'     => "required|is_unique[users.pseudo]|alpha_numeric",
            'email'     => "required|is_unique[users.email]|valid_email",
            'password'  => 'required|min_length[6]',
            'password_confirmation' => 'required|matches[password]',
        ]))
        {
            // var_dump( $this->validator->getErrors() );
            // $this->validator->listErrors();
            $this->status = "nok";
            $this->message = $this->validator->listErrors();
            $this->datas = null;
        } else {
            #  Proccess with model if there is not errors
            $usersModel = new Usersmodel;
            $resultSet = $usersModel->register($this->request->getPost());
            // var_dump( $resultSet );
            if ( $resultSet === true ) {
                # we will send an email for activation
                $activationModel = new Activationmodel;
                # We create activation token and datas
                # for view
                helper('text');
                $emailDatas = array(
                    'token'     => random_string('alnum', 32),
                    'pseudo'    => $this->request->getPost('pseudo'),
                    'email'     => $this->request->getPost('email')
                );
                $tokenDatas = array(
                    'token'     => $emailDatas['token'],
                    'email'     => $emailDatas['email']
                );
                # We create token for activation
                if ( $activationModel->createToken($tokenDatas) ) {
                    # we send mail
                    if ( $this->signupValidationEmail($emailDatas) ) {
                        $this->status = "ok"; 
                        $this->message = 'An email has been send to activate your account!';
                    } else {
                        $this->status = "nok"; 
                        $this->message = 'An error occured when creating account';
                        # we delete the token
                        $activationModel->deleteToken(
                            $emailDatas['token'],
                            $emailDatas['email']);
                        # normally, we have to hard delete account
                        # in database and let user try again
                    }
                } else {
                    $this->status = "nok"; 
                    $this->message = 'An error occured when creating account';
                }
                
                
            } else {
                $this->status = "nok";
                $this->message = 'An error occured! Please try again on few';
            }
        }
        
        echo json_encode([
            'status'    => $this->status, 
            'message'   => $this->message, 
            'datas'     => null
        ]);
    }

    public function login() {
        // var_dump($this->request->getPost());
        $usersModel = new Usersmodel();
        # we proccess with model
        
        $xx = $usersModel->findWithCredentials(
            $this->request->getPost('credential'), true, 
            $this->request->getPost('password'));
        if ( $xx == null ) {
            $this->status = "nok"; $this->message = 'Your credentials are not correct';
        } else {
            $this->status = "ok"; $this->message = 'Successfully login';
            $this->datas = $xx;
        }
        echo json_encode([
            'status' => $this->status, 
            'message' => $this->message, 
            'datas' => $this->datas
        ]);
    }

    public function getEmail() {
        if (! $this->validate([
            'email'     => "required|valid_email"
        ])) {
            $this->status = "nok";
            $this->message = $this->validator->listErrors();
            $this->datas = null;
        } else {
            # We look if the user's email exist
            $usersModel = new Usersmodel();
            $theUser = $usersModel->findWithCredentials(
                $this->request->getPost('email'), false, null
            );
            if ( sizeof($theUser) === 1 ) {
                # the email exist on the model
                # we're going to send email with 
                # activation token
                # we will send an email for activation
                $activationModel = new Activationmodel;
                //var_dump($theUser);
                helper('text');
                $emailDatas = array(
                    'pseudo'    => $theUser[0]->pseudo,
                    'fullname'  => $theUser[0]->firstname.' '.$theUser[0]->lastname,
                    'email'     => $theUser[0]->email,
                    'token'     => random_string('alnum', 32)
                );
                //var_dump($emailDatas);
                $tokenDatas = array(
                    'token'     => $emailDatas['token'],
                    'email'     => $emailDatas['email']
                );
                # We create token for activation
                if ( $activationModel->createToken($tokenDatas) ) {
                    # we send mail
                    if ( $this->passwordValidationEmail($emailDatas) ) {
                        $this->status = "ok"; 
                        $this->message = 'An email has been send to reset your password!';
                    } else {
                        $this->status = "nok"; 
                        $this->message = 'An error occured when sending email';
                        # we delete the token
                        $activationModel->deleteToken(
                            $emailDatas['token'],
                            $emailDatas['email']);
                        # normally, we have to hard delete account
                        # in database and let user try again
                    }
                } else {
                    $this->status = "nok"; 
                    $this->message = 'An error occured when creating account';
                }
            } else {
                # No email found ...
                $this->status = "nok"; 
                $this->message = 'No account found with this email !';
            }
            
        }
        echo json_encode([
            'status' => $this->status, 
            'message' => $this->message, 
            'datas' => $this->datas
        ]);
    }

    public function changePassword() {
        if (! $this->validate([
            'userid'  => 'required',
            'password'  => 'required|min_length[6]',
            'password_confirmation' => 'required|matches[password]',
        ]))
        {
            $this->status = "nok";
            $this->message = $this->validator->listErrors();
            $this->datas = null;
        } else { 
            # updating on model
            $usersModel = new Usersmodel;
            if ( $usersModel->changePassword(
                $this->request->getPost('password'),
                $this->request->getPost('userid')
            )) {
                # password updated successfully...
                $this->status = "ok"; 
                $this->message = "New password saved";
                $this->datas = $usersModel->findWithCredentials(
                    $this->request->getPost('userid'), 
                    false, 
                    null
                );
            } else {
                # error occured when trying the update ...
                $this->status = "nok"; 
                $this->message = 'Error occured. Please try again.';
            }
        }
        echo json_encode([
            'status' => $this->status, 
            'message' => $this->message, 
            'datas' => $this->datas
        ]);
    }

    public function activeAccount() {
        if (! $this->validate([
            'token'     => "required|alpha_numeric",
            'email'     => "required|valid_email"
        ]))
        {
            $this->status = "nok";
            $this->message = $this->validator->listErrors();
            $this->datas = null;
        } else {
            $usersModel = new Usersmodel();
            $activationModel = new Activationmodel();
            $token = $this->request->getPost('token');
            $email = $this->request->getPost('email');
            # we send the token and email to model to check if they exists
            # if true, we set 'is_activated' flag to 1 in users table
            # and also delete token and email in users_activation table
            if ( $activationModel->checkToken($token,$email) ) {
                if ( $usersModel->setIsActivated($email) ) {
                    # we are going to delete the token
                    if ( $activationModel->deleteToken($token, $email) ) {
                        $this->status = "ok"; $this->message = 'You account is now activated';
                        $this->datas = $usersModel->findWithCredentials($email, false, null);
                    } else {
                        $this->status = "nok"; $this->message = 'Deleting token error';
                    }
                } else {
                    $this->status = "nok"; $this->message = 'Activating account error';
                }
            } else {
                $this->status = "nok"; $this->message = 'No token available or expired';
            }
        }
        echo json_encode([
            'status' => $this->status, 
            'message' => $this->message, 
            'datas' => $this->datas[0]
        ]);
    }

    /**
     * Update personal information of user
     */
    public function setPersonal() {
        # var_dump( $this->request->getPost() );
        if (! $this->validate([
            'userid'        => "required"
        ])) {
            $this->status = "nok";
            $this->message = $this->validator->listErrors();
            $this->datas = null;
        } else {
            $usersModel = new Usersmodel;
            $usersModel->setPersonal($this->request->getPost());

            $this->status = "ok";
            $this->message = "Personals informations saved";
            $this->datas = $usersModel->findWithCredentials(
                $this->request->getPost('userid'), 
                false, null);
        }
        /*
        var_dump($usersModel->findWithCredentials(
            $this->request->getPost('userid'), 
            false, null));
            */
        echo json_encode([
            'status' => $this->status, 
            'message' => $this->message, 
            'datas' => $this->datas[0]
        ]);
    }

    /**
     * Update activity informations of users
     */
    public function setJobInformation() {
        # var_dump( $this->request->getPost() );
        if (! $this->validate([
            'userid'            => "required"
        ])) {
            $this->status = "nok";
            $this->message = $this->validator->listErrors();
            $this->datas = null;
        } else {
            $usersModel = new Usersmodel;
            $usersModel->setJob($this->request->getPost());

            $this->status = "ok";
            $this->message = "Personals informations saved";
            $this->datas = $usersModel->findWithCredentials(
                $this->request->getPost('userid'), 
                false, 
                null
            );
        }
        echo json_encode([
            'status' => $this->status, 
            'message' => $this->message, 
            'datas' => $this->datas[0]
        ]);
    }

    public function setAvatar() {
        
    }

    /**
     * Send an email with template locate at
     *  views/emails/register_confirmation.php
     */
    public function signupValidationEmail($datas) {
        $email = \Config\Services::email();
        
        $email->setFrom('noreply@makits.net', 'Makits');
        $email->setTo($datas['email']);
        $email->setSubject('welcome to makits - Confirm your account');
        //  We take content of view
        $email->setMessage( view(
            'emails/register_confirmation', 
            $datas) );
        return $email->send();
    }

    /**
     * Send an email with template locate at
     *  views/emails/register_confirmation.php
     */
    public function passwordValidationEmail($datas) {
        $email = \Config\Services::email();
        
        $email->setFrom('noreply@makits.net', 'Makits');
        $email->setTo($datas['email']);
        $email->setSubject('Makits - Reset your password');
        //  We take content of view
        $email->setMessage( view(
            'emails/password_reset', 
            $datas) );
        return $email->send();
    }
}
