<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

/**
 * Network-related classes
 */
namespace pocketmine\network;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\network\protocol\AddPaintingPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\ContainerClosePacket;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\ContainerSetDataPacket;
use pocketmine\network\protocol\ContainerSetSlotPacket;
use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\network\protocol\CraftingEventPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DropItemPacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\Info;
use pocketmine\network\protocol\MapInfoRequestPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\SpawnExperienceOrbPacket;
use pocketmine\network\protocol\TileEntityDataPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\ExplodePacket;
use pocketmine\network\protocol\HurtArmorPacket;
use pocketmine\network\protocol\Info92 as ProtocolInfo92;
use pocketmine\network\protocol\Info105 as ProtocolInfo105;
use pocketmine\network\protocol\Info110 as ProtocolInfo110;
use pocketmine\network\protocol\Info120 as ProtocolInfo120;
use pocketmine\network\protocol\Info310 as ProtocolInfo310;
use pocketmine\network\protocol\Info331 as ProtocolInfo331;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\LevelSoundEventPacket;
use pocketmine\network\protocol\DisconnectPacket;
use pocketmine\network\protocol\LoginPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\network\protocol\RemoveBlockPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\SetDifficultyPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\network\protocol\TileEventPacket;
use pocketmine\network\protocol\TransferPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\UseItemPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\v120\PlayerSkinPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\utils\BinaryStream;
use pocketmine\network\protocol\ChunkRadiusUpdatePacket;
use pocketmine\network\protocol\RequestChunkRadiusPacket;
use pocketmine\network\protocol\SetCommandsEnabledPacket;
use pocketmine\network\protocol\AvailableCommandsPacket;
use pocketmine\network\protocol\CommandStepPacket;
use pocketmine\network\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\protocol\ResourcePacksInfoPacket;
use pocketmine\network\protocol\ClientToServerHandshakePacket;
use pocketmine\network\protocol\CreativeContentPacket;
use pocketmine\network\protocol\ItemComponentPacket;
use pocketmine\network\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\protocol\v120\CommandRequestPacket;
use pocketmine\network\protocol\v120\InventoryContentPacket;
use pocketmine\network\protocol\v120\InventoryTransactionPacket;
use pocketmine\network\protocol\v120\ModalFormResponsePacket;
use pocketmine\network\protocol\v120\PlayerHotbarPacket;
use pocketmine\network\protocol\v120\PurchaseReceiptPacket;
use pocketmine\network\protocol\v120\ServerSettingsRequestPacket;
use pocketmine\network\protocol\v120\SubClientLoginPacket;
use pocketmine\network\protocol\ResourcePackChunkRequestPacket;
use pocketmine\network\protocol\PlayerInputPacket;
use pocketmine\network\protocol\v310\AvailableEntityIdentifiersPacket;
use pocketmine\network\protocol\v310\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\protocol\v310\SpawnParticleEffectPacket;

class Network {	
	
	public static $BATCH_THRESHOLD = 512;
	
	/** @var \SplFixedArray */
	private $packetPool92;
	/** @var \SplFixedArray */
	private $packetPool105;
	/** @var \SplFixedArray */
	private $packetPool110;
	/** @var \SplFixedArray */
	private $packetPool120;
	/** @var \SplFixedArray */
	private $packetPool310;
	/** @var \SplFixedArray */
	private $packetPool331;

	/** @var Server */
	private $server;

	/** @var SourceInterface[] */
	private $interfaces = [];

	/** @var AdvancedSourceInterface[] */
	private $advancedInterfaces = [];

	private $upload = 0;
	private $download = 0;

	private $name;

	public function __construct(Server $server){
		$this->registerPackets92();
		$this->registerPackets105();
		$this->registerPackets110();
		$this->registerPackets120();
		$this->registerPackets310();
		$this->registerPackets331();
		$this->server = $server;
	}

	public function addStatistics($upload, $download){
		$this->upload += $upload;
		$this->download += $download;
	}

	public function getUpload(){
		return $this->upload;
	}

	public function getDownload(){
		return $this->download;
	}

	public function resetStatistics(){
		$this->upload = 0;
		$this->download = 0;
	}

	/**
	 * @return SourceInterface[]
	 */
	public function getInterfaces(){
		return $this->interfaces;
	}

