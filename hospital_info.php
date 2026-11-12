<?php
// ============================================================
// Hospital / Business Details — used in letterheads for
// printable documents (delivery notes, inventory reports)
// Edit these values in ONE place to update every document.
// ============================================================

$hospital_name_en = "Gampaha Beheth Sappuwa";
$hospital_name_si = "ගම්පහ බෙහෙත් ශාලාව";
$hospital_subtitle_si = "සී/ස ගම්පහ සිද්ධායුර්වේද රසායනෞෂධ සමාගම";
$hospital_proprietor = "වෛද්‍ය යූ.ඒ. අල්ගම (D.S.A.M.S — University of Kelaniya)";
$hospital_address = "No. 48/11, Kandy Road, Kiribathgoda";
$hospital_phone = "071 866 3231 / 011 2 906108";
$hospital_license = "License No: 9902";

// Path to the hospital's own logo image, placed in /images/logo.png
// Replace images/logo.png with your own logo file (same filename) once ready.
$hospital_logo_path = "images/logo.png";

// Reusable letterhead block — used at the top of every printable document
function renderLetterhead() {
    global $hospital_name_en, $hospital_name_si, $hospital_subtitle_si,
           $hospital_proprietor, $hospital_address, $hospital_phone,
           $hospital_license, $hospital_logo_path;
    $logoExists = file_exists(__DIR__ . '/' . $hospital_logo_path);
    ?>
    <div class="letterhead">
        <div class="letterhead-logo">
            <?php if ($logoExists): ?>
                <img src="<?php echo $hospital_logo_path; ?>" alt="Logo">
            <?php else: ?>
                <div class="logo-placeholder">LOGO</div>
            <?php endif; ?>
        </div>
        <div class="letterhead-text">
            <div class="lh-name-si"><?php echo $hospital_name_si; ?></div>
            <div class="lh-name-en"><?php echo htmlspecialchars($hospital_name_en); ?></div>
            <div class="lh-subtitle"><?php echo $hospital_subtitle_si; ?></div>
            <div class="lh-details">
                <?php echo htmlspecialchars($hospital_proprietor); ?><br>
                <?php echo htmlspecialchars($hospital_address); ?> &nbsp;•&nbsp; Tel: <?php echo htmlspecialchars($hospital_phone); ?><br>
                <?php echo htmlspecialchars($hospital_license); ?>
            </div>
        </div>
    </div>
    <hr class="letterhead-rule">
    <?php
}
?>
