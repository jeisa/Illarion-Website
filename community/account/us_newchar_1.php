<?php
	include $_SERVER['DOCUMENT_ROOT'].'/shared/shared.php';

	if (!IllaUser::loggedIn())
	{
		header('HTTP/1.0 401 Unauthorized');
		exit();
	}

	$pgSQL =& Database::getPostgreSQL( 'illarionserver' );
	$query = 'SELECT COUNT(*)'
	.PHP_EOL.' FROM chars'
	.PHP_EOL.' WHERE chr_accid = '.$pgSQL->Quote( IllaUser::$ID )
	;
	$pgSQL->setQuery( $query );
	$charcount = $pgSQL->loadResult();

	$servers = array();
	if ($charcount < IllaUser::$charlimit || IllaUser::$charlimit == 0)
	{
		$servers[] = 'rs';
	}

	if (IllaUser::auth('testserver'))
	{
		$servers[] = 'ts';
	}

	if (!count($servers))
	{
		exit('Error - Character limit reached.');
	}

	if (!count(IllaUser::$allowed_races))
	{
		exit('Error - No races allowed.');
	}

	$enable_lightwindow = !( Page::getBrowserName() == 'msie' && Page::getBrowserVersion() <= 6 );

	if ($enable_lightwindow)
	{
		Page::setXML();
	}
	else
	{
		Page::setXHTML();
		Page::addJavaScript( 'prototype' );
		Page::addJavaScript( 'newchar_1' );
	}
	Page::Init();
?>

<div>
	<h1>Step 1</h1>

	<form method="post" name="char_form" id="char_form" action="<?php echo Page::getURL(); ?>/community/account/us_newchar.php">
		<table style="width:100%;">
			<tbody>
				<tr>
					<td style="width:45%;vertical-align:top;" rowspan="2">
						Name:<br />check the<a href="<?php echo Page::getURL(); ?>/illarion/us_name_rules.php">name rules</a>
					</td>
					<td>
						<input type="text" name="charname" id="charname" value="" style="width:98%;" onkeyup="checkCharname();return true;" />
					</td>
				</tr>
				<tr>
					<td id="charname_result" style="height:70px;vertical-align:top;"></td>
				</tr>
				<tr>
					<td>Race:</td>
					<td>
						<select name="race" style="width:100%;">
							<?php foreach( IllaUser::$allowed_races as $race ): ?>
							<option value="<?php echo $race; ?>"><?php echo IllarionData::getRaceName($race); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Sex:</td>
					<td>
						<select name="sex" style="width:100%;">
							<option value="0">male</option>
							<option value="1">female</option>
						</select>
						<input type="hidden" name="action" value="newchar_1" />
					</td>
				</tr>
				<?php if (count($servers) == 2): ?>
				<tr>
					<td style="height:20px;" />
				</tr>
				<tr>
					<td>Server:</td>
					<td>
						<select name="server" id="server" style="width:100%;" onchange="checkCharname();return true;">
							<option value="0">Gameserver</option>
						</select>
					</td>
				</tr>
				<tr>
					<td style="height:20px;" />
				</tr>
				<?php elseif ($servers[0] == 'ts' ): ?>
				<tr>
					<td style="height:20px;" />
				</tr>
				<tr>
					<td>
						Testserver
						<input type="hidden" name="server" value="1" />
					</td>
				</tr>
				<tr>
					<td style="height:20px;" />
				</tr>
				<?php else: ?>
				<tr>
					<td style="height:20px;"><input type="hidden" name="server" value="0" /></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</form>
	<p style="text-align:center;">
		<button onclick="document.forms.char_form.submit()" name="submit" id="submit" style="margin-right:10px;">Send</button>
		<?php if($enable_lightwindow): ?><button onclick="window.parent.myLightWindow.deactivate();return false;" style="margin-left:10px;">Cancel</button><?php endif; ?>
	</p>
</div>