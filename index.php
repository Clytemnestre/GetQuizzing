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

// DB::$user = 'cp4724_marilou';
//  DB::$password = 'x%#BHsDnlJhZ';
//  DB::$dbName = 'cp4724_marilou';
//  DB::$host = 'ipd8.info'; 

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
            unset($user['password']);
            $_SESSION['user'] = $user;
            $log->debug(sprintf("User %s logged in successfuly from IP %s", $user['ID'], $_SERVER['REMOTE_ADDR']));
        } else {
            $log->debug(sprintf("User failed for email %s from IP %s", $email, $_SERVER['REMOTE_ADDR']));
            echo 'SHIT2';
        }
    }
});
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////EDUCATORS HOME//////////////////////////////////////
$app->get('/educatorshome', function() use ($app) {
    if (empty($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $app->render('createquiz.html.twig', array('educator' => $_SESSION['user']));
    }
});


$app->post('/educatorshome', function() use ($app) {

    $errorList = array();
    $title = $app->request->post('quizname');

    if ((strlen($title) < 3) || (strlen($title) > 100)) {
        array_push($errorList, 'The title of you quiz must be between 3 and 150 characters long');
    }

    if ($errorList) {
        $app->render('createquiz.html.twig', array(
            'errorList' => $errorList
        ));
    } else {
        DB::insert('quizzes', array(
            'educatorID' => $_SESSION['user']['ID'],
            'title' => $title
        ));

        $quizList = DB::query("SELECT * FROM quizzes WHERE educatorID=%d", $_SESSION['user']['ID']);
        $app->render('existingquizzes.html.twig', array('educator' => $_SESSION['user'], 'quizList' => $quizList));
    }
});
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////EDUCATORS EXISTING QUIZZES//////////////////////////
$app->get('/existingquizzes', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $quizList = DB::query("SELECT * FROM quizzes WHERE educatorID=%d", $_SESSION['user']['ID']);
        $app->render('existingquizzes.html.twig', array('educator' => $_SESSION['user'], 'quizList' => $quizList));
    }
});
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////////INVITE LEARNERS TO YOUR TAKE YOUR QUIZ//////////////
$app->get('/invites/:id', function($quizID) use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $studentList = DB::query("SELECT * FROM learners");
        $app->render('inviteLearners.html.twig', array('studentList' => $studentList));
    }
});

$app->post('/invites/:id', function($quizID) use ($app) {
    $invitations = array();
    $quizID = $quizID;
    $errorList = array();

    if (!isset($_POST['check_list'])) {
        array_push($errorList, "you must select at least one learner to do your quiz");
    } else {
        foreach ($_POST['check_list'] as $invite) {
            array_push($invitations, $invite);
        }
    }

    if ($errorList) {
        $studentList = DB::query("SELECT * FROM learners");
        $app->render('inviteLearners.html.twig', array('errorList' => $errorList, 'studentList' => $studentList));
    } else {
        foreach ($invitations as $learnerID) {
            DB::insert('invitations', array(
                'quizID' => $quizID,
                'leanerID' => $learnerID
            ));
            $quizList = DB::query("SELECT * FROM quizzes WHERE educatorID=%d", $_SESSION['user']['ID']);
            $app->render('existingquizzes.html.twig', array('educator' => $_SESSION['user'], 'quizList' => $quizList));
        }
    }
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////QUESTIONS FOR A QUIZ///////////////////////////////////
$app->get('/questions/:id', function($quizID) use ($app) {
    if (empty($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $questionList = DB::query("SELECT * FROM questions WHERE quizID=%d", $quizID);
        $quizinfo = DB::queryFirstRow("SELECT * FROM quizzes WHERE ID=%d", $quizID);

        $app->render('managequizquiz.html.twig', array('questionList' => $questionList, 'quizinfo' => $quizinfo, 'educator' => $_SESSION['user']));
    }
});

//////////////////////////////////////////////////////////////////////////////////
///////////////////////////SEE RESULTS OF A QUIZ//////////////////////////////////
$app->get('/seeresults/:id', function($quizID) use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $resultList = DB::query("SELECT * FROM results WHERE quizID=%d", $quizID);
        if (!$resultList) {
            $app->render('noresults.html.twig');
        } else {
            $app->render('seeresults.html.twig', array('resultList' => $resultList));
        }
    }
});
//////////////////////////////////////////////////////////////////////////////////
//////////////////////////DELETE QUIZ////////////////////////////////////////////////////////
$app->get('/deletequiz/:id', function($quizID) use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        DB::delete('quizzes', "ID=%d", $quizID);
        DB::delete('invitations', "quizID=%d", $quizID);
        $app->render('deletesuccess.html.twig');
    }
});
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////EDUCATOR ACCOUNT//////////////////////////////////////
$app->get('/account', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
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
            'email' => $email), "ID=%d", $_SESSION['user']['ID']);
        //$id = DB::insertId();
        $app->render('change_success_educator.html.twig');
    }
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
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $app->render('learnerhome.html.twig', array('learner' => $_SESSION['user']));
    }
});
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////////LEARNER INVITATIONS///////////////////////////////
$app->get('/invitations', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $invitationList = DB::query("SELECT * FROM invitations WHERE leanerID=%d", $_SESSION['user']['ID']);
        $app->render('invitations.html.twig', array('learner' => $_SESSION['user'], 'invitationList' => $invitationList));
    }
});
//////////////////////////////////////////////////////////////////////////////////
/////////////////////////////LEARNER TAKE QUIZ////////////////////////////////////
$app->get('/takequiz/:id', function($quizID) use ($app) {
    $quizID = $quizID;
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $questionList = DB::query("SELECT * FROM questions WHERE quizID=%d", $quizID);
        $question1 = $questionList[0];
        $question2 = $questionList[1];
        $question3 = $questionList[2];
        print_r($question2);
        $question4 = $questionList[3];
        $question5 = $questionList[4];
        $question6 = $questionList[5];

        $app->render('takequiz.html.twig', array('learner' => $_SESSION['user'],
            'q1' => $question1,
            'q2' => $question2,
            'q3' => $question3,
            'q4' => $question4,
            'q5' => $question5,
            'q6' => $question6));
    }
});

