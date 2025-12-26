<?php
require '../../_base.php';
require '../../lib/db.php';
include '../../_admin_head.php';
$_title = "Modify Users | Four Eyes Collective";

auth('Admin');

$user_id = get('user_id');
// Load product
$stm = $_db->prepare("SELECT * FROM users WHERE user_id = ?");
$stm->execute([$user_id]);
$user = $stm->fetch();


if (!$user) {
    die("User not found.");
}

[$year_db, $month_db, $date_db] = explode('-', $user->date_of_birth);
$role = $user->role;

if (is_post()) {

    $email              = req('email');
    $name               = req('name');
    $gender             = req('gender');
    $phone              = req('phone');

    $date               = req('date');
    $month              = req('month');
    $year               = req('year');
    $photo              = $_FILES['photo'] ?? null;
    $status             = req('status');

    $date_of_birth = "$year-$month-$date";
    $registration_date  = req('registration_date');

    //Validate email
    if (strlen($email) > 100) {
        $_err['email'] = 'Maximum 100 characters';
    } else if (!is_email($email)) {
        $_err['email'] = 'Invalid email format';
    } else if (!is_unique_except($email, 'users', 'email', 'user_id', $user_id)) {
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

    $photo_filename = $user->photo; // keep current photo by default

    if ($photo && $photo['error'] == 0 && $photo['size'] > 0) {
        // Only validate if a photo was uploaded
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));

        if (!str_starts_with($photo['type'], 'image/')) {
            $_err['photo'] = 'Must be an image';
        } else if (!in_array($ext, $allowed)) {
            $_err['photo'] = 'Only JPG, JPEG, PNG, GIF files are allowed';
        } else if ($photo['size'] > 1 * 1024 * 1024) {
            $_err['photo'] = 'Maximum 1MB';
        }
    }

    //Validate phone number
    if (!preg_match('/^[1-9][0-9]{7,9}$/', $phone)) {
        $_err['phone'] = 'Phone number must be in format 0XXXXXXXXX';
    } else if (!is_unique_except($phone, 'users', 'phone', 'user_id', $user_id)) {
        $_err['phone'] = 'Duplicated phone number';
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

    // Update into database
    if (!$_err) {

        // Handle photo upload
        if ($photo && $photo['error'] == 0 && $photo['size'] > 0 && !isset($_err['photo'])) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/images/users/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Delete old photo if it's not the default user image
            $old_photo_path = $upload_dir . $user->photo;
            if (
                $user->photo !== 'user_default.jpg' &&
                file_exists($old_photo_path)
            ) {
                unlink($old_photo_path);
            }

            // Sanitize username for new filename
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
                $photo_filename = $user->photo;
            }
        } else {
            // Use default photo
            $photo_filename = $user->photo;
        }


        $stm = $_db->prepare('
        UPDATE users SET
        email =?,
        name =?,
        gender =?,
        phone=?,
        date_of_birth =?,
        photo =?,
        status =?
        WHERE user_id = ?
    ');

        $stm->execute([
            $email,
            $name,
            $gender,
            $phone,
            $date_of_birth,
            $photo_filename,
            $status,
            $user_id
        ]);

        // Redirect after successful update
        header("Location: view_user.php?role=$role&msg=updated&user_id=$user_id");
        exit;
    }
}

?>

