<?php   defined('C5_EXECUTE') or die(_("Access Denied.")); ?>

<?php  echo t('Title')?><br />
<input type="text" name="title" value="<?php  echo t('Comments')?>:" /><br /><br />

<?php  echo t('Comments Require Moderator Approval?')?><br/>
<input type="radio" name="requireApproval" value="1" /> <?php  echo t('Yes')?><br />
<input type="radio" name="requireApproval" value="0" checked="checked" /> <?php  echo t('No')?><br /><br />

<?php  echo t('Posting Comments is Enabled?')?><br/>
<input type="radio" name="displayGuestBookForm" value="1" checked="checked" /> <?php  echo t('Yes')?><br />
<input type="radio" name="displayGuestBookForm" value="0" /> <?php  echo t('No')?><br /><br />

<?php  echo t('Authentication Required to Post')?><br/>

<input type="radio" name="authenticationRequired" value="0" checked /> <?php  echo t('Email Only')?><br />
<input type="radio" name="authenticationRequired" value="1" /> <?php  echo t('Users must login to C5')?><br /><br />

<?php  echo t('Alert Email Address when Comment Posted')?><br/>
<input name="notifyEmail" type="text" value="<?php  echo $notifyEmail?>" size="30" /><br /><br />

