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

namespace ReinfyTeam\Zuri\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\TextFormat;
use ReinfyTeam\Zuri\config\ConfigManager;
use ReinfyTeam\Zuri\config\ConfigPaths;
use function date;
use function json_decode;
use function strtotime;

class UpdateCheckerAsyncTask extends AsyncTask {
	private string $currentVersion;

	public function __construct(string $currentVersion) {
		$this->currentVersion = $currentVersion;
	}

	public function onRun() : void {
		$result = Internet::getURL("https://api.github.com/repos/ReinfyTeam/Zuri-Rewrite/releases/latest", 10, [], $err);
		$this->setResult([$result->getBody(), $err]);
	}

	public function onCompletion() : void {
		$server = Server::getInstance();
		$result = $this->getResult();
		$name = "";
		$ver = "";
		$download_url = "";
		$noUpdates = false;
		if ($result[1] === null) {
			$json = json_decode($result[0], true);
			if ($json !== false && $json !== null) {
				if (($ver = $json["tag_name"]) !== "v" . $this->currentVersion) {
					$name = $json["name"];
					if ($json["prerelease"]) {
						$ver = $ver . "-PRERELEASE";
					}
					$download_url = $json["assets"][0]["browser_download_url"];
					$file_size = $json["assets"][0]["size"] / 1000;
					$dlcount = $json["assets"][0]["download_count"];
					$branch = $json["target_commitish"];
					$publishTime = date('F j, o', strtotime($json["published_at"]));

				} else {
					$noUpdates = true;
				}
			}
		} else {
			$server->getLogger()->notice(ConfigManager::getData(ConfigPaths::PREFIX) . " " . TextFormat::RED . "An error occur while checking updates from github. Error Code: " . $result[1] . ", Please check your internet connection, and try again.");
			return;
		}

		if ($noUpdates) {
			$server->getLogger()->notice(ConfigManager::getData(ConfigPaths::PREFIX) . " " . TextFormat::GREEN . "No updates found. Enjoy!");
		} else {
			$server->getLogger()->warning(ConfigManager::getData(ConfigPaths::PREFIX) . " " . TextFormat::AQUA . "A new latest version of Zuri is released! (" . $publishTime . ")");
			$server->getLogger()->warning(ConfigManager::getData(ConfigPaths::PREFIX) . " " . TextFormat::AQUA . "Current Version: v" . $this->currentVersion);
			$server->getLogger()->warning(ConfigManager::getData(ConfigPaths::PREFIX) . " " . TextFormat::AQUA . "Latest Version: " . $ver . " (" . $branch . ")");
			$server->getLogger()->warning(ConfigManager::getData(ConfigPaths::PREFIX) . " " . TextFormat::AQUA . "Download Count: " . $dlcount);
			$server->getLogger()->warning(ConfigManager::getData(ConfigPaths::PREFIX) . " " . TextFormat::AQUA . "Download: " . $download_url . " (" . $file_size . " KB)");
		}
	}
}