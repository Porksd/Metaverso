<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style>
    .mainContainer {
      width: 85%;
      display: flex;
      justify-content: space-around;
      margin: 0 auto;
      margin-top: 110px;
    }
    .mainCard {
        text-align: center;
    }

    .sideCard {
        display: flex;
        flex-direction: column;
        width: 400px;
    }

    .bf-logo svg {
        margin-bottom: 0;
        width: 80px;
        height: auto;
    }

    .bf-logo p {
        margin: 0 0 5px 0;
        font-size: 20px;
        color: #46596b;
        font-family: 'Roboto';
        font-weight: 600;
    }

    .errorMsg {
        padding: 40px 50px;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 30px auto 0 auto;
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
    }

    .errorMsg svg {
        color: #f54756;
        width: 80px;
        height: auto;
    }

    .errorMsg p {
        font-size: 18px;
        font-family: 'Roboto';
        color: #3b4e5d;
        margin-bottom: 0;
    }

    .successMsg svg {
        color: green;
    }

    .formField {
        margin-top: 20px;
    }

    .formField form {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .formField form p {
        font-weight: 600;
        margin: 0 8px 0 0;
        color: #2c3b47;
    }

    .formField form input {
        cursor: pointer;
        background-color: #f54756;
        border: none;
        border-radius: 100px;
        padding: 8px 16px;
        color: #fff;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
        color: #fff;
        transition: 0.3s all ease;
    }

    .formField form input:hover {
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 15%);
    }

    .backBtn {
        text-align: center;
        margin-top: 25px;
    }

    .btn2 {
        display: inline-flex;
        text-decoration: none;
        align-items: center;
        font-size: 14px;
        background-color: #03a9f4;
        color: #fff;
        padding: 5px 15px;
        border-radius: 100px;
        font-weight: 600;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
    }

    .btn2 svg {
        width: 20px;
        margin-right: 5px;
    }

    .btn2:hover {
        color: #fff;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 15%);
    }

    .footerBtn {
        margin-top: 60px;
        text-align: center;
    }

    .footerBtn a {
        font-weight: 400;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 100px;
        margin-right: 5px;
        transition: 0.3s all ease;
    }

    .subscribeBtn {
        border: 0.15em solid #f54756;
        color: #f54756;
        background-color: rgb(255 255 255 / 50%)
    }

    .subscribeBtn:hover {
        color: #f54756;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 15%);
    }

    .homeBtn {
        background-color: #0f1923;
        color: #fff;
        border: 0.15em solid #0f1923;
    }

    .homeBtn:hover {
        color: #fff;
        box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 15%);
    }
</style>


