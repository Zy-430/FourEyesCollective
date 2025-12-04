<?php
require '../_base.php';
//require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    //header('Location: User/login.php');
    //exit();
}


// TEMPORARY: fake user data for testing without DB
$user = (object)[
    'user_id' => 'ME0001',
    'name' => 'Test User',
    'email' => 'test@example.com',
    'gender' => 'Male',
    'phone' => '012-3456789',
    'date_of_birth' => '2000-01-01'
];

// Get the current logged-in user_id from the session
//$user_id = $_SESSION['user_id'];

// Retrieve user data from the database
// $stmt = $_db->prepare("SELECT * from user WHERE user_id = ?");
// $stmt->execute([$user_id]);
// $user = $stmt->fetch();

// If no user found, show error
if (!$user){
    echo "User not found.";
    exit();
}

?>

<!doctype html>
<html>
    <head>
        <meta charset = "utf-8">
        <title>My Profile</title>
    </head>

    <body>
        <h1>My Profile</h1>

        <!-- displaying all user information -->
        <p><strong>User ID:</strong> <?= encode($user->user_id) ?></p>
        <p><strong>Name:</strong> <?= encode($user->name) ?></p>
        <p><strong>Email:</strong> <?= encode($user->email) ?></p>
        <p><strong>Gender:</strong> <?= encode($user->gender) ?></p>
        <p><strong>Phone:</strong> <?= encode($user->phone) ?></p>
        <p><strong>DOB:</strong> <?= encode($user->date_of_birth) ?></p>

        <p>
            <a href="profile_edit.php">Edit Profile</a> |
            <a href="profile_photo.php">Change Profile Photo</a> |
            <a href="profile_change_password.php">Change Password</a> |
            <a href="profile_address_list.php">My Address</a>
        </p>
    </body>
</html>

$_title = 'Home | User Profile';
include '../_head.php';
?>
<h1>Hello</h1>

<?php
include '../_foot.php';