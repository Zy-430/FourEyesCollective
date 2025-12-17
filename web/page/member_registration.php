<?php
require '../_base.php';
require '../lib/db.php';
require '../lib/email_action.php';


// Registration is only for member ; admin will be add manually through admin mode

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

    // Validate password
    $password = trim($password);

    if (!is_strong_password($password)) {
        $_err['password'] =
            'Password must be at least 8 characters and include uppercase, lowercase, number and symbol';
    }


    //Validate Confirm password 
    if ($password !== $confirm_password) {
        $_err['confirm_password'] = 'Passwords do not match. Please try again!';
    }

    //Validate gender
    if (!array_key_exists($gender, $_genders)) {
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
    if (!preg_match('/^[1-9][0-9]{7,9}$/', $phone)) {
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

    // Auto-generate user ID (only for member)
    $user_id = generateMemberID($_db);

    // Password hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);


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
            INSERT INTO users 
            (user_id, role, email, password, name, gender, phone, date_of_birth, photo, registration_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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

        sendEmailAction($email, 'verification');

        temp('success', 'Registration successful! Please check your email to verify your account.');
        redirect('login.php');
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
    <style>
        .photo-upload-container.dragover {
            border-color: #27ae60 !important;
            background-color: rgba(39, 174, 96, 0.1) !important;
        }

        .photo-upload-label.dragover {
            border-color: #27ae60 !important;
        }

        .drag-text {
            display: none;
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .photo-upload-container:hover .drag-text {
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
                        value="<?= encode($GLOBALS['email'] ?? '') ?>" required>
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

            <!-- Date of Birth -->
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
        // Get elements
        const dropZone = document.getElementById('photoDropZone');
        const photoInput = document.getElementById('photo');
        const photoPreview = document.getElementById('photoPreview');
        const photoLabel = document.querySelector('.photo-upload-label');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Highlight drop zone when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
            photoLabel.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
            photoLabel.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropZone.classList.add('dragover');
            photoLabel.classList.add('dragover');
        }

        function unhighlight() {
            dropZone.classList.remove('dragover');
            photoLabel.classList.remove('dragover');
        }

        // Handle dropped files
        dropZone.addEventListener('drop', handleDrop, false);
        photoLabel.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                // Only process the first file
                const file = files[0];

                // Validate file type
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF)');
                    return;
                }

                // Validate file size (1MB = 1048576 bytes)
                if (file.size > 1048576) {
                    alert('File is too large. Maximum size is 1MB.');
                    return;
                }

                // Set the file to the input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                photoInput.files = dataTransfer.files;

                // Trigger change event to update preview
                const event = new Event('change', {
                    bubbles: true
                });
                photoInput.dispatchEvent(event);

                // Update preview immediately
                const reader = new FileReader();
                reader.onload = function(e) {
                    photoPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        // Also allow clicking the entire drop zone to trigger file input
        dropZone.addEventListener('click', function(e) {
            // Only trigger if the click wasn't on the file input or label
            if (e.target !== photoInput && !photoLabel.contains(e.target)) {
                photoInput.click();
            }
        });

        // Show drag text on hover
        dropZone.addEventListener('mouseenter', function() {
            const dragText = dropZone.querySelector('.drag-text');
            if (dragText) dragText.style.display = 'block';
        });

        dropZone.addEventListener('mouseleave', function() {
            const dragText = dropZone.querySelector('.drag-text');
            if (dragText) dragText.style.display = 'none';
        });
    </script>
</body>

</html>