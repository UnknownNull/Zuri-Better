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

namespace ReinfyTeam\Zuri;

use Phar;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use ReinfyTeam\Zuri\checks\aimassist\AimAssistA;
use ReinfyTeam\Zuri\checks\aimassist\AimAssistB;
use ReinfyTeam\Zuri\checks\aimassist\AimAssistC;
use ReinfyTeam\Zuri\checks\aimassist\AimAssistD;
use ReinfyTeam\Zuri\checks\aimassist\AimAssistE;
use ReinfyTeam\Zuri\checks\badpackets\Crasher;
use ReinfyTeam\Zuri\checks\badpackets\FastEat;
use ReinfyTeam\Zuri\checks\badpackets\FastThrow;
use ReinfyTeam\Zuri\checks\badpackets\SelfHit;
use ReinfyTeam\Zuri\checks\blockbreak\InstaBreak;
use ReinfyTeam\Zuri\checks\blockbreak\WrongMining;
use ReinfyTeam\Zuri\checks\blockinteract\BlockReach;
use ReinfyTeam\Zuri\checks\blockplace\FillBlock;
use ReinfyTeam\Zuri\checks\chat\SpamA;
use ReinfyTeam\Zuri\checks\chat\SpamB;
use ReinfyTeam\Zuri\checks\chat\SpamC;
use ReinfyTeam\Zuri\checks\combat\autoclick\AutoClickA;
use ReinfyTeam\Zuri\checks\combat\autoclick\AutoClickB;
use ReinfyTeam\Zuri\checks\combat\autoclick\AutoClickC;
use ReinfyTeam\Zuri\checks\combat\ImposibleHit;
use ReinfyTeam\Zuri\checks\combat\killaura\KillAuraA;
use ReinfyTeam\Zuri\checks\combat\killaura\KillAuraB;
use ReinfyTeam\Zuri\checks\combat\killaura\KillAuraC;
use ReinfyTeam\Zuri\checks\combat\killaura\KillAuraD;
use ReinfyTeam\Zuri\checks\combat\killaura\KillAuraE;
use ReinfyTeam\Zuri\checks\combat\reach\ReachA;
use ReinfyTeam\Zuri\checks\combat\reach\ReachB;
use ReinfyTeam\Zuri\checks\combat\velocity\VelocityA;
use ReinfyTeam\Zuri\checks\combat\velocity\VelocityB;
use ReinfyTeam\Zuri\checks\fly\FlyA;
use ReinfyTeam\Zuri\checks\fly\FlyB;
use ReinfyTeam\Zuri\checks\fly\FlyC;
use ReinfyTeam\Zuri\checks\inventory\AutoArmor;
use ReinfyTeam\Zuri\checks\inventory\ChestAura;
use ReinfyTeam\Zuri\checks\inventory\ChestStealler;
use ReinfyTeam\Zuri\checks\inventory\InventoryCleaner;
use ReinfyTeam\Zuri\checks\inventory\InventoryMove;
use ReinfyTeam\Zuri\checks\moving\AirMovement;
use ReinfyTeam\Zuri\checks\moving\AntiImmobile;
use ReinfyTeam\Zuri\checks\moving\FastLadder;
use ReinfyTeam\Zuri\checks\moving\FastSwim;
use ReinfyTeam\Zuri\checks\moving\Jesus;
use ReinfyTeam\Zuri\checks\moving\OmniSprint;
use ReinfyTeam\Zuri\checks\moving\Phase;
use ReinfyTeam\Zuri\checks\moving\speed\SpeedA;
use ReinfyTeam\Zuri\checks\moving\speed\SpeedB;
use ReinfyTeam\Zuri\checks\moving\speed\SpeedC;
use ReinfyTeam\Zuri\checks\moving\Spider;
use ReinfyTeam\Zuri\checks\moving\Step;
use ReinfyTeam\Zuri\checks\moving\Timer;
use ReinfyTeam\Zuri\checks\moving\WrongPitch;
use ReinfyTeam\Zuri\checks\network\AntiBot;
use ReinfyTeam\Zuri\checks\network\EditionFaker;
use ReinfyTeam\Zuri\checks\network\ProxyBot;
use ReinfyTeam\Zuri\checks\payload\CustomPayloadA;
use ReinfyTeam\Zuri\checks\scaffold\ScaffoldA;
use ReinfyTeam\Zuri\checks\scaffold\ScaffoldB;
use ReinfyTeam\Zuri\checks\scaffold\ScaffoldC;
use ReinfyTeam\Zuri\checks\scaffold\ScaffoldD;
use ReinfyTeam\Zuri\checks\scaffold\ScaffoldE;
use ReinfyTeam\Zuri\command\ZuriCommand;
use ReinfyTeam\Zuri\config\ConfigManager;
use ReinfyTeam\Zuri\listener\PlayerListener;
use ReinfyTeam\Zuri\listener\ServerListener;
use ReinfyTeam\Zuri\network\ProxyUDPSocket;
use ReinfyTeam\Zuri\task\CaptchaTask;
use ReinfyTeam\Zuri\task\NetworkTickTask;
use ReinfyTeam\Zuri\task\ServerTickTask;
use ReinfyTeam\Zuri\task\UpdateCheckerAsyncTask;
use ReinfyTeam\Zuri\utils\InternetAddress;
use ReinfyTeam\Zuri\utils\PermissionManager;

