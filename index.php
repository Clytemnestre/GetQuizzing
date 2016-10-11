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
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {

        $app->render('educatorhome.html.twig', array('educator' => $_SESSION['user']));
    }
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////////EDUCATOR CREATE QUIZ///////////////////////////////
$app->get('/createquiz', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $app->render('createquiz.html.twig');
    }
});

$app->post('/createquiz', function() use ($app) {
    $errorList = array();
////////////////////////////////////////////////////////////////////////////////
/////////////////////////QUESTION 1/////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
    $title = $app->request->post('quizname');
    if ((strlen($title) < 3) || (strlen($title) > 100)) {
        array_push($errorList, 'The title of you quiz must be between 3 and 150 characters long');
    }

    $question1 = $app->request->post('q1');
    if (empty($question1) || (strlen($question1) < 5) || (strlen($question1) > 500)) {
        array_push($errorList, 'The first question must be between 5 and 500 characters');
    }

    $q1c1 = $app->request->post('q1c1');
    if ((empty($q1c1)) || (strlen($q1c1) > 500)) {
        array_push($errorList, 'The first choice in the first question is either empty or above 500 chatacters.');
    }

    $q1c2 = $app->request->post('q1c2');
    if ((empty($q1c2)) || (strlen($q1c2) > 500)) {
        array_push($errorList, 'The second choice in the first question is either empty or above 500 chatacters.');
    }
    $q1c3 = $app->request->post('q1c3');
    if ((empty($q1c3)) || (strlen($q1c3) > 500)) {
        array_push($errorList, 'The third choice in the first question is either empty or above 500 chatacters.');
    }
    $q1c4 = $app->request->post('q1c4');
    if ((empty($q1c4)) || (strlen($q1c4) > 500)) {
        array_push($errorList, 'The fourth choice in the first question is either empty or above 500 chatacters.');
    }

    $answer1 = $app->request->post('question1');
    if (empty($answer1)) {
        array_push($errorList, 'please select the answer for the first question');
    }
////////////////////////////////////////////////////////////////////////////////
/////////////////////////QUESTION 2/////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////   
    $question2 = $app->request->post('q2');
    if (empty($question2) || (strlen($question2) < 5) || (strlen($question2) > 500)) {
        array_push($errorList, 'The second question must be between 5 and 500 characters');
    }

    $q2c1 = $app->request->post('q2c1');
    if ((empty($q2c1)) || (strlen($q2c1) > 500)) {
        array_push($errorList, 'The first choice in the second question is either empty or above 500 chatacters.');
    }

    $q2c2 = $app->request->post('q2c2');
    if ((empty($q2c2)) || (strlen($q2c2) > 500)) {
        array_push($errorList, 'The second choice in the second question is either empty or above 500 chatacters.');
    }
    $q2c3 = $app->request->post('q2c3');
    if ((empty($q2c3)) || (strlen($q2c3) > 500)) {
        array_push($errorList, 'The third choice in the second question is either empty or above 500 chatacters.');
    }
    $q2c4 = $app->request->post('q2c4');
    if ((empty($q2c4)) || (strlen($q2c4) > 500)) {
        array_push($errorList, 'The fourth choice in the second question is either empty or above 500 chatacters.');
    }

    $answer2 = $app->request->post('question2');
    if (empty($answer2)) {
        array_push($errorList, 'please select the answer for the first question');
    }

////////////////////////////////////////////////////////////////////////////////
/////////////////////////QUESTION 3/////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////   
    $question3 = $app->request->post('q3');
    if (empty($question3) || (strlen($question3) < 5) || (strlen($question3) > 500)) {
        array_push($errorList, 'The third question must be between 5 and 500 characters');
    }

    $q3c1 = $app->request->post('q3c1');
    if ((empty($q3c1)) || (strlen($q3c1) > 500)) {
        array_push($errorList, 'The first choice in the third question is either empty or above 500 chatacters.');
    }

    $q3c2 = $app->request->post('q3c2');
    if ((empty($q3c2)) || (strlen($q3c2) > 500)) {
        array_push($errorList, 'The second choice in the third question is either empty or above 500 chatacters.');
    }
    $q3c3 = $app->request->post('q3c3');
    if ((empty($q3c3)) || (strlen($q3c3) > 500)) {
        array_push($errorList, 'The third choice in the third question is either empty or above 500 chatacters.');
    }
    $q3c4 = $app->request->post('q3c4');
    if ((empty($q3c4)) || (strlen($q3c4) > 500)) {
        array_push($errorList, 'The fourth choice in the third question is either empty or above 500 chatacters.');
    }

    $answer3 = $app->request->post('question3');
    if (empty($answer3)) {
        array_push($errorList, 'please select the answer for the third question');
    }
////////////////////////////////////////////////////////////////////////////////
/////////////////////////QUESTION 4/////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////   
    $question4 = $app->request->post('q4');
    if (empty($question4) || (strlen($question4) < 5) || (strlen($question4) > 500)) {
        array_push($errorList, 'The fourth question must be between 5 and 500 characters');
    }

    $q4c1 = $app->request->post('q4c1');
    if ((empty($q4c1)) || (strlen($q4c1) > 500)) {
        array_push($errorList, 'The first choice in the fourth question is either empty or above 500 chatacters.');
    }

    $q4c2 = $app->request->post('q4c2');
    if ((empty($q4c2)) || (strlen($q4c2) > 500)) {
        array_push($errorList, 'The second choice in the fourth question is either empty or above 500 chatacters.');
    }
    $q4c3 = $app->request->post('q4c3');
    if ((empty($q4c3)) || (strlen($q4c3) > 500)) {
        array_push($errorList, 'The third choice in the fourth question is either empty or above 500 chatacters.');
    }
    $q4c4 = $app->request->post('q4c4');
    if ((empty($q4c4)) || (strlen($q4c4) > 500)) {
        array_push($errorList, 'The fourth choice in the fourth question is either empty or above 500 chatacters.');
    }

    $answer4 = $app->request->post('question4');    
    if (empty($answer4)) {
        array_push($errorList, 'please select the answer for the fourth question');
    }    

////////////////////////////////////////////////////////////////////////////////
/////////////////////////QUESTION 5/////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////   
    $question5 = $app->request->post('q5');
    if (empty($question5) || (strlen($question5) < 5) || (strlen($question5) > 500)) {
        array_push($errorList, 'The fifth question must be between 5 and 500 characters');
    }

    $q5c1 = $app->request->post('q5c1');
    if ((empty($q5c1)) || (strlen($q5c1) > 500)) {
        array_push($errorList, 'The first choice in the fifth question is either empty or above 500 chatacters.');
    }

    $q5c2 = $app->request->post('q5c2');
    if ((empty($q5c2)) || (strlen($q5c2) > 500)) {
        array_push($errorList, 'The second choice in the fifth question is either empty or above 500 chatacters.');
    }
    $q5c3 = $app->request->post('q5c3');
    if ((empty($q5c3)) || (strlen($q5c3) > 500)) {
        array_push($errorList, 'The third choice in the fifth question is either empty or above 500 chatacters.');
    }
    $q5c4 = $app->request->post('q5c4');
    if ((empty($q5c4)) || (strlen($q5c4) > 500)) {
        array_push($errorList, 'The fourth choice in the fifth question is either empty or above 500 chatacters.');
    }

    $answer5 = $app->request->post('question5'); 
    if (empty($answer5)) {
        array_push($errorList, 'please select the answer for the fifth question');
    }
////////////////////////////////////////////////////////////////////////////////
/////////////////////////QUESTION 6/////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////   
    $question6 = $app->request->post('q6');
    if (empty($question6) || (strlen($question6) < 5) || (strlen($question6) > 500)) {
        array_push($errorList, 'The sixth question must be between 5 and 500 characters');
    }

    $q6c1 = $app->request->post('q6c1');
    if ((empty($q6c1)) || (strlen($q6c1) > 500)) {
        array_push($errorList, 'The first choice in the sixth question is either empty or above 500 chatacters.');
    }

    $q6c2 = $app->request->post('q6c2');
    if ((empty($q6c2)) || (strlen($q6c2) > 500)) {
        array_push($errorList, 'The second choice in the sixth question is either empty or above 500 chatacters.');
    }
    $q6c3 = $app->request->post('q6c3');
    if ((empty($q6c3)) || (strlen($q6c3) > 500)) {
        array_push($errorList, 'The third choice in the sixth question is either empty or above 500 chatacters.');
    }
    $q6c4 = $app->request->post('q6c4');
    if ((empty($q6c4)) || (strlen($q6c4) > 500)) {
        array_push($errorList, 'The fourth choice in the sixth question is either empty or above 500 chatacters.');
    }

    $answer6 = $app->request->post('question6'); 
    if (empty($answer6)) {
        array_push($errorList, 'please select the answer for the sixth question');
    }   
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////    
    if ($errorList) {       
        $app->render('createquiz.html.twig', array(
            'errorList' => $errorList
        ));
    } else {
        DB::insert('quizzes', array(
          'educatorID' => $_SESSION['user']['ID'],
          'title' => $title
          ));
          $quizID = DB::insertId();
        DB::insert('questions', array(
          'body' => $question1,
          'c1' => $q1c1,
          'c2' => $q1c2,
          'c3' => $q1c3,
          'c4' => $q1c4,
          'answer' => $answer1,
          'quizID' => $quizID
          ));
        DB::insert('questions', array(
          'body' => $question2,
          'c1' => $q2c1,
          'c2' => $q2c2,
          'c3' => $q2c3,
          'c4' => $q2c4,
          'answer' => $answer2,
          'quizID' => $quizID
          ));        
        DB::insert('questions', array(
          'body' => $question3,
          'c1' => $q3c1,
          'c2' => $q3c2,
          'c3' => $q3c3,
          'c4' => $q3c4,
          'answer' => $answer3,
          'quizID' => $quizID
          ));
        DB::insert('questions', array(
          'body' => $question4,
          'c1' => $q4c1,
          'c2' => $q4c2,
          'c3' => $q4c3,
          'c4' => $q4c4,
          'answer' => $answer4,
          'quizID' => $quizID
          ));
        DB::insert('questions', array(
          'body' => $question5,
          'c1' => $q5c1,
          'c2' => $q5c2,
          'c3' => $q5c3,
          'c4' => $q5c4,
          'answer' => $answer5,
          'quizID' => $quizID
          ));
        DB::insert('questions', array(
          'body' => $question6,
          'c1' => $q6c1,
          'c2' => $q6c2,
          'c3' => $q6c3,
          'c4' => $q6c4,
          'answer' => $answer6,
          'quizID' => $quizID
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
//////////////////////////////ADD QUESTION TO EXISTING QUIZZES////////////////////
$app->get('/newquestion/:id', function() use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $app->render('newquestion.html.twig');
    }
});

$app->post('/newquestion/:id', function($ID) use ($app) {
    $quizID = $ID;
    $body = $app->request->post('questionbody');
    $c1 = $app->request->post('c1');
    $c2 = $app->request->post('c2');
    $c3 = $app->request->post('c3');
    $c4 = $app->request->post('c4');
    $answer = $app->request->post('questionchoice');
    $errorList = array();

    if (!isset($answer)) {
        array_push($errorList, "select the correct answer");
    }

    if ((strlen($body) < 10) || (strlen($body) > 500)) {
        array_push($errorList, "question must be between 10 and 500 characters");
    }

    if (strlen($c1) < 1) {
        array_push($errorList, "enter more text in the first text box");
    }

    if (strlen($c2) < 1) {
        array_push($errorList, "enter more text in the second text box");
    }

    if (strlen($c3) < 1) {
        array_push($errorList, "enter more text in the third text box");
    }

    if (strlen($c4) < 1) {
        array_push($errorList, "enter more text in the fourth text box");
    }

    if ($errorList) {
        // STATE 3: submission failed        
        $app->render('newquestion.html.twig', array(
            'errorList' => $errorList
        ));
    } else {
        // STATE 2: submission successful
        DB::insert('questions', array(
            'quizID' => $quizID,
            'body' => $body,
            'c1' => $c1,
            'c2' => $c2,
            'c3' => $c3,
            'c4' => $c4,
            'answer' => $answer
        ));
        //$id = DB::insertId();
        $app->render('addquestion_success.html.twig');
    }
});
//////////////////////////////////////////////////////////////////////////////////
////////////////////////////VISUALIZE A QUIZZ/////////////////////////////////////
$app->get('/seeQuiz/:id', function($quizID) use ($app) {
    if (!isset($_SESSION['user'])) {
        $app->render('notloggedinwarning.html.twig');
    } else {
        $questionList = DB::query("SELECT * FROM questions WHERE quizID=%d", $quizID);
        $app->render('fullquiz.html.twig', array('questionList' => $questionList));
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
        }
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
    if ($q1answer === $answer1){
        $score = $score + 1;
    }
    if ($q2answer === $answer2){
        $score = $score + 1;
    }
    
    if ($q3answer === $answer3){
        $score = $score + 1;
    }
    if ($q4answer === $answer4){
        $score = $score + 1;
    }
    if ($q5answer === $answer5){
        $score = $score + 1;
    }
    if ($q6answer === $answer6){
        $score = $score + 1;
    }
    
    DB::insert('results', array(
                'quizID' => $quizID,
                'learnerID' => $_SESSION['user']['ID'],
                'result' => $score
            ));
    
    echo "yay";
});
//////////////////////////////////////////////////////////////////////////////////
///////////////////////////////LEARNER LOGOUT////////////////////////////////////
$app->get('/logoutlearner', function() use ($app, $log) {
    $_SESSION['user'] = array();
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
