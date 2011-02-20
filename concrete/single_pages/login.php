<?php  defined('C5_EXECUTE') or die(_("Access Denied.")); ?>

<?php  if ($validated) { ?>

<h1>Email Address Verified</h1>

<p>The email address <b><?php echo $uEmail?></b> has been verified and you are now a fully validated member of this website.</p>
<p><a href="<?php echo $this->url('/')?>">Return to Home &gt;</a></p>

<?php  } else { ?>

<h1>Sign In to Concrete5</h1>

<?php  if (isset($intro_msg)) { ?>
<h2><?php echo $intro_msg?></h2>
<?php  } ?>

<div class="ccm-form">

<form method="post" action="<?php echo $this->url('/login', 'do_login')?>">
	<div>
	<label for="uName"><?php echo $uNameLabel?></label><br/>
	<input type="text" name="uName" id="uName" class="ccm-input-text">
	</div>
	<br>
	<div>
	<label for="uPassword">Password</label><br/>
	<input type="password" name="uPassword" id="uPassword" class="ccm-input-text">
	</div>

	<?php echo $form->checkbox('uMaintainLogin', 1)?> Remember Me
	
	<div class="ccm-button">
	<?php echo $form->submit('submit', 'Sign In &gt;')?>
	</div>

	<input type="hidden" name="rcURL" value="<?php echo $rcURL?>" />

</form>
</div>


<h2 style="margin-top:32px">Forgot Password?</h2>

<p>If you've forgotten your password, enter your email address below. We will reset it to a new password, and send the new one to you.</p>

<div class="ccm-form">

<a name="forgot_password"></a>

<form method="post" action="<?php echo $this->url('/login', 'forgot_password')?>">
	
	<label for="uEmail">Email Address</label><br/>
	<input type="hidden" name="rcURL" value="<?php echo $rcURL?>" />
	<input type="text" name="uEmail" value="" class="ccm-input-text" >

	<div class="ccm-button">
	<?php echo $form->submit('submit', 'Reset and Email Password &gt;')?>
	</div>
	
</form>

</div>


<script type="text/javascript">
	document.getElementById("uName").focus();
</script>

<?php  } ?>

