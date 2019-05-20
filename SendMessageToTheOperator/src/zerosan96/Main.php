<?php

/*
プラグインの著作権は、すべて
zerosan96に帰属します
*/

/*
開発者さまから見るといらないuse文がたくさん書いてあります...
許してください...
*/

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
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\entity\Effect;
use pocketmine\event\entity\EntityDamageByEntityEvent; 
use pocketmine\event\entity\EntityDamageEvent; 
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDeathEvent;
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
use pocketmine\utils\Utils;

class Main extends PluginBase implements Listener{
	
	public function onEnable(){

		$pluginName = "SendMessageToTheOperator";

		$this->getServer()->getPluginManager()->registerEvents($this,$this);

		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder(), 0744, true);
		}

		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML,

			[

				'configVersion' => '10'

			]

		);

		$this->getLogger()->info("§b>> {$pluginName}を読み込みました！");
		$this->getLogger()->critical("§c>> 二次配布及び改造配布を禁じます。 §dBy zerosan96");
		
	}

	public function onCommand(CommandSender  $sender, Command $command,  string $label, array $args): bool{

		$name = $sender->getName();

		switch($command->getName()){

			case "som":

			if(!isset($args[0])){

				$sender->sendMessage("§a>> /som メッセージ...現在オンラインの権限者にメッセージを送信できます。");

			}else{

				$players = $this->getServer()->getOnlinePlayers();
				$operators = array();

				foreach($players as $player){

					if($player->isOp()){

						array_push($operators, $player);

					}

				}

				if(count($operators) > 0){

					$sender->sendMessage("§a>> 権限者にメッセージを送信しました！: {$args[0]}");

					foreach($operators as $operator){

						$operator->sendMessage("§7[§eSOM§7]§c>> §b{$name}からOP宛メッセージ: {$args[0]}");

					}

				}else{

					$sender->sendMessage("§c>> 現在オンラインの権限者が存在しません。");

				}

			}

			return true;

		}

	}
	
}