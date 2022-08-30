<?php  // Final -> User Profile
    require_once('login.php');

    // Establish connection with db
    $conn = new mysqli($hn, $un, $pw, $db);
    if($conn->connect_error)die(errorMsg("DATABASE CONNECTION ERROR"));

    session_start();
    if(isset($_SESSION['check']) == hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'])){
        $user_id = mysql_sanatize($conn, $_SESSION['userID']);
        ini_set('session.gc_maxlifetime', 60*60*24); // Session info will only last one day
        
        echo <<<_END
        <html>
            <head>
                <title>FINAL</title>
            </head>
            <body>
                <h1>Profile</h1>
                <p> 
                <a href=http://localhost:8080/main.php>Click here to go to the main page.</a><br><br>
                <form action='sign_in.php'><input type="submit" name='signout' value='Sign-Out'></form>
                </p>
            </body>
        </html>
        _END;

        display_cookbook($conn, $user_id);
    }

    // If a user signs-out
    if(isset($_POST['signout'])){
        destroy_session_and_data();
        // Action set to redirect user to sign-in page
    }
    
    // HELPER FUNCTIONS
    // Error message function
    function errorMsg($msg){
        echo <<<_END
        <h3>$msg</h3>
        <p>Please try again.</p>
        _END;
    }

    // Destroy session data
    function destroy_session_and_data(){
        session_start();
        $_SESSION = array();
        setcookie(session_name(), "", time()-2592000, "/");
        session_destroy();
    }
    
    // Sanitize function
    function mysql_sanatize($connection, $string){
        $answer = htmlentities($connection->real_escape_string($string));
        return $answer;
    } 

    // This function displays the cookbook of the specified user
    function display_cookbook($connection, $ID){
        $selectUserQuery = "SELECT * FROM user_cookbooks WHERE userID='$ID'"; 
        $selectUserResult = $connection->query($selectUserQuery);
        $rows = $selectUserResult->num_rows; 

        echo <<<_END
            <h3>Your Cookbook</h3>
        _END;

        if($rows < 1){
            echo "You have not yet added any favorite recipes. Redirect to the main page to start adding favorites!";
            exit();
        }

        // Collect the all the recipe ids that the user has favorited
        $recipe_IDs = array();
        for ($i = 0; $i < $rows; ++$i){
            $selectUserResult->data_seek($i); 
            array_push($recipe_IDs, $selectUserResult->fetch_assoc()['recipeID']);
        }
        $selectUserResult->close();
        
        // Display the content for each recipe
        foreach ($recipe_IDs as $recipe_id){
            $selectRecipeQuery = "SELECT * FROM recipes WHERE recipeID='$recipe_id'"; 
            $selectRecipeResult = $connection->query($selectRecipeQuery);
            $row = $selectRecipeResult->fetch_row();
            
            echo <<<_END
            <pre>
                Recipe Name: $row[1]
                Cook time: $row[2] minutes
                Description: $row[3]
            </pre>
            _END;

            $selectRecipeResult->close();
        }
    }

    $conn->close();
?>