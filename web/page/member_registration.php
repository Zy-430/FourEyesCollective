<?php
require '../_base.php';
require '../lib/db.php';


// Registration is only for member ; staff will be add manually through admin mode
// Auto-generate user ID (only for member)
function generateMemberID($db)
{
    $last = $db->query("SELECT user_id FROM users WHERE role='Member'ORDER BY user_id DESC LIMIT 1")->fetchColumn();

    // If no member exists, start with ME0001
    if (!$last) return "ME0001";

    // Else get the last member id  and extract numeric part, increment, and pad with zeros
    $num = intval(substr($last, 2)) + 1;
    return "ME" . str_pad($num, 4, "0", STR_PAD_LEFT);
}

$user_id = generateMemberID($_db);

//Form submit
if (is_post()) {

    //Input
    $email              = req('email');
    $name               = req('name');
    $password           = req('password');
    $confirm_password   = req('confirm_password');
    $gender             = req('gender');
    $phone              = req('phone');

    // To get DOB
    $date               = req('date');
    $month              = req('month');
    $year               = req('year');
    $photo              = get_file('photo');


    //Combine date of birth
    $date_of_birth = "$year-$month-$date";

    //Validate email
    if ($email == '') {
        $_err['email'] = 'Required';
    } else if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } else if (!is_unique($email, 'users', 'email')) {
        $_err['email'] = 'Duplicated email';
    }

    //Validate name
    if ($name == '') {
        $_err['name'] = 'Required';
    } else if (strlen($name) > 100) {
        $_err['name'] = 'Maximum length 100';
    }

    //Validate password
    if ($password == '') {
        $_err['password'] = 'Required';
    } else {
        $password = trim($password);

        //Password format
        // Min length 8 charcater
        if (strlen($password) < 8) {
            $_err['password'] = 'Password must be at least 8 characters';
        }

        // Max length 15 character
        else if (strlen($password) > 15) {
            $_err['password'] = 'Password maximum length is 15 characters';
        }
    }

    //Validate Confirm password 
    if ($confirm_password == '') {
        $_err['confirm_password'] = 'Required';
    } else if ($password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match. Please try again!';
    }

    //Validate gender
    if ($gender == '') {
        $_err['gender'] = 'Required';
    } else if (!array_key_exists($gender, $_genders)) {
        $_err['gender'] = 'Invalid value';
    }

    // Validate photo (optional : user can uplaod / use default image)
    if ($photo && $photo->size > 0) {
        // Only validate if a photo was uploaded
        if (!str_starts_with($photo->type, 'image/')) {
            $_err['photo'] = 'Must be an image';
        } else if ($photo->size > 1 * 1024 * 1024) {
            $_err['photo'] = 'Maximum 1MB';
        }
    }

    //Validate phone number
    if ($phone == '') {
        $_err['phone'] = 'Required';
    } else if (!preg_match('/^[1-9][0-9]{7,9}$/', $phone)) {
        $_err['phone'] = 'Phone number must be in format 0XXXXXXXXX';
    }

    //Validate day , month , year (later combine for date of borth)
    if ($date == '' || $month == '' || $year == '') {
        $_err['date_of_birth'] = 'Date of birth is required';
    } else {
        // Check if date is valid for the selected month
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        if ($date > $days_in_month) {
            $_err['date_of_birth'] = "February has only $days_in_month days in $year";
        } else if (!checkdate($month, $date, $year)) {
            $_err['date_of_birth'] = 'Invalid date of birth';
        } else {
            //Age restriction (member must be at least 12 years old)
            $current_year = date('Y');
            $age = $current_year - $year;
            if ($age < 12) {
                $_err['date_of_birth'] = 'You must be at least 12 years old';
            } else if ($age > 100) {
                $_err['date_of_birth'] = 'Please enter a valid date of birth';
            }
        }
    }

    $registration_date = date('Y-m-d');


    // Insert into database
    if (!$_err) {

        if ($photo && $photo->size > 0) {
            $photo_filename = save_photo($photo, '../images/users');
        } else {
            // Use default photo
            $photo_filename = 'default_user.png';
        }

        // Member registration only for member
        // Default status for new members is inactive (wait for email verification)
        $role = 'Member';
        $status = 'Inactive';

        // Get current date for registration_date 
        if (empty($registration_date)) {
            $registration_date = date('Y-m-d');
        }

        // Begin transaction
        $_db->beginTransaction();

        $stm = $_db->prepare('
        INSERT INTO users (user_id, role, email, password, name, gender, phone, date_of_birth, photo, registration_date, status)
        VALUES (?, ?, ?, SHA1(?), ?, ?, ?, ?, ?, ?, ?)
    ');

        $stm->execute([
            $user_id,
            $role,
            $email,
            $password,
            $name,
            $gender,
            $phone,
            $date_of_birth,
            $photo_filename,
            $registration_date,
            $status
        ]);

        // Generate verification token
        $verification_token = sha1(uniqid() . rand());

        // Then store verification token (it will expires in 24 hours)
        $stm = $_db->prepare('
            INSERT INTO token (token_id, expire, user_id, type)
            VALUES(?, ADDTIME(NOW(), "24:00"), ?, "verification")
        ');
        $stm->execute([$verification_token, $user_id]);

        $_db->commit();

        // Send verification email
        $verification_url = base("page/activate_account.php?token_id=$verification_token");

        $m = get_mail();
        $m->addAddress($email, $name);
        $m->isHTML(true);
        $m->Subject = 'Verify Your Account - Four Eyes Collective';

        $m->Body =  "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; background: #2c3e50; color: #ddd; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 15px 0; }
                .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; }
                .warning {padding-top: 10px; padding-bottom:10px; color:red; font-style:italic;}
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Four Eyes Collective</h2>
                    <h3>Account Verification</h3>
                </div>
                <div class='content'>
                    <p>Hello " . htmlspecialchars($name) . ",</p>
                    
                    <p>Thank you for registering with Four Eyes Collective!</p>
                    
                    <p>To activate your account, please click the button below:</p>
                    
                    <p style='text-align: center;'>
                        <a href='$verification_url' class='button'>Verify Account</a>
                    </p>
                    
                    <p>Or copy and paste this link into your browser:</p>
                    <p><code>$verification_url</code></p>
                    
                    <div class='warning'>
                        <p>* This link will expire in 24 hours. If you didn't create an account with us, please ignore this email.</p>
                    </div>
                    
                    <p>Best regards,<br>
                    <strong>The Four Eyes Collective Team</strong></p>
                </div>
                <div class='footer'>
                    <p>This is an automated message, please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        if ($m->send()) {
            temp('success', 'Registration successful! Please check your email to verify your account.');
            redirect('login.php');
        } else {
            throw new Exception('Failed to send verification email');
        }
    }
}