	public function setCount($count, $maxcount = 31360) {
		$this->server->mainInterface->setCount($count, $maxcount);
	}

	public function processInterfaces() {
		foreach($this->interfaces as $interface) {
			try {
				$interface->process();
			}catch(\Exception $e){
				$logger = $this->server->getLogger();
				if(\pocketmine\DEBUG > 1){
					if($logger instanceof MainLogger){
						$logger->logException($e);
					}
				}

				$interface->emergencyShutdown();
				$this->unregisterInterface($interface);
				$logger->critical("Network error: ".$e->getMessage());
			}
		}
	}

	/**
	 * @param SourceInterface $interface
	 */
	public function registerInterface(SourceInterface $interface) {
		$this->interfaces[$hash = spl_object_hash($interface)] = $interface;
		if($interface instanceof AdvancedSourceInterface) {
			$this->advancedInterfaces[$hash] = $interface;
			$interface->setNetwork($this);
		}
		$interface->setName($this->name);
	}

	/**
	 * @param SourceInterface $interface
	 */
	public function unregisterInterface(SourceInterface $interface) {
		unset($this->interfaces[$hash = spl_object_hash($interface)], $this->advancedInterfaces[$hash]);
	}

	/**
	 * Sets the server name shown on each interface Query
	 *
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = (string)$name;
		foreach($this->interfaces as $interface) {
			$interface->setName($this->name);
		}
	}

	public function getName(){
		return $this->name;
	}

	public function updateName() {
		foreach($this->interfaces as $interface) {
			$interface->setName($this->name);
		}
	}
	
    /**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket92($id, $class){
		$this->packetPool92[$id] = new $class;
	}
	
    /**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket105($id, $class){
		$this->packetPool105[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket110($id, $class){
		$this->packetPool110[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket120($id, $class){
		$this->packetPool120[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket310($id, $class){
		$this->packetPool310[$id] = new $class;
	}
	
	/**
	 * @param int        $id 0-255
	 * @param DataPacket $class
	 */
	public function registerPacket331($id, $class){
		$this->packetPool331[$id] = new $class;
	}
	
	public function getServer(){
		return $this->server;
	}
		
	public function processBatch(BatchPacket $packet, Player $p){
		$str = @\zlib_decode($packet->payload, 1024 * 1024 * 64); //Max 64MB
		if ($str === false) {
			$p->checkVersion();
			return;
		}
		$len = strlen($str);
		$offset = 0;
		try{
			$stream = new BinaryStream($str);
			$length = strlen($str);
			while ($stream->getOffset() < $length) {
				$buf = $stream->getString();
				if(strlen($buf) === 0){
					throw new \InvalidStateException("Empty or invalid BatchPacket received");
				}

//				if (ord($buf{0}) !== 0x13) {
//					echo 'Recive: 0x'. bin2hex($buf{0}).PHP_EOL;
//				}
				
				if (($pk = $this->getPacket(ord($buf{0}), $p->getPlayerProtocol())) !== null) {
					if ($pk::NETWORK_ID === Info::BATCH_PACKET) {
						throw new \InvalidStateException("Invalid BatchPacket inside BatchPacket");
					}

					$pk->setBuffer($buf, 1);
					$pk->decode($p->getPlayerProtocol());
					$p->handleDataPacket($pk);
					if ($pk->getOffset() <= 0) {
						return;
					}
				} else {
//					echo "UNKNOWN PACKET: ".bin2hex($buf{0}).PHP_EOL;
//					echo "Buffer DEC: ".$buf.PHP_EOL;
//					echo "Buffer HEX: ".bin2hex($buf).PHP_EOL;
				}
			}
		}catch(\Exception $e){
			if(\pocketmine\DEBUG > 1){
				$logger = $this->server->getLogger();
				if($logger instanceof MainLogger){
					$logger->debug("BatchPacket " . " 0x" . bin2hex($packet->payload));
					$logger->logException($e);
				}
			}
		}
	}

