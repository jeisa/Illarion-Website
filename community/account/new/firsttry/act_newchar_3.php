<?php
	checkAndUpdateChar();

	function checkAndUpdateChar()
	{
		if (!IllaUser::loggedIn())
		{
			Messages::add((Page::isGerman()?'Nicht eingeloggt':'Not logged in'),'error');
			return;
		}

		$server = ( isset( $_GET['server'] ) && (int)$_GET['server'] == 1 ? 'testserver' : 'illarionserver' );
		$charid = ( isset( $_GET['charid'] ) && is_numeric($_GET['charid']) ? (int)$_GET['charid'] : 0 );
		$pgSQL =& Database::getPostgreSQL( $server );

		$query = 'SELECT chr_race, chr_sex'
		.PHP_EOL.' FROM chars'
		.PHP_EOL.' WHERE chr_playerid = '.$pgSQL->Quote( $charid )
		.PHP_EOL.' AND chr_accid = '.$pgSQL->Quote( IllaUser::$ID )
		;
		$pgSQL->setQuery( $query );
		list( $race, $sex ) = $pgSQL->loadRow();

		if ($race === null || $race === false)
		{
			Messages::add((Page::isGerman()?'Charakter nicht gefunden':'The character was not found'),'error');
			return;
		}

		$account =& Database::getPostgreSQL( 'accounts' );
		$query = 'SELECT *'
		.PHP_EOL.' FROM raceattr'
		.PHP_EOL.' WHERE id IN ( -1, '.$account->Quote( $race ).' )'
		.PHP_EOL.' ORDER BY id DESC'
		;
		$account->setQuery( $query, 0, 1 );
		$limits = $account->loadAssocRow();

		$attributes = array();
		$sum = 0;
		foreach( array( 'strength', 'agility', 'constitution', 'dexterity', 'intelligence', 'perception', 'willpower', 'essence' ) as $name )
		{
			$attributes[$name] = ( is_numeric($_POST[$name]) ? (int)$_POST[$name] : 0 );
			if ( $attributes[$name] < $limits['min'.$name] || $attributes[$name] > $limits['max'.$name] )
			{
				Messages::add((Page::isGerman()?'Attribute überschreiten die vorgegebenen Grenzen':'Attributes out of range'),'error');
				return;
			}
			$sum += $attributes[$name];
			$attributes[$name] = $pgSQL->Quote( $attributes[$name] );
		}

		if ($sum > $limits['maxattribs'])
		{
			Messages::add((Page::isGerman()?'Die Summe der Attribute ist zu groß. Es müssen genau '.$limits['maxattribs'].' Attributspunkte verteilt werden.':'The sum of attributes is too large. You have to use exactly '.$limits['maxattribs'].' attribute points.'),'error');
			return;
		}
		elseif ($sum < $limits['maxattribs'])
		{
			Messages::add((Page::isGerman()?'Die Summe der Attribute ist zu klein. Es müssen genau '.$limits['maxattribs'].' Attributspunkte verteilt werden.':'The sum of attributes is too low. You have to use exactly '.$limits['maxattribs'].' attribute points.'),'error');
			return;
		}
		$query = "UPDATE `player`"
		. "\n SET `ply_strength` = ".$attributes['strength']
		. ", `ply_dexterity` = ".$attributes['dexterity']
		. ", `ply_constitution` = ".$attributes['constitution']
		. ", `ply_agility` = ".$attributes['agility']
		. ", `ply_intelligence` = ".$attributes['intelligence']
		. ", `ply_perception` = ".$attributes['perception']
		. ", `ply_willpower` = ".$attributes['willpower']
		. ", `ply_essence` = ".$attributes['essence']
		. "\n WHERE `char_id` =".$pgSQL->Quote( $charid )
		;
		$pgSQL->setQuery( $query );
		$pgSQL->query();
	}
?>