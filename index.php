<?php

session_start();
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////standard copy pasted stuff////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
// enable on demain class loader
require_once 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create a log channel
$log = new Logger('main');
$log->pushHandler(new StreamHandler('logs/everything.log', Logger::DEBUG));
$log->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));

/*DB::$user = 'cp4724_marilou';
DB::$password = 'x%#BHsDnlJhZ';
DB::$dbName = 'cp4724_marilou';
DB::$host = 'ipd8.info';*/

DB::$user = 'getquizzing';
DB::$password = 'YfAruab4HzDXhTKC';
DB::$dbName = 'getquizzing';


DB::$error_handler = 'sql_error_handler';
DB::$nonsql_error_handler = 'nonsql_error_handler';

function sql_error_handler($params) {
    global $app, $log;
    $log->error("SQL error: " . $params['error']);
    $log->error("in query: " . $params['query']);
    $app->render('error_internal.html.twig');
    die; // don't want to keep going if a query broke
}

function nonsql_error_handler($params) {
    global $app, $log;
    $log->error("database error: " . $params['error']);
    http_response_code(500);
    $app->render('error_internal.html.twig');
    die;
}

// instantiate Slim - router in front controller (this file)
// Slim creation and setup
$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig()
        ));

$view = $app->view();
$view->parserOptions = array(
    'debug' => true,
    'cache' => dirname(__FILE__) . '/cache'
);
$view->setTemplatesDirectory(dirname(__FILE__) . '/templates');

\Slim\Route::setDefaultConditions(array('id' => '\d+'));
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////end of copy pasted stuff//////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////////render the educaors index//////////////////////////
$app->get('/', function() use ($app) {
    $app->render('index.html.twig');
});
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////////render the learner index//////////////////////////
$app->get('/learner', function() use ($app) {
    $app->render('learnerindex.html.twig');
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////RENDER EDUCATOR REGISTRATION///////////////////////////
$app->get('/registerEducator', function() use ($app) {
    $app->render('registereducator.html.twig');
});
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////REGISTER A NEW EDUCATOR////////////////////////
$app->post('/registerEducator', function() use ($app) {
    $firstName = $app->request->post('educatorFirstName');
    $lastName = $app->request->post('educatorLastName');
    $email = $app->request->post('educatorEmail');
    $pass1 = $app->request->post('pass1');
    $pass2 = $app->request->post('pass2');
    $errorList = array();

    if (strlen($firstName) < 2) {
        array_push($errorList, 'first name must be at least two characters long');
    }

    if (strlen($lastName) < 2) {
        array_push($errorList, 'last name must be at least two characters long');
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
        array_push($errorList, "Email does not look like a valid email");
    } else {
        $user = DB::queryFirstRow("SELECT ID FROM educators WHERE email=%s", $email);
        if ($user) {
            array_push($errorList, "Email already registered");
        }
    }

    if (!preg_match('/[0-9;\'".,<>`~|!@#$%^&*()_+=-]/', $pass1) || (!preg_match('/[a-z]/', $pass1)) || (!preg_match('/[A-Z]/', $pass1)) || (strlen($pass1) < 8)) {
        array_push($errorList, "Password must be at least 8 characters " .
                "long, contain at least one upper case, one lower case, " .
                " one digit or special character");
    } else if ($pass1 != $pass2) {
        array_push($errorList, "Passwords don't match");
    }

    if ($errorList) {
        // STATE 3: submission failed        
        $app->render('registereducator.html.twig', array(
            'errorList' => $errorList
        ));
    } else {
        //STATE 2: submission successful
        DB::insert('educators', array(
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'password' => $pass1
        ));
        //$id = DB::insertId();
        $app->render('registration_success_educator.html.twig');
    }
});
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////RENDER THE LEARNER REGISTRATION/////////////////////
$app->get('/registerlearner', function() use ($app) {
    $app->render('registerlearner.html.twig');
});
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////REGISTER A NEW LEARNER////////////////////////////////
$app->post('/registerlearner', function() use ($app) {
    $firstName = $app->request->post('learnerFirstName');
    $lastName = $app->request->post('learnerLastName');
    $email = $app->request->post('learnerEmail');
    $phone = $app->request->post('phonenumber');
    $pass1 = $app->request->post('pass1');
    $pass2 = $app->request->post('pass2');
    $errorList = array();

    if (strlen($firstName) < 2) {
        array_push($errorList, 'first name must be at least two characters long');
    }

    if (strlen($lastName) < 2) {
        array_push($errorList, 'last name must be at least two characters long');
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
        array_push($errorList, "Email does not look like a valid email");
    } else {
        $user = DB::queryFirstRow("SELECT ID FROM learners WHERE email=%s", $email);
        if ($user) {
            array_push($errorList, "Email already registered");
        }
    }
    if (strlen($phone) != 10) {
        array_push($errorList, "pleae enter your phone number in the following format 9999999999");
    }

    if (!preg_match('/[0-9;\'".,<>`~|!@#$%^&*()_+=-]/', $pass1) || (!preg_match('/[a-z]/', $pass1)) || (!preg_match('/[A-Z]/', $pass1)) || (strlen($pass1) < 8)) {
        array_push($errorList, "Password must be at least 8 characters " .
                "long, contain at least one upper case, one lower case, " .
                " one digit or special character");
    } else if ($pass1 != $pass2) {
        array_push($errorList, "Passwords don't match");
    }

    if ($errorList) {
        // STATE 3: submission failed        
        $app->render('registerlearner.html.twig', array(
            'errorList' => $errorList
        ));
    } else {
        // STATE 2: submission successful
        DB::insert('learners', array(
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => $pass1
                // 'password' => hash('sha256', $pass1)
        ));
        //$id = DB::insertId();
        $app->render('registration_success_learner.html.twig');
    }
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////EDUCATOR LOG IN////////////////////////////////////////
$app->post('/', function() use ($app, $log) {
    $email = $app->request->post('email');
    $pass = $app->request->post('password');
    $user = DB::queryFirstRow("SELECT * FROM educators WHERE email=%s", $email);
    if (!$user) {
        $log->debug(sprintf("User failed for email %s from IP %s", $email, $_SERVER['REMOTE_ADDR']));
        echo 'SHIT';
    } else {

        if ($user['password'] === $pass) {
            //LOGIN successful
            unset($user['password']);
            $_SESSION['user'] = $user;
            $log->debug(sprintf("User %s logged in successfuly from IP %s", $user['ID'], $_SERVER['REMOTE_ADDR']));
            $app->render('login_success_educators.html.twig');
        } else {
            $log->debug(sprintf("User failed for email %s from IP %s", $email, $_SERVER['REMOTE_ADDR']));
            echo 'SHIT2';
        }
    }
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////LEARNER LOG IN/////////////////////////////////////////////
$app->post('/learner', function() use ($app, $log) {
    $email = $app->request->post('email');
    $pass = $app->request->post('password');
    $user = DB::queryFirstRow("SELECT * FROM learners WHERE email=%s", $email);
    if (!$user) {
        $log->debug(sprintf("User failed for email %s from IP %s", $email, $_SERVER['REMOTE_ADDR']));
        echo 'SHIT';
    } else {

        if ($user['password'] === $pass) {
            $app->render('login_success_learners.html.twig');
            // LOGIN successful
            /* unset($user['password']);
              $_SESSION['user'] = $user;
              $log->debug(sprintf("User %s logged in successfuly from IP %s",
              $user['ID'], $_SERVER['REMOTE_ADDR'])); */
        } else {
            $log->debug(sprintf("User failed for email %s from IP %s", $email, $_SERVER['REMOTE_ADDR']));
            echo 'SHIT2';
        }
    }
});
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////EDUCATORS HOME/CREATE QUIZZES///////////////////////
$app->get('/educatorshome', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        echo "You can't sit with us!";
    } else {

        $app->render('educatorhome.html.twig', array('educator' => $_SESSION['user']));
    }
});

$app->post('/educatorshome', function() use ($app) {
    $title = $app->request->post('quizName');
    $educatorID = $_SESSION['user']['ID'];
    if ((strlen($title) < 3) || (strlen($title) > 140)) {
        return;
    } else {
        DB::insert('quizzes', array('educatorID' => $educatorID, 'title' => $title));
    }
});
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////EDUCATORS EXISTING QUIZZES//////////////////////////
$app->get('/existingquizzes', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        echo "You can't sit with us!";
    } else {
        $quizList = DB::query("SELECT * FROM quizzes WHERE educatorID=%d", $_SESSION['user']['ID']);
        $app->render('existingquizzes.html.twig', array('educator' => $_SESSION['user'], 'quizList' => $quizList));
    }
});
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////ADD QUESTION TO EXISTING QUIZZES////////////////////
$app->get('/newquestion/:id', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        echo "You can't sit with us!";
    } else {
        $app->render('newquestion.html.twig');
    }
});

