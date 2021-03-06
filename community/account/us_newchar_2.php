<?php
	include $_SERVER['DOCUMENT_ROOT'].'/shared/shared.php';

	if (!IllaUser::loggedIn())
	{
		header('HTTP/1.0 401 Unauthorized');
		exit();
	}

	includeWrapper::includeOnce( Page::getRootPath().'/community/account/inc_editinfos.php' );

	$server = ( isset( $_GET['server'] ) && $_GET['server'] == '1' ? 'testserver' : 'illarionserver');
	$charid = ( isset( $_GET['charid'] )  && is_numeric($_GET['charid']) ? (int)$_GET['charid'] : false );
	if (!$charid)
	{
		exit('Fehler - Charakter ID wurde nicht richtig übergeben.');
	}

	$pgSQL =& Database::getPostgreSQL();

	$query = 'SELECT chr_race'
	.PHP_EOL.' FROM '.$server.'.chars'
	.PHP_EOL.' WHERE chr_playerid = '.$pgSQL->Quote( $charid )
	.PHP_EOL.' AND chr_accid = '.$pgSQL->Quote( IllaUser::$ID )
	;
	$pgSQL->setQuery( $query );
	$race = $pgSQL->loadResult();

	if ($race === null || $race === false)
	{
		header('HTTP/1.0 401 Unauthorized');
		exit();
	}

	$query = 'SELECT COUNT(*)'
	.PHP_EOL.' FROM '.$server.'.player'
	.PHP_EOL.'WHERE ply_playerid = '.$pgSQL->Quote( $charid )
	;
	$pgSQL->setQuery( $query );
	if ($pgSQL->loadResult())
	{
		exit('Error - Values already set');
	}

	$query = 'SELECT *'
	.PHP_EOL.' FROM accounts.raceattr'
	.PHP_EOL.' WHERE id IN ( -1, '.$pgSQL->Quote( $race ).' )'
	.PHP_EOL.' ORDER BY id DESC'
	;
	$pgSQL->setQuery( $query, 0, 1 );
	$limits = $pgSQL->loadAssocRow();

	$limits['curr_weight'] = 0;
	$limits['curr_bodyheight'] = 0;
	$limits['curr_age'] = 0;
	$dob= array( 'day' => 1, 'month' => 1 );

	$limits['curr_agility'] = $limits['minagility'];
	$limits['curr_strength'] = $limits['minstrength'];
	$limits['curr_constitution'] = $limits['minconstitution'];
	$limits['curr_dexterity'] = $limits['mindexterity'];
	$limits['curr_perception'] = $limits['minperception'];
	$limits['curr_willpower'] = $limits['minwillpower'];
	$limits['curr_intelligence'] = $limits['minintelligence'];
	$limits['curr_essence'] = $limits['minessence'];
	$limits['curr_remaining'] = $limits['maxattribs'] - ( $limits['curr_agility']+$limits['curr_strength']+$limits['curr_constitution']+$limits['curr_dexterity']+$limits['curr_perception']+$limits['curr_willpower']+$limits['curr_intelligence']+$limits['curr_essence'] );
	$limits['minremaining'] = 0;
	$limits['maxremaining'] = $limits['maxattribs'];

	calculateLimits( &$limits );
	$limit_text = generateLimitTexts( $limits );

    $query = 'SELECT attr_name_us AS name, attr_str, attr_agi, attr_dex, attr_con, attr_int, attr_per, attr_wil, attr_ess'
            .PHP_EOL.' FROM accounts.attribtemp'
            .PHP_EOL.' ORDER BY attr_id'
            ;
    $pgSQL->setQuery($query);
    $templates = $pgSQL->loadAssocList();


	$enable_lightwindow = !( Page::getBrowserName() == 'msie' && Page::getBrowserVersion() <= 6 );

	if ($enable_lightwindow)
	{
		Page::setXML();
	}
	else
	{
		Page::setXHTML();
		Page::addJavaScript( 'prototype' );
		Page::addJavaScript( 'effects' );
		Page::addCSS( 'slider' );
		Page::addJavaScript( 'slider' );
	}
	Page::Init();
