<?php 
$facebookGroupUrl = 'https://www.facebook.com/groups/3308027439209387';
?>

<style>
  .facebook-container * {
    padding: 0;
    margin: 0;
  }
  .facebook-container {
    box-sizing: border-box;
    background-color: #fff;
    margin-top: 10px;
    text-align: center;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0px 3px 10px 1px rgb(0 0 0 / 5%);
  }

  .facebook-container .join-title {
    font-size: 22px;
    margin-bottom: 5px;
  }
  
  .facebook-container p {
    margin-bottom: 5px;
  }
  
  .facebook-container .interect {
    font-size: 15px;
  }

  .facebook-container .update {
    font-size: 13px;
  }

  .facebook-container .join-btn {
    display: inline-block;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 500;
    line-height: 1.4;
    padding: 2px 10px;
    position: relative;
    font-size: 1rem;
    color: #fff;
    margin-top: 10px;
    padding: 5px 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
  }
</style>

<div class="facebook-container">
  <h3 class="join-title">Join our facebook community</h3>
  <p class="interect">Interact with users & communicate</p>
  <p class="update">Receive updates to stay informed about upcoming features.</p>
  <a class="join-btn blue" href="<?= $facebookGroupUrl ?>" target="_blank">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-facebook">
      <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
    </svg>
    Join Now
  </a>
</div>