class APIProvider extends PluginBase {
    const VERSION_PLUGIN = "1.1.2";
    private static APIProvider $instance;
	private ProxyUDPSocket $proxyUDPSocket;

	private array $checks = [];

	public function onLoad() : void {
		self::$instance = $this;
		ConfigManager::checkConfig();

		if (!Phar::running(true)) {
			$this->getServer()->getLogger()->notice(ConfigManager::getData(ConfigManager::PREFIX) . TextFormat::RED . " You are running source-code of the plugin, this might degrade Zuri checking performance. We recommended to download phar plugin from poggit builds or github. Instead of using source-code from github.");
		}
	}

	public static function getInstance() : APIProvider {
		return self::$instance;
	}

	public function onEnable() : void {
		$this->loadChecks();
		$this->getScheduler()->scheduleRepeatingTask(new ServerTickTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new CaptchaTask($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new NetworkTickTask($this), 100);
		$this->getServer()->getAsyncPool()->submitTask(new UpdateCheckerAsyncTask($this->getDescription()->getVersion()));
		PermissionManager::getInstance()->register(ConfigManager::getData(ConfigManager::PERMISSION_BYPASS_PERMISSION), PermissionManager::OPERATOR);
		$this->getServer()->getPluginManager()->registerEvents(new PlayerListener(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new ServerListener(), $this);
		$this->getServer()->getCommandMap()->register("Zuri", new ZuriCommand());
		$this->proxyUDPSocket = new ProxyUDPSocket();
		if (ConfigManager::getData(ConfigManager::PROXY_ENABLE)) {
			$ip = ConfigManager::getData(ConfigManager::PROXY_IP);
			$port = ConfigManager::getData(ConfigManager::PROXY_PORT);
			try {
				$this->proxyUDPSocket->bind(new InternetAddress($ip, $port));
			} catch (\Exception $exception) {
				$this->getServer()->getLogger()->notice(ConfigManager::getData(ConfigManager::PREFIX) . TextFormat::RED . " {$exception->getMessage()}, stopping proxy...");
				return;
			}
		}
	}

	/**
	 * Do not call internally, or do not call double.
	 */
	public function loadChecks() : void {
		if (!empty($this->checks)) {
			$this->checks = [];
		}

		// Aim Assist
		$this->checks[] = new AimAssistA();
		$this->checks[] = new AimAssistB();
		$this->checks[] = new AimAssistC();
		$this->checks[] = new AimAssistD();
		$this->checks[] = new AimAssistE();

		// Badpackets
		$this->checks[] = new Crasher();
		$this->checks[] = new FastEat();
		$this->checks[] = new SelfHit();
		$this->checks[] = new FastThrow();

		// Blockbreak
		$this->checks[] = new WrongMining();
		$this->checks[] = new InstaBreak();

		// BlockInteract
		$this->checks[] = new BlockReach();

		// BlockPlace
		$this->checks[] = new FillBlock();

		// Chat
		$this->checks[] = new SpamA();
		$this->checks[] = new SpamB();
		$this->checks[] = new SpamC();

		// Combat
		$this->checks[] = new ReachA();
		$this->checks[] = new ReachB();
		$this->checks[] = new AutoClickA();
		$this->checks[] = new AutoClickB();
		$this->checks[] = new AutoClickC();
		$this->checks[] = new KillAuraA();
		$this->checks[] = new KillAuraB();
		$this->checks[] = new KillAuraC();
		$this->checks[] = new KillAuraD();
		$this->checks[] = new KillAuraE();
		$this->checks[] = new VelocityA();
		$this->checks[] = new VelocityB();
		$this->checks[] = new ImposibleHit();

		// Fly
		$this->checks[] = new FlyA();
		$this->checks[] = new FlyB();
		$this->checks[] = new FlyC();

		// Inventory
		$this->checks[] = new AutoArmor();
		$this->checks[] = new ChestAura();
		$this->checks[] = new InventoryMove();
		$this->checks[] = new ChestStealler();
		$this->checks[] = new InventoryCleaner();

		// Movements
		$this->checks[] = new WrongPitch();
		$this->checks[] = new AirMovement();
		$this->checks[] = new AntiImmobile();
		$this->checks[] = new Phase();
		$this->checks[] = new Step();
		$this->checks[] = new Timer();
		$this->checks[] = new OmniSprint();
		$this->checks[] = new Jesus();
		$this->checks[] = new Spider();
		$this->checks[] = new FastLadder();
		$this->checks[] = new FastSwim();
		$this->checks[] = new SpeedA();
		$this->checks[] = new SpeedB();
		$this->checks[] = new SpeedC();

		// Network related
		$this->checks[] = new AntiBot();
		$this->checks[] = new EditionFaker();
		$this->checks[] = new ProxyBot();

		// Payloads
		$this->checks[] = new CustomPayloadA();

		// Scaffold
		// Todo: Improve and add more checks in next release..
		$this->checks[] = new ScaffoldA();
		$this->checks[] = new ScaffoldB();
		$this->checks[] = new ScaffoldC();
		$this->checks[] = new ScaffoldD();
		$this->checks[] = new ScaffoldE();
	}

	public static function Checks() : array {
		return APIProvider::getInstance()->checks;
	}
}
