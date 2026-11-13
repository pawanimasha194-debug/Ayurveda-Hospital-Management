<?php
// ============================================================
// Hospital / Business Details
// ============================================================

$hospital_name_en = "Gampaha Beheth Sappuwa";
$hospital_name_si = "ගම්පහ බෙහෙත් ශාලාව";
$hospital_subtitle_si = "සී/ස ගම්පහ සිද්ධායුර්වේද රසායනෞෂධ සමාගම";
$hospital_proprietor = "වෛද්‍ය යූ.ඒ. අල්ගම (D.S.A.M.S — University of Kelaniya)";
$hospital_address = "No. 48/11, Kandy Road, Kiribathgoda";
$hospital_phone = "071 866 3231 / 011 2 906108";
$hospital_license = "License No: 9902";

$hospital_logo_path = "images/logo.png";

function renderLetterhead() {
    global $hospital_name_en, $hospital_name_si, $hospital_subtitle_si,
           $hospital_proprietor, $hospital_address, $hospital_phone,
           $hospital_license, $hospital_logo_path;
    $logoExists = file_exists(__DIR__ . '/' . $hospital_logo_path);
    ?>
    <!-- Letterhead wrapper -->
    <div class="letterhead" style="display: flex; align-items: center; gap: 20px; margin-bottom: 15px;">
        
        <!-- Logo -->
        <div class="letterhead-logo" style="flex-shrink: 0;">
            <?php if ($logoExists): ?>
                <img src="<?php echo $hospital_logo_path; ?>" alt="Logo" style="width: 85px; height: 85px; object-fit: contain; display: block;">
            <?php else: ?>
                <div class="logo-placeholder" style="width: 85px; height: 85px; border-radius: 50%; border: 2px dashed #dfece0; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #5f6f68; text-align: center;">LOGO</div>
            <?php endif; ?>
        </div>

        <!-- Text Area -->
        <div class="letterhead-text" style="flex: 1;">
            <!-- Sinhala Name with Noto Sans Sinhala font -->
            <div class="lh-name-si" style="font-family: 'Noto Sans Sinhala', 'Iskoola Pota', sans-serif; font-size: 19px; font-weight: 700; color: #1b5e20; line-height: 1.2;">
                <?php echo $hospital_name_si; ?>
            </div>
            
            <div class="lh-name-en" style="font-size: 15px; font-weight: 700; color: #263238; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">
                <?php echo htmlspecialchars($hospital_name_en); ?>
            </div>
            
            <div class="lh-subtitle" style="font-family: 'Noto Sans Sinhala', 'Iskoola Pota', sans-serif; font-size: 13px; color: #5f6f68; font-style: italic; margin-top: 2px;">
                <?php echo $hospital_subtitle_si; ?>
            </div>
            
            <div class="lh-details" style="font-size: 12px; color: #5f6f68; margin-top: 6px; line-height: 1.5;">
                <!-- Proprietor name needs Sinhala font -->
                <span style="font-family: 'Noto Sans Sinhala', 'Iskoola Pota', sans-serif;"><?php echo htmlspecialchars($hospital_proprietor); ?></span><br>
                <?php echo htmlspecialchars($hospital_address); ?> &nbsp;•&nbsp; Tel: <?php echo htmlspecialchars($hospital_phone); ?><br>
                <?php echo htmlspecialchars($hospital_license); ?>
            </div>
        </div>
    </div>
    <hr class="letterhead-rule" style="border: none; border-top: 2px solid #1b5e20; margin: 15px 0 20px;">
    <?php
}
?>