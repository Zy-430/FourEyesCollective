<?php
require '../_base.php';

if (is_post()) {

    //Input
    $email = req('email');
    $name = req('name');
    $password = req('password');
    $confirm_password = req('confirm_password');
    $gender = req('gender');
    $phone_number = req('phone_number');
    $date = req('date');
    $month = req('month');
    $year = req('year');
    $registration_date = req('registration_date');

    //Combine date of birth
    $date_of_birth = "$year-$month-$date";

    //Validate email
    if($email == ''){
        $_err['email'] = 'Required';
    }
    else if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $_err['email'] = 'Invalid email format';
    }

    //Validate name
    if($name == ''){
        $_err['name'] = 'Required';
    }
    else if (strlen($name) > 100) {
        $_err['name'] = 'Maximum length 100';
    }

    //Validate password
    if($password == ''){
        $_err['password'] = 'Required';
    }
    else {
        $password = trim($password);

        //Password format
        // Min length 8 charcater
        if(strlen($password) < 8){
            $_err['password'] = 'Password must be at least 8 characters';
        }

        // Max length 15 character
        else if(strlen($password) > 15){
             $_err['password'] = 'Password maximum length is 15 characters';
        }

        // At least one uppercase
        else if(!preg_match('/[A-Z]/', $password)){
             $_err['password'] = 'Password must contain at least one uppercase letter';
        }

        // At least one lowercase
        else if(!preg_match('/[a-z]/', $password)){
             $_err['password'] = 'Password must contain at least one lowercase letter';
        }

        // At least one number
        else if(!preg_match('/[0-9]/', $password)){
        $_err['password'] = 'Password must contain at least one number';
        }

        // At least one special symbol
        else if(!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)){
        $_err['password'] = 'Password must contain at least one special character';
        }
    }

    //Validate Confirm password 
    if($confirm_password == ''){
        $_err['confirm_password'] = 'Required';
    }
    else if($password !== $confirm_password){
        $_err['confirm_password'] = 'Passwords do not match. Please try again!';
    }

     //Validate gender
    if($gender == ''){
        $_err['gender'] = 'Required';
    }
    else if (!array_key_exists($gender, $_genders)) {
        $_err['gender'] = 'Invalid value';
    }

    //Validate phone number
    if($phone_number == ''){
        $_err['phone_number'] = 'Required';
    }
    else if(!preg_match('/^0\d{2}-\d{7}$/', $phone_number)){
        $_err['phone_number'] = 'Phone number must be in format 0XX-XXXXXXX';
    }

    //Validate day , month , year (later combine for date of borth)
    if($date == '' || $month == '' || $year == ''){
        $_err['date_of_birth'] = 'Date of birth is required';
    }
    else {
        // Check if date is valid for the selected month
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
        if($date > $days_in_month){
            $_err['date_of_birth'] = "February has only $days_in_month days in $year";
        }
        else if(!checkdate($month, $date, $year)){
            $_err['date_of_birth'] = 'Invalid date of birth';
        }
        else {
            //Age restriction (member must be at least 15 years old)
            $current_year = date('Y');
            $age = $current_year - $year;
            if($age < 15){
                $_err['date_of_birth'] = 'You must be at least 15 years old';
            }
            else if($age > 100){
                $_err['date_of_birth'] = 'Please enter a valid date of birth';
            }
        }
    }

    //Output
    if(!$_err){
        temp('info', "Registration Successful! Welcome $name");
        temp('success_message', "Thank you for registering! Your account has been created successfully.");
    
        // Store user data for display
        $user_data = (object)[
            'name' => $name,
            'email' => $email, 
            'phone_number' => $phone_number,
            'gender' => $_genders[$gender] ?? $gender,
            'date_of_birth' => date('F j, Y', strtotime("$year-$month-$date")),
            'registration_date' => date('F j, Y g:i A')   //F- full month , j- day of month, Y- year
        ];
        temp('user_data', $user_data);
    
        redirect('registration_success.php'); 
    }
    
}

// ----------------------------------------------------------------------------
$_title = 'Member Registration';
include '../_head.php';
?>

<div class="registration-container">
    <div class="registration-header">
        <h1>Sign up for a free exclusive membership</h1>
        <p>Join thousands of satisfied customers worldwide</p>
    </div>

    <form method="post" class="registration-form">
        <!-- Email -->
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" 
                   placeholder="your@email.com" maxlength="60"
                   value="<?= encode($GLOBALS['email'] ?? '') ?>">
            <?= err('email') ?>
        </div>

        <!-- Name -->
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control" 
                   placeholder="Your name" maxlength="100"
                   value="<?= encode($GLOBALS['name'] ?? '') ?>">
            <?= err('name') ?>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control" 
                   placeholder="Create a password" maxlength="15">
            <?= err('password') ?>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="confirm_password">Password confirmation</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                   placeholder="Confirm your password" maxlength="15">
            <?= err('confirm_password') ?>
        </div>

        <!-- Gender -->
        <div class="form-group">
            <label>What is your gender?</label>
            <div class="radio-group">
                <?php foreach ($_genders as $id => $text): ?>
                    <label class="radio-option">
                        <input type="radio" name="gender" value="<?= $id ?>" 
                               <?= ($GLOBALS['gender'] ?? '') == $id ? 'checked' : '' ?>>
                        <span class="radio-label"><?= $text ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?= err('gender') ?>
        </div>

        <!-- Phone Number -->
        <div class="form-group">
            <label for="phone_number">Phone Number</label>
            <input type="text" id="phone_number" name="phone_number" class="form-control" 
                   placeholder="012-3456789" 
                   value="<?= encode($GLOBALS['phone_number'] ?? '') ?>">
            <?= err('phone_number') ?>
        </div>

        <!-- Date of Birth -->
        <div class="form-group">
            <label>When is your Birthday?</label>
            <div class="dob-group">
                <div>
                    <select id="date" name="date" class="dob-select">
                        <option value="">Day</option>
                        <?php foreach ($_days as $id => $text): ?>
                            <option value="<?= $id ?>" <?= ($GLOBALS['date'] ?? '') == $id ? 'selected' : '' ?>>
                                <?= $text ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select id="month" name="month" class="dob-select">
                        <option value="">Month</option>
                        <?php foreach ($_months as $id => $text): ?>
                            <option value="<?= $id ?>" <?= ($GLOBALS['month'] ?? '') == $id ? 'selected' : '' ?>>
                                <?= $text ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select id="year" name="year" class="dob-select">
                        <option value="">Year</option>
                        <?php foreach ($_years as $id => $text): ?>
                            <option value="<?= $id ?>" <?= ($GLOBALS['year'] ?? '') == $id ? 'selected' : '' ?>>
                                <?= $text ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?= err('date_of_birth') ?>
        </div>

        <!-- Newsletter -->
        <div class="checkbox-group">
            <input type="checkbox" id="newsletter" name="newsletter" value="1">
            <label for="newsletter" class="checkbox-label">
                Subscribe to our Newsletter
            </label>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="btn btn-primary">Register</button>

        <!-- Sign In Link -->
        <div class="signin-link">
            Already a member? <a href="/User/login.php">Sign In</a>
        </div>
    </form>
</div>

<?php
include '../_foot.php';