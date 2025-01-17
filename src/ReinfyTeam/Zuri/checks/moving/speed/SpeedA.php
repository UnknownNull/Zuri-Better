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

namespace ReinfyTeam\Zuri\checks\moving\speed;

use pocketmine\block\BlockTypeIds;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use ReinfyTeam\Zuri\checks\Check;
use ReinfyTeam\Zuri\player\PlayerAPI;
use ReinfyTeam\Zuri\utils\BlockUtil;
use function intval;
use function pow;

class SpeedA extends Check {
	public function getName() : string {
		return "Speed";
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
		return 8;
	}

	public function check(DataPacket $packet, PlayerAPI $playerAPI) : void {
		$nLocation = $playerAPI->getNLocation();
		$player = $playerAPI->getPlayer();
		if ($playerAPI->getOnlineTime() > 10 && !empty($nLocation) && $player->isSurvival()) {
			$recived = false;
			if ($packet instanceof MovePlayerPacket) {
				$recived = true;
			}
			if ($packet instanceof PlayerAuthInputPacket) {
				$limit = $player->getMovementSpeed() * 35;
				$distX = $nLocation["to"]->getX() - $nLocation["from"]->getX();
				$distZ = $nLocation["to"]->getZ() - $nLocation["from"]->getZ();
				$dist = ($distX * $distX) + ($distZ * $distZ);
				$lastDist = $dist;
				$shiftedLastDist = $lastDist * 0.91;
				$equalness = $dist - $shiftedLastDist;
				$scaledEqualness = $equalness * 138;
				$idBlockDown = $player->getWorld()->getBlockAt(intval($player->getLocation()->getX()), intval($player->getLocation()->getY() - 0.01), intval($player->getLocation()->getZ()))->getTypeId();
				$isFalling = $playerAPI->getLastGroundY() > $player->getLocation()->getY();
				$limit += $playerAPI->getJumpTicks() < 40 ? ($limit / 3) : 0;
				$limit += $player->isSprinting() ? ($limit / 33) : 0;
				$effects = [];
				foreach ($player->getEffects()->all() as $index => $effect) {
					$transtable = $effect->getType()->getName()->getText();
					$effects[$transtable] = $effect->getEffectLevel() + 1;
				}
				$limit += isset($effects["potion.moveSpeed"]) ? (pow($effects["potion.moveSpeed"] * 2, 2) / 16) : 0;
				$limit -= $playerAPI->isInLiquid() ? ($limit / 2.6) : 0;
				$limit -= $playerAPI->isInWeb() ? ($limit / 1.1) : 0;
				$limit -= BlockUtil::isUnderBlock($nLocation["to"], [BlockTypeIds::SOUL_SAND], 1) ? ($limit / 1.3) : 0;
				if ($playerAPI->isOnGround() && !$playerAPI->isOnAdhesion() && !$playerAPI->isOnIce() && $playerAPI->getAttackTicks() > 100 && $player->isSurvival() && !$recived && !$isFalling && $idBlockDown !== 0) {
					if ($scaledEqualness > $limit) {
						$this->failed($playerAPI);
						$this->debug($playerAPI, "isFalling=$isFalling, limit=$limit, distX=$distX, distZ=$distZ, dist=$dist, lastDist=$dist, shiftedLastDist=$shiftedLastDist, equalness=$equalness, scaledEqualness=$scaledEqualness");
					}
				}
			}
		}
	}
}