// ----------------------------------------------------------------------------
$_title = 'Member Registration';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_1.png">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/user.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>

<body class="registration-page" style="overflow-y: auto;">
    <div class="registration-container" style="max-width: 900px;">
        <div class="registration-header" style="border-radius: 25px 25px 0 0;">
            <div class="header-content">
                <div class="logo-container">
                    <img src="/images/WIS_logo_white.png" alt="Four Eyes Collective Logo" class="header-logo">
                </div>
                <div class="header-text">
                    <h1>Sign up for a free exclusive membership</h1>
                    <p>Join thousands of satisfied customers worldwide</p>
                </div>
            </div>
        </div>

        <form method="post" class="registration-form" enctype="multipart/form-data">
            <div class="form-row">
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="your@email.com" maxlength="100"
                        value="<?= encode($GLOBALS['email'] ?? '') ?>">
                    <?= err('email') ?>
                </div>

                <!-- Name -->
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-control"
                        placeholder="Your full name" maxlength="100"
                        value="<?= encode($GLOBALS['name'] ?? '') ?>">
                    <?= err('name') ?>
                </div>
            </div>

            <div class="form-row">
                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Create a password (8-15 characters)" maxlength="15">
                    <?= err('password') ?>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                        placeholder="Re-enter your password" maxlength="15">
                    <?= err('confirm_password') ?>
                </div>
            </div>

            <div class="form-row">
                <!-- Gender -->
                <div class="form-group">
                    <label>Gender *</label>
                    <div class="radio-group">
                        <?php foreach ($_genders as $id => $text): ?>
                            <div class="radio-option">
                                <input type="radio" id="gender_<?= $id ?>" name="gender" value="<?= $id ?>"
                                    <?= ($GLOBALS['gender'] ?? '') == $id ? 'checked' : '' ?>>
                                <label for="gender_<?= $id ?>"><?= $text ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= err('gender') ?>
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <span class="phone-prefix">+60 &nbsp;</span>
                    <input type="text" id="phone" name="phone" class="form-control-phone"
                        placeholder="123456789"
                        pattern="[1-9][0-9]{7,9}"
                        maxlength="9"
                        value="<?= encode($GLOBALS['phone'] ?? '') ?>">
                    <?= err('phone') ?>
                </div>
            </div>

            <!-- Date of Birth -->
            <div class="form-row">
                <div class="form-group">
                    <label>Profile Photo </label>
                    <div class="photo-upload-container">
                        <label class="photo-upload-label" for="photo" tabindex="0">
                            <div class="photo-preview">
                                <img id="photoPreview" src="/images/upload.png">
                            </div>

                            <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                        </label>
                        <div class="upload-instructions">
                            <p>• Accepted formats: JPG, PNG</p>
                            <p>• Maximum size: 1MB</p>
                            <p>• Optional - you can add later</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Date of Birth *</label>
                    <div class="dob-group">
                        <div class="dob-selectors">
                            <select id="date" name="date" class="dob-select">
                                <option value="">Day</option>
                                <?php foreach ($_days as $id => $text): ?>
                                    <option value="<?= $id ?>" <?= ($GLOBALS['date'] ?? '') == $id ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="month" name="month" class="dob-select">
                                <option value="">Month</option>
                                <?php foreach ($_months as $id => $text): ?>
                                    <option value="<?= $id ?>" <?= ($GLOBALS['month'] ?? '') == $id ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

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
            </div>

            <!-- Submit Buttons -->
            <div class="form-row button-row">
                <button type="submit" class="btn btn-black">Register</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>

            <div class="links-container">
                <div class="back-link">
                    <a href="/">Back to Home</a>
                </div>

                <div class="signin-link">
                    Already a member? <a href="login.php">Sign In</a>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Photo preview function
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>