//////////////////////////////////////////////////////////////////////////////////
////////////////////////////EDUCATOR ACCOUNT//////////////////////////////////////
$app->get('/account', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        echo "You can't sit with us!";
    } else {
        $app->render('educatoraccount.html.twig', array('educator' => $_SESSION['user']));
    }
});

$app->post('/account', function() use ($app) {
    $firstName = $app->request->post('educatorFirstName');
    $lastName = $app->request->post('educatorLastName');
    $email = $app->request->post('educatorEmail');
    $errorList = array();

    if (strlen($firstName) < 2) {
        array_push($errorList, 'first name must be at least two characters long');
    }

    if (strlen($lastName) < 2) {
        array_push($errorList, 'last name must be at least two characters long');
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
        array_push($errorList, "Email does not look like a valid email");
    } else {
        $user = DB::queryFirstRow("SELECT ID FROM learners WHERE email=%s", $email);
        if ($user) {
            array_push($errorList, "Email already registered");
        }
    }

    if ($errorList) {
        // STATE 3: submission failed        
        $app->render('educatoraccount.html.twig', array(
            'errorList' => $errorList
        ));
    } else {
        //STATE 2: submission successful
        DB::update('educators', array(
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email),
            "ID=%d", $_SESSION['user']['ID']);
        //$id = DB::insertId();
        $app->render('change_success_educator.html.twig');
    }
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////////EDUCATOR EXISTING QUIZZES//////////////////////////
$app->get('/existingquizzes', function() use ($app) {
    $app->render('existingquizzes.html.twig');
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////////EDUCATOR LOGOUT////////////////////////////////////
$app->get('/logout', function() use ($app, $log) {
    $_SESSION['user'] = array();
    $app->render('logout_success.html.twig');
});

//////////////////////////////////////////////////////////////////////////////////
////////////////////////////LEARNERS HOME/////////////////////////////////////////
$app->get('/learnershome', function() use ($app) {

});
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////////LEARNER INVITATIONS///////////////////////////////
$app->get('/learnerinvitation', function() use ($app) {

});

$app->run();