<div class="mainContainer">
    <div class="mainCard">
        <div class="bf-logo">
            <svg width="101" height="101" viewBox="0 0 101 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="0.427124" y="0.285355" width="99.7932" height="99.7932" rx="22.1018" fill="#0F1923" />
                <path d="M65.3912 48.2973C66.4143 48.6383 67.3238 48.8657 68.1195 49.2067C73.0077 51.3665 76.077 55.0041 77.1001 60.1194C78.3505 66.0304 76.1907 71.6005 71.0751 75.3517C68.2332 77.3979 65.0502 78.3073 61.5262 78.3073C56.5243 78.3073 51.5225 78.4209 46.407 78.4209C45.725 78.4209 45.0429 78.1936 44.4745 77.8526C43.4514 77.1705 43.224 76.2611 43.5651 75.1244C43.9061 74.1013 44.7018 73.4193 45.9523 73.3056C48.7942 73.3056 51.5225 73.3056 54.3645 73.3056C57.0928 73.3056 59.821 73.3056 62.5493 73.3056C67.5511 73.3056 71.8709 68.8723 72.0982 63.8706C72.3256 58.7553 68.5742 54.4357 63.6861 53.4126C63.4587 53.4126 63.2314 53.4126 63.004 53.4126C58.3432 53.4126 53.6824 53.4126 49.0216 53.4126C48.9079 53.4126 48.7943 53.4126 48.5669 53.4126C48.5669 54.4357 48.5669 55.4588 48.5669 56.5955C48.5669 57.7322 48.5669 58.869 48.5669 60.0057C48.5669 60.5741 48.6806 60.8014 49.3626 60.8014C52.2046 60.8014 55.0465 60.8014 57.7748 60.8014C58.2295 60.8014 58.6842 60.5741 59.0253 60.2331C61.0715 58.528 62.8903 58.869 64.3681 59.7784C66.3007 61.1425 66.7554 63.3023 65.846 65.3484C64.7092 67.6219 61.2988 68.4176 59.3663 66.5988C58.7979 66.0305 58.2295 65.8031 57.4338 65.8031C53.6824 65.8031 49.931 65.8031 46.1796 65.8031C44.4745 65.8031 43.5651 64.8937 43.5651 63.1886C43.5651 58.9827 43.5651 54.8904 43.5651 50.6844C43.5651 48.9793 44.1334 48.1836 45.9523 48.0699C48.6806 47.9563 51.4088 47.9563 54.1371 47.9563C60.2757 47.9563 64.9365 42.6136 64.0271 36.4752C63.345 32.2692 60.0484 29.2 56.297 28.2906C55.3876 28.0633 54.4781 28.0633 53.5687 28.0633C47.0891 28.0633 40.4958 27.9496 34.0161 28.0633C32.5383 28.0633 31.0605 26.8129 31.0605 25.3351C31.0605 23.8573 32.4246 22.7206 34.0161 22.7206C37.9948 22.7206 41.9736 22.7206 45.9523 22.7206C48.6806 22.7206 51.4088 22.7206 54.1371 22.7206C60.9578 22.8343 66.9827 27.1539 68.9153 33.9743C70.2794 38.7487 69.2563 43.182 66.3007 47.2742C65.846 47.6152 65.6186 47.8426 65.3912 48.2973Z" fill="#F54756" />
                <path d="M36.0666 40.6816C36.0666 41.25 36.0666 41.591 36.0666 42.0457C36.0666 51.2533 36.0666 60.4609 36.0666 69.6685C36.0666 71.601 36.0666 73.5334 36.0666 75.5796C36.0666 77.8531 34.3614 78.9898 32.2015 78.1941C31.0647 77.7394 30.951 76.6026 30.951 75.5796C30.951 72.283 30.951 69.1001 30.951 65.8036C30.951 57.0507 30.951 48.2978 30.951 39.5449C30.951 38.9765 30.9511 38.5218 31.0647 37.9534C31.1784 36.5893 32.2015 35.7936 33.452 35.7936C38.6812 35.7936 43.9104 35.7936 49.2532 35.7936C49.9353 35.7936 50.39 35.7936 50.9584 35.2252C52.2088 33.7475 54.7098 33.9748 55.7329 34.4295C57.438 35.3389 58.3475 36.9304 58.2338 38.7491C58.0064 40.5679 56.756 42.0457 54.9371 42.5004C53.3456 42.9551 51.9815 42.273 50.731 41.25C50.5037 41.0226 50.2763 40.9089 49.9353 40.7953C45.2745 40.5679 40.841 40.6816 36.0666 40.6816Z" fill="#F54756" />
            </svg>
            <p>Bit Form</p>
        </div>


        <?php
        if (isset($_POST) && isset($_POST['disconnect'])) {
            include_once BITFORMPRO_PLUGIN_DIR_PATH . 'includes/Core/Update/API.php';
            $activationStatus = BitCode\BitFormPro\Core\Update\API::disconnectLicense();
            if ($activationStatus === true) { ?>
                <div class="errorMsg">
                    <svg width="16px" height="16px" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.617 3.844a2.87 2.87 0 0 0-.451-.868l1.354-1.36L13.904 1l-1.36 1.354a2.877 2.877 0 0 0-.868-.452 3.073 3.073 0 0 0-2.14.075 3.03 3.03 0 0 0-.991.664L7 4.192l4.327 4.328 1.552-1.545c.287-.287.508-.618.663-.992a3.074 3.074 0 0 0 .075-2.14zm-.889 1.804a2.15 2.15 0 0 1-.471.705l-.93.93-3.09-3.09.93-.93a2.15 2.15 0 0 1 .704-.472 2.134 2.134 0 0 1 1.689.007c.264.114.494.271.69.472.2.195.358.426.472.69a2.134 2.134 0 0 1 .007 1.688zm-4.824 4.994l1.484-1.545-.616-.622-1.49 1.551-1.86-1.859 1.491-1.552L6.291 6 4.808 7.545l-.616-.615-1.551 1.545a3 3 0 0 0-.663.998 3.023 3.023 0 0 0-.233 1.169c0 .332.05.656.15.97.105.31.258.597.459.862L1 13.834l.615.615 1.36-1.353c.265.2.552.353.862.458.314.1.638.15.97.15.406 0 .796-.077 1.17-.232.378-.155.71-.376.998-.663l1.545-1.552-.616-.615zm-2.262 2.023a2.16 2.16 0 0 1-.834.164c-.301 0-.586-.057-.855-.17a2.278 2.278 0 0 1-.697-.466 2.28 2.28 0 0 1-.465-.697 2.167 2.167 0 0 1-.17-.854 2.16 2.16 0 0 1 .642-1.545l.93-.93 3.09 3.09-.93.93a2.22 2.22 0 0 1-.711.478z" />
                    </svg>
                    <p>License Disconnected Successfully</p>
                </div>
            <?php
            } else { ?>

                <div class="errorMsg">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <p><?php echo $activationStatus; ?></p>
                </div>
                <div class="formField">
                    <form action="" method="post">
                        <p>Disconnect this site from license? </p>
                        <input type="submit" name="disconnect" value="Disconnect">
                    </form>
                </div>
                <?php
            }
        } else {

            if (!empty($integrateStatus['expireIn'])) {
                $expireInDays = (strtotime($integrateStatus['expireIn']) - time()) / DAY_IN_SECONDS;
                if ($expireInDays < 25) {
                    $notice = $expireInDays > 0 ?
                        sprintf(__("Bit Form Pro License will expire in %s days", "bitformpro"), (int) $expireInDays)
                        : __("Bit Form Pro License is expired", "bitformpro")
                ?>

                    <div class="errorMsg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-triangle">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        <p><?php echo $notice; ?></p>
                    </div>

            <?php
                }
            }
            ?>
            <div class="formField">
                <form action="" method="post">
                    <p>Disconnect this site from license? </p>
                    <input type="submit" name="disconnect" value="Disconnect">
                </form>
            </div>

        <?php
        }
        ?>
        <div class="footerBtn">
        <a href="https://subscription.bitapps.pro/wp/login" class="subscribeBtn">Go to Subscription</a>
        <a href="<?= get_admin_url() ?>admin.php?page=bitform#/" class="homeBtn">Go Bit Form Dashboard</a>
        </div>
    </div>
    <div class="sideCard">
        <?php include_once(BITFORMPRO_PLUGIN_DIR_PATH . 'views/cashback.php'); ?>
        <?php include_once(BITFORMPRO_PLUGIN_DIR_PATH . 'views/facebook.php'); ?>
    </div>
</div>