<div class="admin-content">
    <div class="content-header">
        <h1 class="dashboard-title">Modify <?= $user->role ?> (<?= $user_id ?>)</h1>
    </div>

    <div class="form-container">
        <form method="post" class="add-form" enctype="multipart/form-data">
            <div class="form-row">
                <!-- USer ID -->
                <div class="form-group">
                    <label><?= $user->role ?> ID</label>
                    <input type="text" id="id" name="user_id" class="form-control" required
                        value="<?= $user->user_id ?>" disabled>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" class="form-control" required
                        placeholder="<?= $role ?>@email.com" maxlength="100"
                        value="<?= encode($user->email) ?>">
                    <?= err('email') ?>
                </div>

                <!-- Name -->
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" class="form-control" required
                        placeholder="Enter <?= $role ?> name" maxlength="100"
                        value="<?= encode($user->name) ?>">
                    <?= err('name') ?>
                </div>

            </div>

            <div class="form-row">

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <span class="phone-prefix">+60 &nbsp;</span>
                    <input type="text" id="phone" name="phone" class="form-control-phone" required
                        placeholder="123456789"
                        pattern="[1-9][0-9]{7,9}"
                        maxlength="9"
                        value="<?= encode($user->phone) ?>">
                    <?= err('phone') ?>
                </div>


                <!-- Gender -->
                <div class="form-group">
                    <label>Gender *</label>
                    <div class="radio-group">
                        <?php foreach ($_genders as $id => $text): ?>
                            <div class="radio-option">
                                <input type="radio"
                                    id="gender_<?= $id ?>"
                                    name="gender"
                                    value="<?= $id ?>" required
                                    <?= $user->gender == $id ? 'checked' : '' ?>>
                                <label for="gender_<?= $id ?>" style="text-transform:none;"><?= $text ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?= err('gender') ?>
                </div>

                <div class="form-group">
                    <label>Date of Birth *</label>
                    <div class="dob-group">
                        <div class="dob-selectors">
                            <select id="date" name="date" class="dob-select" required>
                                <option value="">Day</option>
                                <?php foreach ($_days as $id => $text): ?>
                                    <option value="<?= $id ?>" <?= $date_db == $id ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="month" name="month" class="dob-select" required>
                                <option value="">Month</option>
                                <?php foreach ($_months as $id => $text): ?>
                                    <option value="<?= $id ?>" <?= $month_db == $id ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="year" name="year" class="dob-select" required>
                                <option value="">Year</option>
                                <?php foreach ($_years as $id => $text): ?>
                                    <option value="<?= $id ?>" <?= $year_db == $id ? 'selected' : '' ?>>
                                        <?= $text ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?= err('date_of_birth') ?>
                </div>


            </div>

            <!-- Date of Birth -->
            <div class="form-row">
                <div class="form-group">
                    <label>Profile Photo </label>
                    <div class="upload-photo-container" id="photoDropZone">
                        <label class="photo-label" for="photo" tabindex="0">
                            <div class="photo-preview">
                                <img id="photoPreview"
                                    src="/images/users/<?= encode($user->photo) ?>">
                            </div>
                            <input type="file" id="photo" name="photo" accept="image/*" style="display: none;">
                        </label>
                        <div class="upload-instructions">
                            <p>• Accepted formats: JPG, JPEG, PNG, GIF</p>
                            <p>• Maximum size: 1MB</p>
                            <p>• Optional - Provides a default photo</p>
                        </div>
                    </div>
                    <?= err('photo') ?>
                </div>

                <!-- Registration Date -->
                <div class="form-group">
                    <label for="registration_date">Registration Date</label>
                    <?php $reg_date = date('Y-m-d', strtotime($user->registration_date)); ?>
                    <input type="date" id="registration_date" name="registration_date" class="form-control"
                        value="<?= encode($reg_date) ?>" disabled>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label>Status</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" id="status_active" name="status" value="Active"
                                <?= $user->status == 'Active' ? 'checked' : '' ?>>
                            <label for="status_active" style="text-transform:none;">Active</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="status_blocked" name="status" value="Blocked"
                                <?= $user->status == 'Blocked' ? 'checked' : '' ?>>
                            <label for="status_blocked" style="text-transform:none;">Blocked</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="status_pending" name="status" value="Pending"
                                <?= $user->status == 'Pending' ? 'checked' : '' ?>
                                disabled
                                onclick="return false;">
                            <label for="status_pending" style="text-transform:none; color: #888;">Pending</label>
                        </div>
                    </div>
                    <?= err('status') ?>
                </div>

            </div>
            <input type="hidden" name="role" value="<?= $role ?>">


            <!-- Submit Buttons -->
            <div class="form-row button-row" style="margin-top: 40px;">
                <button type="button" class="btn btn-white" onclick="location.href='view_user.php?role=<?= $role ?>'">Back</button>
                <button type="submit" class="btn btn-add">Update</button>
                <button type="reset" class="btn btn-white">Reset</button>
            </div>
        </form>

    </div>

</div>
<script>
$(function () {

    const $input    = $('#photo');
    const $preview  = $('#photoPreview');
    const $dropZone = $('#photoDropZone');
    const $label    = $('.photo-label');

    // Upload validation
    const MAX_SIZE = 1024 * 1024; // 1MB
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

    // File select (click)
    $input.on('change', function () {
        if (this.files && this.files[0]) {
            handleFile(this.files[0]);
        }
    });

    // Drag enter / drag over
    $dropZone.on('dragenter dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dropZone.addClass('dragover');
        $label.addClass('dragover');
    });

    // Drag leave / drop
    $dropZone.on('dragleave drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $dropZone.removeClass('dragover');
        $label.removeClass('dragover');
    });

    // Handle drop
    $dropZone.on('drop', function (e) {
        const files = e.originalEvent.dataTransfer.files;
        if (files && files[0]) {
            $input[0].files = files; // assign dropped file
            handleFile(files[0]);
        }
    });

    function handleFile(file) {

        // Validate type
        if (!ALLOWED_TYPES.includes(file.type)) {
            alert('Invalid file type. Only JPG, JPEG, PNG, GIF allowed.');
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
        reader.onload = function (e) {
            $preview.attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
    }

    function resetFile() {
        $input.val('');
        // Keep existing image if reset
        $preview.attr('src', $preview.attr('src'));
    }

});
</script>
</body>

</html>
