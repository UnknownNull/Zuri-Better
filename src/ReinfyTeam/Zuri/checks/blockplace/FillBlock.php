<?php

/*
 *
 *  ____           _            __           _____
 * |  _ \    ___  (_)  _ __    / _|  _   _  |_   _|   ___    __ _   _ __ ___
 * | |_) |  / _ \ | | | '_ \  | |_  | | | |   | |    / _ \  / _` | | '_ ` _ \
 * |  _ <  |  __/ | | | | | | |  _| | |_| |   | |   |  __/ | (_| | | | | | | |
 * |_| \_\  \___| |_| |_| |_| |_|    \__, |   |_|    \___|  \__,_| |_| |_| |_|
 *                                   |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author ReinfyTeam
 * @link https://github.com/ReinfyTeam/
 *
 *
 */

declare(strict_types=1);

namespace ReinfyTeam\Zuri\checks\blockplace;

use pocketmine\network\mcpe\protocol\DataPacket;
use ReinfyTeam\Zuri\checks\Check;
use ReinfyTeam\Zuri\player\PlayerAPI;

class FillBlock extends Check {
	public function getName() : string {
		return "FillBlock";
	}

	public function getSubType() : string {
		return "A";
	}

	public function ban() : bool {
		return false;
	}

	public function kick() : bool {
		return true;
	}

	public function flag() : bool {
		return false;
	}

	public function captcha() : bool {
		return false;
	}

	public function maxViolations() : int {
		return 1;
	}

	public function check(DataPacket $packet, PlayerAPI $playerAPI) : void {
		$isCreative = $playerAPI->getPlayer()->isCreative() ? 10 : 0;
		if ($playerAPI->actionPlacingSpecial() && (($playerAPI->getNumberBlocksAllowPlace() + $isCreative) < $playerAPI->getBlocksPlacedASec())) {
			$this->failed($playerAPI);
			$player = $playerAPI->getPlayer();
			if (!$player->spawned && !$player->isConnected()) {
				return;
			}
			$playerAPI->setActionPlacingSpecial(false);
			$playerAPI->setBlocksPlacedASec(0);
			$playerAPI->setFlagged(true);
			$this->debug($playerAPI, "numberBlocksAllowPlace=" . $playerAPI->getNumberBlocksAllowPlace() . ", blocksPlacedASec=" . $playerAPI->getBlocksPlacedASec());
		}
	}
}