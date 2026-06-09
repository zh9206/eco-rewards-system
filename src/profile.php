<?php if (isset($_SESSION['profile_error'])): ?>
    <div class="error-msg">
        <?php echo $_SESSION['profile_error']; ?>
    </div>
    <?php unset($_SESSION['profile_error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['profile_success'])): ?>
    <div class="success-msg">
        <?php echo $_SESSION['profile_success']; ?>
    </div>
    <?php unset($_SESSION['profile_success']); ?>
<?php endif; ?>
<link rel="stylesheet" href="styles.css">
<style>
    .error-msg {
        position: fixed;
        top: 30px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10000;

        min-width: 280px;
        max-width: 420px;
        padding: 14px 18px;
        border-radius: 14px;

        background: rgba(248, 215, 218, 0.95);
        color: #842029;
        border: 1px solid rgba(220, 53, 69, 0.35);
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        backdrop-filter: blur(8px);

        font-size: 14px;
        font-weight: 500;
        text-align: center;

        animation: fadeSlideDown 0.3s ease;
    }

    .success-msg {
        position: fixed;
        top: 30px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10000;

        min-width: 280px;
        max-width: 420px;
        padding: 14px 18px;
        border-radius: 14px;

        background: rgba(212, 237, 218, 0.95);
        color: #155724;
        border: 1px solid rgba(40, 167, 69, 0.35);
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        backdrop-filter: blur(8px);

        font-size: 14px;
        font-weight: 500;
        text-align: center;

        animation: fadeSlideDown 0.3s ease;
    }

    @keyframes fadeSlideDown {
        from {
            opacity: 0;
            transform: translate(-50%, -12px);
        }
        to {
            opacity: 1;
            transform: translate(-50%, 0);
        }
    }

    .profile-modal {
    display: none; /* hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;

    background: rgba(0,0,0,0.5); /* ⭐ THIS IS THE DARK BACKGROUND */

    justify-content: center;
    align-items: center;
    z-index: 9999; /* ⭐ MUST be high */
}

.profile-modal-content {
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(15px);
    border-radius: 20px;
    padding: 25px 30px;
    width: 520px;
    max-width: 92%;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.profile-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px 20px;
    margin-top: 10px;
}

.profile-form .full {
    grid-column: span 2;
}

.profile-form label {
    font-size: 13px;
    font-weight: 600;
    color: #555;
    margin-bottom: 4px;
}

.profile-form input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 14px;
    transition: 0.2s;
}

.profile-form input:focus {
    border-color: #84B179;
    outline: none;
    box-shadow: 0 0 0 2px rgba(132,177,121,0.2);
}

.profile-form input[readonly] {
    background: #f3f3f3;
}

.profile-form select,
.profile-form input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 14px;
    transition: 0.2s;
    box-sizing: border-box;
}

.profile-form select:focus,
.profile-form input:focus {
    border-color: #84B179;
    outline: none;
    box-shadow: 0 0 0 2px rgba(132,177,121,0.2);
}

.save-btn {
    padding: 10px;
    border-radius: 10px;
    border: none;
    background: #84B179;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}

.save-btn:hover {
    background: #6fa864;
}

.profile-modal-content h2 {
    margin-bottom: 15px;
    color: #2e7d32;
}

.profile-modal-content h3 {
    margin-top: 20px;
    margin-bottom: 10px;
    color: #2e7d32;
}

hr {
    margin: 20px 0;
    border: none;
    border-top: 1px solid #ddd;
}

.profile-modal-content {
    animation: zoomIn 0.25s ease;
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.action-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.link-btn {
    font-size: 14px;
    color: #2e7d32;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
    transition: 0.2s;
}

.link-btn:hover {
    text-decoration: underline;
}
</style>

<!-- PROFILE MODAL -->
<div id="profileModal" class="profile-modal">
    <div class="profile-modal-content">
        <span class="close-btn" onclick="closeProfileModal()">✖</span>
        <h2>My Profile</h2>

        <?php if (isset($_SESSION['profile_success'])): ?>
            <div class="success-msg"><?php echo $_SESSION['profile_success']; unset($_SESSION['profile_success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['profile_error'])): ?>
            <div class="error-msg"><?php echo $_SESSION['profile_error']; unset($_SESSION['profile_error']); ?></div>
        <?php endif; ?>

        <!-- Profile Form -->
        <form action="updateProfile.php" method="POST" class="profile-form">
            <input type="hidden" name="return_page" value="<?php echo $_SERVER['PHP_SELF']; ?>">

            <div>
                <label>User ID</label>
                <input type="text" value="<?php echo $user['UserID']; ?>" readonly>
            </div>

            <div>
                <label>Role</label>
                <input type="text" value="<?php echo $user['Role_Name']; ?>" readonly>
            </div>

            <div>
                <label>Username</label>
                <input type="text" name="username" value="<?php echo $user['Username']; ?>" required>
            </div>

            <div>
                <label>Gender</label>
                <select name="gender" required>
                    <option value="Male" <?php if($user['Gender'] == 'Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if($user['Gender'] == 'Female') echo 'selected'; ?>>Female</option>
                </select>
            </div>

            <div>
                <label>Date of Birth</label>
                <input type="date" name="dob" value="<?php echo $user['DOB']; ?>" required>
            </div>

            <div>
                <label>Contact</label>
                <input type="text" name="contact" value="<?php echo $user['Contact']; ?>" required>
            </div>

            <div class="full action-row">
                <button type="submit" class="save-btn">SAVE</button>
                <a href="#" onclick="switchToPasswordModal()" class="link-btn">
                    Change Password?
                </a>
            </div>
        </form>
    </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div id="passwordModal" class="profile-modal">
    <div class="profile-modal-content">
        <span class="close-btn" onclick="closePasswordModal()">✖</span>
        <h2>Change Password</h2>

        <form action="changePassword.php" method="POST" class="profile-form">
            <input type="hidden" name="return_page" value="<?php echo $_SERVER['PHP_SELF']; ?>">

            <div class="full">
                <label>Old Password</label>
                <input type="password" name="old_password" required>
            </div>

            <div class="full">
                <label>New Password</label>
                <input type="password" name="new_password" required>
            </div>

            <div class="full">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <div class="full action-row">
                <button class="save-btn">CHANGE</button>
                <a href="#" onclick="switchToProfileModal()" class="link-btn">
                    ← Back
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function openProfileModal() {
    document.getElementById("profileModal").style.display = "flex";
}

function closeProfileModal() {
    document.getElementById("profileModal").style.display = "none";
}

function switchToPasswordModal() {
    closeProfileModal();
    document.getElementById("passwordModal").style.display = "flex";
}

function switchToProfileModal() {
    closePasswordModal();
    document.getElementById("profileModal").style.display = "flex";
}

function closePasswordModal() {
    document.getElementById("passwordModal").style.display = "none";
}

setTimeout(() => {
    const errorMsg = document.querySelector(".error-msg");
    const successMsg = document.querySelector(".success-msg");

    if (errorMsg) errorMsg.style.display = "none";
    if (successMsg) successMsg.style.display = "none";
}, 3000);
</script>