$app->post('/takequiz/:id', function($quizID) use ($app) {
    $q1id = $app->request->post('q1id');
    $q1answer = $app->request->post('q1');
    $q2id = $app->request->post('q2id');
    $q2answer = $app->request->post('q2');
    $q3id = $app->request->post('q3id');
    $q3answer = $app->request->post('q3');
    $q4id = $app->request->post('q4id');
    $q4answer = $app->request->post('q4');
    $q5id = $app->request->post('q5id');
    $q5answer = $app->request->post('q5');
    $q6id = $app->request->post('q6id');
    $q6answer = $app->request->post('q6');

    DB::insert('answers', array(
        'learnerID' => $_SESSION['user']['ID'],
        'questionID' => $q1id,
        'answer' => $q1answer
    ));
    DB::insert('answers', array(
        'learnerID' => $_SESSION['user']['ID'],
        'questionID' => $q2id,
        'answer' => $q2answer
    ));
    DB::insert('answers', array(
        'learnerID' => $_SESSION['user']['ID'],
        'questionID' => $q3id,
        'answer' => $q3answer
    ));
    DB::insert('answers', array(
        'learnerID' => $_SESSION['user']['ID'],
        'questionID' => $q4id,
        'answer' => $q4answer
    ));
    DB::insert('answers', array(
        'learnerID' => $_SESSION['user']['ID'],
        'questionID' => $q6id,
        'answer' => $q6answer
    ));

    $answer = DB::queryFirstRow("SELECT answer FROM questions WHERE ID=%s", $q1id);
    $answer1 = $answer['answer'];
    $answer = DB::queryFirstRow("SELECT answer FROM questions WHERE ID=%s", $q2id);
    $answer2 = $answer['answer'];
    $answer = DB::queryFirstRow("SELECT answer FROM questions WHERE ID=%s", $q3id);
    $answer3 = $answer['answer'];
    $answer = DB::queryFirstRow("SELECT answer FROM questions WHERE ID=%s", $q4id);
    $answer4 = $answer['answer'];
    $answer = DB::queryFirstRow("SELECT answer FROM questions WHERE ID=%s", $q5id);
    $answer5 = $answer['answer'];
    $answer = DB::queryFirstRow("SELECT answer FROM questions WHERE ID=%s", $q6id);
    $answer6 = $answer['answer'];

    $score = 0;
    if ($q1answer === $answer1) {
        $score = $score + 1;
    }
    if ($q2answer === $answer2) {
        $score = $score + 1;
    }

    if ($q3answer === $answer3) {
        $score = $score + 1;
    }
    if ($q4answer === $answer4) {
        $score = $score + 1;
    }
    if ($q5answer === $answer5) {
        $score = $score + 1;
    }
    if ($q6answer === $answer6) {
        $score = $score + 1;
    }

    DB::insert('results', array(
        'quizID' => $quizID,
        'learnerID' => $_SESSION['user']['ID'],
        'result' => $score
    ));
    $resultList = DB::query("SELECT * FROM results WHERE learnerID=%d", $_SESSION['user']['ID']);
    $app->render('result.html.twig', array('learner' => $_SESSION['user'], 'resultList' => $resultList));
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////////LEARNER RESULTS////////////////////////////////////
$app->get('/results', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $resultList = DB::query("SELECT * FROM results WHERE learnerID=%d", $_SESSION['user']['ID']);
        $app->render('result.html.twig', array('learner' => $_SESSION['user'], 'resultList' => $resultList));
    }
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////////LEARNER LOGOUT////////////////////////////////////
$app->get('/logoutlearner', function() use ($app, $log) {
    unset($_SESSION['user']);
    $app->render('logout_success.html.twig');
});
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////LEARNER ACCOUNT///////////////////////////////////////
$app->get('/learneraccount', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $app->render('learneraccount.html.twig', array('learner' => $_SESSION['user']));
    }
});

$app->post('/learneraccount', function() use ($app) {
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
            'email' => $email), "ID=%d", $_SESSION['user']['ID']);
        //$id = DB::insertId();
        $app->render('change_success_educator.html.twig');
    }
});
$app->run();
