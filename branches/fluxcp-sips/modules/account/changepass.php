<?php
if (!defined('FLUX_ROOT')) exit;

$this->loginRequired();

$title = 'Change Password';

if (count($_POST)) {
	$currentPassword    = $params->get('currentpass');
	$newPassword        = trim($params->get('newpass'));
	$confirmNewPassword = trim($params->get('confirmnewpass'));
	
	if (!$currentPassword) {
		$errorMessage = Flux::message('NeedCurrentPassword');
	}
	elseif (!$newPassword) {
		$errorMessage = Flux::message('NeedNewPassword');
	}
	elseif (strlen($newPassword) < Flux::config('MinPasswordLength')) {
		$errorMessage = Flux::message('PasswordTooShort');
	}
	elseif (strlen($newPassword) > Flux::config('MaxPasswordLength')) {
		$errorMessage = Flux::message('PasswordTooLong');
	}
	elseif (!$confirmNewPassword) {
		$errorMessage = Flux::message('ConfirmNewPassword');
	}
	elseif ($newPassword != $confirmNewPassword) {
		$errorMessage = Flux::message('PasswordsDoNotMatch');
	}
	elseif ($newPassword == $currentPassword) {
		$errorMessage = Flux::message('NewPasswordSameAsOld');
	}
	else {
		$sql = "SELECT user_pass AS currentPassword FROM {$server->loginDatabase}.login WHERE account_id = ?";
		$sth = $server->connection->getStatement($sql);
		$sth->execute(array($session->account->account_id));
		
		$account         = $sth->fetch();
		$useMD5          = $session->loginServer->config->getUseMD5();
		$currentPassword = $useMD5 ? md5($currentPassword) : $currentPassword;
		$newPassword     = $useMD5 ? md5($newPassword) : $newPassword;
		
		if ($currentPassword != $account->currentPassword) {
			$errorMessage = Flux::message('OldPasswordInvalid');
		}
		else {
			$sql = "UPDATE {$server->loginDatabase}.login SET user_pass = ? WHERE account_id = ?";
			$sth = $server->connection->getStatement($sql);
			
			if ($sth->execute(array($newPassword, $session->account->account_id))) {
				$session->setMessageData(Flux::message('PasswordHasBeenChanged'));
				$session->logout();
				$this->redirect($this->url('account', 'login'));
			}
			else {
				$errorMessage = Flux::message('FailedToChangePassword');
			}
		}
	}
}
?>