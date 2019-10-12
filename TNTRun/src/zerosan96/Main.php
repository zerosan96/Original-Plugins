<?php

namespace zerosan96;

use pocketmine\Player;
use pocketmine\Entitiy;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\SetEntityDataPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\utils\UUID;
use pocketmine\utils\Internet;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\scheduler\PluginTask;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityDamageByEntityEvent; 
use pocketmine\event\entity\EntityDamageEvent; 
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerDeathEvent;
use zerosan96\CallbackTask;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\entity\EffectInstance;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\ArmorInventoryEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\utils\Color;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\math\AxisAlignedBB;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\network\mcpe\protocol\MoveEntityAbsolutePacket;
use pocketmine\network\mcpe\handler\SessionHandler;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\projectile\Allow;
use pocketmine\event\entity\EntityDamageByChildEntityEvent; 
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\byteTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;

class Main extends PluginBase implements Listener{

	public function onEnable(){

		$this->getLogger()->info("§a>> TNTRUNSystemを読み込みました。");
		$this->getLogger()->info("§b>> Created by zerosan96");

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, 'runCheck'], []), 5);

		$this->status = false;

	}

	public function onCommand(CommandSender  $sender, Command $command,  string $label, array $args): bool{

		$name = $sender->getName();

		$players = $this->getServer()->getOnlinePlayers();

		switch($command->getName()){

			case "tntrun":
			if($sender->isOp()){

				if(!isset($args[0])){

					$sender->sendMessage("§e>> サブコマンドを入力してください。");

				}elseif($args[0] === "true"){

					$this->status = true;

					$sender->sendMessage("§a>> TNTRunを有効化しました！");

				}elseif($args[0] === "false"){

					$this->status = false;

					$sender->sendMessage("§a>> TNTRunを無効化しました！");

				}else{

					$sender->sendMessage("§e>> そのようなサブコマンドは存在しません！");

				}

			}else{

				$sender->sendMessage("§c>> このコマンドを実行する権限がありません");
				
			}
			return true;

		}

	}

	public function runCheck(){

		$players = $this->getServer()->getOnlinePlayers();

		foreach($players as $player){

			$name = $player->getName();

			$level = $player->getLevel();

			$x = floor($player->getX());
			$y = floor($player->getY());
			$z = floor($player->getZ());

			$py = $y + 1;
			$my = $y - 2;
			$mmy = $y - 3;

			if(!isset($this->count[$name])){

				$this->count[$name] = 0;

			}else{

				if($this->status === true){

					if(isset($this->y[$name])){

						$minusy = $this->y[$name] - 1;
						$plusy = $this->y[$name] + 1;

						if($minusy == $y || $plusy == $py){

							if(!isset($this->xyz[$name])){

								$this->xyz[$name] = array();

								$px = $x + 1;
								$mx = $x - 1;
								$pz = $z + 1;
								$mz = $z - 1;
								$py = $y + 1;

								array_push($this->xyz[$name], "{$x},{$y},{$z}");
								array_push($this->xyz[$name], "{$x},{$y},{$pz}");
								array_push($this->xyz[$name], "{$x},{$y},{$mz}");
								array_push($this->xyz[$name], "{$px},{$y},{$z}");
								array_push($this->xyz[$name], "{$px},{$y},{$pz}");
								array_push($this->xyz[$name], "{$px},{$y},{$mz}");
								array_push($this->xyz[$name], "{$mx},{$y},{$z}");
								array_push($this->xyz[$name], "{$mx},{$y},{$pz}");
								array_push($this->xyz[$name], "{$mx},{$y},{$mz}");

								array_push($this->xyz[$name], "{$x},{$py},{$z}");
								array_push($this->xyz[$name], "{$x},{$py},{$pz}");
								array_push($this->xyz[$name], "{$x},{$py},{$mz}");
								array_push($this->xyz[$name], "{$px},{$py},{$z}");
								array_push($this->xyz[$name], "{$px},{$py},{$pz}");
								array_push($this->xyz[$name], "{$px},{$py},{$mz}");
								array_push($this->xyz[$name], "{$mx},{$py},{$z}");
								array_push($this->xyz[$name], "{$mx},{$py},{$pz}");
								array_push($this->xyz[$name], "{$mx},{$py},{$mz}");

							}else{

								$bool = false;

								foreach($this->xyz[$name] as $xyz){

									if($xyz === "{$x},{$y},{$z}"){

										$bool = true;

									}

								}

								if($bool){

									$this->count[$name]++;

								}else{

									$this->count[$name] = 0;

								}

								if($this->count[$name] >= 20){

									$this->xyz[$name] = array();

									$xp = $x + 1;
									$xm = $x - 1;
									$zp = $z + 1;
									$zm = $z - 1;

									$my = $y - 1;
									$mmy = $y - 2;
									$mmmy = $y - 3;

									if($level->getBlock(new Position($x,$my,$z,$level))->getId() === 12 || $level->getBlock(new Position($x,$my,$z,$level))->getId() === 46 || $level->getBlock(new Position($x,$my,$z,$level))->getId() === 0){
										$level->setBlock(new Position($x,$my,$z,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($x,$my,$zp,$level))->getId() === 12 || $level->getBlock(new Position($x,$my,$zp,$level))->getId() === 46 || $level->getBlock(new Position($x,$my,$zp,$level))->getId() === 0){
										$level->setBlock(new Position($x,$my,$zp,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($x,$my,$zm,$level))->getId() === 12 || $level->getBlock(new Position($x,$my,$zm,$level))->getId() === 46 || $level->getBlock(new Position($x,$my,$zm,$level))->getId() === 0){
										$level->setBlock(new Position($x,$my,$zm,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xp,$my,$z,$level))->getId() === 12 || $level->getBlock(new Position($xp,$my,$z,$level))->getId() === 46 || $level->getBlock(new Position($xp,$my,$z,$level))->getId() === 0){
										$level->setBlock(new Position($xp,$my,$z,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xp,$my,$zp,$level))->getId() === 12 || $level->getBlock(new Position($xp,$my,$zp,$level))->getId() === 46 || $level->getBlock(new Position($xp,$my,$zp,$level))->getId() === 0){
										$level->setBlock(new Position($xp,$my,$zp,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xp,$my,$zm,$level))->getId() === 12 || $level->getBlock(new Position($xp,$my,$zm,$level))->getId() === 46 || $level->getBlock(new Position($xp,$my,$zm,$level))->getId() === 0){
										$level->setBlock(new Position($xp,$my,$zm,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xm,$my,$z,$level))->getId() === 12 || $level->getBlock(new Position($xm,$my,$z,$level))->getId() === 46 || $level->getBlock(new Position($xm,$my,$z,$level))->getId() === 0){
										$level->setBlock(new Position($xm,$my,$z,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xm,$my,$zp,$level))->getId() === 12 || $level->getBlock(new Position($xm,$my,$zp,$level))->getId() === 46 || $level->getBlock(new Position($xm,$my,$zp,$level))->getId() === 0){
										$level->setBlock(new Position($xm,$my,$zp,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xp,$my,$zm,$level))->getId() === 12 || $level->getBlock(new Position($x,$my,$zm,$level))->getId() === 46 || $level->getBlock(new Position($x,$my,$zm,$level))->getId() === 0){
										$level->setBlock(new Position($xm,$my,$zm,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($x,$mmy,$z,$level))->getId() === 12 || $level->getBlock(new Position($x,$mmy,$z,$level))->getId() === 46 || $level->getBlock(new Position($x,$mmy,$z,$level))->getId() === 0){
										$level->setBlock(new Position($x,$mmy,$z,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($x,$mmy,$zp,$level))->getId() === 12 || $level->getBlock(new Position($x,$mmy,$zp,$level))->getId() === 46 || $level->getBlock(new Position($x,$mmy,$zp,$level))->getId() === 0){
										$level->setBlock(new Position($x,$mmy,$zp,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($x,$mmy,$zm,$level))->getId() === 12 || $level->getBlock(new Position($x,$mmy,$zm,$level))->getId() === 46 || $level->getBlock(new Position($x,$mmy,$zm,$level))->getId() === 0){
										$level->setBlock(new Position($x,$mmy,$zm,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xp,$mmy,$z,$level))->getId() === 12 || $level->getBlock(new Position($xp,$mmy,$z,$level))->getId() === 46 || $level->getBlock(new Position($xp,$mmy,$z,$level))->getId() === 0){
										$level->setBlock(new Position($xp,$mmy,$z,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xp,$mmy,$zp,$level))->getId() === 12 || $level->getBlock(new Position($xp,$mmy,$zp,$level))->getId() === 46 || $level->getBlock(new Position($xp,$mmy,$zp,$level))->getId() === 0){
										$level->setBlock(new Position($xp,$mmy,$zp,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xp,$mmy,$zm,$level))->getId() === 12 || $level->getBlock(new Position($xp,$mmy,$zm,$level))->getId() === 46 || $level->getBlock(new Position($xp,$mmy,$zm,$level))->getId() === 0){
										$level->setBlock(new Position($xp,$mmy,$zm,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xm,$mmy,$z,$level))->getId() === 12 || $level->getBlock(new Position($xm,$mmy,$z,$level))->getId() === 46 || $level->getBlock(new Position($xm,$mmy,$z,$level))->getId() === 0){
										$level->setBlock(new Position($xm,$mmy,$z,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xm,$mmy,$zp,$level))->getId() === 12 || $level->getBlock(new Position($xm,$mmy,$zp,$level))->getId() === 46 || $level->getBlock(new Position($xm,$mmy,$zp,$level))->getId() === 0){
										$level->setBlock(new Position($xm,$mmy,$zp,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($xp,$mmy,$zm,$level))->getId() === 12 || $level->getBlock(new Position($x,$mmy,$zm,$level))->getId() === 46 || $level->getBlock(new Position($x,$mmy,$zm,$level))->getId() === 0){
										$level->setBlock(new Position($xm,$mmy,$zm,$level), Block::get(0,0));
									}
									if($level->getBlock(new Position($x,$mmmy,$z,$level))->getId() === 12 || $level->getBlock(new Position($x,$mmmy,$z,$level))->getId() === 46 || $level->getBlock(new Position($x,$mmmy,$z,$level))->getId() === 0){
										$level->setBlock(new Position($x,$mmmy,$z,$level), Block::get(0,0));
									}
									
								}else{

									$this->xyz[$name] = array();

									$px = $x + 1;
									$mx = $x - 1;
									$pz = $z + 1;
									$mz = $z - 1;

									$py = $y + 1;

									array_push($this->xyz[$name], "{$x},{$y},{$z}");
									array_push($this->xyz[$name], "{$x},{$y},{$pz}");
									array_push($this->xyz[$name], "{$x},{$y},{$mz}");
									array_push($this->xyz[$name], "{$px},{$y},{$z}");
									array_push($this->xyz[$name], "{$px},{$y},{$pz}");
									array_push($this->xyz[$name], "{$px},{$y},{$mz}");
									array_push($this->xyz[$name], "{$mx},{$y},{$z}");
									array_push($this->xyz[$name], "{$mx},{$y},{$pz}");
									array_push($this->xyz[$name], "{$mx},{$y},{$mz}");

									array_push($this->xyz[$name], "{$x},{$py},{$z}");
									array_push($this->xyz[$name], "{$x},{$py},{$pz}");
									array_push($this->xyz[$name], "{$x},{$py},{$mz}");
									array_push($this->xyz[$name], "{$px},{$py},{$z}");
									array_push($this->xyz[$name], "{$px},{$py},{$pz}");
									array_push($this->xyz[$name], "{$px},{$py},{$mz}");
									array_push($this->xyz[$name], "{$mx},{$py},{$z}");
									array_push($this->xyz[$name], "{$mx},{$py},{$pz}");
									array_push($this->xyz[$name], "{$mx},{$py},{$mz}");

								}

							}
						
						}

					}

				}

			}

		}

	}

	public function onMove(PlayerMoveEvent $event){

		$player = $event->getPlayer();
		$name = $player->getName();

		$level = $player->getLevel();

		$x = floor($player->x);
		$y = floor($player->y);
		$z = floor($player->z);

		$pos = $player->subtract(0,1);

		$blockx = $level->getBlock($pos)->getX();
		$blocky = $level->getBlock($pos)->getY();
		$blockz = $level->getBlock($pos)->getZ();

		var_dump($blockx);
		var_dump($blocky);
		var_dump($blockz);

		$py = $y + 1;

		$myid = $level->getBlock(new Vector3($blockx,$blocky,$blockz))->getId();
		$mmyid = $level->getBlock(new Vector3($blockx,$blocky - 1,$blockz))->getId();
		$mmmyid = $level->getBlock(new Vector3($blockx,$blocky - 2,$blockz))->getId();

		/*
		$myid = $player->getLevel()->getBlock(new Position($x,$my,$z,$level))->getId();
		$mmyid = $player->getLevel()->getBlock(new Position($x,$mmy,$z,$level))->getId();
		$mmmyid = $player->getLevel()->getBlock(new Position($x,$mmmy,$z,$level))->getId();
		*/

		if($myid === 46 || $mmyid === 46 || $mmmyid === 46){

			if($this->status === true){

				if(!isset($this->xyz[$name])){

					$this->x[$name] = $blockx;
					$this->y[$name] = $blocky + 1;
					$this->z[$name] = $blockz;

				}else{

					if($this->x[$name] === $x && $this->z[$name] === $z){

						$minusy = $this->y[$name] - 1;
						$plusy = $this->y[$name] + 1;

						if($minusy == $y || $plusy == $py){

							//No progress

						}else{

							$sx = $this->x[$name];
							$sz = $this->z[$name];
							$y = $this->y[$name];

							$level->setBlock(new Position($sx,$y,$sz,$level),Block::get(0,0));
							$level->setBlock(new Position($sx,$y - 1,$sz,$level),Block::get(0,0));
							$level->setBlock(new Position($sx,$y - 2,$sz,$level),Block::get(0,0));

							$this->x[$name] = $blockx;
							$this->y[$name] = $blocky + 1;
							$this->z[$name] = $blockz;

						}

					}else{

						$sx = $this->x[$name];
						$sz = $this->z[$name];

						$level->setBlock(new Position($sx,$y,$sz,$level),Block::get(0,0));
						$level->setBlock(new Position($sx,$y - 1,$sz,$level),Block::get(0,0));
						$level->setBlock(new Position($sx,$y - 2,$sz,$level),Block::get(0,0));

						$this->x[$name] = $blockx;
						$this->y[$name] = $blocky + 1;
						$this->z[$name] = $blockz;

					}

				}

			}

		}

	}

	public function PlayerKick(PlayerKickEvent $event){

		$player = $event->getPlayer();
		$name = $player->getName();

		if($event->getReason() === "Server is white-listed"){

			$player->kick("現在サーバーは開いておりません", false);

		}elseif($event->getReason() === "You have been banned"){

			$player->kick("貴方は死んだのでもう参加できません！", false);

		}elseif($event->getReason() === "You are banned"){

			$player->kick("貴方は死んだのでもう参加できません！", false);


		}

	}

}