	/**
	 * @param $id
	 *
	 * @return DataPacket
	 */
	public function getPacket($id, $playerProtocol){
		/** @var DataPacket $class */
		switch ($playerProtocol) {
			case Info::PROTOCOL_331:
			case Info::PROTOCOL_332:
			case Info::PROTOCOL_340:
			case Info::PROTOCOL_342:
			case Info::PROTOCOL_350:
			case Info::PROTOCOL_351:
			case Info::PROTOCOL_354:
			case Info::PROTOCOL_360:
			case Info::PROTOCOL_361:
			case Info::PROTOCOL_370:
		    case Info::PROTOCOL_385:
			case Info::PROTOCOL_386:
			case Info::PROTOCOL_390:
			case Info::PROTOCOL_389:
			case Info::PROTOCOL_392:
			case Info::PROTOCOL_393:
			case Info::PROTOCOL_400:
			case Info::PROTOCOL_406:	
			case Info::PROTOCOL_407:	
			case Info::PROTOCOL_408:
			case Info::PROTOCOL_409:
			case Info::PROTOCOL_419:
			case Info::PROTOCOL_422:
				$class = $this->packetPool331[$id];
				break;
			case Info::PROTOCOL_310:
			case Info::PROTOCOL_311:
			case Info::PROTOCOL_330:
				$class = $this->packetPool310[$id];
				break;
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_200:
			case Info::PROTOCOL_220:
			case Info::PROTOCOL_221:
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_260:
			case Info::PROTOCOL_271:
			case Info::PROTOCOL_273:
			case Info::PROTOCOL_274:
			case Info::PROTOCOL_280:
			case Info::PROTOCOL_282:
			case Info::PROTOCOL_290:
				$class = $this->packetPool120[$id];
				break;
			case Info::PROTOCOL_110:
			case Info::PROTOCOL_111:
			case info::PROTOCOL_112:
			case info::PROTOCOL_113:
				$class = $this->packetPool110[$id];
				break;
			case Info::PROTOCOL_105:
				$class = $this->packetPool105[$id];
				break;
			case Info::PROTOCOL_92:
			case Info::PROTOCOL_100:
			case Info::PROTOCOL_101:
			case Info::PROTOCOL_102:
				$class = $this->packetPool92[$id];
				break;
		}
		if($class !== null){
			return clone $class;
		}
		return null;
	}
	
	public static function getChunkPacketProtocol($playerProtocol){
		switch ($playerProtocol) {
			case Info::PROTOCOL_422:
			case Info::PROTOCOL_419:
				return Info::PROTOCOL_419;
			case Info::PROTOCOL_409:
				return Info::PROTOCOL_409;
			case Info::PROTOCOL_408:
			case Info::PROTOCOL_407:
			case Info::PROTOCOL_406:
				return Info::PROTOCOL_406;
			case Info::PROTOCOL_400:
			case Info::PROTOCOL_393:
			case Info::PROTOCOL_392:
			case Info::PROTOCOL_390:
			case Info::PROTOCOL_389:
			case Info::PROTOCOL_386:
			case Info::PROTOCOL_385:
			case Info::PROTOCOL_370:
			case Info::PROTOCOL_361:
			case Info::PROTOCOL_360:
				return Info::PROTOCOL_360;
			case Info::PROTOCOL_354:
			case Info::PROTOCOL_351:
			case Info::PROTOCOL_350:
			case Info::PROTOCOL_342:
			case Info::PROTOCOL_340:
			case Info::PROTOCOL_332:
			case Info::PROTOCOL_331:
			case Info::PROTOCOL_330:
			case Info::PROTOCOL_311:
			case Info::PROTOCOL_310:
			case Info::PROTOCOL_290:
			case Info::PROTOCOL_282:
			case Info::PROTOCOL_280:
				return Info::PROTOCOL_280;
			case Info::PROTOCOL_120:
			case Info::PROTOCOL_200:
			case Info::PROTOCOL_220:
			case Info::PROTOCOL_221:
			case Info::PROTOCOL_240:
			case Info::PROTOCOL_260:
			case Info::PROTOCOL_271:
			case Info::PROTOCOL_273:
			case Info::PROTOCOL_274:
				return Info::PROTOCOL_120;
			default:
				return Info::PROTOCOL_110;
			
		}
	}
	
	/**
	 * @param string $address
	 * @param int    $port
	 * @param string $payload
	 */
	public function sendPacket($address, $port, $payload){
		foreach($this->advancedInterfaces as $interface){
			$interface->sendRawPacket($address, $port, $payload);
		}
	}