?>
<div>
	<h1>Step 2</h1>

	<form action="<?php echo Page::getURL(); ?>/community/account/us_newchar.php?charid=<?php echo $charid,($_GET['server'] == '1' ? '&amp;server=1' : ''); ?>" method="post" name="create_char" id="create_char">
		<div>
			<h2>Information</h2>
			<table style="width:100%">
				<tbody>
					<tr>
						<td>
							Weight: <?php echo $limit_text['weight']; ?>
						</td>
						<td style="width:423px;">
							<?php include_slider( $limits, 'weight' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Body height: <?php echo $limit_text['height']; ?>
						</td>
						<td>
							<?php include_slider( $limits, 'bodyheight' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Age: <?php echo $limits['minage']; ?> years to <?php echo $limits['maxage']; ?> years
						</td>
						<td>
							<?php include_slider( $limits, 'age' ); ?>
							<p>
								Date of Birth:
								<select name="day" id="day" style="width:50px;margin-right:10px;">
									<?php for ($i = 1;$i <= 24;$i++) { ?>
									<option value="<?php echo $i; ?>"<?php echo ($dob['day'] == $i ? ' selected="selected"' : '' ); ?>><?php echo $i; ?>.</option>
									<?php } ?>
								</select>
								<select name="month" id="month" style="margin-left:10px;">
									<?php for ($i = 1;$i <= 16;$i++) { ?>
									<option value="<?php echo $i; ?>"<?php echo ($dob['month'] == $i ? ' selected="selected"' : '' ); ?>><?php echo $month_names[$i-1]; ?></option>
									<?php } ?>
								</select>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php include_heightweight_js( $limits ); ?>
			<?php include_age_js( $limits ); ?>

			<h2>Attributes</h2>

			<table style="width:100%">
				<tbody>
					<tr>
						<td>
							Package
						</td>
						<td style="width:423px;">
							<select id="attrib_pack">
								<option>none</option>
								<?php foreach($templates as $template){ ?>
								<option value="<?php echo $template['attr_str'],'|',$template['attr_agi'],'|',$template['attr_dex'],'|',$template['attr_con'],'|',$template['attr_int'],'|',$template['attr_per'],'|',$template['attr_wil'],'|',$template['attr_ess']; ?>"><?php echo $template['name']; ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							Strength (<?php echo $limits['minstrength'],' - ',$limits['maxstrength']; ?>)
						</td>
						<td style="width:423px;">
							<?php include_slider( $limits, 'strength' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Agility (<?php echo $limits['minagility'],' - ',$limits['maxagility']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'agility' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Constitution (<?php echo $limits['minconstitution'],' - ',$limits['maxconstitution']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'constitution' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Dexterity (<?php echo $limits['mindexterity'],' - ',$limits['maxdexterity']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'dexterity' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Intelligence (<?php echo $limits['minintelligence'],' - ',$limits['maxintelligence']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'intelligence' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Perception (<?php echo $limits['minperception'],' - ',$limits['maxperception']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'perception' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Willpower (<?php echo $limits['minwillpower'],' - ',$limits['maxwillpower']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'willpower' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Essence (<?php echo $limits['minessence'],' - ',$limits['maxessence']; ?>)
						</td>
						<td>
							<?php include_slider( $limits, 'essence' ); ?>
						</td>
					</tr>
					<tr>
						<td>
							Remaining points
						</td>
						<td>
							<?php include_slider( $limits, 'remaining' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php include_attribute_js( $limits ); ?>
			<p style="text-align:center;padding-bottom:10px;">
				<input type="hidden" name="action" value="newchar_2" />
				<button onclick="document.forms.create_char.submit();" style="margin-right:10px;">Save data</button>
				<?php if($enable_lightwindow): ?><button onclick="myLightWindow.deactivate();return false;"  style="margin-left:10px;">Cancel</button><?php endif; ?>
			</p>
		</div>
	</form>
</div>
