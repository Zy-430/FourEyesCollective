<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
$_title = "Add User | Four Eyes Collective";

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

// Member status is pending (wait for email verification)
// Admin status is active immediately
$status = ($role === 'Admin') ? 'Active' : 'Pending';

// Set to force password change on first login
$force_password_change = 1;
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
    } elseif (!preg_match('/^[A-Za-z ]+$/', $name)) {
        $errors[] = "Name can only contain letters and spaces.";
    }

    //Validate gender
    if (!array_key_exists($gender, $_genders)) {
        $_err['gender'] = 'Invalid value';
    }

    // Validate photo (optional : user can upload / use default image)
    $photo_filename = 'user_default.jpg'; // Default filename

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
    } else if (!is_unique($phone, 'users', 'phone')) {
        $_err['phone'] = 'Duplicated phone number';
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
        if ($role === 'Member') {
            if ($age < 12) {
                $_err['date_of_birth'] = 'You must be at least 12 years old';
            } else if ($age > 100) {
                $_err['date_of_birth'] = 'Please enter a valid date of birth';
            }
        } else {
            if ($age < 18) {
                $_err['date_of_birth'] = 'You must be at least 18 years old';
            } else if ($age > 100) {
                $_err['date_of_birth'] = 'Please enter a valid date of birth';
            }
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
            $ext = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? $ext : 'jpg';

            // Create a sanitized username for filename
            $sanitized_name = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
            if (empty($sanitized_name)) {
                $sanitized_name = 'user';
            }

            // Check if filename already exists , then find next available number
            $counter = 1;
            do {
                // Filename format - steven15_01.jpg, steven15_02.jpg...
                $photo_filename = $sanitized_name . sprintf('_%02d', $counter) . '.' . $ext;
                $file_path = $upload_dir . $photo_filename;
                $counter++;
            } while (file_exists($file_path) && $counter <= 99);

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
        INSERT INTO users (user_id, role, email, password, name, gender, phone, date_of_birth, photo, registration_date, status, force_password_change)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
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
            $status,
            $force_password_change
        ]);

        $verification_token = null;


        // If is member then create verification token and store into db (it will expires in 24 hours)
        if ($role === 'Member') {
            $verification_token = sha1(uniqid() . rand());

            $stm = $_db->prepare('
                INSERT INTO token (token_id, expire, user_id, type)
                VALUES(?, ADDTIME(NOW(), "24:00"), ?, "verification")
            ');
            $stm->execute([$verification_token, $user_id]);
        }

        $_db->commit();

        // verification url with token (send in email)
        $verification_url = base("page/activate_account.php?token_id=$verification_token");

        $m = get_mail();
        $m->addAddress($email, $name);
        $m->isHTML(true);

        // Send verifiction email to member for activation (with verification url & default password)
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
                                        <a href='$verification_url' class='button' style='color:white;'>Verify Account</a>
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
        } // Send email to admin to inform them their default password for this system (then first login will force reset password)
        elseif ($role === 'Admin') {
            $m->Subject = 'Admin Account - Four Eyes Collective';

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
                        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; }
                    </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Four Eyes Collective</h2>
                                <h3>Admin Account</h3>
                            </div>

                        <div class='content'>
                            <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                            <p>An administrator account has been created for you.</p>

                            <p>Here is your login credentials:</p>
                                    <ul>
                                        <li><strong>Email   :</strong> $email</li>
                                        <li><strong>Password:</strong> $password</li>
                                    </ul>                            
                                    
                             <p>Your account is already <strong>active</strong>.</p>

                            <p>Please login and change your password immediately:</p>
                            <p style='text-align: center;'>
                                        <a href='" . base("page/login.php") . "' class='button' style='color:white;'>Login Now</a>
                                </p>

                            <p>Best regards,<br>
                            <strong>Four Eyes Collective System Administration</strong></p>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " Four Eyes Collective. All rights reserved.</p>
                            </div>
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
                        value="<?= encode($password) ?>" disabled>
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

            <!-- Profile photo & Date of Birth -->
            <div class="form-row">
                <div class="form-group">
                    <label>Profile Photo </label>
                    <div class="upload-photo-container" id="photoDropZone">
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
                <div style="width: 380px;">
                    <div class="form-group">
                        <label for="registration_date">Registration Date</label>
                        <input type="date" id="registration_date" name="registartion_date" class="form-control"
                            value="<?= encode($registration_date) ?>" disabled>
                    </div>

                    <!-- Status (readonly radios - show only relevant option per role) -->
                    <div class="form-group" style="margin-top: 20px;">
                        <label for="status">Status</label>
                        <div class="radio-group">
                            <?php if ($role === 'Admin'): ?>
                                <div class="radio-option">
                                    <input type="radio" id="status_active" name="status_display" value="Active" checked disabled>
                                    <label for="status_active" style="text-transform:none;">Active</label>
                                </div>
                            <?php else: ?>
                                <div class="radio-option">
                                    <input type="radio" id="status_pending" name="status_display" value="Pending" checked disabled>
                                    <label for="status_pending" style="text-transform:none;">Pending</label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="status" value="<?= encode($status) ?>">
                    </div>
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
    document.addEventListener('DOMContentLoaded', function() {

            const input = document.getElementById('photo');
            const preview = document.getElementById('photoPreview');
            const dropZone = document.getElementById('photoDropZone');
            const label = document.querySelector('.photo-label');

            // Upload validation
            const MAX_SIZE = 1024 * 1024; // 1MB
            const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

            // Handle normal file selection (click the area and choose file)
            input.addEventListener('change', function() {
                if (this.files[0]) handleFile(this.files[0]);
            });

            // Highlight the drop zone during drag
            ['dragenter', 'dragover'].forEach(event => {
                dropZone.addEventListener(event, e => {
                    e.preventDefault();
                    dropZone.classList.add('dragover');
                    label.classList.add('dragover');
                });
            });

            // Remove highlight when drag ends
            ['dragleave', 'drop'].forEach(event => {
                dropZone.addEventListener(event, e => {
                    e.preventDefault();
                    dropZone.classList.remove('dragover');
                    label.classList.remove('dragover');
                });
            });

            // Handle drag-drop file uploaded
            dropZone.addEventListener('drop', function(e) {
                const file = e.dataTransfer.files[0];
                if (file) {
                    input.files = e.dataTransfer.files;  // Assign file to input
                    handleFile(file);  // File validation
                }
            });

            function handleFile(file) {
                // Validate file type
                if (!ALLOWED_TYPES.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, GIF allowed.');
                    input.value = '';
                    return;
                }

                // Validate file size
                if (file.size > MAX_SIZE) {
                    alert('File too large. Maximum size is 1MB.');
                    input.value = '';
                    return;
                }

                // Image preview
                const reader = new FileReader();
                reader.onload = e => preview.src = e.target.result;
                reader.readAsDataURL(file);
            }
        });
</script>
</body>
</html>