	/**
	 * Blocks an IP address from the main interface. Setting timeout to -1 will block it forever
	 *
	 * @param string $address
	 * @param int    $timeout
	 */
	public function blockAddress($address, $timeout = 300){
		foreach($this->advancedInterfaces as $interface){
			$interface->blockAddress($address, $timeout);
		}
	}
	private function registerPackets92(){
		$this->packetPool = new \SplFixedArray(256);
		$this->registerPacket92(ProtocolInfo92::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket92(ProtocolInfo92::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket92(ProtocolInfo92::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket92(ProtocolInfo92::BATCH_PACKET, BatchPacket::class);
		$this->registerPacket92(ProtocolInfo92::TEXT_PACKET, TextPacket::class);
		$this->registerPacket92(ProtocolInfo92::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket92(ProtocolInfo92::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket92(ProtocolInfo92::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket92(ProtocolInfo92::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket92(ProtocolInfo92::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket92(ProtocolInfo92::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket92(ProtocolInfo92::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket92(ProtocolInfo92::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket92(ProtocolInfo92::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket92(ProtocolInfo92::REMOVE_BLOCK_PACKET, RemoveBlockPacket::class);
		$this->registerPacket92(ProtocolInfo92::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket92(ProtocolInfo92::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket92(ProtocolInfo92::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket92(ProtocolInfo92::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket92(ProtocolInfo92::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket92(ProtocolInfo92::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket92(ProtocolInfo92::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket92(ProtocolInfo92::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket92(ProtocolInfo92::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket92(ProtocolInfo92::USE_ITEM_PACKET, UseItemPacket::class);
		$this->registerPacket92(ProtocolInfo92::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket92(ProtocolInfo92::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket92(ProtocolInfo92::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket92(ProtocolInfo92::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket92(ProtocolInfo92::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket92(ProtocolInfo92::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket92(ProtocolInfo92::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket92(ProtocolInfo92::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket92(ProtocolInfo92::DROP_ITEM_PACKET, DropItemPacket::class);
		$this->registerPacket92(ProtocolInfo92::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket92(ProtocolInfo92::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket92(ProtocolInfo92::CONTAINER_SET_SLOT_PACKET, ContainerSetSlotPacket::class);
		$this->registerPacket92(ProtocolInfo92::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket92(ProtocolInfo92::CONTAINER_SET_CONTENT_PACKET, ContainerSetContentPacket::class);
		$this->registerPacket92(ProtocolInfo92::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket92(ProtocolInfo92::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket92(ProtocolInfo92::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket92(ProtocolInfo92::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket92(ProtocolInfo92::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket92(ProtocolInfo92::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket92(ProtocolInfo92::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket92(ProtocolInfo92::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket92(ProtocolInfo92::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket92(ProtocolInfo92::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket92(ProtocolInfo92::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket92(ProtocolInfo92::COMMAND_STEP_PACKET, CommandStepPacket::class);
		$this->registerPacket92(ProtocolInfo92::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket92(ProtocolInfo92::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket92(ProtocolInfo92::RESOURCE_PACKS_INFO_PACKET, ResourcePackDataInfoPacket::class);
		
		
	}
	private function registerPackets105(){
		$this->packetPool105 = new \SplFixedArray(256);
		$this->registerPacket105(ProtocolInfo105::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket105(ProtocolInfo105::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket105(ProtocolInfo105::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket105(ProtocolInfo105::BATCH_PACKET, BatchPacket::class);
		$this->registerPacket105(ProtocolInfo105::TEXT_PACKET, TextPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket105(ProtocolInfo105::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket105(ProtocolInfo105::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket105(ProtocolInfo105::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket105(ProtocolInfo105::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket105(ProtocolInfo105::REMOVE_BLOCK_PACKET, RemoveBlockPacket::class);
		$this->registerPacket105(ProtocolInfo105::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket105(ProtocolInfo105::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket105(ProtocolInfo105::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket105(ProtocolInfo105::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket105(ProtocolInfo105::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket105(ProtocolInfo105::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket105(ProtocolInfo105::USE_ITEM_PACKET, UseItemPacket::class);
		$this->registerPacket105(ProtocolInfo105::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket105(ProtocolInfo105::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket105(ProtocolInfo105::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket105(ProtocolInfo105::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket105(ProtocolInfo105::DROP_ITEM_PACKET, DropItemPacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_SET_SLOT_PACKET, ContainerSetSlotPacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::CONTAINER_SET_CONTENT_PACKET, ContainerSetContentPacket::class);
		$this->registerPacket105(ProtocolInfo105::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket105(ProtocolInfo105::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket105(ProtocolInfo105::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket105(ProtocolInfo105::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket105(ProtocolInfo105::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket105(ProtocolInfo105::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket105(ProtocolInfo105::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket105(ProtocolInfo105::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket105(ProtocolInfo105::COMMAND_STEP_PACKET, CommandStepPacket::class);
		$this->registerPacket105(ProtocolInfo105::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket105(ProtocolInfo105::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket105(ProtocolInfo105::RESOURCE_PACKS_INFO_PACKET, ResourcePackDataInfoPacket::class);
	}

	private function registerPackets110(){
		$this->packetPool110 = new \SplFixedArray(256);
		$this->registerPacket110(ProtocolInfo110::LOGIN_PACKET, LoginPacket::class);
		$this->registerPacket110(ProtocolInfo110::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket110(ProtocolInfo110::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket110(ProtocolInfo110::TEXT_PACKET, TextPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket110(ProtocolInfo110::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket110(ProtocolInfo110::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket110(ProtocolInfo110::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket110(ProtocolInfo110::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket110(ProtocolInfo110::REMOVE_BLOCK_PACKET, RemoveBlockPacket::class);
		$this->registerPacket110(ProtocolInfo110::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket110(ProtocolInfo110::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket110(ProtocolInfo110::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket110(ProtocolInfo110::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket110(ProtocolInfo110::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket110(ProtocolInfo110::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket110(ProtocolInfo110::USE_ITEM_PACKET, UseItemPacket::class);
		$this->registerPacket110(ProtocolInfo110::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket110(ProtocolInfo110::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket110(ProtocolInfo110::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket110(ProtocolInfo110::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket110(ProtocolInfo110::DROP_ITEM_PACKET, DropItemPacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_SET_SLOT_PACKET, ContainerSetSlotPacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::CONTAINER_SET_CONTENT_PACKET, ContainerSetContentPacket::class);
		$this->registerPacket110(ProtocolInfo110::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket110(ProtocolInfo110::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket110(ProtocolInfo110::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket110(ProtocolInfo110::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket110(ProtocolInfo110::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket110(ProtocolInfo110::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket110(ProtocolInfo110::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket110(ProtocolInfo110::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket110(ProtocolInfo110::COMMAND_STEP_PACKET, CommandStepPacket::class);
		$this->registerPacket110(ProtocolInfo110::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket110(ProtocolInfo110::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket110(ProtocolInfo110::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket110(ProtocolInfo110::RESOURCE_PACKS_INFO_PACKET, ResourcePacksInfoPacket::class);
		$this->registerPacket110(ProtocolInfo110::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket110(ProtocolInfo110::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket110(ProtocolInfo110::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
	}
	
	private function registerPackets120() {
		$this->packetPool120 = new \SplFixedArray(256);
		$this->registerPacket120(ProtocolInfo120::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket120(ProtocolInfo120::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket120(ProtocolInfo120::TEXT_PACKET, TextPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket120(ProtocolInfo120::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket120(ProtocolInfo120::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket120(ProtocolInfo120::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket120(ProtocolInfo120::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket120(ProtocolInfo120::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket120(ProtocolInfo120::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket120(ProtocolInfo120::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket120(ProtocolInfo120::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket120(ProtocolInfo120::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket120(ProtocolInfo120::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket120(ProtocolInfo120::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket120(ProtocolInfo120::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket120(ProtocolInfo120::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket120(ProtocolInfo120::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket120(ProtocolInfo120::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket120(ProtocolInfo120::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket120(ProtocolInfo120::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket120(ProtocolInfo120::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket120(ProtocolInfo120::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket120(ProtocolInfo120::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket120(ProtocolInfo120::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket120(ProtocolInfo120::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket120(ProtocolInfo120::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket120(ProtocolInfo120::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket120(ProtocolInfo120::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket120(ProtocolInfo120::RESOURCE_PACKS_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket120(ProtocolInfo120::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket120(ProtocolInfo120::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
		$this->registerPacket120(ProtocolInfo120::MAP_INFO_REQUEST_PACKET, MapInfoRequestPacket::class);
		// new
		$this->registerPacket120(ProtocolInfo120::INVENTORY_TRANSACTION_PACKET, InventoryTransactionPacket::class);
		$this->registerPacket120(ProtocolInfo120::INVENTORY_CONTENT_PACKET, InventoryContentPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_HOTBAR_PACKET, PlayerHotbarPacket::class);
		$this->registerPacket120(ProtocolInfo120::COMMAND_REQUEST_PACKET, CommandRequestPacket::class);
		$this->registerPacket120(ProtocolInfo120::PLAYER_SKIN_PACKET, PlayerSkinPacket::class);
		$this->registerPacket120(ProtocolInfo120::MODAL_FORM_RESPONSE_PACKET, ModalFormResponsePacket::class);
		$this->registerPacket120(ProtocolInfo120::SERVER_SETTINGS_REQUEST_PACKET, ServerSettingsRequestPacket::class);
		$this->registerPacket120(ProtocolInfo120::PURCHASE_RECEIPT_PACKET, PurchaseReceiptPacket::class);
		$this->registerPacket120(ProtocolInfo120::SUB_CLIENT_LOGIN_PACKET, SubClientLoginPacket::class);		
		$this->registerPacket120(ProtocolInfo120::SPAWN_EXPERIENCE_ORB_PACKET, SpawnExperienceOrbPacket::class);
	}
	
	private function registerPackets310() {
		$this->packetPool310 = new \SplFixedArray(256);
		$this->registerPacket310(ProtocolInfo310::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket310(ProtocolInfo310::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket310(ProtocolInfo310::TEXT_PACKET, TextPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket310(ProtocolInfo310::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket310(ProtocolInfo310::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket310(ProtocolInfo310::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket310(ProtocolInfo310::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket310(ProtocolInfo310::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket310(ProtocolInfo310::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket310(ProtocolInfo310::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket310(ProtocolInfo310::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket310(ProtocolInfo310::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket310(ProtocolInfo310::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket310(ProtocolInfo310::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket310(ProtocolInfo310::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket310(ProtocolInfo310::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket310(ProtocolInfo310::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket310(ProtocolInfo310::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket310(ProtocolInfo310::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket310(ProtocolInfo310::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket310(ProtocolInfo310::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket310(ProtocolInfo310::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket310(ProtocolInfo310::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket310(ProtocolInfo310::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket310(ProtocolInfo310::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket310(ProtocolInfo310::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket310(ProtocolInfo310::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket310(ProtocolInfo310::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket310(ProtocolInfo310::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket310(ProtocolInfo310::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket310(ProtocolInfo310::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket310(ProtocolInfo310::RESOURCE_PACKS_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket310(ProtocolInfo310::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket310(ProtocolInfo310::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
		$this->registerPacket310(ProtocolInfo310::MAP_INFO_REQUEST_PACKET, MapInfoRequestPacket::class);
		$this->registerPacket310(ProtocolInfo310::INVENTORY_TRANSACTION_PACKET, InventoryTransactionPacket::class);
		$this->registerPacket310(ProtocolInfo310::INVENTORY_CONTENT_PACKET, InventoryContentPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_HOTBAR_PACKET, PlayerHotbarPacket::class);
		$this->registerPacket310(ProtocolInfo310::COMMAND_REQUEST_PACKET, CommandRequestPacket::class);
		$this->registerPacket310(ProtocolInfo310::PLAYER_SKIN_PACKET, PlayerSkinPacket::class);
		$this->registerPacket310(ProtocolInfo310::MODAL_FORM_RESPONSE_PACKET, ModalFormResponsePacket::class);
		$this->registerPacket310(ProtocolInfo310::SERVER_SETTINGS_REQUEST_PACKET, ServerSettingsRequestPacket::class);
		$this->registerPacket310(ProtocolInfo310::PURCHASE_RECEIPT_PACKET, PurchaseReceiptPacket::class);
		$this->registerPacket310(ProtocolInfo310::SUB_CLIENT_LOGIN_PACKET, SubClientLoginPacket::class);			
		$this->registerPacket310(ProtocolInfo310::AVAILABLE_ENTITY_IDENTIFIERS_PACKET, AvailableEntityIdentifiersPacket::class);	
		$this->registerPacket310(ProtocolInfo310::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);	
		$this->registerPacket310(ProtocolInfo310::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET, NetworkChunkPublisherUpdatePacket::class);	
		$this->registerPacket310(ProtocolInfo310::SPAWN_PARTICLE_EFFECT_PACKET, SpawnParticleEffectPacket::class);
		$this->registerPacket310(ProtocolInfo310::SPAWN_EXPERIENCE_ORB_PACKET, SpawnExperienceOrbPacket::class);

	}
	
	private function registerPackets331() {
		$this->packetPool331 = new \SplFixedArray(256);
		$this->registerPacket331(ProtocolInfo331::PLAY_STATUS_PACKET, PlayStatusPacket::class);
		$this->registerPacket331(ProtocolInfo331::DISCONNECT_PACKET, DisconnectPacket::class);
		$this->registerPacket331(ProtocolInfo331::TEXT_PACKET, TextPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_TIME_PACKET, SetTimePacket::class);
		$this->registerPacket331(ProtocolInfo331::START_GAME_PACKET, StartGamePacket::class);
		$this->registerPacket331(ProtocolInfo331::ADD_PLAYER_PACKET, AddPlayerPacket::class);
		$this->registerPacket331(ProtocolInfo331::ADD_ENTITY_PACKET, AddEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::REMOVE_ENTITY_PACKET, RemoveEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::ADD_ITEM_ENTITY_PACKET, AddItemEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::TAKE_ITEM_ENTITY_PACKET, TakeItemEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::MOVE_ENTITY_PACKET, MoveEntityPacket::class);
		$this->registerPacket331(ProtocolInfo331::MOVE_PLAYER_PACKET, MovePlayerPacket::class);
		$this->registerPacket331(ProtocolInfo331::UPDATE_BLOCK_PACKET, UpdateBlockPacket::class);
		$this->registerPacket331(ProtocolInfo331::ADD_PAINTING_PACKET, AddPaintingPacket::class);
		$this->registerPacket331(ProtocolInfo331::EXPLODE_PACKET, ExplodePacket::class);
		$this->registerPacket331(ProtocolInfo331::LEVEL_EVENT_PACKET, LevelEventPacket::class);
		$this->registerPacket331(ProtocolInfo331::TILE_EVENT_PACKET, TileEventPacket::class);
		$this->registerPacket331(ProtocolInfo331::ENTITY_EVENT_PACKET, EntityEventPacket::class);
		$this->registerPacket331(ProtocolInfo331::MOB_EQUIPMENT_PACKET, MobEquipmentPacket::class);
		$this->registerPacket331(ProtocolInfo331::MOB_ARMOR_EQUIPMENT_PACKET, MobArmorEquipmentPacket::class);
		$this->registerPacket331(ProtocolInfo331::INTERACT_PACKET, InteractPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_ACTION_PACKET, PlayerActionPacket::class);
		$this->registerPacket331(ProtocolInfo331::HURT_ARMOR_PACKET, HurtArmorPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_ENTITY_DATA_PACKET, SetEntityDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_ENTITY_MOTION_PACKET, SetEntityMotionPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_ENTITY_LINK_PACKET, SetEntityLinkPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_SPAWN_POSITION_PACKET, SetSpawnPositionPacket::class);
		$this->registerPacket331(ProtocolInfo331::ANIMATE_PACKET, AnimatePacket::class);
		$this->registerPacket331(ProtocolInfo331::RESPAWN_PACKET, RespawnPacket::class);
		$this->registerPacket331(ProtocolInfo331::CONTAINER_OPEN_PACKET, ContainerOpenPacket::class);
		$this->registerPacket331(ProtocolInfo331::CONTAINER_CLOSE_PACKET, ContainerClosePacket::class);
		$this->registerPacket331(ProtocolInfo331::CONTAINER_SET_DATA_PACKET, ContainerSetDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::CRAFTING_DATA_PACKET, CraftingDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::CRAFTING_EVENT_PACKET, CraftingEventPacket::class);
		$this->registerPacket331(ProtocolInfo331::ADVENTURE_SETTINGS_PACKET, AdventureSettingsPacket::class);
		$this->registerPacket331(ProtocolInfo331::TILE_ENTITY_DATA_PACKET, TileEntityDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::FULL_CHUNK_DATA_PACKET, FullChunkDataPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_COMMANDS_ENABLED_PACKET, SetCommandsEnabledPacket::class);
		$this->registerPacket331(ProtocolInfo331::SET_DIFFICULTY_PACKET, SetDifficultyPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_LIST_PACKET, PlayerListPacket::class);
		$this->registerPacket331(ProtocolInfo331::REQUEST_CHUNK_RADIUS_PACKET, RequestChunkRadiusPacket::class);
		$this->registerPacket331(ProtocolInfo331::CHUNK_RADIUS_UPDATE_PACKET, ChunkRadiusUpdatePacket::class);
		$this->registerPacket331(ProtocolInfo331::AVAILABLE_COMMANDS_PACKET, AvailableCommandsPacket::class);
		$this->registerPacket331(ProtocolInfo331::TRANSFER_PACKET, TransferPacket::class);
		$this->registerPacket331(ProtocolInfo331::CLIENT_TO_SERVER_HANDSHAKE_PACKET, ClientToServerHandshakePacket::class);
		$this->registerPacket331(ProtocolInfo331::RESOURCE_PACK_DATA_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket331(ProtocolInfo331::RESOURCE_PACKS_INFO_PACKET, ResourcePackDataInfoPacket::class);
		$this->registerPacket331(ProtocolInfo331::RESOURCE_PACKS_CLIENT_RESPONSE_PACKET, ResourcePackClientResponsePacket::class);
		$this->registerPacket331(ProtocolInfo331::RESOURCE_PACK_CHUNK_REQUEST_PACKET, ResourcePackChunkRequestPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_INPUT_PACKET, PlayerInputPacket::class);
		$this->registerPacket331(ProtocolInfo331::MAP_INFO_REQUEST_PACKET, MapInfoRequestPacket::class);
		$this->registerPacket331(ProtocolInfo331::INVENTORY_TRANSACTION_PACKET, InventoryTransactionPacket::class);
		$this->registerPacket331(ProtocolInfo331::INVENTORY_CONTENT_PACKET, InventoryContentPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_HOTBAR_PACKET, PlayerHotbarPacket::class);
		$this->registerPacket331(ProtocolInfo331::COMMAND_REQUEST_PACKET, CommandRequestPacket::class);
		$this->registerPacket331(ProtocolInfo331::PLAYER_SKIN_PACKET, PlayerSkinPacket::class);
		$this->registerPacket331(ProtocolInfo331::MODAL_FORM_RESPONSE_PACKET, ModalFormResponsePacket::class);
		$this->registerPacket331(ProtocolInfo331::SERVER_SETTINGS_REQUEST_PACKET, ServerSettingsRequestPacket::class);
		$this->registerPacket331(ProtocolInfo331::PURCHASE_RECEIPT_PACKET, PurchaseReceiptPacket::class);
		$this->registerPacket331(ProtocolInfo331::SUB_CLIENT_LOGIN_PACKET, SubClientLoginPacket::class);			
		$this->registerPacket331(ProtocolInfo331::AVAILABLE_ENTITY_IDENTIFIERS_PACKET, AvailableEntityIdentifiersPacket::class);	
		$this->registerPacket331(ProtocolInfo331::LEVEL_SOUND_EVENT_PACKET, LevelSoundEventPacket::class);	
		$this->registerPacket331(ProtocolInfo331::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET, NetworkChunkPublisherUpdatePacket::class);	
		$this->registerPacket331(ProtocolInfo331::SPAWN_PARTICLE_EFFECT_PACKET, SpawnParticleEffectPacket::class);
		$this->registerPacket331(ProtocolInfo331::SPAWN_EXPERIENCE_ORB_PACKET, SpawnExperienceOrbPacket::class);
		$this->registerPacket331(ProtocolInfo331::ITEM_COMPONENT_PACKET, ItemComponentPacket::class);
		$this->registerPacket331(ProtocolInfo331::CREATIVE_CONTENT_PACKET, CreativeContentPacket::class);
	}
}
