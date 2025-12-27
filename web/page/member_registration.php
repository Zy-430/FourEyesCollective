<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/email_action.php';

// Registration is only for member ; admin will be add manually through admin panel
// Form submit
if (is_post()) {

    // Input
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
    $photo              = $_FILES['photo'] ?? null;

    // Combine date of birth
    $date_of_birth = "$year-$month-$date";

    // Validate email
    if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } else if (!is_unique($email, 'users', 'email')) {
        $_err['email'] = 'Duplicated email';
    }

    // Validate name
    if (strlen($name) > 100) {
        $_err['name'] = 'Maximum length 100';
    } elseif (!preg_match('/^[A-Za-z ]+$/', $name)) {
        $errors[] = "Name can only contain letters and spaces.";
    }

    // Validate password
    $password = trim($password);

    if (!is_strong_password($password)) {
        $_err['password'] = 'Invalid password format';
    }

    // Validate Confirm password 
    if ($password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match. Please try again!';
    }

    // Validate gender
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

    // Validate phone number
    if (!preg_match('/^[1-9][0-9]{7,9}$/', $phone)) {
        $_err['phone'] = 'Phone number must be in format 0XXXXXXXXX';
    } else if (!is_unique($phone, 'users', 'phone')) {
        $_err['phone'] = 'Duplicated phone number';
    }

    // Validate day , month , year (later combine for date of birth)
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
            // Age restriction (member must be at least 12 years old)
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

    // Auto-generate user ID (only for member)
    $user_id = generateMemberID($_db);

    // Password hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert into database
    if (!$_err) {
        // Handle photo upload with username-based naming
        if ($photo && $photo['error'] == 0 && $photo['size'] > 0 && !isset($_err['photo'])) {
            $upload_dir = __DIR__ . '/../images/users/';
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
                // Example : steven15_01.jpg, steven15_02.jpg...
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

        // Member registration only for member
        // Default status for new members is pending (wait for email verification)
        $role = 'Member';
        $status = 'Pending';

        // Get current date for registration_date 
        if (empty($registration_date)) {
            $registration_date = date('Y-m-d');
        }

        // Begin transaction
        $_db->beginTransaction();

        $stm = $_db->prepare('
            INSERT INTO users 
            (user_id, role, email, password, name, gender, phone, date_of_birth, photo, registration_date, status)
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

        // Generate verification token (user can get verification email)
        $verification_token = sha1(uniqid() . rand());

        // Then store verification token (it will expires in 24 hours)
        $stm = $_db->prepare('
            INSERT INTO token (token_id, expire, user_id, type)
            VALUES(?, ADDTIME(NOW(), "24:00"), ?, "verification")
        ');
        $stm->execute([$verification_token, $user_id]);
        $_db->commit();

        // Send email with type verification
        sendEmailAction($email, 'verification');

        temp('success', 'Registration successful! Please check your email to verify your account.');
        redirect('login.php');
    }
}

// ----------------------------------------------------------------------------
$_title = 'Member Registration | Four Eyes Collective';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?? 'Four Eyes Collective' ?></title>
    <link rel="shortcut icon" href="/images/WIS_logo_white.png">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/user.css">
    <style>
        .photo-upload-container.dragover {
            border-color: #27ae60 !important;
            background-color: rgba(39, 174, 96, 0.1) !important;
        }

        .photo-upload-label.dragover {
            border-color: #27ae60 !important;
        }

        .photo-error {
            color: #e74c3c;
            font-size: 0.9em;
            margin-top: 5px;
            display: block;
        }
    </style>
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
                        value="<?= encode($GLOBALS['email'] ?? '') ?>" autofocus required>
                    <?= err('email') ?>
                </div>

                <!-- Name -->
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-control"
                        placeholder="Your full name" maxlength="100"
                        value="<?= encode($GLOBALS['name'] ?? '') ?>" required>
                    <?= err('name') ?>
                </div>
            </div>

            <div class="form-row">
                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Create a password (8-15 characters)" maxlength="15" required>
                    <small><i>* Strong password: mix uppercase, lowercase, numbers & symbols.</i></small>
                    <?= err('password') ?>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                        placeholder="Re-enter your password" maxlength="15" required>
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
                                    <?= ($GLOBALS['gender'] ?? '') == $id ? 'checked' : '' ?> required>
                                <label for="gender_<?= $id ?>"><?= $text ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= err('gender') ?>
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <span class="phone-prefix" style="font-size: 13px;">+60 &nbsp;</span>
                    <input type="text" id="phone" name="phone" class="form-control-phone"
                        placeholder="123456789"
                        pattern="[1-9][0-9]{7,9}"
                        maxlength="9"
                        value="<?= encode($GLOBALS['phone'] ?? '') ?>" required>
                    <?= err('phone') ?>
                </div>
            </div>

            <!-- Profile Photo & Date of Birth -->
            <div class="form-row">
                <div class="form-group">
                    <label>Profile Photo </label>
                    <div class="photo-upload-container" id="photoDropZone">
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
                    <span class="photo-error"><?= $_err['photo'] ?? '' ?></span>
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
        $(function() {

            const $input = $('#photo');
            const $preview = $('#photoPreview');
            const $dropZone = $('#photoDropZone');
            const $label = $('.photo-upload-label');

            const MAX_SIZE = 1024 * 1024; // 1MB
            const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

            // Normal file select
            $input.on('change', function() {
                if (this.files && this.files[0]) {
                    handleFile(this.files[0]);
                }
            });

            // Drag enter / over
            $dropZone.on('dragenter dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropZone.addClass('dragover');
                $label.addClass('dragover');
            });

            // Drag leave / drop
            $dropZone.on('dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $dropZone.removeClass('dragover');
                $label.removeClass('dragover');
            });

            // Handle drop
            $dropZone.on('drop', function(e) {
                const files = e.originalEvent.dataTransfer.files;
                if (files && files[0]) {
                    $input[0].files = files; // assign dropped file to input
                    handleFile(files[0]);
                }
            });

            function handleFile(file) {

                // Validate type
                if (!ALLOWED_TYPES.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, GIF allowed.');
                    resetFile();
                    return;
                }

                // Validate size
                if (file.size > MAX_SIZE) {
                    alert('File too large. Maximum size is 1MB.');
                    resetFile();
                    return;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    $preview.attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }

            function resetFile() {
                $input.val('');
                $preview.attr('src', '/images/upload.png');
            }

        });
    </script>
</body>

</html>
