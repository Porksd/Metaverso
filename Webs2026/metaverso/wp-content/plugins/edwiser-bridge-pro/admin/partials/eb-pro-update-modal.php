<?php
if (! defined('ABSPATH')) exit;
if (! is_admin() || ! get_option('eb_pro_show_enroll_students_update_modal')) return;
$woo_gutenberg_pages = get_option('eb_woo_gutenberg_pages', array());
$new_design_url = '';
if (!empty($woo_gutenberg_pages['eb_pro_enroll_students_page_id'])) {
    $new_design_url = get_permalink($woo_gutenberg_pages['eb_pro_enroll_students_page_id']);
}
?>
<div class="eb-modal-overlay">
    <div class="eb-modal--enroll-update">
        <button class="eb-modal__close" id="eb-modal-enroll-close" aria-label="Close"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-x-icon lucide-x">
                <path d="M18 6 6 18" />
                <path d="m6 6 12 12" />
            </svg></button>
        <div class="eb-modal-initial-body">
            <div class="eb-modal__body">
                <div class="eb-modal__visual">
                    <img src="<?php echo plugins_url('admin/assets/images/enroll-students-preview.png', dirname(__FILE__, 2)); ?>" alt="Enroll Students Preview" class="eb-modal__image" />
                </div>
                <div class="eb-modal__content">
                    <h2 class="eb-modal__title">Checkout the revamp version of 'Enroll students' page</h2>
                    <p class="eb-modal__desc">
                        We've refreshed this design while keeping the workflow intact—cleaner, faster, and easier to navigate.
                        <a href="<?php echo esc_url($new_design_url); ?>" class="eb-modal__link" target="_blank">Preview new design</a>
                    </p>
                    <div class="eb-modal__note">
                        <strong>NOTE:</strong>
                        <ul>
                            <li>You can switch to the new design later: <br>
                                <span class="eb-modal__note-path">WP Admin &gt; Edwiser Bridge &gt; Settings &gt; General &gt; Enroll Students Page</span>
                            </li>
                            <li>Customizations on the current 'Enroll Students' page will not transfer to the new design but will remain on the old page. You can switch back at any time.</li>
                        </ul>
                    </div>
                    <div class="eb-modal__actions">
                        <button class="eb-modal__btn eb-modal__btn--secondary" id="eb-modal-enroll-old">Continue with old design</button>
                        <button class="eb-modal__btn eb-modal__btn--primary" id="eb-modal-enroll-new">Switch to new design</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="eb-modal-success-body" style="display: none;">
            <h2 class="eb-modal__title">Congratulations!</h2>
            <p>You've successfully switched to the revamped version of 'Enroll students' page</p>
            <div class="eb-modal__actions">
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=eb_course&page=eb-settings#mucp_group_enrol_page_id')); ?>" class="eb-modal__btn eb-modal__btn--secondary">View settings</a>
                <a href="<?php echo esc_url($new_design_url); ?>" class="eb-modal__btn eb-modal__btn--primary" target="_blank">View page</a>
            </div>
        </div>
    </div>
</div>
