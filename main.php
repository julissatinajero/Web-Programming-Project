<?php  // Final -> Main page
    require_once('login.php');
    echo <<<_END
        <html>
            <head>
                <title>FINAL</title>
            </head>
            <body>
                <h1>Welcome to Cucinare</h1>
                <p> 
                    Check the ingredient(s) you would like to see in your recipe and submit to start your search! <br>
                    Note: You are unable to favorite a recipe until you sign in. 
                    <a href=http://localhost:8080/sign_in.php>Click here to log in or sign-up</a>
                </p>
                <form method='post' action='main.php'>
                    <input type="checkbox" id="ingredient1" name="ingredient[]" value="carrot">
                    <label for="ingredient1">carrot</label><br>
                    <input type="checkbox" id="ingredient2" name="ingredient[]" value="chicken">
                    <label for="ingredient2">chicken</label><br>
                    <input type="checkbox" id="ingredient3" name="ingredient[]" value="tomato">
                    <label for="ingredient3">tomato</label><br>
                    <input type="checkbox" id="ingredient4" name="ingredient[]" value="egg">
                    <label for="ingredient4">egg</label><br>
                    <input type="checkbox" id="ingredient5" name="ingredient[]" value="beef">
                    <label for="ingredient5">beef</label><br>
                    <br><input type="submit" name='search' value='Search'>
                </form>
            </body>
        </html>
    _END;

    // Establish connection with db
    $conn = new mysqli($hn, $un, $pw, $db);
    if($conn->connect_error)die(errorMsg("DATABASE CONNECTION ERROR"));

    // Any user can seach for recipes 
    if(isset($_POST['search'])){
        if(!empty($_POST['ingredient'])) { 
            $ingredient_ids = array();   
            foreach($_POST['ingredient'] as $value){
                $temp_id = get_ingredientID($conn, $value);
                array_push($ingredient_ids, $temp_id);
            }
            $recipe_IDs = get_recipeIDs($conn, $ingredient_ids);
            display_recipe_info($conn, $recipe_IDs);
        }
        else{
            errorMsg("Select ingredients to start your search.");
        }
    }

    // Only users with accounts can favorite a recipe
    session_start();
    if(isset($_POST['favorite']) && isset($_POST['recipeID'])){
        // Check the session is valid
        if(isset($_SESSION['check']) == hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'])){
            $user_id = mysql_sanatize($conn, $_SESSION['userID']);
            $recipe_id = mysql_sanatize($conn, $_POST['recipeID']);
            destroy_session_and_data();
            
            addFavorite($conn, $user_id, $recipe_id);
            echo "Added recipe to your favorites. Please navigate to your profile again using the login link to see your cookbook.";
        }
        else{
            errorMsg("You can not favorite recipes until you have signed in.");
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

    // This function adds the favorited recipe to a user's cookbook
    // The user_cookbooks table has two values: userID and recipeID. A userID can appear multiple times and be matched to uniqe recipeIDs
    function addFavorite($connection, $user_ID, $recipe_ID){
        $stmt = $connection->prepare("INSERT INTO user_cookbooks VALUES(?,?)");
        $stmt->bind_param('ii', $user_ID, $recipe_ID);
        $stmt->execute();
        $stmt->close();
    }

    // This function matches an ingredient name to it's ID
    // Return the id corresponding to the given ingredient
    function get_ingredientID($connection, $ingredient){
        $selectIngredientQuery = "SELECT * FROM ingredients WHERE ingredientName='$ingredient'"; 
        $selectIngredientResult = $connection->query($selectIngredientQuery);
        $row = $selectIngredientResult->fetch_row();
        $selectIngredientResult->close();
        return($row[0]);
    }

    // This function matches an ingredient ID to a recipe ID that uses that ingredient
    // Return an array of IDs corresponding to the recipes that use the given ingredients
    function get_recipeIDs($connection, $ingredient_ID_array){
        $IDs = array();
        foreach ($ingredient_ID_array as $ingredientID){
            $selectRecipeIDQuery = "SELECT * FROM recipe_ingredient WHERE ingredientID='$ingredientID'"; 
            $selectRecipeIDResult = $connection->query($selectRecipeIDQuery);
            $row = $selectRecipeIDResult->fetch_row();
            // If the recipe ID has not yet been added, push it to the array
            if(!in_array($ingredientID, $IDs, true)){
                array_push($IDs, $row[0]);
            }
            $selectRecipeIDResult->close();
        }
        return $IDs;
    }

    // Each recipe has a recipeID, recipeName, cookTime, and description
    // It is assumed that the description contains cooking steps and ingredients used
    function display_recipe_info($connection, $recipeIDs){
        echo <<<_END
            <h3>Recipes</h3>
        _END;
        foreach ($recipeIDs as $recipe){
            $selectRecipeQuery = "SELECT * FROM recipes WHERE recipeID='$recipe'"; 
            $selectRecipeResult = $connection->query($selectRecipeQuery);
            $row = $selectRecipeResult->fetch_row();
            echo <<<_END
                <pre>
                    Recipe Name: $row[1]
                    Cook time: $row[2] minutes
                    Description: $row[3]
                    
                    <form method='post' action='main.php'>
                    <input type="hidden" name="favorite" value="yes">
                    <input type="hidden" name="recipeID" value="$row[0]">
                    <input type="submit" value="Favorite"></form>
                </pre>         
            _END;
            $selectRecipeResult->close();
        }
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

    $conn->close();
?>