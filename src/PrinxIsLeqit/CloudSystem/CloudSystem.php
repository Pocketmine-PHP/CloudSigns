<?php

namespace PrinxIsLeqit\CloudSystem;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class CloudSystem extends PluginBase {
    public $prefix = '§a[CloudSystem]';
    public $cfg;

    public $threecount = 1;

    public $player = "";
    public $ip;
    public $port;
    public $name;

    public $listener;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        if (!file_exists($this->getDataFolder() . 'config.yml')) {
            $this->initConfig();
        }

        $this->cfg = new Config($this->getDataFolder() . 'config.yml', Config::YAML);

        $this->getServer()->getScheduler()->scheduleRepeatingTask(new CloudSystemTask($this), $this->cfg->get('refresh') * 20);

        $this->listener = new CloudSystemListener($this);

        $this->getLogger()->info($this->prefix . '§ageladen§f!');
    }

    public function initConfig() {
        $this->cfg = new Config($this->getDataFolder() . 'config.yml', Config::YAML);
        $this->cfg->set('refresh', 1);
        $this->cfg->set('signs', []);
        $this->cfg->save();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command == 'cloud') {
            if (empty($args[0])) {
                $sender->sendMessage($this->prefix . "\n§r: §r");
                return FALSE;
            } else {
                if ($sender instanceof Player) {
                    if ($args[0] == 'help') {
                        if ($sender->hasPermission('cloud.help')) {
                            $sender->sendMessage($this->prefix . "\n§bGUI: §f/cloud gui\n§bRestart server: §f /cloud restart\n§bCreate sign: §f/cloud sign");
                        } else {
                            $sender->sendMessage($this->prefix . '§f');
                        }
                    } elseif ($args[0] == 'gui') {
                        if ($sender->hasPermission('cloud.admin')) {
                            $this->sendGui($sender);
                        } else {
                            $sender->sendMessage($this->prefix . '§f No permission!');
                        }
                    }  elseif ($args[0] == 'restart') {
                        if ($sender->hasPermission('cloud.restart')) {
                            $this->sendRestart($sender);
                        } else {
                            $sender->sendMessage($this->prefix . '§f No permission!');
                        }
                    } elseif ($args[0] == 'sign') {
                        if ($sender->hasPermission('cloud.createsign')) {
                            $this->sendCreateSign($sender);
                        } else {
                            $sender->sendMessage($this->prefix . '§f No permission!');
                        }
                    }
                }
            }
            return TRUE;
        }
    }

    public function sendGui(Player $player){
        $fdata = [];

        $fdata['title'] = '§cRestart';
        $fdata['buttons'] = [];
        $fdata['content'] = "";
        $fdata['type'] = 'form';

        $fdata['buttons'][] = ['text' => 'Restart server'];
        $fdata['buttons'][] = ['text' => 'Create sign'];
        $fdata['buttons'][] = ['text' => 'Serverstatus'];

        $pk = new ModalFormRequestPacket();
        $pk->formId = 1;
        $pk->formData = json_encode($fdata);

        $player->sendDataPacket($pk);
    }

    public function sendRestart(Player $player){
        $fdata = [];

        $fdata['title'] = '§bRestart';
        $fdata['content'] = [];
        $fdata['type'] = 'custom_form';

        $fdata['content'][] = ["type" => "input", "text" => '§fServer IP', "placeholder" => '', "default" => ''];
        $fdata['content'][] = ["type" => "input", "text" => '§fServer Port', "placeholder" => '', "default" => ''];

        $pk = new ModalFormRequestPacket();
        $pk->formId = 2;
        $pk->formData = json_encode($fdata);

        $player->sendDataPacket($pk);
    }

    public function sendCreateSign(Player $player){
        $fdata = [];

        $fdata['title'] = '§bCreate sign';
        $fdata['content'] = [];
        $fdata['type'] = 'custom_form';

        $fdata['content'][] = ["type" => "input", "text" => '§fServer IP', "placeholder" => '', "default" => ''];
        $fdata['content'][] = ["type" => "input", "text" => '§fServer Port', "placeholder" => '', "default" => ''];
        $fdata['content'][] = ["type" => "input", "text" => '§fServer Name', "placeholder" => '', "default" => ''];

        $pk = new ModalFormRequestPacket();
        $pk->formId = 3;
        $pk->formData = json_encode($fdata);

        $player->sendDataPacket($pk);
    }

    public function sendServerStatus(Player $player){
        $fdata = [];

        $fdata['title'] = '§bServer status';
        $fdata['content'] = [];
        $fdata['type'] = 'custom_form';

        $fdata['content'][] = ["type" => "input", "text" => '§fServer IP', "placeholder" => '', "default" => ''];
        $fdata['content'][] = ["type" => "input", "text" => '§fServer Port', "placeholder" => '', "default" => ''];

        $pk = new ModalFormRequestPacket();
        $pk->formId = 4;
        $pk->formData = json_encode($fdata);

        $player->sendDataPacket($pk);
    }
}
