<?php

namespace PrinxIsLeqit\CloudSystem;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class CloudSystemListener implements Listener {
    private $plugin;

    public function __construct(CloudSystem $plugin) {
        $this->plugin = $plugin;
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    public function onPacketReceive(DataPacketReceiveEvent $ev) {
        $pk = $ev->getPacket();
        $player = $ev->getPlayer();
        if ($pk instanceof ModalFormResponsePacket) {
            $id = $pk->formId;
            $data = json_decode($pk->formData);
            if ($id == 1) {
                if ($data !== NULL) {
                    if ($data == 0) {
                        $player->sendMessage($this->plugin->prefix . '§f soon!');
                    } elseif ($data == 1) {
                        $this->plugin->sendCreateSign($player);
                    } elseif ($data == 2) {
                        $this->plugin->sendServerStatus($player);
                    }
                }
            } elseif ($id == 2) {

            } elseif ($id == 3) {
                if ($data !== NULL) {
                    $fdata = [];

                    $fdata['title'] = '§bCreate sign';
                    $fdata['content'] = [];
                    $fdata['type'] = 'custom_form';

                    $fdata['content'][] = ["type" => "label", "text" => '§fIP: ' . $data[0] . "\nPort: " . $data[1] . "\nName: " . $data[2] . "\n§4If correct, press send!"];

                    $this->plugin->ip = $data[0];
                    $this->plugin->port = $data[1];
                    $this->plugin->name = $data[2];

                    $pk = new ModalFormRequestPacket();
                    $pk->formId = 5;
                    $pk->formData = json_encode($fdata);

                    $player->sendDataPacket($pk);
                }
            } elseif ($id == 4) {
                if ($data !== NULL) {
                    $player->sendMessage($this->plugin->prefix . '§f Please wait');
                    $query = new MinecraftQuery();
                    $query->connect($data[0], intval($data[1]));

                    if ($query->isOnline()) {
                        $status = $query->getInfo();

                        $text = "§fStatus: §aOnline\n§fMOTD: " . $status['HostName'] . "§r\n§fVersion: §e" . $status['Version'] . "\n§fServersoftware: §e" . $status['ServerEngine'] . "\n§fMap: §e" . $status['Map'] . "\n§fPlayers: §e" . $status['Players'] . " / " . $status['MaxPlayers'] . "\n§fWhiteList: §e" . $status['WhiteList'];

                        $fdata = [];

                        $fdata['title'] = '§bServer status';
                        $fdata['content'] = [];
                        $fdata['type'] = 'custom_form';

                        $fdata['content'][] = ["type" => "label", "text" => $text];

                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 25793;
                        $pk->formData = json_encode($fdata);

                        $player->sendDataPacket($pk);

                    } else {

                        $fdata = [];

                        $fdata['title'] = '§bServer status';
                        $fdata['content'] = [];
                        $fdata['type'] = 'custom_form';

                        $fdata['content'][] = ["type" => "label", "text" => '§fStatus: §4Offline'];

                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 25793;
                        $pk->formData = json_encode($fdata);

                        $player->sendDataPacket($pk);

                    }
                }
            } elseif ($id == 5) {
                if ($data !== NULL) {
                    $this->plugin->player = $player->getName();

                    $query = new MinecraftQuery();
                    $query->connect($this->plugin->ip, $this->plugin->port);

                    if ($query->isOnline()) {
                        $player->sendMessage($this->plugin->prefix . '§f Please touch the sign you would like to register!');
                    } else {
                        $player->sendMessage($this->plugin->prefix . '§f Server ist offline!');
                        $this->plugin->player = "";
                    }
                }
            }
        }
    }

    public function onInteract(PlayerInteractEvent $ev) {
        $player = $ev->getPlayer();

        if ($player->getName() === $this->plugin->player) {
            $block = $ev->getBlock();
            $tile = $player->getLevel()->getTile($block);

            if ($tile instanceof Sign) {

                $query = new MinecraftQuery();
                $query->connect($this->plugin->ip, $this->plugin->port);

                if ($query->isOnline()) {
                    $info = $query->getInfo();
                    $playercount = $info['Players'];
                    $mplayers = $info['MaxPlayers'];
                    $tile->setText(
                        $this->plugin->name,
                        '§7[§aJoin§7]',
                        '§e' . $playercount . " §7/ §e" . $mplayers,
                        '§7ooO'
                    );

                    $cvar = $tile->x . ":" . $tile->y . ":" . $tile->z;
                    $ip = $this->plugin->ip;
                    $port = $this->plugin->port;
                    $name = $this->plugin->name;

                    $signs = $this->plugin->cfg->get('signs');
                    $signs[TextFormat::clean($name)] = ['name' => $name, 'ip' => $ip, 'port' => $port, 'coords' => $cvar, 'level' => $tile->getLevel()->getName()];

                    $this->plugin->cfg->set('signs', $signs);
                    $this->plugin->cfg->save();

                    $this->plugin->player = "";

                    $player->sendMessage($this->plugin->prefix . "§f Sign registered successfully!");

                } else {
                    $player->sendMessage($this->plugin->prefix . '§f Server is offline!');
                    $this->plugin->player = "";
                }
            }
        } else {
            $block = $ev->getBlock();
            $tile = $player->getLevel()->getTile($block);

            if ($tile instanceof Sign) {
                $name = $tile->getLine(0);

                $sdata = $this->plugin->cfg->get('signs');

                $data = $sdata[TextFormat::clean($name)];

                $ip = $data['ip'];
                $port = $data['port'];

                if($tile->getLine(1) === '§7[§aJoin§7]'){
                    $player->transfer($ip, $port);
                }else{
                    $player->sendMessage($this->plugin->prefix.'§f Server is full or offline!');
                }
            }
        }
    }
}