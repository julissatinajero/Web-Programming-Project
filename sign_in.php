<?php  // Final -> sign-in and sign-up
    require_once('login.php');
    echo <<<_END
        <html>
            <head>
                <title>FINAL</title>
                <script>
                    function validateForm(){
                        var name = document.forms["SignUpForm"]["username_signup"].value;
                        var email = document.forms["SignUpForm"]["email"].value;
                        var password = document.forms["SignUpForm"]["password"].value;

                        // NOTE: each form field is set to 'required'
                        // so I do not check for empty fields
                        if(!(/^[A-Za-z]*$/.test(name))){
                            alert("Name must only contain letters.");
                            return false;
                        }
                        if(!(/^[a-zA-Z0-9]+@[a-zA-Z0-9]+\.[a-zA-Z]{2,4}$/.test(email))){
                            alert("Enter a valid email address.");
                            return false;
                        }
                        if(!(/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$.test(password))){
                            alert("Password must be 8 characters long and include at least one letter and one number.");
                            return false;
                        }
                        return true;
                    }
                </script>
            </head>
            <body>
                <form name='SignUpForm' method='post' onsubmit="return validateForm()">
                    <h2>Sign-Up</h2>
                    Username: <input type='text' name='username_signup' required><br><br>
                    E-mail: <input type='text' name='email' required><br><br>
                    Password:  <input type ="password" name='password' required><br><br>
                    <input type='submit' name='submit_signup' value='Submit'>
                </form>
                <br>
                <form name='LoginInForm' method='post'>
                    <h2>Login</h2>
                    Username: <input type='text' name='username_login' required><br><br>
                    Password: <input type ="password" name='pass_login' required><br><br>
                    <input type='submit' name='submit_login' value='Submit'>
                </form>
                <p><a href=http://localhost:8080/main.php>Click here to return to the main page.</a></p>
            </body>
    _END;

    // Establish connection 
    $conn = new mysqli($hn, $un, $pw, $db);
    if($conn->connect_error)die(errorMsg("DATABASE CONNECTION ERROR"));
    destroy_session_and_data(); // Ensure no session information exists at the start of the page

    $name = '';
    $id = ''; // Assigned by table
    $email = '';
    $pass = '';
    $token = '';

    // If a user has properly signed up, insert the new credentials to the database
    if(isset($_POST['submit_signup'])){
        $username = mysql_sanatize($conn, $_POST['username_signup']);
        $email = mysql_sanatize($conn, $_POST['email']);
        $pass = mysql_sanatize($conn, $_POST['password']);
        $token = password_hash($pass, PASSWORD_DEFAULT);
        
        // Placeholder: Prepared statement to insert new user
        $stmt = $conn->prepare("INSERT INTO credentials VALUES(?,?,?,?)");
        $stmt->bind_param('isss', $id, $username, $email, $token);
        $stmt->execute();
        $stmt->close();

        echo "Log in to see your cookbook and be able to start adding recipes.";
    }
    //If a user tries to log in, ensure the credentials exist in the database
    elseif(isset($_POST['submit_login'])){
        $username = mysql_sanatize($conn, $_POST['username_login']);
        $pass = mysql_sanatize($conn, $_POST['pass_login']);
        
        // If the user exists, display their cookbook containing their favorite recipes
        if(userExists($conn, $username, $pass)) {
            $id = set_userID($conn, $username);
            session_start();
            $_SESSION['userID'] = $id;  
            $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
            
            // Re-direct to profile page with cookbook
            header("Location:http://localhost:8080/user_profile.php");
            exit();
        }
        else{
            errorMsg("Invalid username or password.");
        }
    }
    
    // HELPER FUNCTIONS
    // Error message function
    function errorMsg($msg){
        echo <<<_END
        <h3>$msg</h3>
        <p>Please try again.</p>
        _END;
    }

    // This function checks if the specified user exists in the credentials table
    // Return TRUE if the user exists, otherwise FALSE
    function userExists($connection, $user, $password){
        $answer = FALSE;

        // Check if the given username exists
        $selectUserQuery = "SELECT * FROM credentials WHERE username='$user' LIMIT 1"; 
        $selectUserResult = $connection->query($selectUserQuery);
        $rows = $selectUserResult->num_rows; // Rows needs to be 1 for the username to exist

        if($rows>0) {
            $hashed_pass = $selectUserResult->fetch_object()->password;
            // Compare the given password with the hashed password
            $isVerified = password_verify($password, $hashed_pass);
            // If the password is verified, the user exists
            if($isVerified){
                $answer = TRUE;
            }
        } 
        $selectUserResult->close();
        return $answer;
    }

    // This function find's the ID of the specified user
    // Return the user's ID
    function set_userID($connection, $user){
        $selectUserQuery = "SELECT * FROM credentials WHERE username='$user' LIMIT 1"; 
        $selectUserResult = $connection->query($selectUserQuery);

        $row = $selectUserResult->fetch_row();
        $selectUserResult->close();
        return $row[0];
    }

    // Sanitize function
    function mysql_sanatize($connection, $string){
        $answer = htmlentities($connection->real_escape_string($string));
        return $answer;
    } 

    // Destroy session data
    function destroy_session_and_data(){
        session_start();
        $_SESSION = array();
        setcookie(session_name(), "", time()-2592000, "/");
        session_destroy();
    }

    $conn->close();
?>