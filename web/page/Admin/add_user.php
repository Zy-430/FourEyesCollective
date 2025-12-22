<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
$_title = 'Add User';

auth('Admin');
$role = req('role', 'Member');

if ($role === 'Admin') {
    $user_id = generateAdminID($_db);
} else {
    $user_id = generateMemberID($_db);
}

function generateDefaultPassword($length = 8)
{
    // Characters allowed in the password
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $password;
}

$password = generateDefaultPassword();

// All status is inactive (wait for email verification)
$status = 'Inactive';
$registration_date = date('Y-m-d');


if (is_post()) {

    //Input
    $email              = req('email');
    $name               = req('name');
    $gender             = req('gender');
    $phone              = req('phone');
    $photo              = $_FILES['photo'] ?? null;

    // To get DOB
    $date               = req('date');
    $month              = req('month');
    $year               = req('year');

    //Combine date of birth
    $date_of_birth = "$year-$month-$date";

    //Validate email
    if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } else if (!is_unique($email, 'users', 'email')) {
        $_err['email'] = 'Duplicated email';
    }

    //Validate name
    if (strlen($name) > 100) {
        $_err['name'] = 'Maximum length 100';
    }

    //Validate gender
    if (!array_key_exists($gender, $_genders)) {
        $_err['gender'] = 'Invalid value';
    }

    // Validate photo (optional : user can upload / use default image)
    $photo_filename = 'default_user.png'; // Default filename

    if ($photo && $photo['error'] == 0 && $photo['size'] > 0) {
        // Only validate if a photo was uploaded
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));

        if (!str_starts_with($photo['type'], 'image/')) {
            $_err['photo'] = 'Must be an image';
        } else if (!in_array($ext, $allowed)) {
            $_err['photo'] = 'Only JPG, PNG, GIF files are allowed';
        } else if ($photo['size'] > 1 * 1024 * 1024) {
            $_err['photo'] = 'Maximum 1MB';
        }
    }

    //Validate phone number
    if (!preg_match('/^[1-9][0-9]{7,9}$/', $phone)) {
        $_err['phone'] = 'Phone number must be in format 0XXXXXXXXX';
    }

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


    // Password hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    if (!$_err) {

        // Handle photo upload
        if ($photo && $photo['error'] == 0 && $photo['size'] > 0 && !isset($_err['photo'])) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/images/users/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $ext = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
            $photo_filename = 'user_' . $user_id . '.' . $ext;
            $file_path = $upload_dir . $photo_filename;

            if (move_uploaded_file($photo['tmp_name'], $file_path)) {
                // Photo uploaded successfully
            } else {
                // If upload fails, use default
                $photo_filename = 'user_default.jpg';
            }
        } else {
            // Use default photo
            $photo_filename = 'user_default.jpg';
        }

        // Begin transaction
        $_db->beginTransaction();

        $stm = $_db->prepare('
        INSERT INTO users (user_id, role, email, password, name, gender, phone, date_of_birth, photo, registration_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
    ');

        $stm->execute([
            $user_id,
            $role,
            $email,
            $hashed_password,
            $name,
            $gender,
            $phone,
            $date_of_birth,
            $photo_filename,
            $status
        ]);

        // Generate verification token
        $verification_token = sha1(uniqid() . rand());

        // Then store verification token into db (it will expires in 24 hours)
        $stm = $_db->prepare('
            INSERT INTO token (token_id, expire, user_id, type)
            VALUES(?, ADDTIME(NOW(), "24:00"), ?, "verification")
        ');
        $stm->execute([$verification_token, $user_id]);

        $_db->commit();

        // Send verification email to user
        $verification_url = base("page/activate_account.php?token_id=$verification_token");

        $m = get_mail();
        $m->addAddress($email, $name);
        $m->isHTML(true);

        if ($role === 'Member') {
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
                                <p>A new account has been created for you at Four Eyes Collective by our staff.</p>
                                <p>Here is your temporary login credentials:</p>
                                    <ul>
                                        <li><strong>Email   :</strong> $email</li>
                                        <li><strong>Password:</strong> $password</li>
                                    </ul>
                                <p>Please activate your account and set your own password using the link below:</p>
                    
                                <p style='text-align: center;'>
                                        <a href='$verification_url' class='button'>Verify Account</a>
                                </p>
                    
                                <p>You may also use the link below:</p>
                                    <p><code>$verification_url</code></p>
                    
                                <div class='warning'>
                                    <p>* This link will expire in 24 hours. If you didn't create an account with us, please ignore it.</p>
                                </div>
                    
                                <p>Best regards,<br>
                                <strong>The Four Eyes Collective Team</strong></p>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                </html>
                ";
        } elseif ($role === 'Admin') {
            $m->Subject = 'Admin Account Activation - Four Eyes Collective';

            $m->Body = "
                <!DOCTYPE html>
                <html>
                    <head>
                    <style>l
                        body { font-family: Arial, sans-serif; color:#333; }
                        .container { max-width:600px; margin:auto; }
                        .header { background:#0d1a3f; color:white; padding:20px; text-align:center; }
                        .content { background:#f9f9f9; padding:30px; }
                        .button { background:#0d1a3f; color:#ddd; padding:12px 24px; text-decoration:none; border-radius:6px; }
                        .warning { color:red; font-style:italic; }
                    </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Four Eyes Collective</h2>
                                <h3>Admin Account Activation</h3>
                            </div>

                        <div class='content'>
                            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                            <p>An <strong>administrator account</strong> has been created for you by an existing system administrator.</p>
                            <p>Here is your temporary login credentials:</p>
                            <ul>
                                <li><strong>Email   :</strong> $email</li>
                                <li><strong>Password:</strong> $password</li>
                            </ul>
                            <p>Please activate your account and set your own password using the link below:</p>

                            <p style='text-align:center'>
                                <a href='$verification_url' class='button'>Activate Admin Account</a>
                            </p>

                            <p>You may also use the link below:</p>
                            <p><code>$verification_url</code></p>

                            <div class='warning'>
                                <p>* This link will expire in 24 hours. If you didn't create an account with us, please ignore it.</p>
                            </div>

                            <p>Best regards,<br>
                            <strong>Four Eyes Collective System Administration</strong></p>
                        </div>
                    </div>
                </body>
            </html>
            ";
        }

        try {
            $m->send();
            // Redirect with success message
            header("Location: view_user.php?role=$role&msg=added&user_id=$user_id");
            exit;
        } catch (Exception $e) {
            // Error but still redirect (user was created)
            error_log("Email sending failed: " . $e->getMessage());

            // Redirect but with email_failed message
            header("Location: view_user.php?role=$role&msg=added_no_email&user_id=$user_id");
            exit;
        }
    }
}
?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Add New <?= $role ?></h1>
    </div>

    <div class="form-container ">
        <form method="post" class="add-form" enctype="multipart/form-data">
            <div class="form-row">
                <!-- User ID -->
                <div class="form-group">
                    <label><?= $role ?> ID</label>
                    <input type="text" id="id" name="user_id" class="form-control" required
                        value="<?= encode($user_id) ?>" disabled>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required
                        placeholder="<?= $role ?>@email.com" maxlength="100"
                        value="<?= encode($GLOBALS['email'] ?? '') ?>">
                    <?= err('email') ?>
                </div>

                <!-- Name -->
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required
                        placeholder="Enter <?= $role ?> name" maxlength="100"
                        value="<?= encode($GLOBALS['name'] ?? '') ?>">
                    <?= err('name') ?>
                </div>

            </div>

            <div class="form-row">
                <input type="hidden" name="password" value="<?= encode($password) ?>">
                <!-- Password -->
                <div class="form-group">
                    <label for="password">
                        Password (Auto-generated)
                    </label> <input type="password" id="password_display" class="form-control" required
                        value="<?= encode($password) ?>" readonly>
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <span class="phone-prefix">+60 &nbsp;</span>
                    <input type="text" id="phone" name="phone" class="form-control-phone" required
                        placeholder="123456789"
                        pattern="[1-9][0-9]{7,9}"
                        maxlength="9"
                        value="<?= encode($GLOBALS['phone'] ?? '') ?>">
                    <?= err('phone') ?>
                </div>

                <!-- Gender -->
                <div class="form-group">
                    <label>Gender *</label>
                    <div class="radio-group">
                        <?php foreach ($_genders as $id => $text): ?>
                            <div class="radio-option">
                                <input type="radio" id="gender_<?= $id ?>" name="gender" required value="<?= $id ?>"
                                    <?= ($GLOBALS['gender'] ?? '') == $id ? 'checked' : '' ?>>
                                <label for="gender_<?= $id ?>" style="text-transform:none;"><?= $text ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= err('gender') ?>
                </div>
            </div>

            <!-- Date of Birth -->
            <div class="form-row">
                <div class="form-group">
                    <label>Profile Photo </label>
                    <div class="upload-photo-container">
                        <label class="photo-label" for="photo" tabindex="0">
                            <div class="photo-preview">
                                <img id="photoPreview" src="/images/upload.png">
                            </div>
                            <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                        </label>
                        <div class="upload-instructions">
                            <p>• Accepted formats: JPG, PNG</p>
                            <p>• Maximum size: 1MB</p>
                            <p>• Optional - Provides a default photo</p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Date of Birth *</label>
                    <div class="dob-group">
                        <div class="dob-selectors">
                            <select id="date" name="date" class="dob-select" required>
                                <option value="">Day</option>
                                <?php foreach ($_days as $id => $text): ?>
                                    <option value="<?= $id ?>" <?= ($GLOBALS['date'] ?? '') == $id ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="month" name="month" class="dob-select" required>
                                <option value="">Month</option>
                                <?php foreach ($_months as $id => $text): ?>
                                    <option value="<?= $id ?>" <?= ($GLOBALS['month'] ?? '') == $id ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="year" name="year" class="dob-select" required>
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

                <!-- Registration Date -->
                <div class="form-group">
                    <label for="registration_date">Registration Date</label>
                    <input type="date" id="registration_date" name="registartion_date" class="form-control"
                        value="<?= encode($registration_date) ?>" disabled>
                </div>

            </div>
            <input type="hidden" name="role" value="<?= $role ?>">


            <!-- Submit Buttons -->
            <div class="form-row button-row" style="margin-top: 40px;">
                <button type="button" class="btn btn-white" onclick="location.href='view_user.php?role=<?= $role ?>'">Back</button>
                <button type="submit" class="btn btn-add">Add</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>
        </form>

    </div>

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